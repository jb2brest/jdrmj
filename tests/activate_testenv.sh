#!/bin/bash
# Script d'activation de l'environnement de test

echo "🎲 Activation de l'environnement de test JDR 4 MJ"
echo "================================================"

# Vérifier si l'environnement virtuel existe
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

# Vérifier l'installation des dépendances
echo "🔍 Vérification des dépendances..."
if ! python -c "import selenium, pytest" 2>/dev/null; then
    echo "📦 Installation des dépendances..."
    python -m pip install -r requirements.txt
fi

echo "✅ Environnement de test activé!"
echo ""
echo "📋 Commandes disponibles:"
echo "   python run_tests.py --help          # Aide"
echo "   python run_tests.py --type smoke    # Tests de fumée"
echo "   make test-smoke                     # Tests de fumée (Makefile)"
echo "   make help                           # Aide Makefile"
echo ""
echo "🌐 URL de test par défaut: http://localhost/jdrmj"
echo "   Modifiez avec: export TEST_BASE_URL='votre_url'"
