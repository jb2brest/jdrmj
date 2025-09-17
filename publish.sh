#!/bin/bash

# Script de publication avec mise à jour de version
# Usage: ./publish.sh <type_de_version> <commentaire>
# Types de version: major, minor, patch

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

# Fonction d'aide
show_help() {
    echo "Usage: $0 <type_de_version> <commentaire>"
    echo ""
    echo "Types de version:"
    echo "  major    - Version majeure (1.0.0 -> 2.0.0)"
    echo "  minor    - Version mineure (1.0.0 -> 1.1.0)"
    echo "  patch    - Correctif (1.0.0 -> 1.0.1)"
    echo ""
    echo "Exemples:"
    echo "  $0 patch \"Correction bug affichage\""
    echo "  $0 minor \"Ajout fonctionnalité campagnes\""
    echo "  $0 major \"Refonte complète interface\""
    echo ""
    echo "Le script va:"
    echo "  1. Mettre à jour la version"
    echo "  2. Créer un commit avec le commentaire"
    echo "  3. Créer un tag Git"
    echo "  4. Pousser vers le dépôt distant"
}

# Vérifier les paramètres
if [ $# -lt 2 ]; then
    log_error "Paramètres manquants"
    echo ""
    show_help
    exit 1
fi

VERSION_TYPE="$1"
COMMENT="$2"

# Vérifier le type de version
case "$VERSION_TYPE" in
    major|minor|patch)
        ;;
    *)
        log_error "Type de version invalide: $VERSION_TYPE"
        echo ""
        show_help
        exit 1
        ;;
esac

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "VERSION" ]; then
    log_error "Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

log_info "=== Script de Publication JDR 4 MJ ==="
log_info "Type de version: $VERSION_TYPE"
log_info "Commentaire: $COMMENT"

# Lire la version actuelle
CURRENT_VERSION=$(grep "^VERSION=" VERSION | cut -d'=' -f2)
log_info "Version actuelle: $CURRENT_VERSION"

# Calculer la nouvelle version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

case "$VERSION_TYPE" in
    major)
        NEW_VERSION="$((MAJOR + 1)).0.0"
        ;;
    minor)
        NEW_VERSION="$MAJOR.$((MINOR + 1)).0"
        ;;
    patch)
        NEW_VERSION="$MAJOR.$MINOR.$((PATCH + 1))"
        ;;
esac

log_info "Nouvelle version: $NEW_VERSION"

# Confirmer la publication
echo ""
log_warning "Êtes-vous sûr de vouloir publier la version $NEW_VERSION ?"
echo "Type de version: $VERSION_TYPE"
echo "Commentaire: $COMMENT"
echo ""
read -p "Continuer ? (oui/non): " -r
if [[ ! $REPLY =~ ^[Oo]ui$ ]]; then
    log_info "Publication annulée"
    exit 0
fi

# Mettre à jour la version
log_info "Mise à jour de la version..."
if ! ./update_version.sh "$VERSION_TYPE" "production" "$COMMENT" "publish_script"; then
    log_error "Échec de la mise à jour de la version"
    exit 1
fi
log_success "Version mise à jour vers $NEW_VERSION"

# Ajouter tous les fichiers modifiés
log_info "Ajout des fichiers au commit..."
git add .

# Créer le commit
log_info "Création du commit..."
COMMIT_MESSAGE="v$NEW_VERSION: $COMMENT"
if ! git commit -m "$COMMIT_MESSAGE"; then
    log_error "Échec de la création du commit"
    exit 1
fi
log_success "Commit créé: $COMMIT_MESSAGE"

# Créer le tag
log_info "Création du tag Git..."
if ! git tag -a "v$NEW_VERSION" -m "$COMMIT_MESSAGE"; then
    log_error "Échec de la création du tag"
    exit 1
fi
log_success "Tag créé: v$NEW_VERSION"

# Pousser vers le dépôt distant
log_info "Poussée vers le dépôt distant..."
if ! git push; then
    log_error "Échec de la poussée des commits"
    exit 1
fi

if ! git push origin --tags; then
    log_error "Échec de la poussée des tags"
    exit 1
fi
log_success "Poussée vers le dépôt distant réussie"

# Afficher le résumé
echo ""
log_success "=== Publication Terminée avec Succès ==="
log_info "Version: $NEW_VERSION"
log_info "Type: $VERSION_TYPE"
log_info "Commentaire: $COMMENT"
log_info "Commit: $COMMIT_MESSAGE"
log_info "Tag: v$NEW_VERSION"
echo ""

# Afficher les commandes utiles
log_info "Commandes utiles:"
echo "  git log --oneline -5                    # Voir les 5 derniers commits"
echo "  git tag -l | tail -5                    # Voir les 5 derniers tags"
echo "  git show v$NEW_VERSION                  # Voir les détails du tag"
echo "  git checkout v$NEW_VERSION              # Revenir à cette version"
echo ""