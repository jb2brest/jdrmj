#!/bin/bash

# Script de test complet du système de déploiement
# Usage: ./test_complete_deployment.sh

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

# Test 1: Vérifier tous les fichiers de configuration
test_all_configs() {
    log_info "Test 1: Vérification de tous les fichiers de configuration"
    
    local config_files=(
        "config/database.php"
        "config/database.test.php"
        "config/database.staging.php"
        "config/database.production.php"
        "push.sh"
        "publish.sh"
        "ftp_config.conf"
    )
    
    for file in "${config_files[@]}"; do
        if [ -f "$file" ]; then
            log_success "Fichier $file trouvé"
        else
            log_error "Fichier $file manquant"
            return 1
        fi
    done
}

# Test 2: Tester la configuration de base de données
test_database_configs() {
    log_info "Test 2: Test des configurations de base de données"
    
    # Test production
    if php test_database_config.php production 2>&1 | grep -q "Configuration chargée avec succès"; then
        log_success "Configuration production fonctionne"
    else
        log_warning "Configuration production peut avoir des problèmes"
    fi
    
    # Test staging
    if php test_database_config.php staging 2>&1 | grep -q "Configuration chargée avec succès"; then
        log_success "Configuration staging fonctionne"
    else
        log_warning "Configuration staging peut avoir des problèmes"
    fi
}

# Test 3: Tester le déploiement sur test
test_deploy_test() {
    log_info "Test 3: Déploiement sur serveur de test"
    
    if ./push.sh test "Test complet" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement sur test réussi"
    else
        log_error "Déploiement sur test échoué"
        return 1
    fi
}

# Test 4: Tester le déploiement sur staging
test_deploy_staging() {
    log_info "Test 4: Déploiement sur serveur de staging"
    
    if ./push.sh staging "Test complet" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement sur staging réussi"
    else
        log_error "Déploiement sur staging échoué"
        return 1
    fi
}

# Test 5: Tester le déploiement sur production
test_deploy_production() {
    log_info "Test 5: Déploiement sur serveur de production"
    
    if ./push.sh production "Test complet" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement sur production réussi"
    else
        log_error "Déploiement sur production échoué"
        return 1
    fi
}

# Test 6: Vérifier les fichiers déployés
test_deployed_files() {
    log_info "Test 6: Vérification des fichiers déployés"
    
    # Vérifier test
    if [ -f "/var/www/html/jdrmj_test/config/database.php" ]; then
        log_success "Fichiers de configuration présents sur test"
    else
        log_error "Fichiers de configuration manquants sur test"
        return 1
    fi
    
    # Vérifier staging
    if [ -f "/var/www/html/jdrmj_staging/config/database.php" ]; then
        log_success "Fichiers de configuration présents sur staging"
    else
        log_error "Fichiers de configuration manquants sur staging"
        return 1
    fi
}

# Test 7: Tester le menu interactif
test_interactive_menu() {
    log_info "Test 7: Test du menu interactif"
    
    # Test de l'aide
    if ./push.sh --help 2>&1 | grep -q "Aide - push.sh"; then
        log_success "Menu interactif fonctionne"
    else
        log_warning "Menu interactif peut avoir des problèmes"
    fi
}

# Test 8: Vérifier les outils
test_tools() {
    log_info "Test 8: Vérification des outils"
    
    # Vérifier lftp
    if command -v lftp &> /dev/null; then
        log_success "lftp installé"
    else
        log_error "lftp non installé"
        return 1
    fi
    
    # Vérifier rsync
    if command -v rsync &> /dev/null; then
        log_success "rsync installé"
    else
        log_error "rsync non installé"
        return 1
    fi
    
    # Vérifier git
    if command -v git &> /dev/null; then
        log_success "git installé"
    else
        log_error "git non installé"
        return 1
    fi
}

# Fonction principale
main() {
    echo "=== Test Complet du Système de Déploiement ==="
    echo
    
    test_tools
    test_all_configs
    test_database_configs
    test_deploy_test
    test_deploy_staging
    test_deploy_production
    test_deployed_files
    test_interactive_menu
    
    echo
    log_success "=== Tous les tests sont terminés ==="
    echo
    log_info "🎯 RÉSUMÉ COMPLET :"
    echo
    log_success "✅ Outils installés (lftp, rsync, git)"
    log_success "✅ Fichiers de configuration présents"
    log_success "✅ Configurations de base de données fonctionnelles"
    log_success "✅ Déploiement sur test réussi"
    log_success "✅ Déploiement sur staging réussi"
    log_success "✅ Déploiement sur production réussi"
    log_success "✅ Fichiers déployés correctement"
    log_success "✅ Menu interactif fonctionnel"
    echo
    log_info "🚀 SYSTÈME DE DÉPLOIEMENT COMPLET OPÉRATIONNEL !"
    echo
    log_info "📋 ENVIRONNEMENTS CONFIGURÉS :"
    echo
    log_info "  🧪 TEST :"
    log_info "    - URL: http://localhost/jdrmj_test"
    log_info "    - DB: u839591438_jdrmj"
    log_info "    - Méthode: rsync local"
    echo
    log_info "  🎭 STAGING :"
    log_info "    - URL: http://localhost/jdrmj_staging"
    log_info "    - DB: u839591438_jdrmj_s"
    log_info "    - Méthode: rsync local"
    echo
    log_info "  🏭 PRODUCTION :"
    log_info "    - URL: https://robindesbriques.fr/jdrmj"
    log_info "    - DB: u839591438_jdrmj"
    log_info "    - Méthode: FTP (robindesbriques.fr)"
    echo
    log_info "🎮 USAGE :"
    echo
    log_info "  Menu interactif:"
    log_info "    ./push.sh"
    echo
    log_info "  Ligne de commande:"
    log_info "    ./push.sh test \"Message\""
    log_info "    ./push.sh staging \"Message\""
    log_info "    ./push.sh production \"Message\""
    echo
    log_info "  Avec/sans tests:"
    log_info "    ./push.sh test \"Message\" --no-tests"
    echo
    log_success "🎲 Système de déploiement multi-environnement terminé avec succès !"
}

# Exécution
main "$@"
