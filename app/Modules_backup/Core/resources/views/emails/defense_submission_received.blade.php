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
            background-color: #2c3e50;
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
            background-color: #3498db;
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
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.cap-epac.online/assets/imgs/logo.png" alt="CAP Logo" class="logo">
        <h1>Accusé de Réception</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $studentName }}</strong>,</p>
        
        <div class="details">
            <p>Nous confirmons avoir bien reçu votre dossier de soutenance avec les informations suivantes :</p>
            
            <p><strong>Titre du mémoire :</strong> {{ $thesisTitle }}</p>
            <p><strong>Date de soumission :</strong> {{ $submissionDate }}</p>
        </div>

        <p>Votre dossier est maintenant en cours d'examen par notre équipe pédagogique. Vous recevrez une notification par email dès qu'une décision sera prise concernant votre demande.</p>
        

        <p>Pour toute question, n'hésitez pas à répondre à cet email ou à contacter le service des soutenances.</p>
        
        <p>Cordialement,</p>
        <p><strong>La Cellule Informatique de la Division Formation Continue</strong><br>
        Centre Autonome de Perfectionnement (CAP-EPAC/UAC)</p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} CAP-EPAC - Tous droits réservés</p>
    </div>
</body>
</html>
