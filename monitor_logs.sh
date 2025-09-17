#!/bin/bash

echo "=== Surveillance des logs de sauvegarde des positions ==="
echo "Appuyez sur Ctrl+C pour arrêter"
echo ""

# Surveiller les logs d'erreur Apache
tail -f /var/log/apache2/error.log | grep -E "(UPDATE_TOKEN_POSITION|SAVE TOKEN POSITION|Token|Position)"
