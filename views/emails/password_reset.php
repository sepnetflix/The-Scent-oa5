<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - The Scent</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0;
            padding: 0;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content { 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a90e2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            padding: 20px;
            font-size: 12px; 
            color: #666;
            background: #f8f9fa;
        }
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="<?= BASE_URL ?>assets/images/logo.png" alt="The Scent Logo">
            <h1>Reset Your Password</h1>
        </div>
        
        <div class="content">
            <p>Hello <?= htmlspecialchars($name) ?>,</p>
            
            <p>We received a request to reset your password for your account at The Scent. 
               If you didn't make this request, you can safely ignore this email and your password will remain unchanged.</p>
            
            <p>To reset your password, click the button below:</p>
            
            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($resetLink) ?>" class="button">Reset Password</a>
            </p>
            
            <div class="security-notice">
                <strong>Security Notice:</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>This link will expire in 1 hour</li>
                    <li>Never share this link with anyone</li>
                    <li>Our staff will never ask for this link</li>
                </ul>
            </div>
            
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p style="word-break: break-all;"><?= htmlspecialchars($resetLink) ?></p>
            
            <p>Best regards,<br>The Scent Team</p>
        </div>
        
        <div class="footer">
            <p>This email was sent to <?= htmlspecialchars($user['email']) ?>.</p>
            <p>If you need assistance, please contact our customer support at support@thescent.com</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>