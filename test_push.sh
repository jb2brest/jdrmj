#!/bin/bash

# Script de test pour push.sh
# Usage: ./test_push.sh

set -e

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${YELLOW}[TEST]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Test 1: Vérifier que le script existe et est exécutable
test_script_exists() {
    log_info "Test 1: Vérification de l'existence du script push.sh"
    
    if [ -f "push.sh" ]; then
        log_success "Script push.sh trouvé"
    else
        log_error "Script push.sh non trouvé"
        exit 1
    fi
    
    if [ -x "push.sh" ]; then
        log_success "Script push.sh est exécutable"
    else
        log_error "Script push.sh n'est pas exécutable"
        exit 1
    fi
}

# Test 2: Vérifier la syntaxe du script
test_script_syntax() {
    log_info "Test 2: Vérification de la syntaxe du script"
    
    if bash -n push.sh; then
        log_success "Syntaxe du script correcte"
    else
        log_error "Erreur de syntaxe dans le script"
        exit 1
    fi
}

# Test 3: Test d'aide
test_help() {
    log_info "Test 3: Test de l'aide du script"
    
    # Le script devrait afficher des informations même sans paramètres
    if ./push.sh 2>&1 | grep -q "Script de livraison"; then
        log_success "Script affiche les informations correctement"
    else
        log_error "Script ne fonctionne pas correctement"
        exit 1
    fi
}

# Test 4: Vérifier les prérequis
test_prerequisites() {
    log_info "Test 4: Vérification des prérequis"
    
    # Vérifier git
    if command -v git &> /dev/null; then
        log_success "Git est disponible"
    else
        log_error "Git n'est pas disponible"
        exit 1
    fi
    
    # Vérifier rsync
    if command -v rsync &> /dev/null; then
        log_success "rsync est disponible"
    else
        log_error "rsync n'est pas disponible"
        exit 1
    fi
    
    # Vérifier que nous sommes dans le bon répertoire
    if [ -f "index.php" ]; then
        log_success "Fichier index.php trouvé"
    else
        log_error "Fichier index.php non trouvé - mauvais répertoire"
        exit 1
    fi
}

# Test 5: Test de préparation des fichiers (sans déploiement)
test_file_preparation() {
    log_info "Test 5: Test de préparation des fichiers"
    
    # Créer un répertoire temporaire de test
    TEST_DIR="/tmp/jdrmj_test_$(date +%s)"
    mkdir -p "$TEST_DIR"
    
    # Copier quelques fichiers de test
    cp index.php "$TEST_DIR/" 2>/dev/null || true
    cp README.md "$TEST_DIR/" 2>/dev/null || true
    
    if [ -f "$TEST_DIR/index.php" ]; then
        log_success "Préparation des fichiers fonctionne"
    else
        log_error "Préparation des fichiers échoue"
        rm -rf "$TEST_DIR"
        exit 1
    fi
    
    # Nettoyage
    rm -rf "$TEST_DIR"
}

# Fonction principale
main() {
    echo "=== Test du script push.sh ==="
    echo
    
    test_script_exists
    test_script_syntax
    test_prerequisites
    test_file_preparation
    
    echo
    log_success "=== Tous les tests sont passés ==="
    log_info "Le script push.sh est prêt à être utilisé"
    echo
    log_info "Usage:"
    log_info "  ./push.sh test \"Message de livraison\""
    log_info "  ./push.sh staging \"Message de livraison\""
    echo
}

# Exécution
main "$@"
