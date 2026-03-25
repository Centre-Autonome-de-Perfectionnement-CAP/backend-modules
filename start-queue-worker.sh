#!/bin/bash

# Script pour démarrer le worker de queue pour les emails
# Usage: ./start-queue-worker.sh

echo "🚀 Démarrage du worker de queue pour les emails..."
echo ""
echo "📧 Queue: emails"
echo "🔄 Tentatives: 3"
echo "⏱️  Timeout: 300 secondes"
echo ""
echo "Pour arrêter: Ctrl+C"
echo "----------------------------------------"
echo ""

php artisan queue:work database --queue=emails --tries=3 --timeout=300 --verbose
