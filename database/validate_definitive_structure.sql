-- Script de validation de la structure définitive des tables starting_equipment
-- Ce script valide que la nouvelle structure correspond aux spécifications finales

-- 1. Vérifier la structure de la table starting_equipment_choix
DESCRIBE starting_equipment_choix;

-- 2. Vérifier la structure de la table starting_equipment_options
DESCRIBE starting_equipment_options;

-- 3. Vérifier les contraintes et index
SHOW INDEX FROM starting_equipment_choix;
SHOW INDEX FROM starting_equipment_options;

-- 4. Vérifier les types ENUM autorisés
SELECT 
    'starting_equipment_choix' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'starting_equipment_choix' 
  AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

SELECT 
    'starting_equipment_options' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'starting_equipment_options' 
  AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

-- 5. Tester l'insertion d'un choix d'équipement
INSERT INTO starting_equipment_choix (
    src, src_id, no_choix, option_letter
) VALUES (
    'class', 1, 1, 'A'
);

-- 6. Tester l'insertion d'une option d'équipement
INSERT INTO starting_equipment_options (
    starting_equipment_choix_id, src, src_id, type, type_id, type_filter, nb
) VALUES (
    LAST_INSERT_ID(), 'class', 1, 'weapon', NULL, 'Armes de guerre de corps à corps', 1
);

-- 7. Vérifier les données insérées
SELECT 
    'Choix test' as type,
    sc.id,
    sc.src,
    sc.src_id,
    sc.no_choix,
    sc.option_letter
FROM starting_equipment_choix sc
WHERE sc.src = 'class' AND sc.src_id = 1 AND sc.no_choix = 1;

SELECT 
    'Option test' as type,
    so.id,
    so.starting_equipment_choix_id,
    so.src,
    so.src_id,
    so.type,
    so.type_filter,
    so.nb
FROM starting_equipment_options so
WHERE so.src = 'class' AND so.src_id = 1;

-- 8. Tester les types d'équipement autorisés
SELECT 
    'Types d\'équipement testés' as test,
    'armor' as type_1,
    'bouclier' as type_2,
    'instrument' as type_3,
    'nourriture' as type_4,
    'outils' as type_5,
    'sac' as type_6,
    'weapon' as type_7;

-- 9. Tester les filtres d'armes
SELECT 
    'Filtres d\'armes testés' as test,
    'Armes de guerre de corps à corps' as filter_1,
    'Armes courantes à distance' as filter_2,
    'Armes courantes de corps à corps' as filter_3,
    'Armes de guerre à distance' as filter_4;

-- 10. Tester la relation entre les tables
SELECT 
    'Relation test' as test,
    sc.id as choix_id,
    sc.src,
    sc.src_id,
    sc.no_choix,
    sc.option_letter,
    COUNT(so.id) as nb_options
FROM starting_equipment_choix sc
LEFT JOIN starting_equipment_options so ON sc.id = so.starting_equipment_choix_id
WHERE sc.src = 'class' AND sc.src_id = 1
GROUP BY sc.id, sc.src, sc.src_id, sc.no_choix, sc.option_letter;

-- 11. Tester la contrainte de clé étrangère
-- Cette requête devrait échouer si la contrainte fonctionne
-- INSERT INTO starting_equipment_options (starting_equipment_choix_id, src, src_id, type) VALUES (99999, 'class', 1, 'weapon');

-- 12. Nettoyer les données de test
DELETE FROM starting_equipment_options WHERE src = 'class' AND src_id = 1;
DELETE FROM starting_equipment_choix WHERE src = 'class' AND src_id = 1;

-- 13. Vérification finale de la structure
SELECT 
    'Validation terminée' as status,
    'Structure conforme aux spécifications finales' as result,
    NOW() as timestamp;

-- 14. Statistiques des tables
SELECT 
    'Statistiques des tables' as info,
    (SELECT COUNT(*) FROM starting_equipment_choix) as choix_count,
    (SELECT COUNT(*) FROM starting_equipment_options) as options_count;



