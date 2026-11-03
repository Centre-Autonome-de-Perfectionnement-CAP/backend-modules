<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202b3c; font-size: 10px; }
        h1 { margin: 0 0 5px; color: #174b35; font-size: 18px; text-align: center; }
        .subtitle { margin-bottom: 18px; color: #596579; text-align: center; }
        .summary { margin-bottom: 14px; text-align: right; color: #596579; }
        .group { margin-bottom: 20px; }
        h2 { margin: 0 0 8px; padding: 7px 9px; background: #e8f1ec; color: #174b35; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 7px; border: 1px solid #cbd5df; text-align: left; }
        th { background: #f2f5f7; color: #263648; }
        .empty { padding: 16px; border: 1px solid #cbd5df; text-align: center; color: #596579; }
        .footer { margin-top: 20px; color: #687386; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <h1>Liste des étudiants inscrits au CAP</h1>
    <div class="subtitle">Première et deuxième année — années académiques depuis 2023</div>
    <div class="summary">Total des inscriptions listées : {{ $totalStudents }}</div>

    @forelse ($groups as $group)
        <section class="group">
            <h2>Année académique : {{ $group['academic_year'] }} — Filière : {{ $group['department_name'] }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom</th>
                        <th>Prénoms</th>
                        <th>Niveau</th>
                        <th>Date de naissance</th>
                        <th>Lieu de naissance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['students'] as $student)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $student->last_name }}</td>
                            <td>{{ $student->first_names }}</td>
                            <td>{{ $student->level_number === 1 ? '1re année' : '2e année' }}</td>
                            <td>{{ $student->birth_date ? \Illuminate\Support\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Non renseignée' }}</td>
                            <td>{{ $student->birth_place ?: 'Non renseigné' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <div class="empty">Aucun étudiant inscrit en première ou deuxième année depuis 2023.</div>
    @endforelse

    <div class="footer">Document généré le {{ $generatedAt }}</div>
</body>
</html>
