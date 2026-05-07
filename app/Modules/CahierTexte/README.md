# Module Cahier de Texte

## 📋 Description

Le module **Cahier de Texte** permet aux professeurs de documenter leurs séances de cours, incluant le contenu enseigné, les objectifs pédagogiques, les devoirs assignés, et les observations sur la classe.

## 🎯 Fonctionnalités

### 1. Gestion des Entrées du Cahier de Texte
- ✅ Création d'entrées pour chaque séance de cours
- ✅ Documentation du contenu couvert
- ✅ Définition des objectifs pédagogiques
- ✅ Méthodes d'enseignement utilisées
- ✅ Assignation de devoirs avec dates limites
- ✅ Suivi de la présence des étudiants
- ✅ Observations et notes

### 2. Statuts des Entrées
- **Brouillon (draft)** : Entrée en cours de rédaction
- **Publié (published)** : Entrée visible par les étudiants et administration
- **Validé (validated)** : Entrée validée par l'administration

### 3. Système de Commentaires
- ✅ Commentaires sur les entrées
- ✅ Suggestions d'amélioration
- ✅ Corrections
- ✅ Réponses en fil de discussion (threading)

### 4. Vues et Filtres
- ✅ Vue par groupe de classe
- ✅ Vue par professeur
- ✅ Filtrage par période
- ✅ Filtrage par statut
- ✅ Recherche textuelle

### 5. Statistiques
- ✅ Nombre total d'entrées
- ✅ Répartition par statut
- ✅ Total des heures enseignées
- ✅ Nombre d'entrées avec devoirs

## 📁 Structure du Module

```
app/Modules/CahierTexte/
├── Models/
│   ├── TextbookEntry.php          # Modèle des entrées
│   └── TextbookComment.php        # Modèle des commentaires
├── Http/
│   ├── Controllers/
│   │   ├── TextbookEntryController.php
│   │   └── TextbookCommentController.php
│   ├── Requests/
│   │   ├── CreateTextbookEntryRequest.php
│   │   ├── UpdateTextbookEntryRequest.php
│   │   └── CreateTextbookCommentRequest.php
│   └── Resources/
│       ├── TextbookEntryResource.php
│       └── TextbookCommentResource.php
├── Services/
│   ├── TextbookEntryService.php
│   └── TextbookCommentService.php
├── Providers/
│   └── CahierTexteServiceProvider.php
├── routes/
│   └── api.php
└── README.md
```

## 🗄️ Base de Données

### Tables

#### textbook_entries
Stocke les entrées du cahier de texte.

**Colonnes principales :**
- `program_id` : Lien vers le programme (cours + professeur + groupe)
- `scheduled_course_id` : Lien optionnel vers un cours planifié
- `session_date` : Date de la séance
- `start_time` / `end_time` : Horaires de la séance
- `hours_taught` : Nombre d'heures enseignées
- `session_title` : Titre de la séance
- `content_covered` : Contenu couvert
- `objectives` : Objectifs pédagogiques
- `teaching_methods` : Méthodes d'enseignement
- `homework` : Devoirs assignés
- `homework_due_date` : Date limite des devoirs
- `resources` : Ressources (JSON)
- `attachments` : Pièces jointes (JSON)
- `students_present` / `students_absent` : Présence
- `observations` : Observations
- `status` : Statut (draft, published, validated)

#### textbook_comments
Stocke les commentaires sur les entrées.

**Colonnes principales :**
- `textbook_entry_id` : Lien vers l'entrée
- `user_id` : Auteur du commentaire
- `comment` : Contenu du commentaire
- `type` : Type (comment, suggestion, correction)
- `parent_id` : Commentaire parent (pour threading)

## 🔌 API Endpoints

### Entrées du Cahier de Texte

```
GET    /api/cahier-texte                    # Liste des entrées
POST   /api/cahier-texte                    # Créer une entrée
GET    /api/cahier-texte/{id}               # Détails d'une entrée
PUT    /api/cahier-texte/{id}               # Mettre à jour une entrée
DELETE /api/cahier-texte/{id}               # Supprimer une entrée
POST   /api/cahier-texte/{id}/publish       # Publier une entrée
POST   /api/cahier-texte/{id}/validate      # Valider une entrée
GET    /api/cahier-texte/class-group/{id}   # Entrées par groupe
GET    /api/cahier-texte/professor/{id}     # Entrées par professeur
GET    /api/cahier-texte/statistics/all     # Statistiques
```

### Commentaires

```
GET    /api/cahier-texte/{entryId}/comments              # Liste des commentaires
POST   /api/cahier-texte/{entryId}/comments              # Créer un commentaire
PUT    /api/cahier-texte/{entryId}/comments/{commentId}  # Modifier un commentaire
DELETE /api/cahier-texte/{entryId}/comments/{commentId}  # Supprimer un commentaire
```

## 📝 Exemples d'Utilisation

### Créer une Entrée

```json
POST /api/cahier-texte
{
  "program_id": 1,
  "session_date": "2026-03-15",
  "start_time": "08:00",
  "end_time": "10:00",
  "hours_taught": 2,
  "session_title": "Introduction aux Bases de Données",
  "content_covered": "Concepts fondamentaux des SGBD, modèle relationnel, SQL de base",
  "objectives": "Comprendre les principes des bases de données relationnelles",
  "teaching_methods": "Cours magistral avec exemples pratiques",
  "homework": "Exercices 1 à 5 du chapitre 1",
  "homework_due_date": "2026-03-22",
  "students_present": 45,
  "students_absent": 3,
  "observations": "Bonne participation de la classe",
  "status": "draft"
}
```

### Publier une Entrée

```json
POST /api/cahier-texte/1/publish
```

### Ajouter un Commentaire

```json
POST /api/cahier-texte/1/comments
{
  "comment": "Excellent travail sur cette séance !",
  "type": "comment"
}
```

### Filtrer les Entrées

```
GET /api/cahier-texte?status=published&start_date=2026-03-01&end_date=2026-03-31
```

## 🔐 Sécurité

- ✅ Toutes les routes protégées par `auth:sanctum`
- ✅ Validation stricte des données
- ✅ Messages d'erreur en français
- ✅ Logging de toutes les opérations

## 🚀 Workflow Typique

1. **Professeur crée une entrée** (statut: draft)
2. **Professeur complète les informations** de la séance
3. **Professeur publie l'entrée** (statut: published)
4. **Étudiants et administration peuvent consulter** l'entrée
5. **Administration peut valider** l'entrée (statut: validated)
6. **Utilisateurs peuvent commenter** l'entrée

## 📊 Intégrations

### Module Cours
- Utilise `Program` pour lier cours, professeur et groupe
- Récupère les informations du cours via les relations

### Module Emploi du Temps
- Peut être lié à un `ScheduledCourse`
- Permet de documenter les séances planifiées

### Module Inscription
- Accès aux groupes de classe
- Suivi de la présence des étudiants

## ✨ Améliorations Futures

1. **Export PDF** des cahiers de texte
2. **Notifications** aux étudiants lors de nouveaux devoirs
3. **Rappels automatiques** pour les devoirs à venir
4. **Statistiques avancées** par professeur/classe
5. **Templates** de séances réutilisables
6. **Intégration avec le module Notes** pour lier devoirs et évaluations

## 🎉 Statut

✅ **Module 100% fonctionnel et prêt pour la production**

**Date de création :** 13 Mars 2026  
**Développeur :** Assistant IA
