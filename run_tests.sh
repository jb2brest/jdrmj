#!/bin/bash

# Script pour exécuter les tests Selenium avec l'environnement virtuel

# Activer l'environnement virtuel
source testenv/bin/activate

# Vérifier que l'environnement virtuel est activé
if [[ "$VIRTUAL_ENV" != "" ]]; then
    echo "✅ Environnement virtuel activé: $VIRTUAL_ENV"
else
    echo "❌ Erreur: Environnement virtuel non activé"
    exit 1
fi

# Vérifier que les dépendances sont installées
echo "🔍 Vérification des dépendances..."
python -c "import selenium, pytest, webdriver_manager; print('✅ Toutes les dépendances sont installées')" || {
    echo "❌ Dépendances manquantes. Installation..."
    pip install -r tests/requirements.txt
}

# Fonction pour exécuter les tests
run_test() {
    local test_path="$1"
    local description="$2"
    
    echo ""
    echo "🧪 $description"
    echo "📁 Test: $test_path"
    echo "----------------------------------------"
    
    pytest "$test_path" -v -s --tb=short
}

# Menu interactif
echo ""
echo "🚀 Tests Selenium - JDR D&D"
echo "============================"
echo ""
echo "Choisissez un test à exécuter:"
echo "1) Test de diagnostic des campagnes"
echo "2) Tests simples des campagnes"
echo "3) Test de création de campagne (corrigé)"
echo "4) Tests de création de personnages par étapes"
echo "5) Tous les tests de campagnes"
echo "6) Tous les tests de personnages"
echo "7) Tous les tests"
echo "8) Test spécifique (saisir le chemin)"
echo "0) Quitter"
echo ""

read -p "Votre choix (0-8): " choice

case $choice in
    1)
        run_test "tests/functional/test_campaign_debug.py::TestCampaignDebug::test_campaign_page_diagnostic" "Diagnostic de la page des campagnes"
        ;;
    2)
        run_test "tests/functional/test_campaign_simple.py" "Tests simples des campagnes"
        ;;
    3)
        run_test "tests/functional/test_campaign_management.py::TestCampaignManagement::test_campaign_creation" "Test de création de campagne (corrigé)"
        ;;
    4)
        run_test "tests/functional/test_character_creation_steps.py" "Tests de création de personnages par étapes"
        ;;
    5)
        run_test "tests/functional/test_campaign_*.py" "Tous les tests de campagnes"
        ;;
    6)
        run_test "tests/functional/test_character_*.py" "Tous les tests de personnages"
        ;;
    7)
        run_test "tests/functional/" "Tous les tests fonctionnels"
        ;;
    8)
        read -p "Chemin du test: " test_path
        run_test "$test_path" "Test spécifique"
        ;;
    0)
        echo "👋 Au revoir!"
        exit 0
        ;;
    *)
        echo "❌ Choix invalide"
        exit 1
        ;;
esac

echo ""
echo "✅ Test terminé!"
echo "💡 Pour plus d'options, relancez le script: ./run_tests.sh"
