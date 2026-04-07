@extends('core::pdfs.epac-base')

@section('title', 'Bulletin')

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
<div class="main" style="position: relative;">
    <div style="position: absolute; top: 0px; left: 0;">
        @if(isset($etudiant->photo) && $etudiant->photo && file_exists($etudiant->photo))
            <img src="{{ $etudiant->photo }}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;" alt="Photo étudiant">
        @else
            @if(ucfirst($etudiant->genre) == 'Masculin')
                <img src="{{ storage_path('avatars/homme.png') }}" style="width: 80px; height: 80px;" alt="Avatar homme">
            @else
                <img src="{{ storage_path('avatars/femme.png') }}" style="width: 80px; height: 80px;" alt="Avatar femme">
            @endif
        @endif
    </div>
    <div style="text-align: center; font-weight: bold; margin-bottom: 7px; font-size: 25px;">BULLETIN DE NOTES</div>
    <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 15px;">Année Académique: {{ $annee }}</div>
    <div style="position: absolute; top: -5px; right: 0;">
        <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="100px" height="100px" class="qrcode" alt="Code QR">
    </div>
    
    <table style="width: 100%; text-align: left; margin-bottom: 10px; font-size: 11px; border: none; margin-top: 10px; border-collapse: collapse;">
        <tbody>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Nom :</span> <span style="font-weight: bolder;"> {{ $etudiant->nom }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Sexe :</span> <span style="font-weight: bolder;"> {{ ucfirst($etudiant->genre) }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Cycle :</span> <span style="font-weight: bolder;"> {{ $etudiant->filiere?->diplome?->nom }} </span></td>
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Prénoms :</span> <span style="font-weight: bolder;"> {{ $etudiant->prenoms }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Date de naissance :</span> <span style="font-weight: bolder;"> {{ $etudiant->date_naissance }} </span></td>
                <td style="border: none;"><span style="font-weight: normal;">Filière :</span><span style="font-weight: bolder;"> {{ $etudiant->filiere?->nom }} </span></td>
            </tr>
            <tr>
                
                <td style="border: none;"><span style="font-weight: normal;">Lieu de naissance : </span> <span style="font-weight: bolder;"> {{ $etudiant->lieu_de_naissance }}</span></td>
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
            <tr>
                <td colspan="7" style="font-weight: bold; text-align: center;"></td>
            </tr>
            @php
            $num = 1;
            @endphp
            @foreach($bulletin_data[0] as $line)
            @if(is_array($line))
            <tr>
                <td>{{ $num }}</td>
                <td>{{ $line["code"] }}</td>
                <td style="font-weight: bold;">{{ $line['nom'] }}</td>
                <td>{{ $line['credit'] }}</td>
                <td>{{ $line['moyenne'] * 5 }}</td>  
                <td>{{ $line['frequence'] }}</td>
                <td>{{ $line['etat'] }}</td>
                @php
                    $num++;
                @endphp
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

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
                <td style="border: none;"><span style="font-weight: normal;">Nombre de UE validé : </span><strong> {{ $bulletin_data[0]["nombre_ue_valide"] }}/{{ $bulletin_data[0]["nombre_ue"] }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Crédits obtenus : </span> <strong>{{ $bulletin_data[0]["nombre_credit_obtenu"] }}/{{ $bulletin_data[0]["nombre_credit_total"] }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Moyenne : </span> <strong>{{ $bulletin_data[0]["moyenne"] }}</strong></td>
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">Nombre de UE cumulé : </span> <strong>{{ $bulletin_data[0]["nombre_ue_valide"] }}/{{ $bulletin_data[0]["nombre_ue"] }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Total crédits cumulés : </span> <strong>{{ $bulletin_data[0]["nombre_credit_obtenu"] }}/{{ $bulletin_data[0]["nombre_credit_total"] }}</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Grade ETCS : </span><strong>{{ $bulletin_data[0]["grade"] }}</strong></td> 
            </tr>
            <tr>
                <td style="border: none;"><span style="font-weight: normal;">% Crédits requis : </span><strong> 80%</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">% Crédits cumulés : </span> <strong>{{ round((float)($bulletin_data[0]["nombre_credit_obtenu"] *100 )/ (float)($bulletin_data[0]["nombre_credit_total"]), 2) }}%</strong></td>
                <td style="border: none;"><span style="font-weight: normal;">Décision du conseil : </span><strong> {{ $bulletin_data[0]["decision"] }}</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div style="width: 100%; text-align: center; font-size: 14px;">
        <p style="margin: 5px 0;">Fait à Abomey-Calavi le {{ now()->format('d') }} {{ ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][now()->format('n')] }} {{ now()->format('Y') }}</p>
        <p style="margin: 5px 0;">Le Chef CAP</p>
        <div style="height: 60px;"></div>
        <p style="margin: 5px 0; text-decoration: underline;">{{ $signataire->nomination }}</p>
    </div>
</div>
@endsection

@section('footer-text')
    <div style="text-align: center;">
        <span style="margin-left: 170px;"><i>*Nombre de composition dans l'UE</i></span><br>
        <span style="font-weight: bold; margin-left: 100px;">NB: </span> Ce relevé ne peut en aucun cas tenir lieu d'attestation de diplôme et n'est délivré qu'une seule fois
    </div>
@endsection
