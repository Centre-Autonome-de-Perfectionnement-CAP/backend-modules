<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>{{ $details['title'] }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background-color: #eef5f0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; color: #1a2e1f; -webkit-font-smoothing: antialiased; }
    .email-wrapper { width: 100%; background-color: #eef5f0; padding: 40px 16px; }
    .email-container { max-width: 600px; margin: 0 auto; }

    /* Header */
    .email-header { background-color: #1b5e2a; border-radius: 12px 12px 0 0; padding: 36px 40px; text-align: center; }
    .header-logo-row { display: flex; align-items: center; justify-content: center; gap: 24px; margin-bottom: 28px; }
    .header-logo-divider { width: 1px; height: 40px; background-color: rgba(255,255,255,0.3); }
    .header-institution { color: rgba(255,255,255,0.8); font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px; }
    .header-title { color: #ffffff; font-size: 22px; font-weight: 700; line-height: 1.3; }
    .header-subtitle { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 6px; }
    .status-badge { display: inline-block; background-color: #ffffff; color: #1b5e2a; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 5px 16px; border-radius: 20px; margin-top: 18px; border: 1px solid rgba(255,255,255,0.3); }

    /* Body */
    .email-body { background-color: #ffffff; padding: 40px; }
    .greeting { font-size: 16px; color: #1a2e1f; margin-bottom: 16px; }
    .greeting strong { color: #1b5e2a; }
    .intro-text { font-size: 14px; line-height: 1.75; color: #2c4a2e; margin-bottom: 24px; }

    /* Titre section */
    .section-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #4a7c51; margin-bottom: 10px; margin-top: 24px; }

    /* Info card */
    .info-card { background-color: #f5faf6; border: 1px solid #c8e0cf; border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
    .info-card-header { background-color: #1b5e2a; padding: 10px 20px; }
    .info-card-header-text { color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table tr { border-bottom: 1px solid #d4e8da; }
    .info-table tr:last-child { border-bottom: none; }
    .info-table td { padding: 10px 20px; font-size: 13px; }
    .info-table td:first-child { color: #4a7c51; font-weight: 500; width: 42%; }
    .info-table td:last-child { color: #1a2e1f; font-weight: 600; }

    /* Programmes */
    .programmes-list { background-color: #f5faf6; border: 1px solid #c8e0cf; border-radius: 10px; padding: 14px 20px; margin-bottom: 20px; }
    .programme-item { font-size: 13px; color: #1a2e1f; padding: 4px 0; border-bottom: 1px solid #e0ede3; line-height: 1.5; }
    .programme-item:last-child { border-bottom: none; }
    .programme-code { font-weight: 700; color: #1b5e2a; }

    /* Procédure */
    .procedure-list { background-color: #f5faf6; border: 1px solid #c8e0cf; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; list-style: none; }
    .procedure-list li { font-size: 13px; color: #2c4a2e; line-height: 1.6; padding: 4px 0; display: flex; gap: 10px; }
    .procedure-step { background-color: #1b5e2a; color: #fff; font-size: 11px; font-weight: 700; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }

    /* CTA */
    .cta-section { text-align: center; margin: 28px 0; }
    .cta-label { font-size: 13px; color: #4a7c51; margin-bottom: 14px; }
    .btn-primary { display: inline-block; background-color: #1b5e2a; color: #ffffff !important; text-decoration: none !important; font-size: 15px; font-weight: 700; padding: 15px 40px; border-radius: 8px; letter-spacing: 0.3px; }

    /* Notice */
    .notice-box { background-color: #fffbeb; border-left: 4px solid #d97706; border-right: 1px solid #fde68a; border-top: 1px solid #fde68a; border-bottom: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; }
    .notice-box p { font-size: 13px; color: #92400e; line-height: 1.6; }
    .notice-box strong { color: #78350f; }

    /* Confidentialité */
    .confidential-note { font-size: 12px; color: #6b7280; font-style: italic; margin-bottom: 20px; }

    /* Divider */
    .divider { border: none; border-top: 1px solid #d4e8da; margin: 20px 0; }

    /* Lien alternatif */
    .link-fallback { font-size: 12px; color: #4a7c51; line-height: 1.6; margin-bottom: 20px; }
    .link-fallback a { color: #1b5e2a; word-break: break-all; text-decoration: underline; }

    /* Footer */
    .email-footer { background-color: #f5faf6; border: 1px solid #c8e0cf; border-radius: 0 0 12px 12px; padding: 24px 40px; text-align: center; }
    .footer-institution { font-size: 12px; font-weight: 700; color: #1b5e2a; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
    .footer-address { font-size: 11px; color: #5a8c64; line-height: 1.6; }
    .footer-legal { font-size: 11px; color: #8fba99; margin-top: 14px; padding-top: 14px; border-top: 1px solid #c8e0cf; }

    @media only screen and (max-width: 480px) {
      .email-header, .email-body, .email-footer { padding: 24px 20px; }
      .header-logo-row { flex-direction: column; gap: 12px; }
      .header-logo-divider { display: none; }
      .header-title { font-size: 18px; }
      .btn-primary { display: block; padding: 14px 20px; }
      .info-table td { display: block; width: 100%; padding: 6px 16px; }
      .info-table td:first-child { padding-bottom: 2px; background-color: #eaf5ed; font-size: 11px; }
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="email-container">

      <!-- ── Header ─────────────────────────────────────────────────────── -->
      <div class="email-header">
        <div class="header-logo-row">
          <div>
            <div style="color:rgba(255,255,255,0.7);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-bottom:2px;">École Polytechnique</div>
            <div style="color:#ffffff;font-size:13px;font-weight:700;letter-spacing:1px;">d'Abomey-Calavi</div>
          </div>
          <div class="header-logo-divider"></div>
          <div>
            <div style="color:rgba(255,255,255,0.7);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-bottom:2px;">Centre Autonome de</div>
            <div style="color:#ffffff;font-size:13px;font-weight:700;letter-spacing:1px;">Perfectionnement</div>
          </div>
        </div>
        <div class="header-institution">Gestion des ressources humaines</div>
        <div class="header-title">Contrat d'enseignement</div>
        <div class="header-subtitle">Votre signature est requise</div>
        <div><span class="status-badge">Action requise</span></div>
      </div>

      <!-- ── Body ───────────────────────────────────────────────────────── -->
      <div class="email-body">

        <p class="greeting">Bonjour, <strong>{{ $details['professor_name'] }}</strong>,</p>

        <p class="intro-text">
          Le Centre Autonome de Perfectionnement (CAP) de l'École Polytechnique d'Abomey-Calavi
          vous a adressé un contrat d'enseignement pour l'année académique
          <strong>{{ $details['academic_year'] }}</strong>.
          Veuillez en prendre connaissance et procéder à sa validation dans les meilleurs délais.
        </p>

        <p class="intro-text" style="margin-top:-12px;">
          Votre contrat de prestation <strong>N° {{ $details['contrat_number'] }}</strong>
          est disponible et en attente de votre signature.
        </p>

        <!-- Détails du contrat -->
        <div class="section-title">Détails du contrat</div>
        <div class="info-card">
          <table class="info-table">
            @if($details['division'] !== '—')
            <tr>
              <td>Division</td>
              <td>{{ $details['division_label'] }}</td>
            </tr>
            @endif
            @if($details['regroupement'] !== '—')
            <tr>
              <td>Regroupement</td>
              <td>{{ $details['regroupement_label'] }}</td>
            </tr>
            @endif
            <tr>
              <td>Cycle</td>
              <td>{{ $details['cycle'] }}</td>
            </tr>
            <tr>
              <td>Période</td>
              <td>du {{ $details['start_date'] }} au {{ $details['end_date'] }}</td>
            </tr>
            <tr>
              <td>Montant</td>
              <td>{{ $details['amount'] }} FCFA</td>
            </tr>
          </table>
        </div>

        <!-- Programmes -->
        @if(!empty($details['programmes']))
        <div class="section-title">Programmes concernés</div>
        <div class="programmes-list">
          @foreach($details['programmes'] as $prog)
            @php
              $parts = explode(' : ', $prog, 2);
              $code  = $parts[0] ?? '';
              $label = $parts[1] ?? $prog;
            @endphp
            <div class="programme-item">
              <span class="programme-code">{{ $code }}</span>{{ $code ? ' : ' : '' }}{{ $label }}
            </div>
          @endforeach
        </div>
        @endif

        <!-- Procédure de signature -->
        <div class="section-title">Procédure de signature</div>
        <ul class="procedure-list">
          <li>
            <span class="procedure-step">1</span>
            Connectez-vous à votre espace sur le lien ci-dessous
          </li>
          <li>
            <span class="procedure-step">2</span>
            Consultez votre e-mail (<strong>{{ $details['professor_email'] }}</strong>)
            pour le lien de signature sécurisé
          </li>
          <li>
            <span class="procedure-step">3</span>
            Vérifiez toutes les informations du contrat
          </li>
          <li>
            <span class="procedure-step">4</span>
            Signez électroniquement
          </li>
        </ul>

        <!-- CTA connexion -->
        <div class="cta-section">
          <p class="cta-label">Accéder à votre espace professeur</p>
          <a href="{{ $details['login_url'] }}" class="btn-primary" target="_blank">
            Se connecter
          </a>
        </div>

        <hr class="divider" />

        <!-- Lien alternatif -->
        <div class="link-fallback">
          Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br />
          <a href="{{ $details['login_url'] }}">{{ $details['login_url'] }}</a>
        </div>

        <!-- Notice délai -->
        <div class="notice-box">
          <p>
            <strong>Attention :</strong> Ce lien est valable pendant
            <strong>{{ $details['link_expiry_hours'] }} heures</strong>
            à compter de la réception de ce message.
            Passé ce délai, veuillez contacter le service RH du CAP.
          </p>
        </div>

        <p class="confidential-note">Ce lien est strictement personnel et confidentiel.</p>

        <p style="font-size:13px;color:#1a2e1f;line-height:1.7;margin-top:8px;">
          Cordialement,<br />
          <strong>Service des Ressources Humaines — CAP-EPAC</strong>
        </p>

      </div>

      <!-- ── Footer ─────────────────────────────────────────────────────── -->
      <div class="email-footer">
        <div class="footer-institution">CAP — École Polytechnique d'Abomey-Calavi</div>
        <div class="footer-address">Abomey-Calavi, Bénin • Service des Ressources Humaines</div>
        <div class="footer-legal">
          Cet email a été envoyé automatiquement par le système de gestion RH du CAP.
          Merci de ne pas répondre directement à cet email.
        </div>
      </div>

    </div>
  </div>
</body>
</html>
