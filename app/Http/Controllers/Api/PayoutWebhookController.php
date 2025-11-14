<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\RazorpayPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PayoutWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log entire payload safely
        Log::info('Razorpay webhook called', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);
        $body = file_get_contents('php://input'); // 100% raw
        $signature = $request->header('x-razorpay-signature');
        $secret = config('services.razorpay.webhook_secret');

        Log::info('Raw webhook body:', [$body]);

        try {
            $api = new Api(config('services.razorpay.key_id'), config('services.razorpay.key_secret'));
            $api->utility->verifyWebhookSignature($body, $signature, $secret);
            Log::info('Webhook signature verified ✅');
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Invalid signature ❌', [
                'error' => $e->getMessage(),
                'raw_body' => $body,
                'received_signature' => $signature,
                'secret' => $secret,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // if (!$this->isValidSignature($body, $signature, $secret)) {
        //     Log::info('Signature not valid');
        //     return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        // }

        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $payout = $payload['payload']['payout']['entity'] ?? null;
        if (!$payout) {
            return response()->json(['success' => true]);
        }

        $payoutId = $payout['id'] ?? null;
        $status = $payout['status'] ?? null; // initiated, processing, processed, reversed, cancelled, failed
        $utr = $payout['utr'] ?? null;
        $notes = $payout['notes'] ?? [];
        $withdrawalId = $notes['withdrawal_id'] ?? null;

        $withdrawal = null;
        if ($withdrawalId) {
            $withdrawal = Withdrawal::where('id', $withdrawalId)->first();
        }
        if (!$withdrawal && $payoutId) {
            $withdrawal = Withdrawal::where('gateway_payout_id', $payoutId)->first();
        }
        if (!$withdrawal) {
            return response()->json(['success' => true]);
        }

        // Update statuses and handle refunds idempotently
        DB::beginTransaction();
        try {
            $withdrawal->gateway_status = $status;
            $withdrawal->gateway_payout_id = $payoutId ?: $withdrawal->gateway_payout_id;
            $withdrawal->utr = $utr ?: $withdrawal->utr;

            if (in_array($status, ['processed', 'credited'])) {
                if ($withdrawal->status !== 'completed') {
                    $withdrawal->status = 'completed';
                    $withdrawal->processed_at = now();

                    // Send success notification to user
                    $user = $withdrawal->user;
                    if ($user) {
                        app(\App\Services\FcmService::class)->sendToUser(
                            $user,
                            'Withdrawal Successful',
                            "₹{$withdrawal->amount} has been credited to your bank account",
                            [
                                'type' => 'withdrawal_completed',
                                'withdrawal_id' => (string)$withdrawal->id,
                                'amount' => (string)$withdrawal->amount,
                            ]
                        );
                    }
                }
            } elseif (in_array($status, ['failed', 'reversed', 'cancelled'])) {
                if (!in_array($withdrawal->status, ['failed', 'cancelled'])) {
                    // refund amount + fee
                    $wallet = $withdrawal->user->wallet()->lockForUpdate()->first();
                    $wallet->credit($withdrawal->amount + $withdrawal->fee, 'Payout failed refund');
                    $withdrawal->status = 'failed';
                    $withdrawal->failure_reason = $payout['failure_reason'] ?? ($payload['contains'][0] ?? 'Payout failed');

                    // Send failure notification to user
                    $user = $withdrawal->user;
                    if ($user) {
                        app(\App\Services\FcmService::class)->sendToUser(
                            $user,
                            'Withdrawal Failed',
                            "Your withdrawal of ₹{$withdrawal->amount} has failed. Amount refunded to wallet.",
                            [
                                'type' => 'withdrawal_failed',
                                'withdrawal_id' => (string)$withdrawal->id,
                                'amount' => (string)$withdrawal->amount,
                            ]
                        );
                    }
                }
            }

            $withdrawal->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('payout.webhook ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    protected function isValidSignature(string $payload, ?string $header, ?string $secret): bool
    {
        if (!$header || !$secret) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $header);
    }

    /**
     * Manual sync method to check payout status from Razorpay
     * This can be called when webhook is not working or for testing
     */
    public function syncStatus(Request $request, $withdrawalId)
    {
        try {
            $withdrawal = Withdrawal::findOrFail($withdrawalId);

            if (!$withdrawal->gateway_payout_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No gateway payout ID found'
                ], 400);
            }

            $service = app(RazorpayPayoutService::class);
            $payoutData = $service->fetchPayoutStatus($withdrawal->gateway_payout_id);

            $status = $payoutData['status'] ?? null;

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not fetch payout status'
                ], 400);
            }

            // Update statuses using same logic as webhook
            DB::beginTransaction();
            try {
                $withdrawal->gateway_status = $status;
                $withdrawal->gateway_payout_id = $payoutData['id'] ?? $withdrawal->gateway_payout_id;
                $withdrawal->utr = $payoutData['utr'] ?? $withdrawal->utr;

                if (in_array($status, ['processed', 'credited'])) {
                    if ($withdrawal->status !== 'completed') {
                        $withdrawal->status = 'completed';
                        $withdrawal->processed_at = now();

                        // Send success notification to user
                        $user = $withdrawal->user;
                        if ($user) {
                            app(\App\Services\FcmService::class)->sendToUser(
                                $user,
                                'Withdrawal Successful',
                                "₹{$withdrawal->amount} has been credited to your bank account",
                                [
                                    'type' => 'withdrawal_completed',
                                    'withdrawal_id' => (string)$withdrawal->id,
                                    'amount' => (string)$withdrawal->amount,
                                ]
                            );
                        }
                    }
                } elseif (in_array($status, ['failed', 'reversed', 'cancelled'])) {
                    if (!in_array($withdrawal->status, ['failed', 'cancelled'])) {
                        // refund amount + fee
                        $wallet = $withdrawal->user->wallet()->lockForUpdate()->first();
                        $wallet->credit($withdrawal->amount + $withdrawal->fee, 'Payout failed refund');
                        $withdrawal->status = 'failed';
                        $withdrawal->failure_reason = $payoutData['failure_reason'] ?? 'Payout failed';

                        // Send failure notification to user
                        $user = $withdrawal->user;
                        if ($user) {
                            app(\App\Services\FcmService::class)->sendToUser(
                                $user,
                                'Withdrawal Failed',
                                "Your withdrawal of ₹{$withdrawal->amount} has failed. Amount refunded to wallet.",
                                [
                                    'type' => 'withdrawal_failed',
                                    'withdrawal_id' => (string)$withdrawal->id,
                                    'amount' => (string)$withdrawal->amount,
                                ]
                            );
                        }
                    }
                }

                $withdrawal->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Status synced successfully',
                    'data' => [
                        'id' => $withdrawal->id,
                        'status' => $withdrawal->status,
                        'gateway_status' => $withdrawal->gateway_status,
                        'utr' => $withdrawal->utr,
                    ]
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('payout.sync: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync status: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('payout.sync: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
