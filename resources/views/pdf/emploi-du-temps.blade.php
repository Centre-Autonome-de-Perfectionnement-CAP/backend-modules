<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Emploi du temps</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 9px;
    color: #111;
}

/* ── Entête institution ─────────────────────────── */
.header { text-align: center; margin-bottom: 6px; }
.header h1 { font-size: 12px; font-weight: bold; }
.header h2 { font-size: 10px; font-weight: bold; }
.header h3 { font-size: 10px; font-weight: bold; }
.header .dots { font-size: 9px; }
.header .ref  { font-size: 8px; text-align: left; text-decoration: underline; margin-top: 2px; }
.header .title-edt { font-size: 11px; font-weight: bold; text-align: center; margin-top: 4px; }
.header .period {
    font-size: 10px; font-weight: bold; text-align: center;
    background: #ffff00; display: inline-block; padding: 1px 6px; margin-top: 2px;
}

/* ── Tableau emploi du temps ────────────────────── */
.edt-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 6px;
}
.edt-table th, .edt-table td {
    border: 1.5px solid #555;
    text-align: center;
    vertical-align: middle;
    padding: 4px 3px;
}
.edt-table th {
    background: #d9d9d9;
    font-size: 10px;
    font-weight: bold;
    height: 12mm;
}
.edt-table td {
    height: 42mm;
    vertical-align: middle;
}
.cell-course  { font-weight: bold; font-size: 8.5px; }
.cell-room    { color: #ff00ff; font-weight: bold; font-size: 8px; }
.cell-prof    { font-size: 7.5px; }
.cell-time    { font-weight: bold; font-size: 8.5px; }

/* ── Légende ────────────────────────────────────── */
.legend-table {
    width: auto;
    border-collapse: collapse;
    margin-bottom: 6px;
    min-width: 150mm;
}
.legend-table th, .legend-table td {
    border: 0.5px solid #555;
    padding: 2px 6px;
    font-size: 8px;
    vertical-align: middle;
}
.legend-table th { background: #d9d9d9; font-weight: bold; }
.legend-color { width: 50mm; }
.legend-name  { width: 100mm; font-weight: bold; }

/* ── Signatures ─────────────────────────────────── */
.sig-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}
.sig-table td {
    width: 50%;
    vertical-align: top;
    padding: 4px 6px;
    font-size: 9px;
}
.sig-table .sig-right { text-align: right; }
.sig-name { font-weight: bold; margin-top: 8mm; }

/* ── NB ─────────────────────────────────────────── */
.nb { font-weight: bold; font-size: 9px; margin-bottom: 4px; }
</style>
</head>
<body>

{{-- ── ENTÊTE ──────────────────────────────────────── --}}
<div class="header">
    <h1>{{ $school_name }}</h1>
    <h2>{{ $school_name2 }}</h2>
    <h3>{{ $school_name3 }}</h3>
    <div class="dots">.......................................</div>
    <div class="ref">{{ $ref_code }}</div>
    <div class="title-edt">Emploi du temps {{ $class_name }}</div>
    @if($period)
        <div style="text-align:center;margin-top:3px;">
            <span class="period">du {{ $period }}</span>
        </div>
    @endif
</div>

{{-- ── TABLEAU EDT ──────────────────────────────────── --}}
@php
    $daysOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $daysFr    = [
        'monday'    => 'LUNDI',
        'tuesday'   => 'MARDI',
        'wednesday' => 'MERCREDI',
        'thursday'  => 'JEUDI',
        'friday'    => 'VENDREDI',
        'saturday'  => 'SAMEDI',
        'sunday'    => 'DIMANCHE',
    ];
    // Jours actifs (qui ont au moins un cours)
    $activeDays = array_values(array_filter($daysOrder, fn($d) => !empty($schedule[$d])));
    if (empty($activeDays)) $activeDays = array_slice($daysOrder, 0, 6);

    // Nombre max de cours par jour
    $maxSlots = max(array_map(fn($d) => count($schedule[$d] ?? []), $activeDays));
    if ($maxSlots < 1) $maxSlots = 1;

    // Palette couleurs
    $palette = [
        '#4CAF50','#A8D8EA','#FFD700','#FF9800',
        '#CE93D8','#80CBC4','#F48FB1','#B0BEC5',
    ];
    $colorMap = [];
    $ci = 0;
    foreach ($activeDays as $d) {
        foreach ($schedule[$d] ?? [] as $course) {
            $name = $course['course_name'] ?? '';
            if ($name && !isset($colorMap[$name])) {
                $colorMap[$name] = $palette[$ci % count($palette)];
                $ci++;
            }
        }
    }
@endphp

<table class="edt-table">
    <thead>
        <tr>
            @foreach($activeDays as $d)
                <th>{{ $daysFr[$d] ?? strtoupper($d) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @for($si = 0; $si < $maxSlots; $si++)
            <tr>
                @foreach($activeDays as $d)
                    @php
                        $courses = $schedule[$d] ?? [];
                        $course  = $courses[$si] ?? null;
                        $bg      = $course ? ($colorMap[$course['course_name'] ?? ''] ?? '#ffffff') : '#ffffff';
                    @endphp
                    <td style="background:{{ $bg }};">
                        @if($course)
                            <div class="cell-course">{{ $course['course_name'] ?? '' }}</div>
                            @if(!empty($course['room']))
                                <div class="cell-room">({{ $course['room'] }})</div>
                            @endif
                            @foreach($course['professors'] ?? [] as $prof)
                                <div class="cell-prof">{{ $prof }}</div>
                            @endforeach
                            @if(!empty($course['time_slot']))
                                <div class="cell-time">({{ $course['time_slot'] }})</div>
                            @endif
                        @endif
                    </td>
                @endforeach
            </tr>
        @endfor
    </tbody>
</table>

{{-- ── NB ────────────────────────────────────────── --}}
@if($nb_note)
    <div class="nb">NB : {{ $nb_note }}</div>
@endif

{{-- ── LÉGENDE ──────────────────────────────────── --}}
@if(!empty($colorMap))
<table class="legend-table">
    <thead>
        <tr>
            <th class="legend-color">Légende (Couleur)</th>
            <th class="legend-name">Attribution des Couleurs sur l'emploi du temps</th>
        </tr>
    </thead>
    <tbody>
        @foreach($colorMap as $courseName => $color)
        <tr>
            <td style="background:{{ $color }};">&nbsp;</td>
            <td class="legend-name">{{ $courseName }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── SIGNATURES ───────────────────────────────── --}}
<table class="sig-table">
    <tr>
        <td>
            <div>{{ $signature_left }}</div>
            <div class="sig-name">{{ $name_left }}</div>
        </td>
        <td class="sig-right">
            <div>{{ $signature_right }}</div>
            <div class="sig-name">{{ $name_right }}</div>
        </td>
    </tr>
</table>

</body>
</html>
