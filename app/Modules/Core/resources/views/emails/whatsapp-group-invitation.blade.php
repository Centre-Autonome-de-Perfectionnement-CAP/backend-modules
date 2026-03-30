@extends('core::emails.base')

@section('title', 'Rejoignez le groupe WhatsApp')

@section('header')
    <div style="font-size: 48px; margin-bottom: 10px;">💬</div>
    <h1 style="margin: 0; font-size: 24px;">Rejoignez le groupe WhatsApp</h1>
    <p style="margin: 5px 0 0 0; font-size: 16px;">{{ $departmentName }}</p>
@endsection

@section('content')
    <p>Cher(e) étudiant(e),</p>
    
    <div style="background-color: #f0f9f4; padding: 20px; border-radius: 5px; border-left: 4px solid #25D366; margin: 20px 0;">
        {!! nl2br(e($messageContent)) !!}
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $whatsappLink }}" style="display: inline-block; padding: 15px 40px; background-color: #25D366; color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
            📱 Rejoindre le groupe WhatsApp
        </a>
    </div>

    <div style="background-color: #e8f5e9; padding: 15px; border-radius: 5px; text-align: center; font-size: 14px; margin: 20px 0;">
        <strong>Important :</strong> Cliquez sur le bouton ci-dessus pour rejoindre le groupe WhatsApp de votre filière.
    </div>

    <p>Ce groupe vous permettra de rester informé(e) des actualités importantes et de communiquer avec vos camarades de classe.</p>
@endsection

@section('footer')
    <p style="margin: 0;">
        <strong>CAP-EPAC</strong><br>
        Centre Autonome de Perfectionnement - École Polytechnique d'Abomey-Calavi<br>
        Cet email a été envoyé automatiquement, merci de ne pas y répondre.
    </p>
@endsection
