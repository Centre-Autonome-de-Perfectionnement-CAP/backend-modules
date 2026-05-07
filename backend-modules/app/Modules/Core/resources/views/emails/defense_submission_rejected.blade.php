<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .details {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .reason {
            background-color: #fdecea;
            padding: 15px;
            border-left: 4px solid #e74c3c;
            margin: 15px 0;
        }
        .actions {
            margin-top: 20px;
        }
        .actions li {
            margin-bottom: 10px;
        }
        .warning-icon {
            color: #e74c3c;
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.cap-epac.online/assets/imgs/logo.png" alt="CAP Logo" class="logo">
        <h1>✋ Votre dossier de soutenance n'a pas été retenu</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $studentName }}</strong>,</p>
        
        <div class="details">
            <p><span class="warning-icon">⚠</span> Après examen par notre comité pédagogique, votre dossier de soutenance nécessite des modifications avant acceptation.</p>
            <p><strong>Titre du mémoire :</strong> {{ $thesisTitle }}</p>
        </div>

        <div class="reason">
            <h3>Raisons principales :</h3>
            <p>{{ $rejectionReason }}</p>
        </div>

  
        <p>Cordialement,</p>
        <p><strong>La Cellule Informatique de la Division Formation Continue</strong><br>
        Centre Autonome de Perfectionnement (CAP-EPAC/UAC)</p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} CAP - Tous droits réservés</p>
    </div>
</body>
</html>
