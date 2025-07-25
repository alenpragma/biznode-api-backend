<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaction Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .details {
            margin-top: 20px;
        }
        .details p {
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="{{ $logo_url }}" alt="Company Logo" width="150">
    </div>
    <h2 style="text-align:center; color:green;">✅ Transaction Successful</h2>
    <div class="details">
        <p><strong>Transaction Hash:</strong> {{ $txHash }}</p>
        <p><strong>Nonce:</strong> {{ $nonce }}</p>
        <p><strong>Contract Address:</strong> {{ $contract_address }}</p>
        <p><strong>Amount:</strong> {{ $amount }} Tokens</p>
    </div>
    <div class="footer">
        If you have any questions, contact our support team.<br>
        &copy; {{ date('Y') }} BizNode.io All rights reserved.
    </div>
</div>
</body>
</html>
