#!/bin/bash
# Script d'installation rapide pour les tests Selenium

echo "🎲 Installation des tests Selenium pour JDR 4 MJ"
echo "================================================"

# Vérifier Python 3
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 n'est pas installé"
    exit 1
fi

echo "✅ Python 3 détecté: $(python3 --version)"

# Vérifier pip
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 n'est pas installé"
    echo "Installez pip3 avec: sudo apt install python3-pip"
    exit 1
fi

echo "✅ pip3 détecté"

# Installer les dépendances
echo "🔧 Installation des dépendances Python..."
python3 -m pip install -r requirements.txt

if [ $? -eq 0 ]; then
    echo "✅ Dépendances installées avec succès"
else
    echo "❌ Erreur lors de l'installation des dépendances"
    exit 1
fi

# Créer les répertoires nécessaires
echo "📁 Création des répertoires..."
mkdir -p reports screenshots logs

# Rendre les scripts exécutables
chmod +x run_tests.py setup_test_environment.py

echo ""
echo "✅ Installation terminée avec succès!"
echo ""
echo "📋 Prochaines étapes:"
echo "   1. Assurez-vous que l'application JDR 4 MJ est accessible"
echo "   2. Lancez les tests: python3 run_tests.py"
echo "   3. Ou utilisez le Makefile: make test-smoke"
echo ""
echo "🔧 Configuration avancée:"
echo "   python3 setup_test_environment.py"
