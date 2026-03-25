#!/usr/bin/env php
<?php

/**
 * Script de test rapide pour vérifier la configuration email
 * Usage: php test-email-config.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         Test de Configuration Email - CAP-EPAC            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Afficher la configuration
echo "📧 Configuration Email:\n";
echo "   Mailer: " . Config::get('mail.default') . "\n";
echo "   Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "   Port: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "   Username: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "   Encryption: " . Config::get('mail.mailers.smtp.encryption') . "\n";
echo "   From: " . Config::get('mail.from.address') . " (" . Config::get('mail.from.name') . ")\n";
echo "\n";

// Demander l'email de test
echo "📮 Entrez l'email de test (ou appuyez sur Entrée pour annuler): ";
$testEmail = trim(fgets(STDIN));

if (empty($testEmail)) {
    echo "❌ Test annulé.\n\n";
    exit(0);
}

if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Email invalide.\n\n";
    exit(1);
}

echo "\n🚀 Envoi de l'email de test à: $testEmail\n";
echo "⏳ Veuillez patienter...\n\n";

try {
    Mail::raw('Ceci est un email de test depuis CAP-EPAC. Si vous recevez ce message, votre configuration email fonctionne correctement !', function($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test Email - CAP-EPAC');
    });
    
    echo "✅ Email envoyé avec succès !\n";
    echo "📬 Vérifiez votre boîte de réception (et les spams).\n\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur lors de l'envoi:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "💡 Vérifiez votre configuration dans .env:\n";
    echo "   - MAIL_MAILER\n";
    echo "   - MAIL_HOST\n";
    echo "   - MAIL_PORT\n";
    echo "   - MAIL_USERNAME\n";
    echo "   - MAIL_PASSWORD\n";
    echo "   - MAIL_ENCRYPTION\n\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Test terminé                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
