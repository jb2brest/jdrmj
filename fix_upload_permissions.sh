#!/bin/bash
# Script pour corriger les permissions des dossiers d'upload
# Date: 2025-10-13

echo "🔧 Correction des permissions des dossiers d'upload"
echo "=================================================="

# Vérifier si on est dans le bon répertoire
if [ ! -d "uploads" ]; then
    echo "❌ Erreur: Le dossier 'uploads' n'existe pas dans le répertoire courant"
    echo "   Veuillez exécuter ce script depuis la racine du projet JDRMJ"
    exit 1
fi

echo "📁 Dossier uploads trouvé"

# Créer les dossiers manquants s'ils n'existent pas
mkdir -p uploads/profiles
mkdir -p uploads/countries
mkdir -p uploads/places
mkdir -p uploads/regions
mkdir -p uploads/worlds
mkdir -p uploads/plans

echo "📂 Dossiers créés/vérifiés"

# Définir les permissions
chmod -R 777 uploads/

echo "🔐 Permissions définies (777) pour tous les dossiers d'upload"

# Vérifier les permissions
echo ""
echo "📋 Vérification des permissions:"
ls -la uploads/

echo ""
echo "✅ Permissions corrigées avec succès!"
echo ""
echo "💡 Note: Les permissions 777 permettent à tous les utilisateurs d'écrire."
echo "   En production, il serait préférable d'utiliser des permissions plus restrictives"
echo "   et de changer le propriétaire vers www-data:"
echo "   sudo chown -R www-data:www-data uploads/"
echo "   sudo chmod -R 755 uploads/"
