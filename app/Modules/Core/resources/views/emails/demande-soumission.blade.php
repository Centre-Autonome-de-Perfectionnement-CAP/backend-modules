{{-- resources/views/core/emails/demande-soumission.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Demande reçue — {{ $reference }}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:620px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.08); }
    .header { background:#326761; padding:32px 40px; text-align:center; }
    .header h1 { color:#fff; font-size:22px; font-weight:700; }
    .header p { color:rgba(255,255,255,.75); font-size:13px; margin-top:6px; }
    .body { padding:36px 40px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:12px; }
    .info-card { background:#f8fafb; border:1px solid #e2e8f0; border-left:4px solid #326761; border-radius:8px; padding:20px 24px; margin:24px 0; }
    .info-card table { width:100%; border-collapse:collapse; }
    .info-card td { padding:6px 0; vertical-align:top; }
    .info-card td:first-child { color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:.5px; width:130px; }
    .info-card td:last-child { color:#1a1a2e; font-weight:600; }
    .ref-badge { display:inline-block; background:#326761; color:#fff; font-family:'Courier New',monospace; font-size:13px; font-weight:700; padding:3px 10px; border-radius:4px; letter-spacing:1px; }
    .notice { background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:14px 18px; font-size:14px; color:#166534; margin-bottom:24px; }
    .footer { background:#f8fafb; border-top:1px solid #e2e8f0; padding:20px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ config('app.name', 'CAP-EPAC') }}</h1>
      <p>Demande de document — Accusé de réception</p>
    </div>
    <div class="body">
      <p class="greeting">Votre demande a bien été reçue.</p>
      <p style="color:#444;margin-bottom:4px;">Nous avons enregistré votre demande de document. Vous serez notifié(e) par e-mail et WhatsApp à chaque étape de traitement.</p>

      <div class="info-card">
        <table>
          <tr><td>Référence</td><td><span class="ref-badge">{{ $reference }}</span></td></tr>
          <tr><td>Type</td><td>{{ $typeLabel }}</td></tr>
          <tr><td>E-mail</td><td>{{ $email }}</td></tr>
          <tr><td>Soumis le</td><td>{{ $submittedAt }}</td></tr>
        </table>
      </div>

      <div class="notice">
        Conservez votre numéro de référence <strong>{{ $reference }}</strong>. Il vous permettra de suivre l'avancement de votre dossier.
      </div>

      <p style="font-size:13px;color:#64748b;text-align:center;">Ne répondez pas à cet e-mail.</p>
    </div>
    <div class="footer">
      <p>{{ config('app.name', 'CAP-EPAC') }} — Système de gestion des demandes</p>
    </div>
  </div>
</body>
</html>
