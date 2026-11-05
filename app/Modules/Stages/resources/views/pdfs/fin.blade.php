{{--
    Note de désistement ou de révocation (fin anticipée du stage). Même mise
    en page que la note de service : tout en texte, seule la signature du
    responsable est une image.
--}}
@extends('stages::pdfs.layout')

@section('title', ($revocation ? 'Note de révocation ' : 'Note de désistement ') . $reference)

@section('styles')
    .page { padding-top: 43mm; }
    body { font-size: 12pt; }
    .ref { height: 7mm; margin-bottom: 9mm; }
    .ref .num { float: left; font-weight: bold; }
    .ref .date { float: right; }
    .titre { text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; margin-bottom: 8mm; }
    .objet { margin-bottom: 6mm; }
    .objet b { text-decoration: underline; }
    p.txt { text-align: justify; line-height: 1.6; margin-bottom: 4mm; }
    table.inf { width: 100%; border-collapse: collapse; margin: 1mm 0 5mm; font-size: 11pt; }
    table.inf td { border: 0.6pt solid #000; padding: 1.6mm 2.2mm; vertical-align: top; }
    table.inf td.k { width: 38%; background: #eef2f7; font-weight: bold; }
    .sign { margin-top: 10mm; margin-left: 80mm; width: 90mm; text-align: center; page-break-inside: avoid; }
    .sign .nw { white-space: nowrap; }
    .sign .t { font-weight: bold; line-height: 1.35; }
    .sign .img { margin: 2mm auto; }
    .sign .img img { display: block; margin: 0 auto; }
    .sign .n { font-weight: bold; text-decoration: underline; }
@endsection

@section('content')
<div class="ref">
    <span class="num">{{ $reference }}</span>
    <span class="date">{{ $ville }}, le {{ $date }}</span>
</div>

<div class="titre">{{ $revocation ? 'NOTE DE RÉVOCATION' : 'NOTE DE DÉSISTEMENT' }}</div>

<div class="objet"><b>OBJET</b> : {{ $revocation ? 'Révocation de stage académique' : 'Fin de stage académique pour désistement' }}</div>

<p class="txt">
    @if($revocation)
        Le stage académique de {{ $d['appel'] }} <b>{{ $d['nom'] }} {{ $d['prenoms'] }}</b>{{ $d['formation'] ? ', ' . ($d['masculin'] ? 'étudiant' : 'étudiante') . ' en ' . $d['formation'] : '' }}{{ $d['etablissement'] ? ' (' . $d['etablissement'] . ')' : '' }},
        au Centre Autonome de Perfectionnement (CAP), prend fin par décision de révocation.
    @elseif($constate)
        L'absence prolongée et non justifiée de {{ $d['appel'] }} <b>{{ $d['nom'] }} {{ $d['prenoms'] }}</b>{{ $d['formation'] ? ', ' . ($d['masculin'] ? 'étudiant' : 'étudiante') . ' en ' . $d['formation'] : '' }}{{ $d['etablissement'] ? ' (' . $d['etablissement'] . ')' : '' }},
        a été constatée. Son stage académique au Centre Autonome de Perfectionnement (CAP) est clos pour désistement.
    @else
        {{ $d['appel'] }} <b>{{ $d['nom'] }} {{ $d['prenoms'] }}</b>{{ $d['formation'] ? ', ' . ($d['masculin'] ? 'étudiant' : 'étudiante') . ' en ' . $d['formation'] : '' }}{{ $d['etablissement'] ? ' (' . $d['etablissement'] . ')' : '' }},
        a mis fin, de sa propre initiative, à son stage académique au Centre Autonome de Perfectionnement (CAP).
    @endif
</p>

<table class="inf">
    <tr><td class="k">Référence du stage</td><td>{{ $d['reference'] }}</td></tr>
    <tr><td class="k">Début du stage</td><td>{{ $d['debut'] }}</td></tr>
    <tr><td class="k">Fin prévue</td><td>{{ $d['fin_prevue'] }}</td></tr>
    <tr><td class="k">Dernier jour de présence</td><td>{{ $d['dernier'] ?? 'Aucune présence enregistrée' }}</td></tr>
    @if(!$revocation)
        <tr><td class="k">Type</td><td>{{ $constate ? 'Constaté par l\'administration' : 'À l\'initiative du stagiaire' }}@if($constate && $d['absences']) ({{ $d['absences'] }} {{ $d['absences'] > 1 ? 'jours ouvrés' : 'jour ouvré' }} d'absence)@endif</td></tr>
    @endif
    @if($d['motif_type'] || $d['motif'])
        <tr><td class="k">Motif</td><td>{{ $d['motif_type'] }}{{ $d['motif_type'] && $d['motif'] ? ' : ' : '' }}{{ $d['motif'] }}</td></tr>
    @endif
</table>

<p class="txt">
    Le stage est clos à compter du <b>{{ $d['effet'] }}</b> ; l'accès à l'espace stagiaire est désactivé.
    @if($revocation)
        Aucune attestation de stage ne sera délivrée.
    @endif
</p>

<p class="txt">
    Le Responsable de la Cellule informatique et le Secrétariat du CAP sont chargés,
    chacun en ce qui le concerne, de l'application de la présente note.
</p>

<div class="sign">
    @php([$titre, $sigle] = preg_match('/^(.*\S)\s*(\([^()]+\))[\s,.]*$/u', (string) $signataire_titre, $m) ? [$m[1], $m[2]] : [$signataire_titre, null])
    <div class="t">{{ $titre }}@if($sigle)<br><span class="nw">{{ $sigle }}</span>@endif</div>
    <div class="img" style="width: {{ $signature_box['w'] }}mm; height: {{ $signature_box['h'] }}mm;">
        @if($signature)
            <img src="{{ $signature['src'] }}" alt="" style="width: {{ $signature['width_mm'] }}mm; height: {{ $signature['height_mm'] }}mm; margin-top: {{ round(($signature_box['h'] - $signature['height_mm']) / 2, 2) }}mm;">
        @endif
    </div>
    @if($signataire_nom)
        <div class="n">{{ $signataire_nom }}</div>
    @endif
</div>
@endsection
