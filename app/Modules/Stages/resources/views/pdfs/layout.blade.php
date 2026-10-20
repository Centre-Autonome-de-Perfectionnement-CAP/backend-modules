{{--
    Mise en page des documents du module Stages (attestation, note de
    service) : en-tête et pied de page au style officiel EPAC / CAP (celui des
    bannières de main), refaits en texte. Seuls les logos sont des images ;
    les bandeaux bleus sont dessinés (SVG).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>@yield('title')</title>
<style>
    @font-face { font-family: 'Algerian'; font-weight: normal; src: url("{{ storage_path('fonts/ALGERIA.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Pristina'; font-weight: normal; src: url("{{ storage_path('fonts/PRISTINA.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Pristina'; font-weight: bold; src: url("{{ storage_path('fonts/PRISTINA.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Albertus'; font-weight: normal; src: url("{{ storage_path('fonts/albr55w.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Albertus'; font-weight: bold; src: url("{{ storage_path('fonts/albr55w.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Albertus'; font-weight: normal; font-style: italic; src: url("{{ storage_path('fonts/albr55w.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Albertus'; font-weight: bold; font-style: italic; src: url("{{ storage_path('fonts/albr55w.ttf') }}") format('truetype'); }

    @page { margin: 0; size: A4 portrait; }
    * { margin: 0; padding: 0; }
    body { font-family: Helvetica, Arial, sans-serif; color: #000; font-size: 12pt; }

    /* En-tête : 37,3 mm, comme la bannière officielle */
    .hd { position: fixed; top: 0; left: 0; width: 210mm; height: 37.3mm; }
    .hd .bars { position: absolute; top: 0; left: 0; width: 210mm; height: 37.3mm; }
    .hd .logo-epac { position: absolute; left: 8.9mm; top: 7.2mm; width: 23.2mm; }
    .hd .logo-cap { position: absolute; left: 174.9mm; top: 4.2mm; width: 28.3mm; }
    .hd .ln { position: absolute; left: 33mm; width: 141mm; text-align: center; white-space: nowrap; line-height: 1; }
    .hd .l1 { top: 5.1mm; font-size: 10.2pt; letter-spacing: 0.3pt; }
    .hd .l2 { top: 11.2mm; font-size: 9.5pt; }
    .hd .l3 { top: 16.9mm; font-size: 10.1pt; font-weight: bold; color: #4a8d2f; }
    .hd .l4 { top: 22.0mm; font-size: 10.2pt; font-weight: bold; color: #00407e; transform: scale(1, 1.12); }
    .hd .l5 { top: 26.9mm; font-size: 8.3pt; font-weight: bold; color: #00407e; }

    /* Pied de page : 21,1 mm */
    .ft { position: fixed; bottom: 0; left: 0; width: 210mm; height: 21.1mm; }
    .ft .bars { position: absolute; top: 0; left: 0; width: 210mm; height: 21.1mm; }
    .ft .txt { position: absolute; left: 0; width: 210mm; top: 11.6mm; text-align: center; color: #fff; font-size: 12.5pt; font-weight: bold; line-height: 1; white-space: nowrap; }

    .page { padding: 40mm 25mm 26mm 25mm; }
    @yield('styles')
</style>
</head>
<body>
@php
    $svgHead = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2480 440" width="2480" height="440">'
        . '<rect x="171" y="0" width="142" height="59" fill="#00407e"/>'
        . '<rect x="1087" y="107" width="101" height="8" fill="#4a8d2f"/><rect x="1188" y="107" width="102" height="8" fill="#e4dc06"/><rect x="1290" y="107" width="102" height="8" fill="#ff0000"/>'
        . '<rect x="856" y="179" width="255" height="8" fill="#4a8d2f"/><rect x="1111" y="179" width="255" height="8" fill="#e4dc06"/><rect x="1366" y="179" width="256" height="8" fill="#ff0000"/>'
        . '<rect x="0" y="411" width="1134" height="29" fill="#00407e"/>'
        . '<polygon points="1320,366 1348,366 1247,440 1219,440" fill="#00407e"/>'
        . '<polygon points="1375,366 2480,366 2480,440 1274,440" fill="#00407e"/>'
        . '</svg>';
    $svgFoot = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2480 249" width="2480" height="249">'
        . '<polygon points="0,3 511,3 614,63 1580,63 1619,92 596,92 500,33 0,33" fill="#00407e"/>'
        . '<polygon points="0,55 478,55 576,113 0,113" fill="#00407e"/>'
        . '<rect x="0" y="112" width="2480" height="137" fill="#00407e"/>'
        . '</svg>';
    $img = fn (string $file) => 'data:image/png;base64,' . base64_encode((string) @file_get_contents(base_path('app/Modules/Stages/resources/pdf/' . $file)));
@endphp
<div class="hd">
    <img class="bars" src="data:image/svg+xml;base64,{{ base64_encode($svgHead) }}" alt="">
    <img class="logo-epac" src="{{ $img('epac.png') }}" alt="EPAC">
    <img class="logo-cap" src="{{ $img('cap.png') }}" alt="CAP">
    <div class="ln l1">REPUBLIQUE DU BENIN</div>
    <div class="ln l2">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE</div>
    <div class="ln l3">UNIVERSITE D'ABOMEY-CALAVI</div>
    <div class="ln l4">ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</div>
    <div class="ln l5">CENTRE AUTONOME DE PERFECTIONNEMENT</div>
</div>
<div class="ft">
    <img class="bars" src="data:image/svg+xml;base64,{{ base64_encode($svgFoot) }}" alt="">
    <div class="txt">{{ $footer }}</div>
</div>
<div class="page">
@yield('content')
</div>
</body>
</html>
