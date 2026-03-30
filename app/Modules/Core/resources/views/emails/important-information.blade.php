<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $information['title'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #326761 0%, #4a9d94 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #326761;
        }
        .info-title {
            font-size: 20px;
            font-weight: bold;
            color: #326761;
            margin-bottom: 15px;
        }
        .info-description {
            color: #555;
            line-height: 1.8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 12px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📢 Information Importante</h1>
        <p>CAP-EPAC</p>
    </div>
    
    <div class="content">
        <div class="info-box">
            <div class="info-title">{{ $information['title'] }}</div>
            <div class="info-description">
                {!! nl2br(e($information['description'])) !!}
            </div>
        </div>

        @if($information['link'])
        <p style="text-align: center; margin: 25px 0;">
            <a href="{{ $information['link'] }}" 
               style="display: inline-block; background: #326761; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                En savoir plus
            </a>
        </p>
        @endif

        @if(!empty($fileUrls))
        <p style="background: #e8f5f3; padding: 15px; border-radius: 5px; text-align: center;">
            📎 {{ count($fileUrls) }} document(s) PDF joint(s) à cet email
        </p>
        @endif
    </div>

    <div class="footer">
        <p>
            <strong>CAP-EPAC</strong><br>
            Centre Autonome de Perfectionnement - École Polytechnique d'Abomey-Calavi<br>
            Cet email a été envoyé automatiquement, merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>
