<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Success</title>
</head>

<body>
    <h2>Payment Successful 🎉</h2>
    <p>Booking #{{ $payment->booking_id }} has been confirmed.</p>
    <p>Amount: ₹{{ $payment->amount }}</p>
    <p>Status: {{ $payment->status }}</p>
</body>

</html>