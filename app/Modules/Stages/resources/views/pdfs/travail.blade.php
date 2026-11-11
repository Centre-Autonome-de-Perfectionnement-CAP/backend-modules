{{--
    Attestation de travail - même famille que l'attestation de stage
    (titre Algerian à ruban, Pristina et Albertus Medium, tout en texte).
    Fonctions et tâches : une puce par ligne. Signature et cachet du Chef CAP
    apposés à la main sur l'original.
--}}
@extends('stages::pdfs.layout')

@section('title', 'Attestation de travail - ' . $d['nom_complet'])

@section('styles')
    .page { padding-top: 41mm; }
    .ref { font-size: 12pt; height: 7mm; }
    .ref .num { float: left; font-weight: bold; }
    .ref .date { float: right; }
    .titre { position: relative; height: 21mm; margin-top: 3mm; }
    .titre .t { position: absolute; left: 0; width: 160mm; top: 0; text-align: center; font-family: 'Algerian'; font-size: 29pt; line-height: 1; white-space: nowrap; transform: scale(1, 1.71); }
    .titre .ombre { color: #9a9a9a; left: 0.55mm; top: 0.55mm; }
    .titre .ruban { position: absolute; left: 50mm; top: 15.5mm; width: 60mm; }
    .pristina { font-family: 'Pristina'; font-size: 14pt; text-align: justify; line-height: 1.2; }
    .alb { font-family: 'Albertus'; font-size: 11.5pt; line-height: 1.3; text-align: justify; }
    .nom { font-size: 13pt; }
    .p { margin-bottom: 2.5mm; }
    .intro { text-indent: 12.5mm; }
    ul.puces { margin: 0 0 2.5mm 8mm; padding: 0; list-style: none; }
    ul.puces li { font-family: 'Albertus'; font-size: 11.5pt; line-height: 1.25; padding-left: 6mm; position: relative; margin-bottom: 0.8mm; }
    ul.puces li .b { position: absolute; left: 0; top: 0; }
    .foi { margin: 3mm 0 5mm 0; text-indent: 12.5mm; }
    .sign { font-family: 'Albertus'; font-style: italic; font-size: 14pt; page-break-inside: avoid; }
    .sign .titre-s { margin-left: 100mm; }
    .sign .nom-s { margin-left: 87.4mm; margin-top: 18mm; text-decoration: underline; }
    /* Contenu long : tout reste sur une page. */
    .dense .alb, .dense ul.puces li { font-size: 10.5pt; line-height: 1.2; }
    .dense .p, .dense ul.puces { margin-bottom: 1.8mm; }
    .dense .pristina { font-size: 13pt; }
    .dense .sign .nom-s { margin-top: 15mm; }
    .denser .alb, .denser ul.puces li { font-size: 9.5pt; line-height: 1.12; }
    .denser .p, .denser ul.puces { margin-bottom: 1.2mm; }
    .denser ul.puces li { margin-bottom: 0.3mm; }
    .denser .pristina { font-size: 12pt; }
    .denser .foi { margin: 2mm 0 3mm 0; }
    .denser .sign .nom-s { margin-top: 11mm; }
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
@if($apercu)<div class="apercu">APERÇU - NON ÉMISE</div>@endif
<div class="{{ $d['dense'] }}">
<div class="ref">
    {{-- Numéro écrit à la main sur l'original --}}
    <span class="num">N°{{ str_repeat("\u{00A0}", 26) }}/{{ $suffixe }}</span>
    <span class="date">{{ $ville }}, le {{ $date }}</span>
</div>

<div class="titre">
    <div class="t ombre">ATTESTATION DE TRAVAIL</div>
    <div class="t">ATTESTATION DE TRAVAIL</div>
    <img class="ruban" src="data:image/svg+xml;base64,{{ base64_encode($svgRuban) }}" alt="">
</div>

<p class="pristina p intro">Le Chef du Centre Autonome de Perfectionnement (C/CAP) de l’École Polytechnique d’Abomey-Calavi (EPAC), soussigné, atteste par la présente que&nbsp;:</p>

<p class="alb p">
    <span class="nom">{{ $d['civilite'] }} {{ $d['nom'] }} {{ $d['prenoms'] }}</span>@if($d['diplome']), titulaire du diplôme {{ $d['diplome'] }}@endif,
    a exercé{{ $d['nature'] ? ', ' . $d['nature'] . ',' : '' }} {{ count($d['fonctions']) > 1 ? 'les fonctions' : 'la fonction' }} de&nbsp;:
</p>
<ul class="puces">
    @foreach($d['fonctions'] as $f)<li><span class="b">-</span>{{ $f }}</li>@endforeach
</ul>

<p class="alb p">
    au sein du Centre Autonome de Perfectionnement (CAP) de l’EPAC/UAC, pendant la période allant du {{ $d['debut'] }} au {{ $d['fin'] }}.
</p>

@if(count($d['taches']))
<p class="alb p">Dans le cadre de ses fonctions, {{ $d['masculin'] ? 'il' : 'elle' }} a notamment été {{ $d['masculin'] ? 'chargé' : 'chargée' }} de&nbsp;:</p>
<ul class="puces">
    @foreach($d['taches'] as $t)<li><span class="b">-</span>{{ $t }}</li>@endforeach
</ul>
@endif

@if($d['appreciation'])
<p class="alb p">{{ $d['appreciation'] }}</p>
@endif

<p class="pristina foi">La présente attestation est délivrée pour servir et valoir ce que de droit.</p>

<div class="sign">
    <div class="titre-s">{{ $signataire_titre }}</div>
    <div class="nom-s">{{ $signataire_nom }}</div>
</div>
</div>
@endsection
