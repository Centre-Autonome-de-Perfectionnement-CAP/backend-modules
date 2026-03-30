# Module EmploiDuTemps

## Description

Le module **EmploiDuTemps** gère la planification complète des cours avec détection automatique de conflits. Il permet de créer et gérer des emplois du temps pour les groupes de classe, les professeurs et les salles, en tenant compte de la masse horaire et des disponibilités.

## Architecture

### Modèles

#### 1. **Building** (Bâtiment)
Représente un bâtiment physique contenant des salles.

**Champs:**
- `name`: Nom du bâtiment
- `code`: Code unique du bâtiment
- `address`: Adresse (optionnel)
- `description`: Description (optionnel)
- `is_active`: status actif/inactif

**Relations:**
- `rooms`: Liste des salles du bâtiment

#### 2. **Room** (Salle)
Représente une salle de classe avec ses caractéristiques.

**Champs:**
- `building_id`: Référence au bâtiment
- `name`: Nom de la salle
- `code`: Code unique de la salle
- `capacity`: Capacité maximale (nombre de places)
- `room_type`: Type de salle (amphitheater, classroom, lab, computer_lab, conference)
- `equipment`: Équipements disponibles (JSON: projecteur, climatisation, etc.)
- `is_available`: Disponibilité de la salle

**Relations:**
- `building`: Bâtiment parent
- `scheduledCourses`: Cours planifiés dans cette salle

#### 3. **TimeSlot** (Créneau horaire)
Définit un créneau horaire réutilisable.

**Champs:**
- `day_of_week`: Jour de la semaine (monday-sunday)
- `start_time`: Heure de début (format HH:MM)
- `end_time`: Heure de fin (format HH:MM)
- `type`: Type de créneau (lecture, td, tp, exam)
- `name`: Nom optionnel du créneau

**Propriétés calculées:**
- `duration_in_minutes`: Durée en minutes
- `duration_in_hours`: Durée en heures

**Relations:**
- `scheduledCourses`: Cours planifiés dans ce créneau

#### 4. **ScheduledCourse** (Cours planifié)
Représente un cours planifié avec toutes ses informations.

**Champs:**
- `program_id`: Référence au programme (CourseElement + Professor + ClassGroup)
- `time_slot_id`: Référence au créneau horaire
- `room_id`: Référence à la salle
- `start_date`: Date de début du cours
- `end_date`: Date de fin (calculée ou manuelle)
- `total_hours`: Masse horaire totale prévue
- `hours_completed`: Heures effectuées
- `is_recurring`: Cours récurrent hebdomadaire (true/false)
- `recurrence_end_date`: Date de fin de récurrence
- `excluded_dates`: Dates exclues (jours fériés, vacances) - JSON
- `notes`: Notes additionnelles
- `is_cancelled`: status d'annulation

**Propriétés calculées:**
- `remaining_hours`: Heures restantes
- `progress_percentage`: Pourcentage de progression
- `estimated_end_date`: Date de fin estimée selon la masse horaire

**Relations:**
- `program`: Programme de cours
- `timeSlot`: Créneau horaire
- `room`: Salle
- `courseElement`: Élément de cours (via program)
- `professor`: Professeur (via program)
- `classGroup`: Groupe de classe (via program)

### Services

#### 1. **BuildingService**
Gestion CRUD des bâtiments avec filtres de recherche.

#### 2. **RoomService**
- Gestion CRUD des salles
- Recherche de salles disponibles pour un créneau donné
- Filtrage par capacité, type, bâtiment

#### 3. **TimeSlotService**
- Gestion CRUD des créneaux horaires
- Tri automatique par jour et heure
- Récupération des créneaux d'un jour spécifique

#### 4. **ScheduledCourseService**
- Gestion complète des cours planifiés
- Calcul automatique de la date de fin selon la masse horaire
- Gestion des cours récurrents avec exclusion de dates
- Vues d'emploi du temps par groupe, professeur ou salle
- Suivi de la progression (heures effectuées)

#### 5. **ConflictDetectionService**
Service de détection automatique de 4 types de conflits:

1. **Conflit de salle**: Même salle déjà occupée au même créneau
2. **Conflit de professeur**: Professeur ayant déjà un cours au même créneau
3. **Conflit de groupe de classe**: Groupe ayant déjà un cours au même créneau
4. **Conflit de capacité**: Salle trop petite pour le nombre d'étudiants

**Fonctionnement:**
- Vérification avant création/modification d'un cours
- Retour détaillé avec informations sur le cours en conflit
- Gestion des périodes de récurrence

## API Endpoints

### Buildings (Bâtiments)

```
GET    /api/emploi-temps/buildings              - Liste des bâtiments
POST   /api/emploi-temps/buildings              - Créer un bâtiment
GET    /api/emploi-temps/buildings/{id}         - Détails d'un bâtiment
PUT    /api/emploi-temps/buildings/{id}         - Modifier un bâtiment
DELETE /api/emploi-temps/buildings/{id}         - Supprimer un bâtiment
```

**Filtres disponibles:**
- `search`: Recherche par nom, code ou adresse
- `is_active`: Filtrer par status actif/inactif
- `sort_by`, `sort_order`: Tri personnalisé

### Rooms (Salles)

```
GET    /api/emploi-temps/rooms                  - Liste des salles
POST   /api/emploi-temps/rooms                  - Créer une salle
GET    /api/emploi-temps/rooms/{id}             - Détails d'une salle
PUT    /api/emploi-temps/rooms/{id}             - Modifier une salle
DELETE /api/emploi-temps/rooms/{id}             - Supprimer une salle
GET    /api/emploi-temps/rooms-available        - Salles disponibles pour un créneau
```

**Filtres disponibles:**
- `search`: Recherche par nom ou code
- `building_id`: Filtrer par bâtiment
- `room_type`: Filtrer par type de salle
- `is_available`: Filtrer par disponibilité
- `min_capacity`: Capacité minimale requise

**Endpoint spécial - Salles disponibles:**
```
GET /api/emploi-temps/rooms-available?time_slot_id={id}&date={YYYY-MM-DD}&min_capacity={number}
```

### TimeSlots (Créneaux horaires)

```
GET    /api/emploi-temps/time-slots             - Liste des créneaux
POST   /api/emploi-temps/time-slots             - Créer un créneau
GET    /api/emploi-temps/time-slots/{id}        - Détails d'un créneau
PUT    /api/emploi-temps/time-slots/{id}        - Modifier un créneau
DELETE /api/emploi-temps/time-slots/{id}        - Supprimer un créneau
GET    /api/emploi-temps/time-slots/day/{day}   - Créneaux d'un jour spécifique
```

**Filtres disponibles:**
- `day_of_week`: Filtrer par jour (monday, tuesday, etc.)
- `type`: Filtrer par type (lecture, td, tp, exam)
- `search`: Recherche par nom

### ScheduledCourses (Cours planifiés)

```
GET    /api/emploi-temps/scheduled-courses                      - Liste des cours planifiés
POST   /api/emploi-temps/scheduled-courses                      - Créer un cours planifié
GET    /api/emploi-temps/scheduled-courses/{id}                 - Détails d'un cours
PUT    /api/emploi-temps/scheduled-courses/{id}                 - Modifier un cours
DELETE /api/emploi-temps/scheduled-courses/{id}                 - Supprimer un cours
POST   /api/emploi-temps/scheduled-courses/check-conflicts      - Vérifier les conflits
POST   /api/emploi-temps/scheduled-courses/{id}/cancel          - Annuler un cours
POST   /api/emploi-temps/scheduled-courses/{id}/update-hours    - Mettre à jour les heures effectuées
POST   /api/emploi-temps/scheduled-courses/{id}/exclude-date    - Exclure une date (cours récurrent)
GET    /api/emploi-temps/scheduled-courses/{id}/occurrences     - Obtenir toutes les occurrences
```

**Filtres disponibles:**
- `search`: Recherche par nom de cours
- `program_id`: Filtrer par programme
- `time_slot_id`: Filtrer par créneau
- `room_id`: Filtrer par salle
- `class_group_id`: Filtrer par groupe de classe
- `professor_id`: Filtrer par professeur
- `start_date`, `end_date`: Filtrer par période
- `is_cancelled`: Filtrer les cours annulés
- `is_recurring`: Filtrer les cours récurrents

### Schedule Views (Vues d'emploi du temps)

```
GET /api/emploi-temps/schedule/class-group/{classGroupId}   - Emploi du temps d'un groupe
GET /api/emploi-temps/schedule/professor/{professorId}      - Emploi du temps d'un professeur
GET /api/emploi-temps/schedule/room/{roomId}                - Emploi du temps d'une salle
```

**Paramètres optionnels:**
- `start_date`: Date de début (YYYY-MM-DD)
- `end_date`: Date de fin (YYYY-MM-DD)

## Exemples d'utilisation

### 1. Créer un cours planifié

```json
POST /api/emploi-temps/scheduled-courses
{
  "program_id": 1,
  "time_slot_id": 5,
  "room_id": 3,
  "start_date": "2025-09-15",
  "total_hours": 42,
  "is_recurring": true,
  "notes": "Cours de mathématiques L1"
}
```

**Réponse:** Le système calcule automatiquement la date de fin estimée selon la masse horaire et le créneau.

### 2. Vérifier les conflits avant création

```json
POST /api/emploi-temps/scheduled-courses/check-conflicts
{
  "program_id": 1,
  "time_slot_id": 5,
  "room_id": 3,
  "start_date": "2025-09-15",
  "is_recurring": true,
  "recurrence_end_date": "2025-12-20"
}
```

**Réponse en cas de conflit:**
```json
{
  "has_conflicts": true,
  "conflicts": [
    {
      "type": "room",
      "message": "La salle est déjà occupée à ce créneau horaire",
      "conflicting_course": {
        "id": 12,
        "course_name": "Physique générale",
        "class_group": "L2 Info",
        "start_date": "2025-09-10"
      }
    }
  ]
}
```

### 3. Récupérer l'emploi du temps d'un groupe

```
GET /api/emploi-temps/schedule/class-group/5?start_date=2025-09-01&end_date=2025-12-31
```

### 4. Exclure une date d'un cours récurrent (jour férié)

```json
POST /api/emploi-temps/scheduled-courses/15/exclude-date
{
  "date": "2025-11-01"
}
```

## Calculs automatiques

### 1. Date de fin estimée
Basée sur:
- Masse horaire totale (`total_hours`)
- Durée du créneau horaire (`duration_in_hours`)
- Date de début
- Formule: `weeksNeeded = ceil(total_hours / hours_per_week)`

### 2. Progression du cours
- `remaining_hours = total_hours - hours_completed`
- `progress_percentage = (hours_completed / total_hours) * 100`

### 3. Occurrences d'un cours récurrent
Génère toutes les dates de cours entre `start_date` et `recurrence_end_date`, en excluant les dates dans `excluded_dates`.

## Sécurité

- Toutes les routes sont protégées par le middleware `auth:sanctum`
- Validation stricte des données via les FormRequest
- Gestion des exceptions avec logs détaillés
- Transactions DB pour les opérations critiques

## Relations avec les autres modules

- **Cours**: Utilise `Program` qui lie CourseElement + Professor + ClassGroup
- **RH**: Détection de conflits sur les professeurs
- **Inscription**: Vérifie la capacité selon le nombre d'étudiants dans ClassGroup

## Notes techniques

### Gestion de la récurrence
- Les cours récurrents sont définis une seule fois
- La méthode `getOccurrences()` génère dynamiquement toutes les dates
- Possibilité d'exclure des dates spécifiques (jours fériés, vacances)

### Détection de conflits
- Vérification en temps réel avant création/modification
- Gestion des chevauchements de périodes pour les cours récurrents
- Retour détaillé avec informations complètes sur les conflits

### Performance
- Indexes sur les colonnes fréquemment utilisées
- Eager loading des relations pour éviter N+1 queries
- Pagination sur toutes les listes
