@extends('core::emails.base')

@section('title', 'Quittance Validée - Paiement Accepté')

@section('header')
    <h1 style="color: white;">✅ Paiement Validé</h1>
    <p style="margin: 5px 0 0 0; color: white;">Votre quittance a été acceptée</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $etudiant['nom'] ?? 'Étudiant(e)' }}</strong>,</p>
    
    <div style="background: #e8f5e9; padding: 20px; border-left: 4px solid #4CAF50; margin: 20px 0; text-align: center;">
        <p style="margin: 0; font-size: 16pt; color: #2e7d32;">
            <strong>✅ PAIEMENT VALIDÉ</strong>
        </p>
    </div>

    <p>Nous avons le plaisir de vous informer que votre quittance de paiement a été <strong style="color: #4CAF50;">vérifiée et acceptée</strong> par notre service financier.</p>

    <h3>Détails de la transaction :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Référence :</strong></td>
            <td style="padding: 10px; font-weight: bold; color: #4CAF50;">{{ $numeroReference ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Matricule :</strong></td>
            <td style="padding: 10px;">{{ $etudiant['matricule'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Type de paiement :</strong></td>
            <td style="padding: 10px;">{{ $typePaiement ?? '' }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Montant payé :</strong></td>
            <td style="padding: 10px; font-weight: bold; font-size: 14pt; color: #4CAF50;">{{ $montant ?? '' }}</td>
        </tr>
        @if(isset($periode))
        <tr>
            <td style="padding: 10px;"><strong>Période :</strong></td>
            <td style="padding: 10px;">{{ $periode }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px;"><strong>Date de validation :</strong></td>
            <td style="padding: 10px;">{{ $dateValidation ?? now()->format('d/m/Y à H:i') }}</td>
        </tr>
        @if(isset($numeroBordereau))
        <tr>
            <td style="padding: 10px;"><strong>N° Bordereau :</strong></td>
            <td style="padding: 10px;">{{ $numeroBordereau }}</td>
        </tr>
        @endif
    </table>

    @if(isset($recuPaiement))
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $recuPaiement }}" class="button">Télécharger le reçu</a>
        </div>
    @endif

    @if(isset($soldRestant))
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0;"><strong>💰 Solde restant :</strong></p>
            <table style="border: none; margin-top: 10px;">
                <tr style="border: none;">
                    <td style="border: none; padding: 5px 0;"><strong>Montant total à payer :</strong></td>
                    <td style="border: none; padding: 5px 0; text-align: right;">{{ $soldRestant['total'] ?? '' }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 5px 0;"><strong>Montant déjà payé :</strong></td>
                    <td style="border: none; padding: 5px 0; text-align: right; color: #4CAF50;">{{ $soldRestant['paye'] ?? '' }}</td>
                </tr>
                <tr style="border: none; font-weight: bold; font-size: 12pt;">
                    <td style="border: none; padding: 5px 0; border-top: 2px solid #333;"><strong>Reste à payer :</strong></td>
                    <td style="border: none; padding: 5px 0; text-align: right; border-top: 2px solid #333; color: #ff9800;">{{ $soldRestant['restant'] ?? '' }}</td>
                </tr>
            </table>
        </div>
    @else
        <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
            <p style="margin: 0; color: #2e7d32;"><strong>✅ Votre compte est à jour !</strong></p>
        </div>
    @endif

    @if(isset($avantagesDebloques) && count($avantagesDebloques) > 0)
        <h3>✨ Accès débloqués :</h3>
        <ul style="background: #f9f9f9; padding: 20px 20px 20px 40px; margin: 10px 0; line-height: 1.8;">
            @foreach($avantagesDebloques as $avantage)
                <li style="margin: 5px 0;">{{ $avantage }}</li>
            @endforeach
        </ul>
    @endif

    @if(isset($prochainePaiement))
        <div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📅 Prochain paiement :</strong></p>
            <p style="margin: 0;"><strong>Date limite :</strong> {{ $prochainePaiement['date'] ?? '' }}</p>
            <p style="margin: 5px 0 0 0;"><strong>Montant :</strong> {{ $prochainePaiement['montant'] ?? '' }}</p>
            @if(isset($prochainePaiement['description']))
                <p style="margin: 5px 0 0 0;"><em>{{ $prochainePaiement['description'] }}</em></p>
            @endif
        </div>
    @endif

    @if(isset($urlEspaceEtudiant))
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $urlEspaceEtudiant }}" class="button">Accéder à mon espace</a>
        </div>
    @endif

    <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>ℹ️ Informations importantes :</strong></p>
        <ul style="margin: 0 0 0 20px; padding: 0;">
            <li>Conservez précieusement votre reçu de paiement</li>
            <li>Ce paiement apparaîtra sur votre relevé de compte étudiant dans 24h</li>
            @if(isset($informationsSupplementaires))
                @foreach($informationsSupplementaires as $info)
                    <li>{{ $info }}</li>
                @endforeach
            @endif
        </ul>
    </div>

    @if(isset($contact))
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📞 Service Financier :</strong></p>
            @if(isset($contact['email']))
                <p style="margin: 5px 0;">Email : {{ $contact['email'] }}</p>
            @endif
            @if(isset($contact['telephone']))
                <p style="margin: 5px 0;">Téléphone : {{ $contact['telephone'] }}</p>
            @endif
        </div>
    @endif

    <p style="margin-top: 30px;">Merci pour votre paiement. Nous restons à votre disposition pour toute question.</p>

    <p>Cordialement,<br><strong>{{ $serviceFinancier ?? 'Le Service Financier' }}</strong></p>
@endsection
