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
            $w->syncWalletTransactionNote('processing');

            // --- Synchronous provider call (replace callProvider with real logic) ---
            $providerResult = $this->callProvider($w);

            if ($providerResult['success']) {
                $w->update([
                    'status' => 'completed',
                    'reference' => $providerResult['tx_id'] ?? Str::upper(Str::random(10)),
                    'utr' => $providerResult['utr'] ?? ($providerResult['tx_id'] ?? $w->utr),
                    'processed_at' => now()
                ]);
                $w->syncWalletTransactionNote('completed');
                DB::commit();
                return redirect()->route('admin.withdrawals.show', $w->id)->with('success', 'Withdrawal paid successfully.');
            } else {
                // provider failed — mark rejected and refund
                $w->update([
                    'status' => 'rejected',
                    'admin_note' => $providerResult['message'] ?? 'Provider error',
                    'processed_at' => now()
                ]);
                $w->syncWalletTransactionNote('rejected');

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
            $w->syncWalletTransactionNote('rejected');

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
                'utr' => $transactionId ?: $w->utr,
                'processed_by' => $admin->id,
                'processed_at' => now()
            ]);
            $w->syncWalletTransactionNote('completed');

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

    // Export withdrawals to CSV
    public function export(Request $req)
    {
        $query = Withdrawal::with('user', 'bankingDetail');

        // Apply filters if any
        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        $withdrawals = $query->latest()->get();

        $fileName = 'withdrawals_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function() use ($withdrawals) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Withdrawal ID',
                'User Name',
                'User Phone',
                'Amount',
                'Fee',
                'Net Amount',
                'Status',
                'Payment Method',
                'Account Holder',
                'Account Number',
                'Bank Name',
                'IFSC Code',
                'UPI ID',
                'Reference/Transaction ID',
                'UTR',
                'Admin Note',
                'Requested At',
                'Processed At'
            ]);

            // CSV rows
            foreach ($withdrawals as $w) {
                $bankDetail = $w->bankingDetail;
                
                fputcsv($file, [
                    $w->id,
                    $w->user->name ?? 'N/A',
                    $w->user->phone ?? 'N/A',
                    number_format($w->amount, 2),
                    number_format($w->fee, 2),
                    number_format($w->amount - $w->fee, 2),
                    ucfirst($w->status),
                    $w->payment_method ?? 'N/A',
                    $bankDetail ? $bankDetail->account_holder_name : 'N/A',
                    $bankDetail ? $bankDetail->account_number : 'N/A',
                    $bankDetail ? $bankDetail->bank_name : 'N/A',
                    $bankDetail ? $bankDetail->ifsc_code : 'N/A',
                    $bankDetail ? $bankDetail->upi_id : 'N/A',
                    $w->reference ?? 'N/A',
                    $w->utr ?? 'N/A',
                    $w->admin_note ?? 'N/A',
                    $w->created_at,
                    $w->processed_at ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
