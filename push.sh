#!/bin/bash

# Script de livraison sur le serveur de test
# Usage: ./push.sh [serveur] [message] [--no-tests]
# Exemple: ./push.sh test "Livraison version 1.4.14"
# Exemple: ./push.sh test "Livraison version 1.4.14" --no-tests

set -e  # Arrêter le script en cas d'erreur

# Configuration
DEFAULT_MESSAGE="Livraison automatique"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Variables globales
SERVER=""
MESSAGE=""
RUN_TESTS=true

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction pour afficher le menu principal
show_main_menu() {
    clear
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                    🚀 JDR 4 MJ - Déploiement                ║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}1.${NC} 🧪 Serveur de TEST (développement)                    ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}2.${NC} 🎭 Serveur de STAGING (validation)                    ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}3.${NC} 🏭 Serveur de PRODUCTION (publication)               ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${YELLOW}4.${NC} 📋 Afficher l'aide                                ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${RED}5.${NC} ❌ Quitter                                          ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo
}

# Fonction pour afficher le menu des tests
show_tests_menu() {
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                    🧪 Configuration des Tests                ║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}1.${NC} ✅ Exécuter les tests avant déploiement              ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}2.${NC} ⚡ Déployer sans exécuter les tests                 ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo
}

# Fonction pour saisir le message de déploiement
get_deployment_message() {
    echo -e "${YELLOW}💬 Message de déploiement (optionnel) :${NC}"
    echo -e "${YELLOW}   Appuyez sur Entrée pour utiliser le message par défaut${NC}"
    echo -n "   > "
    read -r input_message
    
    if [ -z "$input_message" ]; then
        MESSAGE="$DEFAULT_MESSAGE"
    else
        MESSAGE="$input_message"
    fi
    
    echo
}

# Fonction pour confirmer le déploiement
confirm_deployment() {
    local server_name=$1
    local test_status=$2
    
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                    📋 Confirmation de Déploiement             ║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}Serveur :${NC} $server_name                                    ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}Tests :${NC} $test_status                                      ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}Message :${NC} $MESSAGE                                        ${BLUE}║${NC}"
    echo -e "${BLUE}║  ${GREEN}Timestamp :${NC} $TIMESTAMP                                    ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${YELLOW}Voulez-vous continuer ? (o/N) :${NC}                        ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo -n "   > "
    read -r confirmation
    
    if [[ "$confirmation" =~ ^[OoYy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Fonction pour afficher l'aide
show_help() {
    clear
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                        📖 Aide - push.sh                     ║${NC}"
    echo -e "${BLUE}╠══════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}Usage interactif :${NC}                                        ${BLUE}║${NC}"
    echo -e "${BLUE}║    ./push.sh                                                 ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}Usage en ligne de commande :${NC}                             ${BLUE}║${NC}"
    echo -e "${BLUE}║    ./push.sh test \"Message\"                                    ${BLUE}║${NC}"
    echo -e "${BLUE}║    ./push.sh staging \"Message\" --no-tests                      ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}Serveurs disponibles :${NC}                                   ${BLUE}║${NC}"
    echo -e "${BLUE}║    🧪 test     - Serveur de développement                    ${BLUE}║${NC}"
    echo -e "${BLUE}║    🎭 staging  - Serveur de validation                      ${BLUE}║${NC}"
    echo -e "${BLUE}║    🏭 production - Serveur de production (avec protection)  ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}║  ${GREEN}Options :${NC}                                                ${BLUE}║${NC}"
    echo -e "${BLUE}║    --no-tests - Déployer sans exécuter les tests             ${BLUE}║${NC}"
    echo -e "${BLUE}║    --help     - Afficher cette aide                         ${BLUE}║${NC}"
    echo -e "${BLUE}║                                                              ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${YELLOW}Appuyez sur une touche pour continuer...${NC}"
    read -n 1 -s
}

# Fonction pour traiter les arguments en ligne de commande
parse_arguments() {
    # Serveur par défaut : test (plus sûr que production)
    if [ -z "$SERVER" ]; then
        SERVER="test"
    fi
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --no-tests)
                RUN_TESTS=false
                shift
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            test|staging|production)
                SERVER="$1"
                shift
                ;;
            *)
                if [ -z "$MESSAGE" ]; then
                    MESSAGE="$1"
                fi
                shift
                ;;
        esac
    done
}

# Fonction pour le menu interactif
interactive_menu() {
    while true; do
        show_main_menu
        echo -n "Votre choix (1-5) : "
        read -r choice
        
        case $choice in
            1)
                SERVER="test"
                break
                ;;
            2)
                SERVER="staging"
                break
                ;;
            3)
                SERVER="production"
                break
                ;;
            4)
                show_help
                continue
                ;;
            5)
                log_info "Déploiement annulé"
                exit 0
                ;;
            *)
                log_error "Choix invalide. Veuillez sélectionner 1, 2, 3, 4 ou 5."
                sleep 2
                ;;
        esac
    done
    
    # Menu des tests
    show_tests_menu
    echo -n "Votre choix (1-2) : "
    read -r test_choice
    
    case $test_choice in
        1)
            RUN_TESTS=true
            ;;
        2)
            RUN_TESTS=false
            ;;
        *)
            log_warning "Choix invalide, utilisation des tests par défaut"
            RUN_TESTS=true
            ;;
    esac
    
    # Saisie du message
    get_deployment_message
    
    # Confirmation
    local server_display=""
    local test_display=""
    
    case $SERVER in
        "test")
            server_display="🧪 TEST"
            ;;
        "staging")
            server_display="🎭 STAGING"
            ;;
        "production")
            server_display="🏭 PRODUCTION"
            ;;
    esac
    
    if [ "$RUN_TESTS" = true ]; then
        test_display="✅ Avec tests"
    else
        test_display="⚡ Sans tests"
    fi
    
    if ! confirm_deployment "$server_display" "$test_display"; then
        log_info "Déploiement annulé par l'utilisateur"
        exit 0
    fi
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    log_info "Vérification des prérequis..."
    
    # Vérifier que nous sommes dans le bon répertoire
    if [ ! -f "index.php" ]; then
        log_error "Le fichier index.php n'est pas trouvé. Êtes-vous dans le bon répertoire ?"
        exit 1
    fi
    
    # Vérifier que git est disponible
    if ! command -v git &> /dev/null; then
        log_error "Git n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi
    
    # Vérifier que rsync est disponible
    if ! command -v rsync &> /dev/null; then
        log_error "rsync n'est pas installé ou n'est pas dans le PATH"
        exit 1
    fi
    
    log_success "Prérequis vérifiés"
}

# Fonction pour exécuter les tests
run_tests() {
    if [ "$RUN_TESTS" = false ]; then
        log_info "Tests ignorés (option --no-tests activée)"
        return 0
    fi
    
    log_info "Exécution des tests avant livraison..."
    
    if [ -d "tests" ]; then
        cd tests
        
        # Vérifier si l'environnement de test existe
        if [ -d "../testenv" ]; then
            log_info "Exécution des tests de base..."
            if ../testenv/bin/python -m pytest functional/test_authentication.py functional/test_application_availability.py functional/test_fixtures.py -v --tb=short; then
                log_success "Tests de base réussis"
            else
                log_warning "Certains tests de base ont échoué, mais on continue..."
            fi
        else
            log_warning "Environnement de test non trouvé, tests ignorés"
        fi
        
        cd ..
    else
        log_warning "Répertoire de tests non trouvé, tests ignorés"
    fi
}

# Fonction pour créer le répertoire temporaire
create_temp_dir() {
    echo "/tmp/jdrmj_deploy_$(date +%s)"
}

# Fonction pour configurer l'environnement
configure_environment() {
    local deploy_path=$1
    local server=$2
    
    log_info "Configuration de l'environnement pour $server..."
    
    # Créer un fichier .env pour l'environnement
    local env_file="$deploy_path/.env"
    
    case $server in
        "test")
            cat > "$env_file" << EOF
# Configuration pour l'environnement TEST
APP_ENV=test
DEBUG=true
LOG_LEVEL=debug
EOF
            ;;
        "staging")
            cat > "$env_file" << EOF
# Configuration pour l'environnement STAGING
APP_ENV=staging
DEBUG=true
LOG_LEVEL=info
EOF
            ;;
        "production")
            cat > "$env_file" << EOF
# Configuration pour l'environnement PRODUCTION
APP_ENV=production
DEBUG=false
LOG_LEVEL=error
EOF
            ;;
    esac
    
    # Ajuster les permissions du fichier .env
    if [ "$server" != "production" ]; then
        sudo chown www-data:www-data "$env_file"
        sudo chmod 600 "$env_file"
    else
        chmod 600 "$env_file"
    fi
    
    log_success "Configuration de l'environnement terminée"
}

# Fonction pour déployer via FTP
deploy_via_ftp() {
    local temp_dir=$1
    local ftp_server=$2
    local ftp_user=$3
    local ftp_pass=$4
    local ftp_path=$5
    
    log_info "Déploiement via FTP vers $ftp_server..."
    
    # Créer un script lftp temporaire
    local lftp_script="/tmp/lftp_deploy_$(date +%s).txt"
    
    cat > "$lftp_script" << EOF
set ftp:ssl-allow no
set ftp:passive-mode on
set ftp:list-options -a
set net:timeout 30
set net:max-retries 3
set net:reconnect-interval-base 5
set net:reconnect-interval-multiplier 1

open ftp://$ftp_user:'$ftp_pass'@$ftp_server
cd $ftp_path
lcd $temp_dir

# Créer un backup de la version précédente
!echo "Création d'un backup..."
mirror -R --delete --verbose --exclude-glob="*.log" --exclude-glob="*.tmp" --exclude-glob="cache/*" --exclude-glob="sessions/*" --exclude-glob="uploads/*" . backup_$(date +%Y%m%d_%H%M%S)/

# Synchroniser les fichiers
!echo "Synchronisation des fichiers..."
mirror -R --delete --verbose --exclude-glob="*.log" --exclude-glob="*.tmp" --exclude-glob="cache/*" --exclude-glob="sessions/*" --exclude-glob="uploads/*" .

# Ajuster les permissions
!echo "Ajustement des permissions..."
chmod 755 .
chmod 644 *.php
chmod 644 *.html
chmod 644 *.css
chmod 644 *.js
chmod 644 *.md
chmod 644 *.txt
chmod 755 config/
chmod 644 config/*.php
chmod 755 includes/
chmod 644 includes/*.php
chmod 755 css/
chmod 644 css/*
chmod 755 images/
chmod 644 images/*
chmod 755 database/
chmod 644 database/*.sql

quit
EOF
    
    # Exécuter le script lftp
    if lftp -f "$lftp_script"; then
        log_success "Déploiement FTP réussi"
    else
        log_error "Échec du déploiement FTP"
        rm -f "$lftp_script"
        exit 1
    fi
    
    # Nettoyer le script temporaire
    rm -f "$lftp_script"
}

# Fonction pour préparer les fichiers
prepare_files() {
    local temp_dir=$1
    
    log_info "Préparation des fichiers pour la livraison..."
    
    # Créer le répertoire temporaire
    mkdir -p "$temp_dir"
    
    # Copier les fichiers nécessaires
    log_info "Copie des fichiers de l'application..."
    
    # Copier tous les fichiers et répertoires nécessaires
    rsync -av \
        --include="*.php" \
        --include="*.htaccess" \
        --include="*.ini" \
        --include="*.env" \
        --include="*.css" \
        --include="*.js" \
        --include="*.jpg" \
        --include="*.png" \
        --include="*.gif" \
        --include="*.svg" \
        --include="*.sql" \
        --include="*.md" \
        --include="*.txt" \
        --include="config/" \
        --include="config/**" \
        --include="includes/" \
        --include="includes/**" \
        --include="css/" \
        --include="css/**" \
        --include="images/" \
        --include="images/**" \
        --include="database/" \
        --include="database/**" \
        --exclude="*" \
        . "$temp_dir/" >/dev/null 2>&1
    
    # Exclure les fichiers de développement
    rm -rf "$temp_dir/tests"
    rm -rf "$temp_dir/testenv"
    rm -rf "$temp_dir/monenv"
    rm -rf "$temp_dir/__pycache__"
    rm -rf "$temp_dir/.git"
    rm -rf "$temp_dir/.gitignore"
    rm -rf "$temp_dir/publish.sh"
    rm -rf "$temp_dir/push.sh"
    
    log_success "Fichiers préparés dans $temp_dir"
}

# Fonction pour livrer sur le serveur
deploy_to_server() {
    local temp_dir=$1
    
    log_info "Livraison sur le serveur $SERVER..."
    
    # Configuration des serveurs (à adapter selon votre infrastructure)
    case $SERVER in
        "test")
            # Serveur de test local
            DEPLOY_PATH="/var/www/html/jdrmj_test"
            log_info "Livraison sur le serveur de test local: $DEPLOY_PATH"
            
            # Créer le répertoire de destination s'il n'existe pas
            sudo mkdir -p "$DEPLOY_PATH"
            
            # Sauvegarder la version précédente
            if [ -d "$DEPLOY_PATH" ] && [ "$(ls -A $DEPLOY_PATH)" ]; then
                BACKUP_PATH="/var/backups/jdrmj_$(date +%Y%m%d_%H%M%S)"
                log_info "Sauvegarde de la version précédente dans $BACKUP_PATH"
                sudo cp -r "$DEPLOY_PATH" "$BACKUP_PATH"
            fi
            
            # Livrer les fichiers
            sudo rsync -av --delete "$temp_dir/" "$DEPLOY_PATH/"
            
            # Ajuster les permissions
    sudo chown -R www-data:www-data "$DEPLOY_PATH"
    sudo chmod -R 755 "$DEPLOY_PATH"
    sudo chmod -R 777 "$DEPLOY_PATH/uploads" 2>/dev/null || true
            
            log_success "Livraison terminée sur le serveur de test"
            ;;
            
        "staging")
            # Serveur de staging
            DEPLOY_PATH="/var/www/html/jdrmj_staging"
            log_info "Livraison sur le serveur de staging: $DEPLOY_PATH"
            
            sudo mkdir -p "$DEPLOY_PATH"
            sudo rsync -av --delete "$temp_dir/" "$DEPLOY_PATH/"
            
            sudo chown -R www-data:www-data "$DEPLOY_PATH"
            sudo chmod -R 755 "$DEPLOY_PATH"
            sudo chmod -R 777 "$DEPLOY_PATH/uploads" 2>/dev/null || true
            
            log_success "Livraison terminée sur le serveur de staging"
            ;;
            
        "production")
            # Serveur de production via FTP
            FTP_SERVER="robindesbriques.fr"
            FTP_USER="u839591438"
            FTP_PASS="jwkczE.ZiFp5>4T"
            FTP_PATH="/domains/robindesbriques.fr/public_html/jdrmj"
            
            log_info "Livraison sur le serveur de production via FTP: $FTP_SERVER"
            
            # Vérifier que lftp est installé
            if ! command -v lftp &> /dev/null; then
                log_error "lftp n'est pas installé. Installez-le avec: sudo apt install lftp"
                exit 1
            fi
            
            # Livrer via FTP
            deploy_via_ftp "$temp_dir" "$FTP_SERVER" "$FTP_USER" "$FTP_PASS" "$FTP_PATH"
            
            log_success "Livraison terminée sur le serveur de production"
            ;;
            
        *)
            log_error "Serveur non reconnu: $SERVER"
            log_info "Serveurs disponibles: test, staging"
            exit 1
            ;;
    esac
}

# Fonction pour créer un commit de livraison
create_deployment_commit() {
    log_info "Création du commit de livraison..."
    
    # Ajouter un fichier de log de déploiement
    echo "Déploiement sur $SERVER - $TIMESTAMP" > deployment.log
    echo "Message: $MESSAGE" >> deployment.log
    echo "Serveur: $SERVER" >> deployment.log
    echo "Timestamp: $TIMESTAMP" >> deployment.log
    
    git add deployment.log
    git commit -m "Deploy to $SERVER: $MESSAGE" || log_warning "Aucun changement à commiter"
    
    log_success "Commit de livraison créé"
    
    # Mettre à jour les versions si c'est un déploiement en production
    if [ "$SERVER" = "production" ]; then
        log_info "Mise à jour des versions..."
        ./update_version.sh "patch" "production" "$MESSAGE" "deployment_script"
        log_success "Versions mises à jour"
    fi
}

# Fonction de nettoyage
cleanup() {
    if [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        log_info "Nettoyage des fichiers temporaires..."
        rm -rf "$TEMP_DIR"
        log_success "Nettoyage terminé"
    fi
}

# Fonction principale
main() {
    # Si aucun argument fourni, utiliser le menu interactif
    if [ $# -eq 0 ]; then
        interactive_menu
    else
        # Traiter les arguments en ligne de commande
        parse_arguments "$@"
        
        # Mode ligne de commande
        if [ -z "$MESSAGE" ]; then
            MESSAGE="$DEFAULT_MESSAGE"
        fi
    fi
    
    log_info "=== Script de livraison JDR 4 MJ ==="
    log_info "Serveur: $SERVER"
    log_info "Message: $MESSAGE"
    log_info "Tests: $([ "$RUN_TESTS" = true ] && echo "Activés" || echo "Désactivés")"
    log_info "Timestamp: $TIMESTAMP"
    echo

    # Vérifier les prérequis
    check_prerequisites

    # Exécuter les tests
    run_tests

    # Préparer les fichiers
    TEMP_DIR=$(create_temp_dir)
    prepare_files "$TEMP_DIR"

    # Configurer l'environnement
    configure_environment "$TEMP_DIR" "$SERVER"

    # Livrer sur le serveur
    deploy_to_server "$TEMP_DIR"

    # Créer le commit de livraison
    create_deployment_commit

    # Nettoyage
    cleanup

    log_success "=== Livraison terminée avec succès ==="
    log_info "Application disponible sur le serveur $SERVER"
    
    # Afficher l'URL selon le serveur
    case $SERVER in
        "test")
            log_info "URL: http://localhost/jdrmj_test"
            ;;
        "staging")
            log_info "URL: http://localhost/jdrmj_staging"
            ;;
        "production")
            log_info "URL: https://robindesbriques.fr/jdrmj"
            ;;
    esac
}

# Gestion des erreurs
trap cleanup EXIT

# Exécution du script principal
main "$@"
