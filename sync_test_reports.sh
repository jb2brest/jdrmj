#!/bin/bash

# Script pour synchroniser les rapports de tests JSON vers le répertoire web

echo "🔄 Synchronisation des rapports de tests JSON..."

# Répertoires source et destination
SOURCE_DIR="/home/jean/Documents/jdrmj/tests/reports"
DEST_DIR="/var/www/html/jdrmj/tests/reports"

# Créer les répertoires de destination s'ils n'existent pas
mkdir -p "$DEST_DIR/individual"
mkdir -p "$DEST_DIR/aggregated"

# Copier les rapports individuels
if [ -d "$SOURCE_DIR/individual" ]; then
    echo "📁 Copie des rapports individuels..."
    cp "$SOURCE_DIR/individual"/*.json "$DEST_DIR/individual/" 2>/dev/null || echo "⚠️ Aucun rapport individuel trouvé"
    echo "✅ Rapports individuels copiés"
else
    echo "⚠️ Répertoire source des rapports individuels non trouvé"
fi

# Copier les rapports agrégés
if [ -d "$SOURCE_DIR/aggregated" ]; then
    echo "📁 Copie des rapports agrégés..."
    cp "$SOURCE_DIR/aggregated"/*.json "$DEST_DIR/aggregated/" 2>/dev/null || echo "⚠️ Aucun rapport agrégé trouvé"
    echo "✅ Rapports agrégés copiés"
else
    echo "⚠️ Répertoire source des rapports agrégés non trouvé"
fi

# Configurer les permissions
echo "🔐 Configuration des permissions..."
chmod -R 755 "$DEST_DIR"
find "$DEST_DIR" -name "*.json" -exec chmod 644 {} \;

# Vérifier la copie
echo "🔍 Vérification de la copie..."
individual_count=$(find "$DEST_DIR/individual" -name "*.json" 2>/dev/null | wc -l)
aggregated_count=$(find "$DEST_DIR/aggregated" -name "*.json" 2>/dev/null | wc -l)

echo "📊 Résultats:"
echo "  - Rapports individuels: $individual_count"
echo "  - Rapports agrégés: $aggregated_count"

if [ $individual_count -gt 0 ] || [ $aggregated_count -gt 0 ]; then
    echo "✅ Synchronisation réussie !"
    echo "🌐 Les rapports sont maintenant accessibles via l'onglet Tests"
else
    echo "⚠️ Aucun rapport trouvé - vérifiez les répertoires source"
fi

echo "🏁 Synchronisation terminée"
