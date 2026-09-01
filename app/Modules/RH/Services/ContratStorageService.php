<?php

namespace App\Modules\RH\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service centralisé de stockage documentaire — Contrats CAP-EPAC
 *
 * Structure normalisée (disk : public) :
 *
 *   contrats/{contrat_number}/
 *   ├── pdf/
 *   │   └── contrat.pdf          ← nom stable, pas de timestamp
 *   ├── signature/
 *   │   └── signature.png
 *   ├── supports/{cep_id}/
 *   │   └── {uuid}-{titre-slug}.pdf
 *   └── factures/
 *       ├── facture.{ext}
 *       └── rib.{ext}
 *
 * Règles :
 *  - Les noms de fichiers sont sémantiques et stables (pas de timestamp).
 *  - Les anciens chemins (contrats/contrat_{id}_{time}.pdf, signatures/sig_…,
 *    supports/…, factures_normalisees/…) ne sont JAMAIS modifiés ni déplacés.
 *    Ce service ne sert qu'aux NOUVEAUX fichiers.
 *  - Toutes les opérations utilisent le disk "public".
 *  - removeWhiteBackground() est centralisé ici (était absent du controller
 *    → fatal error si appelé).
 */
class ContratStorageService
{
    private const DISK = 'public';

    // ════════════════════════════════════════════════════════════════════════════
    // CHEMINS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Sanitise le numéro de contrat pour l'utiliser comme nom de dossier.
     *   "00042" → "00042"
     *   "00042/UAC" → "00042_UAC"
     */
    private function sanitizeNumber(string $contratNumber): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $contratNumber);
    }

    /** Dossier racine d'un contrat : contrats/{contrat_number} */
    public function basePath(string $contratNumber): string
    {
        return 'contrats/' . $this->sanitizeNumber($contratNumber);
    }

    /** Dossier PDF : contrats/{contrat_number}/pdf */
    public function pdfPath(string $contratNumber): string
    {
        return $this->basePath($contratNumber) . '/pdf';
    }

    /** Chemin complet du PDF contrat (nom stable) */
    public function pdfFile(string $contratNumber): string
    {
        return $this->pdfPath($contratNumber) . '/contrat.pdf';
    }

    /** Dossier signature : contrats/{contrat_number}/signature */
    public function signaturePath(string $contratNumber): string
    {
        return $this->basePath($contratNumber) . '/signature';
    }

    /** Chemin complet de la signature (nom stable) */
    public function signatureFile(string $contratNumber): string
    {
        return $this->signaturePath($contratNumber) . '/signature.png';
    }

    /** Dossier supports d'un programme : contrats/{contrat_number}/supports/{cep_id} */
    public function supportsPath(string $contratNumber, int $cepId): string
    {
        return $this->basePath($contratNumber) . '/supports/' . $cepId;
    }

    /** Dossier factures : contrats/{contrat_number}/factures */
    public function facturesPath(string $contratNumber): string
    {
        return $this->basePath($contratNumber) . '/factures';
    }

    // ════════════════════════════════════════════════════════════════════════════
    // CRÉATION DES DOSSIERS
    // ════════════════════════════════════════════════════════════════════════════

    private function ensureFolder(string $path): void
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->makeDirectory($path);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════
    // STOCKAGE — PDF CONTRAT
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Stocke le contenu binaire d'un PDF généré (DomPDF / Snappy).
     * Supprime l'ancien PDF s'il existe au même emplacement.
     * Retourne le chemin relatif stocké.
     */
    public function storePdfContent(string $contratNumber, string $pdfContent): string
    {
        $path = $this->pdfFile($contratNumber);
        $this->ensureFolder($this->pdfPath($contratNumber));
        Storage::disk(self::DISK)->put($path, $pdfContent);
        return $path;
    }

    /**
     * Stocke un fichier PDF uploadé par l'admin.
     * Supprime l'ancien PDF s'il existe.
     * Retourne le chemin relatif stocké.
     */
    public function storePdfUpload(string $contratNumber, UploadedFile $file): string
    {
        $path = $this->pdfFile($contratNumber);
        $this->ensureFolder($this->pdfPath($contratNumber));

        // Supprimer l'ancienne version si elle existe
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));
        return $path;
    }

    // ════════════════════════════════════════════════════════════════════════════
    // STOCKAGE — SIGNATURE
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Stocke une signature dessinée (base64 PNG) ou uploadée.
     * Supprime l'ancienne signature du disque si elle existe.
     * Retourne le chemin relatif stocké.
     */
    public function storeSignature(
        string  $contratNumber,
        string  $imageData,
        ?string $oldPath = null
    ): string {
        // Supprimer l'ancienne si elle existe
        if ($oldPath && Storage::disk(self::DISK)->exists($oldPath)) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        $path = $this->signatureFile($contratNumber);
        $this->ensureFolder($this->signaturePath($contratNumber));
        Storage::disk(self::DISK)->put($path, $imageData);
        return $path;
    }

    // ════════════════════════════════════════════════════════════════════════════
    // STOCKAGE — SUPPORTS DE COURS
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Stocke un support de cours PDF pour un programme donné.
     * Retourne le chemin relatif stocké.
     */
    public function storeSupportFile(
        string       $contratNumber,
        int          $cepId,
        UploadedFile $file,
        string       $title
    ): string {
        $folder = $this->supportsPath($contratNumber, $cepId);
        $this->ensureFolder($folder);

        $slug     = Str::slug($title) ?: Str::uuid();
        $ext      = $file->getClientOriginalExtension() ?: 'pdf';
        $basename = Str::uuid() . '-' . $slug . '.' . $ext;
        $path     = $folder . '/' . $basename;

        $file->storeAs($folder, $basename, self::DISK);
        return $path;
    }

    // ════════════════════════════════════════════════════════════════════════════
    // STOCKAGE — FACTURES NORMALISÉES
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Stocke une facture normalisée ou un RIB.
     *
     * @param  string       $type  'facture' | 'rib' | 'autre'
     * @return string              chemin relatif stocké
     */
    public function storeFacture(
        string       $contratNumber,
        UploadedFile $file,
        string       $type = 'facture'
    ): string {
        $folder = $this->facturesPath($contratNumber);
        $this->ensureFolder($folder);

        $ext      = $file->getClientOriginalExtension() ?: $file->extension();
        // Nom sémantique stable : facture.pdf / rib.pdf / autre-{uuid}.pdf
        $basename = match ($type) {
            'facture' => 'facture.' . $ext,
            'rib'     => 'rib.' . $ext,
            default   => 'autre-' . Str::uuid() . '.' . $ext,
        };

        $path = $folder . '/' . $basename;

        // Supprimer l'ancienne version du même type si elle existe
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        $file->storeAs($folder, $basename, self::DISK);
        return $path;
    }

    // ════════════════════════════════════════════════════════════════════════════
    // SUPPRESSION
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Supprime un fichier du disk public si il existe.
     * Ne lève jamais d'exception.
     */
    public function delete(string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Supprime tout le dossier d'un contrat (utilisé si contrat supprimé).
     * Ne lève jamais d'exception.
     */
    public function deleteContratFolder(string $contratNumber): void
    {
        $path = $this->basePath($contratNumber);
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->deleteDirectory($path);
        }
    }

    // ════════════════════════════════════════════════════════════════════════════
    // URL PUBLIQUE
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Retourne l'URL publique d'un chemin (null si le fichier n'existe pas).
     */
    public function url(string $path): ?string
    {
        if (!$path || !Storage::disk(self::DISK)->exists($path)) {
            return null;
        }
        return Storage::disk(self::DISK)->url($path);
    }

    // ════════════════════════════════════════════════════════════════════════════
    // TRAITEMENT IMAGE — SUPPRESSION FOND BLANC
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Supprime le fond blanc/clair d'une image PNG (signature).
     *
     * CORRECTIF : cette méthode était appelée dans processAndStoreSignature()
     * du ContratController mais n'y était pas définie → fatal error à l'exécution.
     * Elle est maintenant ici, dans le service, et injectée via DI.
     *
     * @param  string       $imageData  Contenu binaire de l'image
     * @param  string|null  $mimeType   MIME type (pour détecter JPEG vs PNG)
     * @return string                   Contenu binaire PNG avec transparence
     */
    public function removeWhiteBackground(string $imageData, ?string $mimeType = null): string
    {
        // Si GD n'est pas disponible, retourner l'image telle quelle
        if (!extension_loaded('gd')) {
            return $imageData;
        }

        try {
            $image = @imagecreatefromstring($imageData);
            if (!$image) {
                return $imageData;
            }

            $width  = imagesx($image);
            $height = imagesy($image);

            // Créer une image RGBA (avec canal alpha)
            $output = imagecreatetruecolor($width, $height);
            if (!$output) {
                imagedestroy($image);
                return $imageData;
            }

            imagealphablending($output, false);
            imagesavealpha($output, true);

            // Fond transparent
            $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
            imagefill($output, 0, 0, $transparent);

            imagealphablending($output, true);

            // Seuil de "blancheur" : pixels clairs (> 240 sur R, G, B) → transparents
            $threshold = 240;

            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgba = imagecolorat($image, $x, $y);
                    $r    = ($rgba >> 16) & 0xFF;
                    $g    = ($rgba >> 8)  & 0xFF;
                    $b    =  $rgba        & 0xFF;

                    if ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
                        // Pixel clair → transparent
                        imagesetpixel($output, $x, $y, $transparent);
                    } else {
                        $color = imagecolorallocate($output, $r, $g, $b);
                        imagesetpixel($output, $x, $y, $color);
                    }
                }
            }

            // Capturer en PNG
            ob_start();
            imagepng($output);
            $result = ob_get_clean();

            imagedestroy($image);
            imagedestroy($output);

            return $result ?: $imageData;

        } catch (\Throwable) {
            return $imageData;
        }
    }
}
