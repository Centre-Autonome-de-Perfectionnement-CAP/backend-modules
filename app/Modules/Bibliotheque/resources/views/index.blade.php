@extends('bibliotheque::layout')

@section('title', 'Travaux des stagiaires et étudiants')
@section('description', 'Rapports de fin de formation et mémoires réalisés au Centre Autonome de Perfectionnement (CAP) de l\'EPAC, Université d\'Abomey-Calavi.')
@section('head')
    <link rel="canonical" href="{{ url('/travaux') }}">
    @if(request()->hasAny(['q', 'annee', 'niveau', 'type']) || $works->currentPage() > 1)<meta name="robots" content="noindex, follow">@endif
@endsection

@section('styles')
    .filters { display: grid; grid-template-columns: minmax(0, 1.6fr) repeat(3, minmax(0, 1fr)) auto; gap: 10px; margin-bottom: 20px; }
    .filters select { padding-right: 6px; }
    .list { display: grid; gap: 12px; }
    .item h3 { margin: 6px 0 4px; font-size: 18px; line-height: 1.35; }
    .item h3 a { color: var(--ink); text-decoration: none; }
    .item h3 a:hover { color: var(--brand); text-decoration: underline; }
    .item p { margin: 6px 0 0; color: var(--muted); font-size: 15px; }
    .pager { display: flex; justify-content: space-between; margin-top: 18px; }
    @media (max-width: 760px) { .filters { grid-template-columns: 1fr 1fr; } .filters .q { grid-column: 1 / -1; } }
@endsection

@section('content')
    <h1>Travaux des stagiaires et étudiants</h1>
    <p class="lead">Rapports de fin de formation et mémoires réalisés au CAP-EPAC.</p>

    <form class="filters" method="get" action="/travaux" role="search">
        <input class="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Exemple : réseau, énergie solaire, gestion…" aria-label="Rechercher">
        <select name="type" aria-label="Type">
            <option value="">Tous les types</option>
            @foreach(\App\Modules\Bibliotheque\Models\LibraryWork::KINDS as $k => $l)
                <option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $l }}</option>
            @endforeach
        </select>
        <select name="niveau" aria-label="Niveau">
            <option value="">Tous les niveaux</option>
            @foreach($facets['cycles'] as $c)
                <option value="{{ $c }}" @selected(($filters['niveau'] ?? '') === $c)>{{ \App\Modules\Stages\Support\StudyLevel::CYCLES[$c][0] ?? $c }}</option>
            @endforeach
        </select>
        <select name="annee" aria-label="Année">
            <option value="">Toutes les années</option>
            @foreach($facets['years'] as $y)
                <option value="{{ $y }}" @selected((int) ($filters['annee'] ?? 0) === (int) $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Rechercher</button>
    </form>

    @if($works->isEmpty())
        <div class="card"><p class="muted" style="margin:0">Aucun travail ne correspond à votre recherche.</p></div>
    @else
        <p class="muted">{{ $works->total() }} travail(aux)</p>
        <div class="list">
            @foreach($works as $w)
                <article class="card item">
                    <span class="pill">{{ \App\Modules\Bibliotheque\Models\LibraryWork::KINDS[$w->kind] }}@if($w->year) · {{ $w->year }}@endif</span>
                    <h3><a href="/travaux/{{ $w->slug }}">{{ $w->title }}</a></h3>
                    <div class="muted">{{ $w->people->pluck('name')->implode(', ') }}@if($w->formation()) · {{ $w->formation() }}@endif</div>
                    @if($w->summary)<p>{{ \Illuminate\Support\Str::limit($w->summary, 220) }}</p>@endif
                </article>
            @endforeach
        </div>
        <div class="pager">
            <span>@if($works->previousPageUrl())<a class="btn ghost" href="{{ $works->previousPageUrl() }}">Précédents</a>@endif</span>
            <span>@if($works->nextPageUrl())<a class="btn ghost" href="{{ $works->nextPageUrl() }}">Suivants</a>@endif</span>
        </div>
    @endif
@endsection
