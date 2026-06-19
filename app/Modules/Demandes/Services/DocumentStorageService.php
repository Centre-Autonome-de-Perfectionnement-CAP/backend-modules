<?php

namespace App\Modules\Demandes\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service centralisé de stockage documentaire — CAP-EPAC
 *
 * Structure normalisée :
 *   storage/app/public/demandes/
 *   └── {type-slug}/
 *       └── {reference}/
 *           ├── demande/
 *           ├── complement/
 *           └── secretaire/
 *
 * Types gérés :
 *   attestation_passage     → attestation-passage
 *   attestation_inscription → attestation-inscription
 *   attestation_definitive  → attestation-definitive
 *   bulletin_annuel         → bulletin
 *
 * Règles :
 *   - Les fichiers sont nommés d'après leur clé métier, jamais le nom original.
 *   - L'extension réelle du fichier est conservée.
 *   - Les dossiers sont créés à la demande, sans jamais provoquer d'erreur
 *     si le dossier existe déjà.
 *   - La priorité de lecture : complement/ > demande/ > null.
 *   - Les fichiers secrétaire sont indépendants de cette logique.
 */
class DocumentStorageService
{
    // ── Mapping type de demande → slug de dossier ─────────────────────────────

    private const TYPE_TO_SLUG = [
        'attestation_passage'     => 'attestation-passage',
        'attestation_inscription' => 'attestation-inscription',
        'attestation_definitive'  => 'attestation-definitive',
        'bulletin_annuel'         => 'bulletin',
    ];

    // ── Libellés métier pour nommer les fichiers ──────────────────────────────

    private const KEY_TO_FILENAME = [
        'demande_manuscrite'      => 'demande-manuscrite',
        'acte_naissance'          => 'acte-naissance',
        'attestation_succes_file' => 'attestation-succes',
        'quittance'               => 'quittance',
        'quittance_online'        => 'quittance-online',
        'recu_paiement'           => 'recu-paiement',
        'bulletin'                => 'bulletin-notes',
        'lettre'                  => 'lettre',
        'document_1'              => 'document-complementaire-1',
        'document_2'              => 'document-complementaire-2',
    ];

    private const DISK = 'public';

    // ════════════════════════════════════════════════════════════════════════════
    // CHEMINS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Retourne le slug de dossier pour un type de demande.
     */
    public function typeSlug(string $type): string
    {
        return self::TYPE_TO_SLUG[$type] ?? str_replace('_', '-', $type);
    }

    /**
     * Chemin de base d'une demande :
     *   demandes/{type-slug}/{reference}
     */
    public function basePath(string $type, string $reference): string
    {
        return 'demandes/' . $this->typeSlug($type) . '/' . $reference;
    }

    /**
     * Chemin du dossier demande/ (documents initiaux).
     */
    public function demandePath(string $type, string $reference): string
    {
        return $this->basePath($type, $reference) . '/demande';
    }

    /**
     * Chemin du dossier complement/ (compléments de dossier).
     */
    public function complementPath(string $type, string $reference): string
    {
        return $this->basePath($type, $reference) . '/complement';
    }

    /**
     * Chemin du dossier secretaire/ (fichiers ajoutés par la secrétaire).
     */
    public function secretairePath(string $type, string $reference): string
    {
        return $this->basePath($type, $reference) . '/secretaire';
    }

    /**
     * Nom de fichier normalisé pour une clé métier + extension.
     *   acte_naissance + pdf → acte-naissance.pdf
     */
    public function filename(string $key, string $extension): string
    {
        $base = self::KEY_TO_FILENAME[$key] ?? str_replace('_', '-', $key);
        return $base . '.' . ltrim($extension, '.');
    }

    // ════════════════════════════════════════════════════════════════════════════
    // CRÉATION DES DOSSIERS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Crée toute la structure de dossiers pour une nouvelle demande.
     * N'échoue jamais si les dossiers existent déjà.
     */
    public function ensureStructure(string $type, string $reference): void
    {
        $folders = [
            $this->demandePath($type, $reference),
            $this->complementPath($type, $reference),
            $this->secretairePath($type, $reference),
        ];

        foreach ($folders as $folder) {
            $this->ensureFolder($folder);
        }
    }

    /**
     * Crée un dossier s'il n'existe pas.
     */
    private function ensureFolder(string $path): void
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->makeDirectory($path);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════
    // STOCKAGE DES FICHIERS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Stocke un ensemble de fichiers dans demande/.
     *
     * @param  UploadedFile[]  $files   Tableau [clé => UploadedFile]
     * @return array<string,string>     Tableau [clé => chemin stocké]
     */
    public function storeDemandeFiles(string $type, string $reference, array $files): array
    {
        $folder = $this->demandePath($type, $reference);
        $this->ensureFolder($folder);

        return $this->storeFiles($folder, $files);
    }

    /**
     * Stocke un ensemble de fichiers dans complement/.
     * Ne supprime jamais ce qui existe dans demande/.
     *
     * @param  UploadedFile[]  $files   Tableau [clé => UploadedFile]
     * @return array<string,string>     Tableau [clé => chemin stocké]
     */
    public function storeComplementFiles(string $type, string $reference, array $files): array
    {
        $folder = $this->complementPath($type, $reference);
        $this->ensureFolder($folder);

        return $this->storeFiles($folder, $files);
    }

    /**
     * Stocke un fichier dans secretaire/.
     * Retourne le chemin stocké.
     */
    public function storeSecretaireFile(string $type, string $reference, UploadedFile $file, string $fileId): string
    {
        $folder = $this->secretairePath($type, $reference);
        $this->ensureFolder($folder);

        $ext      = $file->getClientOriginalExtension() ?: $file->extension();
        $fileName = $fileId . '.' . $ext;

        return $file->storeAs($folder, $fileName, self::DISK);
    }

    /**
     * Stocke un PDF généré (quittance) dans demande/.
     * Retourne le chemin stocké.
     */
    public function storePdfContent(string $type, string $reference, string $filename, string $content): string
    {
        $folder = $this->demandePath($type, $reference);
        $this->ensureFolder($folder);

        $path = $folder . '/' . $filename;
        Storage::disk(self::DISK)->put($path, $content);

        return $path;
    }

    /**
     * Logique de stockage commune : nomme les fichiers d'après leur clé métier.
     *
     * @param  UploadedFile[]  $files
     * @return array<string,string>
     */
    private function storeFiles(string $folder, array $files): array
    {
        $stored = [];

        foreach ($files as $key => $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $ext      = $file->getClientOriginalExtension() ?: $file->extension();
            $fileName = $this->filename($key, $ext);

            $path = $file->storeAs($folder, $fileName, self::DISK);
            $stored[$key] = $path;
        }

        return $stored;
    }

    // ════════════════════════════════════════════════════════════════════════════
    // LECTURE ET RÉSOLUTION DE PRIORITÉ
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Résout les documents actifs pour une demande en appliquant la priorité :
     *   complement/ > demande/ > null
     *
     * Retourne pour chaque clé connue :
     * {
     *   "acte_naissance": {
     *     "version_active": "complement",
     *     "fichier": "demandes/attestation-inscription/ATT-XXXX/complement/acte-naissance.pdf",
     *     "historique": [
     *       "demandes/.../demande/acte-naissance.pdf",
     *       "demandes/.../complement/acte-naissance.pdf"
     *     ]
     *   }
     * }
     *
     * @param  array|string|null  $demandeFiles     Contenu JSON/array de `files`
     * @param  array|string|null  $complementFiles  Contenu JSON/array de `complement_files`
     */
    public function resolveDocuments(
        mixed $demandeFiles,
        mixed $complementFiles
    ): array {
        $initial    = $this->decodeFiles($demandeFiles);
        $complement = $this->decodeFiles($complementFiles);

        // Union de toutes les clés connues
        $allKeys = array_unique(array_merge(array_keys($initial), array_keys($complement)));

        $resolved = [];

        foreach ($allKeys as $key) {
            $inDemande    = $initial[$key]    ?? null;
            $inComplement = $complement[$key] ?? null;

            $historique = array_values(array_filter([$inDemande, $inComplement]));

            if ($inComplement && Storage::disk(self::DISK)->exists($inComplement)) {
                $resolved[$key] = [
                    'version_active' => 'complement',
                    'fichier'        => $inComplement,
                    'historique'     => $historique,
                ];
            } elseif ($inDemande && Storage::disk(self::DISK)->exists($inDemande)) {
                $resolved[$key] = [
                    'version_active' => 'demande',
                    'fichier'        => $inDemande,
                    'historique'     => $historique,
                ];
            } else {
                // Fichier référencé mais absent sur disque (données legacy ou supprimé)
                $resolved[$key] = [
                    'version_active' => null,
                    'fichier'        => null,
                    'historique'     => $historique,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Résout un fichier unique en appliquant la priorité complement > demande.
     * Utilisé pour previewFile() quand source = 'active'.
     *
     * Retourne ['path' => string|null, 'version' => 'complement'|'demande'|null]
     */
    public function resolveActiveFile(
        mixed  $demandeFiles,
        mixed  $complementFiles,
        string $key
    ): array {
        $initial    = $this->decodeFiles($demandeFiles);
        $complement = $this->decodeFiles($complementFiles);

        $inComplement = $complement[$key] ?? null;
        $inDemande    = $initial[$key]    ?? null;

        if ($inComplement && Storage::disk(self::DISK)->exists($inComplement)) {
            return ['path' => $inComplement, 'version' => 'complement'];
        }
        if ($inDemande && Storage::disk(self::DISK)->exists($inDemande)) {
            return ['path' => $inDemande, 'version' => 'demande'];
        }

        return ['path' => null, 'version' => null];
    }

    /**
     * Supprime un fichier du disque si il existe.
     */
    public function deleteFile(string $path): void
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Décode une valeur JSON/array/null en tableau PHP.
     */
    private function decodeFiles(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            return json_decode($raw, true) ?: [];
        }
        return [];
    }
}
