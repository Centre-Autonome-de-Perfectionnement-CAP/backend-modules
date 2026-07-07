{{-- resources/views/core/emails/demande-delivered.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirmation de retrait — {{ $reference }}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.05); }
    .header { background:#1e293b; padding:24px 32px; text-align:center; border-bottom:4px solid #326761; }
    .header h1 { color:#ffffff; font-size:20px; font-weight:600; letter-spacing:0.5px; }
    .body { padding:32px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:16px; color:#0f172a; }
    .text { margin-bottom:24px; color:#475569; }
    .info-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:20px; margin-bottom:24px; }
    .info-row { display:flex; margin-bottom:12px; }
    .info-row:last-child { margin-bottom:0; }
    .info-label { width:120px; color:#64748b; font-size:13px; text-transform:uppercase; font-weight:600; }
    .info-value { color:#0f172a; font-weight:500; flex:1; }
    .ref-badge { background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:4px; font-family:monospace; font-size:14px; letter-spacing:1px; }
    .footer { background:#f8fafc; padding:20px 32px; text-align:center; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>CAP-EPAC</h1>
    </div>
    <div class="body">
      <p class="greeting">Bonjour{{ !empty($nomEtudiant) ? ' ' . $nomEtudiant : '' }},</p>
      <p class="text">
        Nous vous confirmons que le document suivant vous a été remis en mains propres. Votre dossier est désormais clos.
      </p>

      <div class="info-card">
        <div class="info-row">
          <div class="info-label">Référence</div>
          <div class="info-value"><span class="ref-badge">{{ $reference }}</span></div>
        </div>
        <div class="info-row">
          <div class="info-label">Document</div>
          <div class="info-value">{{ $type }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Date</div>
          <div class="info-value">{{ now()->format('d/m/Y à H:i') }}</div>
        </div>
      </div>

      <p class="text">
        Nous vous remercions pour votre confiance et vous souhaitons une excellente continuation.
      </p>
    </div>
    <div class="footer">
      <p>Cet e-mail a été généré automatiquement. Merci de ne pas y répondre.</p>
      <p style="margin-top:8px;">Le Secrétariat du CAP-EPAC</p>
    </div>
  </div>
</body>
</html>
