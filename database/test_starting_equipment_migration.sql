-- Script de test pour vérifier la migration des tables starting_equipment
-- Ce script teste la cohérence des données après migration

-- 1. Vérifier que tous les équipements de starting_equipment ont été migrés
SELECT 
    'Équipements non migrés' as test,
    COUNT(*) as count
FROM starting_equipment se
LEFT JOIN starting_equipment_choix sc ON (
    se.src = sc.src 
    AND se.src_id = sc.src_id 
    AND (se.no_choix = sc.no_choix OR (se.no_choix IS NULL AND sc.no_choix = 0))
)
LEFT JOIN starting_equipment_options so ON (
    sc.id = so.id_choix
    AND se.src = so.src
    AND se.src_id = so.src_id
    AND se.type = so.type
    AND se.type_id = so.type_id
    AND se.nb = so.nb
)
WHERE sc.id IS NULL OR so.id IS NULL;

-- 2. Vérifier l'intégrité des relations
SELECT 
    'Options orphelines' as test,
    COUNT(*) as count
FROM starting_equipment_options so
LEFT JOIN starting_equipment_choix sc ON so.id_choix = sc.id
WHERE sc.id IS NULL;

-- 3. Vérifier la cohérence des types de choix
SELECT 
    'Incohérences type_choix' as test,
    COUNT(*) as count
FROM starting_equipment_choix sc
WHERE sc.type_choix NOT IN ('obligatoire', 'à_choisir');

-- 4. Vérifier que les groupes sont cohérents
SELECT 
    'Incohérences groupe_id' as test,
    COUNT(*) as count
FROM starting_equipment_options so
INNER JOIN starting_equipment_choix sc ON so.id_choix = sc.id
WHERE so.groupe_id IS NOT NULL 
  AND sc.groupe_id IS NOT NULL 
  AND so.groupe_id != sc.groupe_id;

-- 5. Statistiques de migration
SELECT 
    'Statistiques de migration' as test,
    (SELECT COUNT(*) FROM starting_equipment) as starting_equipment_count,
    (SELECT COUNT(*) FROM starting_equipment_choix) as choix_count,
    (SELECT COUNT(*) FROM starting_equipment_options) as options_count;

-- 6. Vérifier la répartition par source
SELECT 
    'Répartition par source' as test,
    src,
    COUNT(*) as choix_count,
    (SELECT COUNT(*) FROM starting_equipment_options so WHERE so.src = sc.src) as options_count
FROM starting_equipment_choix sc
GROUP BY src
ORDER BY src;

-- 7. Vérifier la répartition par type de choix
SELECT 
    'Répartition par type de choix' as test,
    type_choix,
    COUNT(*) as count
FROM starting_equipment_choix
GROUP BY type_choix
ORDER BY type_choix;

-- 8. Exemple de données migrées pour une classe
SELECT 
    'Exemple - Classe Barbare' as test,
    sc.no_choix,
    sc.description,
    sc.type_choix,
    COUNT(so.id) as nb_options,
    GROUP_CONCAT(CONCAT(so.nb, 'x ', so.type, IF(so.type_filter, CONCAT(' (', so.type_filter, ')'), '')) SEPARATOR ', ') as options
FROM starting_equipment_choix sc
LEFT JOIN starting_equipment_options so ON sc.id = so.id_choix
INNER JOIN classes c ON sc.src_id = c.id
WHERE sc.src = 'class' AND c.name = 'Barbare'
GROUP BY sc.id, sc.no_choix, sc.description, sc.type_choix
ORDER BY sc.no_choix
LIMIT 5;

-- 9. Vérifier les équipements avec groupe_id
SELECT 
    'Équipements avec groupe_id' as test,
    sc.groupe_id,
    COUNT(DISTINCT sc.id) as choix_count,
    COUNT(so.id) as options_count
FROM starting_equipment_choix sc
LEFT JOIN starting_equipment_options so ON sc.id = so.id_choix
WHERE sc.groupe_id IS NOT NULL
GROUP BY sc.groupe_id
ORDER BY sc.groupe_id;

-- 10. Test de performance - temps de réponse des requêtes
SELECT 
    'Test de performance' as test,
    'Requête simple' as query_type,
    COUNT(*) as result_count,
    NOW() as timestamp
FROM starting_equipment_choix sc
INNER JOIN starting_equipment_options so ON sc.id = so.id_choix
WHERE sc.src = 'class'
LIMIT 100;

