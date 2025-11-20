<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PV DELIBERATION SEMESTRIELLE</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 1cm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 1cm;
        }

        .contenu{
            
        }

        .logoepac {
            width: 140px;
            position: relative;
            top: 10px;
            left: 50px;
        }

        .logouac {
            width: 140px;
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
            font-size: 12px;
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
            font-size: 8px;
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
            top: -35px;
        }

        .top-page {
            font-size: 10px;
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
        }
    </style>
</head>

<body>

    <div class="pg">
        <div class="contenu">
            <img src="{{ storage_path('/img/epac.png') }}" class="logoepac" alt="logo EPAC UAC">
            <div class="header">
                <h2>Université d'Abomey-Calavi</h2>
                <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                <h2>ECOLE POLYTECHNIQUE D’ABOMEY-CALAVI</h2>
                <img src="{{ storage_path('/img/banner.png') }}" class="" alt="Banner">
                <h3>CENTRE AUTONOME DE PERFECTIONNEMENT</h3>
                <p>01 BP 2009 COTONOU - TEL. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj</p>
            </div>
            <img src="{{ storage_path('/img/cap.png') }}" class="logouac" alt="logo UAC">
        </div>
        <hr />
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">Année Académique:
                <span> {{ $annee }} </span>
            </div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">PV DE DÉLIBÉRATION DU
                {{ $sem ? '2EME' : '1ER' }} SEMESTRE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 13px;"> {{ $classe->filiere->nom }} -
                {{ $classe->niveau }} Année ({{ $classe->filiere->diplome->sigle }})
            </div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                        <th rowspan="2" class="text-center">N°</th>
                        <th rowspan="2" class="text-center">Matricule</th>
                        <th rowspan="2" class="text-center">Nom et Prénoms</th>
                        <th colspan="{{ $nd + 1 }}" class="text-center">
                            SEM{{ $sem ? 2 : 1 }}({{ $sem ? $classe->niveau * 2 : $classe->niveau * 2 - 1 }})
                        </th>
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            <th class="text-center">
                                {{ $p->matiere_professeur->matiere->code }}({{ $sem ? 1 : 2 }}) </th>
                        @endforeach
                        <th class="text-center bg-dark">Moy</th>
                    </tr>

                </thead>
                <tbody style="width: 100%;text-align: left; ">
                    @foreach ($etudiants as $i => $et)
                        <tr class="text-align-center">
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th> {{ $et->nom . ' ' . $et->prenoms }} </th>
                            @foreach ($nt[$i] as $n)
                                <th class="text-center"> {{ $n }} </th>
                            @endforeach
                            <th class="text-center {{ ($classe->filiere->diplome->lmd && $credits[$i] < ($sem ? $classe->cred_sem2 : $classe->cred_sem1)) || ( !$classe->filiere->diplome->lmd &&  $moyennes[$i] < $classe->moy_min*5)   ? 'notOk' : 'ok' }}">
                                @if ($classe->filiere->diplome->lmd)
                                    <span>{{ $moyennes[$i] }}</span>
                                    @if ($credits[$i] < ($sem ? $classe->cred_sem2 : $classe->cred_sem1))
                                        <br>
                                        <span>[NV]</span>
                                    @endif
                                @else
                                    <span>{{ $moyennes[$i] }}</span>
                                    @if ($moyennes[$i] < $classe->moy_min*5)
                                        <br>
                                        <span>[NV]</span>
                                    @endif
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
                    <p>01 BP 2009 COTONOU - TEL. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj</p>
                </div>
                <img src="{{ storage_path('/img/cap.png') }}" class="logouac" alt="logo UAC">
            </div>
            <hr />
            <div class="main">
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">Année Académique:
                    <span> {{ $annee }} </span>
                </div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">PV DE DÉLIBÉRATION DU
                    {{ $sem ? '2EME' : '1ER' }} SEMESTRE -- REPRISE</div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 13px;"> {{ $classe->filiere->nom }} -
                    {{ $classe->niveau }} Année ({{ $classe->filiere->diplome->sigle }})
                </div>

                <table>
                    <thead style="font-weight: bold;">
                        <tr>
                            <th rowspan="2" class="text-center">N°</th>
                            <th rowspan="2" class="text-center">Matricule</th>
                            <th rowspan="2" class="text-center">Nom et Prénoms</th>
                            <th colspan="{{ $nd + 1 }}" class="text-center">
                                SEM{{ $sem ? 2 : 1 }}({{ $sem ? $classe->niveau * 2 : $classe->niveau * 2 - 1 }})
                            </th>
                        </tr>
                        <tr>
                            @foreach ($programmes as $key => $p)
                                <th class="text-center">
                                    {{ $p->matiere_professeur->matiere->code }}({{ $sem ? 1 : 2 }}) </th>
                            @endforeach
                            <th class="text-center bg-dark">Moy</th>
                        </tr>

                    </thead>
                    <tbody style="width: 100%;text-align: left; ">
                        @foreach ($etudiants_reprise as $i => $et)
                            <tr class="text-align-center">
                                <th> {{ $i + 1 }} </th>
                                <th> {{ $et->matricule }} </th>
                                <th> {{ $et->nom . ' ' . $et->prenoms }} </th>
                                @foreach ($ntr[$i] as $n)
                                    <th class="text-center"> {{ $n }} </th>
                                @endforeach
                                <th class="text-center {{($classe->filiere->diplome->lmd && $creditsr[$i] < ($sem ? $classe->cred_sem2 : $classe->cred_sem1)) || ( !$classe->filiere->diplome->lmd &&  $moyennesr[$i] < $classe->moy_min*5) ? 'notOk' : 'ok' }}">
                                    <span>{{ $moyennesr[$i] }}</span>
                                    @if ($creditsr[$i] < ($sem ? $classe->cred_sem2 : $classe->cred_sem1))
                                        <br>
                                        <span>[NV]</span>
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

</body>

</html>