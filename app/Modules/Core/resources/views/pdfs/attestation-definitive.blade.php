<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attestations PDF</title>
    <meta charset="utf-8" />
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            text-align: center;
            font-size: 13pt;
            font-family: "Albertus Medium";
            margin: 0.5cm 1.5cm 2cm 1.5cm;
        }

        .police {
            font-family: "Monserrat";
        }

        .attestation {
            font-size: 42pt;
            font-weight: normal;
            text-shadow: 2px 3px 4px black;
            line-height: 45px;
            margin-bottom: 5px;
            font-family: 'ALGERIA';
            letter-spacing: 5px;
            margin-top: 80px;
        }

        .diplome {
            font-size: 10pt;
            font-weight: bold;
            margin: auto;
            letter-spacing: -1px;
            width: 70%;
        }

        .main {
            text-align: justify;
        }

        .first {
            margin-bottom: 9px;
            font-style: normal;
            font-size: 11pt;
        }

        .mention {
            font-weight: normal;
        }

        .retrait {
            margin-left: 10mm;
        }

        .info {
            margin-top: 5px;
        }

        .info p {
            margin-top: 2px;
            margin-bottom: 2px;
        }

        .filiere {
            text-transform: uppercase;
            color: rgba(36, 88, 187, 1);
            font-size: 16pt;
            font-family: 'Berlin Sans FB';
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: center;
        }

        .text {
            margin-bottom: 4px;
        }

        .avant {
            font-family: 'Berlin Sans FB';
        }

        .decision {
            color: blue;
            opacity: .8;
            font-style: normal;
            font-size: 13pt;
        }

        .global {
            margin-top: 25px;
        }

        .directeur-adjoint {
            display: inline-block;
            width: 49%;
            font-style: normal;
            text-align: center;
            vertical-align: top;
            margin-top: 50px;
        }

        .directeur {
            font-style: normal;
            display: inline-block;
            width: 49%;
            text-align: center;
            vertical-align: top;
            margin-top: 50px;
        }

        .name {
            font-size: 11pt;
        }

        .paragraph-pristina {
            font-size: 14pt;
            font-family: 'Pristina';
            letter-spacing: 1.3px;
        }

    </style>
</head>

<body>
    @php
        $cotes = [
            'Excellent'  => 'A',
            'Très Bien'  => 'B',
            'Bien'       => 'C',
            'Assez-Bien' => 'D',
            'Passable'   => 'E',
        ];
    @endphp
    @php
        $epacLogo = public_path('assets/epac.png');
        $capLogo  = public_path('assets/cap.png');
        $banner   = public_path('assets/banner-1.png');
    @endphp

    @foreach ($attestations as $key => $attestation)
        <div class="attestation-page">

            {{-- ===== EN-TÊTE ===== --}}
            <div class="header">
                <table style="width: 100%;" class="police">
                    <tr>
                        <td style="width: 10%; text-align: left;">
                            @if(file_exists($epacLogo))
                                <img src="{{ $epacLogo }}" alt="EPAC" style="height: 100px;">
                            @endif
                        </td>
                        <td style="width: 80%; text-align: center; font-size: 7.5pt; line-height: 1.2;">
                            <div style="text-transform: uppercase;">
                                REPUBLIQUE DU BENIN
                            </div>
                            <div style="text-transform: uppercase; font-size: 0.7rem;">
                                MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE
                            </div>
                            <div style="text-transform: uppercase; font-size: 0.8rem; font-weight: bold;">
                                UNIVERSITE D'ABOMEY-CALAVI
                            </div>
                            <div style="font-weight: bold; font-size: 1rem; text-transform: uppercase; margin-top: 3px; color: #2E5AAC;">
                                ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI
                            </div>
                            <div style="margin-top: 6px; font-size: 1rem; font-style: italic; color: #2E5AAC;">
                                DIRECTION
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

                <div style="font-size: 10pt; margin-bottom: 20px; height: 60px;">
                    <div style="float: left; width: 45%;">
                        N° <span style="margin-left: 60px;">/EPAC/ CAP/ UAC</span>
                    </div>
                    <div style="float: left; width: 30%;">
                        <span>Abomey-Calavi, le<span style="margin-left: 50px;"> </span><span style="margin-left: 50px;"></span></span>
                    </div>
                    <div style="float: right; width: 25%; text-align: right;">
                        @if(!($attestation->photo_profil && file_exists(storage_path('app/public/' . $attestation->photo_profil))))
                            @if(isset($qrCodes[$key]))
                                <img src="data:image/png;base64,{{ base64_encode($qrCodes[$key]) }}" alt="QR Code"
                                    style="width: 60px; height: 60px;">
                            @endif
                        @endif
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>

            {{-- ===== TITRE + BANNIÈRE ===== --}}
            <div style="position: relative; top: -50px">
                <div style="position: absolute; left: 0; top: 30px;">
                    @if($attestation->photo_profil && file_exists(storage_path('app/public/' . $attestation->photo_profil)))
                        <img src="{{ storage_path('app/public/' . $attestation->photo_profil) }}"
                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;" alt="Photo de profil">
                    @endif
                </div>
                <div style="position: absolute; right: 0; top: 30px;">
                    @if($attestation->photo_profil && file_exists(storage_path('app/public/' . $attestation->photo_profil)))
                        @if(isset($qrCodes[$key]))
                            <img src="data:image/png;base64,{{ base64_encode($qrCodes[$key]) }}" alt="QR Code"
                                style="width: 80px; height: 80px;">
                        @endif
                    @endif
                </div>

                <p class="attestation">ATTESTATION</p>
                <p class="diplome" style="text-transform: uppercase;">
                    DE DIPLÔME DE {{ $attestation->filiere->diplome->libelle }}
                    <br>
                    <span style="font-size: 14pt;">( {{ implode('.', str_split(strtoupper($attestation->filiere->diplome->sigle ?? ''))) }} )</span>
                </p>
                @if(file_exists($banner))
                    <p style="text-align: center; margin-top: 8px;">
                        <img src="{{ $banner }}" alt="" style="width: 30%; display: block; margin: 0 auto;">
                    </p>
                @endif
            </div>

            {{-- ===== CORPS ===== --}}
            <div class="main">
                <p class="paragraph-pristina">
                    <span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
                    (EPAC), de l'Universit&eacute; d'Abomey-Calavi (UAC), soussign&eacute;, atteste que&nbsp;:
                </p>

                <div class="info">
                    <p>
                        <span>{{ $attestation->genre == 'Masculin' ? 'Mr' : 'Mlle' }} </span>
                        <span style="text-transform: uppercase;">{{ $attestation->nom }}</span>
                        <span style="text-transform: capitalize;"> {{ $attestation->prenoms }}</span>
                    </p>
                    <p>
                        N&eacute;<span>{{ $attestation->genre == 'Masculin' ? '' : 'e' }}</span>
                        {{ $vers[$key] == 0 ? 'le ' : '' }}<span class="date">{{ $attestation->date_naissance }}</span>
                        &agrave;
                        <span class="lieu" style="text-transform: capitalize;">
                            {{ $attestation->lieu_naissance }} (Rep. {{ $attestation->pays_naissance }})
                        </span>
                    </p>
                    <p>
                        @if(trim(str_replace("-", "", $attestation->matricule)))
                            Num&eacute;ro matricule : {{ $attestation->matricule }}
                        @endif
                        a obtenu le Dipl&ocirc;me de
                        <span>{{ strtoupper($attestation->filiere->diplome->libelle) }}</span>
                        en&nbsp;:
                    </p>
                </div>

                <div class="filiere">{{ $attestation->filiere->libelle }}</div>

                {{-- Bloc PV / Décision --}}
                @if($attestation->type_document == 'decision')
                    <div class="text" style="font-size: 13pt;">
                        <span class="avant">
                            <span class="retrait">Suivant</span> la d&eacute;cision de fin de formation n&deg;
                        </span>
                        <span class="decision">
                            {{ $attestation->decision }} du {{ $attestation->date_decision . ($attestation->titre_annee ? '' : '.') }}
                        </span>
                        @if($attestation->titre_annee)
                            <span>au titre de l'ann&eacute;e acad&eacute;mique {{ $attestation->titre_annee }}.</span>
                        @endif
                    </div>
                @else
                    <div class="text" style="font-size: 13pt;">
                        <span class="avant">
                            <span class="retrait">Suivant</span> le Proc&egrave;s-Verbal de soutenance N&deg;
                        </span>
                        <span class="decision">
                            {{ $attestation->pv_deliberation_numero }} du {{ $attestation->date_soutenance }}
                        </span>
                        @if($attestation->titre_annee)
                            <span>au titre de l'ann&eacute;e acad&eacute;mique {{ $attestation->titre_annee }}.</span>
                        @else
                            <span>.</span>
                        @endif
                    </div>
                @endif

                {{-- Mention — colonne ajoutée par la migration --}}
                @php
                    $mention = trim($attestation->mention ?? '');
                    $cote    = $cotes[$mention] ?? '';
                @endphp

                @if(trim(str_replace("-", "", $attestation->mention ?? '')))
                    <div>
                        <span style="text-decoration: underline" class="mention">Mention de la formation (C&ocirc;te)</span> :
                        <span>{{ $mention }} ({{ $cote }})</span>
                    </div>
                @endif

                <div class="paragraph-pristina" style="margin-top: 15px;">
                    <span class="retrait">La</span> pr&eacute;sente attestation, rev&ecirc;tue du sceau de l'EPAC,
                    est d&eacute;livr&eacute;e pour servir et valoir ce que de droit, en attendant l'&eacute;tablissement du Dipl&ocirc;me.
                </div>

                {{-- ===== SIGNATURES ===== --}}
                <div class="directeur-adjoint">
                    <p class="first" style="text-transform: capitalize; margin-left: -75px;">{{ $signataire->poste_adjoint }},</p>
                    <span style="font-size: 11pt; font-weight: normal; position: relative; top: -15px; left: -10px; font-style: italic; font-family: 'Times New Roman', Times, serif; text-align: left;">
                        Charg&eacute;e des &Eacute;tudes et des Affaires Acad&eacute;miques,
                    </span>
                    <div style="height: 70px;"></div>
                    <p style="text-decoration: underline; text-align: left;">
                        <strong class="first">{{ $titreSignataireAdjoint }}</strong><strong class="name">{{ $nomSignataireAdjoint }}</strong>
                    </p>
                </div>

                <div class="directeur">
                    <p class="first" style="text-transform: capitalize; margin-right: -75px; font-size: 13pt;">{{ $signataire->poste }},</p>
                    <div style="height: 83px;"></div>
                    <p style="text-decoration: underline; text-align: right;">
                        <strong class="first">{{ $titreSignataire }}</strong><strong class="name">{{ $nomSignataire }}</strong>
                    </p>
                </div>
            </div>

            {{-- ===== PIED DE PAGE ===== --}}
            <div class="police" style="background-color: #2E5AAC; text-align: center; position: fixed; border-top: 1px solid #2E5AAC; height: 20px; bottom: 15px; width: 100%; left: 0;">
                <p style="font-size: 9pt; position: relative; left: 0cm; color: white;">
                    01 B.P. 2009 COTONOU - Tel: O1 95 74 34 54 - IFU : 4201710123211 - E-mail : epac.uac@epac.uac.bj -
                    epacuac@bj.refer.org
                </p>
            </div>

        </div>
        @if(!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
