#!/bin/bash
# Script de démarrage rapide pour les tests

echo "🎲 Tests Selenium pour JDR 4 MJ"
echo "================================"

# Vérifier l'environnement virtuel
if [ ! -d "../testenv" ]; then
    echo "❌ Environnement virtuel non trouvé. Création en cours..."
    cd ..
    python3 -m venv testenv
    cd tests
    echo "✅ Environnement virtuel créé"
fi

# Activer l'environnement virtuel
echo "🔧 Activation de l'environnement virtuel..."
source ../testenv/bin/activate

# Vérifier les dépendances
if ! python -c "import selenium, pytest" 2>/dev/null; then
    echo "📦 Installation des dépendances..."
    python -m pip install -r requirements.txt
fi

# Vérifier ChromeDriver
if ! command -v chromedriver &> /dev/null; then
    echo "❌ ChromeDriver non trouvé. Installation en cours..."
    echo "   Veuillez installer ChromeDriver manuellement:"
    echo "   wget https://storage.googleapis.com/chrome-for-testing-public/140.0.7339.82/linux64/chromedriver-linux64.zip"
    echo "   unzip chromedriver-linux64.zip"
    echo "   sudo mv chromedriver-linux64/chromedriver /usr/local/bin/"
    echo "   sudo chmod +x /usr/local/bin/chromedriver"
    exit 1
fi

echo "✅ Environnement prêt!"
echo ""

# Afficher les options
echo "📋 Commandes disponibles:"
echo "   python test_simple.py                    # Test simple"
echo "   python run_tests.py --type smoke         # Tests de fumée"
echo "   python run_tests.py --type authentication # Tests d'authentification"
echo "   python run_tests.py --help               # Aide complète"
echo "   make help                                # Aide Makefile"
echo ""

# Demander le type de test
echo "Quel type de test voulez-vous lancer ?"
echo "1) Test simple"
echo "2) Tests de fumée"
echo "3) Tests d'authentification"
echo "4) Tous les tests"
echo "5) Aide"
echo ""
read -p "Votre choix (1-5): " choice

case $choice in
    1)
        echo "🧪 Lancement du test simple..."
        python test_simple.py
        ;;
    2)
        echo "🧪 Lancement des tests de fumée..."
        python run_tests.py --type smoke --headless
        ;;
    3)
        echo "🧪 Lancement des tests d'authentification..."
        python run_tests.py --type authentication --headless
        ;;
    4)
        echo "🧪 Lancement de tous les tests..."
        python run_tests.py --type all --headless
        ;;
    5)
        python run_tests.py --help
        ;;
    *)
        echo "❌ Choix invalide"
        exit 1
        ;;
esac
