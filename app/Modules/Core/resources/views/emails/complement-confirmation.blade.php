{{-- resources/views/core/emails/complement-confirmation.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complément de dossier reçu — {{ $reference ?? '—' }}</title>
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
    .pieces-list { margin-top:12px; padding-left:20px; color:#475569; }
    .pieces-list li { margin-bottom:6px; }
    .footer { background:#f8fafc; padding:20px 32px; text-align:center; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ config('app.name', 'CAP-EPAC') }}</h1>
    </div>
    <div class="body">
      <p class="greeting">Bonjour {{ $nomComplet ?? '' }},</p>
      <p class="text">
        Nous accusons réception des pièces complémentaires que vous avez fournies pour votre dossier.
      </p>

      <div class="info-card">
        <div class="info-row">
          <div class="info-label">Référence</div>
          <div class="info-value"><span class="ref-badge">{{ $reference ?? '—' }}</span></div>
        </div>
        <div class="info-row" style="flex-direction:column; margin-top:16px;">
          <div class="info-label" style="margin-bottom:8px;">Pièces reçues</div>
          <div class="info-value">
            @if(!empty($piecesList) && count($piecesList) > 0)
              <ul class="pieces-list">
                @foreach($piecesList as $piece)
                  <li>{{ $piece }}</li>
                @endforeach
              </ul>
            @else
              <em>Aucune pièce spécifiée</em>
            @endif
          </div>
        </div>
      </div>

      <p class="text">
        Ces éléments ont été transmis au secrétariat pour vérification. Le traitement de votre demande va pouvoir reprendre.
      </p>
    </div>
    <div class="footer">
      <p>Cet e-mail a été généré automatiquement. Merci de ne pas y répondre.</p>
      <p style="margin-top:8px;">Le Secrétariat du {{ config('app.name', 'CAP-EPAC') }}</p>
    </div>
  </div>
</body>
</html>
