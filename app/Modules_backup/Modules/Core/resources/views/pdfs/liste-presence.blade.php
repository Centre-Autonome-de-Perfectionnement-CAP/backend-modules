@extends('core::pdfs.epac-base')

@section('title', 'Fiche de Présence')

@section('body-font-size', '12px')

@section('document-title')
    FICHE DE PRÉSENCE
@endsection

@section('info-table')
<table class="info-table">
    <tr>
        <td><strong>Filière :</strong> {{ $filiere }}</td>
        <td><strong>Période : </strong> ....................</td>
    </tr>
    <tr>
        <td><strong>Classe :</strong> {{ $classe  }}</td>
        <td><strong>Date : </strong> .........................</td>
    </tr>
    <tr>
        <td><strong>Matière : </strong> ..................................................................................</td>
        <td colspan="2"><strong>Durée : </strong> .......................</td>
    </tr>
    <tr>
        <td><strong>Enseignant :</strong> .........................................................................................</td>
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
            <th style="text-align:center">Nationalité</th>
            <th colspan="2" style="padding:0;text-align:center">
                Emargement
                <table style="width:100%;border-bottom:none;margin-top:10px">
                    <th style="border-bottom:none;width:50%;text-align:center">Début</th>
                    <th style="border-bottom:none;width:50%;text-align:center">Fin / Dépôt</th>
                </table>
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($etudiantsEnAttente as $key => $etudiant)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align:center">{{ $etudiant['matricule'] }}</td>
                <td>{{ $etudiant['nom'] . ' ' . $etudiant['prenoms'] }}</td>
                <td style="text-align:center">{{ $etudiant['red'] ? "R" : " " }}</td>
                <td style="text-align:center">{{ $etudiant['nationalite'] }}</td>
                <td></td>
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
        <td>Effectif de la classe : <strong>{{ count($etudiantsEnAttente) }}</strong></td>
        <td>Nombre de présent: </td>
        <td>Nombre d'absent : </td>
    </tr>
</table>
<table class="no-border">
    <tr>
        <td><strong>Signature et Nom des surveillants :</strong></td>
        <td><strong>Signature et Nom de l'Enseignant :</strong></td>
    </tr>
</table>
@endsection
