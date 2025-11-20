<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CUCA-CUO</title>
    <style>
        @font-face {
            font-family: "Arial";
            src: url('{{ public_path('fonts/arial.TTF') }}');
        }

        body {
            margin: 0.5cm 1cm;
            box-sizing: border-box;
            font-family: Arial;
        }

        .title {
            font-weight: bold;
            text-decoration: underline;
        }

        .entete {
            font-family: sans-serif;
            font-size: 1.1rem;
            width: 170mm;
            margin-bottom: 10px;
            margin-top: 12px;
            margin-left: 25px;
        }

        .entete thead th {
            padding: 5px;
        }

        .entete tbody {
            text-align: left;
            width: 70mm;
        }

        .entete tbody tr td {
            border: none;
        }

        .entete tbody tr th {
            text-decoration: underline;
            text-align: left;
        }

        .dossier ul {
            text-align: left;
        }

        .dossier {
            padding: 0;
        }

        .liste {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 0 0;
            text-align: center;
        }

        .liste th, td {
            border: 0.5px solid black;
            padding: 5px;
        }

        .name {
            padding: 5px;
            font-size: 1rem;
        }

        .avis-cuca, .decision {
            width: 10%;
        }

        .numero {
            width: 3%;
        }

        .list-group-item {
            text-align: left;
        }
    </style>
</head>
<body>
    <main>
        <table class="entete">
            <thead>
                <tr>
                    <th scope="row" rowspan="2"></th>
                </tr>
            </thead>
            <br />
            <tbody>
                <tr>
                    <th scope="row" class="title">ETABLISSEMENT :</th>
                    <td>Ecole Polytechnique d'Abomey-Calavi (EPAC)</td>
                </tr>
                <tr>
                    <th scope="row" class="title">DEPARTEMENT :</th>
                    <td>Centre Autonome de Perfectionnement (CAP)</td>
                </tr>
                <tr>
                    <th scope="row" class="title">FORMATION :</th>
                    <td>Ingénieur de Conception en {{ $department }} ({{ $formation }})</td>
                </tr>
                <tr>
                    <th scope="row" class="title">ANNEE ACADEMIQUE :</th>
                    <td>{{ $academicYear }}</td>
                </tr>
            </tbody>
        </table>

        <table class="liste">
            <thead>
                <tr>
                    <th>N° d'ordre</th>
                    <th>N° de téléphone</th>
                    <th>Nom et prénoms</th>
                    <!-- @if($includeContact)
                        <th>Contact(s)</th>
                    @endif -->
                    <th>Nationalité</th>
                    <th>Spécialité et année d'études sollicitées</th>
                    <th>Composition du dossier</th>
                    <th>Avis du CUCA (spécialité et années d'études accordées)</th>
                    <th>Raison/Motif</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @foreach($pendingStudents as $student)
                    @php $i++; @endphp
                    <tr>
                        <td class="numero">{{ $i }}</td>
                        <!-- <td>
                            <ul class="list-group list-group-numbered">
                                @foreach(json_decode($student->personalInformation->contacts, true) ?? [] as $contact)
                                    <li class="list-group-item">{{ $contact['phone'] ?? $contact }}</li>
                                @endforeach
                            </ul>
                        </td> -->
                        <td>{{ $student->personalInformation->last_name . ' ' . $student->personalInformation->first_names }}</td>
                        @if($includeContact)
                            <td>
                                <ul class="list-group list-group-numbered">
                                    @foreach(json_decode($student->personalInformation->contacts, true) ?? [] as $contact)
                                        <li class="list-group-item">{{ $contact['phone'] ?? $contact }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        @endif
                        <td>{{ $student->personalInformation->birth_country }}</td>
                        <td>
                            Première année en Classes Préparatoires
                        </td>
                        <td>
                            <ul class="list-group list-group-numbered">
                                @php
                                    $documents = $student->documents;
                                    if (is_string($documents)) {
                                        $documents = json_decode($documents, true) ?? [];
                                    }
                                @endphp
                                @foreach($documents as $piece => $path)
                                    <li class="list-group-item">{{ $piece }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="avis-cuca">
                            {{ $student->cuca_opinion === 'pending' ? 'Non défini' : $student->cuca_opinion }}
                        </td>
                        <td>{{ $student->cuca_comment ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
    <footer></footer>
</body>
</html>