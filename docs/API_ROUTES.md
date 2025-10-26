# Progi CAP API – Documentation

Base URL (développement): `http://127.0.0.1:8000`

---

## API Next Deadline (Périodes d'inscription actives)

### GET `/api/next-deadline`

Retourne **toutes les périodes d'inscription actives groupées par date de fin**.

**Entrée**: aucune

**Sortie 200 - Inscriptions ouvertes**:
```json
{
  "success": true,
  "data": {
    "status": "open",
    "periods": [
      {
        "deadline": "2025-11-25T23:59:59+00:00",
        "filieres": [
          {
            "id": 28,
            "name": "Génie Civil",
            "abbreviation": "GC",
            "cycle": "licence"
          },
          {
            "id": 30,
            "name": "Génie Électrique",
            "abbreviation": "GE",
            "cycle": "licence"
          }
        ]
      },
      {
        "deadline": "2025-12-15T23:59:59+00:00",
        "filieres": [
          {
            "id": 45,
            "name": "Master Informatique",
            "abbreviation": "MI",
            "cycle": "master"
          }
        ]
      }
    ]
  }
}
```

**Sortie 200 - Aucune inscription active**:
```json
{
  "success": true,
  "data": {
    "status": "closed",
    "periods": []
  }
}
```

**Logique**:
- Cherche toutes les périodes de soumission où `end_date >= aujourd'hui`
- Groupe les filières par `end_date` identique
- Trie par `end_date` croissant
- Si aucune période active: `status: "closed"` + `periods: []`

**Usage frontend**:
- Afficher un countdown pour chaque période
- Lister toutes les filières ayant la même deadline
- Masquer le countdown si `status === "closed"`
- La première période (`periods[0]`) est la plus proche

**Exemple cURL**:
```bash
curl http://127.0.0.1:8000/api/next-deadline
```

---

## API Filières (Départements avec périodes)

### GET `/api/filieres`

Retourne **tous les départements de tous les cycles** avec leurs périodes de soumission.

**Entrée**: aucune

**Sortie 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Génie Civil",
      "abbreviation": "GC",
      "cycle": "licence",
      "dateLimite": "2026-01-15",
      "image": "",
      "badge": "inscriptions-ouvertes"
    }
  ]
}
```

**Format Filiere** (TypeScript):
```typescript
interface Filiere {
  id: number;
  title: string;
  abbreviation: string;
  cycle: 'licence' | 'master' | 'ingenierie';
  dateLimite: string | null;
  image: string;
  badge: 'inscriptions-ouvertes' | 'inscriptions-fermees' | 'prochainement';
}
```

**Badges**:
- `inscriptions-ouvertes`: période en cours (aujourd'hui entre start_date et end_date)
- `prochainement`: période future (start_date > aujourd'hui)
- `inscriptions-fermees`: aucune période active ou future

**Exemple cURL**:
```bash
curl http://127.0.0.1:8000/api/filieres
```

---

## API Documents officiels

Basée sur le modèle **File** avec `is_official_document = true`.

### GET `/api/documents`

Liste des documents officiels, avec filtre optionnel par catégorie.

**Entrée**:
- Query parameter: `categorie?` enum `administratif|pedagogique|legal|organisation`

**Sortie 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "titre": "Règlement intérieur",
      "description": "Document officiel du règlement intérieur de l'établissement",
      "type": "pdf",
      "taille": "2.5 MB",
      "datePublication": "2025-10-01",
      "lien": "https://example.com/storage/documents/reglement.pdf",
      "categorie": "administratif"
    }
  ]
}
```

**Format Document** (TypeScript):
```typescript
interface Document {
  id: number;
  titre: string;
  description: string;
  type: 'pdf' | 'doc' | 'xls' | 'ppt' | 'lien';
  taille?: string;
  datePublication: string;
  lien: string;
  categorie: 'administratif' | 'pedagogique' | 'legal' | 'organisation';
}
```

**Exemples cURL**:
```bash
# Tous les documents
curl http://127.0.0.1:8000/api/documents

# Filtrer par catégorie
curl "http://127.0.0.1:8000/api/documents?categorie=pedagogique"
```

---

### GET `/api/documents/{file}`

Détails d'un document spécifique.

**Entrée**: ID du fichier dans l'URL

**Sortie 200**: `{ success: true, data: Document }`

**Erreurs**: 
- 404: Fichier introuvable ou n'est pas un document officiel

---

### POST `/api/documents` [auth]

Créer un document officiel (upload fichier OU lien externe).

**Authentification**: Bearer token requis

**Entrée (multipart/form-data)**:

**Option 1 - Upload fichier**:
- `titre` string (requis)
- `description` string (requis)
- `file` file max 10MB (requis si pas de lien)
- `datePublication` date YYYY-MM-DD (requis)
- `categorie` enum `administratif|pedagogique|legal|organisation` (requis)

**Option 2 - Lien externe** (JSON):
- `titre` string (requis)
- `description` string (requis)
- `lien` URL (requis si pas de file)
- `datePublication` date (requis)
- `categorie` enum (requis)

**Sortie 201**: `{ success: true, data: Document }`

**Erreurs**:
- 401: Non authentifié
- 422: Validation échouée

**Exemples cURL**:
```bash
# Upload fichier
curl -H "Authorization: Bearer <token>" \
     -F "titre=Règlement intérieur 2025" \
     -F "description=Document officiel" \
     -F "file=@reglement.pdf" \
     -F "datePublication=2025-10-26" \
     -F "categorie=administratif" \
     http://127.0.0.1:8000/api/documents

# Lien externe
curl -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" \
     -X POST http://127.0.0.1:8000/api/documents \
     -d '{
       "titre": "Calendrier académique",
       "description": "Calendrier officiel du ministère",
       "lien": "https://memp.gouv.bj/calendrier-2025.pdf",
       "datePublication": "2025-10-26",
       "categorie": "pedagogique"
     }'
```

---

### PUT `/api/documents/{file}` [auth]

Mettre à jour un document.

**Authentification**: Bearer token requis

**Entrée (JSON)**:
- `titre?` string
- `description?` string
- `datePublication?` date
- `categorie?` enum

**Sortie 200**: `{ success: true, data: Document }`

**Erreurs**:
- 401: Non authentifié
- 404: Document introuvable
- 422: Validation échouée

---

### DELETE `/api/documents/{file}` [auth]

Supprimer un document (fichier physique + enregistrement BDD).

**Authentification**: Bearer token requis

**Sortie 200**: `{ success: true }`

**Erreurs**:
- 401: Non authentifié
- 404: Document introuvable

---

## Notes

- Routes marquées [auth] nécessitent: `Authorization: Bearer <token>`
- Documentation interactive: `/api/documentation` (Swagger UI)
- Stockage documents: `public/documents/`
