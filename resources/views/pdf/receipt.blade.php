<!DOCTYPE html>

<html>
<head>
<title>Payment Receipt</title>
<style>
body {
font-family: 'Arial', sans-serif;
line-height: 1.6;
background-color: #f4f4f4;
padding: 20px;
}
.receipt-box {
width: 100%;
max-width: 600px;
margin: 0 auto;
border: 1px solid #ddd;
background-color: #fff;
padding: 30px;
box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
}
.header {
text-align: center;
margin-bottom: 25px;
border-bottom: 2px solid #007bff;
padding-bottom: 15px;
}
.header h2 {
color: #007bff;
margin: 0;
font-size: 24px;
}
.details-section {
margin-bottom: 25px;
padding: 15px;
border: 1px dashed #ccc;
background-color: #fafafa;
}
.details-section p {
margin: 8px 0;
display: flex;
justify-content: space-between;
}
.details-section strong {
color: #333;
font-weight: bold;
}
.amount-row {
font-size: 1.2em;
font-weight: bold;
color: #28a745; 
padding-top: 10px;
margin-top: 10px;
border-top: 1px solid #eee;
}
.footer {
text-align: center;
margin-top: 30px;
font-size: 11px;
color: #777;
}
</style>
</head>
<body>
<div class="receipt-box">
<div class="header">
<h2>Payment Receipt</h2>
<p>For Exam Form Submission</p>
</div>

    <div class="details-section">
        <p><strong>Date of Payment:</strong> <span>{{ $submission->updated_at->format('d M Y') }}</span></p>
        <p><strong>Receipt No.:</strong> <span>ORD-{{ $submission->id }}</span></p>
        <p><strong>Transaction ID:</strong> <span>{{ $submission->payment->transaction_id ?? 'N/A' }}</span></p>
    </div>
    
    <div class="details-section">
        <h3>Payer Details</h3>
        <p><strong>Student Name:</strong> <span>{{ $submission->user->name }}</span></p>
        <p><strong>Student Email:</strong> <span>{{ $submission->user->email }}</span></p>
        <p><strong>Exam Form:</strong> <span>{{ $submission->form->title }}</span></p>
    </div>

    <div class="details-section">
        <h3>Payment Status</h3>
        
        <p><strong>Status:</strong> <span>{{ strtoupper($submission->payment->status ?? 'UNKNOWN') }}</span></p>
        <p><strong>Payment Method:</strong> <span>{{ $submission->payment->payment_method ?? 'Card' }}</span></p>
        <p class="amount-row"><strong>Amount Paid:</strong> <span>{{ $submission->payment->currency ?? 'INR' }} {{ number_format($submission->form->fee_amount, 2) }}</span></p>
    </div>

    <div class="footer">
        Thank you for your payment.
    </div>
</div>


</body>
</html>
