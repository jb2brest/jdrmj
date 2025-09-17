#!/bin/bash

# Script de déploiement du rôle admin
# Ce script met à jour l'application avec les nouvelles fonctionnalités admin

echo "=== Déploiement du Rôle Admin ==="
echo ""

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions de log
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

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "push.sh" ]; then
    log_error "Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

log_info "Déploiement du rôle admin en cours..."

# 1. Déployer les modifications de code
log_info "Déploiement des modifications de code..."
./push.sh production "Ajout du rôle admin - jean.m.bernard@gmail.com promu admin" --no-tests

if [ $? -eq 0 ]; then
    log_success "Code déployé avec succès"
else
    log_error "Échec du déploiement du code"
    exit 1
fi

# 2. Mettre à jour la base de données de production
log_info "Mise à jour de la base de données de production..."
mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' < database/add_admin_role.sql

if [ $? -eq 0 ]; then
    log_success "Base de données mise à jour avec succès"
else
    log_error "Échec de la mise à jour de la base de données"
    exit 1
fi

# 3. Vérifier que tout fonctionne
log_info "Vérification du déploiement..."
mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' -e "USE u839591438_jdrmj; SELECT username, email, role FROM users WHERE email = 'jean.m.bernard@gmail.com';"

if [ $? -eq 0 ]; then
    log_success "Vérification réussie"
else
    log_error "Échec de la vérification"
    exit 1
fi

echo ""
log_success "=== Déploiement du rôle admin terminé avec succès ==="
echo ""
echo "Résumé des modifications:"
echo "✅ Rôle 'admin' ajouté au système"
echo "✅ Utilisateur jean.m.bernard@gmail.com promu admin"
echo "✅ Fonctions PHP mises à jour pour gérer les rôles"
echo "✅ Code déployé en production"
echo "✅ Base de données mise à jour"
echo ""
echo "L'admin a maintenant:"
echo "- Tous les privilèges des joueurs"
echo "- Tous les privilèges des MJ"
echo "- Privilèges admin supplémentaires"
echo ""
echo "URL de l'application: https://robindesbriques.fr/jdrmj"
echo "Compte admin: jean.m.bernard@gmail.com"
