<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrat de Prestation d'Enseignement</title>

<style>
body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 14px;
    line-height: 1.6;
    margin: 40px;
    text-align: justify;
}

.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: bold; }
.justify { text-align: justify; }

.section { margin-top: 18px; }

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.table th, .table td {
    border: 1px solid black;
    padding: 6px;
    font-size: 12px;
    text-align: left;
}

.header-table {
    width: 100%;
    margin-bottom: 20px;
    border-collapse: collapse;
}

.header-table td {
    vertical-align: middle;
    padding: 5px;
}

.header-logo {
    width: 20%;
    text-align: center;
}

.header-text {
    width: 60%;
    text-align: center;
    font-size: 14px;
    line-height: 1.4;
}

.header-logo img {
    max-width: 100%;
    height: auto;
    max-height: 80px;
}

/* ── Bloc signature ─────────────────────────────────────────────────────── */
.signature-block {
    margin-top: 60px;
    width: 100%;
    border-collapse: collapse;
}

.signature-block td {
    vertical-align: top;
    width: 50%;
    padding: 10px;
}

.signature-label {
    font-weight: bold;
    margin-bottom: 8px;
}

.signature-image-wrapper {
    border: 1px dashed #999;
    background: #fafafa;
    padding: 6px;
    display: inline-block;
    min-width: 200px;
    min-height: 80px;
    text-align: center;
}

.signature-image-wrapper img {
    max-width: 220px;
    max-height: 100px;
    display: block;
    margin: 0 auto;
}

.signature-meta {
    font-size: 11px;
    color: #555;
    margin-top: 4px;
}

@media print {
    body { margin: 20px; }
    .header-logo img { max-height: 70px; }
}
</style>
</head>

<body>

@php
    function numberToFrenchLetters($number) {
        if ($number === 0) return 'zéro';
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                  'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
                  'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens  = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        if ($number < 20) return $units[$number];
        if ($number < 70) {
            $unit = $number % 10; $ten = floor($number / 10);
            if ($unit === 0) return $tens[$ten];
            if ($ten === 7 || $ten === 9) return $tens[$ten] . '-' . ($unit === 1 ? 'et-' . $units[$unit] : $units[$unit]);
            return $tens[$ten] . '-' . $units[$unit];
        }
        if ($number < 100) {
            $unit = $number % 10; $ten = floor($number / 10);
            if ($ten === 7) return 'soixante-' . ($unit === 1 ? 'et-onze' : $units[$unit + 10]);
            if ($ten === 8) return 'quatre-vingt' . ($unit > 0 ? '-' . $units[$unit] : 's');
            if ($ten === 9) return 'quatre-vingt-' . ($unit === 1 ? 'onze' : $units[$unit + 10]);
            return $tens[$ten] . ($unit > 0 ? '-' . $units[$unit] : '');
        }
        if ($number < 1000) {
            $hundred = floor($number / 100); $remainder = $number % 100;
            $result = $hundred === 1 ? 'cent' : numberToFrenchLetters($hundred) . ' cent';
            if ($remainder > 0) $result .= ' ' . numberToFrenchLetters($remainder);
            return $result;
        }
        return $number;
    }
@endphp

{{-- ── EN-TÊTE ─────────────────────────────────────────────────────────────── --}}
<table class="header-table">
    <tr>
        <td class="header-logo">
            <img src="assets/epac.png" alt="EPAC" style="max-width:100%;height:auto;max-height:80px;">
        </td>
        <td class="header-text">
            <div class="bold">UNIVERSITE D'ABOMEY-CALAVI</div>
            <div class="bold">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
            <div class="bold">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
            <div>………………………………</div>
        </td>
        <td class="header-logo">
            <img src="assets/cap.png" alt="CAP" style="max-width:100%;height:auto;max-height:80px;">
        </td>
    </tr>
</table>

<div class="center bold">
    CONTRAT DE PRESTATION D'ENSEIGNEMENT (Regroupement {{ $contrat->regroupement ?? '1' }} : Cycle : {{ $contrat->cycle?->name ?? 'Licence Professionnelle' }})
</div>

<br>

<table width="100%">
    <tr>
        <td>N° {{ $contrat->contrat_number ?? '' }} /UAC/EPAC/CAP/RD-FAD/ du {{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
    </tr>
</table>

<br><br>

<div class="justify">
<b>Entre :</b><br><br>
Le Centre Autonome de Perfectionnement de l'Ecole Polytechnique d'Abomey-Calavi de l'Université d'Abomey-Calavi,
Représenté par son Chef, Monsieur Fidèle Paul TCHOBO
Tél : (229) 01 99 54 62 67 —  E-mail professionnel : contact@cap-epac.online
ci-après dénommé CAP d'une part,
</div>

<br><div class="center">Et</div><br>

<div class="justify">
Monsieur/Madame : {{ $contrat->professor->full_name ?? '' }}<br><br>
Nationalité : {{ $contrat->professor->nationality ?? '' }}<br>
Profession : {{ $contrat->professor->profession ?? '' }}<br>
Domicilié(e) à : {{ $contrat->professor->district ?? '' }} / Parcelle : {{ $contrat->professor->plot_number ?? '' }}, Maison : {{ $contrat->professor->house_number ?? '' }}<br>
IFU : {{ $contrat->professor->ifu_number ?? '' }}<br>
RIB : N° {{ $contrat->professor->rib_number ?? '' }} / Banque : {{ $contrat->professor->bank ?? '' }}<br>
Adresse : {{ $contrat->professor->city ?? '' }} / Email : {{ $contrat->professor->email ?? '' }} / Tél : {{ $contrat->professor->phone ?? '' }}
</div>

<br>
<div class="justify">ci-après dénommé « L'ENSEIGNANT PRESTATAIRE » d'autre part,</div>
<br>
<div class="justify">qui déclare être disponible pour fournir les prestations objet du présent contrat, ci-après dénommé « PRESTATIONS D'ENSEIGNEMENT »,</div>
<br>
<div class="justify">Considérant que le CAP est disposé à faciliter à l'enseignant prestataire l'exécution de ses prestations, conformément aux clauses et conditions du présent contrat;</div>
<br>
<div class="justify">Les parties au présent contrat ont convenu de ce qui suit :</div>

<div class="section bold">1- Objet du contrat</div>
<div class="justify">Le présent contrat a pour objet la fourniture de prestations d'enseignement au CAP dans les conditions de délai, normes académiques et de qualité conformément aux clauses et conditions ci-après énoncées.</div>

<div class="section bold">2- Nature des prestations</div>
<div class="justify">
Le Centre retient par la présente les prestations de l'enseignant pour l'exécution de
@php
    $totalHours = 0;
    foreach($contrat->courseElementProfessors as $prog) {
        $totalHours += $prog->pivot->hours ?? 0;
    }
    echo $totalHours;
@endphp
 heures d'enseignement des cours de :
</div>

@foreach($contrat->courseElementProfessors as $prog)
<div>
({{ $prog->courseElement?->code ?? '' }}) : {{ $prog->courseElement?->name ?? '' }}
en {{ $prog->classGroup?->name ?? 'Classe non spécifiée' }} et pendant {{ $prog->pivot->hours ?? '' }} H
</div>
@endforeach

<br>
<div class="justify">conformément aux exigences énumérées dans le cahier de charges joint au présent contrat.</div>

<div class="section bold">3- Date de démarrage et calendrier</div>
<div>
@php
    $startDate = $contrat->start_date ? \Carbon\Carbon::parse($contrat->start_date) : null;
    $endDate   = $contrat->end_date   ? \Carbon\Carbon::parse($contrat->end_date)   : null;
    $durationDays = ($startDate && $endDate) ? $startDate->diffInDays($endDate) + 1 : 0;
    $durationInLetters = $durationDays > 0 ? numberToFrenchLetters($durationDays) : '………';
@endphp
La durée de la prestation est de <strong>{{ $durationDays }}</strong> ({{ $durationInLetters }}) jours ouvrables à partir du :
<strong>{{ $startDate ? $startDate->format('d/m/Y') : '………' }}</strong> au <strong>{{ $endDate ? $endDate->format('d/m/Y') : '………' }}</strong>
</div>

<table class="table">
<thead>
    <tr>
        <th>Département</th>
        <th>Année d'étude</th>
        <th>ECUE</th>
        <th>Nombre d'heures</th>
        <th>Date de démarrage</th>
        <th>Date de fin</th>
    </tr>
</thead>
<tbody>
@foreach($contrat->courseElementProfessors as $prog)
    <tr>
        <td>{{ $prog->courseElement?->teachingUnit?->name ?? '' }}</td>
        <td>
            @if($prog->classGroup)
                {{ $prog->classGroup->name ?? '' }}
            @elseif($contrat->academicYear)
                {{ $contrat->academicYear->academic_year ?? $contrat->academicYear->libelle ?? '' }}
            @else
                Année non spécifiée
            @endif
        </td>
        <td>{{ $prog->courseElement?->name ?? '' }}</td>
        <td>{{ $prog->courseElement?->hours }}</td>
        <td>{{ $startDate ? $startDate->format('d/m/Y') : '' }}</td>
        <td>{{ $endDate ? $endDate->format('d/m/Y') : '' }}</td>
    </tr>
@endforeach
</tbody>
</table>

<div class="section bold">4- Temps de présence</div>
<div class="justify">
Dans l'exécution du présent contrat, l'enseignant prestataire, {{ $contrat->professor->full_name ?? '' }} assurera également la surveillance des évaluations et travaux de recherche conformément aux textes du CAP.<br><br>
Conformément à l'arrêté N°0388/MESRS/DC/SGM/DPAF/DGES/CJ/SA/05 du 03/08/2022, au CAP, les charges horaires des prestataires d'enseignement sont fixées comme suit :<br>
- une heure (01) de Cours Théorique équivaut à une heure et demie (1h30) de travaux dirigés ;<br>
- une heure (01) de Cours Théorique équivaut à deux (02) heures de travaux Pratiques ;<br>
- une heure (01) de Cours Théorique équivaut à cinq (05) heures d'ateliers / sorties pédagogiques / Stage.
</div>

<div class="section bold">5- Termes de paiement et prélèvements</div>
<div class="justify">
Les honoraires pour les prestations d'enseignement sont de ({{ number_format($contrat->amount ?? 6000, 0, ',', ' ') }} FCFA) brut par heure exécutée conformément aux exigences du CAP.<br><br>
Les paiements sont effectués en Francs CFA à la fin des prestations (dépôt de sujet, corrigé type et copies corrigées) dûment constatées par une attestation de service fait, par virement bancaire après le prélèvement de l'AIB.
</div>

<div class="section bold">6- Normes de Performance</div>
<div class="justify">
L'enseignant prestataire s'engage à fournir les prestations conformément aux normes professionnelles, d'éthique et déontologiques, de compétence et d'intégrité les plus exigeantes. Il est systématiquement mis fin au présent contrat en cas de défaillance du prestataire constatée et motivée par écrit au CAP.
</div>

<div class="section bold">7- Droit de propriété, devoir de réserve et non-concurrence</div>
<div class="justify">
Pendant la durée d'exécution du présent contrat et les cinq années suivant son expiration, l'enseignant prestataire ne divulguera aucune information exclusive ou confidentielle concernant la prestation, le présent contrat, les affaires ou les documents du CAP sans avoir obtenu au préalable l'autorisation écrite de l'Unité de formation et de recherche concernée.<br><br>
Tous les rapports ou autres documents, que l'enseignant prestataire préparera pour le compte du CAP dans le cadre du présent contrat deviendront et demeureront la propriété du CAP.<br><br>
Ne sont pas pris en compte les cours et autres documents préparés par l'enseignant pour l'exécution de ses prestations.
</div>

<div class="section bold">8- Règlement des litiges</div>
<div class="justify">
Pour tout ce qui n'est pas prévu au présent contrat, les parties se référeront aux lois béninoises en la matière. Tout litige survenu lors de l'exécution du présent contrat sera soumis aux juridictions compétentes, s'il n'est pas réglé à l'amiable ou par tout autre mode de règlement agréé par les deux parties.
</div>

<br><br>
<div class="center">Fait à Abomey-Calavi, le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>

{{-- ── SIGNATURES ────────────────────────────────────────────────────────────── --}}
<table class="signature-block">
    <tr>
        {{-- Signature de l'enseignant --}}
        <td>
            <div class="signature-label">L'enseignant(e) prestataire,</div>

            @if($contrat->professor_signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($contrat->professor_signature_path))
                {{-- Signature déposée électroniquement --}}
                <div class="signature-image-wrapper">
                    @php
                        $signatureDisk = \Illuminate\Support\Facades\Storage::disk('public');
                        $signatureRaw  = $signatureDisk->get($contrat->professor_signature_path);
                        $signatureB64  = base64_encode($signatureRaw);
                    @endphp
                    <img src="data:image/png;base64,{{ $signatureB64 }}" alt="Signature de {{ $contrat->professor->full_name ?? '' }}">
                </div>
                <div class="signature-meta">
                    Signé électroniquement le {{ $contrat->professor_signed_at ? \Carbon\Carbon::parse($contrat->professor_signed_at)->format('d/m/Y à H:i') : '' }}
                    @if($contrat->professor_signature_type === 'drawn')
                        (signature manuscrite numérique)
                    @elseif($contrat->professor_signature_type === 'uploaded')
                        (signature importée)
                    @endif
                </div>
            @else
                {{-- Espace de signature vide --}}
                <div style="height:80px; border-bottom: 1px solid #333; margin-bottom: 4px;"></div>
                <div class="signature-meta">Signature</div>
            @endif

            <br>
            <strong>{{ $contrat->professor->full_name ?? '' }}</strong>
        </td>

        {{-- Signature du chef CAP --}}
        <td class="center">
            <div class="signature-label">Pour le CAP, Le Chef,</div>
            <div style="height:80px;"></div>

            Fidèle Paul TCHOBO<br>
            Professeur Titulaire de Chimie Alimentaire<br>
            et Chimie Analytique
        </td>
    </tr>
</table>

<br><br>

{{-- Directeur --}}
<table width="100%">
    <tr>
        <td class="center">
            Le Directeur<br><br> <br><br>

            Guy Alain ALITONOU<br>
            Professeur Titulaire de Chimie organique<br>
            et chimie des substances naturelles
        </td>
    </tr>
</table>

</body>
</html>
