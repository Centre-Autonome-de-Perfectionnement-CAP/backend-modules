@extends('bibliotheque::layout')

@php
    use App\Modules\Bibliotheque\Models\LibraryPerson;
    use App\Modules\Bibliotheque\Models\LibraryWork;
    $q = $key ? '?cle=' . $key : '';
    $authors = $work->people->where('role', 'auteur');
    $others = $work->people->where('role', '!=', 'auteur');
    $kind = LibraryWork::KINDS[$work->kind];
    $institution = trim(($work->etablissement ?? '') . ($work->universite ? ', ' . $work->universite : ''), ', ');
    $citation = $authors->pluck('name')->implode(', ') . ($work->year ? " ({$work->year})" : '') . '. ' . $work->title . '. '
        . $kind . ($institution ? ', ' . $institution : '') . '.';
    $pdfUrl = $url . '/pdf';
@endphp

@section('title', $work->title)
@section('description', \Illuminate\Support\Str::limit($work->summary ?: $kind . ' - ' . $authors->pluck('name')->implode(', '), 300))
@section('head')
    @if($work->visibility === 'public')
        <link rel="canonical" href="{{ $url }}">
        {{-- Google Scholar --}}
        <meta name="citation_title" content="{{ $work->title }}">
        @foreach($authors as $a)<meta name="citation_author" content="{{ $a->name }}">@endforeach
        @if($work->year)<meta name="citation_publication_date" content="{{ $work->year }}">@endif
        @if($institution)<meta name="citation_dissertation_institution" content="{{ $institution }}">@endif
        @if($work->keywords)<meta name="citation_keywords" content="{{ implode('; ', $work->keywords) }}">@endif
        @if($work->downloadable)<meta name="citation_pdf_url" content="{{ $pdfUrl }}">@endif
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $work->title }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($work->summary, 200) }}">
        <meta property="og:url" content="{{ $url }}">
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org', '@type' => 'Thesis', 'name' => $work->title,
            'author' => $authors->map(fn ($a) => ['@type' => 'Person', 'name' => $a->name])->values()->all(),
            'datePublished' => $work->year ? (string) $work->year : null, 'abstract' => $work->summary,
            'keywords' => $work->keywords ? implode(', ', $work->keywords) : null, 'inLanguage' => 'fr',
            'sourceOrganization' => $institution ? ['@type' => 'EducationalOrganization', 'name' => $institution] : null,
            'url' => $url,
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @else
        <meta name="robots" content="noindex, nofollow">
    @endif
@endsection

@section('styles')
    .meta { display: grid; grid-template-columns: max-content 1fr; gap: 6px 16px; margin: 16px 0 0; font-size: 15px; }
    .meta dt { color: var(--muted); }
    .meta dd { margin: 0; font-weight: 500; }
    .kw { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0 4px; }
    .people { display: grid; gap: 10px; }
    .person { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; }
    .person .who { flex: 1; min-width: 200px; }
    .person .role { color: var(--muted); font-size: 14px; }
    details.contact { width: 100%; }
    details.contact summary { list-style: none; display: inline-flex; }
    details.contact summary::-webkit-details-marker { display: none; }
    details.contact form { display: grid; gap: 10px; margin-top: 12px; padding: 14px; background: var(--soft); border-radius: 12px; }
    .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .trap { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    .cite { background: var(--soft); border-radius: 12px; padding: 12px 14px; font-size: 15px; }
    @media (max-width: 600px) { .meta { grid-template-columns: 1fr; gap: 0; } .meta dd { margin-bottom: 8px; } .two { grid-template-columns: 1fr; } }
@endsection

@section('content')
    <p style="margin:0 0 14px"><a href="/travaux">← Tous les travaux</a></p>

    <article class="card">
        <span class="pill">{{ $kind }}@if($work->year) · {{ $work->year }}@endif</span>
        <h1 style="margin-top:10px">{{ $work->title }}</h1>
        <div class="muted">{{ $authors->pluck('name')->implode(', ') }}</div>

        <dl class="meta">
            @if($work->formation())<dt>Formation</dt><dd>{{ $work->formation() }}</dd>@endif
            @if($work->etablissement)<dt>Établissement</dt><dd>{{ $work->etablissement }}</dd>@endif
            @if($work->universite)<dt>Université</dt><dd>{{ $work->universite }}</dd>@endif
            @foreach($others as $p)<dt>{{ LibraryPerson::ROLES[$p->role] }}</dt><dd>{{ $p->name }}@if($p->fonction), {{ $p->fonction }}@endif</dd>@endforeach
        </dl>

        @if($work->summary)
            <h2>Résumé</h2>
            <p style="white-space: pre-line; margin: 0">{{ $work->summary }}</p>
        @endif
        @if($work->keywords)
            <div class="kw">@foreach($work->keywords as $k)<span class="pill">{{ $k }}</span>@endforeach</div>
        @endif

        <div class="actions">
            @if($work->downloadable)
                <a class="btn" href="{{ $pdfUrl . $q }}" target="_blank" rel="noopener">Lire le document (PDF)</a>
            @else
                <span class="muted">Le document complet n'est pas en accès libre : vous pouvez le demander à ses auteurs ci-dessous.</span>
            @endif
        </div>
        <p class="muted" style="margin:8px 0 0">{{ LibraryWork::LICENSES[$work->license] ?? '' }}</p>
    </article>

    <h2>Contacter les auteurs et l'encadrement</h2>
    @if(session('sent'))<div class="alert ok">{{ session('sent') }}</div>@endif
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <div class="card people">
        @foreach($work->people as $p)
            <div class="person">
                <div class="who"><strong>{{ $p->name }}</strong><div class="role">{{ LibraryPerson::ROLES[$p->role] }}@if($p->fonction) · {{ $p->fonction }}@endif</div></div>
                @if($p->reachable())
                    <details class="contact" @if(old('person_id') == $p->id && $errors->any()) open @endif>
                        <summary class="btn ghost">Contacter</summary>
                        <form method="post" action="{{ $url . '/contact' . $q }}">
                            @csrf
                            <input type="hidden" name="person_id" value="{{ $p->id }}">
                            <div class="trap" aria-hidden="true"><label>Site<input type="text" name="site" tabindex="-1" autocomplete="off"></label></div>
                            <div class="two">
                                <div><label for="nom{{ $p->id }}">Votre nom</label><input id="nom{{ $p->id }}" name="nom" required maxlength="120" value="{{ old('person_id') == $p->id ? old('nom') : '' }}" placeholder="Exemple : Jean AGOSSA"></div>
                                <div><label for="email{{ $p->id }}">Votre email</label><input id="email{{ $p->id }}" type="email" name="email" required maxlength="254" value="{{ old('person_id') == $p->id ? old('email') : '' }}" placeholder="Exemple : jean.agossa@gmail.com"></div>
                            </div>
                            <div><label for="tel{{ $p->id }}">Votre téléphone (facultatif)</label><input id="tel{{ $p->id }}" name="telephone" maxlength="30" inputmode="tel" value="{{ old('person_id') == $p->id ? old('telephone') : '' }}" placeholder="Exemple : +229 01 97 00 00 00"></div>
                            <div><label for="msg{{ $p->id }}">Votre message</label><textarea id="msg{{ $p->id }}" name="message" rows="4" required minlength="20" maxlength="2000">{{ old('person_id') == $p->id ? old('message') : '' }}</textarea></div>
                            <p class="muted" style="margin:0">{{ $p->name }} reçoit votre message et vous répond directement. Ses coordonnées ne sont pas affichées.</p>
                            <div><button class="btn" type="submit">Envoyer le message</button></div>
                        </form>
                    </details>
                @endif
            </div>
        @endforeach
    </div>

    <h2>Citer ce travail</h2>
    <div class="cite">{{ $citation }} {{ $url }}</div>
@endsection
