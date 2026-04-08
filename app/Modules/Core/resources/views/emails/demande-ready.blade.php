<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background: #10b981; color: white; padding: 32px 40px; }
    .header h1 { margin: 0; font-size: 22px; }
    .body { padding: 32px 40px; color: #374151; line-height: 1.7; }
    .ref { display: inline-block; background: #ecfdf5; color: #065f46; font-family: monospace; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
    .info-box { background: #f0fdf4; border-left: 4px solid #10b981; padding: 16px 20px; border-radius: 4px; margin: 20px 0; }
    .footer { background: #f9fafb; padding: 20px 40px; font-size: 12px; color: #9ca3af; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>✅ Votre document est prêt !</h1>
    </div>
    <div class="body">
      <p>Bonjour,</p>
      <p>Nous avons le plaisir de vous informer que votre document est prêt à être retiré.</p>
      <div class="info-box">
        <strong>Type de document :</strong> {{ $type }}<br>
        <strong>Référence :</strong> <span class="ref">{{ $reference }}</span>
      </div>
      <p>Vous pouvez venir le retirer au secrétariat du <strong>CAP</strong>, muni(e) de votre carte étudiant.</p>
      <p>Cordialement,<br><strong>Le Secrétariat du CAP</strong></p>
    </div>
    <div class="footer">
      Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
    </div>
  </div>
</body>
</html>
