<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste de présence</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 18px;
        }

        /* ===== EN-TÊTE EPAC/CAP avec logos ===== */
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .header-logo {
            width: 80px;
            text-align: center;
        }

        .header-logo img {
            max-width: 75px;
            max-height: 65px;
            object-fit: contain;
        }

        .header-center {
            flex: 1;
            text-align: center;
            padding: 0 12px;
        }

        .header-center .univ  { font-size: 11px; font-weight: bold; }
        .header-center .sep   { font-size: 9px; color: #555; }
        .header-center .epac  { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .header-center .cap   { font-size: 12px; font-weight: bold; }
        .header-center .addr  { font-size: 8px; color: #555; margin-top: 3px; }

        /* ===== TITRE ===== */
        .doc-title {
            text-align: center;
            margin: 10px 0 12px 0;
        }
        .doc-title .annee { font-size: 11px; font-weight: bold; }
        .doc-title .fiche { font-size: 14px; font-weight: bold; text-transform: uppercase; text-decoration: underline; }

        /* ===== INFOS CLASSE ===== */
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            gap: 12px;
        }
        .info-col { flex: 1; }
        .info-line {
            margin-bottom: 4px;
            font-size: 10px;
            padding-bottom: 2px;
            border-bottom: 1px dotted #aaa;
        }

        /* ===== TABLEAU ===== */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }

        thead tr { background: #e8e8e8; }
        thead th {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }
        tbody td {
            border: 1px solid #000;
            padding: 5px 4px;
            font-size: 9px;
        }

        .c-num       { text-align: center; width: 4%; }
        .c-mat       { text-align: center; width: 12%; }
        .c-nom       { text-align: left;   width: 26%; }
        .c-contact   { text-align: center; width: 13%; }
        .c-matiere   { text-align: left;   width: 18%; }
        .c-date      { text-align: center; width: 10%; }
        .c-debut     { text-align: center; width: 8%; }
        .c-fin       { text-align: center; width: 9%; }

        .present { color: #155724; font-weight: bold; }
        .absent  { color: #721c24; font-weight: bold; }

        /* ===== PIED DE PAGE — effectif + signatures ===== */
        .footer-stats {
            margin-top: 14px;
            display: flex;
            gap: 40px;
            font-size: 10px;
        }
        .footer-stats span strong { font-weight: bold; }

        .footer-signatures {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
        }
        .sig-block { width: 45%; }
        .sig-line {
            border-bottom: 1px solid #000;
            margin-top: 40px;
        }

        .footer-printed {
            margin-top: 16px;
            border-top: 1px solid #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            color: #555;
        }

        .filters-bar {
            margin: 6px 0;
            font-size: 9px;
            background: #f5f5f5;
            padding: 4px 8px;
            border-left: 3px solid #4472C4;
        }
        .filters-bar span { margin-right: 14px; }
    </style>
</head>
<body>

    {{-- ===== EN-TÊTE ===== --}}
    <div class="header-top">
        <div class="header-logo">
            <img src="{{ public_path('assets/epac.png') }}" alt="EPAC" />
        </div>
        <div class="header-center">
            <div class="univ">Université d'Abomey-Calavi</div>
            <div class="sep">-=-=-=-=-=-</div>
            <div class="epac">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
            <div class="sep">-=-=-=-=-=-</div>
            <div class="cap">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
            <div class="addr">01 BP 2009 COTONOU - TÉL. 21 36 14 32/21 36 09 93 - Email: epac.uac@epac.uac.bj</div>
        </div>
        <div class="header-logo">
            <img src="{{ public_path('assets/cap.png') }}" alt="CAP" />
        </div>
    </div>

    {{-- ===== TITRE ===== --}}
    <div class="doc-title">
        @if(!empty($filters['year']))
            <div class="annee">Année académique : {{ $filters['year'] }}</div>
        @endif
        <div class="fiche">Fiche de présence</div>
    </div>

    {{-- ===== INFOS DOCUMENT ===== --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-line">
                <strong>Filière :</strong>
                {{ !empty($filters['filiere']) ? $filters['filiere'] : '................................................' }}
            </div>
            <div class="info-line">
                <strong>Matière :</strong>
                {{ !empty($filters['matiere']) ? $filters['matiere'] : '................................................' }}
            </div>
        </div>
        <div class="info-col">
            <div class="info-line">
                <strong>Niveau :</strong>
                {{ !empty($filters['niveau']) ? $filters['niveau'] : '................................' }}
                &nbsp;&nbsp;&nbsp;&nbsp;
                <strong>Date :</strong> .............................
            </div>
            <div class="info-line">
                <strong>Durée :</strong> .............................
                &nbsp;&nbsp;&nbsp;&nbsp;
                @if(!empty($filters['heure']))
                    <strong>Heure :</strong> {{ $filters['heure'] }}
                @else
                    <strong>Heure :</strong> .............................
                @endif
            </div>
        </div>
    </div>

    <div class="info-line" style="margin-bottom:6px; font-size:10px; padding-bottom:2px; border-bottom:1px dotted #aaa;">
        <strong>Enseignant :</strong> ...............................................................................................
    </div>

    {{-- Filtres résumé --}}
    <div class="filters-bar">
        <span><strong>Total :</strong> {{ $total ?? count($students) }}</span>
        @if(!empty($filters['salle'])) <span><strong>Salle :</strong> {{ $filters['salle'] }}</span>@endif
    </div>

    {{-- ===== TABLEAU ===== --}}
    <table>
        <thead>
            <tr>
                <th class="c-num" rowspan="2">N°</th>
                <th class="c-mat" rowspan="2">Matricule</th>
                <th class="c-nom" rowspan="2">Noms et Prénoms</th>
                <th class="c-contact" rowspan="2">Contact</th>
                <th class="c-matiere" rowspan="2">Matière</th>
                <th class="c-date" rowspan="2">Date</th>
                <th colspan="2" style="text-align:center; background:#ddd;">Émargement</th>
            </tr>
            <tr>
                <th class="c-debut" style="background:#e8e8e8;">Début</th>
                <th class="c-fin" style="background:#e8e8e8;">Fin / Dépôt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                @php
                    $debut = $student->status_debut ?? $student->status ?? 'absent';
                    $fin   = $student->status_fin   ?? $student->status ?? 'absent';
                    $debutText = $debut === 'present' ? 'Présent' : 'Absent';
                    $finText   = $fin   === 'present' ? 'Présent' : 'Absent';
                @endphp
                <tr>
                    <td class="c-num">{{ $index + 1 }}</td>
                    <td class="c-mat">{{ $student->matricule ?? 'N/A' }}</td>
                    <td class="c-nom">{{ $student->name ?? 'N/A' }}</td>
                    <td class="c-contact">{{ $student->phone ?? '—' }}</td>
                    <td class="c-matiere">{{ $student->matiere ?? 'N/A' }}</td>
                    <td class="c-date">{{ $student->date ?? 'N/A' }}</td>
                    <td class="c-debut {{ $debut === 'present' ? 'present' : 'absent' }}">
                        {{ $debutText }}
                    </td>
                    <td class="c-fin {{ $fin === 'present' ? 'present' : 'absent' }}">
                        {{ $finText }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:20px; color:#999;">
                        Aucune donnée disponible
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== PIED DE PAGE — Effectif + Signatures ===== --}}
    @php
        $presents = collect($students)->where('status', 'present')->count();
        $absents  = count($students) - $presents;
    @endphp

    <div class="footer-stats">
        <span><strong>Effectif de la classe : {{ count($students) }}</strong></span>
        <span>Nombre de présent: <strong>{{ $presents }}</strong></span>
        <span>Nombre d'absent : <strong>{{ $absents }}</strong></span>
    </div>

    <div class="footer-signatures">
        <div class="sig-block">
            <div>Signature et Nom des surveillants :</div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-block" style="text-align:right;">
            <div>Signature et Nom de l'Enseignant :</div>
            <div class="sig-line"></div>
        </div>
    </div>

    <div class="footer-printed">
        <span>Imprimé le {{ $date }} par le système</span>
        <span>Total : {{ count($students) }} enregistrement(s)</span>
    </div>

</body>
</html>
