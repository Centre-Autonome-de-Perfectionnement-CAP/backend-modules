{{-- Pages publiques des travaux : HTML complet rendu par le serveur (lisible
     par les moteurs de recherche), thème clair, sans JavaScript requis. --}}
<!doctype html>
<html lang="fr" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title') · CAP-EPAC</title>
    <meta name="description" content="@yield('description')">
    @yield('head')
    <link rel="icon" href="/images/cap.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #326761; --brand-dark: #24504b; --ink: #0f172a; --muted: #475569; --line: #e2e8f0; --soft: #f1f6f5; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.6; color: var(--ink); background: #f8fafc; }
        a { color: var(--brand); }
        .wrap { max-width: 980px; margin: 0 auto; padding: 0 16px; }
        header.top { background: #fff; border-bottom: 1px solid var(--line); }
        header.top .wrap { display: flex; align-items: center; gap: 12px; padding-top: 12px; padding-bottom: 12px; }
        header.top img { width: 40px; height: 40px; object-fit: contain; }
        header.top .brand { font-weight: 700; color: var(--ink); text-decoration: none; line-height: 1.2; }
        header.top .brand small { display: block; font-weight: 500; color: var(--muted); font-size: 14px; }
        header.top nav { margin-left: auto; display: flex; gap: 16px; font-size: 15px; font-weight: 600; }
        header.top nav a { text-decoration: none; }
        main.wrap { padding-top: 28px; padding-bottom: 48px; }
        h1 { font-size: 28px; line-height: 1.25; margin: 0 0 8px; letter-spacing: -0.01em; }
        h2 { font-size: 19px; margin: 28px 0 10px; }
        .lead { color: var(--muted); margin: 0 0 20px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px 20px; }
        .pill { display: inline-block; font-size: 14px; font-weight: 600; padding: 2px 10px; border-radius: 999px; background: var(--soft); color: var(--brand-dark); }
        .muted { color: var(--muted); font-size: 15px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 44px; padding: 0 18px; border-radius: 12px; border: 0;
            background: var(--brand); color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn:hover { background: var(--brand-dark); }
        .btn.ghost { background: #fff; color: var(--brand); border: 1px solid #cbd5e1; }
        input, select, textarea { font: inherit; font-size: 16px; width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; background: #fff; color: var(--ink); }
        input:focus, select:focus, textarea:focus { outline: 3px solid rgba(50, 103, 97, .2); border-color: var(--brand); }
        label { font-size: 15px; font-weight: 600; display: block; margin-bottom: 4px; }
        .alert { border-radius: 12px; padding: 12px 14px; margin: 12px 0; font-size: 15px; }
        .alert.ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        footer { border-top: 1px solid var(--line); background: #fff; color: var(--muted); font-size: 14px; padding: 18px 0; }
        @media (max-width: 600px) { h1 { font-size: 23px; } header.top nav { gap: 10px; font-size: 14px; } }
        @yield('styles')
    </style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <img src="/images/cap.png" alt="">
        <a class="brand" href="/">CAP-EPAC<small>Centre Autonome de Perfectionnement</small></a>
        <nav><a href="/travaux">Travaux</a><a href="/">Accueil</a></nav>
    </div>
</header>
<main class="wrap">
    @yield('content')
</main>
<footer><div class="wrap">Centre Autonome de Perfectionnement (CAP) · École Polytechnique d'Abomey-Calavi · Université d'Abomey-Calavi</div></footer>
</body>
</html>
