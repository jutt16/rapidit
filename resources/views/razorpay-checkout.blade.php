<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Razorpay Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body>
    <h3>Pay for Booking #{{ $booking->id }}</h3>

    <script>
        var options = {
            "key": "{{ $razorpayKey }}", // from controller
            "amount": "{{ $amount }}", // in paise
            "currency": "INR",
            "name": "RapidIT",
            "description": "Booking #{{ $booking->id }}",
            "order_id": "{{ $orderId }}", // from Razorpay API
            "prefill": {
                "name": "{{ $customer['name'] ?? '' }}",
                "email": "{{ $customer['email'] ?? '' }}",
                "contact": "{{ $customer['contact'] ?? '' }}"
            },
            "theme": {
                "color": "#3399cc"
            },
            "handler": function(response) {
                // ✅ Create hidden form to send payment response
                var form = document.createElement('form');
                form.method = "POST";
                form.action = "{{ route('razorpay.callback') }}";

                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
                    <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
                    <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        };

        var rzp1 = new Razorpay(options);

        // ✅ Auto open popup on page load
        window.onload = function() {
            rzp1.open();
        };
    </script>
</body>

</html>