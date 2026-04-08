<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<title>Quittance {{ $quittanceNumber }}</title>
<style>
  @font-face {
    font-family: "DejaVu Sans";
    src: url({{ storage_path('fonts/DejaVuSans.ttf') }});
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #ffffff;
  }

  /* ══ Page ══ */
  .page {
    width: 100%;
    min-height: 270mm;
    padding: 10mm 14mm 20mm;
    position: relative;
  }

  /* ══ En-tête : logos + texte République ══ */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2mm;
  }
  .header-table td {
    vertical-align: middle;
    padding: 0 3mm;
  }
  .header-logo {
    width: 18mm;
  }
  .header-center {
    text-align: center;
    line-height: 1.4;
  }
  .header-center .republic {
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .header-center .ministere {
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-top: 1mm;
    color: #333;
  }
  .header-center .universite {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 1mm;
  }
  .header-center .ecole {
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    color: #2E5AAC;
    margin-top: 1mm;
  }
  .header-center .direction {
    font-size: 9px;
    font-style: italic;
    color: #2E5AAC;
    margin-top: 1mm;
  }
  .header-logo-right {
    width: 18mm;
    text-align: right;
  }
  .header-logo img, .header-logo-right img {
    height: 70px;
    max-width: 70px;
    object-fit: contain;
  }

  /* ══ Séparateur bleu EPAC ══ */
  .hr-epac {
    border: none;
    border-top: 2.5px solid #2E5AAC;
    margin: 2mm 0;
  }
  .hr-thin {
    border: none;
    border-top: 0.5px solid #ccc;
    margin: 3mm 0;
  }

  /* ══ Titre document ══ */
  .doc-title-block {
    text-align: center;
    margin: 4mm 0 3mm;
  }
  .doc-title-block .doc-label {
    font-size: 8px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-style: italic;
  }
  .doc-title-block h1 {
    font-size: 15px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #1a1a1a;
    margin-top: 1mm;
  }
  .doc-title-block .doc-subtitle {
    font-size: 9px;
    color: #2E5AAC;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 0.5mm;
  }

  /* ══ Bloc numéro quittance (style UNSTIM) ══ */
  .quittance-ref-block {
    border: 1.5px solid #2E5AAC;
    border-radius: 3pt;
    padding: 3mm 6mm;
    margin: 4mm auto;
    width: 160mm;
    background: #f5f8ff;
  }
  .quittance-ref-block table {
    width: 100%;
    border-collapse: collapse;
  }
  .quittance-ref-block td {
    padding: 1.5mm 2mm;
    font-size: 10px;
  }
  .quittance-ref-block .ref-key {
    color: #555;
    font-style: italic;
    width: 40%;
  }
  .quittance-ref-block .ref-val {
    font-weight: bold;
    color: #1a1a1a;
    font-family: "Courier New", monospace;
    font-size: 11px;
  }
  .quittance-ref-block .ref-title {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #2E5AAC;
    padding-bottom: 1.5mm;
    border-bottom: 0.5px solid #ccc;
    margin-bottom: 1mm;
  }

  /* ══ Bloc montant (bandeau vert foncé) ══ */
  .montant-block {
    background: #2E5AAC;
    color: #ffffff;
    padding: 4mm 8mm;
    margin: 4mm 0;
    border-radius: 2pt;
    display: table;
    width: 100%;
  }
  .montant-block .montant-label {
    display: table-cell;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.85;
    vertical-align: middle;
  }
  .montant-block .montant-value {
    display: table-cell;
    text-align: right;
    font-size: 20px;
    font-weight: bold;
    vertical-align: middle;
    letter-spacing: 0.5px;
  }
  .montant-block .montant-value span {
    font-size: 12px;
    font-weight: normal;
    opacity: 0.8;
    margin-left: 3px;
  }

  /* ══ Section formation (style UNSTIM) ══ */
  .section-title {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #2E5AAC;
    border-bottom: 1px solid #2E5AAC;
    padding-bottom: 1mm;
    margin: 4mm 0 2mm;
  }

  /* ══ Table infos ══ */
  .info-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 3mm;
  }
  .info-table tr {
    border-bottom: 0.5px solid #e5e5e5;
  }
  .info-table tr:nth-child(even) td {
    background: #f9f9fb;
  }
  .info-table td {
    padding: 2.5mm 3mm;
    font-size: 10px;
  }
  .info-table td.key {
    color: #666;
    font-style: italic;
    width: 36%;
    font-size: 9px;
  }
  .info-table td.val {
    font-weight: bold;
    color: #111;
  }

  /* ══ Montant en lettres ══ */
  .montant-lettres {
    text-align: center;
    font-size: 11px;
    font-weight: bold;
    margin: 3mm 0 4mm;
    color: #1a1a1a;
  }
  .montant-lettres em {
    font-style: italic;
    font-weight: normal;
    font-size: 9px;
    color: #555;
  }

  /* ══ QR code + validation ══ */
  .bottom-section {
    display: table;
    width: 100%;
    margin-top: 5mm;
  }
  .bottom-left {
    display: table-cell;
    width: 25%;
    vertical-align: top;
  }
  .bottom-center {
    display: table-cell;
    width: 50%;
    vertical-align: middle;
    text-align: center;
    padding: 0 4mm;
  }
  .bottom-right {
    display: table-cell;
    width: 25%;
    vertical-align: top;
    text-align: right;
  }
  .validation-badge {
    border: 1px solid #2E5AAC;
    border-radius: 2pt;
    padding: 3mm 4mm;
    font-size: 9px;
    color: #2E5AAC;
    background: #f5f8ff;
    text-align: center;
    line-height: 1.5;
  }
  .validation-badge strong {
    display: block;
    font-size: 10px;
    margin-bottom: 1mm;
  }

  /* ══ Signature / cachet ══ */
  .sig-box {
    border: 0.5pt dashed #aaa;
    height: 20mm;
    border-radius: 2pt;
    padding: 2mm;
  }
  .sig-box .sig-label {
    font-size: 7.5px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.8px;
  }
  .sig-box .sig-hash {
    font-size: 7px;
    color: #bbb;
    margin-top: 1.5mm;
    font-family: monospace;
    word-break: break-all;
  }

  /* ══ Pied de page ══ */
  .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #2E5AAC;
    color: #ffffff;
    text-align: center;
    padding: 3mm 10mm;
    font-size: 7.5px;
    height: 14mm;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1.6;
  }

  /* ══ Filigrane SIMULATION ══ */
  .watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size: 64px;
    font-weight: 900;
    color: rgba(200, 30, 30, 0.05);
    white-space: nowrap;
    z-index: 0;
    pointer-events: none;
  }
</style>
</head>
<body>

@if($simulation ?? false)
<div class="watermark">SIMULATION</div>
@endif

@php
  $epacLogo = public_path('assets/epac.png');
  $capLogo  = public_path('assets/cap.png');
@endphp

<div class="page">

  {{-- ══ EN-TÊTE ══ --}}
  <table class="header-table">
    <tr>
      <td class="header-logo">
        @if(file_exists($epacLogo))
          <img src="{{ $epacLogo }}" alt="EPAC">
        @endif
      </td>
      <td class="header-center">
        <div class="republic">République du Bénin</div>
        <div class="ministere">Ministère de l'Enseignement Supérieur et de la Recherche Scientifique</div>
        <div class="universite">Université d'Abomey-Calavi</div>
        <div class="ecole">École Polytechnique d'Abomey-Calavi (EPAC)</div>
        <div class="direction">Direction</div>
        <hr class="hr-epac" style="margin-top: 2mm;">
      </td>
      <td class="header-logo-right">
        @if(file_exists($capLogo))
          <img src="{{ $capLogo }}" alt="CAP">
        @endif
      </td>
    </tr>
  </table>

  {{-- ══ TITRE ══ --}}
  <div class="doc-title-block">
    <div class="doc-label">Document officiel — Paiement en ligne</div>
    <h1>Quittance de Paiement</h1>
    <div class="doc-subtitle">CAP-EPAC — Université d'Abomey-Calavi | Trésor Public du Bénin</div>
  </div>

  <hr class="hr-thin">

  {{-- ══ RÉFÉRENCE DE LA QUITTANCE (style UNSTIM) ══ --}}
  <div class="quittance-ref-block">
    <div class="ref-title">Référence de la quittance</div>
    <table>
      <tr>
        <td class="ref-key">Quittance N°</td>
        <td class="ref-val">{{ $quittanceNumber }}</td>
        <td class="ref-key">Montant versé</td>
        <td class="ref-val">{{ number_format($montant, 0, ',', ' ') }} CFA</td>
      </tr>
      <tr>
        <td class="ref-key">Étudiant</td>
        <td class="ref-val">{{ $nomEtudiant }}</td>
        <td class="ref-key">Matricule</td>
        <td class="ref-val">{{ $matricule }}</td>
      </tr>
      <tr>
        <td class="ref-key">Mode de paiement</td>
        <td class="ref-val">Paiement en ligne</td>
        <td class="ref-key">Date de paiement</td>
        <td class="ref-val">{{ $datePaiement }}</td>
      </tr>
    </table>
  </div>

  {{-- ══ BLOC MONTANT ══ --}}
  <div class="montant-block">
    <div class="montant-label">Montant versé</div>
    <div class="montant-value">{{ number_format($montant, 0, ',', ' ') }}<span>CFA</span></div>
  </div>

  {{-- ══ MONTANT EN LETTRES ══ --}}
  <div class="montant-lettres">
    Arrêté la présente quittance à la somme de <em>{{ $montantLettres ?? number_format($montant, 0, ',', ' ') . ' FCFA' }}</em>
  </div>

  {{-- ══ SECTION PAIEMENT ══ --}}
  <div class="section-title">Détails du paiement</div>
  <table class="info-table">
    <tr>
      <td class="key">Motif du paiement</td>
      <td class="val">{{ $motif }}</td>
    </tr>
    <tr>
      <td class="key">Organisme bénéficiaire</td>
      <td class="val">CAP-EPAC — Université d'Abomey-Calavi</td>
    </tr>
    <tr>
      <td class="key">Compte bénéficiaire</td>
      <td class="val" style="font-family: monospace;">Trésor Public du Bénin — Paiement dématérialisé</td>
    </tr>
    <tr>
      <td class="key">Référence demande</td>
      <td class="val" style="font-family: monospace; color: #2E5AAC;">{{ $referenceDemande }}</td>
    </tr>
    <tr>
      <td class="key">Mode de règlement</td>
      <td class="val">Paiement en ligne — Plateforme sécurisée Trésor Public</td>
    </tr>
    <tr>
      <td class="key">Date d'impression</td>
      <td class="val">{{ \Carbon\Carbon::now()->setTimezone('Africa/Porto-Novo')->format('d/m/Y H:i:s') }}</td>
    </tr>
  </table>

  {{-- ══ BAS DE PAGE : QR + VALIDATION + SIGNATURE ══ --}}
  <div class="bottom-section">

    {{-- QR Code --}}
    <div class="bottom-left">
      @if(isset($qrCode))
        <img src="data:image/png;base64,{{ base64_encode($qrCode) }}" alt="QR Code" style="width: 28mm; height: 28mm;">
      @else
        <div style="width: 28mm; height: 28mm; border: 0.5pt dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 7px; color: #aaa; text-align: center; padding: 2mm;">
          Code<br>vérification
        </div>
      @endif
    </div>

    {{-- Badge de validation ══ --}}
    <div class="bottom-center">
      <div class="validation-badge">
        <strong>&#10003; Document authentique — Paiement validé</strong>
        Ce document constitue une preuve officielle de paiement.<br>
        Conservez-le soigneusement. Il vous sera demandé lors du retrait de votre document.
      </div>
    </div>

    {{-- Signature électronique ══ --}}
    <div class="bottom-right">
      <div class="sig-box">
        <div class="sig-label">Signature électronique</div>
        <div class="sig-hash">{{ strtolower(md5($quittanceNumber . $datePaiement)) }}</div>
      </div>
    </div>

  </div>

  {{-- ══ PIED DE PAGE (style bande bleue EPAC) ══ --}}
  <div class="footer">
    01 B.P. 2009 COTONOU &nbsp;·&nbsp; Tél : 01 95 74 34 54 &nbsp;·&nbsp; IFU : 4201710123211 &nbsp;·&nbsp;
    cap@epac.uac.bj &nbsp;·&nbsp; www.cap-epac.bj &nbsp;·&nbsp;
    Réf. {{ $quittanceNumber }} &nbsp;·&nbsp; Émis le {{ $datePaiement }}
  </div>

</div>
</body>
</html>
