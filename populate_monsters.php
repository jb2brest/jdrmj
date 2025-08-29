<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isDM()) {
    header('Location: index.php');
    exit();
}

// Vérifier si des monstres existent déjà
$stmt = $pdo->query("SELECT COUNT(*) as count FROM dnd_monsters");
$count = $stmt->fetch()['count'];

if ($count > 0) {
    echo "<h2>Des monstres existent déjà dans la base de données</h2>";
    echo "<p>Nombre de monstres : $count</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit();
}

// Monstres d'exemple
$monsters = [
    [
        'name' => 'Gobelin',
        'type' => 'Humanoïde',
        'size' => 'Petit',
        'alignment' => 'Neutre Mauvais',
        'challenge_rating' => 0.25,
        'hit_points' => 7,
        'armor_class' => 15,
        'speed' => '9 m',
        'proficiency_bonus' => 2,
        'description' => 'Les gobelins sont de petits humanoïdes malveillants qui vivent dans des cavernes sombres et des ruines abandonnées.',
        'actions' => 'Attaque de cimeterre. Attaque de corps à corps avec une arme : +4 pour toucher, allonge 1,50 m, une cible. Touché : 5 (1d6 + 2) dégâts tranchants.',
        'special_abilities' => 'Furtivité. Le gobelin peut se cacher comme une action bonus.'
    ],
    [
        'name' => 'Orc',
        'type' => 'Humanoïde',
        'size' => 'Moyen',
        'alignment' => 'Chaotique Mauvais',
        'challenge_rating' => 0.5,
        'hit_points' => 15,
        'armor_class' => 13,
        'speed' => '12 m',
        'proficiency_bonus' => 2,
        'description' => 'Les orcs sont des guerriers féroces et brutaux qui vivent pour le combat et la conquête.',
        'actions' => 'Attaque de hache de guerre. Attaque de corps à corps avec une arme : +5 pour toucher, allonge 1,50 m, une cible. Touché : 6 (1d8 + 2) dégâts tranchants.',
        'special_abilities' => 'Attaque agressive. L\'orc peut utiliser une action bonus pour se déplacer vers un ennemi.'
    ],
    [
        'name' => 'Ogre',
        'type' => 'Géant',
        'size' => 'Grand',
        'alignment' => 'Chaotique Mauvais',
        'challenge_rating' => 2.0,
        'hit_points' => 59,
        'armor_class' => 11,
        'speed' => '12 m',
        'proficiency_bonus' => 2,
        'description' => 'Les ogres sont des géants stupides et brutaux qui se nourrissent de chair humaine.',
        'actions' => 'Attaque de massue. Attaque de corps à corps avec une arme : +6 pour toucher, allonge 3 m, une cible. Touché : 13 (2d8 + 4) dégâts contondants.',
        'special_abilities' => 'Force brute. L\'ogre peut pousser ou renverser un adversaire avec une attaque réussie.'
    ],
    [
        'name' => 'Troll',
        'type' => 'Géant',
        'size' => 'Grand',
        'alignment' => 'Chaotique Mauvais',
        'challenge_rating' => 5.0,
        'hit_points' => 84,
        'armor_class' => 15,
        'speed' => '9 m',
        'proficiency_bonus' => 3,
        'description' => 'Les trolls sont des créatures régénératrices redoutables qui se nourrissent de chair fraîche.',
        'actions' => 'Attaque de griffe. Attaque de corps à corps : +7 pour toucher, allonge 3 m, une cible. Touché : 7 (1d6 + 4) dégâts tranchants.',
        'special_abilities' => 'Régénération. Le troll récupère 10 points de vie au début de son tour s\'il a au moins 1 point de vie.'
    ],
    [
        'name' => 'Dragon Rouge Jeune',
        'type' => 'Dragon',
        'size' => 'Grand',
        'alignment' => 'Chaotique Mauvais',
        'challenge_rating' => 10.0,
        'hit_points' => 178,
        'armor_class' => 18,
        'speed' => '12 m, vol 24 m',
        'proficiency_bonus' => 4,
        'description' => 'Un jeune dragon rouge est déjà une créature redoutable, avide de trésors et de domination.',
        'actions' => 'Attaque de morsure. Attaque de corps à corps : +10 pour toucher, allonge 3 m, une cible. Touché : 17 (2d10 + 6) dégâts perforants plus 3 (1d6) dégâts de feu.',
        'special_abilities' => 'Souffle de feu. Le dragon peut cracher du feu sur un cône de 9 mètres.'
    ],
    [
        'name' => 'Liche',
        'type' => 'Mort-vivant',
        'size' => 'Moyen',
        'alignment' => 'Neutre Mauvais',
        'challenge_rating' => 21.0,
        'hit_points' => 135,
        'armor_class' => 17,
        'speed' => '9 m',
        'proficiency_bonus' => 7,
        'description' => 'Une liche est un magicien mort-vivant qui a transcendé la mortalité grâce à la magie noire.',
        'actions' => 'Attaque de paralysie. Attaque de corps à corps : +12 pour toucher, allonge 1,50 m, une cible. Touché : 10 (3d6) dégâts nécrotiques.',
        'special_abilities' => 'Résistance légendaire. La liche peut choisir de réussir automatiquement un jet de sauvegarde.'
    ]
];

// Insérer les monstres
$stmt = $pdo->prepare("
    INSERT INTO dnd_monsters (name, type, size, alignment, challenge_rating, hit_points, armor_class, speed, proficiency_bonus, description, actions, special_abilities) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$inserted = 0;
foreach ($monsters as $monster) {
    try {
        $stmt->execute([
            $monster['name'],
            $monster['type'],
            $monster['size'],
            $monster['alignment'],
            $monster['challenge_rating'],
            $monster['hit_points'],
            $monster['armor_class'],
            $monster['speed'],
            $monster['proficiency_bonus'],
            $monster['description'],
            $monster['actions'],
            $monster['special_abilities']
        ]);
        $inserted++;
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur lors de l'insertion de {$monster['name']}: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Population de la base de données terminée</h2>";
echo "<p>$inserted monstres ont été ajoutés à la base de données.</p>";
echo "<p><a href='bestiary.php'>Parcourir le bestiaire</a></p>";
echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?>
