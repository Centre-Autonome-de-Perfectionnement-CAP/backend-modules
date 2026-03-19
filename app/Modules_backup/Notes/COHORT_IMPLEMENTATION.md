# Implémentation du Support des Cohortes dans le Module Notes

## Vue d'ensemble
Le module Notes supporte maintenant le filtrage par cohorte, similaire à l'implémentation dans le module Inscription. Les cohortes sont basées sur la colonne `cohort` de la table `academic_paths`.

## Modifications Backend

### Services Modifiés

#### 1. LmdGradeService
- **getStudentsByProgram()** : Ajout du paramètre `?string $cohort = null`
  - Filtre les étudiants par cohorte via `academic_paths.cohort`
  
- **getProfessorClassesByCycle()** : Paramètre cohort déjà présent mais non utilisé (nettoyé)

- **getGradeSheet()** : Supporte le paramètre cohort

- **getGradesByFilters()** : Nouvelle implémentation avec support cohort
  - Filtre les programmes selon les critères (année, filière, niveau, cohorte)
  - Retourne les statistiques par programme

- **getProgramDetailsForAdmin()** : Ajout du paramètre cohort

- **exportGradesByDepartment()** : Ajout du paramètre cohort

#### 2. OldGradeService
- **getStudentsByProgram()** : Ajout du paramètre `?string $cohort = null`
  - Filtre les étudiants par cohorte via `academic_paths.cohort`

### Contrôleurs Modifiés

#### 1. LmdGradeController
- **getGradeSheet()** : Validation et passage du paramètre cohort
- **getRetakeList()** : Validation et passage du paramètre cohort

#### 2. OldGradeController
- **getGradeSheet()** : Validation et passage du paramètre cohort

#### 3. ProfessorGradeController
- **getMyClasses()** : Accepte le paramètre cohort (déjà implémenté)
- **getGradeSheet()** : Passe le paramètre cohort au service

#### 4. AdminGradeController
- **getGradesByDepartmentLevel()** : Implémentation complète avec support cohort
- **getProgramDetails()** : Ajout du paramètre cohort
- **exportGradesByDepartment()** : Ajout du paramètre cohort

#### 5. DecisionController
- Tous les endpoints supportent déjà le paramètre cohort (déjà implémenté)

### Requests
- **GetGradeSheetRequest** : Supporte déjà le paramètre cohort

## Utilisation Frontend

### Endpoints API Mis à Jour

#### Professeurs
```typescript
// Obtenir les classes avec filtre cohort
GET /api/notes/professor/my-classes?academic_year_id=1&department_id=2&cohort=1

// Obtenir la fiche de notation avec cohort
POST /api/notes/professor/grade-sheet
Body: { program_id: 45, cohort: "1" }
```

#### Administration
```typescript
// Obtenir les notes par filière avec cohort
GET /api/notes/admin/grades-by-department-level?academic_year_id=1&department_id=2&level=L1&cohort=1

// Détails d'un programme avec cohort
GET /api/notes/admin/program-details/45?cohort=1

// Export avec cohort
POST /api/notes/admin/export-grades-by-department
Body: { academic_year_id: 1, department_id: 2, level: "L1", format: "pdf", cohort: "1" }
```

#### Décisions
```typescript
// PV fin d'année avec cohort
POST /api/notes/decisions/export-pv-fin-annee
Body: { academic_year_id: 1, department_id: 2, level: "L1", cohort: "1", validation_average: 10 }

// PV délibération avec cohort
POST /api/notes/decisions/export-pv-deliberation
Body: { academic_year_id: 1, department_id: 2, level: "L1", cohort: "1", semester: 1 }

// Récap notes avec cohort
POST /api/notes/decisions/export-recap-notes
Body: { academic_year_id: 1, department_id: 2, level: "L1", cohort: "1" }
```

## Logique de Filtrage

### Base de Données
La cohorte est stockée dans `academic_paths.cohort` et est déterminée lors de la création de l'étudiant officiel basée sur la période de soumission du dossier.

### Requête SQL
```sql
SELECT * FROM academic_paths
WHERE cohort = '1'
AND student_pending_student_id IN (
    SELECT id FROM student_pending_students
    WHERE academic_year_id = ?
    AND study_level = ?
)
```

## Tests Recommandés

1. **Test de filtrage par cohorte**
   - Créer des étudiants dans différentes cohortes
   - Vérifier que le filtrage retourne uniquement les étudiants de la cohorte sélectionnée

2. **Test sans cohorte**
   - Vérifier que sans paramètre cohort, tous les étudiants sont retournés

3. **Test des exports**
   - Vérifier que les exports PDF/Excel respectent le filtre cohort

4. **Test des décisions**
   - Vérifier que les PV sont générés uniquement pour la cohorte sélectionnée

## Migration Frontend

Le frontend doit être mis à jour pour :
1. Ajouter un sélecteur de cohorte dans les interfaces professeur et admin
2. Passer le paramètre cohort dans tous les appels API concernés
3. Afficher la cohorte sélectionnée dans les en-têtes de tableaux

## Compatibilité

- ✅ Compatible avec l'ancien système (sans cohorte)
- ✅ Compatible avec le système LMD et ancien système
- ✅ Pas de breaking changes (paramètre optionnel)
