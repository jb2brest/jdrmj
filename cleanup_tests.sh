#!/bin/bash

# Script de nettoyage automatique des données de test
# Usage: ./cleanup_tests.sh [--days=N] [--dry-run] [--all] [--python]

# Configuration par défaut
DAYS_OLD=1
DRY_RUN=false
CLEAN_ALL=false
USE_PYTHON=false

# Parser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --days=*)
            DAYS_OLD="${1#*=}"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --all)
            CLEAN_ALL=true
            shift
            ;;
        --python)
            USE_PYTHON=true
            shift
            ;;
        -h|--help)
            echo "Usage: $0 [--days=N] [--dry-run] [--all] [--python]"
            echo "  --days=N    Supprimer les données > N jours (défaut: 1)"
            echo "  --dry-run    Afficher ce qui serait supprimé sans supprimer"
            echo "  --all        Supprimer TOUTES les données de test"
            echo "  --python     Utiliser le script Python au lieu du script PHP"
            echo "  -h, --help   Afficher cette aide"
            exit 0
            ;;
        *)
            echo "Argument inconnu: $1"
            echo "Utilisez --help pour voir les options disponibles"
            exit 1
            ;;
    esac
done

echo "🧹 Nettoyage des données de test"
echo "================================"

if [ "$DRY_RUN" = true ]; then
    echo "🔍 Mode dry-run activé - Aucune donnée ne sera supprimée"
fi

if [ "$CLEAN_ALL" = true ]; then
    echo "⚠️  Mode 'tout supprimer' activé - TOUTES les données de test seront supprimées"
    DAYS_OLD=0
fi

echo "📅 Suppression des données > $DAYS_OLD jour(s)"
echo ""

# Changer vers le répertoire du projet
cd "$(dirname "$0")"

if [ "$USE_PYTHON" = true ]; then
    echo "🐍 Utilisation du script Python..."
    if [ "$DRY_RUN" = true ]; then
        python3 tests/cleanup_test_users.py --dry-run --days=$DAYS_OLD
    elif [ "$CLEAN_ALL" = true ]; then
        python3 tests/cleanup_test_users.py --all
    else
        python3 tests/cleanup_test_users.py --days=$DAYS_OLD
    fi
else
    echo "🐘 Utilisation du script PHP intelligent..."
    if [ "$DRY_RUN" = true ]; then
        python3 smart_cleanup.py --dry-run
    elif [ "$CLEAN_ALL" = true ]; then
        python3 smart_cleanup.py --all
    else
        python3 smart_cleanup.py --days=$DAYS_OLD
    fi
fi

echo ""
echo "✅ Nettoyage terminé"