<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Notes</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .main {
            position: relative;
        }
        .corps {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            text-align: center;
            font-size: 12px;
        }
        .corps th, .corps td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: left;
        }
        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            text-align: center;
        }
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="main">
        <div style="text-align: center; font-weight: bold; margin-bottom: 7px; font-size: 25px;">BULLETIN DE NOTES</div>
        <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 15px;">Année Académique: {{ $annee }}</div>
        <div style="position: absolute; top: -2px; right: 0;">
            <img src="data:image/svg+xml;base64,{{ $qrcode }}" width="100px" height="100px" alt="Code QR">
        </div>
        
        <table style="width: 100%; text-align: left; margin-bottom: 10px; font-size: 11px; border: none; margin-top: 10px; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">Matricule :</span> <span style="font-weight: bolder;"> {{ $etudiant->matricule }} </span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Sexe :</span> <span style="font-weight: bolder;"> {{ ucfirst($etudiant->genre) }} </span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Cycle :</span> <span style="font-weight: bolder;"> {{ $etudiant->filiere?->diplome?->nom }} </span></td>
                </tr>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">Nom :</span> <span style="font-weight: bolder;"> {{ $etudiant->nom }} </span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Date de naissance :</span> <span style="font-weight: bolder;"> {{ $etudiant->date_naissance }} </span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Filière :</span><span style="font-weight: bolder;"> {{ $etudiant->filiere?->nom }} </span></td>
                </tr>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">Prénoms :</span> <span style="font-weight: bolder;"> {{ $etudiant->prenoms }} </span></td>
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
                @php $num = 1; @endphp
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
                    @php $num++; @endphp
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>

        <table style="width: 100%; margin: 7px; text-align: center; font-size: 14px; border: none; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td colspan="3" style="font-weight: bolder; text-align: center; border: none;">BILAN DE L'ANNÉE</td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; text-align: left; padding-left: 10px; margin-bottom: 10px; font-size: 13px; border: none; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">Nombre de UE validé : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["nombre_ue_valide"] }}/{{ $bulletin_data[0]["nombre_ue"] }}</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Crédits obtenus : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["nombre_credit_obtenu"] }}/{{ $bulletin_data[0]["nombre_credit_total"] }}</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Moyenne : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["moyenne"] }}</span></td>
                </tr>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">Nombre de UE cumulé : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["nombre_ue_valide"] }}/{{ $bulletin_data[0]["nombre_ue"] }}</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Total crédits cumulés : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["nombre_credit_obtenu"] }}/{{ $bulletin_data[0]["nombre_credit_total"] }}</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Grade ETCS : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["grade"] }}</span></td> 
                </tr>
                <tr>
                    <td style="border: none;"><span style="font-weight: normal;">% Crédits requis : </span><span style="font-weight: bold;">80%</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">% Crédits cumulés : </span><span style="font-weight: bold;">{{ round((float)($bulletin_data[0]["nombre_credit_obtenu"] *100 )/ (float)($bulletin_data[0]["nombre_credit_total"]), 2) }}%</span></td>
                    <td style="border: none;"><span style="font-weight: normal;">Décision du conseil : </span><span style="font-weight: bold;">{{ $bulletin_data[0]["decision"] }}</span></td>
                </tr>
            </tbody>
        </table>
        
        <div style="width: 100%; text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; font-weight: bold;">Fait à Abomey-Calavi le {{ $date_impression }}</p>
            <p style="font-size: 14px; font-weight: bold;">Le Chef CAP</p>
            <div style="height: 50px;"></div>
            <p style="font-size: 14px; font-weight: bold; text-decoration: underline;">{{ $signataire->nomination }}</p>
        </div>
    </div>

    <footer>
        <span><i>*Nombre de composition dans l'UE</i></span><br>
        <span style="font-weight: bold;">NB: </span> Ce relevé ne peut en aucun cas tenir lieu d'attestation de diplôme et n'est délivré qu'une seule fois
    </footer>
</body>
</html>
