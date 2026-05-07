<?php

namespace App\Modules\Stockage\Services;

use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Models\FileShare;
use App\Modules\Stockage\Models\FileActivity;
use Illuminate\Support\Facades\DB;

class FileShareService
{
    /**
     * Crée un lien de partage pour un fichier.
     *
     * @param File $file
     * @param int $createdBy
     * @param array $options
     * @return FileShare
     */
    public function createShare(File $file, int $createdBy, array $options = []): FileShare
    {
        return DB::transaction(function () use ($file, $createdBy, $options) {
            $passwordHash = null;
            if (isset($options['password'])) {
                $passwordHash = password_hash($options['password'], PASSWORD_DEFAULT);
            }

            $share = FileShare::create([
                'file_id' => $file->id,
                'password_hash' => $passwordHash,
                'allow_download' => $options['allow_download'] ?? true,
                'allow_preview' => $options['allow_preview'] ?? true,
                'max_downloads' => $options['max_downloads'] ?? null,
                'expires_at' => $options['expires_at'] ?? null,
                'created_by' => $createdBy,
                'is_active' => true,
            ]);

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $createdBy,
                'shared',
                "Lien de partage créé (token: {$share->token})"
            );

            return $share;
        });
    }

    /**
     * Récupère un partage par son token.
     *
     * @param string $token
     * @return FileShare|null
     */
    public function getShareByToken(string $token): ?FileShare
    {
        return FileShare::with('file')->byToken($token)->first();
    }

    /**
     * Valide l'accès à un partage.
     *
     * @param FileShare $share
     * @param string|null $password
     * @return bool
     */
    public function validateShareAccess(FileShare $share, ?string $password = null): bool
    {
        // Vérifier si le partage est valide
        if (!$share->isValid()) {
            return false;
        }

        // Vérifier le mot de passe si nécessaire
        return $share->checkPassword($password);
    }

    /**
     * Accède à un fichier via un lien de partage.
     *
     * @param FileShare $share
     * @param string|null $password
     * @param bool $download
     * @return array
     * @throws \RuntimeException
     */
    public function accessSharedFile(FileShare $share, ?string $password = null, bool $download = false): array
    {
        if (!$this->validateShareAccess($share, $password)) {
            throw new \App\Exceptions\BusinessException(
                message: 'Accès au partage refusé',
                errorCode: 'SHARE_ACCESS_DENIED',
                statusCode: 403
            );
        }

        $file = $share->file;

        // Vérifier les permissions du partage
        if ($download && !$share->allow_download) {
            throw new \App\Exceptions\BusinessException(
                message: 'Le téléchargement n\'est pas autorisé pour ce partage',
                errorCode: 'DOWNLOAD_NOT_ALLOWED',
                statusCode: 403
            );
        }

        // Incrémenter le compteur si c'est un téléchargement
        if ($download) {
            $share->incrementDownloadCount();
        }

        return [
            'file' => $file,
            'share' => $share,
        ];
    }

    /**
     * Désactive un partage.
     *
     * @param FileShare $share
     * @param int $userId
     * @return FileShare
     */
    public function deactivateShare(FileShare $share, int $userId): FileShare
    {
        $share->update(['is_active' => false]);

        FileActivity::log(
            $share->file_id,
            $userId,
            'shared',
            "Lien de partage désactivé (token: {$share->token})"
        );

        return $share->fresh();
    }

    /**
     * Supprime un partage.
     *
     * @param FileShare $share
     * @param int $userId
     * @return bool
     */
    public function deleteShare(FileShare $share, int $userId): bool
    {
        FileActivity::log(
            $share->file_id,
            $userId,
            'shared',
            "Lien de partage supprimé (token: {$share->token})"
        );

        return $share->delete();
    }

    /**
     * Récupère tous les partages d'un fichier.
     *
     * @param File $file
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFileShares(File $file)
    {
        return FileShare::where('file_id', $file->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Nettoie les partages expirés.
     *
     * @return int
     */
    public function cleanExpiredShares(): int
    {
        return FileShare::expired()->delete();
    }
}
