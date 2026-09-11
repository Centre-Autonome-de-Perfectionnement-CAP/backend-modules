<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document PDF')</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0cm;
            counter-increment: page;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .contenu {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            text-align: center;
            font-size: 13px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 3px;
            text-align: center;
        }

        .main {
            text-align: center;
            margin: 5px 25px 55px 25px;
        }

        .top-page {
            font-size: 14px;
            text-align: left;
        }

        .notOk {
            background: rgb(228, 159, 159);
        }

        .ok {
            background: rgb(140, 211, 240);
        }

        .pg {
            margin: 0;
            padding: 0;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        footer .footer-img {
            width: 100%;
            height: auto;
            display: block;
        }

        footer .footer-info-left {
            position: absolute;
            bottom: 4px;
            left: 25px;
            color: #ffffff;
            font-size: 8px;
            z-index: 10;
        }

        footer .footer-info-right {
            position: absolute;
            bottom: 4px;
            right: 25px;
            color: #ffffff;
            font-size: 8px;
            z-index: 10;
        }

        footer .page:after {
            content: "Page " counter(page);
        }

        .no-page-break {
            page-break-after: avoid;
        }

        @yield('styles')
    </style>
</head>
<body>
    @yield('content')

    <footer>
        @php
            $footerLandscape = file_exists(public_path("images/epac_footer_landscape.png"))
                ? public_path("images/epac_footer_landscape.png")
                : (file_exists(storage_path("images/epac_footer_landscape.png"))
                    ? storage_path("images/epac_footer_landscape.png")
                    : base_path("storage/images/epac_footer_landscape.png"));
            $footerBase64 = file_exists($footerLandscape) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerLandscape)) : '';
        @endphp
        @if($footerBase64)
            <img src="{{ $footerBase64 }}" class="footer-img" alt="Pied de page officiel EPAC - CAP">
        @endif
        <div class="footer-info-left">
            Imprimé le {{ date('d/m/Y à H:i') }} par la Cellule Informatique CAP
        </div>
        <div class="footer-info-right page"></div>
    </footer>
</body>
</html>
