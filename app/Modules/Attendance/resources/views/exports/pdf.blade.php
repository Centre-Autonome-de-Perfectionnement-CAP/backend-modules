<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        <style>
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 9px;
        margin: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-table {
        margin-bottom: 10px;
    }

    .logo-img {
        width: 60px;
    }

    .header-center {
        text-align: center;
    }

    .univ { font-size: 10px; font-weight: bold; }
    .epac { font-size: 11px; font-weight: bold; text-transform: uppercase; }

    .hr-line {
        border-top: 2px solid #000;
        margin: 5px 0 10px 0;
    }

    .doc-title {
        text-align: center;
        margin-bottom: 10px;
    }

    .doc-title h1 {
        font-size: 14px;
        text-transform: uppercase;
        text-decoration: underline;
    }

    .info-table td {
        border-bottom: 1px dotted #000;
        padding: 4px;
        font-size: 10px;
    }

    .main-table th,
    .main-table td {
        border: 1px solid #000;
        padding: 4px;
        font-size: 9px;
        text-align: center;
    }

    .main-table th {
        background: #eeeeee;
    }

    .c-nom {
        text-align: left;
    }

    .present {
        color: green;
        font-weight: bold;
    }

    .absent {
        color: red;
        font-weight: bold;
    }

    .footer-stats {
        margin-top: 10px;
        font-size: 10px;
    }

    .footer-sig {
        margin-top: 20px;
    }

    .sig-line {
        border-bottom: 1px solid #000;
        width: 180px;
        margin-top: 40px;
    }

    .page {
        page-break-after: always;
    }

    .page:last-child {
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }
</style>
    </style>
</head>
<body>

@foreach($pages as $pageIndex => $students)
<div class="page">

    <!-- EN-TÊTE -->
    <table class="header-table">
        <tr>
            <td width="90" align="left">
                <img src="{{ public_path('assets/epac.png') }}" class="logo-img">
            </td>
            <td class="header-center">
                <div class="univ">Université d'Abomey-Calavi</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
            </td>
            <td width="90" align="right">
                <img src="{{ public_path('assets/cap.png') }}" class="logo-img">
            </td>
        </tr>
    </table>

    <div class="hr-line"></div>

    <!-- TITRE -->
    <div class="doc-title">
        <div style="font-weight: bold; font-size: 10pt; margin-bottom: 4px;">
            Année académique : {{ $filters['year'] ?? $filters['annee'] ?? '' }}
        </div>
        <h1>Fiche de présence et d'émargement</h1>
    </div>

    <!-- INFORMATIONS -->
    <table class="info-table">
        <tr>
            <td class="info-td"><strong>Filière :</strong> {{ $filters['filiere'] ?? '' }}</td>
            <td class="info-td" style="padding-left:15px;">
                <strong>Niveau :</strong> {{ $filters['niveau'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td class="info-td"><strong>Matière :</strong> {{ $filters['matiere'] ?? '' }}</td>
            <td class="info-td" style="padding-left:15px;">
                <strong>Date :</strong> {{ $date }}
            </td>
        </tr>
    </table>

    <!-- TABLEAU PRINCIPAL -->
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" width="25">N°</th>
                <th rowspan="2" width="75">Matricule</th>
                <th rowspan="2" class="c-nom">Noms et Prénoms</th>
                <th rowspan="2" width="80">Contact</th>
                <th colspan="2">Émargement</th>
            </tr>
            <tr>
                <th width="70">Début</th>
                <th width="70">Fin / Dépôt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $s)
            <tr>
                <td>{{ $index + 1 + ($pageIndex * 25) }}</td>
                <td>{{ $s->matricule ?? '' }}</td>
                <td class="c-nom">{{ $s->name }}</td>
                <td>{{ $s->phone ?? '' }}</td>
                <td class="{{ ($s->status ?? '') == 'present' ? 'present' : 'absent' }}">
                    {{ ($s->status ?? '') == 'present' ? 'Présent' : 'Absent' }}
                </td>
                <td class="{{ ($s->status ?? '') == 'present' ? 'present' : 'absent' }}">
                    {{ ($s->status ?? '') == 'present' ? 'Présent' : 'Absent' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- PIED DE PAGE -->
    <table class="footer-stats">
        <tr>
            <td width="33%"><strong>Effectif : {{ $total }}</strong></td>
            <td width="33%" align="center">
                Page {{ $pageIndex + 1 }} / {{ count($pages) }}
            </td>
            <td width="33%" align="right">
                Imprimé le : {{ $date }}
            </td>
        </tr>
    </table>

    <!-- SIGNATURES -->
    <table class="footer-sig">
        <tr>
            <td width="50%">
                <strong>Signature des surveillants :</strong>
                <div class="sig-line"></div>
            </td>
            <td width="50%" align="right">
                <strong>Signature de l'Enseignant :</strong>
                <div class="sig-line" style="margin-left:auto;"></div>
            </td>
        </tr>
    </table>

</div>
@endforeach

</body>
</html>