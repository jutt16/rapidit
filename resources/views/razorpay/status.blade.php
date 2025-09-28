{{-- resources/views/razorpay/status.blade.php --}}
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Status - RapidIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            background: #f6f7fb;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .status-icon {
            font-size: 72px;
            line-height: 1;
        }

        .muted {
            color: #6c757d;
        }

        .small-meta {
            font-size: .9rem;
            color: #444;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card p-4">
                    <div class="text-center mb-3">
                        @if(isset($status) && $status === 'success')
                        <div class="status-icon text-success">✔️</div>
                        <h2 class="mt-2">Payment Successful</h2>
                        <p class="muted">Thank you — your booking has been confirmed.</p>
                        @else
                        <div class="status-icon text-danger">❌</div>
                        <h2 class="mt-2">Payment Failed</h2>
                        <p class="muted">We couldn't process your payment. You can try again or contact support.</p>
                        @endif
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h5>Booking Details</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Booking ID</span>
                                <strong>#{{ $booking->id }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Name</span>
                                <span>{{ $booking->user->name ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Email</span>
                                <span>{{ $booking->user->email ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Phone</span>
                                <span>{{ $booking->user->phone ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Amount</span>
                                <strong>₹ {{ number_format($booking->amount, 2) }}</strong>
                            </li>
                        </ul>
                    </div>

                    @if(isset($payment))
                    <hr>
                    <div class="mb-3">
                        <h5>Transaction</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Transaction ID</span>
                                <strong>{{ $payment->razorpay_payment_id ?? '—' }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Order ID</span>
                                <span>{{ $payment->razorpay_order_id ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="small-meta">Status</span>
                                <span class="{{ ($payment->status ?? '') === 'paid' ? 'text-success' : 'text-danger' }}">
                                    {{ ucfirst($payment->status ?? $status) }}
                                </span>
                            </li>
                            @if(!empty($payment->meta))
                            @php
                            // try to decode meta safely
                            $meta = json_decode($payment->meta, true);
                            @endphp

                            @if(is_array($meta))
                            <li class="list-group-item">
                                <div class="small-meta mb-1">Payment Details</div>
                                <pre style="white-space:pre-wrap;word-break:break-word;font-size:.85rem;margin:0;">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </li>
                            @endif
                            @endif
                        </ul>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-outline-primary">
                            View Booking
                        </a>

                        @if(isset($status) && $status !== 'success')
                        <a href="{{ route('razorpay.pay', $booking->id) }}" class="btn btn-warning">
                            Try Again
                        </a>
                        @else
                        <a href="{{ url('/') }}" class="btn btn-secondary">
                            Home
                        </a>
                        @endif
                    </div>
                </div>

                <p class="text-center mt-3 muted small">If you need help, contact support@rapidit.in</p>
            </div>
        </div>
    </div>

    <!-- optional bootstrap js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>