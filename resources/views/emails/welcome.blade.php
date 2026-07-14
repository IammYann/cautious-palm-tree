<!DOCTYPE html>
<html>
<head>
    <style>
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our Shop!</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>

            <p>Thank you for registering with us! Your account has been created successfully.</p>

            <div class="role">
                <p><strong>Your Account Role:</strong> {{ ucfirst($user->role) }}</p>
            </div>

            <h3>What You Can Do Now:</h3>
            <ul>
                <li>Browse our amazing products</li>
                <li>View detailed product information</li>
                <li>Manage your profile</li>
                <li>Keep track of your account</li>
            </ul>

            

            <h3>Need Help?</h3>
            <p>If you have any questions or need assistance, feel free to contact our support team.</p>

            <p>
                Best regards,<br>
                <strong>The Shop Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y-m-d') }} Our Shop. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
