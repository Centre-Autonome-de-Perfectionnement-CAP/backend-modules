@extends('core::pdfs.layouts.base')

@section('title', 'RECAPITULATIF DES NOTES SESSION NORMALE')

@section('content')
    <style>
        thead { page-break-inside: avoid; page-break-after: avoid; }
    </style>
    <div class="pg">
        @include('core::pdfs.partials.header')
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">Année Académique:
                <span> {{ $annee }} </span>
            </div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">RÉCAPITULATIF DES NOTES SESSION NORMALE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 13px; font-size: 25px;"> {{ $classe->filiere->nom }} - {{ $classe->filiere->diplome->sigle }}</div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                        <th rowspan="3" class="text-center">#</th>
                        <th rowspan="3" class="text-center">Matricule</th>
                        <th rowspan="3" class="text-center">Nom et Prénoms</th>
                        <th colspan="{{ $nd }}" class="text-center">Semestre {{ $sem == 1 ? 'Impair' : 'Pair' }}</th>
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            @php
                                $ncol = ($p->maxWeightCount ?? 0) + 1;
                            @endphp
                            <th colspan="{{ $ncol }}" class="text-center">{{ $p->code }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($programmes as $key => $p)
                            @php
                                $weightCount = $p->maxWeightCount ?? 0;
                            @endphp
                            @for ($i = 1; $i <= $weightCount; $i++)
                                <th class="text-center">Dev{{ $i }}</th>
                            @endfor
                            <th class="text-center">Moy</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody style="width: 100%;text-align: left;">
                    @foreach ($etudiants as $i => $et)
                        <tr>
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th style="text-align:left; padding-left:10px;"> {{ $et->nom . ' ' . $et->prenoms }} </th>
                            @php
                                $colIndex = 0;
                            @endphp
                            @foreach ($programmes as $key => $p)
                                @php
                                    $weightCount = $p->maxWeightCount ?? 0;
                                @endphp
                                @for ($j = 0; $j < $weightCount; $j++)
                                    <th class="text-center">{{ $nt[$i][$colIndex++] }}</th>
                                @endfor
                                @php
                                    $moy = $moyennes[$i][$key] ?? '-';
                                    $isValidated = is_numeric($moy) && $moy >= $classe->moy_min;
                                @endphp
                                <th class="text-center {{ is_numeric($moy) ? ($isValidated ? 'ok' : 'notOk') : '' }}">{{ $moy }}</th>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="top-page" style="margin-top: 20px;">
                @foreach ($programmes as $key => $programme)
                    <span class="mx-2">{{ $programme->code . ': ' . $programme->matiere_professeur->matiere->libelle }} #</span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
