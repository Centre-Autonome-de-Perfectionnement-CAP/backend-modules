@extends('core::pdfs.epac-base')

@section('title', 'Bulletins')

@section('custom-header')
<div class="header">
    @php
        $epacLogo = public_path("assets/epac.png");
        $capLogo = public_path("assets/cap.png");
    @endphp
    @if(file_exists($epacLogo) && filesize($epacLogo) > 0)
    <img src='{{ $epacLogo }}' alt="logo-epac" class="logo-header epac">
    @endif
    @if(file_exists($capLogo) && filesize($capLogo) > 0)
    <img src='{{ $capLogo }}' alt="logo-cap"  class="logo-header">
    @endif
    <h3 style="margin:0px">Université d'Abomey-Calavi</h3>
    @php
        $bannerImg = public_path("assets/banner.png");
        $hasBanner = file_exists($bannerImg) && filesize($bannerImg) > 0;
    @endphp
    @if($hasBanner)
    <img src='{{ $bannerImg }}' alt="header-separator-img" style="margin:0px">
    @else
    <hr style="margin: 5px 0;">
    @endif
    <h2 style="margin:0">Ecole Polytechnique d'Abomey-Calavi</h2>
    @if($hasBanner)
    <img src='{{ $bannerImg }}' alt="header-separator-img" style="margin:0px">
    @else
    <hr style="margin: 5px 0;">
    @endif
    <h1 style="margin:0;">Centre Autonome de Perfectionnement</h1>
    <p>
        01 BP 2009 COTONOU - TEl. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj
    </p>
    <hr>
</div>
@endsection

@section('content')
@foreach($bulletins as $bulletin)
<div class="main" style="position: relative; {{ !$loop->last ? 'page-break-after: always;' : '' }}">
    @if(!$loop->first)
    <div class="header" style="margin-bottom: 20px;">
        @php
            $epacLogo = public_path("assets/epac.png");
            $capLogo = public_path("assets/cap.png");
            $epacBase64 = file_exists($epacLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($epacLogo)) : '';
            $capBase64 = file_exists($capLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($capLogo)) : '';
        @endphp
        @if($epacBase64)
        <img src='{{ $epacBase64 }}' alt="logo-epac" style="position: absolute; left: 0; top: 0; height: 100px;">
        @endif
        @if($capBase64)
        <img src='{{ $capBase64 }}' alt="logo-cap" style="position: absolute; right: 0; top: 0; height: 100px;">
        @endif
        <h3 style="margin:0px">Université d'Abomey-Calavi</h3>
        <hr style="margin: 5px auto; width: 80px;">
        <h2 style="margin:0">Ecole Polytechnique d'Abomey-Calavi</h2>
        <hr style="margin: 5px auto; width: 150px;">
        <h1 style="margin:0;">Centre Autonome de Perfectionnement</h1>
        <p>01 BP 2009 COTONOU - TEl. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj</p>
        <hr>
    </div>
    @endif
    <div style="text-align: center; font-weight: bold; margin-bottom: 7px; font-size: 25px;">BULLETIN DE NOTES</div>
    <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 15px;">Année Académique: {{ $bulletin['annee'] ?? '' }}</div>
    @if(isset($bulletin['qrcode']))
    <div style="position: absolute; top: {{ !$loop->first ? '120px' : '-2px' }}; right: 0;">
        <img src="data:image/svg+xml;base64,{{ $bulletin['qrcode'] }}" width="100px" height="100px" class="qrcode" alt="Code QR">
    </div>
    @endif
    
    <table style="width: 100%; text-align: left; margin-bottom: 10px; font-size: 11px; border: none; margin-top: 10px; border-collapse: collapse;">
        <tbody>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Matricule :</span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->matricule ?? '' }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Sexe :</span> <span style="font-weight: bolder;"> {{ ucfirst($bulletin['etudiant']->genre ?? '') }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Cycle :</span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->filiere?->diplome?->nom ?? '' }} </span></td>
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Nom :</span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->nom ?? '' }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Date de naissance :</span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->date_naissance ?? '' }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Filière :</span><span style="font-weight: bolder;"> {{ $bulletin['etudiant']->filiere?->nom ?? '' }} </span></td>
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Prénoms :</span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->prenoms ?? '' }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Lieu de naissance : </span> <span style="font-weight: bolder;"> {{ $bulletin['etudiant']->lieu_de_naissance ?? '' }}</span></td>
                <td style="border: none;"><span style="font-weight: normal;">Niveau : </span> <span style="font-weight: bolder;"> Classes Préparatoires </span></td>
            </tr>
        </tbody>
    </table>
    
    <table class="corps">
        <thead style="font-weight: bold;">
            <tr>
                <td>N°</td>
                <td>Codes</td>
                <td>Unités d'Enseignements</td>
                <td>Crédits</td>
                <td>Moyenne /100</td>
                <td>Freq*</td>
                <td>Etat UE</td>
            </tr>
        </thead>
        <tbody style="width: 100%; font-weight: bold;">
            @if(isset($bulletin['bulletin_data'][0]))
            @php $num = 1; @endphp
            @foreach($bulletin['bulletin_data'][0] as $line)
            @if(is_array($line))
            <tr>
                <td>{{ $num }}</td>
                <td>{{ $line["code"] ?? '' }}</td>
                <td style="font-weight: bold;">{{ $line['nom'] ?? '' }}</td>
                <td>{{ $line['credit'] ?? '' }}</td>
                <td>{{ ($line['moyenne'] ?? 0) * 5 }}</td>  
                <td>{{ $line['frequence'] ?? '' }}</td>
                <td>{{ $line['etat'] ?? '' }}</td>
                @php $num++; @endphp
            </tr>
            @endif
            @endforeach
            @endif
        </tbody>
    </table>

    @if(isset($bulletin['bulletin_data'][0]))
    <table style="width: 100%; margin: 7px; text-align: center; font-size: 13px; border: none; border-collapse: collapse;">
        <tbody>
            <tr>
                <td colspan="3" style="font-weight: bolder; text-align: center; border: none;">BILAN DE L'ANNÉE</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; text-align: left; padding-left: 10px; margin-bottom: 10px; font-size: 12px; border: none; border-collapse: collapse;">
        <tbody>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Nombre de UE validé : </span><strong> {{ $bulletin['bulletin_data'][0]["nombre_ue_valide"] ?? 0 }}/{{ $bulletin['bulletin_data'][0]["nombre_ue"] ?? 0 }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Crédits obtenus : </span> <strong>{{ $bulletin['bulletin_data'][0]["nombre_credit_obtenu"] ?? 0 }}/{{ $bulletin['bulletin_data'][0]["nombre_credit_total"] ?? 0 }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Moyenne : </span> <strong>{{ $bulletin['bulletin_data'][0]["moyenne"] ?? 0 }}</strong></td>
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Nombre de UE cumulé : </span> <strong>{{ $bulletin['bulletin_data'][0]["nombre_ue_valide"] ?? 0 }}/{{ $bulletin['bulletin_data'][0]["nombre_ue"] ?? 0 }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Total crédits cumulés : </span> <strong>{{ $bulletin['bulletin_data'][0]["nombre_credit_obtenu"] ?? 0 }}/{{ $bulletin['bulletin_data'][0]["nombre_credit_total"] ?? 0 }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Grade ETCS : </span><strong>{{ $bulletin['bulletin_data'][0]["grade"] ?? '' }}</strong></td> 
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">% Crédits requis : </span><strong> 80%</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">% Crédits cumulés : </span> <strong>{{ isset($bulletin['bulletin_data'][0]) ? round((float)($bulletin['bulletin_data'][0]["nombre_credit_obtenu"] *100 )/ (float)($bulletin['bulletin_data'][0]["nombre_credit_total"]), 2) : 0 }}%</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Décision du conseil : </span><strong> {{ $bulletin['bulletin_data'][0]["decision"] ?? '' }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif
    <br>
    <div style="width: 100%; text-align: center; font-size: 14px;">
        <p style="margin: 5px 0;">Fait à Abomey-Calavi le {{ now()->format('d') }} {{ ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][now()->format('n')] }} {{ now()->format('Y') }}</p>
        <p style="margin: 5px 0;">Le Chef CAP</p>
        <div style="height: 60px;"></div>
        <p style="margin: 5px 0; text-decoration: underline;">{{ $bulletin['signataire']->nomination ?? '' }}</p>
    </div>
</div>
@endforeach
@endsection

@section('footer-text')
    <div style="text-align: center;">
        <span style="margin-left: 200px;"><i>*Nombre de composition dans l'UE</i></span><br><br>
        <span style="font-weight: bold; margin-left: 100px;">NB: </span> Ce relevé ne peut en aucun cas tenir lieu d'attestation de diplôme et n'est délivré qu'une seule fois
    </div>
@endsection