<<<<<<< HEAD
@php
    use Illuminate\Support\Str;

    // On enlève les accents et on met en minuscule
    $filiereNameNormalized = Str::lower(Str::ascii($classe->filiere->nom));
    $isPrepa = Str::contains($filiereNameNormalized, 'prepa');
@endphp

@extends('core::pdfs.layouts.base')

@section('title', 'PROCES VERBAL RESULTATS FIN D\'ANNEE')

@section('content')
<style>
    .notOk { background-color: #ffcccc; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 11px; }
    th, td { border: 2px solid #000; padding: 6px; text-align: center; font-weight: bold; }

    /* En-têtes sans fond gris */
    th { font-size: 12px; }
    thead { page-break-inside: avoid; page-break-after: avoid; }

    /* Couleurs pour les colonnes Moy / Décision */
    .cell-admis {
        background-color: #9bd3f7;   /* bleu clair */
    }
    .cell-redouble {
        background-color: #f4a6a6;   /* rouge clair */
    }

    .legend { margin-top: 15px; font-size: 10px; }
</style>



    <div class="pg">
        @include('core::pdfs.partials.header')
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 28px;">Année Académique: {{ $annee }}</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 28px; text-transform: uppercase; letter-spacing: 1px;">PROCES VERBAL DES RESULTATS DE FIN D'ANNEE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 26px;">
                @unless($isPrepa)
                    {{ $classe->filiere->diplome->sigle }}
                @endunless
            
                {{ $classe->filiere->nom }} - (2e Cohorte) 
            
                @unless($isPrepa)
                    -
                    @if($classe->niveau == '1') 1ère année
                    @elseif($classe->niveau == '2') 2e année
                    @elseif($classe->niveau == '3') 3e année
                    @else {{ $classe->niveau }}e année
                    @endif
                @endunless
            </div>



            <table>
                <thead>
                    <tr>
                        <th rowspan="2">N°</th>
                        <th rowspan="2">Matricule</th>
                        <th rowspan="2">Nom et Prénoms</th>
                        <th rowspan="2">Red</th>
                        @if($hasSem1)
                            <th colspan="{{ $programsSem1->count() + 1 }}">
                                {{ $isPrepa ? 'Modules' : 'Semestre Impair' }}
                            </th>
                        @endif
                        @if($hasSem2)
                            <th colspan="{{ $programsSem2->count() + 1 }}">
                                {{ $isPrepa ? 'Modules' : 'Semestre Pair' }}
                            </th>
                        @endif

                        <th rowspan="2">Moy.</th>
                        <th rowspan="2">Décision</th>
                    </tr>
                    <tr>
                        @if($hasSem1)
                            @foreach($programsSem1 as $prog)
                                <th>{{ $prog->code }}</th>
                            @endforeach
                            <th>Moy</th>
                        @endif
                        @if($hasSem2)
                            @foreach($programsSem2 as $prog)
                                <th>{{ $prog->code }}</th>
                            @endforeach
                            <th>Moy</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($etudiants as $index => $etudiant)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $etudiant->matricule }}</td>
                            <td style="text-align: left;">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                            <td>{{ $etudiant->isRedoublant ? 'R' : '' }}</td>
                            @if($hasSem1)
                                @foreach($etudiant->moyennesSem1 as $moy)
                                    <td>{{ is_numeric($moy) ? number_format($moy, 2) : $moy }}</td>
                                @endforeach
                                <td class="{{ $etudiant->moyenneSem1 > 0 && $etudiant->moyenneSem1 < $classe->moy_min ? 'notOk' : '' }}">
                                    @if($etudiant->moyenneSem1 > 0)
                                        {{ number_format($etudiant->moyenneSem1, 2) }}<br>
                                        <small>{{ $etudiant->moyenneSem1 >= $classe->moy_min ? '[V]' : '[NV]' }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            @if($hasSem2)
                                @foreach($etudiant->moyennesSem2 as $moy)
                                    <td>{{ is_numeric($moy) ? number_format($moy, 2) : $moy }}</td>
                                @endforeach
                                <td class="{{ $etudiant->moyenneSem2 > 0 && $etudiant->moyenneSem2 < $classe->moy_min ? 'notOk' : '' }}">
                                    @if($etudiant->moyenneSem2 > 0)
                                        {{ number_format($etudiant->moyenneSem2, 2) }}<br>
                                        <small>{{ $etudiant->moyenneSem2 >= $classe->moy_min ? '[V]' : '[NV]' }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            @php
                                $isAdmis = $etudiant->moyenneAnnuelle > 0
                                    && !$etudiant->hasZero
                                    && $etudiant->moyenneAnnuelle >= $classe->moy_min;
                            @endphp

                            <td class="{{ $etudiant->moyenneAnnuelle > 0 ? ($isAdmis ? 'cell-admis' : 'cell-redouble') : '' }}">
                                {{ $etudiant->moyenneAnnuelle > 0 ? number_format($etudiant->moyenneAnnuelle, 2) : '-' }}
                            </td>
                            <td class="{{ $etudiant->moyenneAnnuelle > 0 ? ($isAdmis ? 'cell-admis' : 'cell-redouble') : '' }}">
                                @if($etudiant->moyenneAnnuelle > 0)
                                    {{ $isAdmis ? 'Admis' : 'Redouble' }}
                                @else
                                    -
                                @endif
                            </td>

                        </tr>
=======
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROCES VERBAL RESULTATS FIN D'ANNEE</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 1cm;
            counter-increment: page;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 1cm;
        }

        .contenu {
            height: 200px;
        }

        .logoepac {
            width: 150px;
            position: relative;
            top: 10px;
            left: 50px;
        }

        .logouac {
            width: 150px;
            position: absolute;
            top: 10px;
            right: 50px;
        }

        .header {
            text-align: center;
            position: absolute;
            top: 10px;
            left: 35%;
            right: 35%;
        }

        .header h2 {
            font-size: 20px;
            margin: 2px 0;
        }

        .header h3 {
            position: relative;
            top: -20px;
        }

        .header p {
            position: relative;
            top: -35px;
            font-size: 11px;
        }

        hr {
            border: .7px solid black;
            position: relative;
            top: -35px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            text-align: center;
            font-size: 15px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 2px;
            text-align: center;
        }

        .main {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            top: -15px;
        }

        .top-page {
            font-size: 17px;
            text-align: left;
        }

        .notOk {
            background: rgb(228, 159, 159);
        }

        .ok {
            background: rgb(140, 211, 240);
        }

        .pg {
            min-height: 96%;
            page-break-after: always; /* Forcer une nouvelle page après chaque section */
        }

        footer {
            text-align: right;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 12px;
        }

        footer .page:after {
            content: "Page " counter(page);
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <div class="pg">
        <div class="contenu">
            <img src="{{ storage_path('/img/epac.png') }}" class="logoepac" alt="logo EPAC UAC">
            <div class="header">
                <h2>RÉPUBLIQUE DU BÉNIN</h2>
                <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                <h2>UNIVERSITÉ D'ABOMEY-CALAVI</h2>
                <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                <h2>ECOLE POLYTECHNIQUE D’ABOMEY-CALAVI</h2>
                <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                <h3>CENTRE AUTONOME DE PERFECTIONNEMENT</h3>
                <p>01 BP 2009 COTONOU - TEL. 21 36 14 32/21 36 09 93 - Email. contact@cap-epac.online</p>
            </div>
            <img src="{{ storage_path('/img/cap.tif') }}" class="logouac" alt="logo UAC">
        </div>
        <hr />
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">Année Académique:
                <span> {{ $annee }} </span>
            </div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">PROCES VERBAL DES RESULTATS DE FIN
                D'ANNEE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 13px; font-size: 25px;"> {{(!$classe->filiere->diplome->lmd && $classe->niveau == 1) ? 'PREPA' : ''}} {{ $classe->filiere->nom }} 
                <!--{{ $classe->niveau }} {{ $classe->niveau == 1 ? 'ère' : 'e' }}  Année ({{ $classe->filiere->diplome->sigle }})-->
            </div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                                        <th rowspan="3" class="text-center">N°</th>
                                        <th rowspan="3" class="text-center">Matricule</th>
                                        <th rowspan="3" class="text-center">Nom et Prénoms</th>

                                        <th colspan="{{ $nd[0] + (!(!$classe->filiere->diplome->lmd && $classe->niveau == 1) ? 1 : 0) }}" class="text-center">
                                           <!-- SEMESTRE {{ $sems[$classe->niveau * 2 - 2] }}--> MODULES
                                        </th>
                                        @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                                            <th colspan="{{ $nd[1] + 1 }}" class="text-center">
                                                <!--SEMESTRE {{ $sems[$classe->niveau * 2 - 1] }}-->
                                            </th>
                                        @endif
                                        
                                        <th rowspan="3" class="text-center">Moy</th>
                                        <th rowspan="3" class="text-center">Décision</th>
                                    </tr>
                    <tr>

                                        <th colspan="{{ $nd[0] + (!(!$classe->filiere->diplome->lmd && $classe->niveau == 1) ? 1 : 0) }}" class="text-center">
                                            <!--SEM1-->
                                        </th>
                                        @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                                            <th colspan="{{ $nd[1] + 1 }}" class="text-center">
                                            <!--SEM2-->
                                            </th>
                                        @endif
                                        
                                    </tr>
                    <tr>
                                        @foreach ($programmes as $key => $p)
                                            @if (!$p->sem)
                                                <th class="text-center">
                                                    {{ "M".$key+1  }} <!--{{ $p->matiere_professeur->matiere->code }}({{ $classe->niveau }})-->
                                                </th>
                                            @endif
                                        @endforeach
                                        @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                                            <th class="text-center">Moy</th>
                                        @endif
                                        @foreach ($programmes as $key => $p)
                                            @if ($p->sem)
                                                <th class="text-center">
                                                    {{ $p->matiere_professeur->matiere->code }}({{ $classe->niveau }})
                                                </th>
                                            @endif
                                        @endforeach
                                        @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                                            <th class="text-center">Moy</th>
                                        @endif
                                        
                                    </tr>
                </thead>
                <tbody style="width: 100%;text-align: left; ">
                    @foreach ($etudiants as $i => $et)
                        @if(!empty(array_filter($nt[$i])))

                        <tr class="">
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th style="text-align:left; padding-left:10px;"> {{ $et->nom . ' ' . $et->prenoms }} </th>
                            @foreach ($nt[$i] as $index => $n)
                                @if (!$programmes[$index]->sem)
                                    <th class="text-center"> {{ $n }} </th>
                                @endif
                            @endforeach
                            @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                                            <th
                                    class="text-center {{ $credits[0][$i] < $classe->cred_sem1 || $moyennes[0][$i] < $classe->moy_min*5 ? 'notOk' : 'ok' }} ">
                                @if ($classe->filiere->diplome->lmd)
                                    <span>{{ $moyennes[0][$i] }}</span>
                                    @if ($credits[0][$i] < $classe->cred_sem1)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                @else
                                    <span>{{ $moyennes[0][$i] }}</span>
                                    @if ($moyennes[0][$i] < $classe->moy_min*5)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                @endif
                            </th>
                            @endif
                            
                            @foreach ($nt[$i] as $index => $n)
                                @if ($programmes[$index]->sem)
                                    <th class="text-center"> {{ $n }} </th>
                                @endif
                            @endforeach
                            @if(!(!$classe->filiere->diplome->lmd && $classe->niveau == 1))
                            <th
                                class="text-center {{ $credits[1][$i] < $classe->cred_sem2 || $moyennes[1][$i] < $classe->moy_min*5 ? 'notOk' : 'ok' }}">
                                @if ($classe->filiere->diplome->lmd)
                                    <span>{{ $moyennes[1][$i] }}</span>
                                    @if ($credits[1][$i] < $classe->cred_sem2)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                @else
                                    <span>{{ $moyennes[1][$i] }}</span>
                                    @if ($moyennes[1][$i] < $classe->moy_min*5)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                @endif
                            </th>
                            @endif
                            
                            @php
                                $moyenne_annuelle = (!$classe->filiere->diplome->lmd && $classe->niveau == 1 ) ? $moyennes[0][$i] : ($moyennes[0][$i] + $moyennes[1][$i]) / 2;
                            @endphp
                            <th
                                class="text-center {{ ($classe->filiere->diplome->lmd && $credits[0][$i] + $credits[1][$i] >= ($classe->cred_sem1 + $classe->cred_sem2) * 0.8) || (!$classe->filiere->diplome->lmd && $moyenne_annuelle >= $classe->moy_min*5 && !$a_zeros[$i]) ? 'ok' : 'notOk' }}">
                                {{ $moyenne_annuelle}} </th>
                            <th
                                class="text-center {{ ($classe->filiere->diplome->lmd && $credits[0][$i] + $credits[1][$i] >= ($classe->cred_sem1 + $classe->cred_sem2) * 0.8) || (!$classe->filiere->diplome->lmd && $moyenne_annuelle >= $classe->moy_min*5 && !$a_zeros[$i]) ? 'ok' : 'notOk' }}">
                                @if ($classe->filiere->diplome->lmd)
                                    @if ($credits[0][$i] + $credits[1][$i] >= ($classe->cred_sem1 + $classe->cred_sem2) * 0.8)
                                        {{ $credits[0][$i] + $credits[1][$i] == $classe->cred_sem1 + $classe->cred_sem2 ? 'Admis' : 'Enjambé' }}
                                    @else
                                        {{ 'Red' }}
                                    @endif
                                @else
                                    {{ $moyenne_annuelle >= $classe->moy_min*5 && !$a_zeros[$i] ? 'Admis' : 'Redouble' }}
                                @endif
                            </th>
                        </tr>
                        @endif
>>>>>>> eea2b06 (draft)
                    @endforeach
                </tbody>
            </table>

<<<<<<< HEAD
            <div class="legend" style="text-align: left;">
                @if($hasSem1)
                    @foreach($programsSem1 as $key => $prog)
                        {{ $prog->code }}: {{ $prog->matiere_professeur->matiere->libelle }} #
                    @endforeach
                @endif
                @if($hasSem2)
                    @foreach($programsSem2 as $key => $prog)
                        {{ $prog->code }}: {{ $prog->matiere_professeur->matiere->libelle }} #
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
=======
            <div class="top-page">
                @foreach ($programmes as $key => $programme)
                    <span
                        class="mx-2">{{ "M".$key+1 . ' : ' . $programme->matiere_professeur->matiere->libelle ."#" }} <!--{{ $programme->matiere_professeur->matiere->code . ' : ' . $programme->matiere_professeur->matiere->libelle }}-->
                        #</span>
                @endforeach
            </div>
        </div>
    </div>


    @if (count($etudiants_reprise))
        <div class="pg">
            <div class="contenu">
                <img src="{{ storage_path('/img/epac.png') }}" class="logoepac" alt="logo EPAC UAC">
                <div class="header">
                    <h2>Université d'Abomey-Calavi</h2>
                    <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                    <h2>ECOLE POLYTECHNIQUE D’ABOMEY-CALAVI</h2>
                    <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                    <h3>CENTRE AUTONOME DE PERFECTIONNEMENT</h3>
                    <p>01 BP 2009 COTONOU - TEL. 21 36 14 32/21 36 09 93 - Email. contact@cap-epac.online</p>
                </div>
                <img src="{{ storage_path('/img/cap.png') }}" class="logouac" alt="logo UAC">
            </div>
            <hr />
            <div class="main">
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">Année Académique:
                    <span> {{ $annee }} </span>
                </div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">PROCES VERBAL DES RESULTATS DE
                    FIN
                    D'ANNEE -- REPRISE</div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 13px;"> {{ $classe->filiere->nom }} -
                    {{ $classe->niveau }} Année ({{ $classe->filiere->diplome->sigle }})
                </div>

                <table>
                    <thead style="font-weight: bold;">
                        <tr>
                            <th rowspan="3" style="text-align: center; ">#</th>
                            <th rowspan="3" style="text-align: center; ">Matricule</th>
                            <th rowspan="3" style="text-align: center; ">Nom et Prénoms</th>

                            <th colspan="{{ $nd[0] + 1 }}" style="text-align: center; ">
                                SEMESTRE {{ $sems[$classe->niveau * 2 - 2] }}
                            </th>
                            <th colspan="{{ $nd[1] + 1 }}" style="text-align: center; ">
                                SEMESTRE {{ $sems[$classe->niveau * 2 - 1] }}
                            </th>
                            <th rowspan="3" style="text-align: center; ">Moy</th>
                            <th rowspan="3" style="text-align: center; ">Décision</th>
                        </tr>
                        <tr>

                            <th colspan="{{ $nd[0] + 1 }}" style="text-align: center; ">
                                SEM1
                            </th>
                            <th colspan="{{ $nd[1] + 1 }}" style="text-align: center; ">
                                SEM2
                            </th>
                        </tr>
                        <tr>
                            @foreach ($programmes as $key => $p)
                                @if (!$p->sem)
                                    <th style="text-align: center; ">
                                        {{ $p->matiere_professeur->matiere->code }}({{ $classe->niveau }})
                                    </th>
                                @endif
                            @endforeach
                            <th style="text-align: center; ">Moy</th>
                            @foreach ($programmes as $key => $p)
                                @if ($p->sem)
                                    <th style="text-align: center; ">
                                        {{ $p->matiere_professeur->matiere->code }}({{ $classe->niveau }})
                                    </th>
                                @endif
                            @endforeach
                            <th style="text-align: center; ">Moy</th>
                        </tr>
                    </thead>
                    <tbody style="width: 100%;text-align: left; ">
                        @foreach ($etudiants_reprise as $i => $et)
                            <tr class="text-align-center">
                                <th> {{ $i + 1 }} </th>
                                <th> {{ $et->matricule }} </th>
                                <th style="text-align:left; padding-left:10px;"> {{ $et->nom . ' ' . $et->prenoms }} </th>
                                @foreach ($ntr[$i] as $index => $n)
                                    @if (!$programmes[$index]->sem)
                                        <th class="text-center"> {{ $n }} </th>
                                    @endif
                                @endforeach
                                <th
                                    class="text-center {{ $creditsr[0][$i] < $classe->cred_sem1 || $moyennesr[0][$i] < $classe->moy_min*5 ? 'notOk' : 'ok' }} ">

                                    <span>{{ $moyennesr[0][$i] }}</span>
                                    @if ($creditsr[0][$i] < $classe->cred_sem1)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                </th>
                                @foreach ($ntr[$i] as $index => $n)
                                    @if ($programmes[$index]->sem)
                                        <th class="text-center"> {{ $n }} </th>
                                    @endif
                                @endforeach
                                <th
                                    class="text-center {{ $creditsr[1][$i] < $classe->cred_sem2 || $moyennesr[1][$i] < $classe->moy_min*5 ? 'notOk' : 'ok' }}">

                                    <span>{{ $moyennesr[1][$i] }}</span>
                                    @if ($creditsr[1][$i] < $classe->cred_sem2)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                </th>
                                <th
                                    class="text-center {{ ($moyennesr[0][$i] + $moyennesr[1][$i]) / 2 < $classe->moy_min*5 ? 'notOk' : 'ok' }}">
                                    {{ ($moyennesr[0][$i] + $moyennesr[1][$i]) / 2 }} </th>
                                <th
                                    class="text-center {{ $creditsr[0][$i] + $creditsr[1][$i] >= ($classe->cred_sem1 + $classe->cred_sem2) * 0.8 ? 'ok' : 'notOk' }}">

                                    @if ($creditsr[0][$i] + $creditsr[1][$i] >= ($classe->cred_sem1 + $classe->cred_sem2) * 0.8)
                                        {{ $creditsr[0][$i] + $creditsr[1][$i] == $classe->cred_sem1 + $classe->cred_sem2 ? 'Admis' : 'Enjambé' }}
                                    @else
                                        {{ 'Redouble' }}
                                    @endif
                                </th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="top-page">
                    @foreach ($programmes as $programme)
                        <span
                            class="mx-2">{{ $programme->matiere_professeur->matiere->code . ' : ' . $programme->matiere_professeur->matiere->libelle }}
                            #</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <footer>
  <div style="text-align: left; font-size: 12px;">
        Imprimé le {{ date('d/m/Y à H:i') }} par la Cellule Informatique de la Division Formation Continue CAP
    </div>
    <div class="page"></div>
</footer>


</body>

</html>
>>>>>>> eea2b06 (draft)
