<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Attestation d'Inscription - CAP/EPAC</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @font-face {
            font-family: "Montserrat";
            src: url({{ storage_path('fonts/Montserrat/Montserrat-Regular.ttf') }});
        }
        @font-face {
            font-family: "ALGERIA";
            src: url({{ storage_path('fonts/ALGERIA.ttf') }});
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
        }
        @font-face {
            font-family: "Berlin Sans FB";
            src: url({{ storage_path('fonts/Berlin Sans FB Regular.ttf') }});
        }
        @font-face {
            font-family: "DejaVu Sans";
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
            font-weight: normal;
        }
        @font-face {
            font-family: "DejaVu Sans";
            src: url({{ storage_path('fonts/DejaVuSans-Bold.ttf') }}) format('truetype');
            font-weight: bold;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            text-align: center;
            font-size: 13pt;
            font-family: "DejaVu Sans", "Albertus Medium", serif;
            margin: 0.5cm 1.5cm 2cm 1.5cm;
        }

        .police {
            font-family: "Montserrat", "DejaVu Sans", sans-serif;
        }

        /* Filigrane */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.15;
            z-index: -1;
        }
        .watermark img {
            width: 420px;
            height: auto;
        }

        /* Grand titre style ALGERIA comme doc 2 */
        .titre-grand {
            font-size: 42pt;
            font-weight: normal;
            text-shadow: 2px 3px 4px black;
            line-height: 45px;
            margin-bottom: 5px;
            font-family: 'ALGERIA', "DejaVu Sans", serif;
            letter-spacing: 5px;
            margin-top: 60px;
        }

        .titre-sous {
            font-size: 10pt;
            font-weight: bold;
            margin: auto;
            letter-spacing: -1px;
            width: 70%;
            text-transform: uppercase;
        }

        .separateur-deco {
            text-align: center;
            margin-top: 5px;
            letter-spacing: 3px;
            font-size: 12pt;
        }

        /* Corps principal */
        .main {
            text-align: justify;
        }

        .retrait {
            margin-left: 10mm;
        }

        .paragraph-pristina {
            font-size: 14pt;
            font-family: 'Pristina', "DejaVu Sans", cursive;
            letter-spacing: 1.3px;
            margin-bottom: 10px;
        }

        .decision {
            color: blue;
            opacity: .8;
            font-style: normal;
            font-size: 13pt;
        }

        .info p {
            margin-top: 4px;
            margin-bottom: 2px;
        }

        /* Pied de page fixe */
        .footer {
            background-color: #2E5AAC;
            text-align: center;
            position: fixed;
            border-top: 1px solid #2E5AAC;
            height: 20px;
            bottom: 15px;
            width: 100%;
            left: 0;
        }
        .footer p {
            font-size: 9pt;
            position: relative;
            left: 0cm;
            color: white;
            font-family: "Montserrat", "DejaVu Sans", sans-serif;
        }

        .attestation-page {
            page-break-after: always;
        }
        .attestation-page:last-child {
            page-break-after: avoid;
        }
    </style>
</head>
<body>

    @php
        $epacLogo = public_path('assets/epac.png');
        $capLogo  = public_path('assets/cap.png');
    @endphp

    @foreach ($attestations as $key => $attestation)
    <div class="attestation-page">

        {{-- FILIGRANE --}}
        <div class="watermark">
            @if(file_exists($capLogo))
                <img src="{{ $capLogo }}" alt="CAP">
            @elseif(file_exists($epacLogo))
                <img src="{{ $epacLogo }}" alt="EPAC">
            @endif
        </div>

        {{-- EN-TÊTE --}}
        <table style="width: 100%;" class="police">
            <tr>
                <td style="width: 10%; text-align: left;">
                    @if(file_exists($epacLogo))
                        <img src="{{ $epacLogo }}" alt="EPAC" style="height: 100px;">
                    @endif
                </td>
                <td style="width: 80%; text-align: center; font-size: 7.5pt; line-height: 1.2;">
                    <div style="text-transform: uppercase;">REPUBLIQUE DU BENIN</div>
                    <div style="text-transform: uppercase; font-size: 0.7rem;">
                        MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE
                    </div>
                    <div style="text-transform: uppercase; font-size: 0.8rem; font-weight: bold;">
                        UNIVERSITE D'ABOMEY-CALAVI
                    </div>
                    <div style="font-weight: bold; font-size: 1rem; text-transform: uppercase; margin-top: 3px; color: #2E5AAC;">
                        ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI (EPAC)
                    </div>
                    <div style="font-size: 0.9rem; font-style: italic; color: #2E5AAC; margin-top: 2px;">
                        Centre Autonome de Perfectionnement (CAP)
                    </div>
                    <div style="font-size: 0.9rem; font-style: italic; color: #2E5AAC; margin-top: 2px;">
                        Direction
                    </div>
                    <hr style="border: 3px solid #2E5AAC; margin: 3px auto 0px auto; width: 150px;">
                </td>
                <td style="width: 10%; text-align: right;">
                    @if(file_exists($capLogo))
                        <img src="{{ $capLogo }}" alt="CAP" style="height: 100px;">
                    @endif
                </td>
            </tr>
        </table>
        <hr style="border: 0.5px solid #2E5AAC; margin: -3px 0 8px; width: 100%;">

        {{-- NUMÉRO + DATE --}}
        <div style="font-size: 10pt; margin-bottom: 20px; height: 60px;" class="police">
            <div style="float: left; width: 45%; text-align: left;">
                N&deg; <span style="margin-left: 60px;">/EPAC/CAP/UAC</span>
            </div>
            <div style="float: right; width: 40%; text-align: right;">
                @if(isset($qrCodes[$key]))
                    <img src="data:image/png;base64,{{ base64_encode($qrCodes[$key]) }}"
                         alt="QR Code" style="width: 55px; height: 55px;">
                @endif
            </div>
            <div style="clear: both;"></div>
        </div>

        {{-- GRAND TITRE style doc 2 --}}
        <div style="position: relative; top: -40px;">
            <p class="titre-grand">ATTESTATION</p>
            <p class="titre-sous">D'INSCRIPTION</p>
            <p class="separateur-deco">=-=-=-=-=-=-=-=</p>
        </div>

        {{-- CORPS --}}
        <div class="main">

            {{-- Introduction Pristina --}}
            <p class="paragraph-pristina">
                <span class="retrait">Le</span> Directeur du Centre Autonome de Perfectionnement (CAP)
                de l'&Eacute;cole Polytechnique d'Abomey-Calavi (EPAC)
                de l'Universit&eacute; d'Abomey-Calavi (UAC), soussign&eacute;, atteste que&nbsp;:
            </p>

            {{-- Bloc informations étudiant --}}
            <div class="info" style="margin-bottom: 8px;">

                <p style="margin-top: 6px;">
                    Au titre de l'ann&eacute;e acad&eacute;mique
                    <span class="decision">{{ $attestation->annee_academique }}</span>,
                    au Centre Autonome de Perfectionnement, en Formation Continue,
                </p>

                <p style="margin-top: 6px;">
                    @if($attestation->genre == 'M')
                        M.
                    @else
                        Mme / Mlle
                    @endif
                    <span style="text-transform: uppercase; font-weight: bold;">{{ $attestation->nom }}</span>
                    <span style="text-transform: capitalize; font-weight: bold;"> {{ $attestation->prenoms }}</span>
                </p>

                <p style="margin-top: 4px;">
                    N&eacute;(e) le&nbsp;:
                    <span class="decision">{{ $attestation->date_naissance }}</span>
                    &agrave; <span class="decision" style="text-transform: capitalize;">{{ $attestation->lieu_naissance }}</span>
                </p>

                @if(trim(str_replace('-', '', $attestation->matricule ?? '')))
                <p style="margin-top: 4px;">
                    Matricule&nbsp;: <span class="decision">{{ $attestation->matricule }}</span>
                </p>
                @endif

                <p style="margin-top: 4px;">
                    Fili&egrave;re&nbsp;:
                    <span class="decision">{{ $attestation->filiere_nom }}</span>
                </p>

                <p style="margin-top: 4px;">
                    Niveau&nbsp;:
                    <span class="decision">
                        {{ $attestation->niveau_label }}
                        @if(!empty($attestation->diplome_libelle))
                            ({{ $attestation->diplome_libelle }}
                            @if(!empty($attestation->diplome_sigle))
                                &mdash; {{ $attestation->diplome_sigle }}
                            @endif
                            )
                        @endif
                    </span>
                </p>

                <p style="margin-top: 8px;">
                    est r&eacute;guli&egrave;rement inscrit(e) au
                    <span class="decision">Centre Autonome de Perfectionnement (CAP)</span>
                    de l'&Eacute;cole Polytechnique d'Abomey-Calavi (EPAC)
                    de l'Universit&eacute; d'Abomey-Calavi (UAC).
                </p>

            </div>

            {{-- Formule de clôture Pristina --}}
            <div class="paragraph-pristina" style="margin-top: 20px;">
                <span class="retrait">Cette</span> attestation a &eacute;t&eacute; d&eacute;livr&eacute;e
                &agrave; l'int&eacute;ress&eacute;(e) pour servir et valoir ce que de droit.
            </div>

            {{-- Fait à --}}
            <p style="text-align: center; margin-top: 20px; font-size: 12pt;">
                Fait &agrave; Abomey-Calavi, le {{ $dateDuJour }}
            </p>

            {{-- SIGNATURES --}}
            <div style="width: 100%; margin-top: 30px;">

                {{-- QR code à gauche (si photo profil) --}}
                @if($attestation->photo_profil && file_exists(storage_path('app/public/' . $attestation->photo_profil)))
                    <div style="display: inline-block; width: 22%; vertical-align: top; text-align: left; padding-top: 5px;">
                        @if(isset($qrCodes[$key]))
                            <img src="data:image/png;base64,{{ base64_encode($qrCodes[$key]) }}"
                                 alt="QR Code" style="width: 80px; height: 80px;">
                        @endif
                    </div>
                @endif

                {{-- Bloc signataire à droite --}}
                <div style="text-align: right; margin-top: 10px;">
                    <p style="font-size: 11pt;">
                        {{ $signataire->poste ?? 'Le Directeur' }}
                    </p>
                    <div style="height: 70px;"></div>
                    <p style="text-decoration: underline; font-weight: bold; font-size: 12pt;">
                        {{ $nomSignataire }}
                    </p>
                </div>

            </div>

        </div>{{-- /main --}}

        {{-- PIED DE PAGE --}}
        <div class="footer police">
            <p>
                01 B.P. 2009 COTONOU &mdash; T&eacute;l : 01 95 74 34 54 &mdash;
                IFU : 4201710123211 &mdash;
                E-mail : epac.uac@epac.uac.bj &mdash;
                epacuac@bj.refer.org
            </p>
        </div>

    </div>
    @endforeach

</body>
</html>
