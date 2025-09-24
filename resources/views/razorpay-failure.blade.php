<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Failed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        .box {
            display: inline-block;
            padding: 30px;
            border: 2px solid #dc3545;
            border-radius: 12px;
            background: #fff5f5;
        }

        h2 {
            color: #dc3545;
        }

        .error {
            margin-top: 15px;
            text-align: left;
            color: #721c24;
        }

        .btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #dc3545;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>❌ Payment Failed</h2>
        <p>We couldn’t process your payment. Please try again.</p>
        @if(!empty($error))
        <div class="error">
            <p><strong>Error:</strong> {{ $error }}</p>
        </div>
        @endif
        <a href="{{ url()->previous() }}" class="btn">Try Again</a>
    </div>
</body>

</html>