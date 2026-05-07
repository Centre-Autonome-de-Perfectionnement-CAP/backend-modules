<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject_display ?? 'Notification CUCA' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #003087;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
        }
        .content p {
            margin: 0 0 15px;
        }
        .highlight {
            background-color: #e6f3ff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            color: #003087;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .footer p {
            margin: 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #003087;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        @media screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $subject_display ?? 'Notification CUCA' }}</h1>
        </div>
        <div class="content">
            <p>Cher(e) {{ $student->name ?? 'Étudiant(e)' }},</p>
            {!! $content !!}
        </div>
        <div class="footer">
            <p>Cordialement,<br>L'équipe CAP</p>
        </div>
    </div>
</body>
</html>