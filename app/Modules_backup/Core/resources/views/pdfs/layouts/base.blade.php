<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document PDF')</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 1cm;
            counter-increment: page;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 1cm;
        }

        .contenu {
            height: 200px;
        }

        .logoepac {
            width: 150px;
            position: relative;
            top: 10px;
            left: 50px;
        }

        .logouac {
            width: 150px;
            position: absolute;
            top: 10px;
            right: 50px;
        }

        .header {
            text-align: center;
            position: absolute;
            top: 10px;
            left: 35%;
            right: 35%;
        }

        .header h3 {
            font-size: 20px;
            margin: 2px 0;
        }

        .header h4 {
            position: relative;
            top: -20px;
        }

        .header p {
            position: relative;
            top: -35px;
            font-size: 11px;
        }

        hr {
            border: .7px solid black;
            position: relative;
            top: -35px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            text-align: center;
            font-size: 15px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 2px;
            text-align: center;
        }

        .main {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            top: -15px;
        }

        .top-page {
            font-size: 17px;
            text-align: left;
        }

        .notOk {
            background: rgb(228, 159, 159);
        }

        .ok {
            background: rgb(140, 211, 240);
        }

        .pg {
            min-height: 96%;
        }

        footer {
            text-align: right;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 12px;
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
        <div style="text-align: left; font-size: 12px;">
            Imprimé le {{ date('d/m/Y à H:i') }} par la Cellule Informatique de la Division Formation Continue CAP
        </div>
        <div class="page"></div>
    </footer>
</body>
</html>
