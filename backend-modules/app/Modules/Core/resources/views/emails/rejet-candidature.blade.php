@extends('core::emails.base')

@section('title', 'Décision de Candidature')

@section('header')
    <h1 style="color: white;">Réponse à votre Candidature</h1>
    <p style="margin: 5px 0 0 0; color: white;">Décision concernant votre dossier</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $prenoms ?? '' }} {{ $nom ?? 'Candidat(e)' }}</strong>,</p>
    
    <div style="background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0; text-align: center;">
        <p style="margin: 0; font-size: 16pt; color: #c62828;">
            <strong>CANDIDATURE NON RETENUE</strong>
        </p>
    </div>

    <p>Nous vous remercions sincèrement pour l'intérêt que vous portez à notre établissement et pour avoir soumis votre candidature.</p>

    <p>Après un examen attentif de votre dossier par notre commission d'admission, nous avons le regret de vous informer que nous ne sommes pas en mesure de vous proposer une place pour cette rentrée.</p>

    @if(isset($filiere))
    <h3>Informations de votre candidature :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Filière :</strong></td>
            <td style="padding: 10px;">{{ $filiere }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Statut :</strong></td>
            <td style="padding: 10px; color: #f44336; font-weight: bold;">Non retenu(e)</td>
        </tr>
    </table>
    @endif

    @if(isset($commentaireCuca) || isset($commentaireCuo))
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>Informations complémentaires :</strong></p>
            @if(isset($commentaireCuca))
                <p style="margin: 5px 0;">{{ $commentaireCuca }}</p>
            @endif
            @if(isset($commentaireCuo))
                <p style="margin: 5px 0;">{{ $commentaireCuo }}</p>
            @endif
        </div>
    @endif

    <div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
        <p style="margin: 0;"><strong>ℹ️ Information importante :</strong></p>
        <p style="margin: 10px 0 0 0;">
            Nous vous encourageons à soumettre une nouvelle candidature pour la prochaine rentrée académique. N'hésitez pas à améliorer votre dossier en tenant compte des recommandations qui vous ont été communiquées.
        </p>
    </div>

    @if(isset($contact))
        <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>📞 Contact :</strong></p>
            <p style="margin: 0;">Si vous souhaitez obtenir plus d'informations sur votre candidature :</p>
            @if(isset($contact['email']))
                <p style="margin: 5px 0;">Email : {{ $contact['email'] }}</p>
            @endif
            @if(isset($contact['telephone']))
                <p style="margin: 5px 0;">Téléphone : {{ $contact['telephone'] }}</p>
            @endif
        </div>
    @endif

    <p style="margin-top: 30px;">Nous apprécions l'effort que vous avez consacré à votre candidature et vous souhaitons beaucoup de succès dans la poursuite de vos projets académiques et professionnels.</p>

    <p>Cordialement,<br><strong>{{ $etablissement ?? 'Service Informatique du CAP' }}</strong></p>
@endsection
