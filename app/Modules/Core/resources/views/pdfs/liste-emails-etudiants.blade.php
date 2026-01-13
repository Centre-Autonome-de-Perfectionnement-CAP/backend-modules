<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Emails - Étudiants en Attente</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 18pt;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .info-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .info-section p {
            margin: 5px 0;
            font-size: 10pt;
        }
        .info-section strong {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0056b3;
        }
        td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #6c757d;
        }
        .email-list {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
        }
        .email-list h3 {
            margin-top: 0;
            color: #856404;
        }
        .email-list p {
            font-size: 9pt;
            line-height: 1.6;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste des Emails - Étudiants en Attente</h1>
    </div>

    <div class="info-section">
        <p><strong>Année Académique :</strong> {{ $academicYear }}</p>
        <p><strong>Filière :</strong> {{ $department }}</p>
        <p><strong>Nombre total d'étudiants :</strong> {{ $totalStudents }}</p>
        <p><strong>Date d'export :</strong> {{ $exportDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 45%;">Nom et Prénoms</th>
                <th style="width: 50%;">Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach($emails as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student['name'] }}</td>
                <td>{{ $student['email'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="email-list">
        <h3>Liste des emails (copier-coller)</h3>
        <p>
            @foreach($emails as $index => $student)
                {{ $student['email'] }}@if($index < count($emails) - 1), @endif
            @endforeach
        </p>
    </div>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>École Polytechnique d'Abomey-Calavi (EPAC) - Centre Autonome de Perfectionnement (CAP)</p>
    </div>
</body>
</html>
