@extends('core::emails.base')

@section('title', 'Bienvenue')

@section('header')
    <h1>Bienvenue sur {{ config('app.name') }}</h1>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $name ?? 'Utilisateur' }}</strong>,</p>
    
    <p>Nous sommes ravis de vous accueillir sur notre plateforme. Votre compte a été créé avec succès.</p>

    @if(isset($credentials))
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Vos identifiants de connexion :</h3>
            <p><strong>Email :</strong> {{ $credentials['email'] }}</p>
            @if(isset($credentials['password']))
                <p><strong>Mot de passe temporaire :</strong> {{ $credentials['password'] }}</p>
                <p style="color: #d32f2f; font-size: 11pt;"><em>⚠️ Veuillez changer ce mot de passe lors de votre première connexion.</em></p>
            @endif
        </div>
    @endif

    <p>Pour commencer, veuillez cliquer sur le bouton ci-dessous pour accéder à votre compte :</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $loginUrl ?? '#' }}" class="button">Se connecter</a>
    </div>

    @if(isset($nextSteps))
        <h3>Prochaines étapes :</h3>
        <ul>
            @foreach($nextSteps as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ul>
    @endif

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
@endsection
