<?php
// ATTENTION: Supprimer ce fichier après utilisation pour des raisons de sécurité

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('migrate', ['--force' => true]);

echo "Migration status: " . $status . "\n";
echo "Migrations executed successfully!";
