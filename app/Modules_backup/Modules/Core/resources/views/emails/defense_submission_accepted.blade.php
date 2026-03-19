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
            background-color: #27ae60;
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
            background-color: #2ecc71;
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
        .checkmark {
            color: #27ae60;
            font-size: 24px;
            margin-right: 10px;
        }
        .next-steps {
            margin-top: 20px;
        }
        .next-steps li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.cap-epac.online/assets/imgs/logo.png" alt="CAP Logo" class="logo">
        <h1>🎉 Félicitations ! Votre dossier est accepté</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $studentName }}</strong>,</p>
        
        <div class="details">
            <p><span class="checkmark">✓</span> Nous avons le plaisir de vous informer que votre dossier de soutenance a été <strong>accepté</strong> par notre comité pédagogique.</p>
            <p><strong>Titre du mémoire :</strong> {{ $thesisTitle }}</p>
        </div>

        <div class="next-steps">
            <h3>Prochaines étapes :</h3>
            <ul>
                <li><strong>Planification :</strong> Vous recevrez sous peu les dates possibles pour votre soutenance</li>
                <li><strong>Préparation :</strong> Commencez à préparer votre présentation (15-20 minutes)</li>
                <li><strong>Documents :</strong> Préparez les exemplaires papier de votre mémoire pour les membres du jury</li>
            </ul>
        </div>
        
        <p>Nous restons à votre disposition pour toute question concernant la préparation de votre soutenance.</p>
        
        <p>Cordialement,</p>
        <p><strong>La Cellule Informatique de la Division Formation Continue</strong><br>
        Centre Autonome de Perfectionnement (CAP-EPAC/UAC)</p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} EPAC - Tous droits réservés</p>
    </div>
</body>
</html>
