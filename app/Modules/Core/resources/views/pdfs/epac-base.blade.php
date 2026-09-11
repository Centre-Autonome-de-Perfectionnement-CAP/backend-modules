<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document PDF')</title>
    <style>
        @page {
            margin: 0cm;
        }

        @yield('font-faces')
        
        body {
            font-family: Arial, sans-serif;
            font-size: @yield('body-font-size', '10px');
            margin: 0;
            padding: 0;
            font-weight: @yield('body-font-weight', 'bold');
            position: relative;
        }

        .header-banner-container {
            width: 100%;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .header-banner {
            width: 100%;
            height: auto;
            display: block;
        }

        .footer-banner-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .footer-banner {
            width: 100%;
            height: auto;
            display: block;
        }

        .document-body {
            margin: @yield('body-margin', '10px 25px 60px 25px');
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            font-weight: normal;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }

        .printed-info {
            position: fixed;
            bottom: 3px;
            right: 20px;
            color: #ffffff;
            font-size: 8px;
            font-weight: normal;
            z-index: 10;
        }

        .header-title-section {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .header-title-section h2 {
            font-size: @yield('header-h2-size', '15px');
            text-transform: uppercase;
            margin: 5px 0;
        }

        .header-title-section .annee-acad {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-table, .info-table tr, .info-table td {
            border: none;
        }
        .info-table td:first-child {
            width: 70%;
        }
        .info-table td {
            overflow: hidden;
        }
        .no-border, .no-border tr, .no-border td {
            border: none;
            margin-top: 15px;
            margin-bottom: 70px;
        }

        @yield('extra-styles')
    </style>
</head>
<body>
    @sectionMissing('custom-header')
    {{-- Bannière d'en-tête officielle avec logo EPAC, textes officiels et logo CAP --}}
    <div class="header-banner-container">
        @php
            $headerImg = file_exists(public_path("images/epac_header_portrait.png"))
                ? public_path("images/epac_header_portrait.png")
                : (file_exists(storage_path("images/epac_header_portrait.png"))
                    ? storage_path("images/epac_header_portrait.png")
                    : base_path("storage/images/epac_header_portrait.png"));
            $headerBase64 = file_exists($headerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerImg)) : '';
        @endphp
        @if($headerBase64)
            <img src="{{ $headerBase64 }}" alt="En-tête officiel EPAC" class="header-banner">
        @endif
    </div>

    @if((!View::hasSection('hide-annee') && (isset($anneeAcamedique) || isset($annee))) || View::hasSection('document-title'))
    <div class="header-title-section">
        @sectionMissing('hide-annee')
            @if(isset($anneeAcamedique) || isset($annee))
                <div class="annee-acad">Année académique : {{ $anneeAcamedique ?? $annee ?? '' }}</div>
            @endif
        @endif

        @hasSection('document-title')
            <h2>@yield('document-title')</h2>
        @endif
    </div>
    @endif
    @else
    {{-- Header personnalisé si défini --}}
    @yield('custom-header')
    @endif

    <div class="document-body">
        {{-- Info table (filière, classe, matière, etc.) --}}
        @yield('info-table')

        {{-- Content (students table, etc.) --}}
        @yield('content')

        {{-- Additional content --}}
        @yield('additional-content')
    </div>

    {{-- Bannière de pied de page officielle --}}
    @sectionMissing('hide-footer')
    <div class="footer-banner-container">
        @php
            $footerImg = file_exists(public_path("images/epac_footer_portrait.png"))
                ? public_path("images/epac_footer_portrait.png")
                : (file_exists(storage_path("images/epac_footer_portrait.png"))
                    ? storage_path("images/epac_footer_portrait.png")
                    : base_path("storage/images/epac_footer_portrait.png"));
            $footerBase64 = file_exists($footerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerImg)) : '';
        @endphp
        @if($footerBase64)
            <img src="{{ $footerBase64 }}" alt="Pied de page officiel EPAC" class="footer-banner">
        @endif
        <div class="printed-info">
            @yield('footer-text', 'Imprimé le ' . now()->format('d/m/Y à H:i'))
        </div>
    </div>
    @endif
</body>
</html>
