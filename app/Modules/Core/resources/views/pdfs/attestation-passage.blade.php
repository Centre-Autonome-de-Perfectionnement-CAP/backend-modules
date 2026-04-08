<!DOCTYPE html>
<html lang="fr">

<head>
    <title>Attestation de Passage en Classe Sup&eacute;rieure</title>
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

        .attestation {
            font-size: 42pt;
            font-weight: normal;
            text-shadow: 2px 3px 4px black;
            line-height: 45px;
            margin-bottom: 5px;
            font-family: 'ALGERIA', "DejaVu Sans", serif;
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
            font-family: 'Berlin Sans FB', "DejaVu Sans", serif;
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: center;
        }

        .text {
            margin-bottom: 4px;
        }

        .avant {
            font-family: 'Berlin Sans FB', "DejaVu Sans", serif;
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

        .name {
            font-size: 11pt;
        }

        .paragraph-pristina {
            font-size: 14pt;
            font-family: 'Pristina', "DejaVu Sans", cursive;
            letter-spacing: 1.3px;
        }
    </style>
</head>

<body>
    @php
        $epacLogo = public_path('assets/epac.png');
        $uacLogo  = public_path('assets/uac.jpeg');

        $accord = (strtolower($etudiant->genre ?? '') === 'féminin') ? 'e' : '';

        /*
        |------------------------------------------------------------
        | Logique d'affichage des années selon le cycle
        |------------------------------------------------------------
        | Ingénierie (4 ans : 1 an de prépa + 3 ans de spécialité)
        |   passage 1→2 : "en année de prépa" → "admis en 1ère année de spécialité"
        |   passage 2→3 : "en 1ère année de spécialité" → "admis en 2ème année de spécialité"
        |   passage 3→4 : "en 2ème année de spécialité" → "admis en 3ème année de spécialité"
        |
        | Autres cycles (Licence Pro, Master…) :
        |   on affiche directement annee_passage et annee_superieure tels quels
        |------------------------------------------------------------
        */

        $cycleIngenierie = false;
        $diplomeNom      = $etudiant->filiere->diplome->nom ?? '';
        $diplomeSigle    = '';

        $nomDiplomeLower = strtolower($diplomeNom);
        if (str_contains($nomDiplomeLower, 'ingéni') || str_contains($nomDiplomeLower, 'ingeni')) {
            $cycleIngenierie = true;
            $diplomeSigle    = 'Ing&eacute;nieur de Conception (DIC)';
        } elseif (str_contains($nomDiplomeLower, 'master')) {
            $diplomeSigle = 'Master (DM)';
        } elseif (str_contains($nomDiplomeLower, 'licence')) {
            $diplomeSigle = 'Licence Professionnelle (DLP)';
        } else {
            $diplomeSigle = $diplomeNom;
        }

        $anneeDepart    = $etudiant->annee_passage     ?? '';
        $anneeArrivee   = $etudiant->annee_superieure  ?? '';

        if ($cycleIngenierie) {
            $dep = intval($anneeDepart);
            $arr = intval($anneeArrivee);

            if ($dep === 1) {
                $labelDepart  = 'pr&eacute;pa';
                $labelArrivee = 'admis' . $accord . ' en 1&egrave;re ann&eacute;e de sp&eacute;cialit&eacute;';
            } else {
                $specDep = $dep - 1;
                $specArr = $arr - 1;
                $ordinals = ['1&egrave;re', '2&egrave;me', '3&egrave;me', '4&egrave;me', '5&egrave;me'];
                $ordDep = $ordinals[$specDep - 1] ?? ($specDep . '&egrave;me');
                $ordArr = $ordinals[$specArr - 1] ?? ($specArr . '&egrave;me');
                $labelDepart  = 'en ' . $ordDep . ' ann&eacute;e de sp&eacute;cialit&eacute;';
                $labelArrivee = 'admis' . $accord . ' en ' . $ordArr . ' ann&eacute;e de sp&eacute;cialit&eacute;';
            }
        } else {
            $labelDepart  = 'en ' . $anneeDepart;
            $labelArrivee = 'admis' . $accord . ' en ' . $anneeArrivee;
        }
    @endphp

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
                        @if(file_exists($uacLogo))
                            <img src="{{ $uacLogo }}" alt="UAC" style="height: 100px;">
                        @endif
                    </td>
                </tr>
            </table>
            <hr style="border: 0.5px solid #2E5AAC; margin: -3px 0 8px; width: 100%;">

            <div style="font-size: 10pt; margin-bottom: 20px; height: 60px;">
                <div style="float: left; width: 45%;">
                    N&deg; <span style="margin-left: 60px;">/EPAC/ CAP/ UAC</span>
                </div>
                <div style="float: left; width: 30%;">
                    <span>Abomey-Calavi, le<span style="margin-left: 50px;"></span></span>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>

        {{-- ===== TITRE ===== --}}
        <div style="position: relative; top: -50px">
            <p class="attestation">ATTESTATION</p>
            <p class="diplome" style="text-transform: uppercase;">
                DE PASSAGE EN CLASSE SUPERIEURE
            </p>
            <p style="text-align: center; margin-top: 5px; letter-spacing: 3px; font-size: 12pt;">
                =-=-=-=-=-=-=-=
            </p>
        </div>

        {{-- ===== CORPS ===== --}}
        <div class="main">

            {{-- Phrase d'introduction --}}
            <p class="paragraph-pristina" style="margin-bottom: 10px;">
                <span class="retrait">Le</span> Directeur de l'Ecole Polytechnique d'Abomey-Calavi
                (EPAC), soussign&eacute;, atteste que&nbsp;:
            </p>

            {{-- Bloc informations étudiant --}}
            <div class="info" style="margin-bottom: 8px;">

                <p style="margin-top: 6px;">
                    Au titre de l'<span class="decision">{{ $etudiant->annee_academique }}</span>,
                    au Centre Autonome de Perfectionnement, en Formation Continue,
                </p>

                <p style="margin-top: 6px;">
                    <span>{{ $etudiant->civilite }} </span>
                    <span style="text-transform: uppercase; font-weight: bold;">{{ $etudiant->nom }}</span>
                    <span style="text-transform: capitalize; font-weight: bold;"> {{ $etudiant->prenoms }}</span>
                </p>

                <p style="margin-top: 4px;">
                    @if(trim(str_replace('-', '', $etudiant->matricule ?? '')))
                        Matricule&nbsp;: <span class="decision">{{ $etudiant->matricule }}</span>
                    @endif
                </p>

                <p style="margin-top: 4px;">
                    Ann&eacute;e d'&eacute;tude&nbsp;:
                    <span class="decision">{!! $labelDepart !!}</span>
                </p>

                <p style="margin-top: 4px;">
                    Fili&egrave;re&nbsp;:
                    <span class="decision">
                        {{ $etudiant->filiere->nom }}
                        @if(!empty($etudiant->filiere->sigle))
                            ({{ $etudiant->filiere->sigle }})
                        @endif
                    </span>
                </p>

                <p style="margin-top: 4px;">
                    Dipl&ocirc;me pr&eacute;par&eacute;&nbsp;:
                    <span class="decision">{!! $diplomeSigle !!}</span>
                </p>

                <p style="margin-top: 8px;">
                    A r&eacute;guli&egrave;rement suivi les cours avec succ&egrave;s et est
                    <span class="decision">{!! $labelArrivee !!}</span>.
                </p>

            </div>

            {{-- Formule de clôture --}}
            <div class="paragraph-pristina" style="margin-top: 20px;">
                <span class="retrait">En</span> foi de quoi, la pr&eacute;sente attestation lui est
                d&eacute;livr&eacute;e pour servir et valoir ce que de droit.
            </div>

            {{-- ===== SIGNATURES (à droite) ===== --}}
            <div style="text-align: right; margin-top: 50px;">
                <p class="first">
                    Pour le Directeur et P.o, la Directrice Adjointe
                </p>
                <p style="font-size: 11pt; font-weight: normal; font-style: italic; font-family: 'Times New Roman', Times, serif;">
                    Charg&eacute;e des &Eacute;tudes et des Affaires Acad&eacute;miques,
                </p>
                <div style="height: 70px;"></div>
                <p style="text-decoration: underline;">
                    <strong class="name">{{ $signataire->nomination }}</strong>
                </p>
            </div>

        </div>

        {{-- ===== PIED DE PAGE ===== --}}
        <div class="police" style="background-color: #2E5AAC; text-align: center; position: fixed; border-top: 1px solid #2E5AAC; height: 20px; bottom: 15px; width: 100%; left: 0;">
            <p style="font-size: 9pt; position: relative; left: 0cm; color: white;">
                01 B.P. 2009 COTONOU - Tel: 01 95 74 34 54 - IFU : 4201710123211 - E-mail : epac.uac@epac.uac.bj -
                epacuac@bj.refer.org
            </p>
        </div>

    </div>
</body>

</html>