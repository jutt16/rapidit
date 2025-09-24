<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        .box {
            display: inline-block;
            padding: 30px;
            border: 2px solid #28a745;
            border-radius: 12px;
            background: #f9fff9;
        }

        h2 {
            color: #28a745;
        }

        .details {
            margin-top: 15px;
            text-align: left;
        }

        .btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>✅ Payment Successful!</h2>
        <p>Thank you for your payment.</p>
        <div class="details">
            <p><strong>Booking ID:</strong> {{ $payment->booking_id }}</p>
            <p><strong>Payment ID:</strong> {{ $payment->razorpay_payment_id }}</p>
            <p><strong>Amount Paid:</strong> ₹{{ number_format($payment->amount, 2) }}</p>
        </div>
        <a href="{{ url('/') }}" class="btn">Go to Dashboard</a>
    </div>
</body>

</html>