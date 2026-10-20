{{--
    Attestation de stage — reproduit le modèle Word du CAP
    (Attestation_Stage_Nom_Prénoms.docx) : polices Algerian, Pristina et
    Albertus Medium, tout en texte. En-tête et pied de page : style officiel.
    Signature et cachet : apposés à la main sur l'original.
--}}
@extends('stages::pdfs.layout')

@section('title', 'Attestation de stage — ' . $d['nom_complet'])

@section('styles')
    .page { padding-top: 41mm; }
    .ref { font-size: 12pt; height: 7mm; }
    .ref .num { float: left; font-weight: bold; }
    .ref .date { float: right; }
    .titre { position: relative; height: 26mm; margin-top: 5mm; }
    .titre .t { position: absolute; left: 0; width: 160mm; top: 0; text-align: center; font-family: 'Algerian'; font-size: 32.3pt; line-height: 1; white-space: nowrap; transform: scale(1, 1.71); }
    .titre .ombre { color: #9a9a9a; left: 0.55mm; top: 0.55mm; }
    .titre .ruban { position: absolute; left: 53.7mm; top: 17mm; width: 52.6mm; }
    .pristina { font-family: 'Pristina'; font-size: 16pt; text-align: justify; line-height: 1.25; }
    .alb { font-family: 'Albertus'; }
    .p1 { text-indent: 12.5mm; margin-bottom: 4mm; }
    .civ { font-size: 14pt; line-height: 1.5; margin-bottom: 3mm; }
    .civ .nom { font-size: 18pt; }
    .naiss { font-size: 16pt; line-height: 1.5; margin-bottom: 3mm; }
    .sc { font-size: 12.5pt; text-transform: uppercase; }
    .corps { font-size: 14pt; line-height: 1.5; text-align: justify; margin-bottom: 9mm; }
    .foi { margin-bottom: 9mm; text-indent: 12.5mm; }
    .sign { font-family: 'Albertus'; font-style: italic; font-size: 14pt; }
    .sign .titre-s { margin-left: 100mm; }
    .sign .nom-s { margin-left: 87.4mm; margin-top: 30mm; text-decoration: underline; }
    s { text-decoration: line-through; }
    .apercu { position: fixed; top: 130mm; left: 15mm; width: 180mm; text-align: center; font-size: 34pt; font-weight: bold; color: #94a3b8; opacity: 0.22; z-index: -1; transform: rotate(-30deg); }
@endsection

@section('content')
@php
    $svgRuban = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-6 -6 1257 92" width="1257" height="92">'
        . '<polygon points="0,17 310,11 310,48 465,48 465,55 0,65 155,40" fill="#fff" stroke="#000" stroke-width="7" stroke-linejoin="round"/>'
        . '<polygon points="1245,17 935,11 935,48 780,48 780,55 1245,65 1090,40" fill="#fff" stroke="#000" stroke-width="7" stroke-linejoin="round"/>'
        . '<rect x="310" y="3" width="625" height="45" rx="4" fill="#fff" stroke="#000" stroke-width="7"/>'
        . '</svg>';
@endphp
@if($apercu)<div class="apercu">APERÇU — NON VALIDÉE</div>@endif
<div class="ref">
    <span class="num">N° {{ $numero ? $numero : str_repeat("\u{00A0}", 18) }}/{{ $suffixe }}</span>
    <span class="date">{{ $ville }}, le {{ $date }}</span>
</div>

<div class="titre">
    <div class="t ombre">ATTESTATION DE STAGE</div>
    <div class="t">ATTESTATION DE STAGE</div>
    <img class="ruban" src="data:image/svg+xml;base64,{{ base64_encode($svgRuban) }}" alt="">
</div>

<p class="pristina p1">Le Chef du Centre Autonome de Perfectionnement (CAP) de l’EPAC, atteste par la présente, que :</p>

<p class="alb civ">
    @if($d['masculin'])<s>Mlle</s> / Mr @else {{ $d['civilite'] === 'Mme' ? 'Mme' : 'Mlle' }} / <s>Mr</s> @endif :
    <span class="nom">{{ $d['nom'] }} {{ $d['prenoms'] }}</span>
</p>

<p class="alb naiss">
    {{ $d['masculin'] ? 'Né' : 'Née' }} le {{ $d['date_naissance_long'] }} à {{ $d['lieu_naissance'] }}@if($d['pays_naissance']) ({!! $d['pays_naissance_html'] !!})@endif
</p>

<p class="alb corps">
    A effectué du {{ $d['debut_long'] }} au {{ $d['fin_long'] }}, un stage {{ $d['type_stage'] }} au sein {{ $d['structure'] }}.
</p>

<p class="pristina foi">En foi de quoi, la présente Attestation est délivrée pour servir et valoir ce que de droit.&nbsp;-</p>

<div class="sign">
    <div class="titre-s">{{ $signataire_titre }}</div>
    <div class="nom-s">{{ $signataire_nom }}</div>
</div>
@endsection
