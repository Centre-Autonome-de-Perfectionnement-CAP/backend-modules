@extends('core::emails.base')

@section('title', 'Confirmation de Soumission - Fiche à Joindre')

@section('header')
    <h1>Confirmation de Soumission</h1>
    <p style="margin: 5px 0 0 0; color: white;">Dossier d'Inscription en Ligne</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $first_names }} {{ $last_name }}</strong>,</p>
    
    <p>Nous accusons réception de votre dossier d'inscription en ligne soumis le <strong>{{ $submission_datetime }}</strong>.</p>

    <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
        <p style="margin: 0;"><strong>Code de suivi :</strong> <span style="font-size: 14pt; color: #2e7d32;">{{ $tracking_code }}</span></p>
    </div>

    <h3>Informations de votre candidature :</h3>
    <table style="background: #f5f5f5;">
        <tr>
            <td style="width: 40%; padding: 10px;"><strong>Cycle :</strong></td>
            <td style="padding: 10px;">{{ $cycle_name }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Filière/Spécialité :</strong></td>
            <td style="padding: 10px;">{{ $department }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Niveau d'études :</strong></td>
            <td style="padding: 10px;">{{ $study_level }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;"><strong>Année académique :</strong></td>
            <td style="padding: 10px;">{{ $academic_year }}</td>
        </tr>
    </table>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
        <h3 style="margin: 0 0 10px 0; color: #856404;">⚠️ IMPORTANT - FICHE DE CONFIRMATION JOINTE</h3>
        <p style="margin: 5px 0; color: #856404; line-height: 1.8;">
            <strong>Vous trouverez en pièce jointe votre FICHE DE CONFIRMATION D'INSCRIPTION EN LIGNE.</strong>
        </p>
        <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #856404; line-height: 1.8;">
            <li><strong>Cette fiche est OBLIGATOIRE</strong> pour le dépôt physique de votre dossier.</li>
            <li>Vous devez <strong>IMPRIMER</strong> cette fiche et la joindre à votre dossier physique.</li>
            <li><strong>Sans cette fiche, votre dossier physique ne sera PAS ACCEPTÉ et votre candidature sera REJETÉE.</strong></li>
            <li>Si vous avez fourni un mauvais email, vous ne recevrez pas cette fiche et ne pourrez pas déposer votre dossier physique.</li>
        </ul>
    </div>

    <h3>Prochaines étapes :</h3>
    <ol style="line-height: 1.8;">
        <li><strong>Téléchargez et imprimez</strong> la fiche de confirmation jointe à cet email</li>
        <li><strong>Préparez votre dossier physique</strong> avec tous les documents originaux</li>
        <li><strong>Joignez la fiche imprimée</strong> à votre dossier physique</li>
        <li><strong>Déposez votre dossier complet</strong> au service des inscriptions pendant la période de dépôt physique</li>
        <li>Votre dossier sera ensuite examiné par notre commission d'admission</li>
    </ol>

    <div style="background: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 20px 0;">
        <p style="margin: 0; color: #c62828; font-weight: bold;">
            ⛔ ATTENTION : Toute candidature sans la fiche de confirmation imprimée sera automatiquement rejetée.
        </p>
    </div>

    <p style="margin-top: 30px;">Pour toute question concernant votre dossier, veuillez nous contacter en mentionnant votre code de suivi : <strong>{{ $tracking_code }}</strong></p>

    <p>Nous vous remercions pour votre candidature et restons à votre disposition.</p>

    <p style="margin-top: 20px;">Cordialement,<br><strong>Le Service des Inscriptions - CAP/EPAC</strong></p>
@endsection
