<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étudiants Validés {{ $typeLabel }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @page {
            size: A4 landscape;
            margin: 0cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        .content-body {
            margin: 10px 25px 55px 25px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 14px;
            margin: 3px 0;
        }

        .header p {
            font-size: 10px;
            margin: 2px 0;
        }

        .info-section {
            margin: 15px 0;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 8px;
            background-color: #007bff;
            color: white;
            text-align: center;
            text-transform: uppercase;
        }

        .section-title.prepa {
            background-color: #28a745;
        }

        .section-title.licence {
            background-color: #dc3545;
        }

        .section-title.specialite {
            background-color: #ffc107;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }

        .numero {
            width: 5%;
            text-align: center;
        }

        .nom {
            width: 25%;
        }

        .nationalite {
            width: 15%;
        }

        .filiere {
            width: 20%;
        }

        .niveau {
            width: 15%;
        }

        .statut {
            width: 10%;
            text-align: center;
        }

        .date-validation {
            width: 10%;
            text-align: center;
        }

        .no-students {
            text-align: center;
            font-style: italic;
            color: #666;
            padding: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    @php
        $headerLandscape = file_exists(public_path("images/epac_header_landscape.png"))
            ? public_path("images/epac_header_landscape.png")
            : (file_exists(storage_path("images/epac_header_landscape.png"))
                ? storage_path("images/epac_header_landscape.png")
                : base_path("storage/images/epac_header_landscape.png"));
        $headerBase64 = file_exists($headerLandscape) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerLandscape)) : '';
        $footerLandscape = file_exists(public_path("images/epac_footer_landscape.png"))
            ? public_path("images/epac_footer_landscape.png")
            : (file_exists(storage_path("images/epac_footer_landscape.png"))
                ? storage_path("images/epac_footer_landscape.png")
                : base_path("storage/images/epac_footer_landscape.png"));
        $footerBase64 = file_exists($footerLandscape) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerLandscape)) : '';
    @endphp

    <div style="margin: 0; padding: 0; width: 100%;">
        @if($headerBase64)
            <img src="{{ $headerBase64 }}" style="width: 100%; height: auto; display: block;" alt="En-tête officiel EPAC - CAP">
        @endif
    </div>

    <div class="content-body">

    <div class="info-section">
        <div style="text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 10px;">
            LISTE DES ÉTUDIANTS VALIDÉS - {{ strtoupper($typeLabel) }}
        </div>
        <div class="info-row">
            <span><strong>Année Académique:</strong> {{ $academicYear }}</span>
            <span><strong>Date d'export:</strong> {{ $exportDate }} à {{ $exportTime }}</span>
        </div>
        <div class="info-row">
            <span><strong>Type:</strong> {{ $typeLabel }}</span>
            <span><strong>Total Étudiants Validés:</strong> {{ $totalStudents }}</span>
        </div>
        <div style="margin-top: 8px; font-size: 9px; color: #666; text-align: center;">
            <em>Étudiants validés par la {{ $validationCriteria }}</em>
        </div>
    </div>

    <div class="section-title {{ $type }}">
        {{ $typeLabel }} - Validés {{ $validationCriteria }} ({{ $totalStudents }} étudiant{{ $totalStudents > 1 ? 's' : '' }})
    </div>

    @if($totalStudents > 0)
    <table>
        <thead>
            <tr>
                <th class="numero">N°</th>
                <th class="nom">Nom et Prénoms</th>
                <th class="nationalite">Nationalité</th>
                <th class="filiere">Filière</th>
                <th class="niveau">Niveau</th>
                <th class="statut">Statut {{ $validationCriteria }}</th>
                <th class="date-validation">Validation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td class="numero">{{ $index + 1 }}</td>
                <td class="nom">{{ $student->personalInformation->last_name }} {{ $student->personalInformation->first_names }}</td>
                <td class="nationalite">{{ $student->personalInformation->birth_country ?? 'Bénin' }}</td>
                <td class="filiere">{{ $student->department->name ?? 'N/A' }}</td>
                <td class="niveau">{{ $student->level ?? 'N/A' }}</td>
                <td class="statut" style="color: green; font-weight: bold;">VALIDÉ</td>
                <td class="date-validation">{{ $student->updated_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-students">Aucun étudiant de {{ strtolower($typeLabel) }} validé par la {{ $validationCriteria }} pour cette année académique.</div>
    @endif

    <div class="footer">
        <p><strong>Note:</strong> Cette liste contient les étudiants de {{ strtolower($typeLabel) }} validés par la {{ $validationCriteria }}.</p>
        <p>Document généré automatiquement le {{ $exportDate }} à {{ $exportTime }}</p>
    </div>

    </div>

    <footer style="position: fixed; bottom: 0; left: 0; right: 0; width: 100%; margin: 0; padding: 0;">
        @if($footerBase64)
            <img src="{{ $footerBase64 }}" style="width: 100%; height: auto; display: block;" alt="Pied de page officiel EPAC - CAP">
        @endif
        <div style="position: absolute; bottom: 4px; left: 25px; color: #ffffff; font-size: 8px;">
            Document généré automatiquement le {{ $exportDate }} à {{ $exportTime }}
        </div>
        <div style="position: absolute; bottom: 4px; right: 25px; color: #ffffff; font-size: 8px;">
            Cellule Informatique CAP
        </div>
    </footer>
</body>
</html>