<div style="width: 100%; margin: 0 0 10px 0; padding: 0; text-align: center;">
    @php
        $headerLandscape = storage_path("images/epac_header_landscape.png");
        $headerBase64 = file_exists($headerLandscape) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerLandscape)) : '';
    @endphp
    @if($headerBase64)
        <img src="{{ $headerBase64 }}" style="width: 100%; height: auto; display: block;" alt="En-tête officiel EPAC - CAP">
    @endif
</div>

