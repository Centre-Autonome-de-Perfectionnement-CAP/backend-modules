@extends('core::pdfs.epac-base')

@section('title', 'Fiche de Confirmation d\'Inscription')

@section('custom-header')
<div class="header">
    @php
        $epacLogo = public_path("assets/epac.png");
        $capLogo = public_path("assets/cap.png");
    @endphp
    @if(file_exists($epacLogo) && filesize($epacLogo) > 0)
    <img src='{{ $epacLogo }}' alt="logo-epac" class="logo-header epac">
    @endif
    @if(file_exists($capLogo) && filesize($capLogo) > 0)
    <img src='{{ $capLogo }}' alt="logo-cap"  class="logo-header">
    @endif
    <h3 style="margin:0px">Université d'Abomey-Calavi</h3>
    @php
        $bannerImg = public_path("assets/banner.png");
        $hasBanner = file_exists($bannerImg) && filesize($bannerImg) > 0;
    @endphp
    @if($hasBanner)
    <img src='{{ $bannerImg }}' alt="header-separator-img" style="margin:0px">
    @else
    <hr style="margin: 5px 0;">
    @endif
    <h2 style="margin:0">Ecole Polytechnique d'Abomey-Calavi</h2>
    @if($hasBanner)
    <img src='{{ $bannerImg }}' alt="header-separator-img" style="margin:0px">
    @else
    <hr style="margin: 5px 0;">
    @endif
    <h1 style="margin:0;">Centre Autonome de Perfectionnement</h1>
    <p>
        01 BP 2009 COTONOU - TEl. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj
    </p>
    <hr>
</div>
@endsection

@section('content')
<div class="main">
    <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 22px; text-decoration: underline;">
        FICHE DE CONFIRMATION D'INSCRIPTION EN LIGNE
    </div>

    <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
        <p style="margin: 5px 0;"><strong>Code de suivi :</strong> <span style="font-size: 16pt; color: #2e7d32;">{{ $tracking_code }}</span></p>
        <p style="margin: 5px 0;"><strong>Date et heure de soumission :</strong> {{ $submission_datetime }}</p>
    </div>

    <div style="margin: 20px 0;">
        <h3 style="border-bottom: 2px solid #333; padding-bottom: 5px;">INFORMATIONS PERSONNELLES</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
            <tr>
                <td style="padding: 8px; width: 40%; border: 1px solid #ddd; background: #f5f5f5;"><strong>Nom :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $last_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Prénoms :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $first_names }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Email :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Contacts :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ implode(', ', $contacts) }}</td>
            </tr>
            @if(isset($birth_date))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Date de naissance :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $birth_date }}</td>
            </tr>
            @endif
            @if(isset($birth_place))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Lieu de naissance :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $birth_place }}</td>
            </tr>
            @endif
            @if(isset($gender))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Sexe :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $gender === 'M' ? 'Masculin' : 'Féminin' }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="margin: 20px 0;">
        <h3 style="border-bottom: 2px solid #333; padding-bottom: 5px;">CHOIX DE FORMATION</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
            <tr>
                <td style="padding: 8px; width: 40%; border: 1px solid #ddd; background: #f5f5f5;"><strong>Cycle :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $cycle_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Filière/Spécialité :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $department }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Niveau d'études :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $study_level }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;"><strong>Année académique :</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $academic_year }}</td>
            </tr>
        </table>
    </div>

    <div style="margin: 20px 0;">
        <h3 style="border-bottom: 2px solid #333; padding-bottom: 5px;">RÉCAPITULATIF DES PIÈCES DÉPOSÉES</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">N°</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nom du document</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $index => $document)
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $index + 1 }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">{{ $document }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #2e7d32;">✓ Déposé</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 30px 0;">
        <h3 style="margin: 0 0 10px 0; color: #856404;">⚠️ IMPORTANT - À LIRE ATTENTIVEMENT</h3>
        <ul style="margin: 0; padding-left: 20px; color: #856404; line-height: 1.8;">
            <li><strong>Cette fiche est OBLIGATOIRE pour le dépôt physique de votre dossier.</strong></li>
            <li>Vous devez <strong>IMPRIMER</strong> cette fiche et la joindre à votre dossier physique.</li>
            <li>Sans cette fiche, votre dossier physique ne sera <strong>PAS ACCEPTÉ</strong>.</li>
            <li>Conservez précieusement votre code de suivi : <strong>{{ $tracking_code }}</strong></li>
            <li>En cas de perte de cette fiche, contactez immédiatement le service des inscriptions.</li>
        </ul>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #666;">
        <p style="margin: 5px 0;">Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p style="margin: 5px 0;">Ce document atteste de la soumission en ligne de votre dossier d'inscription</p>
    </div>
</div>
@endsection

@section('footer-text')
    <div style="text-align: center; font-size: 11px;">
        <strong>NB:</strong> Cette fiche doit être imprimée et jointe au dossier physique. Toute candidature sans cette fiche sera automatiquement rejetée.
    </div>
@endsection
