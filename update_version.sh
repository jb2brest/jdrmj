#!/bin/bash

# Script de mise à jour des versions
# Ce script met à jour les versions de l'application et de la base de données

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
if [ ! -f "VERSION" ]; then
    log_error "Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

# Paramètres
VERSION_TYPE=${1:-"patch"}  # major, minor, patch
ENVIRONMENT=${2:-"production"}
RELEASE_NOTES=${3:-"Mise à jour automatique"}
DEPLOY_USER=${4:-"system"}

log_info "Mise à jour des versions - Type: $VERSION_TYPE, Environnement: $ENVIRONMENT"

# Lire la version actuelle
CURRENT_VERSION=$(grep "^VERSION=" VERSION | cut -d'=' -f2)
log_info "Version actuelle: $CURRENT_VERSION"

# Calculer la nouvelle version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

case $VERSION_TYPE in
    "major")
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    "minor")
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    "patch")
        PATCH=$((PATCH + 1))
        ;;
    *)
        log_error "Type de version invalide: $VERSION_TYPE (major, minor, patch)"
        exit 1
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
log_info "Nouvelle version: $NEW_VERSION"

# Générer un Build ID
BUILD_ID=$(date +"%Y%m%d-%H%M%S")

# Obtenir le commit Git actuel
GIT_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "unknown")

# Mettre à jour le fichier VERSION
log_info "Mise à jour du fichier VERSION..."
cat > VERSION << EOF
# Version de l'application JDR MJ
# Format: MAJOR.MINOR.PATCH

# Version actuelle
VERSION=$NEW_VERSION

# Date de déploiement
DEPLOY_DATE=$(date +"%Y-%m-%d")

# Environnement
ENVIRONMENT=$ENVIRONMENT

# Commit Git (sera mis à jour automatiquement)
GIT_COMMIT=$GIT_COMMIT

# Build ID (sera généré automatiquement)
BUILD_ID=$BUILD_ID

# Notes de version
RELEASE_NOTES="$RELEASE_NOTES"
EOF

log_success "Fichier VERSION mis à jour"

# Mettre à jour la base de données si on est en production
if [ "$ENVIRONMENT" = "production" ]; then
    log_info "Mise à jour de la base de données de production..."
    
    # Créer un script SQL temporaire
    TEMP_SQL="/tmp/update_version_$$.sql"
    cat > "$TEMP_SQL" << EOF
USE u839591438_jdrmj;

-- Marquer les versions actuelles comme non actuelles
UPDATE system_versions SET is_current = FALSE WHERE is_current = TRUE;

-- Ajouter la nouvelle version de l'application
INSERT INTO system_versions (
    version_type, 
    version_number, 
    build_id, 
    git_commit, 
    deploy_date, 
    deploy_user, 
    environment, 
    release_notes,
    is_current
) VALUES (
    'application',
    '$NEW_VERSION',
    '$BUILD_ID',
    '$GIT_COMMIT',
    NOW(),
    '$DEPLOY_USER',
    '$ENVIRONMENT',
    '$RELEASE_NOTES',
    TRUE
);

-- Ajouter la nouvelle version de la base de données si c'est une migration
EOF

    # Ajouter la version de la base de données si c'est une migration majeure ou mineure
    if [ "$VERSION_TYPE" = "major" ] || [ "$VERSION_TYPE" = "minor" ]; then
        cat >> "$TEMP_SQL" << EOF
INSERT INTO system_versions (
    version_type, 
    version_number, 
    build_id, 
    deploy_date, 
    deploy_user, 
    environment, 
    release_notes,
    is_current
) VALUES (
    'database',
    '$NEW_VERSION',
    '$BUILD_ID',
    NOW(),
    '$DEPLOY_USER',
    '$ENVIRONMENT',
    '$RELEASE_NOTES',
    TRUE
);
EOF
    fi

    cat >> "$TEMP_SQL" << EOF

-- Afficher les versions actuelles
SELECT 'Versions mises à jour:' as Status;
SELECT 
    version_type as 'Type',
    version_number as 'Version',
    build_id as 'Build ID',
    deploy_date as 'Date de déploiement',
    environment as 'Environnement'
FROM system_versions 
WHERE is_current = TRUE
ORDER BY version_type;
EOF

    # Exécuter le script SQL
    if mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' < "$TEMP_SQL"; then
        log_success "Base de données mise à jour"
    else
        log_error "Échec de la mise à jour de la base de données"
        rm -f "$TEMP_SQL"
        exit 1
    fi
    
    # Nettoyer le fichier temporaire
    rm -f "$TEMP_SQL"
fi

# Afficher le résumé
echo ""
log_success "=== Mise à jour des versions terminée ==="
echo ""
echo "Résumé:"
echo "  Version précédente: $CURRENT_VERSION"
echo "  Nouvelle version:   $NEW_VERSION"
echo "  Type de mise à jour: $VERSION_TYPE"
echo "  Build ID:          $BUILD_ID"
echo "  Environnement:     $ENVIRONMENT"
echo "  Commit Git:        $GIT_COMMIT"
echo "  Notes:             $RELEASE_NOTES"
echo ""

# Proposer de commiter les changements
if [ "$GIT_COMMIT" != "unknown" ]; then
    log_info "Voulez-vous commiter ces changements ? (y/N)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        git add VERSION
        git commit -m "Version $NEW_VERSION: $RELEASE_NOTES"
        log_success "Changements commités"
    fi
fi

log_success "Mise à jour terminée avec succès !"
