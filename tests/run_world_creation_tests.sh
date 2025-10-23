#!/bin/bash
# Script pour lancer les tests de création des mondes, pays, régions et lieux

# Variables par défaut
HEADLESS_MODE=false
ENVIRONMENT=""
KEEP_TEST_DATA=false

# Fonction d'aide
show_help() {
    echo "🌍 Tests de Création des Mondes, Pays, Régions et Lieux"
    echo "========================================================"
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
    echo "  $0 -k             # Conserver les données de test après exécution"
    echo "  $0 -e staging -h  # Lancement en mode headless avec environnement staging"
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

echo "🌍 Tests de Création des Mondes, Pays, Régions et Lieux"
echo "========================================================"
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
    echo "   Installez Python 3 pour utiliser les tests"
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

# Changer vers le répertoire des tests
cd tests

echo "🧹 Nettoyage préliminaire des données de test existantes..."
python3 cleanup_world_test_data.py

echo ""
echo "🚀 Lancement des tests de création des mondes, pays, régions et lieux..."
echo ""

# Construire la commande pytest
test_files="functional/test_world_creation.py functional/test_country_creation.py functional/test_region_creation.py functional/test_place_creation.py"

# Ajouter les variables d'environnement
env_vars=""
if [ "$HEADLESS_MODE" = true ]; then
    env_vars="$env_vars HEADLESS=true"
fi
if [ -n "$ENVIRONMENT" ]; then
    env_vars="$env_vars TEST_ENVIRONMENT=$ENVIRONMENT"
fi
if [ "$KEEP_TEST_DATA" = true ]; then
    env_vars="$env_vars KEEP_TEST_DATA=true"
fi

# Exécuter les tests
if [ -n "$env_vars" ]; then
    cmd="$env_vars PYTHONPATH=/home/jean/Documents/jdrmj/tests python3 -m pytest $test_files -v -p pytest_json_reporter"
else
    cmd="PYTHONPATH=/home/jean/Documents/jdrmj/tests python3 -m pytest $test_files -v -p pytest_json_reporter"
fi

echo "📋 Commande exécutée: $cmd"
echo ""

# Exécuter la commande
eval $cmd
test_result=$?

echo ""
echo "📊 Résultats des tests:"
if [ $test_result -eq 0 ]; then
    echo "✅ Tous les tests sont passés avec succès!"
else
    echo "❌ Certains tests ont échoué (code de sortie: $test_result)"
fi

# Nettoyage final si demandé
if [ "$KEEP_TEST_DATA" = false ]; then
    echo ""
    echo "🧹 Nettoyage final des données de test..."
    python3 cleanup_world_test_data.py
else
    echo ""
    echo "💾 Conservation des données de test activée - Aucun nettoyage effectué"
fi

echo ""
echo "🏁 Tests terminés!"
exit $test_result
