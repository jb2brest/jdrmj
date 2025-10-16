#!/bin/bash

# Script de nettoyage des utilisateurs de test
# Usage: ./cleanup_tests.sh [options]

echo "🧹 Script de nettoyage des utilisateurs de test - JDR MJ"
echo "=================================================="

# Fonction d'aide
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --dry-run          Afficher les utilisateurs qui seraient supprimés sans les supprimer"
    echo "  --all              Supprimer tous les utilisateurs de test (peu importe l'âge)"
    echo "  --days=N           Supprimer les utilisateurs créés il y a plus de N jours (défaut: 1)"
    echo "  --python           Utiliser le script Python au lieu du script PHP"
    echo "  --help, -h         Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0 --dry-run                    # Voir ce qui serait supprimé"
    echo "  $0 --days=7                     # Supprimer les utilisateurs > 7 jours"
    echo "  $0 --all                        # Supprimer tous les utilisateurs de test"
    echo "  $0 --python --dry-run           # Utiliser le script Python en mode dry-run"
}

# Variables par défaut
DRY_RUN=""
ALL=""
DAYS=""
USE_PYTHON=""

# Analyser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN="--dry-run"
            shift
            ;;
        --all)
            ALL="--all"
            shift
            ;;
        --days=*)
            DAYS="--days=${1#*=}"
            shift
            ;;
        --python)
            USE_PYTHON="true"
            shift
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        *)
            echo "❌ Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Vérifier que nous sommes dans le bon répertoire
if [[ ! -f "config/database.test.php" ]]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis la racine du projet"
    echo "   (le répertoire contenant config/database.test.php)"
    exit 1
fi

# Choisir le script à utiliser
if [[ "$USE_PYTHON" == "true" ]]; then
    echo "🐍 Utilisation du script Python..."
    
    # Vérifier que Python est disponible
    if ! command -v python3 &> /dev/null; then
        echo "❌ Python3 n'est pas installé"
        exit 1
    fi
    
    # Vérifier que le script Python existe
    if [[ ! -f "tests/cleanup_test_users.py" ]]; then
        echo "❌ Le script Python tests/cleanup_test_users.py n'existe pas"
        exit 1
    fi
    
    # Installer pymysql si nécessaire
    echo "📦 Vérification des dépendances Python..."
    python3 -c "import pymysql" 2>/dev/null || {
        echo "📦 Installation de pymysql..."
        pip3 install pymysql
    }
    
    # Exécuter le script Python
    cd tests
    python3 cleanup_test_users.py
    cd ..
    
else
    echo "🐘 Utilisation du script PHP..."
    
    # Vérifier que PHP est disponible
    if ! command -v php &> /dev/null; then
        echo "❌ PHP n'est pas installé"
        exit 1
    fi
    
    # Vérifier que le script PHP existe
    if [[ ! -f "cleanup_test_data.php" ]]; then
        echo "❌ Le script PHP cleanup_test_data.php n'existe pas"
        exit 1
    fi
    
    # Exécuter le script PHP
    php cleanup_test_data.php $DRY_RUN $ALL $DAYS
fi

echo ""
echo "✅ Script terminé"
