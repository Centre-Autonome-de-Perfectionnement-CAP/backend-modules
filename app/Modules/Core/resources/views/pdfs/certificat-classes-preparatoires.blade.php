@extends('core::pdfs.epac-base')

@section('title', 'Attestation PDF')

@section('body-font-size', '14pt')
@section('body-font-weight', 'normal')
@section('body-margin', '5cm 2.5cm 2cm 2.5cm')

@section('font-faces')
    @font-face {
        font-family: "ALGERIA";
        src: url({{ storage_path('fonts/ALGERIA.ttf') }});
    }
    @font-face {
        font-family: "Arial";
        src: url({{ storage_path('fonts/arial.ttf') }});
    }
    @font-face {
        font-family: "Albertus Medium";
        src: url({{ storage_path('fonts/albr55w.ttf') }});
    }
    @font-face {
        font-family: 'Pristina';
        src: url({{ storage_path('fonts/PRISTINA.ttf') }}) format('truetype');
    }
    @font-face {
        font-family: "Berlin Sans FB";
        src: url({{ storage_path('fonts/Berlin Sans FB Regular.ttf') }});
    }
@endsection

@section('extra-styles')
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        text-align: center;
        font-family: "Albertus Medium";
    }
    .top {
        height: 2.75cm;
        border-radius: 1px solid black;
        text-align: left;
    }
    .attestation {
        font-size: 35pt;
        font-family: 'ALGERIA';
    }
    .diplome {
        font-size: 10pt;
        font-weight: bold;
        margin-bottom: 13px;
        letter-spacing: -1px;
    }
    .main {
        text-align: justify;
        margin-top: 20px;
    }
    .first {
        margin-bottom: 9px;
        font-style: italic;
        font-size: 13pt;
    }
    .mention {
        text-decoration: underline;
        font-weight: normal;
    }
    .retrait {
        margin-left: 10mm;
    }
    .info {
        margin-top: 5px;
    }
    .info p {
        margin-top: 2px;
        margin-bottom: 2px;
    }
    .filiere {
        text-transform: uppercase;
        color: rgba(36, 88, 187, 1);
        font-size: 19pt;
        font-family: 'Berlin Sans FB';
        margin-top: 15px;
        margin-bottom: 15px;
        text-align: center;
    }
    .text {
        margin-bottom: 4px;
    }
    .avant {
        font-family: 'Berlin Sans FB';
    }
    .decision {
        color: blue;
        opacity: .8;
        font-style: normal;
        font-size: 13pt;
    }
    .directeur {
        margin-top: 30px;
        text-align: center;
        padding-left: 42%;
    }
    .name {
        font-size: 13pt;
        font-style: italic;
    }
    .paragraph-pristina {
        font-size: 14pt;
        font-family: 'Pristina';
        letter-spacing: 1.3px;
    }
@endsection

@section('custom-header')
<div style="text-align: center; margin-bottom: 30px; page-break-inside: avoid;">
    <p style="font-size: 28pt; margin: 20px 0; text-transform: uppercase; " class="attestation">
        CERTIFICAT PREPARATOIRE<br>AUX ETUDES D'INGENIEUR
</p>
    @php
        $bannerPath = public_path('assets/banner-1.png');
    @endphp
    @if(file_exists($bannerPath))
    <img src="{{ $bannerPath }}" alt="Banner" style="max-width: 100%; height: auto;">
    @endif
</div>
@endsection

@section('hide-footer', true)

@section('content')
<div class="main">
    <p class="paragraph-pristina">
        <span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
        (EPAC), ex-Collège Polytechnique Universitaire (CPU), soussigné, atteste que:
    </p>
    <br/>
    <div class="info">
        <p style="margin-bottom: 5px;">
            <span style="{{ $etudiant->genre == 'M' ? '' : 'text-decoration: line-through;' }}">Mr</span> / <span style="{{ $etudiant->genre == 'F' ? '' : 'text-decoration: line-through;' }}">Mlle</span>
            <span style="text-transform: uppercase;">{{ $etudiant->nom }}</span>
            <span style="text-transform: capitalize;"> {{ $etudiant->prenoms }} </span> .........................
        </p> 
        <p style="margin-bottom: 5px;">
            Né<span>{{ $etudiant->genre == 'M' ? '' : 'e' }}</span>
            {{ $etudiant->ne_vers == 0 ? 'le ' : '' }}<span class="date">{{ $etudiant->date_naissance }}</span> à
            <span class="lieu" style="text-transform:capitalize;">{{ $etudiant->lieu_naissance }} (Rep. {{ $etudiant->pays_naissance }})</span>................
        </p>
        <p>
            <!-- @if(trim(str_replace("-","",$etudiant->matricule))) Numéro matricule : {{ $etudiant->matricule }}<br>@endif -->
            a obtenu le Certificat Préparatoire aux Etudes d'Ingénieur conformément à la délibération du {{ $etudiant->date_soutenance }}:
        </p>
    </div>
    <div class="filiere">FILIERE : {{ trim($etudiant->filiere->libelle) }}</div>

    <div class="paragraph-pristina" style="margin-top: 15px;">
        <span class="retrait">Le</span> présent certificat, revêtu du sceau de l'EPAC, est délivrée pour servir et valor ce que de droit.
    </div>
    <br/><br/>
    <div class="directeur">
        <p class="first" style="text-transform: capitalize;">{{ $signataire->poste }},</p>
        <div style="height: 55px;"></div>
        <p style="text-decoration: underline;">
            <strong class="name">{{ $signataire->nomination }}</strong>
        </p>
    </div>
    
    <div style="position: fixed; bottom: 60px; text-align: center;">
        <hr style="border: 1px solid black;">
        <p style="font-size: 9pt; font-weight: bold;">
            Ce certificat est le résultat de la mise à niveau de l'étudiant et n'est valable que pour une inscription aux études ingénieurs du CAP
        </p>
    </div>
</div>
@endsection
