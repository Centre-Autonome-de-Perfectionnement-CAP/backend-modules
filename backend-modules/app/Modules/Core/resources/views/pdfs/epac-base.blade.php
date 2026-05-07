<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document PDF')</title>
    <style>
        @yield('font-faces')
        
        body {
            font-family: Arial, sans-serif;
            font-size: @yield('body-font-size', '10px');
            margin: @yield('body-margin', '20px');
            font-weight: @yield('body-font-weight', 'bold');
            position: relative;
        }
        .header, .footer {
            width: 100%;
            text-align: center;
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
        .footer {
            position: fixed;
            bottom: 8px;
            width: 100%;
            left: 0;
        }
        .printed-info {
            position: fixed;
            bottom: 0px;
            left: 10px;
        }
        .header{
            position: relative;
        }
        .header h1{
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2{
            font-size: @yield('header-h2-size', '14px');
            text-transform: uppercase;
            margin: @yield('header-h2-margin', '2px 0');
        }
        .header h3{
            font-size: @yield('header-h3-size', '13px');
            position: relative;
            top: @yield('header-h3-top', '0');
        }
        .logo-header{
            position: absolute;
            right: 0;
            height: 110px;
            @yield('logo-cap-styles')
        }
        .logo-header.epac{
            left: 0;
            text-align: left;
            height: 110px;
            @yield('logo-epac-styles')
        }
        .header p{
            font-weight: normal;
            font-size: @yield('header-p-size', '10px');
            position: relative;
            top: @yield('header-p-top', '0');
        }
        .header hr {
            border: @yield('header-hr-border', '.7px solid black');
            position: relative;
            top: @yield('header-hr-top', '0');
        }
        .info-table,.info-table tr,.info-table td{
            border: none;
        }
        .info-table td:first-child{
            width: 70%;
        }
        .info-table td{
            overflow: hidden;
        }
        .no-border,.no-border tr,.no-border td{
            border: none;
            margin-top: 15px;
            margin-bottom: 70px;
        }
        @yield('extra-styles')
    </style>
</head>
<body>
    @sectionMissing('custom-header')
    {{-- Header EPAC/CAP par défaut --}}
    <div class="header">
        @php
            $epacLogo = storage_path("images/epac.png");
            $capLogo = storage_path("images/cap.png");
            $epacBase64 = file_exists($epacLogo) && filesize($epacLogo) > 0 ? 'data:image/png;base64,' . base64_encode(file_get_contents($epacLogo)) : '';
            $capBase64 = file_exists($capLogo) && filesize($capLogo) > 0 ? 'data:image/png;base64,' . base64_encode(file_get_contents($capLogo)) : '';
        @endphp
        @if($epacBase64)
        <img src='{{ $epacBase64 }}' alt="logo-epac" class="logo-header epac">
        @endif
        @if($capBase64)
        <img src='{{ $capBase64 }}' alt="logo-cap"  class="logo-header">
        @endif
        <h3 style="margin:0px">Université d'Abomey-Calavi</h3>
        @php
            $bannerImg = storage_path("images/banner.png");
            $hasBanner = file_exists($bannerImg) && filesize($bannerImg) > 0;
            $bannerBase64 = $hasBanner ? 'data:image/png;base64,' . base64_encode(file_get_contents($bannerImg)) : '';
        @endphp
        @if($bannerBase64)
        <img src='{{ $bannerBase64 }}' alt="header-separator-img" style="margin:0px">
        @else
        <hr style="margin: 5px 0;">
        @endif
        <h2 style="margin:0">Ecole Polytechnique d'Abomey-Calavi</h2>
        @if($bannerBase64)
        <img src='{{ $bannerBase64 }}' alt="header-separator-img" style="margin:0px">
        @else
        <hr style="margin: 5px 0;">
        @endif
        <h1 style="margin:0;">Centre Autonome de Perfectionnement</h1>
        <p>
            01 BP 2009 COTONOU - TEl. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj
        </p>
        <hr>
        @sectionMissing('hide-annee')
        <div>Année académique : {{ $anneeAcamedique ?? $annee ?? '' }}</div>
        @endif
        @hasSection('document-title')
        <h2>@yield('document-title')</h2>
        @endif
    </div>
    @else
    {{-- Header personnalisé --}}
    @yield('custom-header')
    @endif

    {{-- Info table (filière, classe, matière, etc.) --}}
    @yield('info-table')

    {{-- Content (students table) --}}
    @yield('content')

    {{-- Additional content --}}
    @yield('additional-content')

    {{-- Footer --}}
    @sectionMissing('hide-footer')
    <div class="printed-info">
        @yield('footer-text', 'Imprimé le ' . now()->format('d/m/Y à H:i:s') . ' par Administrateur')
    </div>
    <hr class="footer">
    @endif
</body>
</html>
