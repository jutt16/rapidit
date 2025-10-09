<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // if using Http client for provider
use App\Models\Withdrawal;
use Illuminate\Support\Str;

class WithdrawalAdminController extends Controller
{
    // 1. List withdrawals
    public function index(Request $req)
    {
        $withdrawals = Withdrawal::with('user','bankingDetail')->latest()->paginate(25);
        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    // 2. Show single withdrawal
    public function show(Request $req, $id)
    {
        $w = Withdrawal::with('user','bankingDetail')->findOrFail($id);
        return view('admin.withdrawals.show', compact('w'));
    }

    // 3. Approve and process payout synchronously
    public function approve(Request $req, $id)
    {
        $admin = $req->user();

        DB::beginTransaction();
        try {
            // lock withdrawal row
            $w = Withdrawal::lockForUpdate()->findOrFail($id);

            if ($w->status !== 'pending') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Withdrawal status must be pending to approve.');
            }

            // mark as processing and record who started it
            $w->update(['status' => 'processing', 'processed_by' => $admin->id]);

            // --- Synchronous provider call (replace callProvider with real logic) ---
            $providerResult = $this->callProvider($w);

            if ($providerResult['success']) {
                $w->update([
                    'status' => 'completed',
                    'reference' => $providerResult['tx_id'] ?? Str::upper(Str::random(10)),
                    'processed_at' => now()
                ]);
                DB::commit();
                return redirect()->route('admin.withdrawals.show', $w->id)->with('success', 'Withdrawal paid successfully.');
            } else {
                // provider failed — mark rejected and refund
                $w->update([
                    'status' => 'rejected',
                    'admin_note' => $providerResult['message'] ?? 'Provider error',
                    'processed_at' => now()
                ]);

                // refund reserved funds back to user's wallet
                $wallet = $w->user->wallet()->lockForUpdate()->first();
                if ($wallet) {
                    $refundAmount = $w->amount + $w->fee;
                    $wallet->credit($refundAmount, 'Refund for failed withdrawal #' . $w->id);
                }

                DB::commit();
                return redirect()->route('admin.withdrawals.show', $w->id)->with('error', 'Provider failed: ' . ($providerResult['message'] ?? 'Unknown'));
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('admin.withdrawal.approve: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }

    // 4. Reject (admin rejects before payout; refund immediately)
    public function reject(Request $req, $id)
    {
        $admin = $req->user();
        $reason = $req->input('reason', 'Rejected by admin');

        DB::beginTransaction();
        try {
            $w = Withdrawal::lockForUpdate()->findOrFail($id);

            if (!in_array($w->status, ['pending','processing'])) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Cannot reject withdrawal in its current status.');
            }

            $w->update([
                'status' => 'rejected',
                'admin_note' => $reason,
                'processed_by' => $admin->id,
                'processed_at' => now()
            ]);

            // Refund
            $wallet = $w->user->wallet()->lockForUpdate()->first();
            if ($wallet) {
                $refundAmount = $w->amount + $w->fee;
                $wallet->credit($refundAmount, 'Refund for rejected withdrawal #' . $w->id);
            }

            DB::commit();
            return redirect()->route('admin.withdrawals.show', $w->id)->with('success', 'Withdrawal rejected and refunded.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('admin.withdrawal.reject: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }

    // 5. Mark Paid (use when payout done manually outside system)
    public function markPaid(Request $req, $id)
    {
        $admin = $req->user();
        $transactionId = $req->input('transaction_id');

        DB::beginTransaction();
        try {
            $w = Withdrawal::lockForUpdate()->findOrFail($id);
            if (!in_array($w->status, ['pending','approved','processing'])) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Cannot mark paid for current status.');
            }

            $w->update([
                'status' => 'completed',
                'reference' => $transactionId,
                'processed_by' => $admin->id,
                'processed_at' => now()
            ]);

            DB::commit();
            return redirect()->route('admin.withdrawals.show', $w->id)->with('success', 'Withdrawal marked as paid.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('admin.withdrawal.markPaid: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }

    /**
     * Replace this with real provider integration.
     * Return ['success' => true, 'tx_id' => '...'] OR ['success' => false, 'message' => 'error...']
     */
    protected function callProvider(Withdrawal $w): array
    {
        // Example synchronous stub (simulate a success)
        // For real providers, use Http::post(...) or Guzzle request and handle responses/timeouts/retries.
        // Be cautious: provider requests can be slow; handle timeouts and show appropriate admin warnings.
        try {
            // Example: fake a provider call -> success
            return ['success' => true, 'tx_id' => 'BANK-' . strtoupper(Str::random(8))];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
