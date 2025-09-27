<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body>
    <h2>Booking Payment</h2>

    {{-- Display Amount --}}
    <p>Pay ₹{{ $amount / 100 }}</p>

    {{-- Pay Button --}}
    <button id="rzp-button1">Pay Now</button>

    <script>
        // Razorpay options
        var options = {
            "key": "{{ env('RAZORPAY_KEY_ID') }}", // ✅ your key from .env
            "amount": "{{ $amount }}", // Amount in paise
            "currency": "INR",
            "name": "RapidIT",
            "description": "Booking #{{ $booking->id }} Payment",
            "order_id": "{{ $orderId }}", // ✅ Razorpay order id from controller
            "handler": function(response) {
                // ✅ When payment is successful
                alert("Payment successful! Payment ID: " + response.razorpay_payment_id);

                // Optionally, send AJAX to backend to verify & update DB
                fetch("{{ url('/api/bookings/'.$booking->id.'/verify-payment') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    }).then(res => res.json())
                    .then(data => {
                        console.log(data);
                    });
            },
            "prefill": {
                "name": "{{ auth()->user()->name ?? 'Test User' }}",
                "email": "{{ auth()->user()->email ?? 'test@example.com' }}",
                "contact": "9999999999"
            },
            "theme": {
                "color": "#3399cc"
            }
        };

        // Attach to button click
        document.getElementById('rzp-button1').onclick = function(e) {
            var rzp1 = new Razorpay(options);
            rzp1.open();
            e.preventDefault();
        }
    </script>
</body>

</html>