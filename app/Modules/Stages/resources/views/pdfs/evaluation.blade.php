{{--
    Bulletin d'évaluation de stage : tout en texte (seuls les logos de
    l'en-tête sont des images). Note retenue, note de chaque critère et
    appréciation du responsable ; ni noms ni commentaires des superviseurs.
--}}
@extends('stages::pdfs.layout')

@section('title', "Bulletin d'évaluation de stage " . $d['reference'])

@section('styles')
    .page { padding-top: 45mm; }
    body { font-size: 12pt; }
    .date { text-align: right; margin-bottom: 8mm; }
    .titre { text-align: center; font-weight: bold; font-size: 15pt; text-decoration: underline; margin-bottom: 8mm; }
    table.inf { width: 100%; border-collapse: collapse; margin-bottom: 7mm; font-size: 11pt; }
    table.inf td { border: 0.6pt solid #000; padding: 1.8mm 2.4mm; vertical-align: top; }
    table.inf td.k { width: 34%; background: #eef2f7; font-weight: bold; }
    table.notes { width: 100%; border-collapse: collapse; font-size: 11.5pt; }
    table.notes th, table.notes td { border: 0.6pt solid #000; padding: 2mm 2.6mm; }
    table.notes th { background: #00407e; color: #fff; text-align: left; }
    table.notes td.n { width: 30mm; text-align: center; }
    table.notes tr.total td { font-weight: bold; font-size: 13pt; background: #eef2f7; }
    .app { margin-top: 8mm; }
    .app .k { font-weight: bold; text-decoration: underline; margin-bottom: 2mm; }
    .app p { text-align: justify; line-height: 1.6; }
    .sign { margin-top: 12mm; margin-left: 90mm; width: 80mm; text-align: center; }
    .sign .t { font-weight: bold; }
@endsection

@section('content')
<div class="date">{{ $ville }}, le {{ $date }}</div>

<div class="titre">BULLETIN D'ÉVALUATION DE STAGE</div>

<table class="inf">
    <tr><td class="k">Stagiaire</td><td>{{ $d['nom_complet'] }}</td></tr>
    <tr><td class="k">Référence</td><td>{{ $d['reference'] }}</td></tr>
    @if ($d['formation'])
        <tr><td class="k">Formation</td><td>{{ $d['formation'] }}</td></tr>
    @endif
    <tr><td class="k">Période de stage</td><td>Du {{ $d['debut'] }} au {{ $d['fin'] }}</td></tr>
</table>

<table class="notes">
    <tr><th>Critère</th><th style="text-align:center">Note</th></tr>
    @foreach ($criteria as $c)
        <tr><td>{{ $c['label'] }}</td><td class="n">{{ $c['score'] }} / {{ $c['max'] }}</td></tr>
    @endforeach
    <tr class="total"><td>Note de stage</td><td class="n">{{ $final }} / 20</td></tr>
</table>

@if ($appreciation)
    <div class="app">
        <div class="k">Appréciation générale</div>
        <p>{{ $appreciation }}</p>
    </div>
@endif

<div class="sign">
    <div class="t">Le Responsable de division</div>
    @if ($validateur)
        <div style="margin-top: 14mm">{{ $validateur }}</div>
    @endif
</div>
@endsection
