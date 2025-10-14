#!/bin/bash
# Script de lancement du menu des tests depuis le répertoire racine

echo "🎲 JDR 4 MJ - Menu des Tests"
echo "============================="
echo ""

# Vérifier que nous sommes dans le bon répertoire
if [ ! -d "tests" ]; then
    echo "❌ Répertoire 'tests' non trouvé"
    echo "   Assurez-vous d'être dans le répertoire racine du projet JDR MJ"
    exit 1
fi

# Changer vers le répertoire des tests et lancer le menu
cd tests
./launch_menu.sh
