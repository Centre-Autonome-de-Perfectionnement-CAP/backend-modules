@extends('core::pdfs.epac-base')

@section('title', 'Bulletins')

@section('hide-annee', 'true')

@section('content')
@foreach($bulletins as $bulletin)
<div class="main" style="position: relative; {{ !$loop->last ? 'page-break-after: always;' : '' }}">
    @if(!$loop->first)
    <div class="header-banner-container" style="margin-left: -25px; margin-right: -25px; margin-top: -10px; margin-bottom: 15px;">
        @php
            $headerImg = storage_path("images/epac_header_portrait.png");
            $headerBase64 = file_exists($headerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerImg)) : '';
        @endphp
        @if($headerBase64)
            <img src="{{ $headerBase64 }}" alt="En-tête officiel EPAC" style="width: 100%; height: auto; display: block;">
        @endif
    </div>
    @endif

    <div style="text-align: center; font-weight: bold; margin-bottom: 5px; font-size: 22px;">BULLETIN DE NOTES</div>
    <div style="text-align: center; font-weight: bold; margin-bottom: 12px; font-size: 14px;">Année Académique: {{ $bulletin['annee'] ?? '' }}</div>

    @php
        $photoBase64 = '';
        if(isset($bulletin['etudiant']->photo) && $bulletin['etudiant']->photo && file_exists($bulletin['etudiant']->photo)) {
            $photoPath = $bulletin['etudiant']->photo;
            $photoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($photoPath));
        } else {
            $avatarPath = (isset($bulletin['etudiant']->genre) && ucfirst($bulletin['etudiant']->genre) == 'Masculin')
                ? storage_path('avatars/homme.png')
                : storage_path('avatars/femme.png');
            $photoBase64 = file_exists($avatarPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($avatarPath)) : '';
        }
    @endphp

    <table style="width: 100%; border: none; margin-bottom: 10px; border-collapse: collapse;">
        <tr>
            <td style="width: 75px; vertical-align: middle; border: none; padding: 0;">
                @if(isset($photoBase64) && $photoBase64)
                    <img src="{{ $photoBase64 }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" alt="Photo étudiant">
                @endif
            </td>
            <td style="vertical-align: middle; border: none; padding: 0 10px;">
                <table style="width: 100%; text-align: left; font-size: 11px; border: none; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Nom :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->nom ?? '' }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Sexe :</span> <span style="font-weight: bolder;">{{ ucfirst($bulletin['etudiant']->genre ?? '') }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Cycle :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->filiere?->diplome?->nom ?? '' }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Prénoms :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->prenoms ?? '' }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Date de naissance :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->date_naissance ?? '' }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Filière :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->filiere?->nom ?? '' }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Lieu de naissance :</span> <span style="font-weight: bolder;">{{ $bulletin['etudiant']->lieu_de_naissance ?? '' }}</span></td>
                            <td style="border: none; padding: 2px 4px;" colspan="2"><span style="font-weight: normal;">Niveau :</span> <span style="font-weight: bolder;">Classes Préparatoires</span></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 80px; text-align: right; vertical-align: middle; border: none; padding: 0;">
                @if(isset($bulletin['qrcode']))
                    <img src="data:image/svg+xml;base64,{{ $bulletin['qrcode'] }}" width="75px" height="75px" class="qrcode" alt="Code QR">
                @endif
            </td>
        </tr>
    </table>
    
    <table class="corps" style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <thead style="font-weight: bold;">
            <tr>
                <th style="text-align:center; padding: 4px;">N°</th>
                <th style="text-align:center; padding: 4px;">Codes</th>
                <th style="text-align:left; padding: 4px;">Unités d'Enseignements</th>
                <th style="text-align:center; padding: 4px;">Crédits</th>
                <th style="text-align:center; padding: 4px;">Moyenne /100</th>
            </tr>
        </thead>
        <tbody style="width: 100%; font-weight: bold;">
            @if(isset($bulletin['bulletin_data'][0]))
            @php $num = 1; @endphp
            @foreach($bulletin['bulletin_data'][0] as $line)
            @if(is_array($line))
            <tr>
                <td style="text-align:center; padding: 3px;">{{ $num }}</td>
                <td style="text-align:center; padding: 3px;">{{ $line["code"] ?? '' }}</td>
                <td style="text-align:left; padding: 3px; font-weight: bold;">{{ $line['nom'] ?? '' }}</td>
                <td style="text-align:center; padding: 3px;">{{ $line['credit'] ?? '' }}</td>
                <td style="text-align:center; padding: 3px;">{{ str_replace('.', ',', number_format(($line['moyenne'] ?? 0) * 5, 2, '.', '')) }}</td>  
                @php $num++; @endphp
            </tr>
            @endif
            @endforeach
            @endif
        </tbody>
    </table>

    @if(isset($bulletin['bulletin_data'][0]))
    <table style="width: 100%; margin: 5px 0; font-size: 12px; border: none; border-collapse: collapse;">
        <tbody>
            <tr>
                <td style="font-weight: bolder; text-align: left; border: none; padding: 2px 0;">BILAN DE L'ANNÉE</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; text-align: left; margin-bottom: 8px; font-size: 11px; border: none; border-collapse: collapse;">
        <tbody>
            <tr style="text-align:left;">
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Moyenne : </span> <strong>{{ str_replace('.', ',', number_format((float)($bulletin['bulletin_data'][0]["moyenne"] ?? 0), 2, '.', '')) }}</strong></td>
            </tr>
            <tr style="text-align:left;">
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Grade ETCS : </span><strong>{{ $bulletin['bulletin_data'][0]["grade"] ?? '' }}</strong></td> 
            </tr>
            <tr style="text-align:left;">
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Décision du conseil : </span><strong> {{ $bulletin['bulletin_data'][0]["decision"] ?? '' }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div style="font-size: 10px; font-style: italic; margin-bottom: 10px;">
        <strong>NB:</strong> Ce relevé ne peut en aucun cas tenir lieu d'attestation de diplôme et n'est délivré qu'une seule fois.
    </div>

    <div style="width: 100%; text-align: center; font-size: 12px;">
        <p style="margin: 10px 0;">Fait à Abomey-Calavi le, {{ now()->format('d') }} {{ ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][now()->format('n')] }} {{ now()->format('Y') }}</p>
        <p style="margin: 5px 0;">Le Chef CAP</p>
        <div style="height: 50px;"></div>
        <p style="margin: 5px 0; text-decoration: underline; font-weight: bold;">{{ $bulletin['signataire']->nomination ?? '' }}</p>
    </div>
</div>
@endforeach
@endsection
