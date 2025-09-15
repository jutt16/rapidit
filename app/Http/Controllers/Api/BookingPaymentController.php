<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingPaymentController extends Controller
{
    protected $api;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID'));
        $this->keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET'));
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Initiate payment for a booking.
     * If booking->payment_method == 'cod' -> create record (status pending).
     * If 'razorpay' -> create Payment Link, return URL.
     */
    public function pay(Request $request, Booking $booking)
    {
        $user = $request->user();

        // ensure booking belongs to authenticated user
        if ($booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // determine method (use booking.payment_method primarily)
        $method = $booking->payment_method ?? $request->input('payment_method');
        if (!in_array($method, ['cod', 'razorpay'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payment method'], 422);
        }

        // amount (use booking total_amount)
        $amount = $booking->total_amount;
        if (! $amount || $amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid amount on booking'], 422);
        }

        if ($method === 'cod') {
            // Record COD payment (pending until cash collected)
            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'cod',
                'amount' => $amount,
                'status' => 'pending', // pending until collected
                'meta' => ['note' => 'COD — pending cash collection'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'COD recorded. Mark as paid on collection.',
                'data' => $payment
            ], 201);
        }

        // RAZORPAY path: create payment link
        $amountPaise = intval(round($amount * 100));
        $referenceId = 'booking_' . $booking->id . '_' . Str::random(6);
        $callbackUrl = route('payments.callback'); // GET endpoint

        $payload = [
            'amount' => $amountPaise,
            'currency' => 'INR',
            'accept_partial' => false,
            'reference_id' => $referenceId,
            'description' => "Payment for booking #{$booking->id}",
            'customer' => [
                'name' => $booking->user->name ?? '',
                'email' => $booking->user->email ?? '',
                'contact' => $booking->user->phone ?? '',
            ],
            'notify' => [
                'sms' => false,
                'email' => true,
            ],
            'callback_url' => $callbackUrl,
            'callback_method' => 'get',
            'notes' => [
                'booking_id' => $booking->id,
            ],
        ];

        try {
            $link = $this->api->paymentLink->create($payload);

            // Create booking_payment record
            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'razorpay',
                'amount' => $amount,
                'razorpay_link_id' => $link['id'] ?? null,
                'razorpay_short_url' => $link['short_url'] ?? ($link['long_url'] ?? null),
                'razorpay_link_status' => $link['status'] ?? null,
                'razorpay_payment_link_reference_id' => $referenceId,
                'status' => 'pending',
                'meta' => $link,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment link created',
                'data' => [
                    'payment_local_id' => $payment->id,
                    'payment_link_id' => $link['id'] ?? null,
                    'payment_url' => $link['short_url'] ?? ($link['long_url'] ?? null),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Razorpay create link error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create payment link', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Callback (user redirect) - GET. Razorpay will redirect the WebView to this URL with query params:
     * razorpay_payment_id, razorpay_payment_link_id, razorpay_payment_link_reference_id,
     * razorpay_payment_link_status, razorpay_signature
     */
    public function callback(Request $request)
    {
        $paymentId = $request->query('razorpay_payment_id');
        $linkId    = $request->query('razorpay_payment_link_id');
        $refId     = $request->query('razorpay_payment_link_reference_id');
        $linkStatus = $request->query('razorpay_payment_link_status');
        $signature = $request->query('razorpay_signature');

        $payment = null;
        if ($linkId) {
            $payment = BookingPayment::where('razorpay_link_id', $linkId)->first();
        }
        if (!$payment && $refId) {
            $payment = BookingPayment::where('razorpay_payment_link_reference_id', $refId)->first();
        }

        if (!$payment) {
            Log::warning('Payment callback: no payment record found', $request->query());
            // Return a simple HTML page user-friendly for WebView
            return response('<h3>Payment record not found</h3><p>Please contact support.</p>', 404);
        }

        // Build string for signature verification: link_id|reference_id|link_status|payment_id
        $payloadString = implode('|', [
            $linkId ?? '',
            $refId ?? '',
            $linkStatus ?? '',
            $paymentId ?? ''
        ]);

        $generated = hash_hmac('sha256', $payloadString, $this->keySecret);

        if (!hash_equals($generated, (string)$signature)) {
            Log::warning('Payment callback signature mismatch', ['expected' => $generated, 'received' => $signature, 'payload' => $payloadString]);
            $payment->update([
                'razorpay_link_status' => $linkStatus,
                'status' => 'failed',
                'meta' => array_merge($payment->meta ?? [], $request->query()),
            ]);
            return response('<h3>Payment verification failed</h3><p>Please try again.</p>', 400);
        }

        // Signature ok — update record (idempotent)
        $payment->update([
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
            'razorpay_link_status' => $linkStatus,
            'status' => $linkStatus === 'paid' ? 'paid' : ($linkStatus === 'expired' ? 'expired' : $payment->status),
            'meta' => array_merge($payment->meta ?? [], $request->query()),
        ]);

        if ($payment->status === 'paid') {
            $booking = $payment->booking;
            if ($booking) {
                $booking->update(['status' => 'confirmed']);
            }
        }

        // Return a simple HTML success page for WebView
        return response('<h3>Payment processed</h3><p>Status: ' . htmlentities($payment->status) . '</p>', 200);
    }

    /**
     * Webhook endpoint for server->server events.
     * Verify header X-Razorpay-Signature using RAZORPAY_WEBHOOK_SECRET.
     */
    public function webhook(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('x-razorpay-signature');

        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        if (!$webhookSecret) {
            Log::error('Webhook secret not configured');
            return response()->json(['success' => false, 'message' => 'Webhook secret not configured'], 500);
        }

        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        if (!hash_equals($expected, (string)$signature)) {
            Log::warning('Invalid webhook signature', ['expected' => $expected, 'received' => $signature]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($rawBody, true);
        $event = $payload['event'] ?? null;
        $data = $payload['payload'] ?? [];

        try {
            // payment_link.* events
            if (str_starts_with($event, 'payment_link.')) {
                $entity = $data['payment_link']['entity'] ?? null;
                if ($entity) {
                    $linkId = $entity['id'] ?? null;
                    $status = $entity['status'] ?? null;
                    $payments = $entity['payments'] ?? [];
                    $paymentId = null;
                    if (!empty($payments) && isset($payments[0]['id'])) {
                        $paymentId = $payments[0]['id'];
                    }

                    $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    if ($record) {
                        // idempotent update
                        $record->update([
                            'razorpay_link_status' => $status,
                            'razorpay_payment_id' => $paymentId ?? $record->razorpay_payment_id,
                            'status' => $status === 'paid' ? 'paid' : ($status === 'expired' ? 'expired' : $record->status),
                            'meta' => array_merge($record->meta ?? [], $entity),
                        ]);
                        if ($status === 'paid' && $record->booking) {
                            $record->booking->update(['status' => 'confirmed']);
                        }
                    }
                }
            }

            // payment.* events (fallback)
            if (str_starts_with($event, 'payment.')) {
                $entity = $data['payment']['entity'] ?? null;
                if ($entity) {
                    $paymentId = $entity['id'] ?? null;
                    $status = $entity['status'] ?? null; // captured, failed, authorized...
                    // try find by payment id
                    $record = BookingPayment::where('razorpay_payment_id', $paymentId)->first();
                    if (!$record) {
                        // maybe link id is inside notes
                        $linkId = $entity['notes']['payment_link_id'] ?? null;
                        $record = BookingPayment::where('razorpay_link_id', $linkId)->first();
                    }
                    if ($record) {
                        $record->update([
                            'razorpay_payment_id' => $paymentId,
                            'status' => $status === 'captured' ? 'paid' : ($status === 'failed' ? 'failed' : $record->status),
                            'meta' => array_merge($record->meta ?? [], $entity),
                        ]);
                        if ($status === 'captured' && $record->booking) {
                            $record->booking->update(['status' => 'confirmed']);
                        }
                    }
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), ['payload' => $payload]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment status for a booking (for frontend polling)
     */
    public function status(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = BookingPayment::where('booking_id', $booking->id)->latest()->first();

        return response()->json(['success' => true, 'data' => $payment]);
    }
}
