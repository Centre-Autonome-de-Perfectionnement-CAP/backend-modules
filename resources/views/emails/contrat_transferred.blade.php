<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>{{ $details['title'] }}</title>
  <!--[if mso]>
  <noscript>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
  </noscript>
  <![endif]-->
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background-color: #f0f2f5; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
    .email-wrapper { width: 100%; background-color: #f0f2f5; padding: 40px 16px; }
    .email-container { max-width: 600px; margin: 0 auto; }

    /* Header */
    .email-header { background: linear-gradient(135deg, #0d3b6e 0%, #1a5fa8 60%, #0d3b6e 100%); border-radius: 12px 12px 0 0; padding: 36px 40px; text-align: center; }
    .header-logo-row { display: flex; align-items: center; justify-content: center; gap: 24px; margin-bottom: 28px; }
    .header-logo-divider { width: 1px; height: 40px; background-color: rgba(255,255,255,0.3); }
    .header-institution { color: rgba(255,255,255,0.85); font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 6px; }
    .header-title { color: #ffffff; font-size: 22px; font-weight: 700; line-height: 1.3; }
    .header-subtitle { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 6px; }

    /* Badge statut */
    .status-badge { display: inline-block; background-color: #f59e0b; color: #1a1a2e; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 5px 16px; border-radius: 20px; margin-top: 18px; }

    /* Body */
    .email-body { background-color: #ffffff; padding: 40px; }
    .greeting { font-size: 16px; color: #1a1a2e; margin-bottom: 16px; }
    .greeting strong { color: #0d3b6e; }
    .intro-text { font-size: 14px; line-height: 1.75; color: #4a4a6a; margin-bottom: 28px; }

    /* Info card */
    .info-card { background-color: #f8faff; border: 1px solid #dbe8ff; border-radius: 10px; overflow: hidden; margin-bottom: 28px; }
    .info-card-header { background-color: #0d3b6e; padding: 12px 20px; }
    .info-card-header-text { color: #ffffff; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table tr { border-bottom: 1px solid #e8eeff; }
    .info-table tr:last-child { border-bottom: none; }
    .info-table td { padding: 11px 20px; font-size: 13px; }
    .info-table td:first-child { color: #6b7ab0; font-weight: 500; width: 45%; }
    .info-table td:last-child { color: #1a1a2e; font-weight: 600; }

    /* CTA principal */
    .cta-section { text-align: center; margin: 32px 0; }
    .cta-label { font-size: 13px; color: #6b7ab0; margin-bottom: 14px; }
    .btn-primary { display: inline-block; background: linear-gradient(135deg, #0d3b6e, #1a5fa8); color: #ffffff !important; text-decoration: none !important; font-size: 15px; font-weight: 700; padding: 15px 40px; border-radius: 8px; letter-spacing: 0.3px; }
    .btn-primary:hover { background: #0a2f58; }

    /* Notice urgence */
    .notice-box { background-color: #fff8ed; border: 1px solid #fcd89a; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
    .notice-box p { font-size: 13px; color: #92500a; line-height: 1.6; }
    .notice-box strong { color: #7a3f06; }

    /* Séparateur */
    .divider { border: none; border-top: 1px solid #e8eeff; margin: 24px 0; }

    /* Lien texte alternatif */
    .link-fallback { font-size: 12px; color: #6b7ab0; line-height: 1.6; margin-bottom: 20px; }
    .link-fallback a { color: #1a5fa8; word-break: break-all; text-decoration: underline; }

    /* Footer */
    .email-footer { background-color: #f8faff; border: 1px solid #dbe8ff; border-radius: 0 0 12px 12px; padding: 24px 40px; text-align: center; }
    .footer-institution { font-size: 12px; font-weight: 700; color: #0d3b6e; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
    .footer-address { font-size: 11px; color: #8896c0; line-height: 1.6; }
    .footer-legal { font-size: 11px; color: #b0bad8; margin-top: 14px; padding-top: 14px; border-top: 1px solid #dbe8ff; }

    @media only screen and (max-width: 480px) {
      .email-header { padding: 24px 20px; }
      .email-body { padding: 28px 20px; }
      .email-footer { padding: 20px; }
      .header-logo-row { flex-direction: column; gap: 12px; }
      .header-logo-divider { display: none; }
      .header-title { font-size: 18px; }
      .btn-primary { display: block; padding: 14px 20px; }
      .info-table td { display: block; width: 100%; padding: 6px 16px; }
      .info-table td:first-child { padding-bottom: 2px; background-color: #f0f4ff; font-size: 11px; }
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="email-container">

      <!-- ── En-tête ── -->
      <div class="email-header">
        <div class="header-logo-row">
          <div>
            <div style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-bottom:2px;">École Polytechnique</div>
            <div style="color:#ffffff;font-size:13px;font-weight:700;letter-spacing:1px;">d'Abomey-Calavi</div>
          </div>
          <div class="header-logo-divider"></div>
          <div>
            <div style="color:rgba(255,255,255,0.6);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin-bottom:2px;">Centre Autonome de</div>
            <div style="color:#ffffff;font-size:13px;font-weight:700;letter-spacing:1px;">Perfectionnement</div>
          </div>
        </div>

        <div class="header-institution">Gestion des ressources humaines</div>
        <div class="header-title">Contrat d'enseignement</div>
        <div class="header-subtitle">Votre signature est requise</div>
        <div><span class="status-badge">Action requise</span></div>
      </div>

      <!-- ── Corps ── -->
      <div class="email-body">
        <p class="greeting">Bonjour, <strong>{{ $details['professor_name'] }}</strong>,</p>
        <p class="intro-text">
          Le Centre Autonome de Perfectionnement (CAP) de l'École Polytechnique d'Abomey-Calavi
          vous a adressé un contrat d'enseignement pour l'année académique
          <strong>{{ $details['academic_year'] }}</strong>.
          Veuillez en prendre connaissance et procéder à sa validation dans les meilleurs délais.
        </p>

        <!-- Fiche contrat -->
        <div class="info-card">
          <div class="info-card-header">
            <span class="info-card-header-text">Récapitulatif du contrat</span>
          </div>
          <table class="info-table">
            <tr>
              <td>Numéro de contrat</td>
              <td>N° {{ $details['contrat_number'] }}</td>
            </tr>
            <tr>
              <td>Année académique</td>
              <td>{{ $details['academic_year'] }}</td>
            </tr>
            <tr>
              <td>Cycle</td>
              <td>{{ $details['cycle'] }}</td>
            </tr>
            @if($details['division'] !== '—')
            <tr>
              <td>Division</td>
              <td>{{ $details['division'] }}</td>
            </tr>
            @endif
            @if($details['regroupement'] !== '—')
            <tr>
              <td>Regroupement</td>
              <td>{{ $details['regroupement'] }}</td>
            </tr>
            @endif
            <tr>
              <td>Date de prise d'effet</td>
              <td>{{ $details['start_date'] }}</td>
            </tr>
            <tr>
              <td>Montant du contrat</td>
              <td>{{ $details['amount'] }} FCFA</td>
            </tr>
          </table>
        </div>

        <!-- Notice de délai -->
        <div class="notice-box">
          <p>
            <strong>Attention :</strong> Ce lien est valable pendant
            <strong>{{ $details['link_expiry_hours'] }} heures</strong> à compter de la réception
            de cet email. Passé ce délai, veuillez contacter le service RH du CAP.
          </p>
        </div>

        <!-- CTA -->
        <div class="cta-section">
          <p class="cta-label">Cliquez sur le bouton ci-dessous pour consulter et signer votre contrat</p>
          <a href="{{ $details['contrat_url'] }}" class="btn-primary" target="_blank">
            Consulter mon contrat
          </a>
        </div>

        <hr class="divider" />

        <!-- Lien alternatif texte -->
        <div class="link-fallback">
          Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br />
          <a href="{{ $details['contrat_url'] }}">{{ $details['contrat_url'] }}</a>
        </div>

        <p style="font-size:13px;color:#6b7ab0;line-height:1.7;">
          Pour toute question relative à ce contrat, veuillez contacter le service
          des ressources humaines du CAP directement par email ou par téléphone.
        </p>
      </div>

      <!-- ── Pied de page ── -->
      <div class="email-footer">
        <div class="footer-institution">CAP &mdash; École Polytechnique d'Abomey-Calavi</div>
        <div class="footer-address">
          Abomey-Calavi, Bénin &bull; Service des Ressources Humaines
        </div>
        <div class="footer-legal">
          Cet email a été envoyé automatiquement par le système de gestion RH du CAP.
          Merci de ne pas répondre directement à cet email.
        </div>
      </div>

    </div>
  </div>
</body>
</html>
