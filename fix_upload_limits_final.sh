#!/bin/bash
# Script final pour corriger les limites d'upload

echo "Correction finale des limites d'upload"
echo "======================================"

# Fichier php.ini pour Apache
PHP_INI_APACHE="/etc/php/8.3/apache2/php.ini"

echo "Fichier php.ini Apache: $PHP_INI_APACHE"

# Vérifier si le fichier existe
if [ ! -f "$PHP_INI_APACHE" ]; then
    echo "ERREUR: Le fichier $PHP_INI_APACHE n'existe pas"
    echo "Fichiers PHP disponibles:"
    ls -la /etc/php/8.3/
    exit 1
fi

echo "Fichier trouvé. Vérification des paramètres actuels..."

# Afficher les paramètres actuels
echo "Paramètres actuels :"
grep "upload_max_filesize" "$PHP_INI_APACHE"
grep "post_max_size" "$PHP_INI_APACHE"
grep "max_execution_time" "$PHP_INI_APACHE"
grep "memory_limit" "$PHP_INI_APACHE"
grep "max_input_time" "$PHP_INI_APACHE"

echo ""
echo "Modification des paramètres..."

# Modifier upload_max_filesize
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' "$PHP_INI_APACHE"

# Modifier post_max_size
sed -i 's/post_max_size = .*/post_max_size = 12M/' "$PHP_INI_APACHE"

# Modifier max_execution_time
sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI_APACHE"

# Modifier memory_limit
sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI_APACHE"

# Modifier max_input_time
sed -i 's/max_input_time = .*/max_input_time = 300/' "$PHP_INI_APACHE"

echo "Paramètres modifiés. Vérification..."

# Vérifier les modifications
echo "Nouveaux paramètres :"
grep "upload_max_filesize" "$PHP_INI_APACHE"
grep "post_max_size" "$PHP_INI_APACHE"
grep "max_execution_time" "$PHP_INI_APACHE"
grep "memory_limit" "$PHP_INI_APACHE"
grep "max_input_time" "$PHP_INI_APACHE"

echo ""
echo "Modification terminée !"
echo "Vous devez maintenant redémarrer Apache manuellement :"
echo "sudo systemctl restart apache2"
echo ""
echo "Puis vérifier avec :"
echo "php -i | grep upload_max_filesize"
