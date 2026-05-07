@php
    use Illuminate\Support\Str;

    // On enlève les accents et on met en minuscule
    $filiereNameNormalized = Str::lower(Str::ascii($classe->filiere->nom));
    $isPrepa = Str::contains($filiereNameNormalized, 'prepa');
@endphp

@extends('core::pdfs.layouts.base')

@section('title', 'RECAPITULATIF DES NOTES SESSION NORMALE')

@section('content')
    <style>
        thead { page-break-inside: avoid; page-break-after: avoid; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px; /* un peu de padding dans les cellules */
        }

        .ok {
            /* vert très léger pour les validations si tu veux garder le code couleur */
            background-color: #9bd3f7;
        }

        .notOk {
            background-color: #f4a6a6;
        }
    </style>

    <div class="pg">
        @include('core::pdfs.partials.header')
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">
                Année Académique:
                <span> {{ $annee }} </span>
            </div>

            <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 25px;">
                RÉCAPITULATIF DES NOTES SESSION NORMALE
            </div>

            <div style="text-align: center; font-weight: bold; margin-bottom: 13px; font-size: 25px;">
                {{ $classe->filiere->nom }} 
                @unless($isPrepa)
                    - {{ $classe->filiere->diplome->sigle }}
                @endunless
                - (2e Cohorte)
            </div>

            <table>
                <thead style="font-weight: bold;">
                    <tr>
                        <th rowspan="3" class="text-center">#</th>
                        <th rowspan="3" class="text-center">Matricule</th>
                        <th rowspan="3" class="text-center">Nom et Prénoms</th>
                        <th colspan="{{ $nd }}" class="text-center">
                            {{ $isPrepa ? 'Modules' : 'Semestre ' . ($sem == 1 ? 'Impair' : 'Pair') }}
                        </th>
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
                <tbody style="width: 100%; text-align: left;">
                    @foreach ($etudiants as $i => $et)
                        <tr>
                            <th> {{ $i + 1 }} </th>
                            <th> {{ $et->matricule }} </th>
                            <th style="text-align:left; padding-left:10px;">
                                {{ $et->nom . ' ' . $et->prenoms }}
                            </th>
                            @php
                                $colIndex = 0;
                            @endphp
                            @foreach ($programmes as $key => $p)
                                @php
                                    $weightCount = $p->maxWeightCount ?? 0;
                                @endphp
                                @for ($j = 0; $j < $weightCount; $j++)
                                    @php
                                        $note = $nt[$i][$colIndex++] ?? null;

                                        // Remplacer ABS et valeurs négatives par '-'
                                        $displayNote = (
                                            $note === 'ABS'
                                            || $note === null
                                            || $note === ''
                                            || (is_numeric($note) && (float) $note < 0)
                                        ) ? '-' : $note;
                                    @endphp
                                    <th class="text-center">{{ $displayNote }}</th>
                                @endfor
                                @php
                                    $moyRaw = $moyennes[$i][$key] ?? null;

                                    // Si moyenne = -1, ABS ou vide -> afficher '-'
                                    $isInvalidMoy = (
                                        $moyRaw === 'ABS'
                                        || $moyRaw === null
                                        || $moyRaw === ''
                                        || (is_numeric($moyRaw) && (float) $moyRaw < 0)
                                    );

                                    $moyAff = $isInvalidMoy ? '-' : $moyRaw;

                                    $isValidated = !$isInvalidMoy
                                        && is_numeric($moyRaw)
                                        && $moyRaw >= $classe->moy_min;
                                @endphp
                                <th class="text-center {{ !$isInvalidMoy ? ($isValidated ? 'ok' : 'notOk') : '' }}">
                                    {{ $moyAff }}
                                </th>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="top-page" style="margin-top: 20px;">
                @foreach ($programmes as $key => $programme)
                    <span class="mx-2">
                        {{ $programme->code . ': ' . $programme->matiere_professeur->matiere->libelle }} #
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
