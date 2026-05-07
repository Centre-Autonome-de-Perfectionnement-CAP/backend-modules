@extends('core::emails.base')

@section('title', 'Réinitialisation de mot de passe')

@section('header')
    <h1>Réinitialisation de mot de passe</h1>
@endsection

@section('content')
    <p>Bonjour,</p>
    
    <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $resetUrl }}" class="button">Réinitialiser le mot de passe</a>
    </div>

    <p style="font-size: 10pt; color: #666;">
        Ce lien de réinitialisation expirera dans {{ $expireMinutes ?? 60 }} minutes.
    </p>

    <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise de votre part.</p>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p style="margin: 0; color: #856404;">
            <strong>Sécurité :</strong> Si vous n'êtes pas à l'origine de cette demande, 
            veuillez contacter immédiatement notre support.
        </p>
    </div>
@endsection
