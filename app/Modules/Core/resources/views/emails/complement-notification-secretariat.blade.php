<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notification — Nouveau complément de dossier</title>
  <style>
    body { margin: 0; padding: 0; background: #f4f6f8; font-family: 'Segoe UI', Arial, sans-serif; color: #1a2b2b; }
    .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .header  { background: #1e3a37; padding: 28px 40px; }
    .header h1 { color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; }
    .header p  { color: #7fb3ae; margin: 6px 0 0; font-size: 13px; }
    .body    { padding: 28px 40px; }
    .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #326761; font-weight: 700; margin: 0 0 10px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
    .info-item { background: #f8fafb; border: 1px solid #e0e9e8; border-radius: 8px; padding: 12px 14px; }
    .info-item .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .07em; color: #6b8e8a; font-weight: 600; }
    .info-item .val { font-size: 14px; font-weight: 700; color: #1a2b2b; margin-top: 3px; }
    .info-item .val.mono { font-family: 'Courier New', monospace; color: #326761; }
    .pieces-block { background: #f0f9f7; border: 1px solid #b2d8d4; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
    .pieces-block h3 { font-size: 13px; font-weight: 700; color: #326761; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .05em; }
    .pieces-block ul { margin: 0; padding: 0; list-style: none; }
    .pieces-block li { padding: 5px 0; font-size: 13px; color: #1a2b2b; display: flex; align-items: center; gap: 8px; border-bottom: 1px dashed #d1ebe8; }
    .pieces-block li:last-child { border-bottom: none; }
    .pieces-block li::before { content: '📄'; font-size: 14px; }
    .action-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 18px; font-size: 13px; color: #78350f; line-height: 1.6; }
    .footer  { background: #f8fafb; padding: 16px 40px; text-align: center; font-size: 11px; color: #9ab5b2; border-top: 1px solid #e0e9e8; }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class="header">
      <h1>📂 Nouveau complément de dossier</h1>
      <p>Notification automatique — Secrétariat CAP-EPAC</p>
    </div>

    <div class="body">
      <p style="font-size:14px;color:#3d5555;margin-bottom:20px;">
        Un étudiant vient de déposer des pièces complémentaires à son dossier.
        Veuillez en prendre connaissance dans le progiciel.
      </p>

      <!-- Informations du dossier -->
      <p class="section-title">Informations du dossier</p>
      <div class="info-grid">
        <div class="info-item">
          <div class="lbl">Étudiant</div>
          <div class="val">{{ $nomComplet }}</div>
        </div>
        <div class="info-item">
          <div class="lbl">Matricule</div>
          <div class="val mono">{{ $matricule }}</div>
        </div>
        <div class="info-item">
          <div class="lbl">Numéro de référence</div>
          <div class="val mono">{{ $reference }}</div>
        </div>
        <div class="info-item">
          <div class="lbl">Date du complément</div>
          <div class="val">{{ $dateComplement }}</div>
        </div>
        <div class="info-item" style="grid-column: span 2;">
          <div class="lbl">E-mail de l'étudiant</div>
          <div class="val">{{ $email }}</div>
        </div>
      </div>

      <!-- Pièces déposées -->
      <div class="pieces-block">
        <h3>Pièces complémentaires déposées ({{ count($piecesList) }})</h3>
        <ul>
          @foreach($piecesList as $piece)
            <li>{{ $piece }}</li>
          @endforeach
        </ul>
      </div>

      <!-- Action -->
      <div class="action-box">
        ⚠️ <strong>Action requise :</strong> Consultez le dossier dans le progiciel interne sous
        la référence <strong>{{ $reference }}</strong> pour valider ou rejeter les nouvelles pièces.
      </div>
    </div>

    <div class="footer">
      <p>Ce message est généré automatiquement par le système CAP-EPAC — Ne pas répondre.</p>
    </div>

  </div>
</body>
</html>
