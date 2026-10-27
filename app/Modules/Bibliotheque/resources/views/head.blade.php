{{-- Balises écrites par le serveur dans la page du site vitrine (référencement). --}}
<title>{{ $title }} - CAP-EPAC</title>
@if($description)<meta name="description" content="{{ $description }}">@endif
@if($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
@if($robots)<meta name="robots" content="{{ $robots }}">@endif
@if($work)
<meta name="citation_title" content="{{ $work['title'] }}">
@foreach($work['authors'] as $a)<meta name="citation_author" content="{{ $a }}">
@endforeach
@if($work['year'])<meta name="citation_publication_date" content="{{ $work['year'] }}">@endif
@if($work['institution'])<meta name="citation_dissertation_institution" content="{{ $work['institution'] }}">@endif
@if($work['keywords'])<meta name="citation_keywords" content="{{ implode('; ', $work['keywords']) }}">@endif
@if($work['pdf_url'])<meta name="citation_pdf_url" content="{{ $work['pdf_url'] }}">@endif
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $work['title'] }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit((string) $work['summary'], 200) }}">
<meta property="og:url" content="{{ $work['url'] }}">
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Thesis', 'name' => $work['title'],
    'author' => $work['authors']->map(fn ($a) => ['@type' => 'Person', 'name' => $a])->all(),
    'datePublished' => $work['year'] ? (string) $work['year'] : null, 'abstract' => $work['summary'],
    'keywords' => $work['keywords'] ? implode(', ', $work['keywords']) : null, 'inLanguage' => 'fr',
    'sourceOrganization' => $work['institution'] ? ['@type' => 'EducationalOrganization', 'name' => $work['institution']] : null,
    'url' => $work['url'],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif
