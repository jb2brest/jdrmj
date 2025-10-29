-- Ajouter les colonnes pour les caractéristiques recommandées dans la table classes
ALTER TABLE classes 
ADD COLUMN recommended_strength INT DEFAULT 0,
ADD COLUMN recommended_dexterity INT DEFAULT 0,
ADD COLUMN recommended_constitution INT DEFAULT 0,
ADD COLUMN recommended_intelligence INT DEFAULT 0,
ADD COLUMN recommended_wisdom INT DEFAULT 0,
ADD COLUMN recommended_charisma INT DEFAULT 0;

-- Mettre à jour les valeurs recommandées pour chaque classe
UPDATE classes SET 
    recommended_strength = 15,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 8,
    recommended_wisdom = 12,
    recommended_charisma = 10
WHERE name = 'Barbare';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 14,
    recommended_constitution = 13,
    recommended_intelligence = 12,
    recommended_wisdom = 10,
    recommended_charisma = 15
WHERE name = 'Barde';

UPDATE classes SET 
    recommended_strength = 13,
    recommended_dexterity = 12,
    recommended_constitution = 14,
    recommended_intelligence = 8,
    recommended_wisdom = 15,
    recommended_charisma = 10
WHERE name = 'Clerc';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 12,
    recommended_wisdom = 15,
    recommended_charisma = 10
WHERE name = 'Druide';

UPDATE classes SET 
    recommended_strength = 15,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 12,
    recommended_wisdom = 10,
    recommended_charisma = 8
WHERE name = 'Guerrier';

UPDATE classes SET 
    recommended_strength = 12,
    recommended_dexterity = 15,
    recommended_constitution = 13,
    recommended_intelligence = 10,
    recommended_wisdom = 14,
    recommended_charisma = 8
WHERE name = 'Moine';

UPDATE classes SET 
    recommended_strength = 15,
    recommended_dexterity = 12,
    recommended_constitution = 13,
    recommended_intelligence = 8,
    recommended_wisdom = 10,
    recommended_charisma = 14
WHERE name = 'Paladin';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 15,
    recommended_wisdom = 12,
    recommended_charisma = 10
WHERE name = 'Magicien';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 12,
    recommended_wisdom = 10,
    recommended_charisma = 15
WHERE name = 'Ensorceleur';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 13,
    recommended_constitution = 14,
    recommended_intelligence = 12,
    recommended_wisdom = 10,
    recommended_charisma = 15
WHERE name = 'Occultiste';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 15,
    recommended_constitution = 13,
    recommended_intelligence = 14,
    recommended_wisdom = 10,
    recommended_charisma = 12
WHERE name = 'Roublard';

UPDATE classes SET 
    recommended_strength = 8,
    recommended_dexterity = 15,
    recommended_constitution = 13,
    recommended_intelligence = 12,
    recommended_wisdom = 14,
    recommended_charisma = 10
WHERE name = 'Rôdeur';
