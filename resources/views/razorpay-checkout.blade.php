<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Razorpay Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body>
    <h3>Pay for Booking #{{ $booking->id }}</h3>

    <button id="rzp-button">Pay with Razorpay</button>

    <script>
        var options = {
            "key": "{{ $key }}",
            "amount": "{{ $amount }}", // amount in paise
            "currency": "INR",
            "name": "RapidIT",
            "description": "Booking #{{ $booking->id }}",
            "order_id": "{{ $order_id }}",
            "prefill": {
                "name": "{{ $booking->user->name }}",
                "email": "{{ $booking->user->email }}",
                "contact": "{{ $booking->user->phone }}"
            },
            "theme": {
                "color": "#3399cc"
            },
            "handler": function(response) {
                // Redirect to callback route with payment details
                window.location.href =
                    "{{ route('payments.callback') }}" +
                    "?razorpay_payment_id=" + response.razorpay_payment_id +
                    "&razorpay_order_id=" + response.razorpay_order_id +
                    "&razorpay_signature=" + response.razorpay_signature;
            }
        };

        var rzp1 = new Razorpay(options);
        document.getElementById('rzp-button').onclick = function(e) {
            rzp1.open();
            e.preventDefault();
        }
    </script>
</body>

</html>