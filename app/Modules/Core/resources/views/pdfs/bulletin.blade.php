@extends('core::pdfs.epac-base')

@section('title', 'Bulletin')

@section('hide-annee', 'true')

@section('content')
<div class="main" style="position: relative;">
    <div style="text-align: center; font-weight: bold; margin-bottom: 5px; font-size: 22px;">BULLETIN DE NOTES</div>
    <div style="text-align: center; font-weight: bold; margin-bottom: 12px; font-size: 14px;">Année Académique: {{ $annee }}</div>
    
    @php
        if(isset($etudiant->photo) && $etudiant->photo && file_exists($etudiant->photo)) {
            $photoPath = $etudiant->photo;
            $photoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($photoPath));
        } else {
            if(isset($etudiant->genre) && ucfirst($etudiant->genre) == 'Masculin') {
                $avatarPath = storage_path('avatars/homme.png');
            } else {
                $avatarPath = storage_path('avatars/femme.png');
            }
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
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Nom :</span> <span style="font-weight: bolder;">{{ $etudiant->nom }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Sexe :</span> <span style="font-weight: bolder;">{{ ucfirst($etudiant->genre ?? '') }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Cycle :</span> <span style="font-weight: bolder;">{{ $etudiant->filiere?->diplome?->nom ?? '' }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Prénoms :</span> <span style="font-weight: bolder;">{{ $etudiant->prenoms }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Date de naissance :</span> <span style="font-weight: bolder;">{{ $etudiant->date_naissance }}</span></td>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Filière :</span> <span style="font-weight: bolder;">{{ $etudiant->filiere?->nom ?? '' }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 4px;"><span style="font-weight: normal;">Lieu de naissance :</span> <span style="font-weight: bolder;">{{ $etudiant->lieu_de_naissance }}</span></td>
                            <td style="border: none; padding: 2px 4px;" colspan="2"><span style="font-weight: normal;">Niveau :</span> <span style="font-weight: bolder;">Classes Préparatoires</span></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 80px; text-align: right; vertical-align: middle; border: none; padding: 0;">
                @if(isset($qrcode) && $qrcode)
                <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="75px" height="75px" alt="Code QR">
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
            @php
            $num = 1;
            @endphp
            @foreach($bulletin_data[0] as $line)
            @if(is_array($line))
            <tr>
                <td style="text-align:center; padding: 3px;">{{ $num }}</td>
                <td style="text-align:center; padding: 3px;">{{ $line["code"] ?? '' }}</td>
                <td style="text-align:left; padding: 3px; font-weight: bold;">{{ $line['nom'] ?? '' }}</td>
                <td style="text-align:center; padding: 3px;">{{ $line['credit'] ?? '' }}</td>
                <td style="text-align:center; padding: 3px;">{{ isset($line['moyenne']) ? str_replace('.', ',', number_format($line['moyenne'] * 5, 2, '.', '')) : '' }}</td>  
                @php
                    $num++;
                @endphp
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

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
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Moyenne : </span> <strong>{{ isset($bulletin_data[0]["moyenne"]) ? str_replace('.', ',', number_format((float)$bulletin_data[0]["moyenne"], 2, '.', '')) : '' }}</strong></td>
            </tr>
            <tr style="text-align:left;">
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Grade ETCS : </span><strong>{{ $bulletin_data[0]["grade"] ?? '' }}</strong></td> 
            </tr>
            <tr style="text-align:left;">
                <td style="border: none; padding: 2px 0;"><span style="font-weight: normal;">Décision du conseil : </span><strong> {{ $bulletin_data[0]["decision"] ?? '' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 10px; font-style: italic; margin-bottom: 10px;">
        <strong>NB:</strong> Ce relevé ne peut en aucun cas tenir lieu d'attestation de diplôme et n'est délivré qu'une seule fois.
    </div>
    
    <div style="width: 100%; text-align: center; font-size: 12px;">
        <p style="margin: 10px 0;">Fait à Abomey-Calavi le, {{ now()->format('d') }} {{ ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][now()->format('n')] }} {{ now()->format('Y') }}</p>
        <p style="margin: 5px 0;">Le Chef CAP</p>
        <div style="height: 50px;"></div>
        <p style="margin: 5px 0; text-decoration: underline; font-weight: bold;">{{ $signataire->nomination }}</p>
    </div>
</div>
@endsection
