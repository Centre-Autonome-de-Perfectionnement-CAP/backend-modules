# 📚 Guide des Seeders - Années Académiques et Périodes

## 🎯 Vue d'ensemble

Deux seeders sont disponibles pour créer les années académiques et leurs périodes :

### 1. **AcademicYearCompleteSeeder** (Complet)
Crée plusieurs années académiques (passées, actuelle, futures) avec toutes leurs périodes de soumission et de réclamation pour tous les départements.

### 2. **QuickAcademicYearSeeder** (Rapide)
Crée uniquement l'année académique actuelle avec une période de soumission et une période de réclamation. Idéal pour les tests rapides.

---

## 🚀 Utilisation

### Seeder Complet (Production)
```bash
# Créer toutes les années avec périodes détaillées
php artisan db:seed --class=AcademicYearCompleteSeeder
```

**Ce seeder crée :**
- ✅ 3 années académiques (2023-2024, 2024-2025, 2025-2026)
- ✅ Périodes de soumission pour TOUS les départements
- ✅ 2 périodes de réclamation par année académique
- ✅ Dates intelligentes (période active pour l'année en cours)

### Seeder Rapide (Développement)
```bash
# Créer rapidement l'année actuelle
php artisan db:seed --class=QuickAcademicYearSeeder
```

**Ce seeder crée :**
- ✅ 1 année académique (année en cours)
- ✅ 1 période de soumission globale (active maintenant)
- ✅ 1 période de réclamation

---

## 📊 Structure des Données Créées

### Années Académiques (AcademicYear)
```php
[
    'academic_year' => '2024-2025',
    'year_start' => 2024,
    'year_end' => 2025,
    'submission_start' => '2024-09-01',
    'submission_end' => '2024-10-31',
]
```

### Périodes de Soumission (SubmissionPeriod)
```php
[
    'academic_year_id' => 1,
    'department_id' => 5,        // null pour période globale
    'start_date' => '2024-09-01',
    'end_date' => '2024-10-31',
]
```

### Périodes de Réclamation (ReclamationPeriod)
```php
[
    'academic_year_id' => 1,
    'start_date' => '2024-11-07',  // 7 jours après fin soumission
    'end_date' => '2024-11-21',    // 2 semaines
    'is_active' => true,
]
```

---

## 🔧 Configuration des Périodes

### AcademicYearCompleteSeeder

#### Périodes de Soumission
- **Année Passée** : Utilise les dates définies
- **Année Actuelle** : 
  - Début : 15 jours avant aujourd'hui
  - Fin : 60 jours après aujourd'hui
- **Année Future** : Utilise les dates définies
- **Ajustement** : Les départements d'ingénierie (ID 20-27) commencent 5 jours plus tard

#### Périodes de Réclamation (2 par année)
1. **Première période** : 
   - Début : 7 jours après fin soumission
   - Durée : 2 semaines
2. **Deuxième période** :
   - Début : 90 jours après fin soumission
   - Durée : 2 semaines

### QuickAcademicYearSeeder

- **Soumission** : De 15 jours avant à 45 jours après aujourd'hui
- **Réclamation** : 7 jours après fin soumission, durée 2 semaines

---

## 📝 Exemples d'Utilisation

### Scénario 1 : Première Installation
```bash
# 1. Créer les cycles et départements d'abord
php artisan db:seed --class=CycleSeeder
php artisan db:seed --class=DepartmentSeeder

# 2. Créer les années académiques complètes
php artisan db:seed --class=AcademicYearCompleteSeeder
```

### Scénario 2 : Développement Rapide
```bash
# Créer juste ce qu'il faut pour tester
php artisan db:seed --class=QuickAcademicYearSeeder
```

### Scénario 3 : Réinitialiser les Périodes
```bash
# Les seeders utilisent updateOrCreate, donc vous pouvez les relancer
php artisan db:seed --class=AcademicYearCompleteSeeder
```

---

## 🧪 Tester les Données Créées

### Via API
```bash
# Lister les années académiques
curl http://127.0.0.1:8000/api/academic-years

# Lister les départements avec périodes
curl http://127.0.0.1:8000/api/filieres

# Voir les périodes de soumission
curl http://127.0.0.1:8000/api/submission-periods
```

### Via Tinker
```bash
php artisan tinker
```

```php
// Compter les années
AcademicYear::count();

// Voir les périodes actives
SubmissionPeriod::whereDate('start_date', '<=', now())
    ->whereDate('end_date', '>=', now())
    ->get();

// Voir les réclamations actives
ReclamationPeriod::where('is_active', true)->get();
```

---

## 🔍 Vérification des Données

### Après AcademicYearCompleteSeeder
```
✅ 3 années académiques créées
✅ ~81 périodes de soumission (27 départements × 3 années)
✅ 6 périodes de réclamation (2 × 3 années)
```

### Après QuickAcademicYearSeeder
```
✅ 1 année académique créée
✅ 1 période de soumission globale
✅ 1 période de réclamation
```

---

## ⚠️ Notes Importantes

### Prérequis
- ✅ Les **cycles** doivent être créés avant (CycleSeeder)
- ✅ Les **départements** doivent être créés avant (DepartmentSeeder)

### Sécurité
- Les seeders utilisent `updateOrCreate` : pas de doublons
- Réexécuter le seeder met à jour les données existantes

### Performance
- **AcademicYearCompleteSeeder** : ~3-5 secondes
- **QuickAcademicYearSeeder** : < 1 seconde

---

## 🎨 Personnalisation

### Modifier les Dates
Éditez les fichiers seeders pour ajuster :
- Le nombre d'années académiques
- Les dates de début/fin
- La durée des périodes
- Les départements concernés

### Ajouter une Année
Dans `AcademicYearCompleteSeeder`, ajoutez dans le tableau `$academicYears` :
```php
[
    'academic_year' => '2026-2027',
    'year_start' => 2026,
    'year_end' => 2027,
    'submission_start' => '2026-09-01',
    'submission_end' => '2026-10-31',
    'is_future' => true,
],
```

---

## 🐛 Dépannage

### Erreur : "Département non trouvé"
```bash
# Créer les départements d'abord
php artisan db:seed --class=DepartmentSeeder
```

### Erreur : "Academic year not found"
```bash
# Créer les années d'abord
php artisan db:seed --class=AcademicYearCompleteSeeder
```

### Les périodes ne s'affichent pas dans l'API
```bash
# Vérifier les relations dans les modèles
# Vérifier que les controllers chargent bien les relations
```

---

## 📞 Support

Pour toute question sur les seeders :
1. Vérifier ce README
2. Consulter les commentaires dans les fichiers seeders
3. Tester avec `php artisan tinker`
