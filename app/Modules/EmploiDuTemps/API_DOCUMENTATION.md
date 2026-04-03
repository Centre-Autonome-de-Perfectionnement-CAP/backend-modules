# Documentation API - Module EmploiDuTemps

## Table des matières
1. [Buildings API](#buildings-api)
2. [Rooms API](#rooms-api)
3. [TimeSlots API](#timeslots-api)
4. [ScheduledCourses API](#scheduledcourses-api)
5. [Schedule Views API](#schedule-views-api)

---

## Buildings API

### Liste des bâtiments
```http
GET /api/emploi-temps/buildings
```

**Paramètres de requête:**
- `search` (string, optional): Recherche par nom, code ou adresse
- `is_active` (boolean, optional): Filtrer par statut
- `per_page` (int, optional): Nombre d'éléments par page (défaut: 15)
- `sort_by` (string, optional): Champ de tri (défaut: created_at)
- `sort_order` (string, optional): asc/desc (défaut: desc)

**Exemple de requête:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/buildings?search=Science&is_active=true" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Bâtiments récupérés avec succès",
  "data": {
    "data": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "Bâtiment Sciences",
        "code": "BSC",
        "address": "123 Rue de l'Université",
        "description": "Bâtiment principal des sciences",
        "is_active": true,
        "rooms_count": 25,
        "created_at": "2025-11-13T08:00:00.000000Z",
        "updated_at": "2025-11-13T08:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "total": 1,
    "per_page": 15
  }
}
```

### Créer un bâtiment
```http
POST /api/emploi-temps/buildings
```

**Corps de la requête:**
```json
{
  "name": "Bâtiment A",
  "code": "BA",
  "address": "456 Avenue Principale",
  "description": "Bâtiment administratif et salles de cours",
  "is_active": true
}
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "Bâtiment créé avec succès",
  "data": {
    "id": 2,
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Bâtiment A",
    "code": "BA",
    "address": "456 Avenue Principale",
    "description": "Bâtiment administratif et salles de cours",
    "is_active": true,
    "created_at": "2025-11-13T09:00:00.000000Z",
    "updated_at": "2025-11-13T09:00:00.000000Z"
  }
}
```

---

## Rooms API

### Liste des salles
```http
GET /api/emploi-temps/rooms
```

**Paramètres de requête:**
- `search` (string, optional): Recherche par nom ou code
- `building_id` (int, optional): Filtrer par bâtiment
- `room_type` (string, optional): amphitheater, classroom, lab, computer_lab, conference
- `is_available` (boolean, optional): Filtrer par disponibilité
- `min_capacity` (int, optional): Capacité minimale

**Exemple de requête:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/rooms?building_id=1&min_capacity=50" \
  -H "Authorization: Bearer {token}"
```

### Créer une salle
```http
POST /api/emploi-temps/rooms
```

**Corps de la requête:**
```json
{
  "building_id": 1,
  "name": "Amphi A",
  "code": "BSC-A01",
  "capacity": 200,
  "room_type": "amphitheater",
  "equipment": ["projecteur", "climatisation", "microphones", "tableau_interactif"],
  "is_available": true
}
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "Salle créée avec succès",
  "data": {
    "id": 1,
    "uuid": "770e8400-e29b-41d4-a716-446655440002",
    "building_id": 1,
    "name": "Amphi A",
    "code": "BSC-A01",
    "capacity": 200,
    "room_type": "amphitheater",
    "equipment": ["projecteur", "climatisation", "microphones", "tableau_interactif"],
    "is_available": true,
    "building": {
      "id": 1,
      "name": "Bâtiment Sciences",
      "code": "BSC"
    },
    "created_at": "2025-11-13T09:00:00.000000Z",
    "updated_at": "2025-11-13T09:00:00.000000Z"
  }
}
```

### Salles disponibles
```http
GET /api/emploi-temps/rooms-available
```

**Paramètres de requête (obligatoires):**
- `time_slot_id` (int): ID du créneau horaire
- `date` (date): Date au format YYYY-MM-DD
- `min_capacity` (int, optional): Capacité minimale

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/rooms-available?time_slot_id=3&date=2025-11-15&min_capacity=30" \
  -H "Authorization: Bearer {token}"
```

---

## TimeSlots API

### Liste des créneaux horaires
```http
GET /api/emploi-temps/time-slots
```

**Paramètres de requête:**
- `day_of_week` (string, optional): monday, tuesday, wednesday, thursday, friday, saturday, sunday
- `type` (string, optional): lecture, td, tp, exam
- `search` (string, optional): Recherche par nom

**Exemple de requête:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/time-slots?day_of_week=monday" \
  -H "Authorization: Bearer {token}"
```

### Créer un créneau horaire
```http
POST /api/emploi-temps/time-slots
```

**Corps de la requête:**
```json
{
  "day_of_week": "monday",
  "start_time": "08:00",
  "end_time": "10:00",
  "type": "lecture",
  "name": "Matinée - Bloc 1"
}
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "Créneau horaire créé avec succès",
  "data": {
    "id": 1,
    "uuid": "880e8400-e29b-41d4-a716-446655440003",
    "day_of_week": "monday",
    "start_time": "08:00:00",
    "end_time": "10:00:00",
    "type": "lecture",
    "name": "Matinée - Bloc 1",
    "duration_in_minutes": 120,
    "duration_in_hours": 2,
    "created_at": "2025-11-13T09:00:00.000000Z",
    "updated_at": "2025-11-13T09:00:00.000000Z"
  }
}
```

### Créneaux d'un jour spécifique
```http
GET /api/emploi-temps/time-slots/day/{day}
```

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/time-slots/day/monday" \
  -H "Authorization: Bearer {token}"
```

---

## ScheduledCourses API

### Vérifier les conflits
```http
POST /api/emploi-temps/scheduled-courses/check-conflicts
```

**Corps de la requête:**
```json
{
  "program_id": 5,
  "time_slot_id": 3,
  "room_id": 10,
  "start_date": "2025-09-15",
  "is_recurring": true,
  "recurrence_end_date": "2025-12-20",
  "scheduled_course_id": null
}
```

**Réponse - Aucun conflit (200 OK):**
```json
{
  "success": true,
  "message": "Aucun conflit détecté",
  "data": {
    "has_conflicts": false
  }
}
```

**Réponse - Conflits détectés (200 OK):**
```json
{
  "success": true,
  "message": "Des conflits ont été détectés",
  "data": {
    "has_conflicts": true,
    "conflicts": [
      {
        "type": "room",
        "message": "La salle est déjà occupée à ce créneau horaire",
        "conflicting_course": {
          "id": 12,
          "course_name": "Mathématiques L2",
          "class_group": "L2 Info",
          "start_date": "2025-09-10"
        }
      },
      {
        "type": "professor",
        "message": "Le professeur a déjà un cours à ce créneau horaire",
        "conflicting_course": {
          "id": 15,
          "class_group": "L3 Math",
          "room": "Amphi B",
          "start_date": "2025-09-10"
        }
      }
    ]
  }
}
```

### Créer un cours planifié
```http
POST /api/emploi-temps/scheduled-courses
```

**Corps de la requête:**
```json
{
  "program_id": 5,
  "time_slot_id": 3,
  "room_id": 10,
  "start_date": "2025-09-15",
  "total_hours": 42,
  "is_recurring": true,
  "notes": "Cours de Programmation Orientée Objet"
}
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "Cours planifié créé avec succès",
  "data": {
    "id": 1,
    "uuid": "990e8400-e29b-41d4-a716-446655440004",
    "program_id": 5,
    "time_slot_id": 3,
    "room_id": 10,
    "start_date": "2025-09-15",
    "end_date": "2026-01-15",
    "total_hours": 42.00,
    "hours_completed": 0.00,
    "remaining_hours": 42.00,
    "progress_percentage": 0.00,
    "is_recurring": true,
    "recurrence_end_date": "2026-01-15",
    "excluded_dates": [],
    "notes": "Cours de Programmation Orientée Objet",
    "is_cancelled": false,
    "is_completed": false,
    "estimated_end_date": "2026-01-15",
    "time_slot": {
      "id": 3,
      "day_of_week": "monday",
      "start_time": "10:00:00",
      "end_time": "12:00:00",
      "type": "lecture",
      "duration_in_hours": 2
    },
    "room": {
      "id": 10,
      "name": "Salle 101",
      "code": "BA-101",
      "capacity": 40,
      "building": {
        "id": 1,
        "name": "Bâtiment A"
      }
    },
    "course_element": {
      "id": 8,
      "name": "Programmation Orientée Objet",
      "code": "POO-101",
      "credits": 6
    },
    "professor": {
      "id": 3,
      "first_name": "Jean",
      "last_name": "Dupont",
      "email": "j.dupont@university.com"
    },
    "class_group": {
      "id": 2,
      "group_name": "L2 Info",
      "study_level": "L2"
    },
    "created_at": "2025-11-13T09:30:00.000000Z",
    "updated_at": "2025-11-13T09:30:00.000000Z"
  }
}
```

**Erreur - Conflits détectés (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Des conflits ont été détectés",
  "data": {
    "conflicts": [
      {
        "type": "room",
        "message": "La salle est déjà occupée à ce créneau horaire",
        "conflicting_course": {
          "id": 12,
          "course_name": "Mathématiques L2",
          "class_group": "L2 Info",
          "start_date": "2025-09-10"
        }
      }
    ]
  }
}
```

### Annuler un cours
```http
POST /api/emploi-temps/scheduled-courses/{id}/cancel
```

**Corps de la requête:**
```json
{
  "notes": "Annulation due à l'absence du professeur"
}
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Cours annulé avec succès",
  "data": {
    "id": 1,
    "is_cancelled": true,
    "notes": "Annulation due à l'absence du professeur"
  }
}
```

### Mettre à jour les heures effectuées
```http
POST /api/emploi-temps/scheduled-courses/{id}/update-hours
```

**Corps de la requête:**
```json
{
  "hours_completed": 12.5
}
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Heures effectuées mises à jour avec succès",
  "data": {
    "id": 1,
    "total_hours": 42.00,
    "hours_completed": 12.50,
    "remaining_hours": 29.50,
    "progress_percentage": 29.76
  }
}
```

### Exclure une date (cours récurrent)
```http
POST /api/emploi-temps/scheduled-courses/{id}/exclude-date
```

**Corps de la requête:**
```json
{
  "date": "2025-11-01"
}
```

**Exemple d'utilisation:** Exclure les jours fériés d'un cours récurrent.

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Date exclue avec succès",
  "data": {
    "id": 1,
    "excluded_dates": ["2025-11-01", "2025-12-25"]
  }
}
```

### Obtenir les occurrences d'un cours récurrent
```http
GET /api/emploi-temps/scheduled-courses/{id}/occurrences
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Occurrences récupérées avec succès",
  "data": {
    "occurrences": [
      "2025-09-15",
      "2025-09-22",
      "2025-09-29",
      "2025-10-06",
      "2025-10-13",
      "2025-10-20"
    ],
    "total_occurrences": 6
  }
}
```

---

## Schedule Views API

### Emploi du temps d'un groupe de classe
```http
GET /api/emploi-temps/schedule/class-group/{classGroupId}
```

**Paramètres de requête:**
- `start_date` (date, optional): Date de début (YYYY-MM-DD)
- `end_date` (date, optional): Date de fin (YYYY-MM-DD)

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/emploi-temps/schedule/class-group/5?start_date=2025-09-01&end_date=2025-12-31" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Emploi du temps récupéré avec succès",
  "data": [
    {
      "id": 1,
      "start_date": "2025-09-15",
      "time_slot": {
        "day_of_week": "monday",
        "start_time": "08:00:00",
        "end_time": "10:00:00"
      },
      "room": {
        "name": "Amphi A",
        "code": "BSC-A01"
      },
      "course_element": {
        "name": "Mathématiques",
        "code": "MATH-101"
      },
      "professor": {
        "first_name": "Jean",
        "last_name": "Dupont"
      }
    }
  ]
}
```

### Emploi du temps d'un professeur
```http
GET /api/emploi-temps/schedule/professor/{professorId}
```

**Utilisation identique à l'emploi du temps de groupe de classe.**

### Emploi du temps d'une salle
```http
GET /api/emploi-temps/schedule/room/{roomId}
```

**Utilisation identique à l'emploi du temps de groupe de classe.**

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `422` - Validation échouée ou conflits détectés
- `404` - Ressource non trouvée
- `500` - Erreur serveur

## Authentification

Toutes les routes nécessitent un token Bearer valide:

```bash
Authorization: Bearer {your_access_token}
```
