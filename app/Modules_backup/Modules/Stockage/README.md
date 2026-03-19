# Module Stockage - Documentation

Module professionnel de gestion de fichiers avec système de permissions et partages pour EasySMI.

## 📋 Table des matières

- [Caractéristiques](#caractéristiques)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [API Endpoints](#api-endpoints)
- [Intégration avec d'autres modules](#intégration-avec-dautres-modules)

## ✨ Caractéristiques

### Gestion des fichiers
- ✅ Upload de fichiers avec métadonnées
- ✅ Téléchargement sécurisé
- ✅ Fichiers publics et privés
- ✅ Organisation par collections
- ✅ Verrouillage de fichiers
- ✅ Soft delete avec rétention
- ✅ Historique des activités

### Système de permissions
- ✅ Permissions granulaires (read, write, delete, share, admin)
- ✅ Attribution par utilisateur ou par rôle
- ✅ Permissions temporaires avec expiration
- ✅ Vérification automatique des droits

### Partage de fichiers
- ✅ Génération de liens de partage sécurisés
- ✅ Protection par mot de passe
- ✅ Limitation du nombre de téléchargements
- ✅ Expiration automatique
- ✅ Accès public sans authentification

### Sécurité
- ✅ Fichiers privés non accessibles via URL directe
- ✅ Vérification d'intégrité (hash SHA-256)
- ✅ Logging complet des activités
- ✅ Policies Laravel pour l'autorisation

## 🏗️ Architecture

```
app/Modules/Stockage/
├── Http/
│   ├── Controllers/          # Contrôleurs REST
│   ├── Requests/             # Validation des requêtes
│   ├── Resources/            # Transformation des réponses API
│   └── Middleware/           
├── Models/                   # Modèles Eloquent
│   ├── File.php
│   ├── FilePermission.php
│   ├── FileShare.php
│   ├── FileActivity.php
│   ├── Role.php
│   └── Permission.php
├── Services/                 # Logique métier
│   ├── FileStorageService.php
│   ├── PermissionService.php
│   └── FileShareService.php
├── Policies/                 # Autorisations
│   └── FilePolicy.php
├── Traits/                   # Traits réutilisables
│   ├── HasFiles.php
│   └── HasRolesAndPermissions.php
├── database/
│   └── migrations/           # Migrations de base de données
├── routes/
│   └── api.php              # Routes API
├── config/
│   └── stockage.php         # Configuration
└── Providers/
    └── StockageServiceProvider.php
```

## 📦 Installation

### 1. Vérifier que le ServiceProvider est enregistré

Dans `bootstrap/providers.php`, assurez-vous que le provider est enregistré :

```php
return [
    App\Modules\Stockage\Providers\StockageServiceProvider::class,
];
```

### 2. Exécuter les migrations

```bash
php artisan migrate
```

### 3. Créer les dossiers de stockage

```bash
mkdir -p storage/app/private/files
mkdir -p storage/app/public/files
mkdir -p storage/app/temp
```

### 4. Publier la configuration (optionnel)

```bash
php artisan vendor:publish --tag=stockage-config
```

## ⚙️ Configuration

### Configuration des disks (déjà faite dans `config/filesystems.php`)

Les disks suivants sont préconfigurés :
- `private_files` : Fichiers privés (non accessibles publiquement)
- `public_files` : Fichiers publics (accessibles via API)
- `temp` : Fichiers temporaires

### Variables d'environnement

Ajoutez ces variables dans votre `.env` :

```env
# Stockage
STOCKAGE_MAX_FILE_SIZE=51200  # 50MB en KB
STOCKAGE_PRIVATE_DISK=private_files
STOCKAGE_PUBLIC_DISK=public_files
STOCKAGE_RETENTION_DAYS=30
STOCKAGE_ENABLE_LOGGING=true
STOCKAGE_LOG_RETENTION_DAYS=90
STOCKAGE_AUTO_CLEANUP=true
```

## 🚀 Utilisation

### Ajouter les traits au modèle User

Pour utiliser les fonctionnalités du module, ajoutez les traits au modèle User :

```php
use App\Modules\Stockage\Traits\HasFiles;
use App\Modules\Stockage\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasFiles, HasRolesAndPermissions;
    
    // ... reste du code
}
```

### Upload de fichier depuis un autre module

```php
use App\Modules\Stockage\Services\FileStorageService;

class InvoiceController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService
    ) {}
    
    public function attachFile(Request $request, Invoice $invoice)
    {
        $file = $this->storageService->uploadFile(
            uploadedFile: $request->file('document'),
            userId: auth()->id(),
            visibility: 'private',
            collection: 'invoices',
            moduleName: 'Finance',
            moduleResourceType: 'invoice',
            moduleResourceId: $invoice->id,
            metadata: [
                'invoice_number' => $invoice->number,
                'department' => 'accounting',
            ]
        );
        
        return response()->json([
            'success' => true,
            'file' => $file,
        ]);
    }
}
```

### Récupérer les fichiers d'un module

```php
// Récupérer tous les fichiers d'une facture
$files = File::forModule('Finance', 'invoice', $invoiceId)->get();

// Récupérer les fichiers d'un étudiant
$files = File::forModule('Inscription', 'student', $studentId)->get();
```

### Gérer les permissions

```php
use App\Modules\Stockage\Services\PermissionService;

class FilePermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}
    
    public function shareWithUser(File $file, User $user)
    {
        // Accorder la permission de lecture
        $this->permissionService->grantUserPermission(
            file: $file,
            userId: $user->id,
            permissionType: 'read',
            grantedBy: auth()->id(),
            expiresAt: now()->addDays(30)
        );
    }
    
    public function shareWithRole(File $file, Role $role)
    {
        // Accorder la permission à un rôle
        $this->permissionService->grantRolePermission(
            file: $file,
            roleId: $role->id,
            permissionType: 'read',
            grantedBy: auth()->id()
        );
    }
    
    public function checkAccess(File $file, User $user)
    {
        // Vérifier si l'utilisateur peut accéder au fichier
        $canAccess = $this->permissionService->canAccess($file, $user->id);
        
        return response()->json([
            'can_access' => $canAccess,
        ]);
    }
}
```

### Créer un lien de partage

```php
use App\Modules\Stockage\Services\FileShareService;

class ShareController extends Controller
{
    public function __construct(
        protected FileShareService $shareService
    ) {}
    
    public function createShare(File $file)
    {
        $share = $this->shareService->createShare(
            file: $file,
            createdBy: auth()->id(),
            options: [
                'password' => 'secret123',        // Optionnel
                'allow_download' => true,
                'allow_preview' => true,
                'max_downloads' => 10,            // Optionnel
                'expires_at' => now()->addDays(7), // Optionnel
            ]
        );
        
        // L'URL de partage est accessible via $share->share_url
        return response()->json([
            'share_url' => $share->share_url,
            'token' => $share->token,
        ]);
    }
}
```

### Fichiers publics et accès sans authentification

#### Uploader un fichier public

```php
use App\Modules\Stockage\Services\FileStorageService;

class PublicContentController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService
    ) {}
    
    /**
     * Upload d'un logo d'entreprise (public)
     */
    public function uploadCompanyLogo(Request $request)
    {
        $file = $this->storageService->uploadFile(
            uploadedFile: $request->file('logo'),
            userId: auth()->id(),
            visibility: 'public',  // ← Fichier accessible publiquement
            collection: 'company_assets',
            moduleName: 'Configuration',
            moduleResourceType: 'company',
            moduleResourceId: 1,
            metadata: [
                'asset_type' => 'logo',
                'dimensions' => '500x500',
            ]
        );
        
        // Le fichier public aura une URL accessible
        return response()->json([
            'success' => true,
            'file' => $file,
            'public_url' => $file->url, // URL publique
        ]);
    }
    
    /**
     * Upload d'un document de cours (public pour tous les étudiants)
     */
    public function uploadPublicCourse(Request $request)
    {
        $file = $this->storageService->uploadFile(
            uploadedFile: $request->file('document'),
            userId: auth()->id(),
            visibility: 'public',
            collection: 'courses',
            moduleName: 'Pedagogie',
            moduleResourceType: 'course',
            moduleResourceId: $request->input('course_id'),
            metadata: [
                'course_code' => $request->input('course_code'),
                'semester' => $request->input('semester'),
                'document_type' => 'slides',
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Document de cours uploadé et accessible à tous',
            'download_url' => route('api.files.download', $file->id),
        ]);
    }
}
```

#### Récupérer les fichiers publics (sans authentification)

```php
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Models\File;

class PublicFilesController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService
    ) {}
    
    /**
     * Liste des documents publics d'un cours (pas d'auth requise)
     */
    public function getCourseDocuments($courseId)
    {
        // Récupérer tous les fichiers publics d'un cours
        $files = File::public()
            ->forModule('Pedagogie', 'course', $courseId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'files' => $files->map(fn($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => $file->size_for_humans,
                'type' => $file->mime_type,
                'download_url' => route('api.files.public.download', $file->id),
                'uploaded_at' => $file->created_at,
            ]),
        ]);
    }
    
    /**
     * Télécharger un fichier public (pas d'auth requise)
     */
    public function downloadPublicFile(File $file)
    {
        // Vérifier que le fichier est bien public
        if ($file->visibility !== 'public') {
            abort(403, 'Ce fichier n\'est pas public');
        }
        
        $download = $this->storageService->downloadFile($file, null);
        
        return response()->stream(
            function () use ($download) {
                echo $download['stream'];
            },
            200,
            [
                'Content-Type' => $download['mimeType'],
                'Content-Disposition' => 'attachment; filename="' . $download['filename'] . '"',
            ]
        );
    }
}
```

### Cas d'usage pratiques depuis d'autres modules

#### Module Finance - Factures avec pièces jointes

```php
namespace App\Modules\Finance\Controllers;

use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Services\PermissionService;
use App\Modules\Stockage\Models\File;

class InvoiceController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService,
        protected PermissionService $permissionService
    ) {}
    
    /**
     * Créer une facture avec pièces jointes
     */
    public function store(Request $request)
    {
        $invoice = Invoice::create($request->validated());
        
        // Attacher plusieurs fichiers à la facture
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $attachment) {
                $file = $this->storageService->uploadFile(
                    uploadedFile: $attachment,
                    userId: auth()->id(),
                    visibility: 'private', // Factures privées par défaut
                    collection: 'invoices',
                    moduleName: 'Finance',
                    moduleResourceType: 'invoice',
                    moduleResourceId: $invoice->id,
                    metadata: [
                        'invoice_number' => $invoice->number,
                        'invoice_date' => $invoice->date,
                        'client_id' => $invoice->client_id,
                    ]
                );
                
                // Donner accès au client (utilisateur)
                if ($invoice->client_user_id) {
                    $this->permissionService->grantUserPermission(
                        file: $file,
                        userId: $invoice->client_user_id,
                        permissionType: 'read',
                        grantedBy: auth()->id()
                    );
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'invoice' => $invoice,
            'attachments_count' => $request->file('attachments') ? count($request->file('attachments')) : 0,
        ]);
    }
    
    /**
     * Récupérer les pièces jointes d'une facture
     */
    public function getAttachments(Invoice $invoice)
    {
        $files = File::forModule('Finance', 'invoice', $invoice->id)
            ->get();
        
        // Filtrer selon les permissions de l'utilisateur
        $accessibleFiles = $files->filter(function($file) {
            return $this->permissionService->canAccess($file, auth()->id());
        });
        
        return response()->json([
            'success' => true,
            'files' => $accessibleFiles->values(),
        ]);
    }
    
    /**
     * Partager une facture avec un comptable externe
     */
    public function shareWithAccountant(Invoice $invoice, Request $request)
    {
        $files = File::forModule('Finance', 'invoice', $invoice->id)->get();
        
        foreach ($files as $file) {
            // Accorder permission temporaire (30 jours)
            $this->permissionService->grantUserPermission(
                file: $file,
                userId: $request->input('accountant_user_id'),
                permissionType: 'read',
                grantedBy: auth()->id(),
                expiresAt: now()->addDays(30)
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Accès accordé au comptable pour 30 jours',
        ]);
    }
}
```

#### Module Inscription - Dossier étudiant avec documents

```php
namespace App\Modules\Inscription\Controllers;

use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Services\PermissionService;
use App\Modules\Stockage\Models\File;

class StudentDocumentController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService,
        protected PermissionService $permissionService
    ) {}
    
    /**
     * Upload de documents par l'étudiant
     */
    public function uploadDocument(Request $request, Student $student)
    {
        // Vérifier que c'est bien l'étudiant ou un admin
        $this->authorize('manageDocuments', $student);
        
        $file = $this->storageService->uploadFile(
            uploadedFile: $request->file('document'),
            userId: auth()->id(),
            visibility: 'private',
            collection: 'student_documents',
            moduleName: 'Inscription',
            moduleResourceType: 'student',
            moduleResourceId: $student->id,
            metadata: [
                'document_type' => $request->input('document_type'), // 'id_card', 'diploma', 'photo', etc.
                'academic_year' => $request->input('academic_year'),
                'verification_status' => 'pending',
            ]
        );
        
        // Donner accès au personnel administratif (rôle)
        $adminRole = Role::where('name', 'admin_inscription')->first();
        if ($adminRole) {
            $this->permissionService->grantRolePermission(
                file: $file,
                roleId: $adminRole->id,
                permissionType: 'read',
                grantedBy: auth()->id()
            );
        }
        
        return response()->json([
            'success' => true,
            'file' => $file,
            'message' => 'Document uploadé avec succès',
        ]);
    }
    
    /**
     * Récupérer le dossier complet d'un étudiant
     */
    public function getStudentFolder(Student $student)
    {
        $this->authorize('viewDocuments', $student);
        
        // Récupérer tous les documents de l'étudiant
        $files = File::forModule('Inscription', 'student', $student->id)
            ->get()
            ->groupBy(function($file) {
                return $file->metadata['document_type'] ?? 'other';
            });
        
        return response()->json([
            'success' => true,
            'student' => $student,
            'documents' => $files,
            'total_documents' => $files->flatten()->count(),
        ]);
    }
    
    /**
     * Générer un lien de téléchargement temporaire pour un parent
     */
    public function generateParentAccess(Student $student)
    {
        $this->authorize('manageDocuments', $student);
        
        $files = File::forModule('Inscription', 'student', $student->id)
            ->where('metadata->document_type', 'report_card') // Bulletins uniquement
            ->get();
        
        $shareLinks = [];
        foreach ($files as $file) {
            $share = app(FileShareService::class)->createShare(
                file: $file,
                createdBy: auth()->id(),
                options: [
                    'allow_download' => true,
                    'allow_preview' => true,
                    'max_downloads' => 5,
                    'expires_at' => now()->addDays(15),
                ]
            );
            
            $shareLinks[] = [
                'file_name' => $file->original_name,
                'share_url' => $share->share_url,
                'expires_at' => $share->expires_at,
            ];
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Liens de partage générés pour les parents',
            'share_links' => $shareLinks,
        ]);
    }
}
```

#### Module RH - CV et documents du personnel

```php
namespace App\Modules\RH\Controllers;

use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Models\File;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService
    ) {}
    
    /**
     * Upload du CV d'un candidat
     */
    public function uploadCV(Request $request)
    {
        $file = $this->storageService->uploadFile(
            uploadedFile: $request->file('cv'),
            userId: auth()->id(),
            visibility: 'private',
            collection: 'recruitment',
            moduleName: 'RH',
            moduleResourceType: 'candidate',
            moduleResourceId: auth()->id(),
            metadata: [
                'document_type' => 'cv',
                'position_applied' => $request->input('position'),
                'submission_date' => now(),
            ]
        );
        
        // Donner accès automatiquement au service RH
        $rhRole = Role::where('name', 'rh_manager')->first();
        if ($rhRole) {
            app(PermissionService::class)->grantRolePermission(
                file: $file,
                roleId: $rhRole->id,
                permissionType: 'read',
                grantedBy: auth()->id()
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'CV soumis avec succès',
        ]);
    }
    
    /**
     * Archiver les documents d'un employé qui quitte
     */
    public function archiveEmployeeDocuments(Employee $employee)
    {
        $files = File::forModule('RH', 'employee', $employee->id)->get();
        
        foreach ($files as $file) {
            // Changer la collection vers archives
            $this->storageService->moveToCollection(
                file: $file,
                newCollection: 'archives_' . date('Y'),
                userId: auth()->id()
            );
            
            // Verrouiller pour éviter modifications
            $this->storageService->lockFile($file, auth()->id());
        }
        
        return response()->json([
            'success' => true,
            'message' => count($files) . ' documents archivés et verrouillés',
        ]);
    }
}
```

### Changer la visibilité d'un fichier

```php
use App\Modules\Stockage\Services\FileStorageService;

class FileVisibilityController extends Controller
{
    public function __construct(
        protected FileStorageService $storageService
    ) {}
    
    /**
     * Rendre un document de cours public
     */
    public function makePublic(File $file)
    {
        $this->authorize('changeVisibility', $file);
        
        // Changer de privé à public
        $file = $this->storageService->changeVisibility(
            file: $file,
            visibility: 'public',
            userId: auth()->id()
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Le fichier est maintenant accessible publiquement',
            'public_url' => $file->url,
        ]);
    }
    
    /**
     * Remettre un fichier en privé
     */
    public function makePrivate(File $file)
    {
        $this->authorize('changeVisibility', $file);
        
        $file = $this->storageService->changeVisibility(
            file: $file,
            visibility: 'private',
            userId: auth()->id()
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Le fichier est maintenant privé',
        ]);
    }
}
```

## 📡 API Endpoints

### Fichiers

```http
# Lister les fichiers accessibles
GET /api/files?collection=documents&search=invoice

# Upload un fichier
POST /api/files
Content-Type: multipart/form-data
{
    "file": <fichier>,
    "visibility": "private",
    "collection": "documents",
    "module_name": "Finance",
    "module_resource_type": "invoice",
    "module_resource_id": 123,
    "metadata": {}
}

# Détails d'un fichier
GET /api/files/{file}

# Télécharger un fichier
GET /api/files/{file}/download

# Modifier un fichier
PUT /api/files/{file}
{
    "collection": "archives",
    "metadata": {}
}

# Supprimer un fichier
DELETE /api/files/{file}

# Changer la visibilité
POST /api/files/{file}/visibility
{
    "visibility": "public"
}

# Verrouiller/Déverrouiller
POST /api/files/{file}/lock
POST /api/files/{file}/unlock

# Historique des activités
GET /api/files/{file}/activities

# Fichiers publics (sans auth)
GET /api/files/public
```

### Permissions

```http
# Lister les permissions d'un fichier
GET /api/files/{file}/permissions

# Accorder une permission
POST /api/files/{file}/permissions/grant
{
    "user_id": 123,              # OU role_id
    "permission_type": "read",
    "expires_at": "2025-12-31"
}

# Révoquer une permission
POST /api/files/{file}/permissions/revoke
{
    "user_id": 123,
    "permission_type": "read"
}

# Vérifier une permission
POST /api/files/{file}/permissions/check
{
    "permission_type": "read"
}
```

### Partages

```http
# Lister les partages d'un fichier
GET /api/files/{file}/shares

# Créer un partage
POST /api/files/{file}/shares
{
    "password": "secret",
    "allow_download": true,
    "max_downloads": 10,
    "expires_at": "2025-12-31"
}

# Désactiver un partage
POST /api/files/{file}/shares/{share}/deactivate

# Supprimer un partage
DELETE /api/files/{file}/shares/{share}

# Accéder à un fichier partagé (sans auth)
GET /api/files/share/{token}?password=secret

# Télécharger via un partage (sans auth)
GET /api/files/share/{token}/download?password=secret
```

## 🔗 Intégration avec d'autres modules

### Module Finance

```php
// Dans InvoiceController
public function store(Request $request)
{
    $invoice = Invoice::create($request->validated());
    
    // Attacher des fichiers
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $this->storageService->uploadFile(
                uploadedFile: $file,
                userId: auth()->id(),
                visibility: 'private',
                collection: 'invoices',
                moduleName: 'Finance',
                moduleResourceType: 'invoice',
                moduleResourceId: $invoice->id
            );
        }
    }
    
    return response()->json($invoice);
}
```

### Module Inscription

```php
// Dans StudentController
public function uploadDocument(Request $request, Student $student)
{
    $file = $this->storageService->uploadFile(
        uploadedFile: $request->file('document'),
        userId: auth()->id(),
        visibility: 'private',
        collection: 'student_documents',
        moduleName: 'Inscription',
        moduleResourceType: 'student',
        moduleResourceId: $student->id,
        metadata: [
            'document_type' => $request->input('document_type'),
            'academic_year' => $request->input('academic_year'),
        ]
    );
    
    return response()->json(['file' => $file]);
}
```

## 🛡️ Sécurité

### Bonnes pratiques

1. **Fichiers sensibles** : Toujours utiliser `visibility: 'private'` pour les documents confidentiels
2. **Permissions** : Accorder le minimum de permissions nécessaires
3. **Partages** : Toujours définir une date d'expiration pour les partages
4. **Validation** : Valider les types MIME et tailles de fichiers côté serveur
5. **Audit** : Le système log automatiquement toutes les actions

### Vérification d'intégrité

Le système calcule un hash SHA-256 pour chaque fichier uploadé, permettant de vérifier l'intégrité des fichiers.

## 📝 Maintenance

### Nettoyage automatique

Le système peut nettoyer automatiquement :
- Les permissions expirées
- Les partages expirés
- Les anciennes activités

Créez une commande artisan pour exécuter le nettoyage :

```bash
php artisan stockage:cleanup
```

---

**Développé avec ❤️ pour EasySMI**
