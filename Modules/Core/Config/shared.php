<?php

return [
    'storage' => [
        'public_path' => storage_path('app/shared/public'),
        'private_path' => storage_path('app/shared/private'),
    ],
    'assets' => [
        'path' => public_path('assets/core'),
    ],
    'views' => [
        'emails' => [
            'layout' => 'core::emails.layout',
            'welcome' => 'core::emails.welcome',
        ]
    ]
];
