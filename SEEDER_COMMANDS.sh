#!/bin/bash

# ========================================
# 🎓 Commandes Seeders - Années Académiques
# ========================================

echo "📚 SEEDERS DISPONIBLES"
echo "═════════════════════════════════════════"
echo ""

# Fonction pour afficher un menu
show_menu() {
    echo "1) Seeder Complet (AcademicYearCompleteSeeder)"
    echo "2) Seeder Rapide (QuickAcademicYearSeeder)"
    echo "3) Tous les seeders (DatabaseSeeder)"
    echo "4) Réinitialiser DB + Tous les seeders"
    echo "5) Vérifier les données (Tinker)"
    echo "6) Tester les APIs"
    echo "0) Quitter"
    echo ""
}

# Fonction pour le seeder complet
run_complete_seeder() {
    echo ""
    echo "⚙️  Lancement du seeder complet..."
    php artisan db:seed --class=AcademicYearCompleteSeeder
    echo "✅ Terminé !"
    echo ""
}

# Fonction pour le seeder rapide
run_quick_seeder() {
    echo ""
    echo "⚡ Lancement du seeder rapide..."
    php artisan db:seed --class=QuickAcademicYearSeeder
    echo "✅ Terminé !"
    echo ""
}

# Fonction pour tous les seeders
run_all_seeders() {
    echo ""
    echo "🔄 Lancement de tous les seeders..."
    php artisan db:seed
    echo "✅ Terminé !"
    echo ""
}

# Fonction pour réinitialiser
reset_and_seed() {
    echo ""
    echo "⚠️  ATTENTION : Cette action va supprimer toutes les données !"
    read -p "Continuer ? (y/N) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "🔄 Réinitialisation de la base de données..."
        php artisan migrate:fresh --seed
        echo "✅ Terminé !"
    else
        echo "❌ Annulé"
    fi
    echo ""
}

# Fonction pour vérifier les données
check_data() {
    echo ""
    echo "🔍 Vérification des données..."
    echo ""
    php artisan tinker <<EOF
// Années académiques
\$years = \App\Modules\Inscription\Models\AcademicYear::count();
echo "Années académiques : \$years\n";

// Périodes de soumission
\$submissions = \App\Modules\Inscription\Models\SubmissionPeriod::count();
echo "Périodes de soumission : \$submissions\n";

// Périodes de réclamation
\$reclamations = \App\Modules\Inscription\Models\ReclamationPeriod::count();
echo "Périodes de réclamation : \$reclamations\n";

echo "\n";
exit
EOF
    echo ""
}

# Fonction pour tester les APIs
test_apis() {
    echo ""
    echo "🧪 Test des APIs..."
    echo ""
    
    echo "📡 GET /api/academic-years"
    curl -s http://127.0.0.1:8000/api/academic-years | jq . || echo "⚠️  Installez jq pour un meilleur affichage (ou utilisez un navigateur)"
    
    echo ""
    echo "📡 GET /api/filieres"
    curl -s http://127.0.0.1:8000/api/filieres | jq . || echo "⚠️  Installez jq pour un meilleur affichage"
    
    echo ""
}

# Boucle principale
while true; do
    show_menu
    read -p "Choisissez une option : " choice
    
    case $choice in
        1) run_complete_seeder ;;
        2) run_quick_seeder ;;
        3) run_all_seeders ;;
        4) reset_and_seed ;;
        5) check_data ;;
        6) test_apis ;;
        0) echo "👋 Au revoir !"; exit 0 ;;
        *) echo "❌ Option invalide" ;;
    esac
done
