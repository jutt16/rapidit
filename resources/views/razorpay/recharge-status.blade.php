<!DOCTYPE html>
<html>
<head>
    <title>Recharge Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 40px;
            text-align: center;
        }
        .container {
            max-width: 480px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ddd;
        }
        .success {
            color: green;
        }
        .failed {
            color: red;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            background: #0d6efd;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn:hover {
            background: #084298;
        }
    </style>
</head>
<body>
    <div class="container">
        @if (session('success'))
            <h2 class="success">✅ {{ session('success') }}</h2>
        @elseif (session('error'))
            <h2 class="failed">❌ {{ session('error') }}</h2>
        @else
            <h2>Recharge Status: <span class="{{ $status }}">{{ ucfirst($status) }}</span></h2>
        @endif

        <p><strong>User:</strong> {{ $user->name }}</p>
        <p><strong>Wallet Balance:</strong> ₹{{ number_format($wallet->balance, 2) }}</p>

        @if($lastTxn)
            <p><strong>Transaction ID:</strong> {{ $lastTxn->transaction_id }}</p>
            <p><strong>Amount:</strong> ₹{{ number_format($lastTxn->description, 2) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($lastTxn->status) }}</p>
            
            @php
                // extract text after '#' up to the closing parenthesis, e.g. "pay_RQI5uceEh3eZYd"
                preg_match('/#([^)]*)/', $lastTxn->description ?? '', $matches);
                $paymentId = $matches[1] ?? '';
            @endphp

            <p><strong>Payment ID:</strong> {{ $paymentId }}</p>
        @endif

        <a href="{{ url('/') }}" class="btn">Go Back</a>
    </div>
</body>
</html>
