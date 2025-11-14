<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Validator;
use App\Services\RazorpayPayoutService;

class WithdrawalController extends Controller
{
    // list user's withdrawals
    public function index(Request $req)
    {
        try {
            $user = $req->user();
            $list = $user->withdrawals()->with('bankingDetail')->latest()->paginate(20);

            // Transform to hide sensitive info
            $list->getCollection()->transform(function ($w) {
                return [
                    'id' => $w->id,
                    'amount' => $w->amount,
                    'fee' => $w->fee,
                    'currency' => $w->currency,
                    'status' => $w->status,
                    'reference' => $w->reference,
                    'utr' => $w->utr,
                    'banking_detail' => [
                        'id' => $w->bankingDetail->id,
                        'bank_name' => $w->bankingDetail->bank_name,
                        'account_number_masked' => $w->bankingDetail->masked_account(),
                    ],
                    'created_at' => $w->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $list,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('withdrawal.index ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // create withdrawal
    public function store(Request $req)
    {
        $user = $req->user();

        // ✅ Use Validator facade
        $validator = Validator::make($req->all(), [
            'banking_detail_id' => 'required|integer|exists:banking_details,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();

        // ensure banking detail belongs to user
        $banking = $user->bankingDetails()->where('id', $payload['banking_detail_id'])->firstOrFail();

        $amount = (float) $payload['amount'];
        $fee = $this->calculateFee($amount); // implement your fee logic
        $total = round($amount + $fee, 2);

        DB::beginTransaction();
        try {
            // lock wallet row to avoid race conditions
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found',
                ], 422);
            }

            // debit funds safely
            try {
                $wallet->debit($total, 'Withdrawal request (pending)');
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient funds',
                ], 422);
            }

            // create withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'banking_detail_id' => $banking->id,
                'amount' => $amount,
                'fee' => $fee,
                'currency' => $banking->currency ?? 'PKR',
                'status' => 'processing',
                'reference' => Str::uuid(),
            ]);

            DB::commit();

            // Send notification to user
            app(\App\Services\FcmService::class)->sendToUser(
                $user,
                'Withdrawal Initiated',
                "Your withdrawal request of ₹{$amount} is being processed",
                [
                    'type' => 'withdrawal_initiated',
                    'withdrawal_id' => (string)$withdrawal->id,
                    'amount' => (string)$amount,
                ]
            );

            // Trigger RazorpayX payout
            try {
                $service = app(RazorpayPayoutService::class);
                $payout = $service->createPayout($withdrawal, $banking);

                $withdrawal->gateway = 'razorpay';
                $withdrawal->gateway_payout_id = $payout['id'] ?? null;
                $withdrawal->gateway_status = $payout['status'] ?? 'processing';
                $withdrawal->save();

                return response()->json([
                    'success' => true,
                    'data' => $withdrawal,
                ], 201);
            } catch (\Throwable $payoutEx) {
                \Log::error('withdrawal.payout '.$payoutEx->getMessage());

                // rollback funds to wallet and mark withdrawal failed
                DB::beginTransaction();
                try {
                    $wallet = $user->wallet()->lockForUpdate()->first();
                    $wallet->credit($total, 'Withdrawal failed refund');

                    $withdrawal->status = 'failed';
                    $withdrawal->failure_reason = $payoutEx->getMessage();
                    $withdrawal->save();

                    DB::commit();
                } catch (\Throwable $refundEx) {
                    DB::rollBack();
                    \Log::error('withdrawal.refund '.$refundEx->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Could not initiate payout',
                ], 502);
            }
        } catch (\Throwable $ex) {
            DB::rollBack();
            \Log::error('withdrawal.create ' . $ex->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error',
            ], 500);
        }
    }

    public function show(Request $req, $id)
    {
        $user = $req->user();
        $w = $user->withdrawals()->with('bankingDetail')->findOrFail($id);
        return response()->json(['success' => true, 'data' => [
            'id' => $w->id,
            'amount' => $w->amount,
            'fee' => $w->fee,
            'status' => $w->status,
            'reference' => $w->reference,
            'utr' => $w->utr,
            'banking_detail' => [
                'id' => $w->bankingDetail->id,
                'bank_name' => $w->bankingDetail->bank_name,
                'account_number_masked' => $w->bankingDetail->masked_account()
            ],
            'created_at' => $w->created_at
        ]]);
    }

    // user cancel (only pending)
    public function cancel(Request $req, $id)
    {
        $user = $req->user();
        $w = $user->withdrawals()->where('id', $id)->firstOrFail();
        if ($w->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Cannot cancel'], 422);
        }

        DB::beginTransaction();
        try {
            // refund the reserved amount to wallet (amount + fee)
            $wallet = $user->wallet()->lockForUpdate()->first();
            $refundAmount = $w->amount + $w->fee;
            $wallet->credit($refundAmount, 'Withdrawal cancelled refund');

            $w->update(['status' => 'cancelled', 'admin_note' => 'Cancelled by user']);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('withdrawal.cancel ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    // implement your fee logic here or move to a service
    protected function calculateFee(float $amount): float
    {
        // example: 1% min 10
        $fee = 0; //max(round($amount * 0.01, 2), 10.0);
        return $fee;
    }
}
