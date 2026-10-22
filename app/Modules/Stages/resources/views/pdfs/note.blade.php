{{--
    Note de service (initiale ou rectificative) - style des documents du
    CAP (en-tête et pied de page officiels), adapté du modèle de note
    d'admission en stage. Tout en texte. Pas de signature manuscrite : la note
    porte le cachet numérique du système (code + QR de vérification).
    Les horaires et l'usage de l'espace stagiaire figurent au règlement intérieur.
--}}
@extends('stages::pdfs.layout')

@section('title', ($rectif ? 'Note de service rectificative ' : 'Note de service ') . $numero)

@section('styles')
    .page { padding-top: 43mm; }
    body { font-size: 12pt; }
    .date { text-align: right; margin-bottom: 9mm; }
    .titre { text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; line-height: 1.4; margin-bottom: 8mm; }
    .objet { margin-bottom: 6mm; }
    .objet b { text-decoration: underline; }
    p.txt { text-align: justify; line-height: 1.6; margin-bottom: 4mm; }
    table.chg { width: 100%; border-collapse: collapse; margin: 1mm 0 5mm; font-size: 11pt; }
    table.chg th, table.chg td { border: 0.6pt solid #000; padding: 1.6mm 2.2mm; text-align: left; vertical-align: top; }
    table.chg th { background: #eef2f7; }
    /* Cachet numérique (vert CAP, style tampon incliné) */
    .cachet { margin-top: 10mm; margin-left: 62mm; width: 104mm; color: #326761;
              border: 0.9mm solid #326761; border-radius: 1.6mm; padding: 0.7mm; transform: rotate(-2.5deg); }
    .cachet .in { border: 0.25mm solid #326761; border-radius: 1.1mm; padding: 2mm 3mm 2.6mm; }
    .cachet .t { text-align: center; font-weight: bold; font-size: 12.5pt; letter-spacing: 1.6pt; }
    .cachet .s { text-align: center; font-weight: bold; font-size: 7.6pt; letter-spacing: 0.5pt; margin-top: 0.6mm; }
    .cachet .r { border-top: 0.25mm solid #326761; margin: 1.8mm 0 2mm; }
    .cachet table { width: 100%; border-collapse: collapse; }
    .cachet td { vertical-align: middle; padding: 0; }
    .cachet td.qr { width: 23mm; }
    .cachet td.qr img { width: 21mm; height: 21mm; }
    .cachet .l { font-size: 9pt; line-height: 1.55; }
    .mention { margin-top: 9mm; text-align: center; font-size: 9.5pt; font-style: italic; color: #475569; }
    .cachet .code { font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; letter-spacing: 0.6pt; }
@endsection

@section('content')
<div class="date">{{ $ville }}, le {{ $date }}</div>

<div class="titre">NOTE DE SERVICE{{ $rectif ? ' RECTIFICATIVE' : '' }}<br>N° {{ $numero }}/{{ $suffixe }}</div>

@if(!$rectif)
    <div class="objet"><b>OBJET</b> : Admission en stage académique</div>

    <p class="txt">
        {{ $d['appel'] }} <b>{{ $d['nom'] }} {{ $d['prenoms'] }}</b>, {{ $d['masculin'] ? 'étudiant' : 'étudiante' }} en {{ $d['formation'] }}
        ({{ $d['etablissement'] }}), est {{ $d['masculin'] ? 'admis' : 'admise' }} en stage académique pour une période
        <b>{{ str_starts_with($d['duree'], 'un') ? "d'" : 'de ' }}{{ $d['duree'] }}</b> allant du <b>{{ $d['debut'] }} au {{ $d['fin'] }}</b>
        au {{ $d['service'] ?: 'Centre' }} du Centre Autonome de Perfectionnement (CAP){{ $d['lieu'] && $d['lieu'] !== 'CAP-EPAC' ? ', à ' . $d['lieu'] : '' }}.
    </p>

    <p class="txt">À la fin du stage, {{ $d['masculin'] ? 'il' : 'elle' }} devra déposer un rapport de stage final.</p>
@else
    <div class="objet"><b>OBJET</b> : Rectification de la note de service N° {{ $initial['number'] ?? '' }}/{{ $suffixe }}{{ $initial ? ' du ' . $initial['date'] : '' }}</div>

    <p class="txt">
        Les dispositions de la note de service N° {{ $initial['number'] ?? '' }}/{{ $suffixe }} portant admission en stage académique de
        {{ $d['appel'] }} <b>{{ $d['nom'] }} {{ $d['prenoms'] }}</b> sont modifiées comme suit :
    </p>

    @if(count($changes))
        <table class="chg">
            <tr><th style="width: 22%;"></th><th>Au lieu de</th><th>Lire</th></tr>
            @foreach($changes as [$label, $avant, $apres])
                <tr><td><b>{{ $label }}</b></td><td>{{ $avant }}</td><td><b>{{ $apres }}</b></td></tr>
            @endforeach
        </table>
    @endif

    @if($note->motif)
        <p class="txt"><b>Motif</b> : {{ $note->motif }}</p>
    @endif

    <p class="txt">Le reste sans changement.</p>
@endif

<p class="txt">
    Le Chef de la Division Formation Continue et Perfectionnement et le Secrétariat du CAP sont chargés,
    chacun en ce qui le concerne, de l'application de la présente note de service.
</p>

<div class="cachet"><div class="in">
    <div class="t">CACHET NUMÉRIQUE</div>
    <div class="s">CAP-EPAC · SYSTÈME DE GESTION DES STAGES</div>
    <div class="r"></div>
    <table><tr>
        <td class="qr"><img src="{{ $cachet['qr'] }}" alt=""></td>
        <td class="l">
            Note de service{{ $rectif ? ' rectificative' : '' }} n° <b>{{ $numero }}</b><br>
            Émise le {{ $cachet['date'] }} à {{ $cachet['heure'] }}<br>
            Code de vérification : <span class="code">{{ $cachet['code'] }}</span>
        </td>
    </tr></table>
</div></div>

<div class="mention">Document émis par le système de gestion des stages du CAP-EPAC.</div>

@endsection
