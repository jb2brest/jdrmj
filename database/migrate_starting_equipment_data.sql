-- Script de migration pour convertir les données d'équipement de départ existantes
-- vers la nouvelle table starting_equipment

-- D'abord, créons la table si elle n'existe pas
SOURCE create_starting_equipment_table.sql;

-- Exemples de données pour les classes (à adapter selon vos données réelles)
-- Ces exemples montrent comment structurer les données pour différentes classes

-- Exemple pour le Guerrier (classe ID 1)
-- Choix d'arme: (a) hache à deux mains ou (b) arme de guerre de corps à corps
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('class', 1, 'Armure', NULL, 'a', 1, 'à_choisir'),  -- Armure de cuir clouté OU armure de cuir
('class', 1, 'Bouclier', NULL, 'a', 1, 'à_choisir'), -- Bouclier OU arme à deux mains
('class', 1, 'Arme', NULL, 'a', 2, 'à_choisir'),     -- Hache à deux mains OU arme de guerre de corps à corps
('class', 1, 'Arme', NULL, 'b', 2, 'à_choisir'),     -- Alternative pour le choix d'arme
('class', 1, 'Arme', NULL, NULL, 3, 'obligatoire'),  -- Arbalète de poing et 20 carreaux
('class', 1, 'Accessoire', NULL, NULL, 4, 'obligatoire'); -- Sac d'explorateur

-- Exemple pour le Magicien (classe ID 2)
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('class', 2, 'Arme', NULL, 'a', 1, 'à_choisir'),     -- Bâton OU dague
('class', 2, 'Accessoire', NULL, 'a', 2, 'à_choisir'), -- Sac à composantes OU sac d'érudit
('class', 2, 'Accessoire', NULL, NULL, 3, 'obligatoire'); -- Sac d'explorateur

-- Exemple pour le Clerc (classe ID 3)
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('class', 3, 'Arme', NULL, 'a', 1, 'à_choisir'),     -- Masse de guerre OU masse d'armes
('class', 3, 'Armure', NULL, 'a', 2, 'à_choisir'),   -- Cotte de mailles OU armure de cuir
('class', 3, 'Accessoire', NULL, 'a', 3, 'à_choisir'), -- Bouclier OU arme à deux mains
('class', 3, 'Accessoire', NULL, NULL, 4, 'obligatoire'); -- Sac d'explorateur

-- Exemple pour le Rôdeur (classe ID 4)
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('class', 4, 'Armure', NULL, 'a', 1, 'à_choisir'),   -- Armure d'écailles OU armure de cuir
('class', 4, 'Arme', NULL, 'a', 2, 'à_choisir'),     -- Deux épées courtes OU deux armes courantes de corps à corps
('class', 4, 'Arme', NULL, 'b', 2, 'à_choisir'),     -- Alternative pour le choix d'arme
('class', 4, 'Accessoire', NULL, 'a', 3, 'à_choisir'), -- Sac d'explorateur OU sac d'exploration souterraine
('class', 4, 'Arme', NULL, NULL, 4, 'obligatoire');  -- Arc long et carquois de 20 flèches

-- Exemples pour les backgrounds (à adapter selon vos données)
-- Exemple pour l'Acolyte (background ID 1)
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('background', 1, 'Accessoire', NULL, NULL, 1, 'obligatoire'), -- Symbole sacré
('background', 1, 'Accessoire', NULL, NULL, 2, 'obligatoire'), -- Livre de prières
('background', 1, 'Accessoire', NULL, NULL, 3, 'obligatoire'), -- Encens
('background', 1, 'Accessoire', NULL, NULL, 4, 'obligatoire'), -- Vêtements communs
('background', 1, 'Accessoire', NULL, NULL, 5, 'obligatoire'); -- Bourse avec 15 po

-- Exemple pour l'Artisan (background ID 2)
INSERT INTO starting_equipment (src, src_id, type, type_id, option_indice, groupe_id, type_choix) VALUES
('background', 2, 'Outils', NULL, NULL, 1, 'obligatoire'),     -- Jeu d'outils d'artisan
('background', 2, 'Accessoire', NULL, NULL, 2, 'obligatoire'), -- Lettre de recommandation
('background', 2, 'Accessoire', NULL, NULL, 3, 'obligatoire'), -- Vêtements de voyage
('background', 2, 'Accessoire', NULL, NULL, 4, 'obligatoire'); -- Bourse avec 10 po

-- Note: Les type_id seront remplis plus tard quand on aura les tables d'armes, armures, etc.
-- Pour l'instant, on utilise NULL pour les équipements génériques
