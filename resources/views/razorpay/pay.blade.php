<!DOCTYPE html>
<html>
<head>
    <title>Pay with Razorpay</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <h2>Booking Payment</h2>

    <button id="rzp-button1">Pay ₹{{ $amount / 100 }}</button>

    <script>
        var options = {
            "key": "{{ config('services.razorpay.key') ?? env('RAZORPAY_KEY_ID') }}", // ✅ test key from .env
            "amount": "{{ $amount }}", // amount in paise
            "currency": "INR",
            "name": "RapidIT",
            "description": "Booking #{{ $booking->id }} Payment",
            "order_id": "{{ $orderId }}", // Razorpay order ID from controller
            "handler": function (response){
                alert("Payment successful! Payment ID: " + response.razorpay_payment_id);
                // TODO: send AJAX to server to verify & update booking_payments to "paid"
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

        var rzp1 = new Razorpay(options);
        document.getElementById('rzp-button1').onclick = function(e){
            rzp1.open();
            e.preventDefault();
        }
    </script>
</body>
</html>
