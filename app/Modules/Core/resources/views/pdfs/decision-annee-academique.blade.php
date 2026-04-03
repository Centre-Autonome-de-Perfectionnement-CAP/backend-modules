<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Décision Année Académique</title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4CAF50;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 20pt;
        }
        .header .subtitle {
            color: #666;
            font-size: 10pt;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background: #f8f8f8;
            font-weight: bold;
            color: #333;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        table.no-border {
            border: none;
        }
        table.no-border td {
            border: none;
        }
        table.no-border tr {
            border: none;
        }
        .info-box {
            background: #f5f5f5;
            padding: 12px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
        }
        .info-box p {
            margin: 4px 0;
        }
        .decision-box {
            margin: 20px 0;
            padding: 15px;
            border: 3px solid;
            text-align: center;
        }
        .decision-admis {
            border-color: #4CAF50;
            background: #e8f5e9;
            color: #2e7d32;
        }
        .decision-rejet {
            border-color: #d32f2f;
            background: #ffebee;
            color: #c62828;
        }
        .warning-box {
            background: #fff3cd;
            padding: 12px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
        .info-note {
            background: #e3f2fd;
            padding: 12px;
            border-left: 4px solid #2196F3;
            margin: 15px 0;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DÉCISION DU CONSEIL DE CLASSE</h1>
        <div class="subtitle">Année Académique {{ $anneeAcademique ?? '' }}</div>
    </div>

    {{-- Informations de l'étudiant --}}
    <div class="info-box">
        <table class="no-border">
            <tr>
                <td style="width: 25%;"><strong>Nom et Prénoms :</strong></td>
                <td style="width: 25%;">{{ $etudiant['nom'] ?? '' }}</td>
                <td style="width: 25%;"><strong>Matricule :</strong></td>
                <td style="width: 25%;">{{ $etudiant['matricule'] ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Classe :</strong></td>
                <td>{{ $etudiant['classe'] ?? '' }}</td>
                <td><strong>Spécialité :</strong></td>
                <td>{{ $etudiant['specialite'] ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Date de naissance :</strong></td>
                <td>{{ $etudiant['dateNaissance'] ?? '' }}</td>
                <td><strong>Lieu de naissance :</strong></td>
                <td>{{ $etudiant['lieuNaissance'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    {{-- Résultats par semestre --}}
    <h3 style="margin-top: 20px;">Résultats de l'année</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Période</th>
                <th style="text-align: center; width: 15%;">Moyenne</th>
                <th style="text-align: center; width: 15%;">Crédits</th>
                <th style="text-align: center; width: 15%;">Crédits Acquis</th>
                <th style="width: 30%;">Observation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($semestres ?? [] as $semestre)
                <tr>
                    <td><strong>{{ $semestre['nom'] ?? '' }}</strong></td>
                    <td style="text-align: center;">{{ number_format($semestre['moyenne'] ?? 0, 2) }}/20</td>
                    <td style="text-align: center;">{{ $semestre['credits'] ?? 0 }}</td>
                    <td style="text-align: center;">{{ $semestre['creditsAcquis'] ?? 0 }}</td>
                    <td>{{ $semestre['observation'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td>TOTAL ANNÉE</td>
                <td style="text-align: center;">{{ number_format($moyenneAnnuelle ?? 0, 2) }}/20</td>
                <td style="text-align: center;">{{ $totalCredits ?? 0 }}</td>
                <td style="text-align: center;">{{ $creditsAcquisTotal ?? 0 }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Bilan et décision côte à côte --}}
    <div style="display: table; width: 100%; margin-top: 20px;">
        <div style="display: table-cell; width: 48%; vertical-align: top; padding-right: 2%;">
            <h3>Bilan</h3>
            <table class="no-border" style="background: #f5f5f5; padding: 10px;">
                <tr>
                    <td style="padding: 5px;"><strong>Moyenne générale annuelle :</strong></td>
                    <td style="padding: 5px; font-weight: bold; font-size: 12pt;">{{ number_format($moyenneAnnuelle ?? 0, 2) }}/20</td>
                </tr>
                <tr>
                    <td style="padding: 5px;"><strong>Rang dans la classe :</strong></td>
                    <td style="padding: 5px;">{{ $rang ?? '' }}/{{ $effectifClasse ?? '' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px;"><strong>Mention :</strong></td>
                    <td style="padding: 5px; font-weight: bold;">{{ $mention ?? '' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px;"><strong>Crédits acquis :</strong></td>
                    <td style="padding: 5px;">{{ $creditsAcquisTotal ?? 0 }}/{{ $totalCredits ?? 0 }}</td>
                </tr>
            </table>

            @if(isset($appreciation))
                <div class="warning-box" style="margin-top: 15px; font-size: 9pt;">
                    <p style="margin: 0;"><strong>Appréciation :</strong></p>
                    <p style="margin: 5px 0 0 0; font-style: italic;">{{ $appreciation }}</p>
                </div>
            @endif
        </div>

        <div style="display: table-cell; width: 48%; vertical-align: top; padding-left: 2%;">
            <h3>Décision</h3>
            <div class="decision-box {{ ($decision === 'ADMIS' || $decision === 'ADMIS(E)') ? 'decision-admis' : 'decision-rejet' }}">
                <h2 style="margin: 0; font-size: 18pt;">{{ $decision ?? '' }}</h2>
                @if(isset($decisionDetails))
                    <p style="margin: 10px 0 0 0; font-size: 10pt;">{{ $decisionDetails }}</p>
                @endif
            </div>

            @if(isset($classeSuperieure))
                <div style="margin-top: 15px; text-align: center; font-size: 11pt;">
                    <p style="margin: 0;"><strong>Passage en :</strong></p>
                    <p style="margin: 5px 0 0 0; font-weight: bold; color: #4CAF50; font-size: 12pt;">{{ $classeSuperieure }}</p>
                </div>
            @endif

            @if(isset($conditions) && count($conditions) > 0)
                <div class="info-note" style="margin-top: 15px; font-size: 9pt;">
                    <p style="margin: 0;"><strong>Conditions :</strong></p>
                    <ul style="margin: 5px 0 0 15px; padding: 0;">
                        @foreach($conditions as $condition)
                            <li style="margin: 3px 0;">{{ $condition }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Date et signatures --}}
    <div style="margin-top: 30px;">
        <p><strong>Délibération du Conseil de Classe du :</strong> {{ $dateDeliberation ?? now()->format('d/m/Y') }}</p>
    </div>

    <div style="margin-top: 30px;">
        <table class="no-border">
            <tr>
                <td style="width: 33%; text-align: center;">
                    <p><strong>Le Chef de Département</strong></p>
                    <p style="margin-top: 40px;">___________________</p>
                </td>
                <td style="width: 34%; text-align: center;">
                    <p><strong>Le Directeur des Études</strong></p>
                    <p style="margin-top: 40px;">___________________</p>
                </td>
                <td style="width: 33%; text-align: center;">
                    <p><strong>Le Directeur Général</strong></p>
                    <p style="margin-top: 40px;">___________________</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
