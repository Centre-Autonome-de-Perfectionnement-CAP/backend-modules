{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ $base }}/</loc></url>
    <url><loc>{{ $base }}/stage</loc></url>
    <url><loc>{{ $base }}/travaux</loc></url>
@foreach($works as $w)
    <url><loc>{{ $base }}/travaux/{{ $w->id }}</loc><lastmod>{{ $w->updated_at->toDateString() }}</lastmod></url>
@endforeach
</urlset>
