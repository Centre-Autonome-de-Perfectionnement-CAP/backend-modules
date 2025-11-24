@extends('core::pdfs.layouts.base')

@section('title', 'PV DELIBERATION SEMESTRIELLE')

@section('content')
    <div class="pg">
        @include('core::pdfs.partials.header')
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">Année Académique:
                <span> {{ $annee }} </span>
            </div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">PV DE DÉLIBÉRATION DU {{ $sem == 2 ? '2EME' : '1ER' }} SEMESTRE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 13px;"> {{ $classe->filiere->nom }} - {{ $classe->niveau }} Année ({{ $classe->filiere->diplome->sigle }})</div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                        <th rowspan="2" class="text-center">N°</th>
                        <th rowspan="2" class="text-center">Matricule</th>
                        <th rowspan="2" class="text-center">Nom et Prénoms</th>
                        <th colspan="{{ $nd + 1 }}" class="text-center">SEM{{ $sem }}({{ $sem == 2 ? $classe->niveau * 2 : $classe->niveau * 2 - 1 }})</th>
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            <th class="text-center">{{ $p->matiere_professeur->matiere->code }}({{ $sem }})</th>
                        @endforeach
                        <th class="text-center bg-dark">Moy</th>
                    </tr>
                </thead>
                <tbody style="width: 100%;text-align: left;">
                    @foreach ($etudiants as $i => $et)
                        <tr class="text-align-center">
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th> {{ $et->nom . ' ' . $et->prenoms }} </th>
                            @foreach ($nt[$i] as $n)
                                <th class="text-center"> {{ $n }} </th>
                            @endforeach
                            <th class="text-center {{ ($classe->filiere->diplome->lmd && $credits[$i] < ($sem == 2 ? $classe->cred_sem2 : $classe->cred_sem1)) || (!$classe->filiere->diplome->lmd && $moyennes[$i] < $classe->moy_min*5) ? 'notOk' : 'ok' }}">
                                @if ($classe->filiere->diplome->lmd)
                                    <span>{{ $moyennes[$i] }}</span>
                                    @if ($credits[$i] < ($sem == 2 ? $classe->cred_sem2 : $classe->cred_sem1))
                                        <br><span>[NV]</span>
                                    @endif
                                @else
                                    <span>{{ $moyennes[$i] }}</span>
                                    @if ($moyennes[$i] < $classe->moy_min*5)
                                        <br><span>[NV]</span>
                                    @endif
                                @endif
                            </th>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="top-page">
                @foreach ($programmes as $programme)
                    <span class="mx-2">{{ $programme->matiere_professeur->matiere->code . ' : ' . $programme->matiere_professeur->matiere->libelle }} #</span>
                @endforeach
            </div>
        </div>
    </div>

    @if ($etudiants_reprise->count())
        <div class="pg">
            @include('core::pdfs.partials.header')
            <div class="main">
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">Année Académique: <span> {{ $annee }} </span></div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">PV DE DÉLIBÉRATION DU {{ $sem == 2 ? '2EME' : '1ER' }} SEMESTRE -- REPRISE</div>
                <div style="text-align: center; font-weight: bold; margin-bottom: 13px;"> {{ $classe->filiere->nom }} - {{ $classe->niveau }} Année ({{ $classe->filiere->diplome->sigle }})</div>

                <table>
                    <thead style="font-weight: bold;">
                        <tr>
                            <th rowspan="2" class="text-center">N°</th>
                            <th rowspan="2" class="text-center">Matricule</th>
                            <th rowspan="2" class="text-center">Nom et Prénoms</th>
                            <th colspan="{{ $nd + 1 }}" class="text-center">SEM{{ $sem }}({{ $sem == 2 ? $classe->niveau * 2 : $classe->niveau * 2 - 1 }})</th>
                        </tr>
                        <tr>
                            @foreach ($programmes as $key => $p)
                                <th class="text-center">{{ $p->matiere_professeur->matiere->code }}({{ $sem }})</th>
                            @endforeach
                            <th class="text-center bg-dark">Moy</th>
                        </tr>
                    </thead>
                    <tbody style="width: 100%;text-align: left;">
                        @foreach ($etudiants_reprise as $i => $et)
                            <tr class="text-align-center">
                                <th> {{ $i + 1 }} </th>
                                <th> {{ $et->matricule }} </th>
                                <th> {{ $et->nom . ' ' . $et->prenoms }} </th>
                                @foreach ($ntr[$i] as $n)
                                    <th class="text-center"> {{ $n }} </th>
                                @endforeach
                                <th class="text-center {{($classe->filiere->diplome->lmd && $creditsr[$i] < ($sem == 2 ? $classe->cred_sem2 : $classe->cred_sem1)) || (!$classe->filiere->diplome->lmd && $moyennesr[$i] < $classe->moy_min*5) ? 'notOk' : 'ok' }}">
                                    <span>{{ $moyennesr[$i] }}</span>
                                    @if ($creditsr[$i] < ($sem == 2 ? $classe->cred_sem2 : $classe->cred_sem1))
                                        <br><span>[NV]</span>
                                    @endif
                                </th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="top-page">
                    @foreach ($programmes as $programme)
                        <span class="mx-2">{{ $programme->matiere_professeur->matiere->code . ' : ' . $programme->matiere_professeur->matiere->libelle }} #</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
