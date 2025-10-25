# Progi CAP API – Routes par module

Ce document liste les endpoints disponibles par module, avec l’authentification requise et des exemples rapides.

- Base URL (développement): `http://127.0.0.1:8000`
- Authentification: Bearer token Sanctum
  - Header: `Authorization: Bearer <token>`

---

## Module Auth

- POST `/api/auth/login`
  - Entrée (JSON):
    - `email` string
    - `password` string
  - Sortie 200 (JSON):
    ```json
    {"access_token":"<string>","token_type":"Bearer","user":{}}
    ```
  - Erreurs: 422
- POST `/api/auth/register`
  - Entrée (JSON):
    - `first_name` string (opt)
    - `last_name` string (opt)
    - `email` string (email)
    - `password` string (>=8)
    - `phone` string (opt)
  - Sortie 201 (JSON): même structure que login
  - Erreurs: 422
- POST `/api/auth/logout` [auth]
  - Entrée: header Authorization uniquement
  - Sortie 200: `{ "message": "Logged out" }`
  - Erreurs: 401
- GET `/api/auth/me` [auth]
  - Entrée: header Authorization
  - Sortie 200: objet User
  - Erreurs: 401

---

## Module Inscription

### Pending Students

- POST `/api/pending-students`
  - Entrée (JSON): données du dossier (selon modèle PendingStudent/PersonalInformation)
  - Sortie 201: `{ success: true, data: {...} }`
  - Erreurs: 422
- POST `/api/pending-students/{pendingStudent}/documents`
  - Entrée (multipart/form-data): fichiers
  - Sortie 201: `{ success: true }`
  - Erreurs: 404, 422
- GET `/api/pending-students` [auth]
  - Sortie 200: liste paginée/collection
- GET `/api/pending-students/{pendingStudent}` [auth]
  - Sortie 200: objet PendingStudent détaillé
  - Erreurs: 404
- PUT `/api/pending-students/{pendingStudent}` [auth]
  - Entrée (JSON): champs à mettre à jour
  - Sortie 200: `{ success: true, data: {...} }`
  - Erreurs: 404, 422
- DELETE `/api/pending-students/{pendingStudent}` [auth]
  - Sortie 200: `{ success: true }`
  - Erreurs: 404
- GET `/api/pending-students/{pendingStudent}/documents` [auth]
  - Sortie 200: `{ success: true, data: [ ... ] }`

### Submissions (périodes globales)

- GET `/api/submissions/active-periods`
  - Sortie 200: `{ success: true, data: SubmissionPeriod[] }`
- GET `/api/submissions/active-reclamation-periods`
  - Sortie 200: `{ success: true, data: ReclamationPeriod[] }`
- POST `/api/submissions/check-status`
  - Entrée (JSON): `{ tracking_code: string }`
  - Sortie 200: statut du dossier
- POST `/api/submissions/check-reclamation-status`
  - Entrée (JSON): `{ tracking_code: string }`
  - Sortie 200: statut
- POST `/api/submissions` [auth]
  - Entrée (JSON): `{ academic_year_id: number, department_id: number, start_date: date, end_date: date }`
  - Sortie 201: `{ success: true, data: SubmissionPeriod }`
  - Erreurs: 401, 422
- PUT `/api/submissions/{submissionPeriod}` [auth]
  - Entrée (JSON): champs optionnels ci-dessus
  - Sortie 200: `{ success: true, data: SubmissionPeriod }`
- DELETE `/api/submissions/{submissionPeriod}` [auth]
  - Sortie 200: `{ success: true }`

### Academic Years

- GET `/api/academic-years`
  - Sortie 200: `{ success: true, data: AcademicYear[] }`
- GET `/api/academic-years/{academicYear}`
  - Sortie 200: `{ success: true, data: AcademicYear{ submissionPeriods[], reclamationPeriods[] } }`
- POST `/api/academic-years` [auth]
  - Entrée (JSON):
    - `year_start` date
    - `year_end` date (> start)
    - `submission_start` date (>= year_start)
    - `submission_end` date (> submission_start, < year_end)
    - `departments` number[] (opt)
  - Sortie 201: `{ success: true, data: AcademicYear }`
  - Erreurs: 401, 422
- PUT `/api/academic-years/{academicYear}` [auth]
  - Entrée (JSON, optionnel): `year_start?`, `year_end?`, `submission_start?`, `submission_end?`, `departments?` (ajoute périodes manquantes)
  - Sortie 200: `{ success: true, data: AcademicYear }`
- DELETE `/api/academic-years/{academicYear}` [auth]
  - Sortie 200: `{ success: true }`
- POST `/api/academic-years/{academicYear}/periods` [auth]
  - Entrée (JSON): `{ start_date: date, end_date: date, departments: number[] }`
  - Sortie 201: `{ success: true }`
  - Erreurs: 401, 422 (chevauchement)
- PUT `/api/academic-years/{academicYear}/periods` [auth]
  - Entrée (JSON): `{ start_date, old_end_date, new_end_date, departments: number[] }`
  - Sortie 200: `{ success: true }`
- DELETE `/api/academic-years/{academicYear}/periods` [auth]
  - Entrée (JSON): `{ start_date, end_date, departments: number[] }`
  - Sortie 200: `{ success: true }`

### Dossiers (soumission externe)

- GET `/api/dossiers/periods`
  - Query: `cycle?` enum `Licence|Master|Ingénieur`
  - Sortie 200: `{ data: [ { id, department, academic_year, start_date, end_date } ] }`
- POST `/api/dossiers/licence` (multipart)
  - Entrée (multipart): champs perso + fichiers requis (voir OA). Ex:
    - `last_name`, `first_names`, `email`, `birth_date`, `gender`, `contacts[]`, `study_level`,
    - `academic_year_id`, `department_id`, `entry_diploma_id?`
    - Fichiers: `demande_da`, `cv`, `acte_naissance`, `diplome_bac`, `attestation_travail`, `quittance_rectorat`, `quittance_cap`, `diplome_licence?`, `photo?`, `attestation_depot_dossier?`
  - Sortie 201: `{ message, tracking_code }`
  - Erreurs: 400, 422
- POST `/api/dossiers/master` (multipart)
  - Entrée: similaire à licence, avec `diplome_license` requis
  - Sortie 201: `{ message, tracking_code }`
- POST `/api/dossiers/ingenieur/prepa` (multipart)
  - Entrée: similaire + `diplome_licence` requis
  - Sortie 201: `{ message, tracking_code }`
- POST `/api/dossiers/ingenieur/specialite` (multipart)
  - Entrée: `student_id_number`, `department_id`, `academic_year_id`, `certificat_prepa` (file)
  - Sortie 201: `{ message, tracking_code }`
- POST `/api/dossiers/complement/{trackingCode}` (multipart)
  - Entrée: `names[]` (noms de pièces), `files[]` (fichiers)
  - Sortie 201: `{ message }`
- GET `/api/dossiers/{trackingCode}`
  - Sortie 200: `{ data: { personal_information, department, academic_year, documents[], status, message } }`
  - Erreurs: 404

---

## Module Stockage (fichiers)

- GET `/files`
  - Sortie 200: liste des fichiers
- POST `/files`
  - Entrée: multipart (`file`, metadata...)
  - Sortie 201: fichier créé
- GET `/files/public`
- GET `/files/{file}`
- PUT `/files/{file}`
- DELETE `/files/{file}`
- GET `/files/{file}/download`
- GET `/files/{file}/activities`
- POST `/files/{file}/lock` / `unlock`
- Permissions & shares: voir routes, entrées JSON conformes aux contrôleurs (id/roles/users)

---

## Exemples cURL

- Auth – Login
```bash
curl -s -H "Content-Type: application/json" \
  -X POST http://127.0.0.1:8000/api/auth/login \
  -d '{"email":"admin@example.com","password":"secret"}'
```

- Auth – Me
```bash
curl -s -H "Authorization: Bearer <token>" \
  http://127.0.0.1:8000/api/auth/me
```

- Academic Year – Create [auth]
```bash
curl -s -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -X POST http://127.0.0.1:8000/api/academic-years \
  -d '{
    "year_start":"2025-09-01",
    "year_end":"2026-06-30",
    "submission_start":"2025-10-01",
    "submission_end":"2026-01-15",
    "departments":[1,2]
  }'
```

---

## Notes

- Toutes les routes marquées [auth] nécessitent un `Authorization: Bearer <token>` valide.
- Pour les uploads (multipart), utiliser `-F` avec `curl` et envoyer les fichiers en `form-data`.
- La documentation interactive est disponible sur `/api/documentation` (Swagger UI).
