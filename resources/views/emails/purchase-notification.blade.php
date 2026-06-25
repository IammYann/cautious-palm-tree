<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
        .content { padding: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; padding-top: 20px; border-top: 1px solid #ddd; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Purchase Notification</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $seller->name }}</strong>,</p>

            <p>Great news! Your product <strong>{{ $product->name }}</strong> has been purchased!</p>

            <p><strong>Purchase Details:</strong></p>
            <ul>
                <li><strong>Product:</strong> {{ $product->name }}</li>
                <li><strong>Buyer:</strong> {{ $buyer->name }} ({{ $buyer->email }})</li>
                <li><strong>Price:</strong> Rs. {{ number_format($product->price, 2) }}</li>
                <li><strong>Quantity Sold:</strong> {{ $order->quantity }}</li>
                <li><strong>Total Amount:</strong> Rs. {{ number_format($order->amount, 2) }}</li>
                <li><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y, g:i a') }}</li>
                <li><strong>Transaction ID:</strong> {{ $order->transaction_id }}</li>
            </ul>

            <p>Please ensure the product is delivered to the buyer as per the agreed terms.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Our Shop. All rights reserved.
        </div>
    </div>
</body>
</html>
