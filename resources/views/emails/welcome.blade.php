<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .role {
            background-color: #f0f0f0;
            padding: 10px;
            border-left: 4px solid #3498db;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Shop! 🎉</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>

            <p>Thank you for registering with us! Your account has been created successfully.</p>

            <div class="role">
                <p><strong>Your Account Role:</strong> {{ ucfirst($user->role) }}</p>
            </div>

            <h3>What You Can Do Now:</h3>
            <ul>
                <li>✅ Browse our amazing products</li>
                <li>✅ View detailed product information</li>
                <li>✅ Manage your profile</li>
                <li>✅ Keep track of your account</li>
            </ul>

            <p>
                <a href="{{ route('products.index') }}" class="button">View Our Products</a>
            </p>

            <h3>Need Help?</h3>
            <p>If you have any questions or need assistance, feel free to contact our support team.</p>

            <p>
                Best regards,<br>
                <strong>The Shop Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Our Shop. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
