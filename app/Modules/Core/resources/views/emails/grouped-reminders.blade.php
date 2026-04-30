{{-- resources/views/core/emails/grouped-reminders.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Action requise : Dossiers en attente</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.05); }
    .header { background:#1e293b; padding:24px 32px; text-align:center; border-bottom:4px solid #f59e0b; }
    .header h1 { color:#ffffff; font-size:20px; font-weight:600; letter-spacing:0.5px; }
    .body { padding:32px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:16px; color:#0f172a; }
    .text { margin-bottom:24px; color:#475569; }
    .alert-box { background:#fffbeb; border:1px solid #fde68a; border-radius:6px; padding:16px; color:#92400e; font-size:14px; margin-bottom:24px; font-weight:500; }
    .demande-list { list-style:none; margin-bottom:24px; }
    .demande-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:16px; margin-bottom:12px; }
    .demande-item:last-child { margin-bottom:0; }
    .demande-ref { font-family:monospace; font-weight:600; color:#334155; background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:13px; }
    .demande-details { font-size:14px; color:#475569; margin-top:6px; }
    .demande-date { font-size:13px; color:#ef4444; margin-top:4px; font-weight:500; }
    .cta-container { text-align:center; margin:32px 0; }
    .cta-button { display:inline-block; background-color:#f59e0b; color:#ffffff !important; text-decoration:none; padding:12px 28px; border-radius:6px; font-weight:600; font-size:15px; transition:background-color 0.2s; }
    .cta-button:hover { background-color:#d97706; }
    .footer { background:#f8fafc; padding:20px 32px; text-align:center; font-size:13px; color:#64748b; border-top:1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ config('app.name', 'CAP-EPAC') }}</h1>
    </div>
    <div class="body">
      <p class="greeting">Bonjour {{ $destinataireNom ?? '' }},</p>
      
      <div class="alert-box">
        Ceci est un rappel automatique du système de gestion. Vous avez actuellement {{ $count }} dossier(s) en attente de traitement depuis plus de 36 heures.
      </div>

      <p class="text"><strong>Dossiers concernés :</strong></p>
      
      <ul class="demande-list">
        @foreach(array_slice(is_array($demandes) ? $demandes : iterator_to_array($demandes), 0, 5) as $demande)
          @php
              $typeLabel = \App\Modules\Demandes\WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;
              $date = \Carbon\Carbon::parse($demande->updated_at)->format('d/m/Y à H:i');
          @endphp
          <li class="demande-item">
            <div><span class="demande-ref">{{ $demande->reference }}</span> — <strong>{{ $typeLabel }}</strong></div>
            <div class="demande-details">Étudiant(e) : {{ $demande->first_names }} {{ $demande->last_name }}</div>
            <div class="demande-date">En attente depuis le {{ $date }}</div>
          </li>
        @endforeach
      </ul>
      
      @if($count > 5)
        <p class="text" style="font-size:13px; font-style:italic;">... et {{ $count - 5 }} autre(s) dossier(s).</p>
      @endif

      <p class="text">
        Afin de garantir la fluidité du circuit de validation, merci de vous connecter au tableau de bord pour traiter ces éléments dans les plus brefs délais.
      </p>

      <div class="cta-container">
        <a href="{{ $urlEspace ?? config('app.url') . '/dashboard' }}" class="cta-button">Traiter mes dossiers</a>
      </div>
    </div>
    <div class="footer">
      <p>Notification automatique du système de gestion des demandes.</p>
    </div>
  </div>
</body>
</html>
