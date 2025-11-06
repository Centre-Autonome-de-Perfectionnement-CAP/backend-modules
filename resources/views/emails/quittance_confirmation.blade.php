<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Réception de Quittance</title>
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
            background-color: #28a745;
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
            background-color: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .info-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-table td:first-child {
            font-weight: bold;
            color: #666;
            width: 40%;
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
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            background-color: #ffc107;
            color: #333;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
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
            .info-table td {
                display: block;
                width: 100% !important;
            }
            .info-table td:first-child {
                border-bottom: none;
                padding-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Quittance Reçue</h1>
        </div>
        <div class="content">
            <p>Cher(e) <strong>{{ $prenoms }} {{ $nom }}</strong>,</p>
            <p>Nous accusons réception de votre quittance de paiement.</p>
            
            <div class="highlight">
                <p style="margin: 0; font-size: 16px;">
                    <strong>Référence de suivi :</strong><br>
                    <span style="color: #28a745; font-size: 20px; font-weight: bold;">{{ $reference }}</span>
                </p>
                <p style="margin: 10px 0 0 0;">
                    <span class="status-badge">⏳ En attente de validation</span>
                </p>
            </div>

            <h3 style="color: #28a745; margin-top: 20px;">📋 Détails du paiement</h3>
            <table class="info-table">
                <tr>
                    <td>Matricule</td>
                    <td><strong>{{ $matricule }}</strong></td>
                </tr>
                <tr>
                    <td>Montant</td>
                    <td><strong>{{ number_format($montant, 0, ',', ' ') }} FCFA</strong></td>
                </tr>
                <tr>
                    <td>Numéro de compte</td>
                    <td>{{ $numero_compte }}</td>
                </tr>
                <tr>
                    <td>Date de versement</td>
                    <td>{{ \Carbon\Carbon::parse($date_versement)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Motif</td>
                    <td>{{ $motif }}</td>
                </tr>
            </table>

            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <p style="margin: 0; color: #856404;">
                    <strong>ℹ️ Prochaines étapes</strong>
                </p>
                <p style="margin: 10px 0 0 0; color: #856404;">
                    Votre quittance sera vérifiée par nos services dans les plus brefs délais. 
                    Vous recevrez une notification par email une fois le paiement validé ou en cas de besoin de rectification.
                </p>
            </div>

            <p style="margin-top: 20px;">
                <strong>💡 Astuce :</strong> Conservez précieusement votre référence <strong>{{ $reference }}</strong> 
                pour suivre l'état de votre paiement.
            </p>

            <p>Pour toute question concernant votre paiement, n'hésitez pas à contacter le service financier en mentionnant votre référence.</p>
        </div>
        <div class="footer">
            <p>Merci de votre confiance,<br><strong>Service Financier</strong></p>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            </p>
        </div>
    </div>
</body>
</html>
