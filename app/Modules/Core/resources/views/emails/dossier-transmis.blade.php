{{-- resources/views/core/emails/dossier-transmis.blade.php --}}
{{-- Utilisé pour : soumission (vers secrétaire) + transmissions inter-acteurs --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dossier à traiter — {{ $reference }}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:620px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.08); }
    .header { background:#326761; padding:32px 40px; text-align:center; }
    .header h1 { color:#fff; font-size:22px; font-weight:700; }
    .header p { color:rgba(255,255,255,.75); font-size:13px; margin-top:6px; }
    .body { padding:36px 40px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:12px; }
    .intro { color:#444; margin-bottom:24px; }
    .info-card { background:#f8fafb; border:1px solid #e2e8f0; border-left:4px solid #326761; border-radius:8px; padding:20px 24px; margin-bottom:24px; }
    .info-card table { width:100%; border-collapse:collapse; }
    .info-card td { padding:6px 0; vertical-align:top; }
    .info-card td:first-child { color:#64748b; font-size:13px; text-transform:uppercase; letter-spacing:.5px; width:140px; }
    .info-card td:last-child { color:#1a1a2e; font-weight:600; }
    .ref-badge { display:inline-block; background:#326761; color:#fff; font-family:'Courier New',monospace; font-size:13px; font-weight:700; padding:3px 10px; border-radius:4px; letter-spacing:1px; }
    .commentaire { background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:14px 18px; margin-bottom:24px; font-size:14px; color:#78350f; }
    .commentaire strong { display:block; margin-bottom:4px; color:#92400e; }
    .cta { text-align:center; margin:28px 0; }
    .cta a { display:inline-block; background:#326761; color:#fff !important; text-decoration:none; padding:13px 32px; border-radius:8px; font-weight:700; font-size:15px; }
    .footer { background:#f8fafb; border-top:1px solid #e2e8f0; padding:20px 40px; text-align:center; font-size:12px; color:#94a3b8; }
    hr { border:none; border-top:1px solid #e2e8f0; margin:24px 0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>{{ $etablissement ?? config('app.name', 'CAP-EPAC') }}</h1>
      <p>Système de gestion des demandes de documents</p>
    </div>
    <div class="body">
      <p class="greeting">Bonjour {{ $destinataireNom }},</p>
      <p class="intro">
        @if(!empty($expediteurNom) && !empty($expediteurRole))
          Un dossier vous a été transmis par <strong>{{ $expediteurNom }}</strong> ({{ $expediteurRole }}) et requiert votre attention.
        @else
          Une nouvelle demande de document vient d'être soumise et requiert votre traitement.
        @endif
      </p>

      <div class="info-card">
        <table>
          <tr><td>Référence</td><td><span class="ref-badge">{{ $reference }}</span></td></tr>
          <tr><td>Document</td><td>{{ $typeDocument }}</td></tr>
          <tr><td>Étudiant(e)</td><td>{{ $etudiantNom }}</td></tr>
          @if(!empty($etudiantMatricule) && $etudiantMatricule !== '—')
          <tr><td>Matricule</td><td>{{ $etudiantMatricule }}</td></tr>
          @endif
          <tr><td>Transmis le</td><td>{{ $dateTransmission }}</td></tr>
          @if(!empty($destinataireRole))
          <tr><td>Pour</td><td>{{ $destinataireRole }}</td></tr>
          @endif
        </table>
      </div>

      @if(!empty($commentaire))
      <div class="commentaire">
        <strong>Commentaire :</strong>{{ $commentaire }}
      </div>
      @endif

      <div class="cta">
        <a href="{{ $urlEspace }}">Accéder au tableau de bord</a>
      </div>

      <hr />
      <p style="font-size:13px;color:#64748b;text-align:center;">Ne répondez pas à cet e-mail.</p>
    </div>
    <div class="footer">
      <p>{{ $etablissement ?? config('app.name', 'CAP-EPAC') }}</p>
    </div>
  </div>
</body>
</html>
