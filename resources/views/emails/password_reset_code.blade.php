<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Coolvetica', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background-color: #682C7A;
            color: #FFF7EC;
            padding: 40px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-family: 'SuperDream', Arial, sans-serif;
        }
        .email-body {
            padding: 40px 20px;
            color: #333333;
        }
        .email-body h2 {
            color: #682C7A;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .email-body p {
            line-height: 1.6;
            margin: 10px 0;
            font-size: 14px;
        }
        .code-box {
            background-color: #FFF7EC;
            border: 2px solid #682C7A;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code-box .code {
            font-size: 32px;
            font-weight: bold;
            color: #682C7A;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .code-box .expiry {
            font-size: 12px;
            color: #999999;
            margin-top: 10px;
        }
        .email-footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #eeeeee;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 13px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Password Reset Code</h1>
        </div>
        
        <div class="email-body">
            <h2>Hello {{ $userName }},</h2>
            
            <p>We received a request to reset the password for your Scruffs & Chyrrs account. Use the code below to create a new password.</p>
            
            <div class="code-box">
                <div class="code">{{ $code }}</div>
                <div class="expiry">This code expires in 15 minutes</div>
            </div>
            
            <p><strong>How to use this code:</strong></p>
            <ol style="line-height: 1.8;">
                <li>Go to our password reset page</li>
                <li>Enter this code when prompted</li>
                <li>Create your new password</li>
            </ol>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not request this password reset, please ignore this email and your password will remain unchanged. Never share this code with anyone.
            </div>
            
            <p style="margin-top: 25px;">Best regards,<br><strong>Scruffs & Chyrrs Team</strong></p>
        </div>
        
        <div class="email-footer">
            <p>© 2026 Scruffs & Chyrrs. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
