-- Ajouter les colonnes armor_proficiencies, weapon_proficiencies et tool_proficiencies à la table classes
-- Note: Vérifier d'abord si les colonnes existent avant de les ajouter

-- Ajouter armor_proficiencies
ALTER TABLE classes ADD COLUMN armor_proficiencies TEXT DEFAULT NULL;

-- Ajouter weapon_proficiencies  
ALTER TABLE classes ADD COLUMN weapon_proficiencies TEXT DEFAULT NULL;

-- Ajouter tool_proficiencies
ALTER TABLE classes ADD COLUMN tool_proficiencies TEXT DEFAULT NULL;

-- Mettre à jour les classes existantes avec leurs compétences d'armure
UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Armure lourde", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Guerrier';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère"]',
    weapon_proficiencies = '["Armes simples", "Armes à distance"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Magicien';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Clerc';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Rôdeur';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère"]',
    weapon_proficiencies = '["Armes simples", "Armes à distance", "Armes de mêlée"]',
    tool_proficiencies = '["Outils de voleur", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Roublard';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Armure lourde", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Paladin';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère"]',
    weapon_proficiencies = '["Armes simples", "Armes à distance"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Ensorceleur';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère"]',
    weapon_proficiencies = '["Armes simples", "Armes à distance", "Armes de mêlée"]',
    tool_proficiencies = '["Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Barde';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Druide';

UPDATE classes SET 
    armor_proficiencies = '[]',
    weapon_proficiencies = '["Armes simples", "Armes à distance", "Armes de mêlée"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Moine';

UPDATE classes SET 
    armor_proficiencies = '["Armure légère", "Armure intermédiaire", "Bouclier"]',
    weapon_proficiencies = '["Armes courantes", "Armes de guerre", "Armes simples", "Armes à distance", "Armes de mêlée", "Armes d\'hast", "Armes de lancer"]',
    tool_proficiencies = '["Outils d\'artisan", "Instruments de musique", "Jeux", "Véhicules"]'
WHERE name = 'Artificier';
