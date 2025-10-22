<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .success-container {
            width: 100%;
            max-width: 500px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background-color: #28a745;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }
        .icon-circle svg {
            color: #fff;
            width: 40px;
            height: 40px;
        }
        h1 {
            color: #28a745;
            font-size: 28px;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .details-box {
            background-color: #f1fff4;
            border: 1px solid #d4edda;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .details-box p {
            margin: 5px 0;
            color: #333;
            font-size: 15px;
        }
        .details-box strong {
            font-weight: 600;
            color: #155724;
            display: inline-block;
            width: 120px;
        }
        .receipt-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.1s;
            border: none;
            cursor: pointer;
        }
        .receipt-button:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="icon-circle">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 12.6111L8.92308 17.5L20 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>Payment Successful!</h1>
        <p>Thank you for submitting your payment. Your transaction has been completed and your exam form is now processed.</p>
        
        <div class="details-box">
            <p><strong>Submission ID:</strong> {{ $submission->id }}</p>
            <p><strong>Payment Status:</strong> {{ $submission->payment->status}}</p>
            <p><strong>Amount Paid:</strong> {{ $submission->payment->currency ?? 'INR' }} {{ number_format($submission->form->fee_amount, 2) }}</p>
            <p><strong>Form Title:</strong> {{ $submission->form->title }}</p>
        </div>

        <a href="{{ $receiptUrl }}" target="_blank" class="receipt-button">
            View / Download Receipt
        </a>
    </div>
</body>
</html>
