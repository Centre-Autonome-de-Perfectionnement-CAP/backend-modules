@extends('core::pdfs.epac-base')

@section('title', 'Emploi du Temps')

@section('body-font-size', '9px')
@section('body-margin', '15px')

@section('extra-styles')
.schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 8px;
}
.schedule-table th {
    background-color: #4CAF50;
    color: white;
    padding: 8px;
    text-align: center;
    font-weight: bold;
}
.schedule-table td {
    padding: 5px;
    text-align: center;
    vertical-align: top;
    min-height: 60px;
}
.schedule-table td.time-cell {
    background-color: #f0f0f0;
    font-weight: bold;
    white-space: nowrap;
}
.course-block {
    background-color: #e3f2fd;
    border: 1px solid #2196F3;
    border-radius: 3px;
    padding: 4px;
    margin: 2px 0;
    font-size: 7px;
}
.course-name {
    font-weight: bold;
    margin-bottom: 2px;
}
.course-info {
    font-size: 6px;
    color: #555;
}
.info-section {
    margin: 15px 0;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
}
.info-section strong {
    color: #333;
}
@endsection

@section('document-title')
Emploi du temps des années préparatoires ({{ $classGroup->department->name ?? 'N/A' }})
@if($startDate && $endDate)
du {{ $startDate }} au {{ $endDate }}
@endif
@endsection

@section('info-table')
<div class="info-section">
    <table class="info-table">
        <tr>
            <td><strong>Filière:</strong> {{ $classGroup->department->name ?? 'N/A' }}</td>
            <td><strong>Niveau:</strong> {{ $classGroup->study_level ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Groupe:</strong> {{ $classGroup->group_name ?? 'N/A' }}</td>
            <td><strong>Année Académique:</strong> {{ $classGroup->academicYear->libelle ?? $classGroup->academicYear->academic_year ?? 'N/A' }}</td>
        </tr>
    </table>
</div>
@endsection

@section('content')
<table class="schedule-table">
    <thead>
        <tr>
            <th style="width: 80px;">Horaire</th>
            <th>LUNDI</th>
            <th>MARDI</th>
            <th>MERCREDI</th>
            <th>JEUDI</th>
            <th>VENDREDI</th>
            <th>SAMEDI</th>
        </tr>
    </thead>
    <tbody>
        @foreach($schedule as $timeSlot => $data)
        <tr>
            <td class="time-cell">
                {{ $data['time']['start'] }}<br>-<br>{{ $data['time']['end'] }}
            </td>
            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day)
            <td>
                @foreach($data['days'][$day] as $course)
                <div class="course-block" style="background-color: {{ $course->timeSlot->type === 'lecture' ? '#e3f2fd' : ($course->timeSlot->type === 'td' ? '#e8f5e9' : '#fff3e0') }};">
                    <div class="course-name">{{ $course->program->courseElementProfessor->courseElement->name ?? 'N/A' }}</div>
                    <div class="course-info">
                        <strong>Salle:</strong> {{ $course->room->name ?? 'N/A' }} ({{ $course->room->code ?? '' }})<br>
                        <strong>Prof:</strong> {{ $course->program->courseElementProfessor->professor->first_name ?? '' }} {{ $course->program->courseElementProfessor->professor->last_name ?? '' }}
                    </div>
                </div>
                @endforeach
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

@if(count($schedule) > 0)
<div style="margin-top: 20px; font-size: 8px;">
    <strong>NB:</strong> 
    <span style="background-color: #e3f2fd; padding: 2px 5px; border: 1px solid #2196F3;">Cours Magistral</span>
    <span style="background-color: #e8f5e9; padding: 2px 5px; border: 1px solid #4CAF50;">TD</span>
    <span style="background-color: #fff3e0; padding: 2px 5px; border: 1px solid #FF9800;">TP</span>
</div>
@endif
@endsection

@section('additional-content')
<table class="no-border" style="margin-top: 40px;">
    <tr>
        <td style="text-align: left;">
            <strong>Le Responsable Division Formation Continue</strong>
        </td>
        <td style="text-align: right;">
            <strong>Le Chef CAP</strong>
        </td>
    </tr>
</table>
@endsection
