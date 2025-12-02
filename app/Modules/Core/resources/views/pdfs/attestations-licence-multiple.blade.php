@extends('core::pdfs.epac-base')

@section('title', 'Attestations de Licence')

@section('content')
@foreach($attestations as $attestation)
<div class="main" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
    <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 25px;">ATTESTATION DE LICENCE</div>
    
    <div style="margin: 20px 0;">
        <p><strong>Nom :</strong> {{ $attestation['student']->studentPendingStudent?->pendingStudent?->personalInformation?->last_name ?? '' }}</p>
        <p><strong>Prénoms :</strong> {{ $attestation['student']->studentPendingStudent?->pendingStudent?->personalInformation?->first_names ?? '' }}</p>
        <p><strong>Matricule :</strong> {{ $attestation['student']->studentPendingStudent?->student?->student_id_number ?? '' }}</p>
        <p><strong>Filière :</strong> {{ $attestation['student']->studentPendingStudent?->pendingStudent?->department?->name ?? '' }}</p>
        <p><strong>Année académique :</strong> {{ $attestation['student']->academicYear?->libelle ?? '' }}</p>
    </div>
    
    <div style="margin: 30px 0; text-align: justify;">
        <p>Le Directeur de l'École Polytechnique d'Abomey-Calavi (EPAC) atteste que l'étudiant(e) mentionné(e) ci-dessus a obtenu sa licence avec succès.</p>
    </div>
    
    <div style="text-align: center; margin-top: 50px;">
        <p>Fait à Abomey-Calavi, le {{ now()->format('d/m/Y') }}</p>
        <p style="margin-top: 30px;">Le Directeur</p>
        <div style="height: 60px;"></div>
        <p style="text-decoration: underline; font-weight: bold;">Prof. HOUNKONNOU Mahouton Norbert</p>
    </div>
</div>
@endforeach
@endsection