
{{-- resources/views/core/emails/dossier-transmis.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dossier à traiter — Réf : {{ $reference }}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif; background:#f4f6f8; color:#1a1a2e; font-size:15px; line-height:1.6; }
    .wrapper { max-width:600px; margin:32px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.05); }
    .header { background:#1e293b; padding:24px 32px; text-align:center; border-bottom:4px solid #3b82f6; }
    .header h1 { color:#ffffff; font-size:20px; font-weight:600; letter-spacing:0.5px; }
    .body { padding:32px; }
    .greeting { font-size:16px; font-weight:600; margin-bottom:16px; color:#0f172a; }
    .text { margin-bottom:24px; color:#475569; }
    .info-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:20px; margin-bottom:24px; }
    .info-row { display:flex; margin-bottom:12px; }
    .info-row:last-child { margin-bottom:0; }
    .info-label { width:130px; color:#64748b; font-size:13px; text-transform:uppercase; font-weight:600; }
    .info-value { color:#0f172a; font-weight:500; flex:1; }
    .ref-badge { background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:4px; font-family:monospace; font-size:14px; letter-spacing:1px; }
    .note-box { background:#fffbeb; border:1px solid #fde68a; border-radius:6px; padding:16px; color:#92400e; font-size:14px; margin-bottom:24px; font-style:italic; }
    .cta-container { text-align:center; margin:32px 0; }
    .cta-button { display:inline-block; background-color:#3b82f6; color:#ffffff !important; text-decoration:none; padding:12px 28px; border-radius:6px; font-weight:600; font-size:15px; transition:background-color 0.2s; }
    .cta-button:hover { background-color:#2563eb; }
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
      <p class="text">
        Un dossier nécessitant votre intervention vient de vous être transmis par <strong>{{ $expediteurNom ?? 'le système' }}</strong> {{ !empty($expediteurRole) ? "({$expediteurRole})" : '' }}.
      </p>

      <div class="info-card">
        <div class="info-row">
          <div class="info-label">Référence</div>
          <div class="info-value"><span class="ref-badge">{{ $reference }}</span></div>
        </div>
        <div class="info-row">
          <div class="info-label">Document</div>
          <div class="info-value">{{ $typeDocument ?? '' }}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Étudiant(e)</div>
          <div class="info-value">
            {{ $etudiantNom ?? '' }}
            @if(!empty($etudiantMatricule))
              <span style="color:#64748b; font-size:13px;">({{ $etudiantMatricule }})</span>
            @endif
          </div>
        </div>
      </div>

      @if(!empty($commentaire))
      <div class="note-box">
        <strong>Note :</strong> {{ $commentaire }}
      </div>
      @endif

      <div class="cta-container">
        <a href="{{ $urlEspace ?? config('app.url') . '/dashboard' }}" class="cta-button">Accéder au tableau de bord</a>
      </div>
    </div>
    <div class="footer">
      <p>Notification automatique du système de gestion des demandes.</p>
    </div>
  </div>
</body>
</html>

@extends('core::emails.base')

@section('title', 'Dossier à traiter')

@section('header')
    <h1 style="color: white;">📂 Nouveau dossier à traiter</h1>
    <p style="margin: 5px 0 0 0; color: white;">Un dossier vous a été transmis</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $destinataireNom ?? 'Madame / Monsieur' }}</strong>,</p>

    <p>
        <strong>{{ $expediteurNom ?? 'Un acteur' }}</strong>
        ({{ $expediteurRole ?? '' }}) vous a transmis un dossier qui nécessite votre attention.
    </p>

    <div style="background: #e3f2fd; padding: 20px; border-left: 4px solid #2196F3; margin: 20px 0; text-align: center;">
        <p style="margin: 0; font-size: 16pt; color: #1565c0;">
            <strong>📋 DOSSIER EN ATTENTE DE TRAITEMENT</strong>
        </p>
    </div>

    <h3>Détails du dossier :</h3>
    <table style="background: #f5f5f5; width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Référence :</strong></td>
            <td style="padding: 10px; font-weight: bold; color: #1565c0;">{{ $reference ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Type de document :</strong></td>
            <td style="padding: 10px;">{{ $typeDocument ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Étudiant concerné :</strong></td>
            <td style="padding: 10px;">{{ $etudiantNom ?? '' }}</td>
        </tr>
        @if(!empty($etudiantMatricule))
        <tr>
            <td style="padding: 10px;"><strong>Matricule :</strong></td>
            <td style="padding: 10px;">{{ $etudiantMatricule }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px;"><strong>Transmis le :</strong></td>
            <td style="padding: 10px;">{{ $dateTransmission ?? now()->format('d/m/Y à H:i') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Votre rôle dans ce dossier :</strong></td>
            <td style="padding: 10px; font-weight: bold; color: #e65100;">{{ $destinataireRole ?? '' }}</td>
        </tr>
    </table>

    @if(!empty($commentaire))
    <div style="background: #fff8e1; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p style="margin: 0 0 5px 0;"><strong>💬 Message de {{ $expediteurNom ?? 'l\'expéditeur' }} :</strong></p>
        <p style="margin: 0; font-style: italic;">{{ $commentaire }}</p>
    </div>
    @endif

    <div style="background: #f3e5f5; padding: 15px; border-left: 4px solid #9c27b0; margin: 20px 0;">
        <p style="margin: 0;"><strong>⚡ Action requise :</strong> Veuillez vous connecter à votre espace pour consulter et traiter ce dossier dans les meilleurs délais.</p>
    </div>

    @if(!empty($urlEspace))
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $urlEspace }}" class="button">Accéder au dossier</a>
    </div>
    @endif

    <p style="margin-top: 30px;">Ce message est envoyé automatiquement par le système de gestion des dossiers du CAP-EPAC. Merci de ne pas y répondre directement.</p>

    <p>Cordialement,<br><strong>{{ $etablissement ?? 'Le Système CAP-EPAC' }}</strong></p>
@endsection
 
