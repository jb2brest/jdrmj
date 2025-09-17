#!/bin/bash

# =====================================================
# SCRIPT DE DÉPLOIEMENT DE LA BASE DE DONNÉES
# Application JDR MJ - D&D 5e
# =====================================================
# 
# Ce script déploie la base de données sur les serveurs
# de test, staging et production
#
# Usage: ./deploy_database.sh [environment] [action]
# 
# Environnements: test, staging, production
# Actions: init, verify, reset
#
# =====================================================

set -e

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
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

# Configuration des environnements
declare -A DB_CONFIGS
DB_CONFIGS[test_host]="localhost"
DB_CONFIGS[test_name]="u839591438_jdrmj"
DB_CONFIGS[test_user]="u839591438_jdrmj"
DB_CONFIGS[test_pass]="M8jbsYJUj6FE$;C"

DB_CONFIGS[staging_host]="localhost"
DB_CONFIGS[staging_name]="u839591438_jdrmj_s"
DB_CONFIGS[staging_user]="u839591438_jdrmj"
DB_CONFIGS[staging_pass]="M8jbsYJUj6FE$;C"

DB_CONFIGS[production_host]="localhost"
DB_CONFIGS[production_name]="u839591438_jdrmj"
DB_CONFIGS[production_user]="u839591438_jdrmj"
DB_CONFIGS[production_pass]="M8jbsYJUj6FE$;C"

# Fonction d'aide
show_help() {
    echo "Usage: $0 [environment] [action]"
    echo
    echo "Environnements:"
    echo "  test       - Serveur de test"
    echo "  staging    - Serveur de staging"
    echo "  production - Serveur de production"
    echo
    echo "Actions:"
    echo "  init       - Initialiser la base de données"
    echo "  verify     - Vérifier la base de données"
    echo "  reset      - Réinitialiser la base de données (DANGEREUX)"
    echo "  status     - Afficher le statut de la base de données"
    echo
    echo "Exemples:"
    echo "  $0 test init"
    echo "  $0 staging verify"
    echo "  $0 production status"
    echo
    echo "⚠️  ATTENTION: Ne pas exécuter en local !"
}

# Fonction pour obtenir la configuration de la base de données
get_db_config() {
    local env=$1
    local config_key=$2
    
    case $env in
        "test")
            echo "${DB_CONFIGS[test_$config_key]}"
            ;;
        "staging")
            echo "${DB_CONFIGS[staging_$config_key]}"
            ;;
        "production")
            echo "${DB_CONFIGS[production_$config_key]}"
            ;;
        *)
            log_error "Environnement non reconnu: $env"
            exit 1
            ;;
    esac
}

# Fonction pour tester la connexion à la base de données
test_connection() {
    local env=$1
    local host=$(get_db_config $env "host")
    local name=$(get_db_config $env "name")
    local user=$(get_db_config $env "user")
    local pass=$(get_db_config $env "pass")
    
    log_info "Test de connexion à la base de données $env..."
    
    if mysql -h "$host" -u "$user" -p"$pass" -e "USE $name; SELECT 1;" >/dev/null 2>&1; then
        log_success "Connexion à la base de données $env réussie"
        return 0
    else
        log_error "Échec de la connexion à la base de données $env"
        return 1
    fi
}

# Fonction pour initialiser la base de données
init_database() {
    local env=$1
    local host=$(get_db_config $env "host")
    local name=$(get_db_config $env "name")
    local user=$(get_db_config $env "user")
    local pass=$(get_db_config $env "pass")
    
    log_info "Initialisation de la base de données $env..."
    
    # Déterminer le script d'initialisation à utiliser
    local init_script="database/init_database.sql"
    if [ "$env" = "production" ]; then
        init_script="database/simple_migrate_production.sql"
    fi
    
    # Vérifier que le fichier d'initialisation existe
    if [ ! -f "$init_script" ]; then
        log_error "Fichier $init_script non trouvé"
        exit 1
    fi
    
    log_info "Utilisation du script: $init_script"
    
    # Exécuter le script d'initialisation
    if mysql -h "$host" -u "$user" -p"$pass" < "$init_script"; then
        log_success "Initialisation de la base de données $env réussie"
    else
        log_error "Échec de l'initialisation de la base de données $env"
        exit 1
    fi
}

# Fonction pour vérifier la base de données
verify_database() {
    local env=$1
    local host=$(get_db_config $env "host")
    local name=$(get_db_config $env "name")
    local user=$(get_db_config $env "user")
    local pass=$(get_db_config $env "pass")
    
    log_info "Vérification de la base de données $env..."
    
    # Déterminer le script de vérification à utiliser
    local verify_script="database/verify_database.sql"
    if [ "$env" = "production" ]; then
        verify_script="database/verify_database_production.sql"
    fi
    
    # Vérifier que le fichier de vérification existe
    if [ ! -f "$verify_script" ]; then
        log_error "Fichier $verify_script non trouvé"
        exit 1
    fi
    
    log_info "Utilisation du script: $verify_script"
    
    # Exécuter le script de vérification
    if mysql -h "$host" -u "$user" -p"$pass" < "$verify_script"; then
        log_success "Vérification de la base de données $env réussie"
    else
        log_error "Échec de la vérification de la base de données $env"
        exit 1
    fi
}

# Fonction pour réinitialiser la base de données
reset_database() {
    local env=$1
    local host=$(get_db_config $env "host")
    local name=$(get_db_config $env "name")
    local user=$(get_db_config $env "user")
    local pass=$(get_db_config $env "pass")
    
    log_warning "⚠️  ATTENTION: Cette action va SUPPRIMER toutes les données !"
    log_warning "Base de données: $name sur $host"
    
    read -p "Êtes-vous sûr de vouloir continuer ? (tapez 'OUI' pour confirmer): " confirmation
    
    if [ "$confirmation" != "OUI" ]; then
        log_info "Opération annulée"
        exit 0
    fi
    
    log_info "Suppression de la base de données $env..."
    
    # Supprimer la base de données
    if mysql -h "$host" -u "$user" -p"$pass" -e "DROP DATABASE IF EXISTS $name;"; then
        log_success "Base de données $env supprimée"
    else
        log_error "Échec de la suppression de la base de données $env"
        exit 1
    fi
    
    # Recréer la base de données
    if mysql -h "$host" -u "$user" -p"$pass" -e "CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; then
        log_success "Base de données $env recréée"
    else
        log_error "Échec de la recréation de la base de données $env"
        exit 1
    fi
    
    # Réinitialiser
    init_database $env
}

# Fonction pour afficher le statut de la base de données
show_status() {
    local env=$1
    local host=$(get_db_config $env "host")
    local name=$(get_db_config $env "name")
    local user=$(get_db_config $env "user")
    local pass=$(get_db_config $env "pass")
    
    log_info "Statut de la base de données $env..."
    
    # Test de connexion
    if ! test_connection $env; then
        return 1
    fi
    
    # Informations sur la base de données
    echo
    log_info "Informations sur la base de données:"
    mysql -h "$host" -u "$user" -p"$pass" -e "
        SELECT 
            'Base de données' as Info,
            '$name' as Valeur
        UNION ALL
        SELECT 
            'Serveur',
            '$host'
        UNION ALL
        SELECT 
            'Utilisateur',
            '$user'
        UNION ALL
        SELECT 
            'Tables',
            COUNT(*)
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = '$name'
        UNION ALL
        SELECT 
            'Taille (MB)',
            ROUND(SUM((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2)
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = '$name';
    "
    
    # Tables présentes
    echo
    log_info "Tables présentes:"
    mysql -h "$host" -u "$user" -p"$pass" -e "USE $name; SHOW TABLES;" | tail -n +2 | sort
    
    # Données initiales
    echo
    log_info "Données initiales:"
    mysql -h "$host" -u "$user" -p"$pass" -e "
        USE $name;
        SELECT 'Races' as Table_Name, COUNT(*) as Count FROM races
        UNION ALL
        SELECT 'Classes', COUNT(*) FROM classes
        UNION ALL
        SELECT 'Backgrounds', COUNT(*) FROM backgrounds
        UNION ALL
        SELECT 'Languages', COUNT(*) FROM languages
        UNION ALL
        SELECT 'Experience Levels', COUNT(*) FROM experience_levels
        UNION ALL
        SELECT 'Users', COUNT(*) FROM users
        UNION ALL
        SELECT 'Characters', COUNT(*) FROM characters;
    "
}

# Fonction principale
main() {
    local environment=$1
    local action=$2
    
    # Vérifier les arguments
    if [ $# -ne 2 ]; then
        show_help
        exit 1
    fi
    
    # Vérifier l'environnement
    if [[ ! "$environment" =~ ^(test|staging|production)$ ]]; then
        log_error "Environnement non valide: $environment"
        show_help
        exit 1
    fi
    
    # Vérifier l'action
    if [[ ! "$action" =~ ^(init|verify|reset|status)$ ]]; then
        log_error "Action non valide: $action"
        show_help
        exit 1
    fi
    
    # Avertissement de sécurité
    if [ "$environment" = "production" ]; then
        log_warning "⚠️  ATTENTION: Vous êtes sur l'environnement PRODUCTION !"
        read -p "Continuer ? (y/N): " confirm
        if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
            log_info "Opération annulée"
            exit 0
        fi
    fi
    
    echo "=== Déploiement de la Base de Données ==="
    echo "Environnement: $environment"
    echo "Action: $action"
    echo
    
    # Exécuter l'action demandée
    case $action in
        "init")
            init_database $environment
            ;;
        "verify")
            verify_database $environment
            ;;
        "reset")
            reset_database $environment
            ;;
        "status")
            show_status $environment
            ;;
    esac
    
    echo
    log_success "=== Opération terminée avec succès ==="
}

# Exécution
main "$@"
