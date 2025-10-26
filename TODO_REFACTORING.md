# 📋 TODO - Refactoring Service Layer

## ✅ TERMINÉ (Session Actuelle)

### Modules Corrigés
1. ✅ **PendingStudentController** → PendingStudentService créé
2. ✅ **AuthController** → AuthService créé

### Documentation Créée
1. ✅ `SERVICE_LAYER_GUIDE.md` - Guide complet avec exemples
2. ✅ `REFACTORING_REPORT.md` - Rapport des anomalies détectées
3. ✅ `REFACTORING_SUMMARY.md` - Résumé du refactoring
4. ✅ `TODO_REFACTORING.md` - Cette checklist

---

## ⏳ À FAIRE (Priorité)

### Services à Créer - Module Inscription

#### 1. AcademicYearService ⭐⭐⭐
**Controller:** `app/Modules/Inscription/Http/Controllers/AcademicYearController.php`
**Fichier à créer:** `app/Modules/Inscription/Services/AcademicYearService.php`

**Méthodes requises:**
```php
- getAll(array $filters, int $perPage)
- create(array $data)
- getById(int $id)
- update(AcademicYear $academicYear, array $data)
- delete(AcademicYear $academicYear)
- setCurrent(AcademicYear $academicYear)
- getCurrent()
```

#### 2. CycleService ⭐⭐⭐
**Controller:** `app/Modules/Inscription/Http/Controllers/CycleController.php`
**Fichier à créer:** `app/Modules/Inscription/Services/CycleService.php`

**Méthodes requises:**
```php
- getAll(array $filters, int $perPage)
- create(array $data)
- getById(int $id)
- update(Cycle $cycle, array $data)
- delete(Cycle $cycle)
```

#### 3. StudentIdService ⭐⭐
**Controller:** `app/Modules/Inscription/Http/Controllers/StudentIdController.php`
**Fichier à créer:** `app/Modules/Inscription/Services/StudentIdService.php`

**Méthodes requises:**
```php
- generateStudentId(Student $student)
- validateStudentId(string $studentId)
- assignStudentId(PendingStudent $pending, string $studentId)
```

#### 4. SubmissionService ⭐⭐
**Controller:** `app/Modules/Inscription/Http/Controllers/SubmissionController.php`
**Fichier à créer:** `app/Modules/Inscription/Services/SubmissionService.php`

**Méthodes requises:**
```php
- getAll(array $filters, int $perPage)
- create(array $data, array $files)
- getById(int $id)
- update(Submission $submission, array $data)
- approve(Submission $submission)
- reject(Submission $submission, string $reason)
```

---

### Services à Créer - Module Auth

#### 5. AdministrationService ⭐⭐
**Controller:** `app/Modules/Auth/Http/Controllers/AdministrationController.php`
**Fichier à créer:** `app/Modules/Auth/Services/AdministrationService.php`

**Méthodes requises:**
```php
- getDashboardStats()
- getRecentActivities(int $limit)
- getSystemHealth()
```

---

### Services à Créer - Module Stockage

#### 6. DocumentService ⭐
**Controller:** `app/Modules/Stockage/Http/Controllers/DocumentController.php`
**Fichier à créer:** `app/Modules/Stockage/Services/DocumentService.php`

**Méthodes requises:**
```php
- getAll(array $filters, int $perPage)
- upload(array $data, $file, int $userId)
- getById(int $id)
- update(Document $document, array $data)
- delete(Document $document)
- download(Document $document)
```

---

## 🎯 Plan d'Exécution

### Semaine 1
- [ ] Jour 1: AcademicYearService + refactor controller
- [ ] Jour 2: CycleService + refactor controller
- [ ] Jour 3: StudentIdService + refactor controller

### Semaine 2
- [ ] Jour 1: SubmissionService + refactor controller
- [ ] Jour 2: AdministrationService + refactor controller
- [ ] Jour 3: DocumentService + refactor controller

### Semaine 3
- [ ] Tests unitaires pour tous les services
- [ ] Documentation API mise à jour
- [ ] Code review final

---

## 📝 Template de Service

Utiliser ce template pour créer les nouveaux services:

```php
<?php

namespace App\Modules\{Module}\Services;

use App\Modules\{Module}\Models\{Model};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class {Model}Service
{
    /**
     * Récupérer tous les {models} avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = {Model}::query()->with(['relations']);

        // Filtres...
        if (!empty($filters['search'])) {
            // Recherche
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Créer un {model}
     */
    public function create(array $data): {Model}
    {
        return DB::transaction(function () use ($data) {
            ${model} = {Model}::create($data);

            Log::info('{Model} créé', ['{model}_id' => ${model}->id]);

            return ${model};
        });
    }

    /**
     * Récupérer par ID
     */
    public function getById(int $id): ?{Model}
    {
        return {Model}::with(['relations'])->find($id);
    }

    /**
     * Mettre à jour
     */
    public function update({Model} ${model}, array $data): {Model}
    {
        return DB::transaction(function () use (${model}, $data) {
            ${model}->update($data);

            Log::info('{Model} mis à jour', ['{model}_id' => ${model}->id]);

            return ${model}->fresh(['relations']);
        });
    }

    /**
     * Supprimer
     */
    public function delete({Model} ${model}): bool
    {
        try {
            ${model}->delete();

            Log::info('{Model} supprimé', ['{model}_id' => ${model}->id]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression {model}', [
                '{model}_id' => ${model}->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## ✅ Checklist par Service

Pour chaque service créé, vérifier:

- [ ] Le fichier est dans `app/Modules/{Module}/Services/`
- [ ] Le namespace est correct
- [ ] Les méthodes CRUD de base sont présentes
- [ ] Les transactions DB sont utilisées pour les opérations d'écriture
- [ ] Le logging est implémenté pour chaque opération
- [ ] Les relations sont eager-loadées
- [ ] Les exceptions sont gérées correctement
- [ ] Le controller correspondant a été refactoré
- [ ] Le controller injecte le service via constructeur
- [ ] Aucun appel direct aux models dans le controller
- [ ] Les tests unitaires sont écrits

---

## 📊 Progrès

**Modules 100% conformes:** 3/6 (50%)
- ✅ RH
- ✅ Cours  
- ✅ Finance
- ⏳ Inscription (33%)
- ⏳ Auth (50%)
- ⏳ Stockage (75%)

**Services créés:** 10/16 (62.5%)
**Controllers refactorisés:** 9/18 (50%)

---

## 🎯 Objectif Final

- **16 services** créés
- **16 controllers** refactorisés
- **6 modules** 100% conformes
- **0 logique métier** dans les controllers
- **100% testabilité** des services
