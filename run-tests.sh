#!/bin/bash

# Script de tests automatisés pour le projet CAP
# Ce script doit être exécuté avant chaque build/déploiement

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables pour le tracking des résultats
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
START_TIME=$(date +%s)

# Fonction pour afficher les messages
print_header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

print_message() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[ℹ]${NC} $1"
}

# Vérifier si on est dans le bon répertoire
check_directory() {
    if [[ ! -d "app-cap" || ! -d "app-cap-frontend" || ! -d "backend-modules" ]]; then
        print_error "Vous devez exécuter ce script depuis le répertoire racine contenant app-cap, app-cap-frontend et backend-modules"
        exit 1
    fi
}

# Tests Backend (PHPUnit)
run_backend_tests() {
    print_header "Tests Backend (PHPUnit)"
    
    cd backend-modules
    
    if [[ ! -f "vendor/bin/phpunit" ]]; then
        print_warning "PHPUnit n'est pas installé. Installation des dépendances..."
        composer install --no-interaction
    fi
    
    print_info "Lancement des tests PHPUnit..."
    
    # Exécuter les tests avec capture du code de retour
    ./vendor/bin/phpunit --testdox --colors=always
    BACKEND_STATUS=$?
    
    if [[ $BACKEND_STATUS -eq 0 ]]; then
        print_message "Tests backend : SUCCÈS"
        ((PASSED_TESTS++))
    else
        print_error "Tests backend : ÉCHEC"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    cd ..
    
    return $BACKEND_STATUS
}

# Tests Frontend app-cap (Vitest)
run_app_cap_tests() {
    print_header "Tests Frontend (app-cap)"
    
    cd app-cap
    
    if [[ ! -d "node_modules" ]]; then
        print_warning "Installation des dépendances pour app-cap..."
        npm install
    fi
    
    # Vérifier si vitest est configuré
    if ! grep -q '"test"' package.json; then
        print_warning "Pas de script de test configuré dans app-cap"
        print_info "Passé pour le moment..."
        cd ..
        return 0
    fi
    
    print_info "Lancement des tests Vitest pour app-cap..."
    
    npm run test -- --run --reporter=verbose
    APP_CAP_STATUS=$?
    
    if [[ $APP_CAP_STATUS -eq 0 ]]; then
        print_message "Tests app-cap : SUCCÈS"
        ((PASSED_TESTS++))
    else
        print_error "Tests app-cap : ÉCHEC"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    cd ..
    
    return $APP_CAP_STATUS
}

# Tests Frontend app-cap-frontend (Vitest)
run_app_cap_frontend_tests() {
    print_header "Tests Frontend (app-cap-frontend)"
    
    cd app-cap-frontend
    
    if [[ ! -d "node_modules" ]]; then
        print_warning "Installation des dépendances pour app-cap-frontend..."
        npm install
    fi
    
    # Vérifier si vitest est configuré
    if ! grep -q '"test"' package.json; then
        print_warning "Pas de script de test configuré dans app-cap-frontend"
        print_info "Passé pour le moment..."
        cd ..
        return 0
    fi
    
    print_info "Lancement des tests Vitest pour app-cap-frontend..."
    
    npm run test -- --run --reporter=verbose
    APP_CAP_FRONTEND_STATUS=$?
    
    if [[ $APP_CAP_FRONTEND_STATUS -eq 0 ]]; then
        print_message "Tests app-cap-frontend : SUCCÈS"
        ((PASSED_TESTS++))
    else
        print_error "Tests app-cap-frontend : ÉCHEC"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    cd ..
    
    return $APP_CAP_FRONTEND_STATUS
}

# Type checking TypeScript
run_type_checking() {
    print_header "Vérification des types TypeScript"
    
    TYPE_CHECK_STATUS=0
    
    # app-cap
    print_info "Vérification des types pour app-cap..."
    cd app-cap
    if grep -q '"type-check"' package.json; then
        npm run type-check > /tmp/type-check-app-cap.log 2>&1
        APP_CAP_CHECK_STATUS=$?
        if [[ $APP_CAP_CHECK_STATUS -ne 0 ]]; then
            cat /tmp/type-check-app-cap.log
            TYPE_CHECK_STATUS=1
        fi
    else
        print_warning "Script type-check non trouvé pour app-cap, utilisation de tsc..."
        npx tsc --noEmit > /tmp/type-check-app-cap.log 2>&1
        APP_CAP_CHECK_STATUS=$?
        if [[ $APP_CAP_CHECK_STATUS -ne 0 ]]; then
            cat /tmp/type-check-app-cap.log
            TYPE_CHECK_STATUS=1
        fi
    fi
    cd ..
    
    # app-cap-frontend
    print_info "Vérification des types pour app-cap-frontend..."
    cd app-cap-frontend
    if grep -q '"type-check"' package.json; then
        npm run type-check > /tmp/type-check-app-cap-frontend.log 2>&1
        FRONTEND_CHECK_STATUS=$?
        if [[ $FRONTEND_CHECK_STATUS -ne 0 ]]; then
            cat /tmp/type-check-app-cap-frontend.log
            TYPE_CHECK_STATUS=1
        fi
    fi
    cd ..
    
    if [[ $TYPE_CHECK_STATUS -eq 0 ]]; then
        print_message "Type checking : SUCCÈS"
        ((PASSED_TESTS++))
    else
        print_error "Type checking : ÉCHEC"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    
    return $TYPE_CHECK_STATUS
}

# Rapport final
print_summary() {
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  RAPPORT DES TESTS${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "  Total de suites: ${BLUE}${TOTAL_TESTS}${NC}"
    echo -e "  Succès:          ${GREEN}${PASSED_TESTS}${NC}"
    echo -e "  Échecs:          ${RED}${FAILED_TESTS}${NC}"
    echo -e "  Durée:           ${BLUE}${DURATION}s${NC}"
    echo ""
    
    if [[ $FAILED_TESTS -eq 0 ]]; then
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${GREEN}  ✓ TOUS LES TESTS SONT PASSÉS !${NC}"
        echo -e "${GREEN}  Vous pouvez procéder au déploiement.${NC}"
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${RED}  ✗ DES TESTS ONT ÉCHOUÉ !${NC}"
        echo -e "${RED}  Veuillez corriger les erreurs avant de déployer.${NC}"
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo ""
        return 1
    fi
}

# Fonction principale
main() {
    print_header "DÉMARRAGE DES TESTS AUTOMATISÉS"
    print_info "Date: $(date '+%Y-%m-%d %H:%M:%S')"
    
    # Vérifications initiales
    check_directory
    
    # Exécuter toutes les suites de tests
    OVERALL_STATUS=0
    
    run_backend_tests
    [[ $? -ne 0 ]] && OVERALL_STATUS=1
    
    run_app_cap_tests
    [[ $? -ne 0 ]] && OVERALL_STATUS=1
    
    run_app_cap_frontend_tests
    [[ $? -ne 0 ]] && OVERALL_STATUS=1
    
    run_type_checking
    [[ $? -ne 0 ]] && OVERALL_STATUS=1
    
    # Afficher le rapport final
    print_summary
    
    exit $OVERALL_STATUS
}

# Exécution du script
main "$@"
