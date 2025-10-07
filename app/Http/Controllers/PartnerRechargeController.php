<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Recharge;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PartnerRechargeController extends Controller
{
    private $razorpayApi;

    public function __construct()
    {
        $this->razorpayApi = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_SECRET'));
    }

    /**
     * Show recharge payment page.
     */
    public function index($userId): View|RedirectResponse
    {
        $user = User::findOrFail($userId);

        // ✅ Ensure wallet exists
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $amount = request()->get('amount', 100); // Default ₹100

        try {
            $orderData = [
                'receipt' => 'wallet_recharge_' . $user->id . '_' . now()->timestamp,
                'amount' => $amount * 100, // amount in paise
                'currency' => 'INR',
                'payment_capture' => 1
            ];

            $razorpayOrder = $this->razorpayApi->order->create($orderData);
            $orderId = $razorpayOrder['id'];

            // ✅ Record initial recharge request
            Recharge::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_status' => 'pending',
                'order_id' => $orderId,
                'gateway' => 'razorpay'
            ]);

        } catch (Exception $e) {
            Log::error('Razorpay order creation failed (Recharge): ' . $e->getMessage());
            return redirect()->back()->with("error", "Payment gateway error. Please try again.");
        }

        return view('razorpay.recharge', compact('user', 'amount', 'orderId'));
    }

    /**
     * Handle recharge callback.
     */
    public function handleCallback(Request $request, $encodedUser): RedirectResponse
    {
        try {
            $userId = base64_decode($encodedUser);
            $user = User::findOrFail($userId);
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            $input = $request->all();
            Log::info('Razorpay Recharge Callback:', $input);

            if (empty($input['razorpay_payment_id']) || empty($input['razorpay_signature'])) {
                throw new Exception('Missing payment parameters');
            }

            $attributes = [
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_order_id'   => $input['razorpay_order_id'] ?? null,
                'razorpay_signature'  => $input['razorpay_signature']
            ];

            if (!empty($input['razorpay_order_id'])) {
                $this->razorpayApi->utility->verifyPaymentSignature($attributes);
            }

            $payment = $this->razorpayApi->payment->fetch($input['razorpay_payment_id']);
            $amount = $payment->amount / 100;

            $recharge = Recharge::where('order_id', $payment->order_id)->first();

            if ($payment->status === 'captured') {
                // ✅ Credit wallet and mark recharge success
                $wallet->balance += $amount;
                $wallet->save();

                $recharge?->update([
                    'payment_status' => 'success',
                    'transaction_id' => $payment->id,
                    'meta' => $payment->toArray(),
                ]);

                // ✅ Add Wallet Transaction record
                WalletTransaction::create([
                    'wallet_id'   => $wallet->id,
                    'type'        => 'credit',
                    'amount'      => $amount,
                    'description' => 'Wallet recharge via Razorpay (#' . $payment->id . ')',
                ]);

                return redirect()->route('recharge.status', $user->id)
                    ->with('success', 'Wallet recharged successfully!');
            } else {
                // ❌ Failed transaction
                $recharge?->update([
                    'payment_status' => 'failed',
                    'transaction_id' => $payment->id,
                    'meta' => $payment->toArray(),
                ]);

                return redirect()->route('recharge.status', $user->id)
                    ->with('error', 'Payment failed. Please try again.');
            }
        } catch (Exception $e) {
            Log::error('Recharge Callback Error: ' . $e->getMessage());
            return redirect()->route('recharge.status', base64_decode($encodedUser))
                ->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Show recharge status page.
     */
    public function status($userId): View
    {
        $user = User::findOrFail($userId);
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        $lastRecharge = Recharge::where('user_id', $userId)->latest()->first();
        $status = $lastRecharge?->payment_status ?? 'failed';

        return view('razorpay.recharge-status', compact('user', 'wallet', 'lastRecharge', 'status'));
    }
}
