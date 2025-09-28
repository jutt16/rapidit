<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RapidIt - Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-razorpay {
            background: #ff7529;
            color: white;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 18px;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .btn-razorpay:hover {
            background: #e06622;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center bg-dark text-white">
                        <h3 class="mb-0">Confirm Your Payment</h3>
                    </div>
                    <div class="card-body">
                        {{-- Messages --}}
                        @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        {{-- Booking Details --}}
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Booking ID:</strong> <span>#{{ $booking->id }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Name:</strong> <span>{{ $booking->user->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Email:</strong> <span>{{ $booking->user->email ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Phone:</strong> <span>{{ $booking->user->phone ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Amount:</strong> <span><b>₹ {{ number_format($booking->amount, 2) }}</b></span>
                            </li>
                        </ul>

                        {{-- Payment Button --}}
                        <button id="rzp-button" class="btn btn-razorpay w-100">Confirm & Pay ₹{{ $booking->amount }}</button>

                        {{-- Manual verification form (hidden) --}}
                        <form id="verification-form" action="{{ route('razorpay.verify') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
                            <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
                            <input type="hidden" name="razorpay_signature" id="rzp_signature">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const options = {
            "key": "{{ env('RAZORPAY_KEY_ID') }}",
            "amount": "{{ $booking->amount * 100 }}", // in paise
            "currency": "INR",
            "name": "RapidIt.in",
            "description": "Payment for Booking #{{ $booking->id }}",
            "image": "https://www.rapidit.in/frontTheme/images/logo.png",
            "order_id": "{{ $orderId }}", // Important: order_id from server
            "handler": function(response) {
                // This handler will be called when payment is successful
                console.log('Payment successful:', response);

                // Set the hidden form values
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                document.getElementById('rzp_order_id').value = response.razorpay_order_id;
                document.getElementById('rzp_signature').value = response.razorpay_signature;

                // Submit the form for server-side verification
                document.getElementById('verification-form').submit();
            },
            "prefill": {
                "name": "{{ $booking->user->name }}",
                "email": "{{ $booking->user->email ?? 'test@example.com' }}",
                "contact": "{{ $booking->user->phone ?? '9999999999' }}"
            },
            "theme": {
                "color": "#ff7529"
            },
            "modal": {
                "ondismiss": function() {
                    // This function is called when popup is closed
                    console.log('Payment popup closed');
                }
            }
        };

        const rzp = new Razorpay(options);

        document.getElementById('rzp-button').onclick = function(e) {
            rzp.open();
            e.preventDefault();
        }

        // Optional: Handle payment failure
        rzp.on('payment.failed', function(response) {
            console.error('Payment failed:', response);
            alert('Payment failed. Please try again. Error: ' + response.error.description);
        });
    </script>
</body>

</html>