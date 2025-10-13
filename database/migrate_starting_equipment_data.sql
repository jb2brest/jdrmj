-- Script de migration des données de starting_equipment vers les nouvelles tables
-- Ce script migre les données existantes de starting_equipment vers starting_equipment_choix et starting_equipment_options

-- 1. Créer les choix d'équipement à partir des données existantes
-- Grouper par src, src_id, no_choix, option_letter pour créer les choix
INSERT INTO starting_equipment_choix (src, src_id, no_choix, option_letter, created_at, updated_at)
SELECT DISTINCT 
    se.src,
    se.src_id,
    se.no_choix,
    se.option_letter,
    se.created_at,
    se.updated_at
FROM starting_equipment se
WHERE se.no_choix IS NOT NULL
ORDER BY se.src, se.src_id, se.no_choix, se.option_letter;

-- 2. Migrer les options d'équipement
-- Chaque ligne de starting_equipment devient une option dans starting_equipment_options
INSERT INTO starting_equipment_options (
    starting_equipment_choix_id, src, src_id, type, type_id, type_filter, nb, created_at, updated_at
)
SELECT 
    sc.id as starting_equipment_choix_id,
    se.src,
    se.src_id,
    se.type,
    se.type_id,
    se.type_filter,
    se.nb,
    se.created_at,
    se.updated_at
FROM starting_equipment se
INNER JOIN starting_equipment_choix sc ON (
    se.src = sc.src 
    AND se.src_id = sc.src_id 
    AND se.no_choix = sc.no_choix
    AND (se.option_letter = sc.option_letter OR (se.option_letter IS NULL AND sc.option_letter IS NULL))
)
WHERE se.no_choix IS NOT NULL;

-- 3. Gérer les équipements sans no_choix (équipements obligatoires simples)
-- Créer des choix pour les équipements qui n'ont pas de no_choix
INSERT INTO starting_equipment_choix (src, src_id, no_choix, option_letter, created_at, updated_at)
SELECT DISTINCT 
    se.src,
    se.src_id,
    0 as no_choix, -- Utiliser 0 pour les équipements sans choix
    NULL as option_letter,
    se.created_at,
    se.updated_at
FROM starting_equipment se
WHERE se.no_choix IS NULL
ORDER BY se.src, se.src_id;

-- 4. Migrer les équipements sans no_choix vers les options
INSERT INTO starting_equipment_options (
    starting_equipment_choix_id, src, src_id, type, type_id, type_filter, nb, created_at, updated_at
)
SELECT 
    sc.id as starting_equipment_choix_id,
    se.src,
    se.src_id,
    se.type,
    se.type_id,
    se.type_filter,
    se.nb,
    se.created_at,
    se.updated_at
FROM starting_equipment se
INNER JOIN starting_equipment_choix sc ON (
    se.src = sc.src 
    AND se.src_id = sc.src_id 
    AND sc.no_choix = 0
)
WHERE se.no_choix IS NULL;

-- 5. Vérification des données migrées
-- Compter les enregistrements dans chaque table
SELECT 'starting_equipment' as table_name, COUNT(*) as count FROM starting_equipment
UNION ALL
SELECT 'starting_equipment_choix' as table_name, COUNT(*) as count FROM starting_equipment_choix
UNION ALL
SELECT 'starting_equipment_options' as table_name, COUNT(*) as count FROM starting_equipment_options;

-- 6. Afficher un échantillon des données migrées
SELECT 
    'Choix créés' as type,
    sc.src,
    sc.src_id,
    sc.no_choix,
    sc.option_letter,
    COUNT(so.id) as nb_options
FROM starting_equipment_choix sc
LEFT JOIN starting_equipment_options so ON sc.id = so.starting_equipment_choix_id
GROUP BY sc.id, sc.src, sc.src_id, sc.no_choix, sc.option_letter
ORDER BY sc.src, sc.src_id, sc.no_choix
LIMIT 10;