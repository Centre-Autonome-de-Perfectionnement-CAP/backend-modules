<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        /* Reset pour la cohérence PDF/Word */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9pt; 
            margin: 1cm; 
            line-height: 1.1; 
        }
        
        /* Fix spécifique pour l'export Word (supprime les espacements automatiques) */
        p, div, h1, h2, td { 
            mso-margin-top-alt: 0pt; 
            mso-margin-bottom-alt: 0pt; 
        }

        /* Entête : Tableau pour un alignement strict des logos */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .logo-img { max-width: 85px; height: auto; display: block; }
        .header-center { text-align: center; vertical-align: middle; }
        .univ { font-size: 10pt; font-weight: bold; }
        .epac { font-size: 12pt; font-weight: bold; text-transform: uppercase; }
        .sep { font-size: 9pt; font-weight: bold; margin: 2px 0; }

        /* Ligne de séparation noire épaisse */
        .hr-line { border-top: 2px solid #000; margin: 5px 0 15px 0; }

        /* Titres du document */
        .doc-title { text-align: center; margin-bottom: 15px; }
        .doc-title h1 { 
            font-size: 15pt; 
            text-transform: uppercase; 
            text-decoration: underline; 
            font-weight: bold; 
        }

        /* Bloc des Statistiques (Pastilles Vert/Rouge) */
        .stats-table { width: 100%; margin-bottom: 15px; border-spacing: 10px 0; border-collapse: separate; }
        .stat-box { 
            width: 50%; 
            padding: 8px; 
            border: 1px solid #ccc; 
            text-align: center; 
            border-radius: 5px; 
        }
        .green-box { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .red-box { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Tableau principal des données */
        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { 
            border: 1px solid #000; 
            padding: 6px; 
            background: #f2f2f2; 
            font-size: 9pt; 
            font-weight: bold; 
        }
        .main-table td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: center; 
            font-size: 9pt; 
        }
        .c-nom { text-align: left !important; }

        /* Couleurs de statut (Texte pur pour éviter les "?" des symboles) */
        .status-ok { color: #155724; font-weight: bold; }
        .status-ko { color: #721c24; font-weight: bold; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td width="100" align="left">
                <img src="{{ public_path('assets/epac.png') }}" class="logo-img">
            </td>
            <td class="header-center">
                <div class="univ">Université d'Abomey-Calavi</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
                <div class="sep">-=-=-=-=-=-=-</div>
                <div class="epac">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
            </td>
            <td width="100" align="right">
                <img src="{{ public_path('assets/cap.png') }}" class="logo-img">
            </td>
        </tr>
    </table>

    <div class="hr-line"></div>

    <div class="doc-title">
        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 5px;">Année académique : 2025-2026</div>
        <h1>Liste des empreintes digitales</h1>
    </div>

    @php $studentCollection = collect($students); @endphp
    <table class="stats-table">
        <tr>
            <td class="stat-box green-box">
                <strong>{{ $studentCollection->where('fingerprint', true)->count() }}</strong> Enregistrées
            </td>
            <td class="stat-box red-box">
                <strong>{{ $studentCollection->where('fingerprint', false)->count() }}</strong> Non enregistrées
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="35">N°</th>
                <th width="100">Matricule</th>
                <th class="c-nom">Noms et Prénoms</th>
                <th width="100">Contact</th>
                <th width="130">Statut Empreinte</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $s)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $s->matricule ?? 'N/A' }}</td>
                <td class="c-nom">{{ $s->name }}</td>
                <td>{{ $s->phone ?? '—' }}</td>
                <td>
                    @if(isset($s->fingerprint) && $s->fingerprint)
                        <span class="status-ok">Enregistrée</span>
                    @else
                        <span class="status-ko">Non enregistrée</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>