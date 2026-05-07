# Templates PDF EPAC - Documentation Complète

## Architecture

### Layout de base : `epac-base.blade.php`
Template parent flexible contenant tous les éléments communs des documents EPAC/CAP.

## Templates disponibles

### 1. Documents de listes et fiches
- **`fiche-recapitulatif-notes.blade.php`** - Fiche récapitulatif de notes
- **`liste-emargement.blade.php`** - Liste d'émargement (anciennes versions, voir `epac-liste-emargement.blade.php`)
- **`liste-presence.blade.php`** - Liste de présence (anciennes versions, voir `epac-liste-presence.blade.php`)
- **`epac-liste-presence.blade.php`** ✨ - Fiche de présence refactorisée

### 2. Documents officiels
- **`epac-bulletin.blade.php`** ✨ - Bulletin de notes avec QR code
- **`epac-certificat-classes-preparatoires.blade.php`** ✨ - Certificat préparatoire aux études d'ingénieur
- **`attestation-licence.blade.php`** - Attestation de licence
- **`certificat-classes-preparatoires.blade.php`** - Ancien certificat (voir version epac-)
- **`bulletin.blade.php`** - Ancien bulletin (voir version epac-)

### 3. Autres documents
- **`decision-annee-academique.blade.php`** - Décision d'année académique
- **`liste-cuca-cuo.blade.php`** - Liste CUCA/CUO
- **`document.blade.php`** - Template générique
- **`report.blade.php`** - Rapport générique
- **`base.blade.php`** - Ancien layout de base (non EPAC)

## Sections Blade disponibles dans `epac-base.blade.php`

### Configuration de base
```php
@section('title')              // Titre HTML de la page
@section('body-font-size')     // Taille police du body (défaut: 10px)
@section('body-font-weight')   // Poids police (défaut: bold)
@section('body-margin')        // Marges du body (défaut: 20px)
```

### Configuration des logos
```php
@section('logo-epac-ext')      // Extension logo EPAC (défaut: png, peut être jpeg)
@section('logo-cap-ext')       // Extension logo CAP (défaut: png, peut être jpeg)
@section('logo-epac-styles')   // Styles CSS additionnels pour logo EPAC
@section('logo-cap-styles')    // Styles CSS additionnels pour logo CAP
```

### Configuration du header
```php
@section('header-h2-size')     // Taille h2 header (défaut: 14px)
@section('header-h2-margin')   // Marge h2 (défaut: 2px 0)
@section('header-h3-size')     // Taille h3 (défaut: 13px)
@section('header-h3-top')      // Position top h3 (défaut: 0)
@section('header-p-size')      // Taille paragraphe (défaut: 10px)
@section('header-p-top')       // Position top paragraphe (défaut: 0)
@section('header-hr-border')   // Style bordure HR (défaut: .7px solid black)
@section('header-hr-top')      // Position top HR (défaut: 0)
```

### Configuration des polices personnalisées
```php
@section('font-faces')         // Définition @font-face
```

### Sections de contenu
```php
@section('document-title')     // Titre du document (ex: BULLETIN DE NOTES)
@section('hide-annee')         // true = masquer l'année académique
@section('custom-header')      // Header complètement personnalisé
@section('info-table')         // Tableau d'informations
@section('content')            // Contenu principal
@section('additional-content') // Contenu additionnel
@section('extra-styles')       // Styles CSS supplémentaires
```

### Configuration du footer
```php
@section('footer-text')        // Texte du footer personnalisé
@section('hide-footer')        // true = masquer le footer
```

## Exemples d'utilisation

### 1. Bulletin de notes (`epac-bulletin.blade.php`)

**Caractéristiques:**
- Police 12px, poids normal
- Logos en format JPEG
- QR code intégré
- Header avec positions ajustées (top: -20px, -35px, -45px)
- Footer personnalisé avec note explicative

**Variables requises:**
```php
$annee              // Année académique
$qrcode             // QR code en base64
$etudiant           // Objet étudiant avec: matricule, genre, nom, prenoms, date_naissance, lieu_de_naissance, filiere
$bulletin_data      // Array des données du bulletin
$signataire         // Objet avec: nomination
```

**Utilisation:**
```php
return PDF::loadView('core::pdfs.epac-bulletin', [
    'annee' => '2024-2025',
    'qrcode' => base64_encode($qr),
    'etudiant' => $student,
    'bulletin_data' => $data,
    'signataire' => $signatory
])->stream();
```

### 2. Certificat préparatoire (`epac-certificat-classes-preparatoires.blade.php`)

**Caractéristiques:**
- Police 13pt Albertus Medium
- Polices personnalisées (ALGERIA, Pristina, Berlin Sans FB)
- Marges: 5cm 2.5cm 2cm 2.5cm
- Header complètement personnalisé
- Sans footer

**Variables requises:**
```php
$etudiant           // Objet avec: genre, nom, prenoms, date_naissance, lieu_naissance, pays_naissance, matricule, ne_vers, date_soutenance, filiere
$signataire         // Objet avec: poste, nomination
```

**Utilisation:**
```php
return PDF::loadView('core::pdfs.epac-certificat-classes-preparatoires', [
    'etudiant' => $student,
    'signataire' => $signatory
])->stream();
```

### 3. Fiche de présence (`epac-liste-presence.blade.php`)

**Caractéristiques:**
- Police 12px
- Logos en PNG
- Tableau avec émargement début/fin

**Variables requises:**
```php
$annee              // Année académique
$filiere            // Nom de la filière
$classe             // Code classe
$etudiantsEnAttente // Collection d'étudiants
```

## Structure des fichiers

```
pdfs/
├── epac-base.blade.php                              # 🎨 Layout parent flexible
├── epac-bulletin.blade.php                          # ✅ Bulletin refactorisé
├── epac-certificat-classes-preparatoires.blade.php  # ✅ Certificat refactorisé
├── epac-liste-presence.blade.php                    # ✅ Liste présence refactorisée
├── fiche-recapitulatif-notes.blade.php             # ✅ Fiche récap refactorisée
├── liste-emargement.blade.php                       # ⚠️  Ancienne version
├── liste-presence.blade.php                         # ⚠️  Ancienne version
├── bulletin.blade.php                               # ⚠️  Ancienne version (base.blade.php)
├── certificat-classes-preparatoires.blade.php       # ⚠️  Ancienne version (base.blade.php)
├── base.blade.php                                   # ⚠️  Ancien layout (non EPAC)
├── attestation-licence.blade.php
├── decision-annee-academique.blade.php
├── liste-cuca-cuo.blade.php
├── document.blade.php
└── report.blade.php
```

## Avantages de la refactorisation

### ✅ Code DRY (Don't Repeat Yourself)
- Header EPAC/CAP défini une seule fois
- Styles CSS communs centralisés
- Footer standardisé

### ✅ Maintenance facilitée
- Modification du logo EPAC/CAP en un seul endroit
- Changement d'adresse/contact centralisé
- Mise à jour des styles globale

### ✅ Flexibilité maximale
- Sections @yield permettent la personnalisation
- Header personnalisable ou remplaçable
- Styles CSS extensibles

### ✅ Cohérence visuelle
- Tous les documents partagent le même style de base
- Positionnement uniforme des éléments
- Typographie cohérente

## Migration des anciens templates

Pour migrer un ancien template vers `epac-base.blade.php`:

1. **Remplacer** `@extends('core::pdfs.base')` par `@extends('core::pdfs.epac-base')`
2. **Configurer** les sections de style (tailles, marges, etc.)
3. **Définir** le contenu dans les sections appropriées
4. **Tester** le rendu PDF

## Dépendances

### Images requises dans `storage/images/`
- `epac.png` ou `epac.jpeg` - Logo EPAC
- `cap.png` ou `cap.jpeg` - Logo CAP
- `banner.png` - Séparateur de sections

### Polices requises dans `storage/fonts/` (pour certificat)
- `ALGERIA.ttf`
- `arial.ttf`
- `albr55w.ttf` (Albertus Medium)
- `PRISTINA.ttf`
- `Berlin Sans FB Regular.ttf`

## Notes techniques

- Les templates utilisent Laravel Blade
- Compatible avec barryvdh/laravel-dompdf ou similaire
- Support des images en base64 (QR codes)
- Les fonctions helpers comme `translateEnglishDateToFrench()` doivent être définies

## TODO / Améliorations futures

- [ ] Migrer `attestation-licence.blade.php` vers epac-base
- [ ] Migrer `decision-annee-academique.blade.php` vers epac-base
- [ ] Migrer `liste-cuca-cuo.blade.php` vers epac-base
- [ ] Créer des composants Blade réutilisables (signatures, info-box, etc.)
- [ ] Ajouter support multilingue
- [ ] Documenter les helpers requis
