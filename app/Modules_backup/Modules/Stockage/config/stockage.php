<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration du Module Stockage
    |--------------------------------------------------------------------------
    |
    | Configuration pour la gestion des fichiers, permissions et partages
    |
    */

    // Taille maximale des fichiers (en KB)
    'max_file_size' => env('STOCKAGE_MAX_FILE_SIZE', 51200), // 50MB par défaut

    // Types MIME autorisés (null = tous autorisés)
    'allowed_mime_types' => env('STOCKAGE_ALLOWED_MIME_TYPES', null),

    // Disk par défaut pour les fichiers privés
    'default_private_disk' => env('STOCKAGE_PRIVATE_DISK', 'private_files'),

    // Disk par défaut pour les fichiers publics
    'default_public_disk' => env('STOCKAGE_PUBLIC_DISK', 'public_files'),

    // Durée de conservation des fichiers supprimés (jours)
    'soft_delete_retention_days' => env('STOCKAGE_RETENTION_DAYS', 30),

    // Permissions par défaut
    'default_permissions' => [
        'read',
        'write',
        'delete',
        'share',
        'admin',
    ],

    // Durée d'expiration par défaut des partages (jours, null = pas d'expiration)
    'default_share_expiration_days' => env('STOCKAGE_SHARE_EXPIRATION_DAYS', null),

    // Nombre maximum de téléchargements par défaut pour les partages
    'default_max_downloads' => env('STOCKAGE_MAX_DOWNLOADS', null),

    // Activer le logging des activités
    'enable_activity_logging' => env('STOCKAGE_ENABLE_LOGGING', true),

    // Durée de conservation des logs d'activité (jours)
    'activity_log_retention_days' => env('STOCKAGE_LOG_RETENTION_DAYS', 90),

    // Collections par défaut
    'default_collections' => [
        'default',
        'documents',
        'images',
        'videos',
        'archives',
    ],

    // Nettoyage automatique
    'auto_cleanup' => [
        'enabled' => env('STOCKAGE_AUTO_CLEANUP', true),
        'clean_expired_permissions' => true,
        'clean_expired_shares' => true,
        'clean_old_activities' => true,
    ],

];
