<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clinicName }} • Booking Card</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e2e8f0;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .page-shell { width: 420px; position: relative; }
        .download-btn {
            position: sticky;
            top: 0;
            margin-bottom: 20px;
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 0.9rem 1.4rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.25);
        }
        .booking-card {
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px 20px;
            text-align: center;
        }
        .clinic-name {
            background: white;
            color: #667eea;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .scan-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }
        .card-body {
            padding: 30px 20px;
            text-align: center;
        }
        .qr-container {
            background: white;
            padding: 15px;
            border-radius: 15px;
            border: 3px solid #667eea;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(102, 126, 234, 0.2);
        }
        .qr-code {
            width: 220px;
            height: 220px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .referral-section {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px dashed #667eea;
        }
        .referral-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .referral-code {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 2px;
        }
        .features {
            display: flex;
            justify-content: space-around;
            padding: 20px 10px;
            gap: 10px;
        }
        .feature-item {
            flex: 1;
            text-align: center;
        }
        .feature-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .feature-text {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
        }
        .card-footer {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            padding: 20px;
            text-align: center;
        }
        .footer-text {
            color: #333;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        .footer-brand {
            color: #333;
            font-size: 16px;
            font-weight: 700;
        }
        .target-url {
            margin-top: 8px;
            font-size: 11px;
            color: #475569;
            word-break: break-all;
        }
    </style>
</head>
<body>
<div class="page-shell">
    <button class="download-btn" onclick="downloadCard()">Download booking card</button>
    <div class="booking-card" id="bookingCard">
        <div class="card-header">
            <div class="clinic-name">{{ $clinicName }}</div>
            <div class="scan-title">SCAN TO BOOK YOUR APPOINTMENT</div>
            <div class="target-url">{{ $targetUrl }}</div>
        </div>

        <div class="card-body">
            <div class="qr-container">
                <div class="qr-code">
                    <img src="{{ $qrDataUri }}" alt="QR for {{ $clinicName }}">
                </div>
            </div>

            <div class="referral-section">
                <div class="referral-label">REFERRAL CODE</div>
                <div class="referral-code">{{ $referralCode }}</div>
            </div>

            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">📱</div>
                    <div class="feature-text">Download<br>App</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🩺</div>
                    <div class="feature-text">Video<br>Consultation</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🤖</div>
                    <div class="feature-text">AI Symptom<br>Checker</div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="footer-text">POWERED BY</div>
            <div class="footer-brand">SNOUTIQ - INDIA'S DIGITAL VET NETWORK</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function downloadCard() {
        const node = document.getElementById('bookingCard');
        html2canvas(node, {scale: 2}).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ \Illuminate\Support\Str::slug($clinicName) }}-booking-card.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }
</script>
</body>
</html>
