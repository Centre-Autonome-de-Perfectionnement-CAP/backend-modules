@extends('core::pdfs.epac-base')

@section('title', 'Liste des Étudiants - ' . ($departmentName ?? ''))
@section('hide-annee', 'true')

@section('extra-styles')
.info-box {
    background-color: #f0f0f0;
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 11px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
table thead {
    background-color: #003087;
    color: white;
}
table th {
    padding: 8px 10px;
    text-align: left;
    font-weight: bold;
    font-size: 12px;
    color: white;
    background-color: #003087;
}
table td {
    padding: 6px 10px;
    border-bottom: 1px solid #ddd;
    font-size: 11px;
}
table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}
.total {
    font-weight: bold;
    margin-top: 15px;
    font-size: 13px;
}
@endsection

@section('content')
<div class="main">
    <div style="text-align: center; margin-bottom: 15px;">
        <h1 style="margin: 0; color: #003087; font-size: 20px; text-transform: uppercase;">Liste des Étudiants</h1>
        <p style="margin: 4px 0; font-size: 13px;"><strong>{{ $departmentName }}</strong></p>
        <p style="margin: 2px 0; color: #666; font-size: 11px;">Généré le {{ $generatedAt }}</p>
    </div>

    <div class="info-box">
        <strong>Note :</strong> Cette liste contient les coordonnées des étudiants de votre filière. 
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Nom</th>
                <th style="width: 35%;">Prénom(s)</th>
                <th style="width: 25%;">Téléphone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student['nom'] }}</td>
                <td>{{ $student['prenom'] }}</td>
                <td>{{ $student['telephone'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total : {{ $totalStudents }} étudiant(s)
    </div>
</div>
@endsection
