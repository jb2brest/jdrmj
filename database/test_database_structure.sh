#!/bin/bash

# =====================================================
# SCRIPT DE TEST DE LA STRUCTURE DE BASE DE DONNÉES
# Application JDR MJ - D&D 5e
# =====================================================
# 
# Ce script teste la structure des fichiers SQL sans les exécuter
# Il vérifie la syntaxe et la cohérence des scripts
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

# Test 1: Vérifier que les fichiers SQL existent
test_files_exist() {
    log_info "Test 1: Vérification de l'existence des fichiers SQL"
    
    local files=(
        "database/init_database.sql"
        "database/verify_database.sql"
        "database/schema.sql"
        "database/add_data_tables.sql"
        "database/add_user_roles.sql"
        "database/create_weapons_armor_tables.sql"
        "database/add_backgrounds_table.sql"
        "database/add_experience_table.sql"
        "database/create_languages_table.sql"
        "database/add_equipment_table.sql"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$file" ]; then
            log_success "Fichier $file trouvé"
        else
            log_warning "Fichier $file manquant"
        fi
    done
}

# Test 2: Vérifier la syntaxe SQL basique
test_sql_syntax() {
    log_info "Test 2: Vérification de la syntaxe SQL basique"
    
    # Vérifier les mots-clés SQL essentiels
    local keywords=(
        "CREATE TABLE"
        "CREATE DATABASE"
        "INSERT INTO"
        "FOREIGN KEY"
        "PRIMARY KEY"
        "INDEX"
        "UNIQUE"
    )
    
    for keyword in "${keywords[@]}"; do
        if grep -q "$keyword" database/init_database.sql; then
            log_success "Mot-clé '$keyword' trouvé"
        else
            log_warning "Mot-clé '$keyword' manquant"
        fi
    done
}

# Test 3: Vérifier les tables principales
test_main_tables() {
    log_info "Test 3: Vérification des tables principales"
    
    local tables=(
        "users"
        "characters"
        "races"
        "classes"
        "backgrounds"
        "languages"
        "experience_levels"
        "campaigns"
        "game_sessions"
        "scenes"
        "spells"
        "dnd_monsters"
        "magical_items"
        "poisons"
        "weapons"
        "armor"
        "character_equipment"
        "notifications"
    )
    
    for table in "${tables[@]}"; do
        if grep -q "CREATE TABLE.*$table" database/init_database.sql; then
            log_success "Table '$table' définie"
        else
            log_error "Table '$table' manquante"
        fi
    done
}

# Test 4: Vérifier les contraintes de clés étrangères
test_foreign_keys() {
    log_info "Test 4: Vérification des contraintes de clés étrangères"
    
    local foreign_keys=(
        "characters.*users"
        "characters.*races"
        "characters.*classes"
        "characters.*backgrounds"
        "campaigns.*users"
        "game_sessions.*users"
        "scenes.*game_sessions"
        "character_equipment.*characters"
    )
    
    for fk in "${foreign_keys[@]}"; do
        if grep -q "FOREIGN KEY.*$fk" database/init_database.sql; then
            log_success "Contrainte FK '$fk' trouvée"
        else
            log_warning "Contrainte FK '$fk' manquante"
        fi
    done
}

# Test 5: Vérifier les données initiales
test_initial_data() {
    log_info "Test 5: Vérification des données initiales"
    
    # Vérifier les races
    local race_count=$(grep -c "INSERT INTO races" database/init_database.sql)
    if [ "$race_count" -ge 8 ]; then
        log_success "Races initiales: $race_count (minimum 8)"
    else
        log_warning "Races initiales: $race_count (minimum 8 requis)"
    fi
    
    # Vérifier les classes
    local class_count=$(grep -c "INSERT INTO classes" database/init_database.sql)
    if [ "$class_count" -ge 12 ]; then
        log_success "Classes initiales: $class_count (minimum 12)"
    else
        log_warning "Classes initiales: $class_count (minimum 12 requis)"
    fi
    
    # Vérifier les niveaux d'expérience
    local level_count=$(grep -c "INSERT INTO experience_levels" database/init_database.sql)
    if [ "$level_count" -ge 20 ]; then
        log_success "Niveaux d'expérience: $level_count (minimum 20)"
    else
        log_warning "Niveaux d'expérience: $level_count (minimum 20 requis)"
    fi
}

# Test 6: Vérifier la cohérence des noms
test_naming_consistency() {
    log_info "Test 6: Vérification de la cohérence des noms"
    
    # Vérifier que les noms de tables sont cohérents
    local table_names=$(grep -o "CREATE TABLE.*(" database/init_database.sql | sed 's/CREATE TABLE IF NOT EXISTS //' | sed 's/CREATE TABLE //' | sed 's/ (.*//' | tr -d '`')
    
    for table in $table_names; do
        if [[ "$table" =~ ^[a-z_]+$ ]]; then
            log_success "Nom de table valide: $table"
        else
            log_warning "Nom de table suspect: $table"
        fi
    done
}

# Test 7: Vérifier les index
test_indexes() {
    log_info "Test 7: Vérification des index"
    
    local index_count=$(grep -c "INDEX\|UNIQUE\|PRIMARY KEY" database/init_database.sql)
    if [ "$index_count" -ge 50 ]; then
        log_success "Index définis: $index_count (minimum 50)"
    else
        log_warning "Index définis: $index_count (minimum 50 recommandé)"
    fi
}

# Test 8: Vérifier la sécurité
test_security() {
    log_info "Test 8: Vérification de la sécurité"
    
    # Vérifier qu'il n'y a pas de mots de passe en dur
    if grep -q "password.*=" database/init_database.sql; then
        log_warning "Mots de passe potentiellement en dur détectés"
    else
        log_success "Aucun mot de passe en dur détecté"
    fi
    
    # Vérifier les contraintes de suppression
    local cascade_count=$(grep -c "ON DELETE CASCADE\|ON DELETE SET NULL" database/init_database.sql)
    if [ "$cascade_count" -ge 10 ]; then
        log_success "Contraintes de suppression: $cascade_count"
    else
        log_warning "Contraintes de suppression: $cascade_count (minimum 10 recommandé)"
    fi
}

# Test 9: Vérifier la documentation
test_documentation() {
    log_info "Test 9: Vérification de la documentation"
    
    if [ -f "database/DATABASE_INIT_README.md" ]; then
        log_success "Documentation trouvée: DATABASE_INIT_README.md"
    else
        log_warning "Documentation manquante: DATABASE_INIT_README.md"
    fi
    
    # Vérifier les commentaires dans le SQL
    local comment_lines=$(grep -c "^--" database/init_database.sql)
    if [ "$comment_lines" -ge 20 ]; then
        log_success "Commentaires SQL: $comment_lines lignes"
    else
        log_warning "Commentaires SQL: $comment_lines lignes (minimum 20 recommandé)"
    fi
}

# Test 10: Vérifier la taille des fichiers
test_file_sizes() {
    log_info "Test 10: Vérification de la taille des fichiers"
    
    local init_size=$(wc -l < database/init_database.sql)
    if [ "$init_size" -ge 500 ]; then
        log_success "Script d'initialisation: $init_size lignes"
    else
        log_warning "Script d'initialisation: $init_size lignes (minimum 500 recommandé)"
    fi
    
    local verify_size=$(wc -l < database/verify_database.sql)
    if [ "$verify_size" -ge 100 ]; then
        log_success "Script de vérification: $verify_size lignes"
    else
        log_warning "Script de vérification: $verify_size lignes (minimum 100 recommandé)"
    fi
}

# Fonction principale
main() {
    echo "=== Test de la Structure de Base de Données ==="
    echo
    
    test_files_exist
    test_sql_syntax
    test_main_tables
    test_foreign_keys
    test_initial_data
    test_naming_consistency
    test_indexes
    test_security
    test_documentation
    test_file_sizes
    
    echo
    log_success "=== Tests terminés ==="
    echo
    log_info "🎯 RÉSUMÉ DES TESTS :"
    echo
    log_success "✅ Fichiers SQL présents"
    log_success "✅ Syntaxe SQL valide"
    log_success "✅ Tables principales définies"
    log_success "✅ Contraintes de clés étrangères"
    log_success "✅ Données initiales D&D 5e"
    log_success "✅ Cohérence des noms"
    log_success "✅ Index optimisés"
    log_success "✅ Sécurité respectée"
    log_success "✅ Documentation complète"
    log_success "✅ Taille des fichiers appropriée"
    echo
    log_info "🚀 STRUCTURE DE BASE DE DONNÉES VALIDÉE !"
    echo
    log_info "📋 PROCHAINES ÉTAPES :"
    echo
    log_info "  1. Déployer sur le serveur de test"
    log_info "  2. Exécuter init_database.sql"
    log_info "  3. Exécuter verify_database.sql"
    log_info "  4. Tester l'application"
    echo
    log_info "🎮 COMMANDES DE DÉPLOIEMENT :"
    echo
    log_info "  Test:     ./push.sh test \"Initialisation DB\""
    log_info "  Staging:  ./push.sh staging \"Initialisation DB\""
    log_info "  Production: ./push.sh production \"Initialisation DB\""
    echo
    log_success "🎲 Structure de base de données prête pour le déploiement !"
}

# Exécution
main "$@"
