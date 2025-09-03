<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verification</title>
</head>
<body style="margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #f8fafc; padding: 40px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background-color: #4f46e5; padding: 20px 40px;">
            <h2 style="margin: 0; color: white;">Snoutiq Registeration Verification</h2>
        </div>

        <div style="padding: 30px 40px;">
            <p style="font-size: 18px; color: #111827; margin-bottom: 20px;">
                Hi user,
            </p>

            <p style="font-size: 16px; color: #374151; margin-bottom: 20px;">
                Please use the OTP below to verify your email address:
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <span style="display: inline-block; background-color: #f3f4f6; padding: 15px 30px; font-size: 24px; font-weight: bold; color: #1f2937; border-radius: 8px; letter-spacing: 4px;">
                    {{ $otp }}
                </span>
            </div>

            <p style="font-size: 14px; color: #6b7280;">
                This OTP is valid for 10 minutes. If you didn’t request this, please ignore the message.
            </p>

            <p style="margin-top: 30px; font-size: 16px; color: #111827;">
                Warm regards,<br>
                <strong>Team Snoutiq</strong>
            </p>
        </div>
    </div>
</body>
</html>
