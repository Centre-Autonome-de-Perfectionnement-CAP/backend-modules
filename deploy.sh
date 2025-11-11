#!/bin/bash

# Script d'automatisation pour le déploiement GitHub
# Nom : deploy_to_github.sh

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier si on est dans le bon répertoire
check_directory() {
    if [[ ! -d "app-cap" || ! -d "app-cap-frontend" || ! -d "backend-modules" ]]; then
        print_error "Vous devez exécuter ce script depuis le répertoire racine contenant app-cap, app-cap-frontend et backend-modules"
        exit 1
    fi
}

# Builder app-cap
build_app_cap() {
    print_message "Construction de app-cap..."
    cd app-cap
    
    # Vérifier si node_modules existe
    if [[ ! -d "node_modules" ]]; then
        print_warning "Installation des dépendances pour app-cap..."
        npm install
    fi
    
    # Builder l'application
    npm run build
    
    if [[ $? -ne 0 ]]; then
        print_error "Erreur lors du build de app-cap"
        exit 1
    fi
    
    cd ..
    print_message "app-cap construit avec succès"
}

# Builder app-cap-frontend
build_app_cap_frontend() {
    print_message "Construction de app-cap-frontend..."
    cd app-cap-frontend
    
    # Vérifier si node_modules existe
    if [[ ! -d "node_modules" ]]; then
        print_warning "Installation des dépendances pour app-cap-frontend..."
        npm install
    fi
    
    # Builder l'application
    npm run build
    
    if [[ $? -ne 0 ]]; then
        print_error "Erreur lors du build de app-cap-frontend"
        exit 1
    fi
    
    cd ..
    print_message "app-cap-frontend construit avec succès"
}

# Déployer les builds dans le backend
deploy_to_backend() {
    print_message "Déploiement des builds dans le backend..."
    
    # Créer les dossiers de destination s'ils n'existent pas
    mkdir -p backend-modules/public/app-cap
    mkdir -p backend-modules/public/app-cap-frontend
    
    # Nettoyer les dossiers existants
    rm -rf backend-modules/public/app-cap/*
    rm -rf backend-modules/public/app-cap-frontend/*
    
    # Copier les builds avec vérification
    if [[ -d "app-cap/dist" && "$(ls -A app-cap/dist)" ]]; then
        cp -r app-cap/dist/* backend-modules/public/app-cap/
        print_message "Build app-cap copié"
    else
        print_warning "Aucun build trouvé pour app-cap"
    fi
    
    if [[ -d "app-cap-frontend/build" && "$(ls -A app-cap-frontend/build)" ]]; then
        cp -r app-cap-frontend/build/* backend-modules/public/app-cap-frontend/
        print_message "Build app-cap-frontend copié"
    else
        print_warning "Aucun build trouvé pour app-cap-frontend"
    fi
    
    print_message "Builds déployés dans le backend"
}

# Mettre à jour le fichier de routage Laravel
update_routing() {
    print_message "Mise à jour du fichier de routage Laravel..."
    
    cat > backend-modules/routes/web.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

// Route pour l'application principale app-cap
Route::get('/', function () {
    return file_get_contents(public_path('app-cap/index.html'));
});

// Route pour app-cap-frontend - exclure les fichiers statiques
Route::get('/frontend/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*$');

// Route pour l'API Laravel (si vous en avez)
// Route::get('/api/endpoint', [Controller::class, 'method']);

// Route catch-all pour app-cap (doit être en dernier) - exclure les fichiers statiques
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*$');
EOF

    print_message "Fichier de routage mis à jour"
}

# Vérifier et ajouter les changements Git
check_and_add_git_changes() {
    print_message "Vérification et ajout des changements Git..."
    
    # Se déplacer dans backend-modules pour les opérations Git
    cd backend-modules
    
    # Vérifier si c'est un dépôt Git
    if [[ ! -d ".git" ]]; then
        print_warning "Initialisation du dépôt Git..."
        git init
    fi
    
    # Vérifier les modifications
    if git diff-index --quiet HEAD --; then
        print_warning "Aucun changement détecté"
        cd ..
        return 1
    else
        print_message "Changements détectés, ajout des fichiers..."
        git add .
        
        # Vérifier si l'ajout a fonctionné
        if git diff-index --quiet HEAD --; then
            print_warning "Aucun changement après git add"
            cd ..
            return 1
        else
            print_message "Changements ajoutés avec succès :"
            git status --short
            cd ..
            return 0
        fi
    fi
}

# Commit et push vers GitHub
push_to_github() {
    print_message "Préparation du commit Git..."
    
    # Se déplacer dans backend-modules pour les opérations Git
    cd backend-modules
    
    # Vérifier s'il y a des changements à commiter
    if git diff-index --quiet HEAD --; then
        print_warning "Aucun changement à commiter"
        cd ..
        return 0
    fi
    
    # Vérifier si la remote existe
    if ! git remote get-url origin > /dev/null 2>&1; then
        print_warning "Aucune remote 'origin' configurée"
        echo -n "Voulez-vous ajouter une remote GitHub ? (y/n): "
        read add_remote
        if [[ $add_remote == "y" || $add_remote == "Y" ]]; then
            echo -n "Entrez l'URL du dépôt GitHub : "
            read github_url
            git remote add origin $github_url
        else
            print_message "Commit local effectué sans remote GitHub"
            cd ..
            return 0
        fi
    fi
    
    # Vérifier si la branche main existe
    if ! git show-ref --verify --quiet refs/heads/main; then
        print_warning "La branche 'main' n'existe pas encore"
        print_message "Création de la branche 'main'..."
        
        # Créer la branche main depuis la branche actuelle
        git branch main
        
        if [[ $? -ne 0 ]]; then
            print_error "Erreur lors de la création de la branche main"
            cd ..
            return 1
        fi
        
        print_message "Branche 'main' créée avec succès"
    fi
    
    # Basculer sur la branche main
    print_message "Basculement sur la branche 'main'..."
    git checkout main
    
    if [[ $? -ne 0 ]]; then
        print_error "Erreur lors du basculement vers la branche main"
        cd ..
        return 1
    fi
    
    # Si on vient de créer main, merger les changements de l'ancienne branche
    if [[ -n "$(git branch --list | grep -v 'main')" ]]; then
        previous_branch=$(git branch --list | grep -v 'main' | head -1 | xargs)
        if [[ -n "$previous_branch" && "$previous_branch" != "main" ]]; then
            print_message "Fusion des changements depuis $previous_branch..."
            git merge $previous_branch --no-edit
        fi
    fi
    
    # ======================================
    # SYNCHRONISATION AVEC LE DÉPÔT DISTANT
    # ======================================
    print_message "Synchronisation avec la branche distante..."
    
    # Fetch pour récupérer les dernières modifications
    print_message "Récupération des modifications distantes (fetch)..."
    git fetch origin main 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        # Vérifier si la branche distante existe
        if git rev-parse origin/main >/dev/null 2>&1; then
            # Vérifier si la branche locale est derrière la branche distante
            LOCAL=$(git rev-parse main)
            REMOTE=$(git rev-parse origin/main)
            BASE=$(git merge-base main origin/main)
            
            if [[ "$LOCAL" == "$REMOTE" ]]; then
                print_message "La branche locale est à jour avec origin/main"
            elif [[ "$LOCAL" == "$BASE" ]]; then
                print_message "La branche locale est derrière origin/main"
                print_message "Rebase en cours..."
                
                git rebase origin/main
                REBASE_STATUS=$?
                
                if [[ $REBASE_STATUS -ne 0 ]]; then
                    print_error "Conflit(s) détecté(s) lors du rebase !"
                    echo ""
                    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
                    echo -e "${RED}  CONFLITS À RÉSOUDRE${NC}"
                    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
                    echo ""
                    echo "Fichiers en conflit :"
                    git diff --name-only --diff-filter=U
                    echo ""
                    echo "Actions possibles :"
                    echo "  1. Résoudre les conflits manuellement"
                    echo "  2. Puis exécuter : git rebase --continue"
                    echo "  3. Ou annuler : git rebase --abort"
                    echo ""
                    print_error "Rebase échoué. Veuillez résoudre les conflits manuellement."
                    cd ..
                    exit 1
                fi
                
                print_message "Rebase effectué avec succès !"
            elif [[ "$REMOTE" == "$BASE" ]]; then
                print_message "La branche locale est en avance sur origin/main"
            else
                print_warning "Les branches locale et distante ont divergé"
                print_message "Rebase en cours pour synchroniser..."
                
                git rebase origin/main
                REBASE_STATUS=$?
                
                if [[ $REBASE_STATUS -ne 0 ]]; then
                    print_error "Conflit(s) détecté(s) lors du rebase !"
                    echo ""
                    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
                    echo -e "${RED}  CONFLITS À RÉSOUDRE${NC}"
                    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
                    echo ""
                    echo "Fichiers en conflit :"
                    git diff --name-only --diff-filter=U
                    echo ""
                    echo "Actions possibles :"
                    echo "  1. Résoudre les conflits manuellement"
                    echo "  2. Puis exécuter : git rebase --continue"
                    echo "  3. Ou annuler : git rebase --abort"
                    echo ""
                    print_error "Rebase échoué. Veuillez résoudre les conflits manuellement."
                    cd ..
                    exit 1
                fi
                
                print_message "Rebase effectué avec succès !"
            fi
        else
            print_warning "La branche origin/main n'existe pas encore (premier push)"
        fi
    else
        print_warning "Impossible de fetch depuis origin (peut-être premier déploiement)"
    fi
    
    # Générer un message de commit selon Conventional Commits
    commit_type=""
    commit_scope=""
    commit_description=""
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "  Création du commit (Conventional Commits)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Types disponibles :"
    echo "  1) feat     - Nouvelle fonctionnalité"
    echo "  2) fix      - Correction de bug"
    echo "  3) docs     - Documentation"
    echo "  4) style    - Formatage, point-virgules manquants, etc."
    echo "  5) refactor - Refactoring du code"
    echo "  6) perf     - Amélioration des performances"
    echo "  7) test     - Ajout de tests"
    echo "  8) build    - Changements du système de build"
    echo "  9) ci       - Changements CI/CD"
    echo " 10) chore    - Tâches de maintenance"
    echo ""
    echo -n "Choisissez le type (1-10) [défaut: 10]: "
    read type_choice
    
    case $type_choice in
        1) commit_type="feat" ;;
        2) commit_type="fix" ;;
        3) commit_type="docs" ;;
        4) commit_type="style" ;;
        5) commit_type="refactor" ;;
        6) commit_type="perf" ;;
        7) commit_type="test" ;;
        8) commit_type="build" ;;
        9) commit_type="ci" ;;
        *) commit_type="chore" ;;
    esac
    
    echo -n "Portée/Scope (optionnel, ex: auth, api): "
    read commit_scope
    
    echo -n "Description courte (impératif, ex: add user authentication): "
    read commit_description
    
    if [[ -z "$commit_description" ]]; then
        commit_description="update production build"
    fi
    
    # Demander un corps de message détaillé (optionnel)
    echo ""
    echo "Corps du message (optionnel, appuyez sur Entrée pour passer):"
    echo "Décrivez les changements en détail (une ligne à la fois, ligne vide pour terminer):"
    commit_body=""
    while IFS= read -r line; do
        [[ -z "$line" ]] && break
        commit_body="${commit_body}${line}\n"
    done
    
    # Construire le message de commit
    if [[ -n "$commit_scope" ]]; then
        commit_message="${commit_type}(${commit_scope}): ${commit_description}"
    else
        commit_message="${commit_type}: ${commit_description}"
    fi
    
    if [[ -n "$commit_body" ]]; then
        commit_message="${commit_message}\n\n${commit_body}"
    fi
    
    print_message "Message de commit : ${commit_message}"
    
    # Faire le commit
    echo -e "$commit_message" | git commit -F -
    
    if [[ $? -ne 0 ]]; then
        print_error "Erreur lors du commit"
        cd ..
        return 1
    fi
    
    # Push vers GitHub sur la branche main
    print_message "Push vers GitHub sur la branche main..."
    git push -u origin main
    
    if [[ $? -ne 0 ]]; then
        print_warning "Erreur lors du push vers GitHub"
        
        # Vérifier si c'est juste une question de branche upstream
        if git status | grep -q "have no upstream branch"; then
            print_message "Configuration de la branche upstream..."
            git push --set-upstream origin main
            
            if [[ $? -ne 0 ]]; then
                print_error "Échec de la configuration upstream"
                cd ..
                return 1
            fi
        else
            # Demander confirmation avant un force push
            echo ""
            print_warning "Le push a échoué. Cela peut être dû à :"
            echo "  - Des commits qui doivent être forcés (première fois)"
            echo "  - Une divergence non résolue"
            echo ""
            echo -n "Voulez-vous forcer le push (git push --force) ? (y/n): "
            read force_push
            
            if [[ $force_push == "y" || $force_push == "Y" ]]; then
                print_message "Force push en cours..."
                git push -u origin main --force
                
                if [[ $? -ne 0 ]]; then
                    print_error "Échec du push même avec --force"
                    cd ..
                    return 1
                fi
            else
                print_error "Push annulé par l'utilisateur"
                cd ..
                return 1
            fi
        fi
    fi
    
    # Revenir au répertoire racine
    cd ..
    
    print_message "Déploiement terminé avec succès !"
}

# Fonction principale
main() {
    print_message "Début du processus de déploiement..."
    
    # Vérifications initiales
    check_directory
    
    # ======================================
    # ÉTAPE 1: EXÉCUTION DES TESTS
    # ======================================
    print_message "Exécution des tests automatisés..."
    if [[ -f "./run-tests.sh" ]]; then
        bash ./run-tests.sh
        
        if [[ $? -ne 0 ]]; then
            print_error "Les tests ont échoué. Déploiement annulé."
            print_warning "Veuillez corriger les erreurs avant de déployer."
            exit 1
        fi
        
        print_message "Tous les tests sont passés avec succès !"
    else
        print_warning "Script de tests non trouvé (run-tests.sh). Les tests sont ignorés."
        echo -n "Voulez-vous continuer sans tests ? (y/n): "
        read continue_without_tests
        if [[ $continue_without_tests != "y" && $continue_without_tests != "Y" ]]; then
            print_error "Déploiement annulé."
            exit 1
        fi
    fi
    
    # ======================================
    # ÉTAPE 2: CONSTRUCTION DES APPLICATIONS
    # ======================================
    # Construction des applications React
    build_app_cap
    build_app_cap_frontend
    
    # Déploiement dans le backend
    deploy_to_backend
    
    # Mise à jour du routage
    update_routing
    
    # Gestion Git - ajout automatique des changements
    if check_and_add_git_changes; then
        echo -n "Voulez-vous commit et push vers GitHub ? (y/n): "
        read push_choice
        if [[ $push_choice == "y" || $push_choice == "Y" ]]; then
            push_to_github
        else
            print_message "Changements ajoutés mais non commités"
        fi
    else
        print_message "Aucun changement à commiter"
    fi
    
    print_message "Processus terminé !"
}

# Exécution du script
main "$@"