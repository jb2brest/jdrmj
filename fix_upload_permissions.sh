#!/bin/bash
# Script pour corriger les permissions des dossiers d'upload

UPLOAD_DIR="/var/www/html/jdrmj/uploads"
PROFILE_PHOTOS_DIR="$UPLOAD_DIR/profile_photos"

echo "=========================================="
echo "Correction des permissions des dossiers d'upload"
echo "=========================================="
echo ""

# Créer les dossiers s'ils n'existent pas
echo "Création des dossiers..."
mkdir -p "$PROFILE_PHOTOS_DIR"
echo "✓ Dossiers créés"

# Définir le groupe www-data et les permissions appropriées
if [ "$(id -u)" -eq 0 ]; then
    # Si exécuté en root/sudo - solution sécurisée recommandée
    echo ""
    echo "Configuration sécurisée (propriétaire: jean, groupe: www-data)..."
    chown -R jean:www-data "$UPLOAD_DIR"
    chmod -R 775 "$UPLOAD_DIR"
    echo "✓ Permissions mises à jour avec succès (sécurisé)"
    echo "  Propriétaire: jean:www-data"
    echo "  Permissions: 775 (rwxrwxr-x)"
elif groups | grep -q "\bwww-data\b" || [ "$(stat -c '%G' "$UPLOAD_DIR" 2>/dev/null)" = "$(whoami)" ]; then
    # Si l'utilisateur actuel est dans le groupe www-data ou propriétaire
    echo ""
    echo "Configuration avec groupe www-data..."
    chgrp -R www-data "$UPLOAD_DIR" 2>/dev/null || chmod -R 775 "$UPLOAD_DIR"
    echo "✓ Permissions configurées"
else
    # Si exécuté sans sudo et sans privilèges - solution temporaire
    echo ""
    echo "Configuration avec permissions ouvertes (777 - moins sécurisé)..."
    chmod -R 777 "$UPLOAD_DIR"
    echo "✓ Permissions mises à jour (777)"
    echo ""
    echo "⚠ ATTENTION: Les permissions 777 permettent à tous les utilisateurs d'écrire."
    echo "  Pour une meilleure sécurité, exécutez ce script avec sudo:"
    echo "  sudo ./fix_upload_permissions.sh"
fi

echo ""
echo "=========================================="
echo "Vérification des permissions:"
echo "=========================================="
echo ""
echo "Dossier uploads:"
ls -ld "$UPLOAD_DIR" | awk '{print "  Permissions:", $1, "| Propriétaire:", $3":"$4, "| Dossier:", $9}'

echo ""
echo "Dossier profile_photos:"
if [ -d "$PROFILE_PHOTOS_DIR" ]; then
    ls -ld "$PROFILE_PHOTOS_DIR" | awk '{print "  Permissions:", $1, "| Propriétaire:", $3":"$4, "| Dossier:", $9}'
else
    echo "  ⚠ Dossier non trouvé"
fi

echo ""
echo "Test d'écriture (simulation)..."
if [ -w "$PROFILE_PHOTOS_DIR" ]; then
    echo "  ✓ Le dossier est accessible en écriture"
else
    echo "  ✗ Le dossier n'est PAS accessible en écriture"
    echo "    Exécutez avec sudo pour corriger: sudo ./fix_upload_permissions.sh"
fi

echo ""
echo "=========================================="

