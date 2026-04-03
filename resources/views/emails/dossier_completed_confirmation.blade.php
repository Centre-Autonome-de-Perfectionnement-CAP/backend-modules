<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Complément de Dossier</title>
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
            <h1>Confirmation de Complément de Dossier</h1>
        </div>
        <div class="content">
            <p>Cher(e) <strong>{{ $first_names }}</strong>,</p>
            <p>Nous vous confirmons que votre complément de dossier a été soumis avec succès pour le département <strong>{{ $department }}</strong> pour l'année académique <strong>{{ $academic_year }}</strong>, au niveau d'étude <strong>{{ $study_level }}</strong>.</p>
            <div class="highlight">
                Votre code de suivi est : <strong>{{ $tracking_code }}</strong>
            </div>
            <p>Veuillez conserver ce code pour toute référence future concernant l'état de votre dossier.</p>
            <p>Pour toute question, n'hésitez pas à contacter le bureau des admissions.</p>
            <a href="{{ config('app.url') }}/dossiers/{{ $tracking_code }}" class="button">Consulter l'état du dossier</a>
        </div>
        <div class="footer">
            <p>Merci de votre confiance,<br>Bureau des Admissions</p>
        </div>
    </div>
</body>
</html>
