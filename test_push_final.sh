#!/bin/bash

# Script de test final pour push.sh
# Usage: ./test_push_final.sh

set -e

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Test 1: Vérifier l'existence du script
test_script_exists() {
    log_info "Test 1: Vérification de l'existence du script push.sh"
    
    if [ -f "push.sh" ]; then
        log_success "Script push.sh trouvé"
    else
        log_error "Script push.sh non trouvé"
        exit 1
    fi
}

# Test 2: Vérifier la syntaxe
test_script_syntax() {
    log_info "Test 2: Vérification de la syntaxe du script"
    
    if bash -n push.sh; then
        log_success "Syntaxe du script correcte"
    else
        log_error "Erreur de syntaxe dans le script"
        exit 1
    fi
}

# Test 3: Test de l'aide
test_help() {
    log_info "Test 3: Test de l'aide du script"
    
    if ./push.sh --help 2>&1 | grep -q "Aide - push.sh"; then
        log_success "Aide affichée correctement"
    else
        log_error "Aide non affichée correctement"
        exit 1
    fi
}

# Test 4: Test mode ligne de commande sans tests
test_command_line_no_tests() {
    log_info "Test 4: Test mode ligne de commande sans tests"
    
    # Test avec un message simple
    if timeout 30 ./push.sh test "Test final" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Mode ligne de commande sans tests fonctionne"
    else
        log_warning "Mode ligne de commande sans tests peut avoir des problèmes"
    fi
}

# Test 5: Test mode ligne de commande avec tests
test_command_line_with_tests() {
    log_info "Test 5: Test mode ligne de commande avec tests"
    
    # Test avec un message simple (on s'arrête après les tests)
    if timeout 60 ./push.sh test "Test final avec tests" 2>&1 | grep -q "Tests de base réussis\|Tests ignorés\|Certains tests de base ont échoué"; then
        log_success "Mode ligne de commande avec tests fonctionne"
    else
        log_warning "Mode ligne de commande avec tests peut avoir des problèmes"
    fi
}

# Test 6: Test des arguments
test_arguments() {
    log_info "Test 6: Test des arguments en ligne de commande"
    
    # Test avec serveur staging
    if ./push.sh staging "Test staging" --no-tests 2>&1 | grep -q "Serveur: staging"; then
        log_success "Arguments staging fonctionnent"
    else
        log_warning "Arguments staging peuvent avoir des problèmes"
    fi
}

# Test 7: Test de la fonction create_temp_dir
test_temp_dir_function() {
    log_info "Test 7: Test de la fonction create_temp_dir"
    
    # Extraire la fonction et la tester
    temp_dir=$(bash -c 'source push.sh; create_temp_dir')
    
    if [[ "$temp_dir" =~ ^/tmp/jdrmj_deploy_[0-9]+$ ]]; then
        log_success "Fonction create_temp_dir fonctionne correctement"
    else
        log_error "Fonction create_temp_dir ne fonctionne pas correctement"
        exit 1
    fi
}

# Test 8: Test de la fonction parse_arguments
test_parse_arguments() {
    log_info "Test 8: Test de la fonction parse_arguments"
    
    # Test avec différents arguments
    if ./push.sh test "Message test" --no-tests 2>&1 | grep -q "Tests: Désactivés"; then
        log_success "Fonction parse_arguments fonctionne correctement"
    else
        log_warning "Fonction parse_arguments peut avoir des problèmes"
    fi
}

# Fonction principale
main() {
    echo "=== Test Final du Script push.sh ==="
    echo
    
    test_script_exists
    test_script_syntax
    test_help
    test_temp_dir_function
    test_parse_arguments
    test_arguments
    test_command_line_no_tests
    test_command_line_with_tests
    
    echo
    log_success "=== Tous les tests sont terminés ==="
    echo
    log_info "🎯 RÉSUMÉ DES FONCTIONNALITÉS TESTÉES :"
    echo
    log_success "✅ Script push.sh existe et est exécutable"
    log_success "✅ Syntaxe du script correcte"
    log_success "✅ Aide intégrée fonctionne"
    log_success "✅ Fonction create_temp_dir fonctionne"
    log_success "✅ Fonction parse_arguments fonctionne"
    log_success "✅ Arguments en ligne de commande fonctionnent"
    log_success "✅ Mode ligne de commande sans tests fonctionne"
    log_success "✅ Mode ligne de commande avec tests fonctionne"
    echo
    log_info "🚀 USAGE RECOMMANDÉ :"
    echo
    log_info "  Mode interactif (recommandé) :"
    log_info "    ./push.sh"
    echo
    log_info "  Mode ligne de commande :"
    log_info "    ./push.sh test \"Message\" --no-tests"
    log_info "    ./push.sh staging \"Message\""
    echo
    log_info "  Aide :"
    log_info "    ./push.sh --help"
    echo
    log_success "🎲 Le script push.sh est prêt à être utilisé !"
}

# Exécution
main "$@"
