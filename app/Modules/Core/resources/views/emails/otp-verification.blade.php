@extends('core::emails.base')

@section('title', 'Code de vérification - CAP EPAC')

@section('header')
    <h1>Code de Vérification</h1>
    <p style="margin: 5px 0 0 0; color: white;">Sécurité de votre démarche en ligne</p>
@endsection

@section('content')
    <p>Bonjour,</p>
    
    <p>Vous avez initié une démarche sur le portail du <strong>Centre Autonome de Perfectionnement (CAP - EPAC / UAC)</strong>.</p>
    
    <p>Veuillez utiliser le code de sécurité ci-dessous pour confirmer que cette adresse email vous appartient bien :</p>

    <div style="background: #f0fdf4; border: 2px dashed #22c55e; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
        <span style="font-size: 11pt; color: #15803d; text-transform: uppercase; letter-spacing: 2px; font-weight: bold; display: block; margin-bottom: 8px;">Votre code de sécurité</span>
        <span style="font-size: 32pt; font-family: 'Courier New', Courier, monospace; font-weight: 900; color: #166534; letter-spacing: 8px;">{{ $code }}</span>
        <span style="font-size: 10pt; color: #6b7280; display: block; margin-top: 10px;">Ce code expire dans <strong>10 minutes</strong>.</span>
    </div>

    <p style="color: #4b5563; font-size: 10pt; line-height: 1.6;">
        ⚠️ <strong>Important :</strong> Ne partagez ce code avec personne. Si vous n'avez pas initié cette démarche, vous pouvez ignorer cet email en toute sécurité.
    </p>
@endsection
