{{-- resources/views/emails/secretaire-message.blade.php --}}
{{-- Variables : $reference, $typeLabel, $nomDemandeur, $message, $secretaireName --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Message du secrétariat — CAP-EPAC</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, Arial, sans-serif; background-color: #f4f7f6; color: #333; line-height: 1.6; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .header { background-color: #ffffff; padding: 30px 40px; text-align: center; border-bottom: 3px solid #005043; }
    .institution-name { color: #005043; font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .hero { background-color: #f8faf9; padding: 32px 40px; text-align: center; }
    .hero h1 { color: #1a1a1a; font-size: 22px; font-weight: 700; margin-bottom: 6px; }
    .hero p { color: #666; font-size: 14px; }
    .content { padding: 40px; }
    .greeting { font-size: 16px; font-weight: 600; margin-bottom: 15px; }
    .ref-card { background: #ffffff; border: 2px dashed #005043; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 26px; }
    .ref-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 4px; }
    .ref-code { font-family: 'Monaco', 'Courier New', monospace; font-size: 22px; font-weight: 700; color: #005043; letter-spacing: 1.5px; }
    .type-line { font-size: 13px; color: #888; margin-top: 4px; }
    .message-box { background: #f8faf9; border-left: 4px solid #005043; border-radius: 8px; padding: 22px 24px; margin-bottom: 26px; white-space: pre-line; font-size: 15px; color: #1a1a1a; }
    .signature { font-size: 14px; color: #555; }
    .footer { padding: 26px 40px; text-align: center; font-size: 12px; color: #999; background: #f8faf9; }
    .footer p { margin-bottom: 6px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="institution-name">CAP-EPAC</div>
    </div>

    <div class="hero">
      <h1>Message du secrétariat</h1>
      <p>Un message concernant votre dossier vous a été envoyé</p>
    </div>

    <div class="content">
      <p class="greeting">Bonjour {{ $nomDemandeur }},</p>

      <div class="ref-card">
        <div class="ref-label">Dossier concerné</div>
        <div class="ref-code">{{ $reference }}</div>
        <div class="type-line">{{ $typeLabel }}</div>
      </div>

      <div class="message-box">{{ $message }}</div>

      <p class="signature">— {{ $secretaireName }}, Secrétariat CAP-EPAC</p>
    </div>

    <div class="footer">
      <p>Ce message vous a été envoyé également par WhatsApp si votre numéro est renseigné.</p>
      <p>CAP-EPAC — Ne pas répondre directement à cet email.</p>
    </div>
  </div>
</body>
</html>
