@extends('core::pdfs.epac-base')

@section('title', 'Fiche d\'émargement et de relevé de note partiel')

@section('document-title')
    FICHE d'EMARGEMENT ET DE RELEVE DE NOTE PARTIEL
@endsection

@section('info-table')
<table class="info-table">
    <tr>
        <td>Devoir n° : ....................</td>
        <td>Date :  ....................</td>
    </tr>
    <tr>
        <td>Filière : <strong>{{ $filiere }}</strong></td>
        <td>Période :  ....................</td>
    </tr>
    <tr>
        <td><strong>Classe :</strong> {{ $classe  }}</td>
        <td></td>
    </tr>
    <tr>
        <td>Matière :  ..................................................................................</td>
        <td></td>
    </tr>
    <tr>
        <td>Enseignant : .........................................................................................</td>
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
            <th style="width:250px;overflow:hidden;text-align:center">Noms et Prénoms</th>
            <th style="text-align:center">Red</th>
            <th style="text-align:center">Signature</th>
            <th style="text-align:center">Note /20</th>
            <th style="padding:0;text-align:center">
                Observation
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($etudiants as $key => $etudiant)
            <tr>
                <td style="text-align:center">{{ $loop->iteration }}</td>
                <td style="text-align:center">{{  $etudiant->etudiant->matricule }}</td>
                <td>{{ $etudiant->etudiant->nom . " " . $etudiant->etudiant->prenoms }}</td>
                <td style="text-align:center">{{ $etudiant->etudiant->red ? "R" : " " }}</td>
                <td style="text-align:center"></td>
                <td style="text-align:center"></td>
                <td></td>
            </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center">
                Aucun étudiant
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection

@section('additional-content')
<table class="no-border">
    <tr>
        <td style="text-align:center">Salle de composition : </td>
        <td style="text-align:center">Effectif de la classe : <strong>{{ count($etudiants) }}</strong></td>
    </tr>
    <tr>
        <td style="text-align:center">Nombre de présent: </td>
        <td style="text-align:center">Nombre d'absent : </td>
    </tr>
</table>
<table class="no-border">
    <tr>
        <td style="text-align:center"><strong>Signature et Nom des surveillants :</strong></td>
        <td style="text-align:center"><strong>Signature et Nom de l'Enseignant :</strong></td>
    </tr>
</table>
@endsection
