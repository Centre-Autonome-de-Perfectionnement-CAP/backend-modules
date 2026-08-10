<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat de votre demande de correction</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #003087;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
        }
        .content p {
            margin: 0 0 15px;
        }
        .status-box {
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .status-approved {
            background-color: #e8f5e9;
            border-left: 4px solid #2e7d32;
            color: #2e7d32;
        }
        .status-rejected {
            background-color: #fff3e0;
            border-left: 4px solid #e65100;
            color: #e65100;
        }
        .changes-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }
        .changes-table th {
            background-color: #003087;
            color: #ffffff;
            padding: 8px 12px;
            text-align: left;
        }
        .changes-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .changes-table tr:last-child td {
            border-bottom: none;
        }
        .rejection-reason {
            background-color: #fff8f0;
            border: 1px solid #f5c6a0;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-size: 14px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .footer p {
            margin: 0;
        }
        .matricule {
            display: inline-block;
            background-color: #e6f3ff;
            color: #003087;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 15px;
        }
        @media screen and (max-width: 600px) {
            .container { margin: 10px; }
            .header h1 { font-size: 18px; }
            .content { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Correction d'informations — Résultat</h1>
        </div>

        <div class="content">
            <p>Cher(e) <strong>{{ $first_names }}</strong>,</p>
            <p>Votre matricule : <span class="matricule">{{ $student_id_number }}</span></p>

            @if ($status === 'approved')
                <div class="status-box status-approved">
                    ✅ Votre demande de correction a été approuvée.
                </div>
                <p>Les informations suivantes ont été mises à jour dans notre système :</p>

                @php
                    $labels = [
                        'last_name'   => 'Nom',
                        'first_names' => 'Prénoms',
                        'email'       => 'Adresse email',
                        'contacts'    => 'Numéro(s) de téléphone',
                    ];
                @endphp

                <table class="changes-table">
                    <thead>
                        <tr>
                            <th>Champ</th>
                            <th>Nouvelle valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suggested_values as $field => $value)
                            <tr>
                                <td>{{ $labels[$field] ?? $field }}</td>
                                <td>
                                    @if (is_array($value))
                                        {{ implode(', ', $value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p>Ces informations sont désormais effectives dans notre base de données. Vous recevrez désormais toutes les notifications à vos coordonnées mises à jour.</p>

            @else
                <div class="status-box status-rejected">
                    ❌ Votre demande de correction a été refusée.
                </div>
                <p>Nous avons examiné votre demande de modification des informations suivantes :</p>

                @php
                    $labels = [
                        'last_name'   => 'Nom',
                        'first_names' => 'Prénoms',
                        'email'       => 'Adresse email',
                        'contacts'    => 'Numéro(s) de téléphone',
                    ];
                @endphp

                <table class="changes-table">
                    <thead>
                        <tr>
                            <th>Champ</th>
                            <th>Valeur proposée</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suggested_values as $field => $value)
                            <tr>
                                <td>{{ $labels[$field] ?? $field }}</td>
                                <td>
                                    @if (is_array($value))
                                        {{ implode(', ', $value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($rejection_reason)
                    <div class="rejection-reason">
                        <strong>Motif du refus :</strong><br>
                        {{ $rejection_reason }}
                    </div>
                @endif

                <p>Si vous pensez qu'il s'agit d'une erreur, veuillez contacter directement le bureau des admissions ou soumettre une nouvelle demande avec des justificatifs.</p>
            @endif

            <p>Pour toute question, n'hésitez pas à nous contacter.</p>
        </div>

        <div class="footer">
            <p>Merci de votre confiance,<br>Bureau des Admissions — CAP-EPAC</p>
        </div>
    </div>
</body>
</html>
