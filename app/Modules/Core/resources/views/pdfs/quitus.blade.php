@extends('core::pdfs.epac-base')

@section('title', 'Quitus de Soutenance')
@section('body-font-size', '14px')
@section('body-font-weight', 'normal')
@section('body-margin', '15px 40px 60px 40px')
@section('hide-annee', 'true')

@section('extra-styles')
.titre {
    font-size: 24px;
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    text-align: center;
    margin-top: 25px;
    margin-bottom: 35px;
}
.section {
    font-size: 16px;
    line-height: 1.8;
}
@endsection

@section('content')
<div class="titre">
    QUITUS
</div>

<div class="section">
    <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify; margin-bottom: 20px;">Je soussigné <strong>{{ $titre }} {{ $nom }} {{ $prenom }}</strong>, superviseur du mémoire de l'étudiant <strong>{{ $nometu }} {{ $prenometu }}</strong>, l'autorise à déposer son {{ $diplome == 'Licence Professionnelle' ? 'rapport' : 'mémoire' }} de fin de {{ $diplome }} en Génie Electrique portant sur le thème : « <strong>{{ $intitule }}</strong> », en vue de sa soutenance.</div>
    
    <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify; margin-bottom: 30px;">En foi de quoi, le présent quitus lui est délivré pour servir et valoir ce que de droit.</div>
    
    <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: right; margin-bottom: 30px;">Fait à Abomey-Calavi, le {{ date('d/m/Y') }}.</div>

    <div style="text-align: right;">
        <p style="font-weight: bold; margin-bottom: 40px;">Superviseur de Mémoire</p>
        <p style="font-weight: bold; margin: 2px 0;">{{ $titre }} {{ $nom }} {{ $prenom }}</p>
        <p style="margin: 2px 0;">{{ $grade ?? '' }}</p>
        <p style="margin: 2px 0; font-size: 14px;">Enseignant Chercheur à l'EPAC/UAC</p>
    </div>
</div>
@endsection
