#!/bin/bash

# Script de nettoyage automatique des données de test
# À exécuter via cron pour nettoyer automatiquement les données anciennes

# Configuration
PROJECT_DIR="/home/jean/Documents/jdrmj"
LOG_FILE="$PROJECT_DIR/cleanup.log"
DAYS_OLD=7  # Nettoyer les données > 7 jours

# Fonction de logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Changer vers le répertoire du projet
cd "$PROJECT_DIR"

log "🧹 Début du nettoyage automatique des données de test"

# Exécuter le nettoyage
if ./cleanup_tests.sh --python --days=$DAYS_OLD; then
    log "✅ Nettoyage automatique terminé avec succès"
else
    log "❌ Erreur lors du nettoyage automatique"
    exit 1
fi

log "🏁 Fin du nettoyage automatique"
