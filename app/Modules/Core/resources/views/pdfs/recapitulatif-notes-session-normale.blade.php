<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECAPITULATIF DES NOTES SESSION NORMALE RATRAPAGE</title>
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
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">RÉCAPITULATIF DES NOTES SESSION
                NORMALE
               <!-- {{ $sem ? '2EME' : '1ER' }} SEMESTRE--></div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 13px; font-size: 25px;"> PREPA {{ $classe->filiere->nom }} 
            </div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                        <th rowspan="3" class="text-center">#</th>
                        <th rowspan="3" class="text-center">Matricule</th>
                        <th rowspan="3" class="text-center">Nom et Prénoms</th>
                        <th colspan="{{ $nd }}" class="text-center">MODULES</th>
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            @php
                                $ncol = ($p->ponderation ? count(json_decode($p->ponderation)) : 0) + 1;
                            @endphp
                            <th colspan="{{ $ncol }}" class="text-center">{{ "M".($key+1) }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            @php
                                $ncol = ($p->ponderation ? count(json_decode($p->ponderation)) : 0) + 1;
                            @endphp
                            @for ($i = 1; $i < $ncol; $i++)
                                <th class="text-center"> Dev {{ $i }} </th>
                            @endfor
                            <th class="text-center bg-dark">Moy</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody style="width: 100%;text-align: left;">
                    @foreach ($etudiants as $i => $et)
                        <tr>
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th style="text-align:left; padding-left:10px;"> {{ $et->nom . ' ' . $et->prenoms }} </th>
                            @foreach ($nt[$i] as $key => $n)
                                <th class="text-center {{ $color[$i][$key] && $n < $classe->moy_min ? 'notOk' : '' }}">
                                    {{ $n }} </th>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($classe->filiere->diplome->lmd && $etudiants_reprise->count())
                        <tr>
                            <th colspan="{{ $nd + 3 }}" class="text-center">Reprise</th>
                        </tr>
                        @foreach ($etudiants_reprise as $i => $et)
                            <tr>
                                <th> {{ $i + 1 }} </th>
                                <th>{{ $et->matricule }}</th>
                                <th> {{ $et->nom . ' ' . $et->prenoms }} </th>
                                @foreach ($ntre[$i] as $key => $n)
                                    <th class="text-center {{ $colore[$i][$key] && $n < $classe->moy_min ? 'notOk' : '' }}">
                                        {{ $n }} </th>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
            <div class="top-page">
                @foreach ($programmes as $key => $programme)
                    <span class="mx-2">{{ "M".($key+1) . ' : ' . $programme->matiere_professeur->matiere->libelle }} </span>
                @endforeach
            </div>
        </div>
    </div>

    @if (count($etudiants_rattrape))
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
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">RÉCAPITULATIF DES NOTES SESSION NORMALE
                    {{ $sem ? '2EME' : '1ER' }} SEMESTRE</div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 13px; font-size: 25px;"> PREPA {{ $classe->filiere->nom }} 
                </div>

                <table>
                    <thead style="font-weight: bold;">
                        <tr>
                            <th rowspan="3" class="text-center">#</th>
                            <th rowspan="3" class="text-center">Matricule</th>
                            <th rowspan="3" class="text-center">Nom et Prénoms</th>
                            <th colspan="{{ $nd }}" class="text-center">MODULES</th>
                        </tr>
                        <tr>
                            @foreach ($programmes as $key => $p)
                                @php
                                    $ncol = ($p->ponderation ? count(json_decode($p->ponderation)) : 0) + 1;
                                @endphp
                                <th colspan="{{ $ncol }}" class="text-center">{{ "M".($key+1) }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($programmes as $key => $p)
                                @php
                                    $ncol = ($p->ponderation ? count(json_decode($p->ponderation)) : 0) + 1;
                                @endphp
                                @for ($i = 1; $i < $ncol; $i++)
                                    <th class="text-center"> Dev {{ $i }} </th>
                                @endfor
                                <th class="text-center bg-dark">Moy</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody style="width: 100%;text-align: left;">
                        @foreach ($etudiants_rattrape as $i => $et)
                            <tr>
                                <th> {{ $i + 1 }} </th>
                                <th> {{ $et->matricule }} </th>
                                <th style="text-align:left; padding-left:10px;"> {{ $et->nom . ' ' . $et->prenoms }} </th>
                                @foreach ($nt[$i] as $key => $n)
                                    <th class="text-center {{ $color[$i][$key] && $n < $classe->moy_min ? 'notOk' : '' }}">
                                        {{ $n }} </th>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="top-page">
                    @foreach ($programmes as $key => $programme)
                        <span class="mx-2">{{ "M".($key+1) . ' : ' . $programme->matiere_professeur->matiere->libelle ."#" }} </span>
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
