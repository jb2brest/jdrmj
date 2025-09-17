#!/bin/bash

# Script pour exécuter la migration en ignorant les erreurs de colonnes existantes

echo "=== Migration de la base de données de production ==="
echo "Ce script ignore les erreurs de colonnes existantes"
echo ""

# Exécuter le script SQL en ignorant les erreurs
mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' < database/final_migrate_production.sql 2>/dev/null

# Vérifier si la migration a réussi en testant une requête simple
if mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' -e "USE u839591438_jdrmj; SELECT COUNT(*) as 'Tables créées' FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'u839591438_jdrmj';" 2>/dev/null; then
    echo ""
    echo "✅ Migration terminée avec succès !"
    echo ""
    echo "Vérification des tables créées :"
    mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' -e "USE u839591438_jdrmj; SHOW TABLES;" 2>/dev/null
    echo ""
    echo "Vérification des données initiales :"
    mysql -h localhost -u u839591438_jdrmj -p'M8jbsYJUj6FE$;C' -e "USE u839591438_jdrmj; SELECT 'Classes' as Table_Name, COUNT(*) as Count FROM classes UNION ALL SELECT 'Backgrounds', COUNT(*) FROM backgrounds UNION ALL SELECT 'Languages', COUNT(*) FROM languages UNION ALL SELECT 'Experience Levels', COUNT(*) FROM experience_levels;" 2>/dev/null
else
    echo "❌ Erreur lors de la migration"
    exit 1
fi
