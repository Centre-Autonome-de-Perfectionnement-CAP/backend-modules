<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants - {{ $departmentName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #003087;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #003087;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .info-box {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table thead {
            background-color: #003087;
            color: white;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr:hover {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .total {
            font-weight: bold;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste des Étudiants</h1>
        <p><strong>{{ $departmentName }}</strong></p>
        <p>Généré le {{ $generatedAt }}</p>
    </div>

    <div class="info-box">
        <strong>Note :</strong> Cette liste contient les coordonnées des étudiants de votre filière. 
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Nom</th>
                <th style="width: 35%;">Prénom(s)</th>
                <th style="width: 25%;">Téléphone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student['nom'] }}</td>
                <td>{{ $student['prenom'] }}</td>
                <td>{{ $student['telephone'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total : {{ $totalStudents }} étudiant(s)
    </div>

    <div class="footer">
        <p><strong>CAP-EPAC</strong> - Centre Autonome de Perfectionnement</p>
        <p>École Polytechnique d'Abomey-Calavi</p>
    </div>
</body>
</html>
