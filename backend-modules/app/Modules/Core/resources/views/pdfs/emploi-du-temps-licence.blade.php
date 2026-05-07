<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emploi du Temps - {{ $classGroup->name ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            margin-bottom: 3px;
        }
        .header p {
            font-size: 10px;
            margin: 2px 0;
        }
        .info-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 5px;
            font-size: 10px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 9px;
        }
        .schedule-table th {
            background-color: #333;
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
        }
        .schedule-table td {
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000;
        }
        .schedule-table td.horaire-cell {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .course-cell {
            min-height: 50px;
        }
        .course-name {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .course-details {
            font-size: 7px;
        }
        .week-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            text-align: center;
            background-color: #e0e0e0;
            padding: 8px;
        }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .note-section {
            margin-top: 15px;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMPLOI DU TEMPS</h1>
        <h2>{{ $cycleName ?? 'N/A' }} - {{ $departmentName ?? 'N/A' }}</h2>
        <p><strong>Classe:</strong> {{ $classGroup->name ?? 'N/A' }} | <strong>Niveau:</strong> {{ $level ?? 'N/A' }}</p>
        <p><strong>Année Académique:</strong> {{ $anneeAcamedique ?? 'N/A' }}</p>
        @if($startDate && $endDate)
        <p><strong>Période:</strong> du {{ $startDate }} au {{ $endDate }}</p>
        @endif
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td><strong>FILIÈRE:</strong> {{ $departmentName ?? 'N/A' }}</td>
                <td style="text-align: right;"><strong>{{ $level ?? 'N/A' }} - {{ $classGroup->name ?? 'N/A' }}</strong></td>
            </tr>
        </table>
    </div>

@if(isset($scheduleByWeek) && count($scheduleByWeek) > 0)
    @foreach($scheduleByWeek as $weekNumber => $weekData)
        @if($weekNumber > 1)
            <div style="page-break-before: always;"></div>
        @endif
        
        <div class="week-title">{{ $weekData['title'] }}</div>
        
        <table class="schedule-table">
            <thead>
                <tr>
                    <th style="width: 80px;">HORAIRES</th>
                    @foreach($weekData['dates'] as $date)
                        <th>
                            {{ $date['day_name'] }}<br>
                            <strong>{{ $date['date'] }}</strong>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($weekData['timeSlots'] as $timeSlot => $data)
                <tr>
                    <td class="horaire-cell">
                        {{ $data['time']['start'] }}<br>-<br>{{ $data['time']['end'] }}
                    </td>
                    @foreach($weekData['dates'] as $dateKey => $date)
                    <td class="course-cell">
                        @if(isset($data['courses'][$dateKey]) && count($data['courses'][$dateKey]) > 0)
                            @foreach($data['courses'][$dateKey] as $course)
                                <div style="margin-bottom: 5px;">
                                    <div class="course-name">{{ $course->program->courseElementProfessor->courseElement->name ?? 'N/A' }}</div>
                                    <div class="course-details">
                                        <strong>Prof:</strong> {{ $course->program->courseElementProfessor->professor->first_name ?? '' }} {{ $course->program->courseElementProfessor->professor->last_name ?? '' }}<br>
                                        <strong>Salle:</strong> {{ $course->room->name ?? 'N/A' }}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@else
    <p style="text-align: center; padding: 20px; color: #999;">Aucun cours programmé pour cette période</p>
@endif

    <div class="footer">
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
