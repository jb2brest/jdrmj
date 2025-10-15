#!/bin/bash
# Script de lancement du menu avancé des tests depuis le répertoire racine

echo "🎲 JDR 4 MJ - Menu Avancé des Tests"
echo "===================================="
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
