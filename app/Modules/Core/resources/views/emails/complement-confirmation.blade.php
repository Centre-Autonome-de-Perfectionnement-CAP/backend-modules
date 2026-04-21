<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complément de dossier reçu</title>
  <style>
    body { margin: 0; padding: 0; background: #f4f6f8; font-family: 'Segoe UI', Arial, sans-serif; color: #1a2b2b; }
    .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .header  { background: #326761; padding: 32px 40px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; }
    .header p  { color: #b2d8d4; margin: 6px 0 0; font-size: 14px; }
    .body    { padding: 32px 40px; }
    .salut   { font-size: 16px; margin-bottom: 16px; }
    .intro   { font-size: 14px; line-height: 1.7; color: #3d5555; margin-bottom: 24px; }
    .ref-box { background: #f0f9f7; border: 1px solid #b2d8d4; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; text-align: center; }
    .ref-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #326761; font-weight: 600; }
    .ref-box .value { font-family: 'Courier New', monospace; font-size: 20px; font-weight: 800; color: #326761; margin-top: 4px; }
    .pieces-block { background: #f8fafb; border: 1px solid #e0e9e8; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; }
    .pieces-block h3 { font-size: 13px; font-weight: 700; color: #326761; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .05em; }
    .pieces-block ul { margin: 0; padding: 0; list-style: none; }
    .pieces-block li { padding: 5px 0; font-size: 13px; color: #3d5555; display: flex; align-items: center; gap: 8px; }
    .pieces-block li::before { content: '✔'; color: #326761; font-weight: 700; font-size: 12px; }
    .info-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: #78350f; line-height: 1.6; }
    .footer  { background: #f0f9f7; padding: 20px 40px; text-align: center; font-size: 12px; color: #6b8e8a; border-top: 1px solid #e0e9e8; }
    .footer a { color: #326761; text-decoration: none; }
  </style>
</head>
<body>
  <div class="wrapper">

    <div class="header">
      <h1>✅ Complément de dossier reçu</h1>
      <p>CAP-EPAC — École Polytechnique d'Abomey-Calavi</p>
    </div>

    <div class="body">
      <p class="salut">Bonjour <strong>{{ $nomComplet }}</strong>,</p>

      <p class="intro">
        Nous avons bien reçu votre complément de dossier déposé le
        <strong>{{ $dateComplement }}</strong>.
        Les nouvelles pièces ont été enregistrées et transmises au secrétariat pour traitement.
      </p>

      <!-- Numéro de référence -->
      <div class="ref-box">
        <div class="label">Numéro de référence — inchangé</div>
        <div class="value">{{ $reference }}</div>
      </div>

      <!-- Liste des pièces -->
      <div class="pieces-block">
        <h3>Pièces déposées</h3>
        <ul>
          @foreach($piecesList as $piece)
            <li>{{ $piece }}</li>
          @endforeach
        </ul>
      </div>

      <!-- Conseil -->
      <div class="info-box">
        ℹ️ <strong>Conservez ce numéro de référence</strong> pour suivre l'avancement de votre
        dossier sur notre espace étudiant. Si vous avez des questions, contactez le secrétariat
        du CAP-EPAC.
      </div>

      <p style="font-size:13px;color:#3d5555;">
        Matricule associé : <strong>{{ $matricule }}</strong>
      </p>
    </div>

    <div class="footer">
      <p>CAP-EPAC — Université d'Abomey-Calavi, Bénin</p>
      <p><a href="https://cap-epac.online">cap-epac.online</a></p>
      <p style="margin-top:8px;color:#9ab5b2;">Ce message est généré automatiquement, merci de ne pas y répondre.</p>
    </div>

  </div>
</body>
</html>
