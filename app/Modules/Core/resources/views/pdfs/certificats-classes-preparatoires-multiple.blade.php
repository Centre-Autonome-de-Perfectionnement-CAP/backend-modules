<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificats Préparatoires</title>
    <style>
        @font-face {
            font-family: "ALGERIA";
            src: url({{ storage_path('fonts/ALGERIA.ttf') }});
        }
        @font-face {
            font-family: "Arial";
            src: url({{ storage_path('fonts/arial.ttf') }});
        }
        @font-face {
            font-family: "Economica";
            src: url({{ storage_path('fonts/Economica-Bold-OTF.otf') }});
        }
        @font-face {
            font-family: "Albertus Medium";
            src: url({{ storage_path('fonts/albr55w.ttf') }});
        }
        @font-face {
            font-family: 'Pristina';
            src: url({{ storage_path('fonts/PRISTINA.ttf') }}) format('truetype');
        }
        @font-face {
            font-family: "Berlin Sans FB";
            src: url({{ storage_path('fonts/Berlin Sans FB Regular.ttf') }});
        }

        @page {
            margin: 0cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Albertus Medium";
            font-size: 13pt;
            margin: 0;
            text-align: center;
        }
        
        .page {
            page-break-after: always;
            position: relative;
        }
        
        .page:last-child {
            page-break-after: auto;
        }
        
        .header {
            padding: 10px 1.5cm 0 1.5cm;
        }
        
        .content {
            margin: 0 1.5cm;
        }
        
        table {
            border-collapse: collapse;
        }
        
        table, td {
            border: none;
        }
        
        .attestation {
            font-size: 28pt;
            font-family: 'ALGERIA';
            text-transform: uppercase;
            margin: 22px 0 0;
        }
        
        .main {
            text-align: justify;
            margin-top: 20px;
        }
        
        .retrait {
            margin-left: 10mm;
        }
        
        .info {
            margin-top: 5px;
        }
        
        .info p {
            margin: 2px 0;
        }
        
        .filiere {
            text-transform: uppercase;
            color: rgba(36, 88, 187, 1);
            font-size: 19pt;
            font-family: 'Berlin Sans FB';
            margin: 15px 0;
            text-align: center;
        }
        
        .directeur {
            margin-top: 30px;
            text-align: center;
            padding-left: 42%;
        }
        
        .first {
            margin-bottom: 9px;
            font-style: italic;
            font-size: 13pt;
        }
        
        .name {
            font-size: 13pt;
            font-style: italic;
        }
        
        .paragraph-pristina {
            font-size: 15pt;
            font-family: 'Pristina';
            letter-spacing: 1.3px;
        }
    </style>
</head>
<body>
    @php
        $headerImg = file_exists(public_path("images/epac_header_portrait.png"))
            ? public_path("images/epac_header_portrait.png")
            : (file_exists(storage_path("images/epac_header_portrait.png"))
                ? storage_path("images/epac_header_portrait.png")
                : base_path("storage/images/epac_header_portrait.png"));
        $headerBase64 = file_exists($headerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerImg)) : '';
        $footerImg = file_exists(public_path("images/epac_footer_portrait.png"))
            ? public_path("images/epac_footer_portrait.png")
            : (file_exists(storage_path("images/epac_footer_portrait.png"))
                ? storage_path("images/epac_footer_portrait.png")
                : base_path("storage/images/epac_footer_portrait.png"));
        $footerBase64 = file_exists($footerImg) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerImg)) : '';
        $banner   = public_path('assets/banner-1.png');
    @endphp

    @foreach($etudiants as $etudiant)
    <div class="page">
        <div style="margin: 0; padding: 0; width: 100%; text-align: center;">
            @if($headerBase64)
                <img src="{{ $headerBase64 }}" alt="En-tête officiel EPAC" style="width: 100%; height: auto; display: block;">
            @endif
        </div>

        <div class="header">

        <table style="width: 100%; font-size: 14pt;">
            <tr>
                <td style="width: 50%; text-align: left;  margin-top: 5px; margin-bottom: 5px; margin-right: 50px;">
                    N° <span style="margin-left: 60px;">/EPAC/ CAP/ UAC<span>
                </td>
                <td style="width: 50%; text-align: left;  margin: 5px 0; ">
                    <span>Abomey-Calavi, le<span style="margin-left: 50px;">      /</span><span style="margin-left: 50px;">      /</span> 20    </span>
                </td> 
            </tr>
        </table>
        <br/>
        <br/><br/>
        <p class="attestation">
            CERTIFICAT PREPARATOIRE<br>AUX ETUDES D'INGENIEUR
        </p>
            @if(file_exists($banner))
                <img src="{{ $banner }}" alt="banner">
            @endif
    </div>

    <div class="content">
        {{-- Contenu --}}
        <div class="main">
            <p class="paragraph-pristina">
                <span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
                (EPAC), ex-Collège Polytechnique Universitaire (CPU), soussigné, atteste que:
            </p>
            <br/>
            <div class="info">
                <p style="margin-bottom: 5px;">
                    <span style="{{ $etudiant->genre == 'F' ? '' : 'text-decoration: line-through;' }}">Mlle</span> / <span style="{{ $etudiant->genre == 'M' ? '' : 'text-decoration: line-through;' }}">Mr</span>
                    <span style="text-transform: uppercase;">{{ $etudiant->nom }}</span>
                    <span style="text-transform: capitalize;"> {{ $etudiant->prenoms }} </span> 
                </p> 
                <p style="margin-bottom: 5px; font-size: ">
                    Né<span>{{ $etudiant->genre == 'M' ? '' : 'e' }}</span>
                    {{ $etudiant->ne_vers == 0 ? 'le ' : '' }}<span class="date">{{ $etudiant->date_naissance }}</span> à
                    <!-- <span class="lieu" style="text-transform:capitalize;">{{ $etudiant->lieu_naissance }} <span style="text-transform: capitalize;">(REP. DU {{ $etudiant->pays_naissance }})</span></span>................ -->
                    <span class="lieu" style="text-transform:capitalize;">{{ $etudiant->lieu_naissance }} <span style="text-transform: capitalize;">(REP. DU BENIN)</span></span>
                </p>
                <p>
                    a obtenu le Certificat Préparatoire aux Etudes d'Ingénieur conformément à la délibération du {{ $etudiant->date_soutenance }}
                </p>
            </div>
            <div class="filiere">FILIERE : {{ trim($etudiant->filiere->libelle) }}</div>

            <div class="paragraph-pristina" style="margin-top: 15px;">
                <span class="retrait">Le</span> présent certificat, revêtu du sceau de l'EPAC, est délivrée pour servir et valoir ce que de droit.
            </div>
            <br/><br/>
            <div class="directeur">
                <p class="first" style="text-transform: capitalize; font-weight: bold;">{{ $signataire->poste }},</p>
                <div style="height: 55px;"></div>
                <p style="text-decoration: underline;">
                    <strong class="name">{{ $signataire->nomination }}</strong>
                </p>
            </div>
            
            <div style="position: fixed; bottom: 50px; left: 0; right: 0; text-align: center; width: 100%; padding: 0 1.5cm;">
                <hr style="border: 0.8px solid black; width: 100%; margin-bottom: 4px;">
                <p style="font-size: 10pt; font-family: 'Economica'; font-style: italic;">
                    Ce certificat est le résultat de la mise à niveau de l'étudiant et n'est valable que pour une inscription aux études ingénieurs du CAP.
                </p>
            </div>
            <div style="position: fixed; bottom: 0; left: 0; right: 0; width: 100%; margin: 0; padding: 0;">
                @if($footerBase64)
                    <img src="{{ $footerBase64 }}" alt="Pied de page officiel EPAC" style="width: 100%; height: auto; display: block;">
                @endif
                <div style="position: absolute; bottom: 3px; right: 20px; color: #ffffff; font-size: 8px;">
                    Imprimé le {{ now()->format('d/m/Y à H:i') }}
                </div>
            </div>
        </div>
    </div>
    </div>
    @endforeach
</body>
</html>
