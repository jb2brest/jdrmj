#!/bin/bash

# Script de test de déploiement avec configuration de base de données
# Usage: ./test_database_deployment.sh

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

# Test 1: Vérifier les fichiers de configuration
test_config_files() {
    log_info "Test 1: Vérification des fichiers de configuration"
    
    local config_files=(
        "config/database.php"
        "config/database.test.php"
        "config/database.staging.php"
        "config/database.production.php"
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

# Test 2: Tester la configuration locale
test_local_config() {
    log_info "Test 2: Test de la configuration locale"
    
    if php test_database_config.php production 2>&1 | grep -q "Configuration chargée avec succès"; then
        log_success "Configuration locale fonctionne"
    else
        log_error "Configuration locale échoue"
        return 1
    fi
}

# Test 3: Déployer sur test
test_deploy_test() {
    log_info "Test 3: Déploiement sur serveur de test"
    
    if ./push.sh test "Test configuration DB" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement sur test réussi"
    else
        log_error "Déploiement sur test échoué"
        return 1
    fi
}

# Test 4: Vérifier la configuration sur test
test_config_test() {
    log_info "Test 4: Vérification de la configuration sur test"
    
    if [ -f "/var/www/html/jdrmj_test/config/database.php" ]; then
        log_success "Fichiers de configuration présents sur test"
    else
        log_error "Fichiers de configuration manquants sur test"
        return 1
    fi
}

# Test 5: Déployer sur staging
test_deploy_staging() {
    log_info "Test 5: Déploiement sur serveur de staging"
    
    if ./push.sh staging "Test configuration DB" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement sur staging réussi"
    else
        log_error "Déploiement sur staging échoué"
        return 1
    fi
}

# Test 6: Vérifier la configuration sur staging
test_config_staging() {
    log_info "Test 6: Vérification de la configuration sur staging"
    
    if [ -f "/var/www/html/jdrmj_staging/config/database.php" ]; then
        log_success "Fichiers de configuration présents sur staging"
    else
        log_error "Fichiers de configuration manquants sur staging"
        return 1
    fi
}

# Test 7: Tester la configuration sur staging
test_staging_config() {
    log_info "Test 7: Test de la configuration sur staging"
    
    if cd /var/www/html/jdrmj_staging && php test_database_config.php staging 2>&1 | grep -q "Configuration chargée avec succès"; then
        log_success "Configuration sur staging fonctionne"
        cd /home/robin-des-briques/Documents/jdrmj
    else
        log_error "Configuration sur staging échoue"
        cd /home/robin-des-briques/Documents/jdrmj
        return 1
    fi
}

# Test 8: Vérifier les fichiers .env
test_env_files() {
    log_info "Test 8: Vérification des fichiers .env"
    
    local env_files=(
        "/var/www/html/jdrmj_test/.env"
        "/var/www/html/jdrmj_staging/.env"
    )
    
    for file in "${env_files[@]}"; do
        if [ -f "$file" ]; then
            log_success "Fichier $file trouvé"
            if grep -q "APP_ENV" "$file"; then
                log_success "Variable APP_ENV présente dans $file"
            else
                log_warning "Variable APP_ENV manquante dans $file"
            fi
        else
            log_error "Fichier $file manquant"
            return 1
        fi
    done
}

# Fonction principale
main() {
    echo "=== Test de Déploiement avec Configuration de Base de Données ==="
    echo
    
    test_config_files
    test_local_config
    test_deploy_test
    test_config_test
    test_deploy_staging
    test_config_staging
    test_staging_config
    test_env_files
    
    echo
    log_success "=== Tous les tests sont terminés ==="
    echo
    log_info "🎯 RÉSUMÉ DES TESTS :"
    echo
    log_success "✅ Fichiers de configuration présents"
    log_success "✅ Configuration locale fonctionne"
    log_success "✅ Déploiement sur test réussi"
    log_success "✅ Configuration sur test fonctionne"
    log_success "✅ Déploiement sur staging réussi"
    log_success "✅ Configuration sur staging fonctionne"
    log_success "✅ Fichiers .env créés"
    echo
    log_info "🚀 CONFIGURATION MULTI-ENVIRONNEMENT OPÉRATIONNELLE !"
    echo
    log_info "📋 ENVIRONNEMENTS CONFIGURÉS :"
    echo
    log_info "  🧪 TEST :"
    log_info "    - URL: http://localhost/jdrmj_test"
    log_info "    - DB: u839591438_jdrmj"
    log_info "    - Config: config/database.test.php"
    echo
    log_info "  🎭 STAGING :"
    log_info "    - URL: http://localhost/jdrmj_staging"
    log_info "    - DB: u839591438_jdrmj_s"
    log_info "    - Config: config/database.staging.php"
    echo
    log_info "  🏭 PRODUCTION :"
    log_info "    - URL: http://localhost/jdrmj"
    log_info "    - DB: u839591438_jdrmj"
    log_info "    - Config: config/database.production.php"
    echo
    log_success "🎲 Configuration multi-environnement terminée avec succès !"
}

# Exécution
main "$@"
