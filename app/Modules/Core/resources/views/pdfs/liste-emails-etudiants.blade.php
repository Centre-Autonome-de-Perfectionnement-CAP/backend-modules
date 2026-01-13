<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Emails - Étudiants en Attente</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16pt;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .info-section {
            margin-bottom: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .info-section p {
            margin: 3px 0;
            font-size: 9pt;
        }
        .info-section strong {
            color: #2c3e50;
        }
        .department-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .department-title {
            background-color: #007bff;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #6c757d;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #495057;
            font-size: 9pt;
        }
        td {
            padding: 6px;
            border: 1px solid #dee2e6;
            font-size: 9pt;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #6c757d;
        }
        .email-list {
            margin-top: 15px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 3px;
            page-break-inside: avoid;
        }
        .email-list h3 {
            margin-top: 0;
            color: #856404;
            font-size: 10pt;
        }
        .email-list p {
            font-size: 8pt;
            line-height: 1.4;
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
        <p><strong>Nombre total d'étudiants :</strong> {{ $totalStudents }}</p>
        <p><strong>Date d'export :</strong> {{ $exportDate }}</p>
    </div>

    @foreach($studentsByDepartment as $departmentName => $students)
    <div class="department-section">
        <div class="department-title">
            {{ $departmentName }} ({{ $students->count() }} étudiant(s))
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
                @foreach($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->personalInformation->last_name }} {{ $student->personalInformation->first_names }}</td>
                    <td>{{ $student->personalInformation->email }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>École Polytechnique d'Abomey-Calavi (EPAC) - Centre Autonome de Perfectionnement (CAP)</p>
    </div>
</body>
</html>
