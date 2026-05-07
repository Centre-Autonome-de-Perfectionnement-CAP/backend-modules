<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; margin: 1cm; line-height: 1.2; }

        /* En-tête institution */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .logo-img { max-width: 80px; height: auto; display: block; }
        .header-center { text-align: center; vertical-align: middle; padding: 0 10px; }
        .univ { font-size: 10pt; font-weight: bold; }
        .epac { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
        .sep  { font-size: 8pt; color: #666; margin: 2px 0; }

        /* Séparateur */
        .hr-line { border-top: 2px solid #000; margin: 5px 0 12px 0; }

        /* Titre */
        .doc-title { text-align: center; margin-bottom: 12px; }
        .doc-title .annee { font-weight: bold; font-size: 10pt; margin-bottom: 4px; }
        .doc-title h1 { font-size: 14pt; text-transform: uppercase; text-decoration: underline; font-weight: bold; }

        /* Bloc infos séance */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 3px 6px; font-size: 9pt; }
        .info-label { font-weight: bold; width: 80px; }
        .info-value { border-bottom: 1px dotted #999; }

        /* Bloc statistiques */
        .stats-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 12px; }
        .stat-box { width: 25%; padding: 6px; border: 1px solid #ccc; text-align: center; border-radius: 4px; }
        .stat-num { font-size: 18pt; font-weight: bold; display: block; }
        .stat-lbl { font-size: 8pt; }
        .stat-total   { background: #f0f0f0; color: #333; border-color: #ccc; }
        .stat-present { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .stat-late    { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .stat-absent  { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Tableau principal */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .main-table th {
            border: 1px solid #000; padding: 5px 6px;
            background: #1a237e; color: #fff;
            font-size: 8.5pt; font-weight: bold; text-align: center;
        }
        .main-table td { border: 1px solid #aaa; padding: 5px 6px; font-size: 8.5pt; text-align: center; }
        .main-table tr:nth-child(even) td { background: #f5f7ff; }
        .td-nom { text-align: left !important; }

        /* Statuts */
        .st-present { color: #155724; font-weight: bold; }
        .st-retard  { color: #856404; font-weight: bold; }
        .st-absent  { color: #721c24; font-weight: bold; }

        /* Pied de page */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .footer-table td { padding: 4px; font-size: 9pt; }
        .print-date { text-align: right; font-size: 7.5pt; color: #888; margin-top: 8px; }
    </style>
</head>
<body>

    {{-- EN-TÊTE --}}
    <table class="header-table">
        <tr>
            <td width="90" align="left">
                <img src="{{ public_path('assets/epac.png') }}" class="logo-img" alt="EPAC">
            </td>
            <td class="header-center">
                <div class="univ">Université d'Abomey-Calavi</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
                <div class="sep" style="font-size:7.5pt;">01 BP 2009 COTONOU — TÉL. 21 36 14 32 / 21 36 09 93</div>
            </td>
            <td width="90" align="right">
                <img src="{{ public_path('assets/cap.png') }}" class="logo-img" alt="CAP">
            </td>
        </tr>
    </table>

    <div class="hr-line"></div>

    {{-- TITRE --}}
    <div class="doc-title">
        <div class="annee">Année académique : {{ $meta['annee'] ?? 'N/A' }}</div>
        <h1>Fiche de présence par séance</h1>
    </div>

    {{-- INFOS SÉANCE --}}
    <table class="info-table">
        <tr>
            <td><span class="info-label">Matière :</span></td>
            <td class="info-value">{{ $meta['matiere'] ?? 'N/A' }}</td>
            <td width="20"></td>
            <td><span class="info-label">Filière :</span></td>
            <td class="info-value">{{ $meta['filiere'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><span class="info-label">Date :</span></td>
            <td class="info-value">{{ $meta['date'] ?? 'N/A' }}</td>
            <td></td>
            <td><span class="info-label">Niveau :</span></td>
            <td class="info-value">{{ $meta['niveau'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><span class="info-label">Créneau :</span></td>
            <td class="info-value">{{ $meta['heure'] ?? 'N/A' }}</td>
            <td></td>
            <td><span class="info-label">Salle :</span></td>
            <td class="info-value">{{ $meta['salle'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td colspan="5" style="padding-top:4px;">
                <span class="info-label">Enseignant :</span>
                <span class="info-value" style="display:inline-block;width:70%;border-bottom:1px dotted #999;">&nbsp;</span>
            </td>
        </tr>
    </table>

    {{-- STATISTIQUES --}}
    <table class="stats-table">
        <tr>
            <td class="stat-box stat-total">
                <span class="stat-num">{{ $summary['total'] ?? 0 }}</span>
                <span class="stat-lbl">Effectif</span>
            </td>
            <td class="stat-box stat-present">
                <span class="stat-num">{{ $summary['present'] ?? 0 }}</span>
                <span class="stat-lbl">Présents</span>
            </td>
            <td class="stat-box stat-late">
                <span class="stat-num">{{ $summary['late'] ?? 0 }}</span>
                <span class="stat-lbl">Retards</span>
            </td>
            <td class="stat-box stat-absent">
                <span class="stat-num">{{ $summary['absent'] ?? 0 }}</span>
                <span class="stat-lbl">Absents</span>
            </td>
        </tr>
    </table>

    {{-- LISTE DES ÉTUDIANTS --}}
    <table class="main-table">
        <thead>
            <tr>
                <th width="30">N°</th>
                <th width="90">Matricule</th>
                <th class="td-nom">Noms et Prénoms</th>
                <th width="90">Contact</th>
                <th width="110">Statut</th>
                <th width="110">Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $s)
            @php
                $isPresent = ($s->status ?? '') === 'present';
                $isLate    = $isPresent && !($s->on_time ?? true);
                $statusClass = $isLate ? 'st-retard' : ($isPresent ? 'st-present' : 'st-absent');
                $statusText  = $isLate ? 'Présent (retard)' : ($isPresent ? 'Présent' : 'Absent');
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $s->matricule ?? 'N/A' }}</strong></td>
                <td class="td-nom">{{ $s->name ?? 'N/A' }}</td>
                <td>{{ $s->phone ?? '—' }}</td>
                <td><span class="{{ $statusClass }}">{{ $statusText }}</span></td>
                <td>&nbsp;</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#999;padding:16px;">
                    Aucun enregistrement pour cette séance.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PIED DE PAGE --}}
    <table class="footer-table">
        <tr>
            <td style="width:50%;">
                <strong>Signature et Nom des surveillants :</strong><br><br>
                <span style="border-bottom:1px solid #999;display:block;width:80%;height:30px;"></span>
            </td>
            <td style="width:50%;text-align:right;">
                <strong>Signature et Nom de l'Enseignant :</strong><br><br>
                <span style="border-bottom:1px solid #999;display:inline-block;width:80%;height:30px;"></span>
            </td>
        </tr>
    </table>

    <div class="print-date">
        Imprimé le {{ $date }} par le système de gestion des présences EPAC-CAP
    </div>

</body>
</html>
