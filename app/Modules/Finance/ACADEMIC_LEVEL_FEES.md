# Système de Tarifs par Niveau Académique

## Vue d'ensemble

Ce système permet de définir les tarifs **AVANT** l'acceptation des étudiants, résolvant ainsi le problème de dépendance circulaire.

## Architecture

### Table: `academic_level_fees`

Stocke les tarifs par:
- **Année académique** (academic_year_id)
- **Département/Filière** (department_id)
- **Niveau d'études** (study_level)

### Niveaux d'études supportés

- **Licence**: L1, L2, L3
- **Master**: M1, M2
- **Prépa**: PREPA1, PREPA2
- **Ingénieur Spécialité**: ING1, ING2, ING3, ING4, ING5

### Types de tarifs

Pour chaque niveau, on définit:
- `registration_fee`: Frais d'inscription
- `uemoa_training_fee`: Frais de formation UEMOA
- `non_uemoa_training_fee`: Frais de formation non-UEMOA
- `exempted_training_fee`: Frais de formation exonéré

## Flux de travail

### 1. Configuration initiale (Admin)

```
1. Créer l'année académique 2025-2026
2. Définir les tarifs pour chaque niveau:
   - L1 Production Animale: 500 000 FCFA (UEMOA), 1 000 000 FCFA (non-UEMOA)
   - PREPA1 Génie Civil: 600 000 FCFA (UEMOA), 1 200 000 FCFA (non-UEMOA)
   - ING1 Génie Civil: 800 000 FCFA (UEMOA), 1 500 000 FCFA (non-UEMOA)
```

### 2. Acceptation d'un étudiant

```
1. L'admin accepte un étudiant en attente
2. Le système récupère automatiquement le tarif selon:
   - Son année académique
   - Son département
   - Son niveau d'études (study_level)
   - Son origine (UEMOA/non-UEMOA/exonéré)
3. Le tarif est appliqué à l'étudiant
```

## API Endpoints

### Lister les tarifs
```http
GET /api/finance/academic-level-fees
Query params:
  - academic_year_id (optional)
  - department_id (optional)
  - study_level (optional)
  - is_active (optional)
```

### Créer un tarif
```http
POST /api/finance/academic-level-fees
Body:
{
  "academic_year_id": 1,
  "department_id": 5,
  "study_level": "L1",
  "registration_fee": 50000,
  "uemoa_training_fee": 450000,
  "non_uemoa_training_fee": 950000,
  "exempted_training_fee": 100000,
  "is_active": true
}
```

### Mettre à jour un tarif
```http
PUT /api/finance/academic-level-fees/{uuid}
Body: (mêmes champs que création, tous optionnels)
```

### Supprimer un tarif
```http
DELETE /api/finance/academic-level-fees/{uuid}
```

### Obtenir le tarif pour un étudiant
```http
POST /api/finance/academic-level-fees/student-fee
Body:
{
  "academic_year_id": 1,
  "department_id": 5,
  "study_level": "L1",
  "origin": "uemoa"
}

Response:
{
  "success": true,
  "data": {
    "registration_fee": 50000,
    "training_fee": 450000,
    "total_fee": 500000
  }
}
```

## Avantages

✅ **Indépendant des étudiants**: Les tarifs existent avant l'acceptation
✅ **Flexible**: Tarifs différents par département et niveau
✅ **Simple**: Un seul tarif par combinaison (année + département + niveau)
✅ **Évolutif**: Facile d'ajouter de nouveaux niveaux

## Migration

Pour appliquer les changements:

```bash
cd backend-modules
php artisan migrate
```

## Exemple d'utilisation

### Scénario: Définir les tarifs pour 2025-2026

```php
// 1. Licence 1 Production Animale
POST /api/finance/academic-level-fees
{
  "academic_year_id": 1,
  "department_id": 5,
  "study_level": "L1",
  "registration_fee": 50000,
  "uemoa_training_fee": 450000,
  "non_uemoa_training_fee": 950000,
  "exempted_training_fee": 100000
}

// 2. Prépa 1 Génie Civil
POST /api/finance/academic-level-fees
{
  "academic_year_id": 1,
  "department_id": 3,
  "study_level": "PREPA1",
  "registration_fee": 60000,
  "uemoa_training_fee": 540000,
  "non_uemoa_training_fee": 1140000,
  "exempted_training_fee": 120000
}

// 3. Ingénieur Spécialité 1 Génie Civil
POST /api/finance/academic-level-fees
{
  "academic_year_id": 1,
  "department_id": 3,
  "study_level": "ING1",
  "registration_fee": 80000,
  "uemoa_training_fee": 720000,
  "non_uemoa_training_fee": 1420000,
  "exempted_training_fee": 150000
}
```
