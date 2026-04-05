<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; margin: 1cm; line-height: 1.0; }
        p, div, h1, h2, td { mso-margin-top-alt: 0pt; mso-margin-bottom-alt: 0pt; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-width: 80px; height: auto; }
        .header-center { text-align: center; vertical-align: middle; }
        .univ { font-size: 9pt; font-weight: bold; }
        .epac { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
        .sep { font-size: 8pt; font-weight: bold; margin: 1px 0; }

        .hr-line { border-top: 2px solid #000; margin: 5px 0 10px 0; }

        .doc-title { text-align: center; margin-bottom: 10px; }
        .doc-title h1 { font-size: 13pt; text-transform: uppercase; text-decoration: underline; font-weight: bold; }

        .info-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .info-td { width: 50%; border-bottom: 1px dotted #000; padding: 4px 0; font-size: 9pt; }

        .main-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .main-table th { border: 1px solid #000; padding: 5px; background: #eeeeee; font-size: 8pt; }
        .main-table td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 8pt; height: 25px; }
        .c-nom { text-align: left !important; padding-left: 5px !important; }
        
        .present { color: #155724; font-weight: bold; }
        .absent { color: #721c24; font-weight: bold; }

        .footer-stats { width: 100%; margin-top: 15px; border-collapse: collapse; font-size: 9pt; }
        .footer-sig { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .sig-line { border-bottom: 1px solid #000; margin-top: 45px; width: 180px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td width="90" align="left"><img src="{{ public_path('assets/epac.png') }}" class="logo-img"></td>
            <td class="header-center">
                <div class="univ">Université d'Abomey-Calavi</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
            </td>
            <td width="90" align="right"><img src="{{ public_path('assets/cap.png') }}" class="logo-img"></td>
        </tr>
    </table>

    <div class="hr-line"></div>

    <div class="doc-title">
        <div style="font-weight: bold; font-size: 10pt; margin-bottom: 4px;">Année académique : 2025-2026</div>
        <h1>Fiche de présence et d'émargement</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-td"><strong>Filière :</strong> {{ $filters['filiere'] ?? '' }}</td>
            <td class="info-td" style="padding-left:15px;"><strong>Niveau :</strong> {{ $filters['niveau'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="info-td"><strong>Matière :</strong> {{ $filters['matiere'] ?? '' }}</td>
            <td class="info-td" style="padding-left:15px;"><strong>Date :</strong> {{ $date }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" width="25">N°</th>
                <th rowspan="2" width="75">Matricule</th>
                <th rowspan="2" class="c-nom">Noms et Prénoms</th>
                <th rowspan="2" width="80">Nationalité</th>
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
                <td>{{ $index + 1 }}</td>
                <td>{{ $s->matricule ?? '' }}</td>
                <td class="c-nom">{{ $s->name }}</td>
                <td>{{ $s->nationality ?? '' }}</td>
                <td class="{{ ($s->status_debut ?? $s->status) == 'present' ? 'present' : 'absent' }}">
                    {{ ($s->status_debut ?? $s->status) == 'present' ? 'Présent' : 'Absent' }}
                </td>
                <td class="{{ ($s->status_fin ?? $s->status) == 'present' ? 'present' : 'absent' }}">
                    {{ ($s->status_fin ?? $s->status) == 'present' ? 'Présent' : 'Absent' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer-stats">
        <tr>
            <td width="33%"><strong>Effectif : {{ count($students) }}</strong></td>
            <td width="33%" align="center">Présents : ..........</td>
            <td width="33%" align="right">Absents : ..........</td>
        </tr>
    </table>

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
</body>
</html>