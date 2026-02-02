# API Publique - Consultation des Résultats Étudiants

## 📋 Vue d'ensemble

Cette API permet aux étudiants de consulter leurs résultats académiques en ligne via le site vitrine, sans nécessiter d'authentification par token. L'authentification se fait par matricule + date de naissance.

---

## 🔐 Sécurité

- **Pas de token requis** : Routes publiques accessibles sans `auth:sanctum`
- **Authentification par données personnelles** : Matricule + Date de naissance
- **Validation stricte** : Vérification de la correspondance des données
- **Données limitées** : Seules les informations académiques sont exposées

---

## 🔌 Endpoints

### 1. Authentification Étudiant

**POST** `/api/public/notes/authenticate`

Authentifie un étudiant et retourne ses informations de base avec la liste des années académiques disponibles.

#### Requête

```json
{
  "student_id_number": "CAP2024001",
  "birth_date": "2000-05-15"
}
```

#### Réponse (Succès)

```json
{
  "success": true,
  "message": "Authentification réussie",
  "data": {
    "student": {
      "id": 123,
      "student_id_number": "CAP2024001",
      "last_name": "DOE",
      "first_names": "John",
      "birth_date": "2000-05-15"
    },
    "academic_years": [
      {
        "id": 1,
        "label": "2023-2024",
        "level": "L1",
        "department": "Informatique",
        "is_lmd": true
      },
      {
        "id": 2,
        "label": "2024-2025",
        "level": "L2",
        "department": "Informatique",
        "is_lmd": true
      }
    ]
  }
}
```

#### Réponse (Erreur)

```json
{
  "success": false,
  "message": "Matricule introuvable"
}
```

```json
{
  "success": false,
  "message": "Date de naissance incorrecte"
}
```

---

### 2. Récupération des Résultats

**POST** `/api/public/notes/results`

Récupère les résultats détaillés d'un étudiant pour une année académique spécifique.

#### Requête

```json
{
  "student_id": 123,
  "academic_year_id": 1
}
```

#### Réponse (Succès)

```json
{
  "success": true,
  "message": "Résultats récupérés avec succès",
  "data": {
    "academic_info": {
      "academic_year": "2023-2024",
      "level": "L1",
      "department": "Informatique",
      "is_lmd": true
    },
    "results": [
      {
        "course_name": "Algorithmique et Programmation",
        "course_code": "INF101",
        "professor": "DIALLO Amadou",
        "credits": 6,
        "coefficient": 3,
        "semester": 1,
        "average": 14.5,
        "retake_average": null,
        "final_average": 14.5,
        "validated": true,
        "must_retake": false
      },
      {
        "course_name": "Mathématiques Discrètes",
        "course_code": "MAT101",
        "professor": "SOW Fatou",
        "credits": 5,
        "coefficient": 2,
        "semester": 1,
        "average": 10.5,
        "retake_average": 13.0,
        "final_average": 12.0,
        "validated": true,
        "must_retake": false
      }
    ],
    "summary": {
      "total_credits": 60,
      "obtained_credits": 54,
      "general_average": 13.25,
      "semester_decisions": {
        "s1": "pass",
        "s2": "pass"
      },
      "year_decision": "pass"
    }
  }
}
```

#### Réponse (Erreur)

```json
{
  "success": false,
  "message": "Aucun parcours académique trouvé pour cette année"
}
```

---

## 📊 Structure des Données

### Student (Étudiant)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID interne de l'étudiant |
| `student_id_number` | string | Matricule de l'étudiant |
| `last_name` | string | Nom de famille |
| `first_names` | string | Prénoms |
| `birth_date` | date | Date de naissance (YYYY-MM-DD) |

### AcademicYear (Année Académique)

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID de l'année académique |
| `label` | string | Libellé (ex: "2023-2024") |
| `level` | string | Niveau d'études (L1, L2, M1, etc.) |
| `department` | string | Nom de la filière |
| `is_lmd` | boolean | Système LMD ou ancien système |

### CourseResult (Résultat de Cours)

| Champ | Type | Description |
|-------|------|-------------|
| `course_name` | string | Nom du cours |
| `course_code` | string | Code du cours |
| `professor` | string | Nom du professeur |
| `credits` | integer | Nombre de crédits |
| `coefficient` | integer | Coefficient |
| `semester` | integer | Semestre (1 ou 2) |
| `average` | float | Moyenne session normale |
| `retake_average` | float\|null | Moyenne rattrapage (LMD uniquement) |
| `final_average` | float | Moyenne finale |
| `validated` | boolean | Cours validé (>= 12) |
| `must_retake` | boolean | Doit reprendre le cours (< 7) |

### Summary (Résumé)

| Champ | Type | Description |
|-------|------|-------------|
| `total_credits` | integer | Total des crédits de l'année |
| `obtained_credits` | integer | Crédits validés |
| `general_average` | float | Moyenne générale pondérée |
| `semester_decisions` | object | Décisions semestrielles (s1, s2) |
| `year_decision` | string | Décision annuelle |

### Décisions

Les décisions peuvent avoir les valeurs suivantes :
- `pass` : Admis(e)
- `repeat` : Redouble
- `fail` : Ajourné(e)
- `null` : En attente

---

## 🎯 Logique Métier

### Calcul de la Moyenne Finale

#### Système LMD
```
Si moyenne_normale >= 12:
    moyenne_finale = moyenne_normale
Sinon si rattrapage effectué:
    moyenne_finale = min(moyenne_rattrapage, 12)
Sinon:
    moyenne_finale = moyenne_normale
```

#### Ancien Système
```
moyenne_finale = moyenne_normale
```

### Validation d'un Cours

```
validé = (moyenne_finale >= 12)
```

### Statut de Rattrapage (LMD uniquement)

```
Si moyenne < 7:
    must_retake = true (doit reprendre)
Sinon si 7 <= moyenne < 12:
    can_retake = true (peut rattraper)
Sinon:
    validated = true (validé)
```

### Calcul de la Moyenne Générale

```
moyenne_générale = Σ(moyenne_finale × coefficient) / Σ(coefficient)
```

---

## 🔒 Validation des Données

### Authentification

- `student_id_number` : **requis**, string
- `birth_date` : **requis**, date valide (format YYYY-MM-DD)

### Récupération des Résultats

- `student_id` : **requis**, integer, doit exister dans `student_pending_student`
- `academic_year_id` : **requis**, integer, doit exister dans `academic_years`

---

## 🚀 Utilisation Frontend

### Flux d'Utilisation

1. **Étape 1 : Authentification**
   - L'étudiant entre son matricule et sa date de naissance
   - Appel à `/api/public/notes/authenticate`
   - Récupération de la liste des années académiques

2. **Étape 2 : Sélection de l'Année**
   - L'étudiant sélectionne une année académique
   - Appel à `/api/public/notes/results`
   - Affichage des résultats détaillés

3. **Étape 3 : Consultation**
   - Affichage du tableau des notes
   - Affichage du résumé (moyenne, crédits, décisions)
   - Option d'impression

### Exemple d'Intégration (React/TypeScript)

```typescript
// Authentification
const authenticate = async (matricule: string, birthDate: string) => {
  const response = await fetch('/api/public/notes/authenticate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      student_id_number: matricule,
      birth_date: birthDate
    })
  });
  
  const data = await response.json();
  return data;
};

// Récupération des résultats
const getResults = async (studentId: number, academicYearId: number) => {
  const response = await fetch('/api/public/notes/results', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      student_id: studentId,
      academic_year_id: academicYearId
    })
  });
  
  const data = await response.json();
  return data;
};
```

---

## 🎨 Interface Utilisateur

### Écran 1 : Authentification

- Champ : Matricule
- Champ : Date de naissance
- Bouton : Se connecter
- Message d'erreur si échec

### Écran 2 : Sélection de l'Année

- Affichage du nom de l'étudiant
- Liste des années académiques (cards cliquables)
- Bouton : Déconnexion

### Écran 3 : Résultats

- En-tête : Infos étudiant + année
- Résumé : 4 cards (Moyenne, Crédits, Décisions)
- Tableau : Détail des notes par matière
- Boutons : Retour, Déconnexion, Imprimer

---

## ⚠️ Gestion des Erreurs

### Erreurs Courantes

| Code | Message | Cause |
|------|---------|-------|
| 404 | Matricule introuvable | Matricule inexistant |
| 401 | Date de naissance incorrecte | Date ne correspond pas |
| 404 | Aucun parcours académique trouvé | Pas d'inscription pour cette année |
| 422 | Validation error | Données manquantes ou invalides |
| 500 | Erreur serveur | Erreur interne |

---

## 📝 Notes Importantes

1. **Pas de cache** : Les données sont récupérées en temps réel
2. **Données sensibles** : Seules les informations académiques sont exposées
3. **Pas de modification** : API en lecture seule
4. **Support LMD et ancien système** : Gestion automatique selon le cycle
5. **Décisions** : Peuvent être `null` si pas encore saisies

---

## ✅ Tests Recommandés

### Tests Fonctionnels

1. Authentification avec matricule valide
2. Authentification avec matricule invalide
3. Authentification avec date de naissance incorrecte
4. Récupération des résultats pour une année valide
5. Récupération des résultats pour une année sans parcours
6. Affichage correct des moyennes (normale + rattrapage)
7. Calcul correct de la moyenne générale
8. Affichage correct des décisions

### Tests de Sécurité

1. Tentative d'accès avec ID étudiant d'un autre
2. Injection SQL dans les champs
3. Validation des formats de date
4. Gestion des caractères spéciaux

---

## 🔄 Évolutions Futures

- Export PDF des résultats
- Historique complet (toutes les années)
- Graphiques de progression
- Comparaison avec la moyenne de classe
- Notifications par email lors de publication des résultats
- Téléchargement du bulletin officiel

---

## 📞 Support

Pour toute question ou problème :
- Vérifier les logs Laravel : `storage/logs/laravel.log`
- Vérifier les erreurs de validation
- Tester avec Postman/Insomnia
- Consulter la documentation du module Notes

