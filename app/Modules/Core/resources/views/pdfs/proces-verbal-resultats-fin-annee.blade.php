@extends('core::pdfs.layouts.base')

@section('title', 'PROCES VERBAL RESULTATS FIN D\'ANNEE')

@section('content')
    <style>
        .notOk { background-color: #ffcccc; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 10px; }
        th, td { border: 2px solid #000; padding: 6px; text-align: center; }
        th { background-color: #d9d9d9; font-weight: bold; font-size: 12px; }
        .legend { margin-top: 15px; font-size: 10px; }
    </style>

    <div class="pg">
        @include('core::pdfs.partials.header')
        <div class="main">
            <div style="text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 28px; text-decoration: underline;">Année Académique: {{ $annee }}</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 15px; font-size: 28px; text-transform: uppercase; letter-spacing: 1px;">PROCES VERBAL DES RESULTATS DE FIN D'ANNEE</div>
            <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 26px;">
                {{ $classe->filiere->diplome->sigle }} {{ $classe->filiere->nom }} - 
                @if($classe->niveau == '1') 1ère année
                @elseif($classe->niveau == '2') 2e année
                @elseif($classe->niveau == '3') 3e année
                @else {{ $classe->niveau }}e année
                @endif
            </div>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2">N°</th>
                        <th rowspan="2">Matricule</th>
                        <th rowspan="2">Nom et Prénoms</th>
                        <th rowspan="2">Red</th>
                        @if($hasSem1)
                            <th colspan="{{ $programsSem1->count() + 1 }}">Semestre 1 (Impair)</th>
                        @endif
                        @if($hasSem2)
                            <th colspan="{{ $programsSem2->count() + 1 }}">Semestre 2 (Pair)</th>
                        @endif
                        <th rowspan="2">Moy. Année</th>
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
                                        <small>{{ $etudiant->moyenneSem1 >= $classe->moy_min ? 'V' : 'NV' }}</small>
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
                                        <small>{{ $etudiant->moyenneSem2 >= $classe->moy_min ? 'V' : 'NV' }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            <td>{{ $etudiant->moyenneAnnuelle > 0 ? number_format($etudiant->moyenneAnnuelle, 2) : '-' }}</td>
                            <td class="{{ ($etudiant->hasZero || ($etudiant->moyenneAnnuelle > 0 && $etudiant->moyenneAnnuelle < $classe->moy_min)) ? 'notOk' : '' }}">
                                @if($etudiant->moyenneAnnuelle > 0)
                                    {{ (!$etudiant->hasZero && $etudiant->moyenneAnnuelle >= $classe->moy_min) ? 'Adm' : 'Red' }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="legend" style="text-align: left;">
                <strong>Légende:</strong>
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
