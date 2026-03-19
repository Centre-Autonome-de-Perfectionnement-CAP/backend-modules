@extends('core::emails.base')

@section('title', 'Accusé de Réception - Quittance de Paiement')

@section('header')
    <h1>Accusé de Réception</h1>
    <p style="margin: 5px 0 0 0; color: white;">Quittance de Paiement</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $etudiant['nom'] ?? 'Étudiant(e)' }}</strong>,</p>
    
    <p>Nous accusons réception de votre quittance de paiement soumise le <strong>{{ $dateReception ?? now()->format('d/m/Y à H:i') }}</strong>.</p>

    <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
        <p style="margin: 0;"><strong>Numéro de référence :</strong> <span style="font-size: 14pt; color: #2e7d32;">{{ $numeroReference ?? '' }}</span></p>
    </div>

    <h3>Détails de la soumission :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Matricule :</strong></td>
            <td style="padding: 10px;">{{ $etudiant['matricule'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Type de paiement :</strong></td>
            <td style="padding: 10px;">{{ $typePaiement ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Montant déclaré :</strong></td>
            <td style="padding: 10px; font-weight: bold;">{{ $montant ?? '' }}</td>
        </tr>
        @if(isset($periode))
        <tr>
            <td style="padding: 10px;"><strong>Période :</strong></td>
            <td style="padding: 10px;">{{ $periode }}</td>
        </tr>
        @endif
        @if(isset($numeroBordereau))
        <tr>
            <td style="padding: 10px;"><strong>N° Bordereau/Reçu :</strong></td>
            <td style="padding: 10px;">{{ $numeroBordereau }}</td>
        </tr>
        @endif
        @if(isset($modePaiement))
        <tr>
            <td style="padding: 10px;"><strong>Mode de paiement :</strong></td>
            <td style="padding: 10px;">{{ $modePaiement }}</td>
        </tr>
        @endif
    </table>

    @if(isset($documentsJoints) && count($documentsJoints) > 0)
        <h3>Documents reçus :</h3>
        <ul style="background: #f9f9f9; padding: 20px 20px 20px 40px; margin: 10px 0;">
            @foreach($documentsJoints as $document)
                <li style="margin: 5px 0;">{{ $document }}</li>
            @endforeach
        </ul>
    @endif

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <p style="margin: 0;"><strong>⏳ status :</strong> En cours de vérification</p>
    </div>

    <h3>Prochaines étapes :</h3>
    <ol style="line-height: 1.8;">
        <li>Votre quittance est en cours de vérification par le service financier</li>
        <li>Vous recevrez une notification par email concernant la validation ou le rejet</li>
        <li>Le délai de traitement est généralement de <strong>{{ $delaiTraitement ?? '24 à 48 heures' }}</strong></li>
        @if(isset($etapesSupplementaires))
            @foreach($etapesSupplementaires as $etape)
                <li>{{ $etape }}</li>
            @endforeach
        @endif
    </ol>

    @if(isset($urlSuivi))
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $urlSuivi }}" class="button">Suivre ma demande</a>
        </div>
    @endif

    <div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
        <p style="margin: 0;"><strong>ℹ️ Important :</strong></p>
        <p style="margin: 10px 0 0 0;">
            {{ $informationImportante ?? 'Conservez votre numéro de référence pour toute correspondance ultérieure. Votre paiement ne sera effectif qu\'après validation de votre quittance.' }}
        </p>
    </div>

    <p style="margin-top: 30px;">Pour toute question concernant votre soumission, veuillez nous contacter en mentionnant votre numéro de référence : <strong>{{ $numeroReference ?? '' }}</strong></p>

    @if(isset($contact))
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📞 Contact - Service Financier :</strong></p>
            @if(isset($contact['email']))
                <p style="margin: 5px 0;">Email : {{ $contact['email'] }}</p>
            @endif
            @if(isset($contact['telephone']))
                <p style="margin: 5px 0;">Téléphone : {{ $contact['telephone'] }}</p>
            @endif
            @if(isset($contact['horaires']))
                <p style="margin: 5px 0;">Horaires : {{ $contact['horaires'] }}</p>
            @endif
        </div>
    @endif

    <p>Cordialement,<br><strong>{{ $serviceFinancier ?? 'Le Service Financier' }}</strong></p>
@endsection
