# Tests de Validation - Filtrage par Cohorte

## Tests Manuels à Effectuer

### 1. Test Professeur - Récupération des Classes

#### Sans Cohorte
```bash
curl -X GET "http://localhost:8000/api/notes/professor/my-classes" \
  -H "Authorization: Bearer {token}"
```

**Résultat attendu** : Toutes les classes du professeur

#### Avec Cohorte
```bash
curl -X GET "http://localhost:8000/api/notes/professor/my-classes?cohort=1" \
  -H "Authorization: Bearer {token}"
```

**Résultat attendu** : Classes filtrées par cohorte 1

---

### 2. Test Professeur - Fiche de Notation

#### Sans Cohorte
```bash
curl -X POST "http://localhost:8000/api/notes/professor/grade-sheet" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"program_id": 1}'
```

**Résultat attendu** : Tous les étudiants du programme

#### Avec Cohorte
```bash
curl -X POST "http://localhost:8000/api/notes/professor/grade-sheet" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"program_id": 1, "cohort": "1"}'
```

**Résultat attendu** : Uniquement les étudiants de la cohorte 1

---

### 3. Test Administration - Notes par Filière

#### Avec Cohorte
```bash
curl -X GET "http://localhost:8000/api/notes/admin/grades-by-department-level?academic_year_id=1&department_id=2&cohort=1" \
  -H "Authorization: Bearer {token}"
```

**Résultat attendu** : Programmes avec statistiques filtrées par cohorte 1

---

## Checklist de Validation

### Backend
- [x] Syntaxe PHP validée
- [x] Aucune erreur de compilation
- [ ] Tests manuels API effectués
- [ ] Performance acceptable

### Frontend
- [ ] Sélecteur de cohorte ajouté
- [ ] Paramètre cohort passé dans les appels API
- [ ] Affichage de la cohorte sélectionnée
