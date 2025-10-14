-- Système homogène de gestion des capacités
-- Création des tables pour stocker toutes les capacités (classe, race, historique)

-- Table des types de capacités
CREATE TABLE IF NOT EXISTS capability_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20)
);

-- Table des capacités
CREATE TABLE IF NOT EXISTS capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type_id INT NOT NULL,
    source_type ENUM('class', 'race', 'background', 'feat', 'item') NOT NULL,
    source_id INT, -- ID de la classe, race, historique, etc.
    level_requirement INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES capability_types(id),
    INDEX idx_source (source_type, source_id),
    INDEX idx_level (level_requirement)
);

-- Table de liaison personnage-capacités
CREATE TABLE IF NOT EXISTS character_capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    capability_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    obtained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (capability_id) REFERENCES capabilities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_capability (character_id, capability_id)
);

-- Insertion des types de capacités (en ignorant les doublons)
INSERT IGNORE INTO capability_types (name, description, icon, color) VALUES
('Combat', 'Capacités liées au combat et aux armes', 'fas fa-sword', 'danger'),
('Magie', 'Capacités magiques et de sorts', 'fas fa-magic', 'purple'),
('Défense', 'Capacités défensives et de protection', 'fas fa-shield-alt', 'primary'),
('Mouvement', 'Capacités de déplacement et de mobilité', 'fas fa-running', 'info'),
('Social', 'Capacités sociales et de communication', 'fas fa-users', 'success'),
('Exploration', 'Capacités d\'exploration et de survie', 'fas fa-compass', 'warning'),
('Spécial', 'Capacités spéciales et uniques', 'fas fa-star', 'secondary'),
('Racial', 'Capacités héritées de la race', 'fas fa-dragon', 'dark'),
('Classe', 'Capacités de classe', 'fas fa-shield-alt', 'primary'),
('Historique', 'Capacités d\'historique', 'fas fa-scroll', 'info');

-- Insertion des capacités de base pour les races (en ignorant les doublons)
INSERT IGNORE INTO capabilities (name, description, type_id, source_type, source_id, level_requirement) VALUES
-- Capacités humaines
('Versatilité humaine', 'Vous gagnez +1 à toutes les caractéristiques.', 8, 'race', 1, 1),

-- Capacités elfes
('Vision dans le noir', 'Vous pouvez voir dans l\'obscurité dans un rayon de 18 mètres comme si c\'était une lumière faible, et dans un rayon de 9 mètres comme si c\'était une obscurité totale. Vous ne pouvez pas discerner les couleurs dans l\'obscurité, seulement les nuances de gris.', 8, 'race', 2, 1),
('Fey Ancestry', 'Vous avez un avantage aux jets de sauvegarde contre le charme, et la magie ne peut pas vous endormir.', 8, 'race', 2, 1),
('Transe', 'Les elfes n\'ont pas besoin de dormir. Au lieu de cela, ils méditent profondément, restant semi-conscients, pendant 4 heures par jour.', 8, 'race', 2, 1),

-- Capacités naines
('Vision dans le noir', 'Vous pouvez voir dans l\'obscurité dans un rayon de 18 mètres comme si c\'était une lumière faible, et dans un rayon de 9 mètres comme si c\'était une obscurité totale. Vous ne pouvez pas discerner les couleurs dans l\'obscurité, seulement les nuances de gris.', 8, 'race', 3, 1),
('Résistance aux poisons', 'Vous avez un avantage aux jets de sauvegarde contre les poisons.', 8, 'race', 3, 1),
('Maîtrise d\'outils', 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.', 8, 'race', 3, 1),

-- Capacités halfelins
('Chanceux', 'Quand vous obtenez un 1 naturel sur un jet d\'attaque, de sauvegarde ou de test de caractéristique, vous pouvez relancer le dé et utiliser le nouveau résultat.', 8, 'race', 4, 1),
('Brave', 'Vous avez un avantage aux jets de sauvegarde contre la peur.', 8, 'race', 4, 1),
('Agilité halfeline', 'Vous pouvez vous déplacer à travers l\'espace d\'une créature d\'une taille plus grande que la vôtre.', 8, 'race', 4, 1),

-- Capacités demi-elfes
('Vision dans le noir', 'Vous pouvez voir dans l\'obscurité dans un rayon de 18 mètres comme si c\'était une lumière faible, et dans un rayon de 9 mètres comme si c\'était une obscurité totale. Vous ne pouvez pas discerner les couleurs dans l\'obscurité, seulement les nuances de gris.', 8, 'race', 5, 1),
('Fey Ancestry', 'Vous avez un avantage aux jets de sauvegarde contre le charme, et la magie ne peut pas vous endormir.', 8, 'race', 5, 1),

-- Capacités demi-orcs
('Vision dans le noir', 'Vous pouvez voir dans l\'obscurité dans un rayon de 18 mètres comme si c\'était une lumière faible, et dans un rayon de 9 mètres comme si c\'était une obscurité totale. Vous ne pouvez pas discerner les couleurs dans l\'obscurité, seulement les nuances de gris.', 8, 'race', 6, 1),
('Menace', 'Vous pouvez utiliser une action bonus à votre tour pour intimider un ennemi à 9 mètres ou moins de vous.', 8, 'race', 6, 1),

-- Capacités dragonborns
('Souffle draconique', 'Vous pouvez utiliser une action pour cracher de l\'énergie destructrice. Votre type de souffle détermine la taille, la forme et le type de dégâts de l\'effet.', 8, 'race', 7, 1),
('Résistance aux dégâts', 'Vous avez une résistance à un type de dégâts déterminé par votre ascendance draconique.', 8, 'race', 7, 1),

-- Capacités tieffelins
('Vision dans le noir', 'Vous pouvez voir dans l\'obscurité dans un rayon de 18 mètres comme si c\'était une lumière faible, et dans un rayon de 9 mètres comme si c\'était une obscurité totale. Vous ne pouvez pas discerner les couleurs dans l\'obscurité, seulement les nuances de gris.', 8, 'race', 8, 1),
('Résistance aux dégâts de feu', 'Vous avez une résistance aux dégâts de feu.', 8, 'race', 8, 1);

-- Insertion des capacités de classe de base (niveau 1) (en ignorant les doublons)
INSERT IGNORE INTO capabilities (name, description, type_id, source_type, source_id, level_requirement) VALUES
-- Capacités de base communes à toutes les classes
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 1, 1), -- Guerrier
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 2, 1), -- Magicien
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 3, 1), -- Clerc
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 4, 1), -- Rôdeur
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 5, 1), -- Paladin
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 6, 1), -- Barbare
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 7, 1), -- Barde
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 8, 1), -- Druide
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 9, 1), -- Ensorceleur
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 10, 1), -- Roublard
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 11, 1), -- Moine
('Maîtrise d\'armures', 'Vous maîtrisez les armures légères.', 3, 'class', 12, 1), -- Occultiste

-- Capacités spécifiques par classe
-- Barbare
('Rage', 'En combat, vous pouvez entrer dans un état de rage. Pendant votre rage, vous obtenez les avantages suivants si vous ne portez pas d\'armure lourde : +2 aux dégâts de mêlée avec les armes de Force, résistance aux dégâts contondants, perforants et tranchants, et avantage aux jets de sauvegarde de Force.', 1, 'class', 6, 1),
('Défense sans armure', 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + votre modificateur de Dextérité + votre modificateur de Constitution.', 3, 'class', 6, 1),

-- Guerrier
('Style de combat', 'Vous adoptez un style de combat particulier comme spécialité. Vous ne pouvez pas prendre un style de combat plus d\'une fois, même si vous obtenez plus tard l\'occasion de le faire à nouveau.', 1, 'class', 1, 1),
('Second souffle', 'Vous avez une réserve limitée d\'endurance sur laquelle vous pouvez compter pour vous protéger. À votre tour, vous pouvez utiliser une action bonus pour récupérer des points de vie égaux à 1d10 + votre niveau de guerrier.', 3, 'class', 1, 1),

-- Magicien
('Sorts', 'Vous avez appris à utiliser la magie. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de magicien.', 2, 'class', 2, 1),
('Récupération de sorts', 'Vous avez appris à récupérer une partie de votre énergie magique en étudiant votre grimoire. Une fois par jour quand vous terminez un repos court, vous pouvez choisir des emplacements de sorts épuisés pour les récupérer.', 2, 'class', 2, 1),

-- Clerc
('Sorts', 'Vous avez appris à utiliser la magie divine. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de clerc.', 2, 'class', 3, 1),
('Domaine divin', 'Vous choisissez un domaine divin qui reflète votre dévotion à votre divinité. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 2, 6, 8 et 17.', 2, 'class', 3, 1),

-- Rôdeur
('Ennemis favoris', 'Vous avez une expérience significative dans l\'étude, le suivi, la chasse et même la communication avec un certain type d\'ennemi.', 6, 'class', 4, 1),
('Terrain de prédilection', 'Vous êtes particulièrement familier avec un type d\'environnement naturel et êtes compétent pour voyager et survivre dans de telles régions.', 6, 'class', 4, 1),

-- Paladin
('Sorts', 'Vous avez appris à utiliser la magie divine. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de paladin.', 2, 'class', 5, 1),
('Serment divin', 'Quand vous atteignez le niveau 3, vous prêtez serment qui vous lie pour toujours. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 7, 15 et 20.', 2, 'class', 5, 1),

-- Barde
('Inspiration bardique', 'Vous pouvez inspirer les autres par des mots ou de la musique. Pour ce faire, vous utilisez une action bonus à votre tour pour choisir une créature autre que vous à 18 mètres ou moins de vous qui peut vous entendre.', 5, 'class', 7, 1),
('Jack de tous les métiers', 'Vous pouvez ajouter la moitié de votre bonus de maîtrise, arrondi au supérieur, à tout test de caractéristique que vous effectuez et qui n\'inclut déjà pas votre bonus de maîtrise.', 7, 'class', 7, 2),

-- Druide
('Sorts', 'Vous avez appris à utiliser la magie. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de druide.', 2, 'class', 8, 1),
('Cercle druidique', 'Vous choisissez d\'identifier avec un cercle de druides. Votre choix vous accorde des capacités au niveau 2, puis aux niveaux 3, 6, 10 et 14.', 2, 'class', 8, 2),

-- Ensorceleur
('Sorts', 'Vous avez appris à utiliser la magie. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts d\'ensorceleur.', 2, 'class', 9, 1),
('Origine magique', 'Vous choisissez une origine magique qui explique la source de vos pouvoirs magiques innés. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 6, 14 et 18.', 2, 'class', 9, 1),

-- Roublard
('Expertise', 'Vous choisissez deux de vos maîtrises de compétences, ou une de vos maîtrises de compétences et votre maîtrise de voleur. Votre bonus de maîtrise pour les deux capacités choisies est doublé pour tous les tests de caractéristique que vous effectuez avec elles.', 7, 'class', 10, 1),
('Attaque sournoise', 'Vous savez comment frapper subtilement et exploiter l\'ouverture d\'un ennemi. Une fois par tour, vous pouvez infliger 1d6 dégâts supplémentaires à une créature que vous frappez avec une attaque si vous avez un avantage sur le jet d\'attaque.', 1, 'class', 10, 1),

-- Moine
('Arts martiaux', 'Vous maîtrisez les arts martiaux, vous permettant de vous battre efficacement sans armes ni armure.', 1, 'class', 11, 1),
('Défense sans armure', 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + votre modificateur de Dextérité + votre modificateur de Sagesse.', 3, 'class', 11, 1),

-- Occultiste
('Sorts', 'Vous avez appris à utiliser la magie. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts d\'occultiste.', 2, 'class', 12, 1),
('Pacte occulte', 'Votre recherche de connaissances vous a mené à faire un pacte avec un être puissant. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 2, 3, 7, 10, 11, 12, 15, 16 et 20.', 2, 'class', 12, 1);
