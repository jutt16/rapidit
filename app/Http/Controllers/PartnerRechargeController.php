<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
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
                'amount' => $amount * 100, // paise
                'currency' => 'INR',
                'payment_capture' => 1
            ];

            $razorpayOrder = $this->razorpayApi->order->create($orderData);
            $orderId = $razorpayOrder['id'];
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

            if ($payment->status === 'captured') {
                // ✅ Credit wallet
                $wallet->credit($amount, 'Wallet recharge via Razorpay (#' . $payment->id . ')');

                return redirect()->route('recharge.status', $user->id)
                    ->with('success', 'Wallet recharged successfully!');
            } else {
                // Record failed transaction
                $wallet->transactions()->create([
                    'type' => 'credit',
                    'amount' => $amount,
                    'description' => 'Recharge failed (' . $payment->status . ')',
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
        $lastTxn = $wallet->transactions()->latest()->first();
        $status = $lastTxn && $lastTxn->type === 'credit' ? 'success' : 'failed';

        return view('razorpay.recharge-status', compact('user', 'wallet', 'lastTxn', 'status'));
    }
}
