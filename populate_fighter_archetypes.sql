-- Peupler la table des archétypes martiaux
INSERT INTO fighter_archetypes (name, description, level_3_feature, level_7_feature, level_10_feature, level_15_feature, level_18_feature) VALUES
(
    'Champion',
    'L\'archétype Champion se concentre sur la maîtrise des armes et la précision au combat. Les guerriers de cet archétype sont des maîtres du combat et peuvent infliger des coups critiques dévastateurs.',
    'Amélioration critique : Vos attaques d\'arme marquent un coup critique sur un 19 ou un 20. De plus, vous gagnez un style de combat supplémentaire.',
    'Remarquable athlète : Vous pouvez ajouter la moitié de votre bonus de maîtrise (arrondi vers le bas) à tout test de Force, Dextérité ou Constitution que vous faites et qui n\'utilise pas déjà votre bonus de maîtrise.',
    'Style de combat supplémentaire : Vous gagnez un style de combat supplémentaire. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.',
    'Amélioration critique supérieure : Vos attaques d\'arme marquent un coup critique sur un 18, 19 ou 20.',
    'Survivant : Vous atteignez la perfection physique qui vous permet de survivre aux blessures les plus graves. Vous récupérez 5 + votre modificateur de Constitution (minimum 1) points de vie au début de votre tour si vous n\'avez pas plus de la moitié de vos points de vie maximum.'
),
(
    'Maître d\'armes',
    'L\'archétype Maître d\'armes se concentre sur la maîtrise de toutes les armes et armures. Les guerriers de cet archétype sont des experts en équipement et peuvent utiliser n\'importe quelle arme avec efficacité.',
    'Maîtrise d\'armes : Vous gagnez la maîtrise de toutes les armes et armures. De plus, vous gagnez un style de combat supplémentaire.',
    'Amélioration d\'arme : Vous pouvez utiliser votre action pour améliorer une arme que vous tenez. L\'arme gagne un bonus de +1 aux jets d\'attaque et de dégâts pendant 1 minute.',
    'Style de combat supplémentaire : Vous gagnez un style de combat supplémentaire. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.',
    'Maîtrise d\'arme supérieure : Vous pouvez utiliser votre action pour améliorer une arme que vous tenez. L\'arme gagne un bonus de +2 aux jets d\'attaque et de dégâts pendant 1 minute.',
    'Maître d\'arme légendaire : Vous pouvez utiliser votre action pour améliorer une arme que vous tenez. L\'arme gagne un bonus de +3 aux jets d\'attaque et de dégâts pendant 1 minute.'
),
(
    'École de bataille',
    'L\'archétype École de bataille se concentre sur la tactique et la stratégie au combat. Les guerriers de cet archétype sont des tacticiens et peuvent utiliser des manœuvres spéciales.',
    'Manœuvres de combat : Vous apprenez trois manœuvres de votre choix. Vous gagnez un dé de manœuvre (d8). Vous récupérez tous vos dés de manœuvre dépensés quand vous terminez un repos court ou long.',
    'Connaissance de la guerre : Vous gagnez la maîtrise de deux compétences de votre choix parmi : Animal Handling, History, Insight, Intimidation, Perception et Survival.',
    'Manœuvres supplémentaires : Vous apprenez deux manœuvres supplémentaires de votre choix. Vous gagnez également un dé de manœuvre supplémentaire.',
    'Amélioration de manœuvre : Vos dés de manœuvre deviennent des d10. Au niveau 18, ils deviennent des d12.',
    'Maître de guerre : Vous pouvez utiliser votre action pour donner l\'ordre d\'attaquer à un allié dans un rayon de 9 mètres. L\'allié peut immédiatement faire une attaque d\'arme en réaction.'
),
(
    'École de cavalerie',
    'L\'archétype École de cavalerie se concentre sur le combat monté et la mobilité. Les guerriers de cet archétype sont des cavaliers experts et peuvent utiliser des tactiques de cavalerie.',
    'Maîtrise de la cavalerie : Vous gagnez la maîtrise des animaux de guerre. De plus, vous gagnez un style de combat supplémentaire.',
    'Tactiques de cavalerie : Vous pouvez utiliser votre action pour donner l\'ordre de charger à votre monture. Votre monture peut se déplacer jusqu\'à sa vitesse et faire une attaque d\'arme.',
    'Style de combat supplémentaire : Vous gagnez un style de combat supplémentaire. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.',
    'Maîtrise de la cavalerie supérieure : Vous pouvez utiliser votre action pour donner l\'ordre de charger à votre monture. Votre monture peut se déplacer jusqu\'à deux fois sa vitesse et faire une attaque d\'arme.',
    'Cavalier légendaire : Vous pouvez utiliser votre action pour donner l\'ordre de charger à votre monture. Votre monture peut se déplacer jusqu\'à trois fois sa vitesse et faire une attaque d\'arme.'
),
(
    'École de défense',
    'L\'archétype École de défense se concentre sur la protection et la défense. Les guerriers de cet archétype sont des protecteurs et peuvent utiliser des tactiques défensives.',
    'Maîtrise de la défense : Vous gagnez la maîtrise des boucliers. De plus, vous gagnez un style de combat supplémentaire.',
    'Tactiques défensives : Vous pouvez utiliser votre action pour adopter une posture défensive. Vous gagnez un bonus de +2 à votre CA jusqu\'au début de votre prochain tour.',
    'Style de combat supplémentaire : Vous gagnez un style de combat supplémentaire. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.',
    'Maîtrise de la défense supérieure : Vous pouvez utiliser votre action pour adopter une posture défensive. Vous gagnez un bonus de +3 à votre CA jusqu\'au début de votre prochain tour.',
    'Défenseur légendaire : Vous pouvez utiliser votre action pour adopter une posture défensive. Vous gagnez un bonus de +4 à votre CA jusqu\'au début de votre prochain tour.'
),
(
    'École de duel',
    'L\'archétype École de duel se concentre sur le combat au corps à corps et la précision. Les guerriers de cet archétype sont des duellistes et peuvent utiliser des tactiques de duel.',
    'Maîtrise du duel : Vous gagnez la maîtrise des armes de duel. De plus, vous gagnez un style de combat supplémentaire.',
    'Tactiques de duel : Vous pouvez utiliser votre action pour adopter une posture de duel. Vous gagnez un bonus de +2 aux jets d\'attaque avec les armes de duel jusqu\'au début de votre prochain tour.',
    'Style de combat supplémentaire : Vous gagnez un style de combat supplémentaire. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.',
    'Maîtrise du duel supérieure : Vous pouvez utiliser votre action pour adopter une posture de duel. Vous gagnez un bonus de +3 aux jets d\'attaque avec les armes de duel jusqu\'au début de votre prochain tour.',
    'Duelliste légendaire : Vous pouvez utiliser votre action pour adopter une posture de duel. Vous gagnez un bonus de +4 aux jets d\'attaque avec les armes de duel jusqu\'au début de votre prochain tour.'
);
