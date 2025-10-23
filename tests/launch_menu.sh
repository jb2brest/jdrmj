#!/bin/bash
# Script de lancement rapide du menu interactif des tests

echo "🎲 Lancement du menu interactif des tests JDR MJ..."
echo ""

# Vérifier que Python est installé
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 n'est pas installé"
    exit 1
fi

# Changer vers le répertoire des tests
cd "$(dirname "$0")"

# Lancer le menu interactif
python3 test_menu.py
