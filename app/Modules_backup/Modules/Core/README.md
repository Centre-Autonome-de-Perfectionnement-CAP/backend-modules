# Module Core

Le module Core fournit des services centralisés pour l'envoi d'emails et la génération de PDFs. 
**Ce module ne contient pas de contrôleurs ni de routes API** - il fournit uniquement des services utilisables par les autres modules.

## Services disponibles

### 1. MailService

Service centralisé pour l'envoi d'emails avec templates prédéfinis.

#### Méthodes principales

```php
// Envoyer un email simple
sendEmail($to, string $subject, string $view, array $data = [], array $attachments = []): bool

// Envoyer avec un template prédéfini
sendWithTemplate($to, string $subject, string $template, array $data = [], array $attachments = []): bool

// Envoyer une notification générique
sendNotification($to, string $subject, string $title, string $message, array $additionalData = []): bool

// Envoyer en masse
sendBulkEmail(array $recipients, string $subject, string $view, array $data = []): array
```

### 2. PdfService

Service centralisé pour la génération et le téléchargement de PDFs.

#### Méthodes principales

```php
// Générer un PDF
generatePdf(string $view, array $data = [], array $options = [])

// Télécharger un PDF
downloadPdf(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])

// Télécharger avec template prédéfini
downloadWithTemplate(string $template, array $data = [], string $filename = 'document.pdf', array $options = [])

// Afficher dans le navigateur
streamPdf(string $view, array $data = [], array $options = [])

// Sauvegarder sur disque
savePdf(string $view, array $data = [], string $path, array $options = []): bool
```

## Templates Email Disponibles

### Templates pour les inscriptions

#### 1. `accuse-reception-dossier`
Accusé de réception du dossier d'inscription.

**Variables :**
- `$candidat['nom']` : Nom du candidat
- `$numeroDossier` : Numéro unique du dossier
- `$dateReception` : Date de réception
- `$programme` : Programme demandé
- `$specialite` (optionnel) : Spécialité
- `$anneeAcademique` : Année académique
- `$documentsRecus` : Liste des documents reçus
- `$documentsManquants` (optionnel) : Documents manquants
- `$urlSuivi` (optionnel) : URL de suivi du dossier

#### 2. `acceptation-candidature`
Notification d'acceptation de candidature.

**Variables :**
- `$candidat['nom']` : Nom du candidat
- `$numeroDossier` : Numéro de dossier
- `$programme` : Programme
- `$anneeAcademique` : Année académique
- `$matricule` (optionnel) : Matricule attribué
- `$dateRentree` (optionnel) : Date de rentrée
- `$montantInscription` (optionnel) : Montant à payer
- `$documentsAFournir` (optionnel) : Documents requis
- `$urlConfirmation` (optionnel) : URL de confirmation

#### 3. `rejet-candidature`
Notification de rejet de candidature.

**Variables :**
- `$candidat['nom']` : Nom du candidat
- `$numeroDossier` : Numéro de dossier
- `$programme` : Programme
- `$motifRejet` (optionnel) : Motif du rejet
- `$programmesAlternatifs` (optionnel) : Programmes alternatifs
- `$suggestions` (optionnel) : Suggestions

### Templates pour les paiements

#### 4. `accuse-reception-quittance`
Accusé de réception de quittance de paiement.

**Variables :**
- `$etudiant['nom']`, `$etudiant['matricule']`
- `$numeroReference` : Référence unique
- `$typePaiement` : Type de paiement
- `$montant` : Montant
- `$numeroBordereau` (optionnel) : Numéro de bordereau
- `$urlSuivi` (optionnel) : URL de suivi

#### 5. `acceptation-quittance`
Validation de la quittance de paiement.

**Variables :**
- `$etudiant['nom']`, `$etudiant['matricule']`
- `$numeroReference` : Référence
- `$typePaiement` : Type
- `$montant` : Montant payé
- `$dateValidation` : Date de validation
- `$soldRestant` (optionnel) : Solde restant avec `['total', 'paye', 'restant']`
- `$recuPaiement` (optionnel) : URL du reçu
- `$prochainePaiement` (optionnel) : Prochain paiement

#### 6. `rejet-quittance`
Rejet de la quittance avec motifs.

**Variables :**
- `$etudiant['nom']`, `$etudiant['matricule']`
- `$numeroReference` : Référence
- `$motifsRejet` : Liste des motifs
- `$actionsRequises` : Actions à effectuer
- `$montantCorrect` (optionnel) : Montant correct
- `$urlResoumission` (optionnel) : URL de resoumission

### Templates génériques

#### 7. `notification`
Template générique de notification.

#### 8. `welcome`
Email de bienvenue avec identifiants.

#### 9. `password-reset`
Réinitialisation de mot de passe.

#### 10. `base`
Template de base extensible.

## Templates PDF Disponibles

### Templates académiques

#### 1. `liste-presence`
Liste de présence pour les cours.

**Variables :**
- `$cours`, `$enseignant`, `$classe`, `$date`, `$horaire`, `$salle`
- `$etudiants` : Array avec `matricule` et `nom`
- `$anneeAcademique`, `$semestre`

#### 2. `liste-emargement`
Liste d'émargement pour examens.

**Variables :**
- `$examen`, `$matiere`, `$enseignant`, `$classe`, `$date`, `$horaire`, `$duree`, `$salle`
- `$etudiants` : Array avec `matricule`, `nom`, `place`

#### 3. `liste-cuca-cuo`
Liste officielle CUCA-CUO.

**Variables :**
- `$classe`, `$specialite`, `$niveau`, `$anneeAcademique`
- `$etudiants` : Array avec `matricule`, `nom`, `dateNaissance`, `lieuNaissance`, `sexe`, `nationalite`, `diplome`, `etablissementOrigine`
- `$statistiques` (optionnel) : `['garcons', 'filles']`

#### 4. `bulletin`
Bulletin de notes.

**Variables :**
- `$etudiant` : Array avec `nom`, `matricule`, `classe`, `specialite`, `dateNaissance`, `lieuNaissance`
- `$notes` : Array avec `matiere`, `coefficient`, `note`, `appreciation`
- `$semestre`, `$anneeAcademique`
- `$moyenneGenerale`, `$rang`, `$effectifClasse`, `$mention`
- `$decision`, `$appreciationGenerale` (optionnel)

#### 5. `certificat-classes-preparatoires`
Certificat pour classes préparatoires.

**Variables :**
- `$etudiant` : Array avec `nom`, `dateNaissance`, `lieuNaissance`, `nationalite`, `matricule`
- `$classe`, `$specialite`, `$anneeAcademique`
- `$moyenneGenerale` (optionnel), `$mention` (optionnel)
- `$resultats` (optionnel) : Array de résultats

#### 6. `attestation-licence`
Attestation de réussite pour licence.

**Variables :**
- `$etudiant` : Array complet
- `$diplome`, `$specialite`, `$option`, `$anneeAcademique`
- `$moyenneGenerale`, `$mention`, `$session`
- `$jury` (optionnel) : `['date', 'decision']`

#### 7. `fiche-recapitulatif-notes`
Fiche récapitulative complète.

**Variables :**
- `$etudiant` : Informations complètes
- `$semestres` : Array de semestres avec UE et matières
- `$moyenneGenerale`, `$totalCredits`, `$creditsAcquis`, `$mention`, `$decision`

#### 8. `decision-annee-academique`
Décision du conseil de classe.

**Variables :**
- `$etudiant` : Informations complètes
- `$semestres` : Résultats par semestre
- `$moyenneAnnuelle`, `$rang`, `$effectifClasse`
- `$decision`, `$decisionDetails`, `$appreciation`
- `$classeSuperieure` (optionnel), `$conditions` (optionnel)

#### 9. `document`
Template document générique.

#### 10. `report`
Template rapport structuré.

#### 11. `base`
Template de base extensible.

## Utilisation dans les autres modules

### Exemple dans le module Inscription

```php
<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Core\Services\MailService;
use App\Modules\Core\Services\PdfService;

class InscriptionService
{
    public function __construct(
        private MailService $mailService,
        private PdfService $pdfService
    ) {}

    public function envoyerConfirmationDossier($candidat, $dossier)
    {
        return $this->mailService->sendWithTemplate(
            $candidat->email,
            'Accusé de réception de votre dossier',
            'accuse-reception-dossier',
            [
                'candidat' => ['nom' => $candidat->nom],
                'numeroDossier' => $dossier->numero,
                'dateReception' => now()->format('d/m/Y'),
                'programme' => $dossier->programme,
                'anneeAcademique' => '2024/2025',
                'documentsRecus' => $dossier->documents->pluck('nom')->toArray()
            ]
        );
    }

    public function genererBulletin($etudiant, $semestre)
    {
        return $this->pdfService->downloadWithTemplate(
            'bulletin',
            [
                'etudiant' => [
                    'nom' => $etudiant->nom,
                    'matricule' => $etudiant->matricule,
                    'classe' => $etudiant->classe
                ],
                'semestre' => 'Semestre ' . $semestre,
                'notes' => $etudiant->notes->toArray(),
                'moyenneGenerale' => $etudiant->moyenne
            ],
            "bulletin-{$etudiant->matricule}.pdf"
        );
    }
}
```

### Exemple dans le module Finance

```php
<?php

namespace App\Modules\Finance\Services;

use App\Modules\Core\Services\MailService;
use App\Modules\Core\Services\PdfService;

class PaiementService
{
    public function __construct(
        private MailService $mailService,
        private PdfService $pdfService
    ) {}

    public function validerQuittance($etudiant, $quittance)
    {
        // Envoyer l'email de validation
        $this->mailService->sendWithTemplate(
            $etudiant->email,
            'Paiement validé',
            'acceptation-quittance',
            [
                'etudiant' => [
                    'nom' => $etudiant->nom,
                    'matricule' => $etudiant->matricule
                ],
                'numeroReference' => $quittance->reference,
                'typePaiement' => $quittance->type,
                'montant' => $quittance->montant . ' FCFA',
                'soldRestant' => [
                    'total' => '500 000 FCFA',
                    'paye' => '300 000 FCFA',
                    'restant' => '200 000 FCFA'
                ]
            ]
        );

        // Générer le reçu PDF
        return $this->pdfService->downloadWithTemplate(
            'document',
            [
                'title' => 'Reçu de Paiement',
                'content' => '<p>Paiement validé</p>',
                'data' => [
                    'Référence' => $quittance->reference,
                    'Montant' => $quittance->montant . ' FCFA'
                ]
            ],
            "recu-{$quittance->reference}.pdf"
        );
    }
}
```

## Structure du Module

```
app/Modules/Core/
├── Services/
│   ├── MailService.php           # Service d'envoi d'emails
│   └── PdfService.php             # Service de génération de PDFs
├── Providers/
│   └── CoreServiceProvider.php    # Service Provider
├── resources/views/
│   ├── emails/                    # Templates d'emails
│   │   ├── base.blade.php
│   │   ├── notification.blade.php
│   │   ├── welcome.blade.php
│   │   ├── password-reset.blade.php
│   │   ├── accuse-reception-dossier.blade.php
│   │   ├── acceptation-candidature.blade.php
│   │   ├── rejet-candidature.blade.php
│   │   ├── accuse-reception-quittance.blade.php
│   │   ├── acceptation-quittance.blade.php
│   │   └── rejet-quittance.blade.php
│   └── pdfs/                      # Templates PDF
│       ├── base.blade.php
│       ├── document.blade.php
│       ├── report.blade.php
│       ├── liste-presence.blade.php
│       ├── liste-emargement.blade.php
│       ├── liste-cuca-cuo.blade.php
│       ├── bulletin.blade.php
│       ├── certificat-classes-preparatoires.blade.php
│       ├── attestation-licence.blade.php
│       ├── fiche-recapitulatif-notes.blade.php
│       └── decision-annee-academique.blade.php
└── Tests/
    └── CoreServicesTest.php       # Tests unitaires
```

## Installation

Le module est déjà configuré et opérationnel.

**Provider enregistré** dans `bootstrap/providers.php` :
```php
App\Modules\Core\Providers\CoreServiceProvider::class,
```

**Dépendances installées** :
- `barryvdh/laravel-dompdf` : Génération de PDFs
- `maatwebsite/excel` : Génération Excel
- `phpoffice/phpword` : Génération Word

## Bonnes Pratiques

### 1. Injection de dépendance
Toujours utiliser l'injection de dépendance :
```php
public function __construct(
    private MailService $mailService,
    private PdfService $pdfService
) {}
```

### 2. Gestion des erreurs
```php
try {
    $result = $this->mailService->sendEmail(...);
    if (!$result) {
        Log::warning('Email non envoyé');
    }
} catch (\Exception $e) {
    Log::error('Erreur: ' . $e->getMessage());
}
```

### 3. Nettoyage des fichiers temporaires
```php
$tempPath = storage_path('app/temp/file.pdf');
try {
    $this->pdfService->savePdf(..., $tempPath);
    // Utiliser le fichier
} finally {
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
}
```

### 4. Files d'attente pour emails en masse
```php
use Illuminate\Support\Facades\Queue;

foreach ($etudiants as $etudiant) {
    Queue::push(fn() => $this->mailService->sendEmail(...));
}
```

## Tests

```bash
php artisan test --filter=CoreServicesTest
```

## Support

Le module Core est un module de services pur - il ne contient pas de routes ni de contrôleurs.
Tous les autres modules doivent utiliser ses services via l'injection de dépendance.
