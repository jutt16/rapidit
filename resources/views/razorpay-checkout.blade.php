<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Razorpay Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body>
    <h3>Pay for Booking #{{ $booking->id }}</h3>

    <script>
        var options = {
            "key": "{{ $key }}",
            "amount": "{{ $amount }}", // in paise
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
                // ✅ Create a form to POST response securely
                var form = document.createElement('form');
                form.method = "POST";
                form.action = "{{ route('payments.callback') }}";

                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
                    <input type="hidden" name="razorpay_order_id" value="${response.razorpay_order_id}">
                    <input type="hidden" name="razorpay_signature" value="${response.razorpay_signature}">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        };

        var rzp1 = new Razorpay(options);

        // ✅ Open automatically on page load
        window.onload = function() {
            rzp1.open();
        }
    </script>
</body>

</html>