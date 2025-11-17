<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SnoutIQ Referral Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; background-color: #f7f9fc; padding: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
        <tr>
            <td style="padding: 32px;">
                <h1 style="margin: 0 0 16px; color: #2563eb; font-size: 20px;">Email verified successfully ✅</h1>

                <p style="margin: 0 0 12px;">
                    Hi {{ $user->name ?? 'there' }},
                </p>

                <p style="margin: 0 0 12px;">
                    Thanks for requesting the SnoutIQ app.
                    You're all set—use the referral code below when you sign up so we can personalize your experience.
                </p>

                <div style="margin: 20px 0; padding: 16px; background: #f0f7ff; border: 1px dashed #93c5fd; border-radius: 10px; text-align: center;">
                    <div style="font-size: 14px; text-transform: uppercase; color: #64748b; letter-spacing: 0.08em;">Referral Code</div>
                    <div style="font-size: 32px; font-weight: 700; letter-spacing: 0.3em; color: #0f172a; margin-top: 6px;">{{ $code }}</div>
                </div>

                <p style="margin: 0 0 12px;">
                    Download SnoutIQ and sign in using the same email to apply the code automatically:
                    <a href="https://snoutiq.com/download" style="color: #2563eb;">SnoutIQ for iOS & Android</a>
                </p>

                <p style="margin: 24px 0 0;">
                    See you inside,<br>
                    <strong>The SnoutIQ Team</strong>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
