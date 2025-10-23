#!/bin/bash
# Script de lancement du menu avancé des tests depuis le répertoire racine

# Variables par défaut
HEADLESS_MODE=false
ENVIRONMENT=""
KEEP_TEST_DATA=false

# Fonction d'aide
show_help() {
    echo "🎲 JDR 4 MJ - Menu Avancé des Tests"
    echo "===================================="
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --headless    Activer le mode headless pour les tests Selenium"
    echo "  -e, --env ENV     Spécifier l'environnement d'exécution (local, staging, production)"
    echo "  -k, --keep        Conserver les données de test après exécution (par défaut: suppression)"
    echo "  --help           Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                # Lancement normal avec interface graphique"
    echo "  $0 -h             # Lancement en mode headless (sans interface)"
    echo "  $0 -e local       # Lancement avec environnement local"
    echo "  $0 -e staging -h  # Lancement en mode headless avec environnement staging"
    echo "  $0 --env production # Lancement avec environnement production"
    echo "  $0 -k             # Conserver les données de test après exécution"
    echo ""
}

# Analyser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--headless)
            HEADLESS_MODE=true
            shift
            ;;
        -e|--env)
            if [[ -n "$2" && "$2" != -* ]]; then
                ENVIRONMENT="$2"
                shift 2
            else
                echo "❌ L'option -e/--env nécessite un argument (local, staging, production)"
                exit 1
            fi
            ;;
        -k|--keep)
            KEEP_TEST_DATA=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            echo "❌ Option inconnue: $1"
            echo "Utilisez --help pour voir les options disponibles"
            exit 1
            ;;
    esac
done

echo "🎲 JDR 4 MJ - Menu Avancé des Tests"
echo "===================================="
if [ "$HEADLESS_MODE" = true ]; then
    echo "🔧 Mode headless activé"
fi
if [ -n "$ENVIRONMENT" ]; then
    echo "🌍 Environnement: $ENVIRONMENT"
fi
if [ "$KEEP_TEST_DATA" = true ]; then
    echo "💾 Conservation des données de test activée"
fi
echo ""

# Vérifier que nous sommes dans le bon répertoire
if [ ! -d "tests" ]; then
    echo "❌ Répertoire 'tests' non trouvé"
    echo "   Assurez-vous d'être dans le répertoire racine du projet JDR MJ"
    exit 1
fi

# Vérifier que Python est installé
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 n'est pas installé"
    echo "   Installez Python 3 pour utiliser le menu des tests"
    exit 1
fi

# Exporter les variables d'environnement
if [ "$HEADLESS_MODE" = true ]; then
    export HEADLESS=true
    echo "🌐 Variable d'environnement HEADLESS=true définie"
fi

if [ -n "$ENVIRONMENT" ]; then
    export TEST_ENVIRONMENT="$ENVIRONMENT"
    echo "🌍 Variable d'environnement TEST_ENVIRONMENT=$ENVIRONMENT définie"
fi

if [ "$KEEP_TEST_DATA" = true ]; then
    export KEEP_TEST_DATA="true"
    echo "💾 Variable d'environnement KEEP_TEST_DATA=true définie"
fi

# Changer vers le répertoire des tests et lancer le menu avancé
cd tests

# Vérifier si le menu avancé existe
if [ -f "advanced_test_menu.py" ]; then
    echo "🚀 Lancement du menu avancé des tests..."
    echo ""
    python3 advanced_test_menu.py
else
    echo "⚠️  Menu avancé non trouvé, utilisation du menu classique..."
    echo ""
    ./launch_menu.sh
fi
