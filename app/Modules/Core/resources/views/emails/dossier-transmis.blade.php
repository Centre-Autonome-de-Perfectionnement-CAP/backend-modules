@extends('core::emails.base')

@section('title', 'Dossier à traiter')

@section('header')
    <h1 style="color: white;">📂 Nouveau dossier à traiter</h1>
    <p style="margin: 5px 0 0 0; color: white;">Un dossier vous a été transmis</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $destinataireNom ?? 'Madame / Monsieur' }}</strong>,</p>

    <p>
        <strong>{{ $expediteurNom ?? 'Un acteur' }}</strong>
        ({{ $expediteurRole ?? '' }}) vous a transmis un dossier qui nécessite votre attention.
    </p>

    <div style="background: #e3f2fd; padding: 20px; border-left: 4px solid #2196F3; margin: 20px 0; text-align: center;">
        <p style="margin: 0; font-size: 16pt; color: #1565c0;">
            <strong>📋 DOSSIER EN ATTENTE DE TRAITEMENT</strong>
        </p>
    </div>

    <h3>Détails du dossier :</h3>
    <table style="background: #f5f5f5; width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Référence :</strong></td>
            <td style="padding: 10px; font-weight: bold; color: #1565c0;">{{ $reference ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Type de document :</strong></td>
            <td style="padding: 10px;">{{ $typeDocument ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Étudiant concerné :</strong></td>
            <td style="padding: 10px;">{{ $etudiantNom ?? '' }}</td>
        </tr>
        @if(!empty($etudiantMatricule))
        <tr>
            <td style="padding: 10px;"><strong>Matricule :</strong></td>
            <td style="padding: 10px;">{{ $etudiantMatricule }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px;"><strong>Transmis le :</strong></td>
            <td style="padding: 10px;">{{ $dateTransmission ?? now()->format('d/m/Y à H:i') }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Votre rôle dans ce dossier :</strong></td>
            <td style="padding: 10px; font-weight: bold; color: #e65100;">{{ $destinataireRole ?? '' }}</td>
        </tr>
    </table>

    @if(!empty($commentaire))
    <div style="background: #fff8e1; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p style="margin: 0 0 5px 0;"><strong>💬 Message de {{ $expediteurNom ?? 'l\'expéditeur' }} :</strong></p>
        <p style="margin: 0; font-style: italic;">{{ $commentaire }}</p>
    </div>
    @endif

    <div style="background: #f3e5f5; padding: 15px; border-left: 4px solid #9c27b0; margin: 20px 0;">
        <p style="margin: 0;"><strong>⚡ Action requise :</strong> Veuillez vous connecter à votre espace pour consulter et traiter ce dossier dans les meilleurs délais.</p>
    </div>

    @if(!empty($urlEspace))
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $urlEspace }}" class="button">Accéder au dossier</a>
    </div>
    @endif

    <p style="margin-top: 30px;">Ce message est envoyé automatiquement par le système de gestion des dossiers du CAP-EPAC. Merci de ne pas y répondre directement.</p>

    <p>Cordialement,<br><strong>{{ $etablissement ?? 'Le Système CAP-EPAC' }}</strong></p>
@endsection
