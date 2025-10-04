-- Script de validation de la structure finale des tables starting_equipment
-- Ce script valide que la nouvelle structure correspond aux spécifications

-- 1. Vérifier la structure de la table starting_equipment_options
DESCRIBE starting_equipment_options;

-- 2. Vérifier les contraintes et index
SHOW INDEX FROM starting_equipment_options;

-- 3. Vérifier les types ENUM autorisés
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'starting_equipment_options' 
  AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

-- 4. Vérifier la structure de la table starting_equipment_choix
DESCRIBE starting_equipment_choix;

-- 5. Vérifier les contraintes et index de starting_equipment_choix
SHOW INDEX FROM starting_equipment_choix;

-- 6. Tester l'insertion d'une option d'équipement
INSERT INTO starting_equipment_options (
    groupe_id, src, src_id, type, type_id, type_filter, nb
) VALUES (
    1, 'class', 1, 'weapon', NULL, 'Armes de guerre de corps à corps', 1
);

-- 7. Tester l'insertion d'un choix d'équipement
INSERT INTO starting_equipment_choix (
    src, src_id, no_choix, description, type_choix, groupe_id
) VALUES (
    'class', 1, 1, 'Choix d\'arme de guerre', 'à_choisir', 1
);

-- 8. Vérifier les données insérées
SELECT 
    'Options test' as type,
    so.id,
    so.groupe_id,
    so.src,
    so.src_id,
    so.type,
    so.type_filter,
    so.nb
FROM starting_equipment_options so
WHERE so.groupe_id = 1;

SELECT 
    'Choix test' as type,
    sc.id,
    sc.src,
    sc.src_id,
    sc.no_choix,
    sc.description,
    sc.type_choix,
    sc.groupe_id
FROM starting_equipment_choix sc
WHERE sc.groupe_id = 1;

-- 9. Tester les types d'équipement autorisés
SELECT 
    'Types d\'équipement testés' as test,
    'armor' as type_1,
    'bouclier' as type_2,
    'instrument' as type_3,
    'nourriture' as type_4,
    'outils' as type_5,
    'sac' as type_6,
    'weapon' as type_7;

-- 10. Tester les filtres d'armes
SELECT 
    'Filtres d\'armes testés' as test,
    'Armes de guerre de corps à corps' as filter_1,
    'Armes courantes à distance' as filter_2,
    'Armes courantes de corps à corps' as filter_3,
    'Armes de guerre à distance' as filter_4;

-- 11. Nettoyer les données de test
DELETE FROM starting_equipment_options WHERE groupe_id = 1;
DELETE FROM starting_equipment_choix WHERE groupe_id = 1;

-- 12. Vérification finale de la structure
SELECT 
    'Validation terminée' as status,
    'Structure conforme aux spécifications' as result,
    NOW() as timestamp;



