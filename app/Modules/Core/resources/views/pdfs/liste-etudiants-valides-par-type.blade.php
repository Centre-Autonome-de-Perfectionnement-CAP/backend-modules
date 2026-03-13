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

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            line-height: 1.3;
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
    <div class="header">
        <h1>République du Bénin</h1>
        <h2>Université d'Abomey-Calavi</h2>
        <h2>École Polytechnique d'Abomey-Calavi (EPAC)</h2>
        <h2>Centre Autonome de Perfectionnement (CAP)</h2>
        <p>01 BP 2009 COTONOU - Tél. 21 36 14 32/21 36 09 93 - Email: epac.uac@epac.uac.bj</p>
    </div>

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

    <div class="signature-section">
        <div class="signature-box">
            <p><strong>Le Chef CAP</strong></p>
            <br><br>
            <p>_________________________</p>
        </div>
        <div class="signature-box">
            <p><strong>Le Directeur EPAC</strong></p>
            <br><br>
            <p>_________________________</p>
        </div>
    </div>
</body>
</html>