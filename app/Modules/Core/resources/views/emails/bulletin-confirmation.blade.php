{{-- app/Modules/Core/resources/views/emails/bulletin-confirmation.blade.php --}}
{{-- Variables : $reference, $type, $academicYear, $submittedAt, $paymentMethod, $paymentRef --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmation de demande de bulletin — CAP-EPAC</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, Arial, sans-serif; background-color: #f4f7f6; color: #333; line-height: 1.6; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .header { background-color: #ffffff; padding: 30px 40px; text-align: center; border-bottom: 3px solid #005043; }
    .logo { max-width: 120px; margin-bottom: 15px; }
    .institution-name { color: #005043; font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
    .hero { background-color: #f8faf9; padding: 40px; text-align: center; }
    .hero h1 { color: #1a1a1a; font-size: 24px; font-weight: 700; margin-bottom: 10px; }
    .badge-success { display: inline-block; background: #e6f0ee; color: #005043; padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; margin-top: 10px; }
    .content { padding: 40px; }
    .greeting { font-size: 16px; font-weight: 600; margin-bottom: 15px; }
    .text-muted { color: #555; font-size: 15px; margin-bottom: 30px; }
    .ref-card { background: #ffffff; border: 2px dashed #005043; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px; }
    .ref-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
    .ref-code { font-family: 'Monaco', 'Courier New', monospace; font-size: 28px; font-weight: 700; color: #005043; letter-spacing: 2px; }
    .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .details-table td { padding: 12px 0; border-bottom: 1px solid #eee; font-size: 14px; }
    .label { color: #888; width: 40%; }
    .value { color: #1a1a1a; font-weight: 600; text-align: right; }
    .payment-box { background: #f0f7f4; border: 1.5px solid #b2d8cc; border-left: 4px solid #005043; border-radius: 8px; padding: 18px 20px; margin-bottom: 30px; }
    .payment-box h4 { color: #005043; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
    .quittance-num { font-family: 'Monaco', 'Courier New', monospace; font-size: 16px; font-weight: 700; color: #005043; background: #fff; border: 1px solid #b2d8cc; border-radius: 4px; padding: 6px 12px; display: inline-block; margin: 6px 0; letter-spacing: 1px; }
    .payment-box p { font-size: 13px; color: #2c5f47; margin-top: 4px; }
    .action-box { background-color: #fff5f5; border-left: 4px solid #E30613; padding: 20px; margin-bottom: 30px; }
    .action-box h4 { color: #E30613; font-size: 14px; text-transform: uppercase; margin-bottom: 8px; }
    .action-box p { font-size: 14px; color: #444; }
    .btn { display: block; background-color: #005043; color: #ffffff !important; text-align: center; padding: 16px 20px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; }
    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #999; background: #f8faf9; }
    .footer p { margin-bottom: 8px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="https://cap-epac.online/images/logo.png" alt="Logo CAP-EPAC" class="logo">
      <div class="institution-name">Centre Autonome de Perfectionnement</div>
    </div>
    <div class="hero">
      <h1>Confirmation de Demande</h1>
      <div class="badge-success">Dossier enregistré avec succès</div>
    </div>
    <div class="content">
      <p class="greeting">Bonjour,</p>
      <p class="text-muted">
        Votre demande de bulletin a été prise en compte par nos services.
        Un agent administratif procèdera à la vérification de vos pièces sous peu.
      </p>
      <div class="ref-card">
        <div class="ref-label">Numéro de suivi</div>
        <div class="ref-code">{{ $reference }}</div>
      </div>
      <table class="details-table">
        <tr>
          <td class="label">Type de document</td>
          <td class="value">
            @php
              $semLabels = [
                'bulletin_s1' => 'Bulletin — Semestre 1', 'bulletin_s2' => 'Bulletin — Semestre 2',
                'bulletin_s3' => 'Bulletin — Semestre 3', 'bulletin_s4' => 'Bulletin — Semestre 4',
                'bulletin_s5' => 'Bulletin — Semestre 5', 'bulletin_s6' => 'Bulletin — Semestre 6',
                'bulletin_s7' => 'Bulletin — Semestre 7', 'bulletin_s8' => 'Bulletin — Semestre 8',
                'bulletin_annuel' => 'Bulletin annuel',
              ];
            @endphp
            {{ $semLabels[$type] ?? $type }}
          </td>
        </tr>
        <tr>
          <td class="label">Année académique</td>
          <td class="value">{{ $academicYear }}</td>
        </tr>
        <tr>
          <td class="label">Date de dépôt</td>
          <td class="value">{{ $submittedAt }}</td>
        </tr>
        <tr>
          <td class="label">Mode de paiement</td>
          <td class="value">
            @if(($paymentMethod ?? 'manual') === 'tresor_online')
              <span style="color:#005043;font-weight:700;">Paiement en ligne — Trésor Public</span>
            @else
              Quittance physique
            @endif
          </td>
        </tr>
        <tr>
          <td class="label">État actuel</td>
          <td class="value" style="color: #d97706;">En attente de traitement</td>
        </tr>
      </table>

      @if(($paymentMethod ?? 'manual') === 'tresor_online' && !empty($paymentRef))
      <div class="payment-box">
        <h4>✓ Paiement en ligne confirmé</h4>
        <div class="quittance-num">{{ $paymentRef }}</div>
        <p>
          Votre quittance officielle du Trésor Public du Bénin est jointe à cet email en pièce jointe <strong>(PDF)</strong>.<br>
          Conservez-la soigneusement — elle vous sera demandée lors du retrait.
        </p>
      </div>
      @endif

      <div class="action-box">
        <h4>📌 Prochaine étape</h4>
        @if(($paymentMethod ?? 'manual') === 'tresor_online')
          <p>Votre paiement a été validé en ligne. Présentez-vous au secrétariat avec votre <strong>quittance PDF ci-jointe</strong> (imprimée ou sur mobile) et vos pièces justificatives pour finaliser votre demande.</p>
        @else
          <p>Veuillez vous présenter au secrétariat muni de votre <strong>quittance originale de paiement</strong> et des pièces justificatives pour finaliser votre demande.</p>
        @endif
      </div>

      <a href="https://cap-epac.online/student-services?type=suivi-demandes&ref={{ $reference }}" class="btn">
        Suivre l'avancement en ligne
      </a>
    </div>
    <div class="footer">
      <p><strong>CAP-EPAC — Université d'Abomey-Calavi</strong></p>
      <p>Ceci est un message automatique, merci de ne pas y répondre.</p>
      <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
        &copy; {{ date('Y') }} CAP-EPAC. Tous droits réservés.
      </div>
    </div>
  </div>
</body>
</html>
