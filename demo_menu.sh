#!/bin/bash

# Script de démonstration du menu interactif
# Usage: ./demo_menu.sh

clear
echo "🎯 Démonstration du Menu Interactif push.sh"
echo "=========================================="
echo
echo "Le script push.sh propose maintenant un menu interactif avec les options suivantes :"
echo
echo "📋 MENU PRINCIPAL :"
echo "  1. 🧪 Serveur de TEST (développement)"
echo "  2. 🎭 Serveur de STAGING (validation)"  
echo "  3. 🏭 Serveur de PRODUCTION (publication)"
echo "  4. 📋 Afficher l'aide"
echo "  5. ❌ Quitter"
echo
echo "🧪 MENU DES TESTS :"
echo "  1. ✅ Exécuter les tests avant déploiement"
echo "  2. ⚡ Déployer sans exécuter les tests"
echo
echo "💬 SAISIE DU MESSAGE :"
echo "  - Message personnalisé ou message par défaut"
echo
echo "📋 CONFIRMATION :"
echo "  - Affichage des paramètres choisis"
echo "  - Confirmation avant déploiement"
echo
echo "🚀 USAGE :"
echo "  ./push.sh                    # Menu interactif"
echo "  ./push.sh test \"Message\"     # Ligne de commande"
echo "  ./push.sh --help             # Aide"
echo
echo "✨ FONCTIONNALITÉS :"
echo "  ✅ Menu interactif coloré et intuitif"
echo "  ✅ Choix du serveur (test/staging/production)"
echo "  ✅ Option avec ou sans tests"
echo "  ✅ Saisie du message de déploiement"
echo "  ✅ Confirmation avant déploiement"
echo "  ✅ Mode ligne de commande conservé"
echo "  ✅ Aide intégrée"
echo
echo "🎲 Voulez-vous tester le menu interactif ? (o/N)"
read -r response

if [[ "$response" =~ ^[OoYy]$ ]]; then
    echo
    echo "🚀 Lancement du menu interactif..."
    echo "   (Utilisez Ctrl+C pour annuler)"
    echo
    sleep 2
    ./push.sh
else
    echo
    echo "👋 Démonstration terminée !"
    echo "   Utilisez './push.sh' pour accéder au menu interactif"
fi
