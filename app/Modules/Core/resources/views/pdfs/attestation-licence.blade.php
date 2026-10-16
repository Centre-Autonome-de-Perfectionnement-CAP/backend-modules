@extends('core::pdfs.epac-base')

@section('title', 'Attestation de Licence')
@section('hide-annee', 'true')

@section('content')
<div class="main">
    <div style="text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 25px;">ATTESTATION DE LICENCE</div>
    
    <div style="margin: 20px 0; font-size: 13px; line-height: 1.8;">
        <p><strong>Nom :</strong> {{ $student->studentPendingStudent?->pendingStudent?->personalInformation?->last_name ?? '' }}</p>
        <p><strong>Prénoms :</strong> {{ $student->studentPendingStudent?->pendingStudent?->personalInformation?->first_names ?? '' }}</p>
        <p><strong>Matricule :</strong> {{ $student->studentPendingStudent?->student?->student_id_number ?? '' }}</p>
        <p><strong>Filière :</strong> {{ $student->studentPendingStudent?->pendingStudent?->department?->name ?? '' }}</p>
        <p><strong>Année académique :</strong> {{ $student->academicYear?->libelle ?? $student->academicYear?->academic_year ?? '' }}</p>
    </div>
    
    <div style="margin: 30px 0; text-align: justify; font-size: 14px; line-height: 1.8;">
        <p>Le Directeur de l'École Polytechnique d'Abomey-Calavi (EPAC) atteste que l'étudiant(e) mentionné(e) ci-dessus a obtenu sa licence avec succès.</p>
    </div>
    
    <div style="text-align: center; margin-top: 50px; font-size: 13px;">
        <p>Fait à Abomey-Calavi, le {{ now()->format('d/m/Y') }}</p>
        <p style="margin-top: 20px;">Le Directeur</p>
        <div style="height: 50px;"></div>
        <p style="text-decoration: underline; font-weight: bold;">Prof. HOUNKONNOU Mahouton Norbert</p>
    </div>
</div>
@endsection