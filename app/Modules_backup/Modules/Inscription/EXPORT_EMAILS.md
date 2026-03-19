# Export des Emails des Étudiants en Attente

## Vue d'ensemble

Cette fonctionnalité permet d'exporter la liste des emails de tous les étudiants en attente pour une année académique donnée, avec possibilité de filtrer par filière et cohorte.

## Utilisation

### Interface Utilisateur

1. Accéder à la page **Étudiants en Attente**
2. Sélectionner une **année académique** (obligatoire)
3. Optionnel : Sélectionner une **filière** spécifique
4. Optionnel : Sélectionner une **cohorte** spécifique
5. Cliquer sur le bouton **"Exporter Emails"** (bouton vert)

### Résultat

Un fichier PDF sera téléchargé contenant :
- Un tableau avec le nom complet et l'email de chaque étudiant
- Une section avec tous les emails séparés par des virgules (prêt pour copier-coller)
- Les informations de filtrage appliquées
- Le nombre total d'étudiants

## Format du PDF

Le PDF généré contient :

### En-tête
- Titre : "Liste des Emails - Étudiants en Attente"
- Année académique
- Nombre total d'étudiants
- Date d'export

### Sections par Filière
Les étudiants sont groupés et triés par filière. Pour chaque filière :

#### Tableau
| N° | Nom et Prénoms | Email |
|----|----------------|-------|
| 1  | DUPONT Jean    | jean.dupont@example.com |
| 2  | MARTIN Marie   | marie.martin@example.com |

#### Section Copier-Coller
Une zone avec tous les emails de la filière séparés par des virgules :
```
jean.dupont@example.com, marie.martin@example.com, ...
```

## Cas d'usage

### 1. Envoi d'email groupé par filière
Copier la liste des emails d'une filière spécifique pour envoyer un message ciblé.

### 2. Communication globale
Exporter tous les étudiants de toutes les filières pour une communication générale, avec les emails organisés par filière.

### 3. Communication par cohorte
Contacter uniquement les étudiants d'une cohorte particulière, groupés par filière.

### 4. Archivage
Conserver une trace des emails des étudiants pour une année académique donnée, organisée par filière.

## API Endpoint

```http
GET /api/inscription/export/emails
```

### Paramètres
- `year` (obligatoire) : ID de l'année académique
- `filiere` (optionnel) : ID de la filière
- `cohort` (optionnel) : Numéro de la cohorte

### Exemple
```bash
GET /api/inscription/export/emails?year=1&filiere=5&cohort=1
```

### Réponse
Fichier PDF téléchargé avec le nom :
```
EMAILS_ETUDIANTS_2024_2025_Production_Animale_20250101_143022.pdf
```

## Différences avec les autres exports

| Export | Validation requise | Contenu |
|--------|-------------------|---------|
| PDF/Excel/Word | Année + Filière + Cohorte | Liste complète CUCA-CUO avec avis |
| **Emails** | **Année uniquement** | **Noms et emails uniquement** |

L'export des emails est plus flexible car il ne nécessite pas de sélectionner une filière ou une cohorte spécifique.

## Notes techniques

- Le PDF est généré avec DomPDF
- Le template utilisé : `liste-emails-etudiants.blade.php`
- Format A4 portrait
- Encodage UTF-8 pour supporter les caractères spéciaux
