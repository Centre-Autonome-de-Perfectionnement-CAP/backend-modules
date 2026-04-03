<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des empreintes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; padding: 18px; }

        .header-top {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 8px; border-bottom: 2px solid #000; padding-bottom: 8px;
        }
        .header-logo { width: 80px; text-align: center; }
        .header-logo img { max-width: 75px; max-height: 65px; object-fit: contain; }
        .header-center { flex: 1; text-align: center; padding: 0 12px; }
        .header-center .univ { font-size: 11px; font-weight: bold; }
        .header-center .sep  { font-size: 9px; color: #444; }
        .header-center .epac { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .header-center .cap  { font-size: 12px; font-weight: bold; }
        .header-center .addr { font-size: 8px; color: #555; margin-top: 3px; }

        .doc-title { text-align: center; margin: 10px 0 12px 0; }
        .doc-title .annee { font-size: 11px; font-weight: bold; }
        .doc-title .fiche { font-size: 14px; font-weight: bold; text-transform: uppercase; text-decoration: underline; }

        .info-grid { display: flex; gap: 12px; margin-bottom: 6px; }
        .info-col { flex: 1; }
        .info-line { margin-bottom: 4px; font-size: 10px; padding-bottom: 2px; border-bottom: 1px dotted #aaa; }

        .stats-row { display: flex; gap: 12px; margin: 8px 0; }
        .stat-box { flex: 1; padding: 8px; border-radius: 4px; text-align: center; border: 1px solid; }
        .stat-box.green { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .stat-box.red   { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .stat-box.blue  { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .stat-box .val  { font-size: 20px; font-weight: 800; }
        .stat-box .lbl  { font-size: 8px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead tr { background: #e8e8e8; }
        thead th { border: 1px solid #000; padding: 6px 4px; text-align: center; font-size: 9px; font-weight: bold; }
        tbody td { border: 1px solid #000; padding: 5px 4px; font-size: 9px; }

        .c-num  { text-align: center; width: 5%; }
        .c-mat  { text-align: center; width: 14%; }
        .c-nom  { text-align: left;   width: 32%; }
        .c-con  { text-align: center; width: 18%; }
        .c-niv  { text-align: center; width: 10%; }
        .c-fp   { text-align: center; width: 21%; }

        .ok { color: #155724; font-weight: bold; }
        .ko { color: #721c24; font-weight: bold; }

        /* Bas de page signatures */
        .footer-stats {
            margin-top: 14px;
            display: flex;
            gap: 48px;
            font-size: 10px;
        }

        .footer-signatures {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block { width: 45%; }
        .sig-label { font-size: 10px; font-weight: bold; }
        .sig-line  { border-bottom: 1px solid #000; margin-top: 44px; }

        .footer-printed {
            margin-top: 14px;
            border-top: 1px solid #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            color: #555;
        }
    </style>
</head>
<body>

    {{-- EN-TÊTE EPAC/CAP --}}
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

    {{-- TITRE --}}
    <div class="doc-title">
        @if(!empty($filters['annee'] ?? $filters['year'] ?? ''))
            <div class="annee">Année académique : {{ $filters['annee'] ?? $filters['year'] }}</div>
        @endif
        <div class="fiche">Liste des empreintes digitales</div>
    </div>

    {{-- INFOS --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-line">
                <strong>Filière :</strong>
                {{ !empty($filters['filiere']) ? $filters['filiere'] : '...................................................' }}
            </div>
        </div>
        <div class="info-col">
            <div class="info-line">
                <strong>Niveau :</strong>
                {{ !empty($filters['niveau']) ? $filters['niveau'] : '........................' }}
                &nbsp;&nbsp;&nbsp;
                <strong>Date :</strong> {{ $date }}
            </div>
        </div>
    </div>

    {{-- STATS résumé --}}
    @php
        $registered    = collect($students)->where('fingerprint', true)->count();
        $notRegistered = collect($students)->where('fingerprint', false)->count();
    @endphp

    <div class="stats-row">
        <div class="stat-box green">
            <div class="val">{{ $registered }}</div>
            <div class="lbl">Empreintes enregistrées</div>
        </div>
        <div class="stat-box red">
            <div class="val">{{ $notRegistered }}</div>
            <div class="lbl">Non enregistrées</div>
        </div>
        <div class="stat-box blue">
            <div class="val">{{ count($students) }}</div>
            <div class="lbl">Total étudiants</div>
        </div>
    </div>

    {{-- TABLEAU --}}
    <table>
        <thead>
            <tr>
                <th class="c-num">N°</th>
                <th class="c-mat">Matricule</th>
                <th class="c-nom">Noms et Prénoms</th>
                <th class="c-con">Contact</th>
                <th class="c-niv">Niveau</th>
                <th class="c-fp">Empreinte digitale</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                <tr>
                    <td class="c-num">{{ $index + 1 }}</td>
                    <td class="c-mat">{{ $student->matricule ?? 'N/A' }}</td>
                    <td class="c-nom">{{ $student->name ?? 'N/A' }}</td>
                    <td class="c-con">{{ $student->phone ?? '—' }}</td>
                    <td class="c-niv">{{ $student->niveau ?? 'N/A' }}</td>
                    <td class="c-fp">
                        @if($student->fingerprint ?? false)
                            <span class="ok">✓ Enregistrée</span>
                        @else
                            <span class="ko">✗ Non enregistrée</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#999;">
                        Aucune donnée disponible
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== BAS DE PAGE : Effectif + Signatures ===== --}}
    <div class="footer-stats">
        <span>Effectif de la classe : <strong>{{ count($students) }}</strong></span>
        <span>Nombre enregistré : <strong>{{ $registered }}</strong></span>
        <span>Nombre non enregistré : <strong>{{ $notRegistered }}</strong></span>
    </div>

    <div class="footer-signatures">
        <div class="sig-block">
            <div class="sig-label">Signature et Nom des surveillants :</div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-block" style="text-align:right;">
            <div class="sig-label">Signature et Nom du Responsable :</div>
            <div class="sig-line"></div>
        </div>
    </div>

    <div class="footer-printed">
        <span>Imprimé le {{ $date }} par le système</span>
        <span>Total : {{ count($students) }} étudiant(s)</span>
    </div>

</body>
</html>
