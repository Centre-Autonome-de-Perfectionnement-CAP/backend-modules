@php
    $include_contact = isset($include_contact) ? $include_contact : false;
@endphp



<!DOCTYPE html>
  <html lang="fr">  
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title >CUCA-CUO</title>
      <style>
       
          @font-face {
            font-family: "Arial";
            src: url('./arial.TTF');
          }

          body {
            margin: 0.5cm 1cm;
            box-sizing: border-box;
            font-size: Arial;
          }
          
    

          .title{
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
          
          .entete thead th{
            padding: 5px;
          }
          .entete tbody{
            text-align: left;
            width: 70mm;
          }
          .entete tbody tr td{
            border: none;
          }
          .entete tbody tr th{
/*             width: 60mm; */
            text-decoration: underline;
            text-align: left;
          }
          .dossier ul{
            text-align: left;
          }
          
          .dossier{
            padding: 0;
          }

          .liste {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 0 0;
            text-align: center;
<<<<<<< HEAD
=======
            page-break-inside: avoid;
>>>>>>> eea2b06 (draft)
          }
          .liste th, td {
              border: 0.5px solid black;
              padding: 5px;
          }
<<<<<<< HEAD
     
 
thead {
    display: table-row-group !important; /* Empêche la répétition */
}

.liste tr, .liste td, .liste th {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
.liste tr {
    page-break-inside: auto !important;
}
=======
          .liste tr {
              page-break-inside: avoid;
              page-break-after: auto;
          }
>>>>>>> eea2b06 (draft)

          .name{
            padding: 5px;
            font-size: 1rem;
          }
          
          .avis-cuca, .decision{
            width: 10%;
          }
          
          .numero {
            width: 3%;
          }
          .list-group-item{
            text-align: left;
          }
      </style>
    </head>   
    <body> 

      <main>
          <table class="entete">
<<<<<<< HEAD
            <tbody>
                <tr>
                  <th scope="row" class="title">Etablissement: </th>
                  <td>ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</td>
                </tr>
=======
            <thead>
                <tr>
                    <th scope="row"  rowspan="2" ></th>
                </tr>
            </thead> 
            <br />
            <tbody>
                <tr>
                  <th scope="row" class="title">Etablissement: </th>
                  <td>ECOLE POLYTECHNIQUE D'ABOMEY-CALAVI</td>
                </tr>
>>>>>>> eea2b06 (draft)
                <tr>
                  <th scope="row" class="title">DEPARTEMENT: </th>
                  <td>Centre Autonome de Perfectionnement (CAP)</td>
                </tr>
                <tr>
                  <th scope="row" class="title">FORMATION: </th>
                  <td>{{ $department }} ({{ $formation }})</td>
                </tr>
                <tr>
                  <th scope="row" class="title">ANNEE ACADEMIQUE: </th>
                  <td>{{ $academicYear }}</td>
                </tr>
            </tbody>
          </table>
   
          <table class="liste">
            <thead>
                <tr> 
                    <th>N° d'ordre</th>
                    <th>Nom et prénoms</th>
                    
                    @if($include_contact)
                    <th>Contact(s)</th>
                    @endif
                    
                    <th>Nationalité</th>
                    <th>Spécialité et année d'études sollicitées</th>
                    <th>Composition du dossier</th>
                    <th>Avis du CUCA (spécialité et années d'études accordées)</th>
                    <th>Raison/Motif</th>
                    <th>Décision de la CUO</th>
                </tr>
            </thead> 
            @php
                $i = 0;
            @endphp
            <tbody>
              @foreach($pendingStudents as $student)
              @php
                 $i++;
               @endphp
                <tr>
                    <td class="numero">{{ $i }}</td>
                    <td>{{ $student->personalInformation->last_name . ' ' . $student->personalInformation->first_names }}</td>
                    
                    @if($includeContact)
                    <td>
                        <ul class="list-group list-group-numbered">
                            @php
                                $contacts = $student->personalInformation->contacts;
                                if (is_string($contacts)) {
                                    $contacts = json_decode($contacts, true) ?? [];
                                }
                            @endphp
                            @foreach($contacts as $contact)
                                <li class="list-group-item">{{ is_array($contact) ? ($contact['phone'] ?? '') : $contact }}</li>
                            @endforeach
                        </ul>
                    </td>
                    @endif
                    
                    <td>{{ $student->personalInformation->birth_country }}</td>
                    <td>Première année en {{ $student->department->name ?? '' }}</td>
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
                    <td class="avis-cuca">{{ $student->cuca_opinion === 'pending' ? 'Non défini' : $student->cuca_opinion }}</td>
                    <td>{{ $student->cuca_comment ?? '' }}</td>
                    <td class="decision">{{ $student->cuo_opinion === 'pending' ? 'Non défini' : ($student->cuo_opinion ?? '') }}</td>
                </tr>
                @endforeach
            </tbody>
          </table>
      </main>
      <footer></footer>
    </body>
</html>