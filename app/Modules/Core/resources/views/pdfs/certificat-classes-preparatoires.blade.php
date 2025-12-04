<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat Préparatoire</title>
    <style>
        @font-face {
            font-family: "ALGERIA";
            src: url({{ storage_path('fonts/ALGERIA.ttf') }});
        }
        @font-face {
            font-family: "Economica";
            src: url({{ storage_path('fonts/Economica-Bold-OTF.otf') }});
        }
        @font-face {
            font-family: "Arial";
            src: url({{ storage_path('fonts/arial.ttf') }});
        }
        @font-face {
            font-family: "Albertus Medium";
            src: url({{ storage_path('fonts/albr55w.ttf') }});
        }
        @font-face {
            font-family: 'Pristina';
            src: url({{ storage_path('fonts/PRISTINA.ttf') }}) format('truetype');
            font-style: normal;
            font-weight: 400; /* normal */
        }

        @font-face {
            font-family: 'Pristina';
            src: url({{ storage_path('fonts/PRISTINA.ttf') }}) format('truetype');
            font-style: normal;
            font-weight: 700; /* bold */
        }

        @font-face {
            font-family: "Berlin Sans FB";
            src: url({{ storage_path('fonts/Berlin Sans FB Regular.ttf') }});
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Albertus Medium";
            font-size: 14pt;
            margin: 0;
            text-align: center;
        }
        
        .header {
            padding: 1cm 1cm;
        }
        
        .content {
            margin: 0 2cm;
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
                font-weight: 400;
            }

            .paragraph-pristina-bold {
                font-size: 15pt;
                font-family: 'Pristina';
                letter-spacing: 1.3px;
                font-weight: 700;
            }

    </style>
</head>
<body>
    @php
        $epacLogo = public_path('assets/epac.png');
        $uacLogo  = public_path('assets/uac.jpeg');
        $banner   = public_path('assets/banner-1.png');
    @endphp

    <div class="header">
        <table style="width: 100%;" >
            <tr>
                <td style="width: 20%; text-align: left;">
                    @if(file_exists($epacLogo))
                        <img src="{{ $epacLogo }}" alt="EPAC" style="height: 100px;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; font-size: 10pt; line-height: 1.2;">
                    <div style="font-weight: bold; text-transform: uppercase;">
                        REPUBLIQUE DU BENIN
                    </div>
                    <hr style="border: 1.3px solid #000; margin: 5px auto; width: 80px;">
                    <div style="font-weight: bold; text-transform: uppercase; margin-top: 3px;">
                        UNIVERSITE D'ABOMEY - CALAVI
                    </div>
                    <div style="font-weight: bold; font-size: 1.1rem; text-transform: uppercase; margin-top: 3px;">
                        ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI
                    </div>
                    <hr style="border: 2px solid #000; margin: 5px auto; width: 150px;">
                    <div style="margin-top: 6px; font-size: 1.1rem; font-style: italic;">
                        DIRECTION
                    </div>
                </td>
                <td style="width: 20%; text-align: right;">
                    @if(file_exists($uacLogo))
                        <img src="{{ $uacLogo }}" alt="UAC" style="height: 100px;">
                    @endif
                </td>
            </tr>
        </table>

        <hr style="border: 1px solid #000; margin: 5px 0 8px; width: 100%;">

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
            <p class="paragraph-pristina-bold">
                <span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
                (EPAC), ex-Collège Polytechnique Universitaire (CPU), soussigné, atteste que:
            </p>
            <br/>
            <div class="info">
                <p style="margin-bottom: 5px;">
                    <span style="{{ $etudiant->genre == 'F' ? '' : 'text-decoration: line-through;' }}">Mlle</span> / <span style="{{ $etudiant->genre == 'M' ? '' : 'text-decoration: line-through;' }}">Mr</span>
                    <span style="text-transform: uppercase;">{{ $etudiant->nom }}</span>
                    <span style="text-transform: capitalize;"> {{ $etudiant->prenoms }} </span> .........................
                </p> 
                <p style="margin-bottom: 5px; font-size: ">
                    Né<span>{{ $etudiant->genre == 'M' ? '' : 'e' }}</span>
                    {{ $etudiant->ne_vers == 0 ? 'le ' : '' }}<span class="date">{{ $etudiant->date_naissance }}</span> à
                    <!-- <span class="lieu" style="text-transform:capitalize;">{{ $etudiant->lieu_naissance }} <span style="text-transform: capitalize;">(REP. DU {{ $etudiant->pays_naissance }})</span></span>................ -->
                    <span class="lieu" style="text-transform:capitalize;">{{ $etudiant->lieu_naissance }} <span style="text-transform: capitalize;">(REP. DU BENIN)</span></span>................
                </p>
                <p>
                    a obtenu le Certificat Préparatoire aux Etudes d'Ingénieur conformément à la délibération du {{ $etudiant->date_soutenance }}
                </p>
            </div>
            <div class="filiere">FILIERE : {{ trim($etudiant->filiere->libelle) }}</div>

            <div class="paragraph-pristina-bold" style="margin-top: 15px; font-weight: 700;">
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
            
            <div style="position: fixed; bottom: 40px; left: -2cm; text-align: center; width: 90%; left: 0; padding: 0 2cm;">
                <hr style="border: 1px solid black; width: 100%; position: relative; left: -1cm; right: 1cm;">
                <p style="position: relative; left: -1cm; font-size: 11pt; font-family: 'Economica';">
                    Ce certificat est le résultat de la mise à niveau de l'étudiant et n'est valable que pour une inscription aux études ingénieurs du CAP.
                </p>
            </div>
            <div style="text-align: center; position: fixed; left: -2cm;  bottom: 7px; text-align: center; width: 90%; left: 0; padding: 0 2cm;">
                <hr style="border: 0.7px solid black; width: 100%; position: relative; left: -1cm; right: 1cm;">
                <p style="font-size: 9pt; position: relative; left: -1cm; ">
                    01 B.P.2009 COTONOU - TELEPHONE: 21 36 09 93 - FAX: 21 36 01 99 E-mail : epac.uac@epac.uac.bj - epacuac@bj.refer.org
                </p>
            </div>
        </div>
    </div>
</body>
</html>