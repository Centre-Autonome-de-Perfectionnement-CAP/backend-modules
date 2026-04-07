# 📦 Guide de Migration des Étudiants

## Vue d'ensemble

Ce système migre les données de l'ancienne base de données (`u374405408_progiciel_cap.sql`) vers la nouvelle structure modulaire.

## 🏗️ Structure des Seeders

### 1. **OldDatabaseMigrationSeeder**
Migre les données de référence:
- ✅ Cycles (Licence, Master, Ingénierie)
- ✅ Diplômes d'entrée
- ✅ Années académiques (2002-2026)
- ✅ Départements/Filières

### 2. **StudentMigrationSeeder**
Migre les étudiants:
- ✅ Extraction depuis le fichier SQL
- ✅ Création de `personal_information`
- ✅ Création de `pending_students`
- ✅ Création de `students`
- ✅ Création de `student_pending_student` (pivot)
- ✅ Création de `academic_paths`

### 3. **MigrationDatabaseSeeder**
Orchestrateur qui exécute les deux seeders dans le bon ordre.

## 🚀 Utilisation

### Prérequis

1. Assurez-vous que le fichier `u374405408_progiciel_cap.sql` est à la racine du projet backend
2. Vérifiez que toutes les migrations sont exécutées:
```bash
php artisan migrate:fresh
```

### Exécution Complète

Pour migrer toutes les données:

```bash
php artisan db:seed --class=MigrationDatabaseSeeder
```

### Exécution Séparée

#### 1. Migration des données de référence uniquement:
```bash
php artisan db:seed --class=OldDatabaseMigrationSeeder
```

#### 2. Migration des étudiants uniquement (nécessite l'étape 1):
```bash
php artisan db:seed --class=StudentMigrationSeeder
```

## 📊 Mapping des Données

### Anciennes Tables → Nouvelles Tables

| Ancienne DB | Nouvelle DB |
|------------|-------------|
| `etudiants.nom` | `personal_information.last_name` |
| `etudiants.prenoms` | `personal_information.first_names` |
| `etudiants.matricule` | `students.student_id_number` |
| `etudiants.filiere_id` | `pending_students.department_id` |
| `etudiants.diplome_entree_id` | `pending_students.entry_diploma_id` |
| `etudiants.annee_entree` | `academic_paths.cohort` |
| `etudiants.genre` | `personal_information.gender` (masculin→M, feminin→F) |

### Mapping des IDs

Les IDs de l'ancienne base sont mappés vers les nouveaux:

**Cycles:**
- 7 (Licence Professionnelle) → nouveau ID
- 8 (Master Professionnel) → nouveau ID
- 9 (Ingénierie) → nouveau ID

**Départements/Filières:**
- 27 (Génie Civil) → nouveau ID
- 28 (Génie Electrique) → nouveau ID
- etc.

## 🔍 Vérification Post-Migration

### Compter les étudiants migrés:
```bash
php artisan tinker
```

```php
// Total étudiants
\App\Modules\Inscription\Models\Student::count();

// Total personal_information
\App\Modules\Inscription\Models\PersonalInformation::count();

// Total pending_students
\App\Modules\Inscription\Models\PendingStudent::count();

// Vérifier un étudiant par matricule
$student = \App\Modules\Inscription\Models\Student::where('student_id_number', '2060903')->first();
```

### Statistiques attendues:

Le seeder affiche automatiquement:
- ✅ Total traité
- ✅ Succès
- ⏭ Ignorés (déjà existants)
- ❌ Erreurs

## ⚠️ Gestion des Erreurs

### Erreurs Communes:

1. **"Matricule manquant"**
   - Un étudiant sans matricule dans l'ancienne DB
   - Solution: Ignorer ou corriger manuellement

2. **"Département introuvable"**
   - `filiere_id` invalide
   - Solution: Vérifier le mapping dans `findDepartment()`

3. **"Année académique introuvable"**
   - `annee_entree` hors de la plage 2002-2026
   - Solution: Étendre la plage dans `migrateAcademicYears()`

4. **"Étudiant déjà migré"**
   - Le matricule existe déjà
   - Solution: Normal si vous ré-exécutez le seeder

## 🔄 Ré-exécuter la Migration

Pour nettoyer et recommencer:

```bash
# Supprimer toutes les données
php artisan migrate:fresh

# Relancer la migration
php artisan db:seed --class=MigrationDatabaseSeeder
```

## 🛠️ Personnalisation

### Modifier le mot de passe par défaut:

Dans `StudentMigrationSeeder.php`, ligne ~332:

```php
'password' => Hash::make('cap2024'), // Au lieu du matricule
```

### Modifier le status financier:

Dans `StudentMigrationSeeder.php`, ligne ~358:

```php
'financial_status' => 'Exonéré', // Au lieu de 'Non exonéré'
```

### Ajouter un diplôme d'entrée:

Dans `OldDatabaseMigrationSeeder.php`, méthode `migrateEntryDiplomas()`:

```php
['id' => 5, 'nom' => 'Nouveau Diplôme'],
```

## 📝 Logs et Debug

Les erreurs sont automatiquement capturées et affichées à la fin.

Pour un debug plus détaillé, ajoutez dans `StudentMigrationSeeder.php`:

```php
\Log::info('Migration étudiant: ' . json_encode($oldStudent));
```

## 📧 Support

En cas de problème, vérifiez:
1. Le fichier SQL est bien à la racine
2. Les migrations sont toutes exécutées
3. Les données de référence sont créées en premier

---

**Auteur:** System Migration Tool  
**Date:** Octobre 2025  
**Version:** 1.0
