<!DOCTYPE html>
<html>
<head>
    <title>Recharge Wallet - {{ $user->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 40px; text-align: center; }
        .container { max-width: 480px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px #ddd; }
        button { background: #0d6efd; color: #fff; padding: 12px 30px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #084298; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recharge Wallet</h2>
        <p>Hello, <strong>{{ $user->name }}</strong></p>
        <p>Recharge Amount: <strong>₹{{ number_format($amount, 2) }}</strong></p>

        <button id="rzp-button1">Pay Now</button>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            "key": "{{ env('RAZORPAY_KEY_ID') }}",
            "amount": "{{ $amount * 100 }}",
            "currency": "INR",
            "name": "Wallet Recharge",
            "description": "Recharge Wallet for {{ $user->name }}",
            "order_id": "{{ $orderId }}",
            "handler": function (response) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('recharge.callback', base64_encode($user->id)) }}";

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
                form.appendChild(csrf);

                for (const [key, value] of Object.entries(response)) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = key;
                    hidden.value = value;
                    form.appendChild(hidden);
                }

                document.body.appendChild(form);
                form.submit();
            },
            "theme": { "color": "#0d6efd" }
        };

        var rzp1 = new Razorpay(options);
        document.getElementById('rzp-button1').onclick = function(e) {
            rzp1.open();
            e.preventDefault();
        }
    </script>
</body>
</html>
