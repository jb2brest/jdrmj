-- Script de migration complète des capacités vers le système homogène
-- Ce script migre toutes les capacités depuis les fonctions PHP vers la base de données

-- Capacités de Barbare (niveaux 1-20)
INSERT INTO capabilities (name, description, type_id, source_type, source_id, level_requirement) VALUES
-- Niveau 1
('Rage', 'En combat, vous pouvez entrer dans un état de rage. Pendant votre rage, vous obtenez les avantages suivants si vous ne portez pas d\'armure lourde : +2 aux dégâts de mêlée avec les armes de Force, résistance aux dégâts contondants, perforants et tranchants, et avantage aux jets de sauvegarde de Force.', 1, 'class', 6, 1),
('Défense sans armure', 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + votre modificateur de Dextérité + votre modificateur de Constitution.', 3, 'class', 6, 1),

-- Niveau 2
('Sens du danger', 'Vous avez un avantage aux jets de sauvegarde de Dextérité contre les effets que vous pouvez voir, comme les pièges et les sorts. Pour bénéficier de cet avantage, vous ne devez pas être aveuglé, assourdi ou neutralisé.', 3, 'class', 6, 2),
('Attaque supplémentaire', 'Vous pouvez attaquer deux fois, au lieu d\'une, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.', 1, 'class', 6, 2),

-- Niveau 3
('Voie primitive', 'Vous choisissez une voie primitive qui reflète la nature de votre rage. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 6, 10 et 14.', 7, 'class', 6, 3),

-- Niveau 4
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 6, 4),

-- Niveau 5
('Attaque supplémentaire', 'Vous pouvez attaquer deux fois, au lieu d\'une, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.', 1, 'class', 6, 5),

-- Niveau 7
('Instinct sauvage', 'Vous avez un avantage aux jets d\'initiative.', 7, 'class', 6, 7),

-- Niveau 8
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 6, 8),

-- Niveau 9
('Attaque brutale', 'Vous pouvez relancer un dé de dégâts d\'arme une fois par tour quand vous infligez des dégâts avec une attaque de mêlée.', 1, 'class', 6, 9),

-- Niveau 11
('Rage implacable', 'Votre rage peut vous maintenir en vie malgré des blessures mortelles. Si vous tombez à 0 point de vie pendant que vous êtes enragé et que vous ne mourez pas immédiatement, vous pouvez faire un jet de sauvegarde de Constitution avec un DD de 10 + le nombre de fois que vous avez utilisé cette aptitude depuis votre dernier repos long. En cas de succès, vous tombez à 1 point de vie à la place.', 3, 'class', 6, 11),

-- Niveau 12
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 6, 12),

-- Niveau 15
('Rage persistante', 'Votre rage est si féroce qu\'elle se termine prématurément seulement si vous tombez inconscient ou si vous choisissez de la terminer.', 7, 'class', 6, 15),

-- Niveau 16
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 6, 16),

-- Niveau 18
('Force indomptable', 'Votre force physique devient légendaire. Votre score de Force augmente de 4. Votre maximum pour ce score est maintenant 24.', 7, 'class', 6, 18),

-- Niveau 19
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 6, 19),

-- Niveau 20
('Champion primordial', 'Vous incarniez la puissance de la nature. Votre score de Force et de Constitution augmente de 4. Votre maximum pour ces scores est maintenant 24.', 7, 'class', 6, 20);

-- Capacités de Guerrier (niveaux 1-20)
INSERT INTO capabilities (name, description, type_id, source_type, source_id, level_requirement) VALUES
-- Niveau 1
('Style de combat', 'Vous adoptez un style de combat particulier comme spécialité. Vous ne pouvez pas prendre un style de combat plus d\'une fois, même si vous obtenez plus tard l\'occasion de le faire à nouveau.', 1, 'class', 1, 1),
('Second souffle', 'Vous avez une réserve limitée d\'endurance sur laquelle vous pouvez compter pour vous protéger. À votre tour, vous pouvez utiliser une action bonus pour récupérer des points de vie égaux à 1d10 + votre niveau de guerrier.', 3, 'class', 1, 1),

-- Niveau 2
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 2),

-- Niveau 3
('Archétype martial', 'Vous choisissez un archétype martial qui reflète votre style de combat. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 7, 10, 15 et 18.', 1, 'class', 1, 3),

-- Niveau 4
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 4),

-- Niveau 5
('Attaque supplémentaire', 'Vous pouvez attaquer deux fois, au lieu d\'une, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.', 1, 'class', 1, 5),

-- Niveau 6
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 6),

-- Niveau 7
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 7),

-- Niveau 8
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 8),

-- Niveau 9
('Attaque supplémentaire', 'Vous pouvez attaquer trois fois, au lieu de deux, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.', 1, 'class', 1, 9),

-- Niveau 10
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 10),

-- Niveau 11
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 11),

-- Niveau 12
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 12),

-- Niveau 13
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 13),

-- Niveau 14
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 14),

-- Niveau 15
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 15),

-- Niveau 16
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 16),

-- Niveau 17
('Action supplémentaire', 'Vous pouvez utiliser une action supplémentaire à votre tour. Vous ne pouvez pas utiliser plus d\'une action supplémentaire par tour.', 1, 'class', 1, 17),

-- Niveau 18
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 18),

-- Niveau 19
('Amélioration de caractéristique', 'Vous pouvez augmenter un score de caractéristique de votre choix de 2, ou vous pouvez augmenter deux scores de caractéristique de votre choix de 1. Comme d\'habitude, vous ne pouvez pas augmenter un score de caractéristique au-dessus de 20 en utilisant cette aptitude.', 7, 'class', 1, 19),

-- Niveau 20
('Attaque supplémentaire', 'Vous pouvez attaquer quatre fois, au lieu de trois, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.', 1, 'class', 1, 20);
