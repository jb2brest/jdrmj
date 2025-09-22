#!/bin/bash

# Script pour tester les campagnes avec un utilisateur DM configuré

echo "🚀 Test des campagnes avec configuration DM"
echo "=========================================="

# Activer l'environnement virtuel
source testenv/bin/activate

# Vérifier que l'environnement virtuel est activé
if [[ "$VIRTUAL_ENV" != "" ]]; then
    echo "✅ Environnement virtuel activé: $VIRTUAL_ENV"
else
    echo "❌ Erreur: Environnement virtuel non activé"
    exit 1
fi

echo ""
echo "🔧 Étape 1: Configuration de l'utilisateur DM"
echo "---------------------------------------------"
pytest tests/functional/test_dm_setup.py::TestDMSetup::test_create_dm_user -v -s

echo ""
echo "🔐 Étape 2: Test de connexion DM"
echo "--------------------------------"
pytest tests/functional/test_dm_setup.py::TestDMSetup::test_login_as_dm -v -s

echo ""
echo "🎯 Étape 3: Test d'accès aux campagnes"
echo "-------------------------------------"
pytest tests/functional/test_dm_setup.py::TestDMSetup::test_dm_can_access_campaigns -v -s

echo ""
echo "📊 Étape 4: Diagnostic de la page des campagnes"
echo "-----------------------------------------------"
pytest tests/functional/test_campaign_debug.py::TestCampaignDebug::test_campaign_page_diagnostic -v -s

echo ""
echo "🧪 Étape 5: Tests simples des campagnes"
echo "---------------------------------------"
pytest tests/functional/test_campaign_simple.py -v -s

echo ""
echo "🎮 Étape 6: Test de création de campagne (corrigé)"
echo "--------------------------------------------------"
pytest tests/functional/test_campaign_management.py::TestCampaignManagement::test_campaign_creation -v -s

echo ""
echo "✅ Tous les tests terminés!"
echo "💡 Si des tests échouent, vérifiez les logs ci-dessus pour le diagnostic"
