@extends('core::pdfs.epac-base')

@section('title', 'Fiche récapitulatif')

@section('document-title')
    FICHE RECAPITULATIF DE NOTES
@endsection

@section('info-table')
<table class="info-table">
    <tr>
        <td><strong>Année Académique :</strong> {{ $annee }}</td>
        <td><strong>Période : </strong> ....................</td>
    </tr>
    <tr>
        <td><strong>Filière :</strong> {{ $filiere }}</td>
        <td><strong>Classe :</strong> {{ $classe }}</td>
    </tr>
    <tr>
        <td><strong>Matière :</strong> {{ $matiere }}</td>
        <td></td>
    </tr>
    <tr>
        <td><strong>Enseignant :</strong> {{ $enseignant }}</td>
        <td></td>
    </tr>
</table>
@endsection

@section('content')
<table>
    <thead>
        <tr>
            <th style="text-align:center">N°</th>
            <th style="text-align:center">Matricule</th>
            <th style="width:200px;overflow:hidden;text-align:center">Noms et Prénoms</th>
            @for($i = 0; $i < $column_count; $i++)
                <th style="text-align:center">
                    Note{{ $i + 1 }} /20
                    @if(isset($weighting[$i]))
                        <br><small>({{ $weighting[$i] }}%)</small>
                    @endif
                </th>
            @endfor
            <th style="text-align:center">Moy/20</th>
            @if(isset($include_retake) && $include_retake && $retake_column_count > 0)
                @for($i = 0; $i < $retake_column_count; $i++)
                    <th style="text-align:center">
                        Rattrapage{{ $i + 1 }} /20
                        @if(isset($retake_weighting[$i]))
                            <br><small>({{ $retake_weighting[$i] }}%)</small>
                        @endif
                    </th>
                @endfor
                <th style="text-align:center">Moy. Finale/20</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($etudiants as $key => $etudiant)
            <tr>
                <td style="text-align:center">{{ $loop->iteration }}</td>
                <td style="text-align:center">{{ $etudiant->etudiant->student_id_number }}</td>
                <td>{{ $etudiant->etudiant->nom . " " . $etudiant->etudiant->prenoms }}</td>
                @foreach($etudiant->notes as $note)
                    <td style="text-align:center">{{ $note == -1 ? '' : $note }}</td>
                @endforeach
                <td style="text-align:center">{{ $etudiant->moyenne == -1 ? '' : number_format($etudiant->moyenne, 2) }}</td>
                @if(isset($include_retake) && $include_retake && $retake_column_count > 0)
                    @if($etudiant->moyenne >= 12)
                        @for($i = 0; $i < $retake_column_count; $i++)
                            <td style="text-align:center">V</td>
                        @endfor
                        <td style="text-align:center">{{ number_format($etudiant->moyenne, 2) }}</td>
                    @else
                        @foreach($etudiant->retake_grades as $note)
                            <td style="text-align:center">{{ $note == -1 ? '' : $note }}</td>
                        @endforeach
                        <td style="text-align:center">
                            @if($etudiant->retake_average && $etudiant->retake_average != -1)
                                {{ number_format($etudiant->retake_average >= 12 ? 12 : $etudiant->retake_average, 2) }}
                            @endif
                        </td>
                    @endif
                @endif
            </tr>
        @empty
        <tr>
            <td colspan="{{ 4 + $column_count + ($include_retake && $retake_column_count > 0 ? $retake_column_count + 1 : 0) }}" style="text-align:center">
                Aucun étudiant
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
