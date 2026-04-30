{{-- resources/views/core/emails/grouped-reminders.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Relance — Dossiers en retard</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:620px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.08); }
    .header { background:#b91c1c; padding:32px 40px; text-align:center; }
    .header h1 { color:#fff; font-size:22px; font-weight:700; }
    .header p { color:rgba(255,255,255,.9); font-size:14px; margin-top:6px; }
    .body { padding:36px 40px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:12px; }
    .intro { color:#444; margin-bottom:24px; }
    .dossier-card { background:#f8fafb; border:1px solid #e2e8f0; border-left:4px solid #b91c1c; border-radius:8px; padding:16px 20px; margin-bottom:16px; }
    .dossier-card table { width:100%; border-collapse:collapse; }
    .dossier-card td { padding:4px 0; vertical-align:top; font-size:14px; }
    .dossier-card td:first-child { color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.5px; width:120px; }
    .dossier-card td:last-child { color:#1a1a2e; font-weight:600; }
    .ref-badge { display:inline-block; background:#e2e8f0; color:#475569; font-family:'Courier New',monospace; font-size:13px; font-weight:700; padding:2px 8px; border-radius:4px; }
    .cta { text-align:center; margin:28px 0; }
    .cta a { display:inline-block; background:#b91c1c; color:#fff !important; text-decoration:none; padding:13px 32px; border-radius:8px; font-weight:700; font-size:15px; }
    .footer { background:#f8fafb; border-top:1px solid #e2e8f0; padding:20px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ $etablissement ?? config('app.name', 'CAP-EPAC') }}</h1>
      <p>Alerte de traitement de dossiers</p>
    </div>
    <div class="body">
      <p class="greeting">Bonjour {{ $destinataireNom }},</p>
      <p class="intro">
        Vous avez actuellement <strong>{{ $count }} dossier{{ $count > 1 ? 's' : '' }}</strong> en attente de traitement depuis plus de 36 heures sur la plateforme. Merci de bien vouloir vous connecter pour les traiter dans les plus brefs délais afin de ne pas bloquer le circuit.
      </p>

      @foreach($demandes as $demande)
      <div class="dossier-card">
        <table>
          <tr><td>Référence</td><td><span class="ref-badge">{{ $demande->reference }}</span></td></tr>
          <tr><td>Document</td><td>{{ \App\Modules\Demandes\WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type }}</td></tr>
          <tr><td>Étudiant(e)</td><td>{{ trim(($demande->first_names ?? '') . ' ' . ($demande->last_name ?? '')) ?: 'Étudiant(e)' }}</td></tr>
          <tr><td>Dernière action</td><td>{{ \Carbon\Carbon::parse($demande->updated_at)->format('d/m/Y à H:i') }}</td></tr>
        </table>
      </div>
      @endforeach

      <div class="cta">
        <a href="{{ $urlEspace }}">Accéder au tableau de bord</a>
      </div>
    </div>
    <div class="footer">
      <p>Ce message automatique remplace les notifications individuelles.</p>
    </div>
  </div>
</body>
</html>
