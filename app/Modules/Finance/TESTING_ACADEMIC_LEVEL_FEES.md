# Test du Système de Tarifs par Niveau Académique

## Prérequis

Assurez-vous d'avoir:
- Une année académique active
- Des départements créés

## Test 1: Créer des tarifs pour différents niveaux

### Licence 1 - Production Animale

```bash
curl -X POST http://localhost:8000/api/finance/academic-level-fees \
  -H "Content-Type: application/json" \
  -d '{
    "academic_year_id": 1,
    "department_id": 5,
    "study_level": "L1",
    "registration_fee": 50000,
    "uemoa_training_fee": 450000,
    "non_uemoa_training_fee": 950000,
    "exempted_training_fee": 100000,
    "is_active": true
  }'
```

### Prépa 1 - Génie Civil

```bash
curl -X POST http://localhost:8000/api/finance/academic-level-fees \
  -H "Content-Type: application/json" \
  -d '{
    "academic_year_id": 1,
    "department_id": 3,
    "study_level": "PREPA1",
    "registration_fee": 60000,
    "uemoa_training_fee": 540000,
    "non_uemoa_training_fee": 1140000,
    "exempted_training_fee": 120000,
    "is_active": true
  }'
```

### Ingénieur Spécialité 1 - Génie Civil

```bash
curl -X POST http://localhost:8000/api/finance/academic-level-fees \
  -H "Content-Type: application/json" \
  -d '{
    "academic_year_id": 1,
    "department_id": 3,
    "study_level": "ING1",
    "registration_fee": 80000,
    "uemoa_training_fee": 720000,
    "non_uemoa_training_fee": 1420000,
    "exempted_training_fee": 150000,
    "is_active": true
  }'
```

## Test 2: Lister tous les tarifs

```bash
curl -X GET http://localhost:8000/api/finance/academic-level-fees
```

## Test 3: Filtrer par département

```bash
curl -X GET "http://localhost:8000/api/finance/academic-level-fees?department_id=3"
```

## Test 4: Obtenir le tarif pour un étudiant UEMOA en L1

```bash
curl -X POST http://localhost:8000/api/finance/academic-level-fees/student-fee \
  -H "Content-Type: application/json" \
  -d '{
    "academic_year_id": 1,
    "department_id": 5,
    "study_level": "L1",
    "origin": "uemoa"
  }'
```

Réponse attendue:
```json
{
  "success": true,
  "data": {
    "registration_fee": 50000,
    "training_fee": 450000,
    "total_fee": 500000
  }
}
```

## Test 5: Obtenir le tarif pour un étudiant non-UEMOA en PREPA1

```bash
curl -X POST http://localhost:8000/api/finance/academic-level-fees/student-fee \
  -H "Content-Type: application/json" \
  -d '{
    "academic_year_id": 1,
    "department_id": 3,
    "study_level": "PREPA1",
    "origin": "non_uemoa"
  }'
```

Réponse attendue:
```json
{
  "success": true,
  "data": {
    "registration_fee": 60000,
    "training_fee": 1140000,
    "total_fee": 1200000
  }
}
```

## Test 6: Mettre à jour un tarif

```bash
curl -X PUT http://localhost:8000/api/finance/academic-level-fees/{uuid} \
  -H "Content-Type: application/json" \
  -d '{
    "uemoa_training_fee": 480000
  }'
```

## Test 7: Supprimer un tarif

```bash
curl -X DELETE http://localhost:8000/api/finance/academic-level-fees/{uuid}
```

## Vérification dans la base de données

```sql
-- Voir tous les tarifs
SELECT 
  alf.study_level,
  d.name as department,
  ay.name as academic_year,
  alf.registration_fee,
  alf.uemoa_training_fee,
  alf.non_uemoa_training_fee,
  alf.exempted_training_fee
FROM academic_level_fees alf
JOIN departments d ON alf.department_id = d.id
JOIN academic_years ay ON alf.academic_year_id = ay.id
WHERE alf.is_active = 1;
```
