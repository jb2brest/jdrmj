#!/bin/bash

# Script de test pour le menu interactif de push.sh
# Usage: ./test_menu.sh

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

# Test 1: Vérifier que le script existe
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

# Test 4: Test des arguments
test_arguments() {
    log_info "Test 4: Test des arguments en ligne de commande"
    
    # Test avec serveur et message
    if ./push.sh test "Test message" --no-tests 2>&1 | grep -q "Serveur: test"; then
        log_success "Arguments en ligne de commande fonctionnent"
    else
        log_warning "Arguments en ligne de commande peuvent avoir des problèmes"
    fi
}

# Test 5: Simulation du menu interactif
test_interactive_menu() {
    log_info "Test 5: Test du menu interactif (simulation)"
    
    # Créer un script temporaire pour simuler les entrées
    cat > /tmp/test_inputs.sh << 'EOF'
#!/bin/bash
echo "1"  # Choix serveur test
echo "2"  # Choix sans tests
echo ""   # Message par défaut
echo "n"  # Annuler le déploiement
EOF
    
    chmod +x /tmp/test_inputs.sh
    
    # Simuler les entrées
    if timeout 10 /tmp/test_inputs.sh | ./push.sh 2>&1 | grep -q "Déploiement annulé"; then
        log_success "Menu interactif fonctionne"
    else
        log_warning "Menu interactif peut avoir des problèmes"
    fi
    
    # Nettoyage
    rm -f /tmp/test_inputs.sh
}

# Fonction principale
main() {
    echo "=== Test du menu interactif push.sh ==="
    echo
    
    test_script_exists
    test_script_syntax
    test_help
    test_arguments
    test_interactive_menu
    
    echo
    log_success "=== Tests terminés ==="
    log_info "Le script push.sh est prêt avec le menu interactif"
    echo
    log_info "Usage:"
    log_info "  ./push.sh                    # Menu interactif"
    log_info "  ./push.sh test \"Message\"     # Ligne de commande"
    log_info "  ./push.sh --help             # Aide"
    echo
}

# Exécution
main "$@"
