-- Ajout de l'équipement de départ du Barbare
-- Classe ID: 1 (Barbare)

-- Groupe 1: Choix d'arme principale
-- (a) une hache à deux mains ou (b) n'importe quelle arme de guerre de corps à corps
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Arme', 22, 'a', 1, 'à_choisir'),  -- Hache à deux mains (ID: 22)
('class', 1, 'Arme', NULL, 'b', 1, 'à_choisir'); -- N'importe quelle arme de guerre de corps à corps (générique)

-- Groupe 2: Choix d'arme secondaire  
-- (a) deux hachettes ou (b) n'importe quelle arme courante
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Arme', 4, 'a', 2, 'à_choisir'),   -- Hachette (ID: 4)
('class', 1, 'Arme', NULL, 'b', 2, 'à_choisir'); -- N'importe quelle arme courante (générique)

-- Groupe 3: Équipement obligatoire
-- un sac d'explorateur et quatre javelines
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES 
('class', 1, 'Sac', 1, NULL, 3, 'obligatoire'),     -- Sac d'explorateur (ID: 1)
('class', 1, 'Arme', 5, NULL, 3, 'obligatoire');    -- Javeline (ID: 5)
