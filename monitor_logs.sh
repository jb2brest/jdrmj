#!/bin/bash
# Script pour surveiller les logs en temps réel

echo "Surveillance des logs pour l'identification des PNJ"
echo "=================================================="
echo "Appuyez sur Ctrl+C pour arrêter"
echo ""

# Surveiller les logs avec filtrage pour les messages de debug
tail -f /var/log/apache2/error.log | grep -E "(DEBUG toggleNpcIdentification|DEBUG view_place\.php|toggle_npc_identification)"