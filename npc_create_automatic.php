<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_npc'])) {
    $world_id = (int)($_POST['world_id'] ?? 0);
    $country_id = (int)($_POST['country_id'] ?? 0);
    $place_id = (int)($_POST['place_id'] ?? 0);
    $region_id = !empty($_POST['region_id']) ? (int)$_POST['region_id'] : null;
    $race_id = (int)($_POST['race'] ?? 0);
    $class_id = (int)($_POST['class'] ?? 0);
    $level = (int)($_POST['level'] ?? 1);
    $custom_name = $_POST['custom_name'] ?? '';
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $is_identified = isset($_POST['is_identified']) ? 1 : 0;
    
    // Validation
    if ($world_id <= 0 || $country_id <= 0 || $place_id <= 0 || $race_id <= 0 || $class_id <= 0 || $level < 1 || $level > 20) {
        $error_message = "Veuillez sélectionner un monde, un lieu, une race, une classe et un niveau valide.";
    } else {
        try {
            $pdo = getPdo();
            
            // Vérifier que les données sont valides
            $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE id = ? AND created_by = ?");
            $stmt->execute([$world_id, $user_id]);
            $world = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$world) {
                $error_message = "Monde invalide.";
            } else {
                $stmt = $pdo->prepare("SELECT id, name FROM countries WHERE id = ? AND world_id = ?");
                $stmt->execute([$country_id, $world_id]);
                $country = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$country) {
                    $error_message = "Pays invalide.";
                } else {
                    $stmt = $pdo->prepare("SELECT id, title FROM places WHERE id = ? AND country_id = ?");
                    $stmt->execute([$place_id, $country_id]);
                    $place = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$place) {
                        $error_message = "Lieu invalide.";
                    } else {
                        // Créer le PNJ automatiquement
                        $npc = createAutomaticNPC($race_id, $class_id, $level, $user_id, $custom_name, $place_id, $is_visible, $is_identified);
                        
                        if ($npc) {
                            $success_message = "PNJ créé avec succès : " . htmlspecialchars($npc['name']);
                        } else {
                            $error_message = "Erreur lors de la création du PNJ.";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de la création du PNJ : " . $e->getMessage();
        }
    }
}

// Fonction pour créer un PNJ automatiquement
function createAutomaticNPC($race_id, $class_id, $level, $user_id, $custom_name = '', $place_id, $is_visible = 1, $is_identified = 1) {
    global $pdo;
    
    // Récupérer les noms de race et classe
    $race_name = getRaceName($race_id);
    $class_name = getClassName($class_id);
    
    // Utiliser le nom personnalisé ou générer un nom automatiquement
    $name = !empty(trim($custom_name)) ? trim($custom_name) : generateRandomName($race_name);
    
    // Sélectionner un historique aléatoire
    $background = selectRandomBackground($class_name);
    
    // Générer les caractéristiques selon les recommandations D&D
    $stats = generateRecommendedStats($class_name);
    
    // Générer l'alignement aléatoire
    $alignment = selectRandomAlignment();
    
    // Générer les traits de personnalité
    $personality_traits = generatePersonalityTraits($class_name);
    $ideals = generateIdeals($alignment);
    $bonds = generateBonds($race_name, $class_name);
    $flaws = generateFlaws($class_name);
    
    // Générer l'équipement de départ
    $equipment = generateStartingEquipment($class_name);
    
    // Calculer les points d'expérience selon le niveau D&D
    $experience_points = calculateExperiencePoints($level);
    
    // Créer d'abord le personnage dans la table characters
    $stmt = $pdo->prepare("
        INSERT INTO characters (
            user_id, name, race_id, class_id, level, experience_points, background, alignment,
            strength, dexterity, constitution, intelligence, wisdom, charisma,
            hit_points_max, armor_class, speed, equipment, personality_traits, ideals, bonds, flaws, is_npc, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $user_id, $name, $race_id, $class_id, $level, $experience_points, $background, $alignment,
        $stats['strength'], $stats['dexterity'], $stats['constitution'], 
        $stats['intelligence'], $stats['wisdom'], $stats['charisma'],
        $stats['hit_points'], $stats['armor_class'], $stats['speed'], $equipment,
        $personality_traits, $ideals, $bonds, $flaws
    ]);
    
    $character_id = $pdo->lastInsertId();
    
    // Créer ensuite l'entrée dans place_npcs
    $description = "PNJ de niveau $level - $race_name $class_name. " . $personality_traits;
    
    $stmt = $pdo->prepare("
        INSERT INTO place_npcs (name, description, profile_photo, is_visible, is_identified, place_id, monster_id, npc_character_id) 
        VALUES (?, ?, NULL, ?, ?, ?, NULL, ?)
    ");
    $stmt->execute([$name, $description, $is_visible, $is_identified, $place_id, $character_id]);
    
    return [
        'id' => $character_id,
        'name' => $name,
        'race' => $race_name,
        'class' => $class_name,
        'level' => $level
    ];
}

// Fonction pour récupérer le nom d'une race par ID
function getRaceName($race_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM races WHERE id = ?");
    $stmt->execute([$race_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Humain';
}

// Fonction pour récupérer le nom d'une classe par ID
function getClassName($class_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Guerrier';
}

// Fonction pour générer un nom aléatoire selon la race
function generateRandomName($race) {
    $names = [
        'Humain' => ['Aelric', 'Brenna', 'Cedric', 'Dara', 'Eamon', 'Fiona', 'Gareth', 'Hilda', 'Ivan', 'Jenna'],
        'Elfe' => ['Aelindra', 'Baelor', 'Celebrian', 'Daelin', 'Elenwe', 'Faelar', 'Galadriel', 'Haldir', 'Ithilien', 'Jareth'],
        'Nain' => ['Balin', 'Dwalin', 'Fili', 'Kili', 'Gimli', 'Thorin', 'Dain', 'Bofur', 'Bombur', 'Ori'],
        'Halfelin' => ['Bilbo', 'Frodo', 'Samwise', 'Pippin', 'Merry', 'Rosie', 'Lobelia', 'Polo', 'Bungo', 'Belladonna'],
        'Demi-Orc' => ['Grom', 'Thak', 'Zog', 'Morga', 'Korg', 'Ruga', 'Thokk', 'Gorak', 'Mok', 'Zara'],
        'Tieffelin' => ['Zariel', 'Malphas', 'Belial', 'Asmodeus', 'Mephistopheles', 'Baalzebul', 'Glasya', 'Levistus', 'Mammon', 'Fierna']
    ];
    
    $raceNames = $names[$race] ?? $names['Humain'];
    return $raceNames[array_rand($raceNames)];
}

// Fonction pour sélectionner un historique aléatoire selon la classe
function selectRandomBackground($class) {
    $backgrounds = [
        'Guerrier' => ['Soldat', 'Noble', 'Criminel', 'Folk Hero'],
        'Magicien' => ['Sage', 'Acolyte', 'Hermite', 'Noble'],
        'Clerc' => ['Acolyte', 'Sage', 'Noble', 'Folk Hero'],
        'Voleur' => ['Criminel', 'Charlatan', 'Noble', 'Soldat'],
        'Barde' => ['Artiste', 'Charlatan', 'Noble', 'Sage'],
        'Barbare' => ['Folk Hero', 'Criminel', 'Soldat', 'Hermite'],
        'Moine' => ['Hermite', 'Acolyte', 'Sage', 'Folk Hero'],
        'Rôdeur' => ['Folk Hero', 'Hermite', 'Soldat', 'Criminel'],
        'Paladin' => ['Noble', 'Acolyte', 'Folk Hero', 'Soldat'],
        'Ensorceleur' => ['Hermite', 'Noble', 'Acolyte', 'Sage'],
        'Druide' => ['Hermite', 'Folk Hero', 'Sage', 'Acolyte'],
        'Occultiste' => ['Charlatan', 'Criminel', 'Noble', 'Sage']
    ];
    
    $classBackgrounds = $backgrounds[$class] ?? $backgrounds['Guerrier'];
    return $classBackgrounds[array_rand($classBackgrounds)];
}

// Fonction pour générer les caractéristiques selon les recommandations D&D
function generateRecommendedStats($class) {
    // Valeurs recommandées selon les préconisations D&D (point buy system)
    $recommendedStats = [
        'Barbare' => ['strength' => 15, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 12, 'intelligence' => 8, 'charisma' => 10],
        'Barde' => ['strength' => 8, 'dexterity' => 14, 'constitution' => 13, 'wisdom' => 10, 'intelligence' => 12, 'charisma' => 15],
        'Clerc' => ['strength' => 13, 'dexterity' => 12, 'constitution' => 14, 'wisdom' => 15, 'intelligence' => 8, 'charisma' => 10],
        'Druide' => ['strength' => 8, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 15, 'intelligence' => 12, 'charisma' => 10],
        'Guerrier' => ['strength' => 15, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 10, 'intelligence' => 12, 'charisma' => 8],
        'Moine' => ['strength' => 12, 'dexterity' => 15, 'constitution' => 13, 'wisdom' => 14, 'intelligence' => 10, 'charisma' => 8],
        'Paladin' => ['strength' => 15, 'dexterity' => 12, 'constitution' => 13, 'wisdom' => 10, 'intelligence' => 8, 'charisma' => 14],
        'Magicien' => ['strength' => 8, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 12, 'intelligence' => 15, 'charisma' => 10],
        'Ensorceleur' => ['strength' => 8, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 10, 'intelligence' => 12, 'charisma' => 15],
        'Occultiste' => ['strength' => 8, 'dexterity' => 13, 'constitution' => 14, 'wisdom' => 10, 'intelligence' => 12, 'charisma' => 15],
        'Voleur' => ['strength' => 8, 'dexterity' => 15, 'constitution' => 13, 'wisdom' => 10, 'intelligence' => 14, 'charisma' => 12],
        'Rôdeur' => ['strength' => 8, 'dexterity' => 15, 'constitution' => 13, 'wisdom' => 14, 'intelligence' => 12, 'charisma' => 10]
    ];
    
    $stats = $recommendedStats[$class] ?? $recommendedStats['Guerrier'];
    
    // Calculer les valeurs dérivées
    $stats['hit_points'] = calculateHitPoints($class, $stats['constitution'], 1);
    $stats['armor_class'] = calculateArmorClass($class, $stats['dexterity']);
    $stats['speed'] = 30; // Vitesse de base
    
    return $stats;
}

// Fonction pour calculer les points de vie
function calculateHitPoints($class, $constitution, $level) {
    $hitDie = [
        'Guerrier' => 10, 'Paladin' => 10, 'Rôdeur' => 10,
        'Barbare' => 12,
        'Magicien' => 6, 'Ensorceleur' => 6, 'Occultiste' => 6,
        'Clerc' => 8, 'Druide' => 8, 'Barde' => 8, 'Moine' => 8, 'Voleur' => 8
    ];
    
    $die = $hitDie[$class] ?? 8;
    $constitutionModifier = floor(($constitution - 10) / 2);
    
    return $die + $constitutionModifier;
}

// Fonction pour calculer la classe d'armure
function calculateArmorClass($class, $dexterity) {
    $dexterityModifier = floor(($dexterity - 10) / 2);
    
    // Classe d'armure de base selon la classe
    $baseAC = [
        'Barbare' => 10 + $dexterityModifier + 3, // Défense sans armure
        'Moine' => 10 + $dexterityModifier + 3,   // Défense sans armure
        'Magicien' => 10 + $dexterityModifier,    // Pas d'armure
        'Ensorceleur' => 10 + $dexterityModifier, // Pas d'armure
        'Occultiste' => 10 + $dexterityModifier,  // Pas d'armure
    ];
    
    return $baseAC[$class] ?? (10 + $dexterityModifier + 2); // Armure de cuir +2
}

// Fonction pour sélectionner un alignement aléatoire
function selectRandomAlignment() {
    $alignments = [
        'Loyal Bon', 'Neutre Bon', 'Chaotique Bon',
        'Loyal Neutre', 'Neutre', 'Chaotique Neutre',
        'Loyal Mauvais', 'Neutre Mauvais', 'Chaotique Mauvais'
    ];
    
    return $alignments[array_rand($alignments)];
}

// Fonction pour générer des traits de personnalité
function generatePersonalityTraits($class) {
    $traits = [
        'Guerrier' => ['Courageux et déterminé', 'Protecteur des faibles', 'Fier de ses compétences martiales'],
        'Magicien' => ['Curieux et studieux', 'Analytique et logique', 'Passionné par la connaissance'],
        'Clerc' => ['Dévoué à sa foi', 'Compassionné et guérisseur', 'Ferme dans ses convictions'],
        'Voleur' => ['Rusé et discret', 'Opportuniste et adaptable', 'Méfiant mais loyal'],
        'Barde' => ['Charmeur et éloquent', 'Artiste et créatif', 'Sociable et optimiste'],
        'Barbare' => ['Féroce et impulsif', 'Loyal envers ses amis', 'Simple et direct'],
        'Moine' => ['Discipliné et zen', 'Pacifique mais ferme', 'Spirituel et méditatif'],
        'Rôdeur' => ['Protecteur de la nature', 'Solitaire mais sage', 'Expert de la survie'],
        'Paladin' => ['Noble et chevaleresque', 'Juste et honorable', 'Défenseur du bien'],
        'Ensorceleur' => ['Charismatique et mystérieux', 'Impulsif et passionné', 'Confiant en ses pouvoirs'],
        'Druide' => ['Uni à la nature', 'Sage et patient', 'Protecteur de l\'équilibre'],
        'Occultiste' => ['Mystérieux et calculateur', 'Ambitieux et déterminé', 'Fasciné par le pouvoir']
    ];
    
    $classTraits = $traits[$class] ?? $traits['Guerrier'];
    return $classTraits[array_rand($classTraits)];
}

// Fonction pour générer des idéaux selon l'alignement
function generateIdeals($alignment) {
    $ideals = [
        'Loyal Bon' => ['Protection des innocents', 'Justice et honneur', 'Service au bien commun'],
        'Neutre Bon' => ['Bienveillance universelle', 'Aide aux nécessiteux', 'Compassion pour tous'],
        'Chaotique Bon' => ['Liberté individuelle', 'Rébellion contre l\'oppression', 'Bonté spontanée'],
        'Loyal Neutre' => ['Ordre et tradition', 'Équilibre et stabilité', 'Respect de la loi'],
        'Neutre' => ['Équilibre naturel', 'Neutralité et impartialité', 'Harmonie universelle'],
        'Chaotique Neutre' => ['Liberté personnelle', 'Indépendance totale', 'Rejet des contraintes'],
        'Loyal Mauvais' => ['Domination par l\'ordre', 'Hiérarchie et contrôle', 'Pouvoir structuré'],
        'Neutre Mauvais' => ['Survie du plus fort', 'Égoïsme pragmatique', 'Pouvoir personnel'],
        'Chaotique Mauvais' => ['Destruction et chaos', 'Liberté absolue', 'Pouvoir par la terreur']
    ];
    
    $alignmentIdeals = $ideals[$alignment] ?? $ideals['Neutre'];
    return $alignmentIdeals[array_rand($alignmentIdeals)];
}

// Fonction pour générer des liens
function generateBonds($race, $class) {
    $bonds = [
        'Humain' => ['Ma famille est tout pour moi', 'Je protège mon village natal', 'Je cherche à honorer mes ancêtres'],
        'Elfe' => ['Je protège les anciennes forêts', 'Ma lignée ancestrale me guide', 'Je défends les traditions elfiques'],
        'Nain' => ['Mon clan est ma fierté', 'Je cherche à restaurer l\'honneur familial', 'Mes ancêtres me guident'],
        'Halfelin' => ['Ma communauté est ma famille', 'Je protège les miens', 'Mon foyer est sacré'],
        'Demi-Orc' => ['Je prouve ma valeur', 'Je protège ceux qui m\'acceptent', 'Je surmonte mon héritage'],
        'Tieffelin' => ['Je rachète ma nature démoniaque', 'Je protège les innocents', 'Je contrôle mes pouvoirs']
    ];
    
    $raceBonds = $bonds[$race] ?? $bonds['Humain'];
    return $raceBonds[array_rand($raceBonds)];
}

// Fonction pour générer des défauts
function generateFlaws($class) {
    $flaws = [
        'Guerrier' => ['Trop confiant en mes capacités', 'Impulsif au combat', 'Fier à l\'excès'],
        'Magicien' => ['Obsédé par la connaissance', 'Méprisant envers les non-mages', 'Curieux au point de la témérité'],
        'Clerc' => ['Intolérant envers les autres religions', 'Trop rigide dans mes croyances', 'Naïf face au mal'],
        'Voleur' => ['Méfiant envers tout le monde', 'Tenté par les gains faciles', 'Secret à l\'excès'],
        'Barde' => ['Vaniteux et égocentrique', 'Trop bavard', 'Dramatique à l\'excès'],
        'Barbare' => ['Colérique et violent', 'Impulsif et imprudent', 'Méprisant envers la civilisation'],
        'Moine' => ['Trop rigide et inflexible', 'Méprisant envers les non-initiés', 'Obsédé par la perfection'],
        'Rôdeur' => ['Misanthropique', 'Trop attaché à la nature', 'Méfiant envers la civilisation'],
        'Paladin' => ['Intolérant envers le mal', 'Trop rigide moralement', 'Naïf face à la corruption'],
        'Ensorceleur' => ['Arrogant à cause de mes pouvoirs', 'Impulsif avec la magie', 'Mystérieux à l\'excès'],
        'Druide' => ['Méprisant envers la civilisation', 'Trop attaché à la nature', 'Intolérant envers la technologie'],
        'Occultiste' => ['Obsédé par le pouvoir', 'Mystérieux et secret', 'Tenté par les arts sombres']
    ];
    
    $classFlaws = $flaws[$class] ?? $flaws['Guerrier'];
    return $classFlaws[array_rand($classFlaws)];
}

// Fonction pour calculer les points d'expérience selon le niveau D&D
function calculateExperiencePoints($level) {
    // Table des points d'expérience selon les règles D&D 5e officielles
    $xpTable = [
        1 => 0,        // Niveau 1 : 0 XP
        2 => 300,      // Niveau 2 : 300 XP
        3 => 900,      // Niveau 3 : 900 XP
        4 => 2700,     // Niveau 4 : 2,700 XP
        5 => 6500,     // Niveau 5 : 6,500 XP
        6 => 14000,    // Niveau 6 : 14,000 XP
        7 => 23000,    // Niveau 7 : 23,000 XP
        8 => 34000,    // Niveau 8 : 34,000 XP
        9 => 48000,    // Niveau 9 : 48,000 XP
        10 => 64000,   // Niveau 10 : 64,000 XP
        11 => 85000,   // Niveau 11 : 85,000 XP
        12 => 100000,  // Niveau 12 : 100,000 XP
        13 => 120000,  // Niveau 13 : 120,000 XP
        14 => 140000,  // Niveau 14 : 140,000 XP
        15 => 165000,  // Niveau 15 : 165,000 XP
        16 => 195000,  // Niveau 16 : 195,000 XP
        17 => 225000,  // Niveau 17 : 225,000 XP
        18 => 265000,  // Niveau 18 : 265,000 XP
        19 => 305000,  // Niveau 19 : 305,000 XP
        20 => 355000   // Niveau 20 : 355,000 XP
    ];
    
    // Retourner les points d'expérience pour le niveau donné
    return $xpTable[$level] ?? 0;
}

// Fonction pour générer l'équipement de départ
function generateStartingEquipment($class) {
    $equipment = [
        'Guerrier' => 'Épée longue, Bouclier, Armure de cuir, Sac à dos, 10 flèches',
        'Magicien' => 'Baguette, Grimoire, Sac à composants, Robe, Sac à dos',
        'Clerc' => 'Masse d\'armes, Bouclier, Armure d\'écailles, Symbole sacré, Sac à dos',
        'Voleur' => 'Rapière, Arc court, Sacoche, Outils de voleur, Sac à dos',
        'Barde' => 'Rapière, Luth, Sacoche, Vêtements de voyage, Sac à dos',
        'Barbare' => 'Hache de guerre, 2 javelines, Sacoche, Sac à dos',
        'Moine' => 'Bâton, 10 flèches, Sacoche, Sac à dos',
        'Rôdeur' => 'Épée longue, Arc long, Sacoche, Sac à dos',
        'Paladin' => 'Épée longue, Bouclier, Armure de cuir, Symbole sacré, Sac à dos',
        'Ensorceleur' => 'Dague, Baguette, Sac à composants, Sacoche, Sac à dos',
        'Druide' => 'Bouclier, Bâton, Sacoche, Sac à dos',
        'Occultiste' => 'Dague, Baguette, Sac à composants, Sacoche, Sac à dos'
    ];
    
    return $equipment[$class] ?? 'Épée courte, Sacoche, Sac à dos';
}

// Récupérer les mondes de l'utilisateur
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les races et classes disponibles
    $races = [];
    $stmt = $pdo->query("SELECT id, name FROM races ORDER BY name");
    while ($row = $stmt->fetch()) {
        $races[] = $row;
    }
    
    $classes = [];
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
    while ($row = $stmt->fetch()) {
        $classes[] = $row;
    }
} catch (Exception $e) {
    $worlds = [];
    $races = [];
    $classes = [];
    $error_message = "Erreur lors de la récupération des données.";
}

$page_title = "Création Automatique de PNJ";
$current_page = "manage_npcs";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-magic me-2"></i>Création Automatique de PNJ
                            </h4>
                            <a href="manage_npcs.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            </div>
                            <div class="d-flex justify-content-center">
                                <a href="manage_npcs.php" class="btn btn-primary">
                                    <i class="fas fa-list me-1"></i>Voir tous les PNJ
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error_message): ?>
                                <?php echo displayMessage($error_message, 'error'); ?>
                            <?php endif; ?>
                            
                            <?php if (empty($worlds)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucun monde créé</h5>
                                    <p class="text-muted">Vous devez d'abord créer un monde avant de pouvoir créer des PNJ.</p>
                                    <a href="manage_worlds.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Créer un Monde
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Création automatique :</strong> Cette fonctionnalité génère des PNJ complets avec race, classe, niveau, 
                                    caractéristiques, personnalité et équipement selon les règles D&D 5e. 
                                    Vous pourrez ensuite les personnaliser individuellement.
                                </div>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="world_id" class="form-label">Monde *</label>
                                                <select class="form-select" id="world_id" name="world_id" required>
                                                    <option value="">Sélectionnez un monde</option>
                                                    <?php foreach ($worlds as $world): ?>
                                                        <option value="<?php echo $world['id']; ?>">
                                                            <?php echo htmlspecialchars($world['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="country_id" class="form-label">Pays *</label>
                                                <select class="form-select" id="country_id" name="country_id" required>
                                                    <option value="">Sélectionnez d'abord un monde</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="region_id" class="form-label">Région</label>
                                                <select class="form-select" id="region_id" name="region_id">
                                                    <option value="">Aucune région</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="place_id" class="form-label">Lieu *</label>
                                                <select class="form-select" id="place_id" name="place_id" required>
                                                    <option value="">Sélectionnez d'abord un pays</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="custom_name" class="form-label">Nom du personnage (optionnel)</label>
                                            <input type="text" class="form-control" id="custom_name" name="custom_name" 
                                                   placeholder="Laissez vide pour générer automatiquement un nom">
                                            <small class="text-muted">Si vous laissez ce champ vide, un nom sera généré automatiquement selon la race sélectionnée.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="race" class="form-label">Race *</label>
                                                <select class="form-select" id="race" name="race" required>
                                                    <option value="">Sélectionner une race</option>
                                                    <?php foreach ($races as $race): ?>
                                                        <option value="<?php echo $race['id']; ?>"><?php echo htmlspecialchars($race['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="class" class="form-label">Classe *</label>
                                                <select class="form-select" id="class" name="class" required>
                                                    <option value="">Sélectionner une classe</option>
                                                    <?php foreach ($classes as $class): ?>
                                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="level" class="form-label">Niveau *</label>
                                                <select class="form-select" id="level" name="level" required onchange="updateExperiencePoints()">
                                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $i === 1 ? 'selected' : ''; ?>>
                                                            Niveau <?php echo $i; ?> (<?php echo number_format(calculateExperiencePoints($i)); ?> XP)
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <small class="text-muted" id="xp-display">Points d'expérience : 0 XP</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
                                                    <label class="form-check-label" for="is_visible">
                                                        Visible par les joueurs
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_identified" name="is_identified" checked>
                                                    <label class="form-check-label" for="is_identified">
                                                        Identifié par les joueurs
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Ce qui sera généré automatiquement :</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Nom du personnage (personnalisé ou généré selon la race)</li>
                                            <li>Caractéristiques (selon les recommandations D&D pour la classe)</li>
                                            <li>Points d'expérience (selon le niveau D&D 5e officiel)</li>
                                            <li>Historique (selon la classe)</li>
                                            <li>Alignement (aléatoire)</li>
                                            <li>Traits de personnalité (selon la classe)</li>
                                            <li>Idéaux (selon l'alignement)</li>
                                            <li>Liens (selon la race)</li>
                                            <li>Défauts (selon la classe)</li>
                                            <li>Équipement de départ</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="manage_npcs.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Annuler
                                        </a>
                                        <button type="submit" name="create_npc" class="btn btn-success">
                                            <i class="fas fa-magic me-1"></i>Créer le PNJ
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Table des points d'expérience D&D 5e
        const xpTable = {
            1: 0, 2: 300, 3: 900, 4: 2700, 5: 6500, 6: 14000, 7: 23000, 8: 34000,
            9: 48000, 10: 64000, 11: 85000, 12: 100000, 13: 120000, 14: 140000,
            15: 165000, 16: 195000, 17: 225000, 18: 265000, 19: 305000, 20: 355000
        };

        // Fonction pour mettre à jour l'affichage des points d'expérience
        function updateExperiencePoints() {
            const levelSelect = document.getElementById('level');
            const xpDisplay = document.getElementById('xp-display');
            const selectedLevel = parseInt(levelSelect.value);
            const xp = xpTable[selectedLevel] || 0;
            
            xpDisplay.textContent = `Points d'expérience : ${xp.toLocaleString()} XP`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser l'affichage des points d'expérience
            updateExperiencePoints();
            
            const worldSelect = document.getElementById('world_id');
            const countrySelect = document.getElementById('country_id');
            const regionSelect = document.getElementById('region_id');
            const placeSelect = document.getElementById('place_id');
            
            // Charger les pays, régions et lieux
            let countriesData = [];
            let regionsData = [];
            let placesData = [];
            
            fetch('get_countries_by_world.php')
                .then(response => response.json())
                .then(data => {
                    countriesData = data;
                })
                .catch(error => console.error('Erreur lors du chargement des pays:', error));
            
            fetch('get_regions_by_country.php')
                .then(response => response.json())
                .then(data => {
                    regionsData = data;
                })
                .catch(error => console.error('Erreur lors du chargement des régions:', error));
            
            fetch('get_places_by_region.php')
                .then(response => response.json())
                .then(data => {
                    placesData = data;
                })
                .catch(error => console.error('Erreur lors du chargement des lieux:', error));
            
            function updateCountries(worldId) {
                countrySelect.innerHTML = '<option value="">Sélectionnez un pays</option>';
                regionSelect.innerHTML = '<option value="">Aucune région</option>';
                placeSelect.innerHTML = '<option value="">Sélectionnez un lieu</option>';
                
                if (worldId) {
                    const worldCountries = countriesData.filter(country => country.world_id == worldId);
                    worldCountries.forEach(country => {
                        const option = document.createElement('option');
                        option.value = country.id;
                        option.textContent = country.name;
                        countrySelect.appendChild(option);
                    });
                }
            }
            
            function updateRegions(countryId) {
                regionSelect.innerHTML = '<option value="">Aucune région</option>';
                placeSelect.innerHTML = '<option value="">Sélectionnez un lieu</option>';
                
                if (countryId) {
                    const countryRegions = regionsData.filter(region => region.country_id == countryId);
                    countryRegions.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.id;
                        option.textContent = region.name;
                        regionSelect.appendChild(option);
                    });
                }
            }
            
            function updatePlaces(countryId, regionId) {
                placeSelect.innerHTML = '<option value="">Sélectionnez un lieu</option>';
                
                if (countryId) {
                    let filteredPlaces = placesData.filter(place => place.country_id == countryId);
                    
                    if (regionId) {
                        filteredPlaces = filteredPlaces.filter(place => place.region_id == regionId);
                    }
                    
                    filteredPlaces.forEach(place => {
                        const option = document.createElement('option');
                        option.value = place.id;
                        option.textContent = place.title;
                        placeSelect.appendChild(option);
                    });
                }
            }
            
            worldSelect.addEventListener('change', function() {
                updateCountries(this.value);
            });
            
            countrySelect.addEventListener('change', function() {
                updateRegions(this.value);
                updatePlaces(this.value, '');
            });
            
            regionSelect.addEventListener('change', function() {
                updatePlaces(countrySelect.value, this.value);
            });
        });
    </script>
</body>
</html>
