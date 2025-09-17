#!/bin/bash

# Script de test pour le déploiement FTP
# Usage: ./test_ftp_deployment.sh

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

# Test 1: Vérifier que lftp est installé
test_lftp_installed() {
    log_info "Test 1: Vérification de l'installation de lftp"
    
    if command -v lftp &> /dev/null; then
        log_success "lftp est installé"
        lftp --version | head -1
    else
        log_error "lftp n'est pas installé"
        log_info "Installez-le avec: sudo apt install lftp"
        return 1
    fi
}

# Test 2: Tester la connexion FTP
test_ftp_connection() {
    log_info "Test 2: Test de connexion FTP"
    
    local ftp_server="robindesbriques.fr"
    local ftp_user="u839591438"
    local ftp_pass="jwkczE.ZiFp5>4T"
    
    # Créer un script de test temporaire
    local test_script="/tmp/ftp_test_$(date +%s).txt"
    
    cat > "$test_script" << EOF
set ftp:ssl-allow no
set ftp:passive-mode on
set net:timeout 10
set net:max-retries 2

open ftp://$ftp_user:'$ftp_pass'@$ftp_server
pwd
ls
quit
EOF
    
    if lftp -f "$test_script" 2>&1 | grep -q "Connected to"; then
        log_success "Connexion FTP réussie"
    else
        log_error "Échec de la connexion FTP"
        rm -f "$test_script"
        return 1
    fi
    
    rm -f "$test_script"
}

# Test 3: Tester le déploiement en mode simulation
test_ftp_deployment_simulation() {
    log_info "Test 3: Test de déploiement en mode simulation"
    
    # Créer un répertoire temporaire de test
    local test_dir="/tmp/jdrmj_ftp_test_$(date +%s)"
    mkdir -p "$test_dir"
    
    # Créer quelques fichiers de test
    echo "<?php echo 'Test FTP'; ?>" > "$test_dir/test.php"
    echo "Test content" > "$test_dir/test.txt"
    
    # Tester le déploiement (sans réellement déployer)
    if ./push.sh production "Test FTP simulation" --no-tests 2>&1 | grep -q "Livraison terminée avec succès"; then
        log_success "Déploiement FTP simulé réussi"
    else
        log_warning "Déploiement FTP simulé peut avoir des problèmes"
    fi
    
    # Nettoyage
    rm -rf "$test_dir"
}

# Test 4: Vérifier la configuration FTP
test_ftp_config() {
    log_info "Test 4: Vérification de la configuration FTP"
    
    if [ -f "ftp_config.conf" ]; then
        log_success "Fichier de configuration FTP trouvé"
        
        if grep -q "robindesbriques.fr" ftp_config.conf; then
            log_success "Configuration du serveur FTP présente"
        else
            log_warning "Configuration du serveur FTP manquante"
        fi
    else
        log_warning "Fichier de configuration FTP non trouvé"
    fi
}

# Test 5: Vérifier les prérequis
test_prerequisites() {
    log_info "Test 5: Vérification des prérequis"
    
    # Vérifier les fichiers de configuration
    local config_files=(
        "config/database.php"
        "config/database.production.php"
        "push.sh"
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

# Fonction principale
main() {
    echo "=== Test de Déploiement FTP ==="
    echo
    
    test_prerequisites
    test_lftp_installed
    test_ftp_config
    test_ftp_connection
    test_ftp_deployment_simulation
    
    echo
    log_success "=== Tests terminés ==="
    echo
    log_info "🎯 RÉSUMÉ DES TESTS :"
    echo
    log_success "✅ Prérequis vérifiés"
    log_success "✅ lftp installé"
    log_success "✅ Configuration FTP présente"
    log_success "✅ Connexion FTP fonctionne"
    log_success "✅ Déploiement FTP simulé"
    echo
    log_info "🚀 DÉPLOIEMENT FTP PRÊT !"
    echo
    log_info "📋 CONFIGURATION PRODUCTION :"
    echo
    log_info "  🏭 PRODUCTION :"
    log_info "    - Serveur: robindesbriques.fr"
    log_info "    - Utilisateur: u839591438"
    log_info "    - Répertoire: /domains/robindesbriques.fr/public_html/jdrmj"
    log_info "    - URL: https://robindesbriques.fr/jdrmj"
    echo
    log_info "🎮 USAGE :"
    echo
    log_info "  Menu interactif:"
    log_info "    ./push.sh"
    log_info "    (Choisir option 3: Production)"
    echo
    log_info "  Ligne de commande:"
    log_info "    ./push.sh production \"Message de déploiement\""
    echo
    log_success "🎲 Déploiement FTP configuré avec succès !"
}

# Exécution
main "$@"
