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
            margin: 5px 25px 35px 25px;
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
            bottom: 10px;
            left: 25px;
            right: 25px;
            height: 20px;
            font-size: 11px;
            color: #333333;
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
        <div style="float: left; width: 80%; text-align: left; font-size: 11px;">
            Imprimé le {{ date('d/m/Y à H:i') }} par la Cellule Informatique de la Division Formation Continue CAP
        </div>
        <div class="page" style="float: right; width: 18%; text-align: right; font-size: 11px;"></div>
        <div style="clear: both;"></div>
    </footer>
</body>
</html>
