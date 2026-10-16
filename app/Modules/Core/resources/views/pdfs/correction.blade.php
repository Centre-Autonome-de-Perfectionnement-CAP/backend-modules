@extends('core::pdfs.epac-base')

@section('title', 'Attestation de Correction')
@section('body-font-size', '14px')
@section('body-font-weight', 'normal')
@section('body-margin', '15px 40px 60px 40px')
@section('hide-annee', 'true')

@section('extra-styles')
.titre {
    font-size: 20px;
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
    ATTESTATION DE CORRECTION DE {{ $diplome == 'Licence Professionnelle' ? 'RAPPORT' : 'MEMOIRE' }} DE FIN D'ETUDES
</div>

<div class="section">
    <div style="text-indent: 2em; text-align: justify; margin-bottom: 20px;">
        Je soussigné <strong>{{ $titre }} {{ $nom }} {{ $prenom }}</strong>, {{ $statut }} du jury ayant jugé le mémoire de <strong>{{ $nometu }} {{ $prenometu }}</strong>, candidat au Diplôme de « <strong>{{ $diplome }}</strong> » au Centre Autonome de Perfectionnement, atteste que les corrections exigées par le jury en sa séance du <strong>{{ $date_soutenance }}</strong> ont été prises en compte.
    </div>
    
    <div style="text-indent: 2em; text-align: justify; margin-bottom: 30px;">
        En foi de quoi, le présent quitus lui est délivré pour servir et valoir ce que de droit.
    </div>
    
    <div style="text-align: right; margin-bottom: 60px;">
        Fait à Abomey-Calavi, le {{ date('d/m/Y') }}.
    </div>

    <div style="font-weight: bold; text-align: right;">
        {{ $titre }} {{ $nom }} {{ $prenom }}
    </div>
</div>
@endsection
