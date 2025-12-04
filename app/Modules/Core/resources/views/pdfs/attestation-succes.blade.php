@extends('Core::pdfs.epac-base')

@section('title', 'Attestation de Succès')

@section('font-faces')
@font-face {
    font-family: 'Albertus Medium';
    src: url('{{ storage_path("fonts/AlbertusMedium.ttf") }}');
}
@font-face {
    font-family: 'ALGERIA';
    src: url('{{ storage_path("fonts/ALGERIA.TTF") }}');
}
@font-face {
    font-family: 'Berlin Sans FB';
    src: url('{{ storage_path("fonts/BRLNSB.TTF") }}');
}
@font-face {
    font-family: 'Pristina';
    src: url('{{ storage_path("fonts/PRISTINA.TTF") }}');
}
@endsection

@section('body-font-size', '14pt')
@section('body-margin', '2.5cm 2cm 1.5cm 2cm')

@section('extra-styles')
.attestation {
    font-size: 30pt;
    font-weight: normal;
    text-shadow: 2px 3px 4px black;
    line-height: 45px;
    margin-bottom: 10px;
    font-family: 'ALGERIA';
    letter-spacing: 5px;
    text-align: center;
}
.diplome {
    font-size: 10pt;
    font-weight: bold;
    margin-bottom: 13px;
    letter-spacing: -1px;
    text-align: center;
}
.main {
    text-align: justify;
    margin-top: 20px;
}
.paragraph-pristina {
    font-size: 15pt;
    font-family: 'Pristina';
    letter-spacing: 1.6px;
}
.retrait {
    margin-left: 10mm;
}
.info p {
    margin-top: 4px;
    margin-bottom: 4px;
}
.filiere {
    text-transform: uppercase;
    color: rgba(36, 88, 187, 1);
    font-size: 18pt;
    font-family: 'Berlin Sans FB';
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
}
.avant {
    font-family: 'Berlin Sans FB';
}
.directeur {
    margin-top: 35px;
    text-align: center;
    padding-left: 60%;
}
.name {
    font-size: 13pt;
    font-style: italic;
}
.date {
    font-family: "Albertus Medium";
}
.top {
    height: 2.75cm;
    text-align: left;
    position: relative;
}
.top .one {
    margin-top: 1.21cm;
    font-weight: normal;
    font-size: 12pt;
    font-family: 'Arial';
}
.top .two {
    margin-top: -0.61cm;
    font-weight: normal;
    font-size: 12pt;
    text-align: right;
    font-family: 'Arial';
    width: 60%;
    margin-left: auto;
}
.banner {
    margin-bottom: 7px;
}
.ccap {
    position: absolute;
    width: 100%;
    max-width: 280px;
    top: 0;
    text-align: left;
    margin-right: 60%;
}
.first {
    margin-bottom: 9px;
    font-style: italic;
    font-size: 13pt;
}
@endsection

@section('custom-header')
<div class="header">
    <img src="{{ storage_path('images/1.png') }}" class="logoepac" alt="logo EPAC UAC" style="position: absolute; left: 20px; top: 17px; width: 100px;">
    <div class="entete" style="text-align: center;">
        <span style="font-size: 12px;">République du Bénin</span>
        <div style="height: 2px; background: #000; width: 50px; margin: 5px auto;"></div>
        <span style="font-size: 17px; display: block;">Université d'Abomey-Calavi</span>
        <span style="display: block;">Ecole polytechnique d'Abomey-Calavi</span>
        <img src="{{ storage_path('images/sep.png') }}" alt="" style="margin: 5px 0;">
        <span style="font-size: 18px; display: block; position: relative; top: -15px;">Direction</span>
    </div>
    <img src="{{ storage_path('images/2.png') }}" class="logouac" alt="logo UAC" style="position: absolute; right: 20px; top: 17px; width: 100px;">
    <hr style="width: 97%; margin-left: 12px; position: relative; top: -13px;">
</div>
@endsection

@section('hide-annee')
@endsection

@section('content')
<div class="middle" style="position: relative; top: 30px;">
    <div class="top">
        @if($posD ?? false)
            <img src="{{ storage_path('images/par1.png') }}" alt="" style="position: absolute; top: 27px; left: {{ 10.34*$posD }}px;">
        @endif
        @if($posCAP ?? false)
            <img src="{{ storage_path('images/par2.png') }}" alt="" style="position: absolute; top: 27px; left: {{ 10.34*$posCAP }}px;">
        @endif
       <div class="one">N° {{ $Etudiant->attestation?->decision?->decision }}</div>
       <div class="two">Abomey-Calavi, le <span style="visibility: hidden;">0000</span>/<span style="visibility: hidden;">0000</span>/<span style="visibility: hidden;">000000</span></div>
    </div>
    <div class="bottom">
        <p class="attestation">ATTESTATION DE SUCCES</p>
        <p class="diplome" style="text-transform:uppercase;"> DE DIPLOME   
            @if($Etudiant->starts_with_voy)
                D'I
            @else
                DE
            @endif
            {{ $Etudiant->filiere?->diplome?->nom }} <br> <span style="font-size: 14pt;">({{ $Etudiant->sigle }})</span>
        </p>
        <p class="banner"><img src="{{ storage_path('banner.png') }}" alt=""></p>
    </div>
</div>

<div class="main">
    <p class="paragraph-pristina"><span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
        (EPAC), ex-Collège Polytechnique Universitaire (CPU), soussigné, atteste que: </p>
    <div class="info">
        <p><span>{{ $Etudiant->genre == 'masculin' ? 'Mr' : 'Mlle' }} </span> <span>{{ $Etudiant->nom }}</span> <span> {{ $Etudiant->prenoms }}</span> </p>
        <p>Né<span>{{ $Etudiant->genre == 'masculin' ? '' : 'e' }}</span>  {{ $Etudiant->ne_vers == 0 ? 'le' : 'vers' }}<span class="date">  {{ $Etudiant->getDateDeNaissance() }}</span> à <span class="lieu" style="text-transform:capitalize;">{{ $Etudiant->lieu_de_naissance }} (Rep. {{ $Etudiant->pays_de_naissance }})</span></p>
        <p>a suivi et terminé avec succès la formation @if($Etudiant->filiere->diplome->sigle != "DIC") de @else d' @endif <span>{{ $Etudiant->filiere?->diplome?->nom }}</span> <span style="font-size: 14pt;"> ({{ $Etudiant->filiere?->diplome?->sigle }}) </span> le <span class="date">{{ $Etudiant->attestation?->getDateDeSoutenance() }}</span> dans la filière :</p>
    </div>
    <div class="filiere"> {{ $Etudiant->filiere?->nom }}</div>
    <div class="text" style="margin-bottom: 6px;"><span class="avant"><span class="retrait">La présente attestation revêtue du seau de l'EPAC est délivrée à l'intéressé en attendant l'établissement de l'attestation du diplôme.</span></span></div>
    <div style="position:relative;margin-top: 30px;">
        <div class="directeur">
            <p class="first" style="text-transform: capitalize;">{{ $signataire->poste }}, </p>
            <div style="height: 70px;"> </div>
            <p style="text-decoration: underline;"> <strong class="first">{{ $titreSignataire }}</strong><strong class="name">{{ $nomSignataire }}</strong></p>
        </div>
        @if($ccap ?? false)
        <div class="ccap">
            <p class="first" style="text-transform: capitalize;">{{ $ccap?->poste }}, </p>
            <div style="height: 40px;"> </div>
            <p style="text-decoration: underline;">{{ $ccap?->nomination }}</p>
        </div>
        @endif
    </div>
</div>
@endsection

@section('hide-footer')
@endsection

@section('additional-content')
<div style="position: fixed; left: 15px; right: 15px; bottom: 5px; width: 100%; margin-top: 10px;">
    <div style="width: 97%; margin-left: -6px; height: 1px; background-color: #000;"></div>
    <div style="font-size: 13px; width: 100%; margin-left: -15px; text-align: center;">
        01 B.P. 2009 COTONOU - Téléphone : 21 36 09 93 - FAX : 21 36 01 99 Email : epac.uac@epac.uac.bj - epacuac@bj.refer.org
    </div>
</div>
@endsection
