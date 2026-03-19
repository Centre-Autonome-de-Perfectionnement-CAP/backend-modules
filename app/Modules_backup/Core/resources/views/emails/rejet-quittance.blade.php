@extends('core::emails.base')

@section('title', 'Quittance Non Validée')

@section('header')
    <h1>Quittance Non Validée</h1>
    <p style="margin: 5px 0 0 0; color: white;">Action requise</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $etudiant['nom'] ?? 'Étudiant(e)' }}</strong>,</p>
    
    <p>Nous avons examiné votre quittance de paiement soumise le <strong>{{ $dateReception ?? '' }}</strong>.</p>

    <div style="background: #ffebee; padding: 20px; border-left: 4px solid #d32f2f; margin: 20px 0;">
        <p style="margin: 0; font-size: 14pt; color: #c62828;">
            <strong>❌ QUITTANCE NON VALIDÉE</strong>
        </p>
    </div>

    <p>Malheureusement, nous ne pouvons pas valider votre quittance pour la (les) raison(s) suivante(s) :</p>

    @if(isset($motifsRejet) && count($motifsRejet) > 0)
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>⚠️ Motifs de rejet :</strong></p>
            <ul style="margin: 0 0 0 20px; padding: 0;">
                @foreach($motifsRejet as $motif)
                    <li style="margin: 5px 0; color: #856404;">{{ $motif }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h3>Informations de la soumission :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Référence :</strong></td>
            <td style="padding: 10px;">{{ $numeroReference ?? '' }}</td>
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
            <td style="padding: 10px;"><strong>Montant déclaré :</strong></td>
            <td style="padding: 10px;">{{ $montant ?? '' }}</td>
        </tr>
        @if(isset($numeroBordereau))
        <tr>
            <td style="padding: 10px;"><strong>N° Bordereau :</strong></td>
            <td style="padding: 10px;">{{ $numeroBordereau }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 10px;"><strong>Date de rejet :</strong></td>
            <td style="padding: 10px;">{{ $dateRejet ?? now()->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>

    <h3>🔄 Actions à effectuer :</h3>
    <ol style="line-height: 1.8; background: #f9f9f9; padding: 20px 20px 20px 40px;">
        @if(isset($actionsRequises) && count($actionsRequises) > 0)
            @foreach($actionsRequises as $action)
                <li style="margin: 10px 0;">{{ $action }}</li>
            @endforeach
        @else
            <li>Vérifiez les informations de votre bordereau de paiement</li>
            <li>Assurez-vous que le document est lisible et complet</li>
            <li>Soumettez à nouveau votre quittance avec les corrections nécessaires</li>
        @endif
    </ol>

    @if(isset($documentsCorriges) && count($documentsCorriges) > 0)
        <div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📎 Documents à fournir :</strong></p>
            <ul style="margin: 0 0 0 20px; padding: 0;">
                @foreach($documentsCorriges as $doc)
                    <li style="margin: 5px 0;">{{ $doc }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($montantCorrect))
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0;"><strong>💰 Montant correct à payer :</strong> <span style="font-size: 14pt; font-weight: bold;">{{ $montantCorrect }}</span></p>
            @if(isset($detailsMontant))
                <p style="margin: 10px 0 0 0; font-size: 10pt;">{{ $detailsMontant }}</p>
            @endif
        </div>
    @endif

    @if(isset($delaiResoumission))
        <div style="background: #ffebee; padding: 15px; border-left: 4px solid #d32f2f; margin: 20px 0;">
            <p style="margin: 0; color: #c62828;"><strong>⏰ Date limite de resoumission :</strong> {{ $delaiResoumission }}</p>
        </div>
    @endif

    @if(isset($urlResoumission))
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $urlResoumission }}" class="button" style="background: #ff9800;">Soumettre une nouvelle quittance</a>
        </div>
    @endif

    <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0;"><strong>ℹ️ Informations importantes :</strong></p>
        <ul style="margin: 0 0 0 20px; padding: 0;">
            <li>Votre paiement n'est pas enregistré tant que la quittance n'est pas validée</li>
            <li>Veuillez effectuer la correction dans les meilleurs délais pour éviter tout retard</li>
            @if(isset($informationsSupplementaires))
                @foreach($informationsSupplementaires as $info)
                    <li>{{ $info }}</li>
                @endforeach
            @endif
        </ul>
    </div>

    @if(isset($consequences) && count($consequences) > 0)
        <div style="background: #ffebee; padding: 15px; border-left: 4px solid #d32f2f; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #c62828;"><strong>⚠️ Conséquences en cas de non-régularisation :</strong></p>
            <ul style="margin: 0 0 0 20px; padding: 0;">
                @foreach($consequences as $consequence)
                    <li style="margin: 5px 0; color: #c62828;">{{ $consequence }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(isset($aideDisponible))
        <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
            <p style="margin: 0;"><strong>💡 Besoin d'aide ?</strong></p>
            <p style="margin: 10px 0 0 0;">{{ $aideDisponible }}</p>
        </div>
    @endif

    @if(isset($contact))
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📞 Contactez le Service Financier :</strong></p>
            @if(isset($contact['email']))
                <p style="margin: 5px 0;">Email : {{ $contact['email'] }}</p>
            @endif
            @if(isset($contact['telephone']))
                <p style="margin: 5px 0;">Téléphone : {{ $contact['telephone'] }}</p>
            @endif
            @if(isset($contact['horaires']))
                <p style="margin: 5px 0;">Horaires : {{ $contact['horaires'] }}</p>
            @endif
            <p style="margin: 10px 0 0 0; font-size: 10pt; font-style: italic;">
                N'oubliez pas de mentionner votre numéro de référence : <strong>{{ $numeroReference ?? '' }}</strong>
            </p>
        </div>
    @endif

    <p style="margin-top: 30px;">Nous restons à votre disposition pour vous accompagner dans la régularisation de votre paiement.</p>

    <p>Cordialement,<br><strong>{{ $serviceFinancier ?? 'Le Service Financier' }}</strong></p>
@endsection
