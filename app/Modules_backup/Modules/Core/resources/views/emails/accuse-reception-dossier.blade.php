@extends('core::emails.base')

@section('title', 'Accusé de Réception - Dossier d\'Inscription')

@section('header')
    <h1>Accusé de Réception</h1>
    <p style="margin: 5px 0 0 0; color: white;">Dossier d'Inscription</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $candidat['nom'] ?? 'Candidat(e)' }}</strong>,</p>
    
    <p>Nous accusons réception de votre dossier d'inscription soumis le <strong>{{ $dateReception ?? now()->format('d/m/Y') }}</strong>.</p>

    <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
        <p style="margin: 0;"><strong>Numéro de dossier :</strong> <span style="font-size: 14pt; color: #2e7d32;">{{ $numeroDossier ?? '' }}</span></p>
    </div>

    <h3>Informations de votre candidature :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Programme demandé :</strong></td>
            <td style="padding: 10px;">{{ $programme ?? '' }}</td>
        </tr>
        @if(isset($specialite))
        <tr>
            <td style="padding: 10px;"><strong>Spécialité :</strong></td>
            <td style="padding: 10px;">{{ $specialite }}</td>
        </tr>
        @endif
        @if(isset($niveau))
        <tr>
            <td style="padding: 10px;"><strong>Niveau :</strong></td>
            <td style="padding: 10px;">{{ $niveau }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px;"><strong>Année académique :</strong></td>
            <td style="padding: 10px;">{{ $anneeAcademique ?? '' }}</td>
        </tr>
    </table>

    <h3>Documents reçus :</h3>
    <ul style="background: #f9f9f9; padding: 20px 20px 20px 40px; margin: 10px 0;">
        @foreach($documentsRecus ?? [] as $document)
            <li style="margin: 5px 0;">{{ $document }}</li>
        @endforeach
    </ul>

    @if(isset($documentsManquants) && count($documentsManquants) > 0)
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>⚠️ Documents manquants :</strong></p>
            <ul style="margin: 0 0 0 20px; padding: 0;">
                @foreach($documentsManquants as $document)
                    <li style="margin: 5px 0;">{{ $document }}</li>
                @endforeach
            </ul>
            <p style="margin: 10px 0 0 0; color: #856404;">
                <em>Veuillez compléter votre dossier dans les plus brefs délais.</em>
            </p>
        </div>
    @endif

    <h3>Prochaines étapes :</h3>
    <ol style="line-height: 1.8;">
        <li>Votre dossier est en cours d'examen par notre commission d'admission</li>
        <li>Vous recevrez une notification par email concernant la décision d'admission</li>
        <li>Le délai de traitement est généralement de <strong>{{ $delaiTraitement ?? '5 à 10 jours ouvrables' }}</strong></li>
        @if(isset($etapesSupplementaires))
            @foreach($etapesSupplementaires as $etape)
                <li>{{ $etape }}</li>
            @endforeach
        @endif
    </ol>

    @if(isset($urlSuivi))
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $urlSuivi }}" class="button">Suivre mon dossier</a>
        </div>
    @endif

    <p style="margin-top: 30px;">Pour toute question concernant votre dossier, veuillez nous contacter en mentionnant votre numéro de dossier : <strong>{{ $numeroDossier ?? '' }}</strong></p>

    @if(isset($contact))
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Contact :</strong></p>
            @if(isset($contact['email']))
                <p style="margin: 5px 0;">Email : {{ $contact['email'] }}</p>
            @endif
            @if(isset($contact['telephone']))
                <p style="margin: 5px 0;">Téléphone : {{ $contact['telephone'] }}</p>
            @endif
        </div>
    @endif

    <p>Nous vous remercions pour votre candidature et restons à votre disposition.</p>

    <p style="margin-top: 20px;">Cordialement,<br><strong>{{ $serviceInscription ?? 'Le Service des Inscriptions' }}</strong></p>
@endsection
