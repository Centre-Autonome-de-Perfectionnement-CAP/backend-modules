<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Email')</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background: #f8f8f8;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f8f8f8;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        @hasSection('header')
            <div class="header">
                @yield('header')
            </div>
        @endif
        
        <div class="content">
            @yield('content')
        </div>
        
        <div class="footer">
            @hasSection('footer')
                @yield('footer')
            @else
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
            @endif
        </div>
    </div>
</body>
</html>
