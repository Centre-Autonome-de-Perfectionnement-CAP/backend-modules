<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: 600;
            color: #667eea;
            display: inline-block;
            min-width: 100px;
        }
        .info-value {
            color: #333;
        }
        .message-box {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            line-height: 1.8;
        }
        .message-label {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .alert-icon {
            font-size: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📬 Nouveau Message de Contact</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Centre Autonome de Perfectionnement</p>
        </div>

        <div class="content">
            <div class="alert-box">
                <span class="alert-icon">🔔</span>
                <strong>Attention :</strong> Un nouveau message de contact a été reçu sur le site web.
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">De :</span>
                    <span class="info-value">{{ $contactName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email :</span>
                    <span class="info-value"><a href="mailto:{{ $contactEmail }}" style="color: #667eea; text-decoration: none;">{{ $contactEmail }}</a></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sujet :</span>
                    <span class="info-value"><strong>{{ $contactSubject }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date :</span>
                    <span class="info-value">{{ $contactDate }}</span>
                </div>
            </div>

            <div class="message-box">
                <span class="message-label">Message :</span>
                <div>{{ $contactMessage }}</div>
            </div>

            <p style="color: #666; font-size: 14px; margin-top: 30px;">
                💡 <em>Pensez à répondre rapidement pour maintenir une bonne relation avec vos contacts.</em>
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                <strong>Centre Autonome de Perfectionnement (CAP)</strong><br>
                EPAC, Abomey-Calavi
            </p>
            <p style="margin: 10px 0 0 0;">
                📧 <a href="mailto:contact@cap-epac.online">contact@cap-epac.online</a> | 
                📞 +229 01 91 94 73 67
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Cet email a été généré automatiquement par le système de gestion du site web.
            </p>
        </div>
    </div>
</body>
</html>
