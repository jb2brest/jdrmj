-- Ajouter le champ pour l'identification des objets
ALTER TABLE place_objects 
ADD COLUMN is_identified BOOLEAN DEFAULT FALSE COMMENT 'Si l\'objet est identifié par les joueurs';
