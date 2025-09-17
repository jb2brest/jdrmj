#!/bin/bash

# Script de configuration des bases de données pour les différents environnements
# Usage: ./setup_databases.sh [test|staging|production]

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

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration des bases de données
declare -A DB_CONFIGS

DB_CONFIGS[test_host]="localhost"
DB_CONFIGS[test_db]="jdrmj_test"
DB_CONFIGS[test_user]="jdrmj_test_user"
DB_CONFIGS[test_pass]="test_password_123"

DB_CONFIGS[staging_host]="localhost"
DB_CONFIGS[staging_db]="jdrmj_staging"
DB_CONFIGS[staging_user]="jdrmj_staging_user"
DB_CONFIGS[staging_pass]="staging_password_456"

DB_CONFIGS[production_host]="localhost"
DB_CONFIGS[production_db]="u839591438_jdrmj"
DB_CONFIGS[production_user]="u839591438_jdrmj"
DB_CONFIGS[production_pass]="M8jbsYJUj6FE$;C"

# Fonction pour créer une base de données
create_database() {
    local env=$1
    local host=${DB_CONFIGS[${env}_host]}
    local dbname=${DB_CONFIGS[${env}_db]}
    local username=${DB_CONFIGS[${env}_user]}
    local password=${DB_CONFIGS[${env}_pass]}
    
    log_info "Configuration de la base de données pour l'environnement: $env"
    
    # Créer la base de données
    log_info "Création de la base de données: $dbname"
    mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS \`$dbname\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Créer l'utilisateur
    log_info "Création de l'utilisateur: $username"
    mysql -u root -p -e "CREATE USER IF NOT EXISTS '$username'@'$host' IDENTIFIED BY '$password';"
    
    # Accorder les privilèges
    log_info "Attribution des privilèges à l'utilisateur: $username"
    mysql -u root -p -e "GRANT ALL PRIVILEGES ON \`$dbname\`.* TO '$username'@'$host';"
    mysql -u root -p -e "FLUSH PRIVILEGES;"
    
    log_success "Base de données $env configurée avec succès"
}

# Fonction pour importer le schéma
import_schema() {
    local env=$1
    local dbname=${DB_CONFIGS[${env}_db]}
    
    log_info "Importation du schéma pour l'environnement: $env"
    
    if [ -f "database/schema.sql" ]; then
        mysql -u root -p "$dbname" < database/schema.sql
        log_success "Schéma importé avec succès"
    else
        log_warning "Fichier schema.sql non trouvé"
    fi
}

# Fonction pour importer les données de test
import_test_data() {
    local env=$1
    local dbname=${DB_CONFIGS[${env}_db]}
    
    if [ "$env" = "test" ] || [ "$env" = "staging" ]; then
        log_info "Importation des données de test pour l'environnement: $env"
        
        # Importer les données de test si disponibles
        for sql_file in database/*.sql; do
            if [ -f "$sql_file" ] && [[ "$sql_file" != *"schema.sql" ]]; then
                log_info "Importation de $(basename "$sql_file")"
                mysql -u root -p "$dbname" < "$sql_file"
            fi
        done
        
        log_success "Données de test importées avec succès"
    fi
}

# Fonction pour afficher l'aide
show_help() {
    echo "Usage: $0 [test|staging|production|all]"
    echo
    echo "Options:"
    echo "  test       - Configurer la base de données de test"
    echo "  staging    - Configurer la base de données de staging"
    echo "  production - Configurer la base de données de production"
    echo "  all        - Configurer toutes les bases de données"
    echo "  help       - Afficher cette aide"
    echo
    echo "Exemples:"
    echo "  $0 test"
    echo "  $0 all"
}

# Fonction principale
main() {
    local environment=${1:-"all"}
    
    case $environment in
        "test")
            create_database "test"
            import_schema "test"
            import_test_data "test"
            ;;
        "staging")
            create_database "staging"
            import_schema "staging"
            import_test_data "staging"
            ;;
        "production")
            log_warning "Configuration de la production - Vérifiez les paramètres !"
            read -p "Êtes-vous sûr de vouloir configurer la production ? (y/N): " confirm
            if [[ $confirm =~ ^[Yy]$ ]]; then
                create_database "production"
                import_schema "production"
            else
                log_info "Configuration de la production annulée"
            fi
            ;;
        "all")
            create_database "test"
            import_schema "test"
            import_test_data "test"
            
            create_database "staging"
            import_schema "staging"
            import_test_data "staging"
            
            log_warning "Configuration de la production - Vérifiez les paramètres !"
            read -p "Voulez-vous configurer la production ? (y/N): " confirm
            if [[ $confirm =~ ^[Yy]$ ]]; then
                create_database "production"
                import_schema "production"
            fi
            ;;
        "help"|"-h"|"--help")
            show_help
            exit 0
            ;;
        *)
            log_error "Environnement non reconnu: $environment"
            show_help
            exit 1
            ;;
    esac
    
    log_success "Configuration terminée !"
}

# Exécution
main "$@"
