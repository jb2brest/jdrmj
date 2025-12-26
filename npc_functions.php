<?php
// Utilisation de __DIR__ pour garantir le chemin correct quel que soit le contexte d'appel
require_once __DIR__ . '/classes/NPC.php';

// ===== FONCTIONS UTILITAIRES POUR LA CRÉATION DE PNJ =====

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
    // Valeurs recommandées selon les spécifications D&D
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
        'Roublard' => ['strength' => 8, 'dexterity' => 15, 'constitution' => 13, 'wisdom' => 10, 'intelligence' => 14, 'charisma' => 12],
        'Rôdeur' => ['strength' => 8, 'dexterity' => 15, 'constitution' => 13, 'wisdom' => 14, 'intelligence' => 12, 'charisma' => 10]
    ];
    
    $stats = $recommendedStats[$class] ?? $recommendedStats['Guerrier'];
    
    // Calculer les valeurs dérivées
    $stats['hit_points'] = calculateHitPoints($class, $stats['constitution'], $level);
    $stats['armor_class'] = calculateArmorClass($class, $stats['dexterity']);
    $stats['speed'] = 30; // Vitesse de base
    
    return $stats;
}

// Fonction pour calculer les points de vie selon les règles D&D avec tirages aléatoires
function calculateHitPoints($class, $constitution, $level) {
    $hitDie = [
        'Guerrier' => 10, 'Paladin' => 10, 'Rôdeur' => 10,
        'Barbare' => 12,
        'Magicien' => 6, 'Ensorceleur' => 6, 'Occultiste' => 6,
        'Clerc' => 8, 'Druide' => 8, 'Barde' => 8, 'Moine' => 8, 'Roublard' => 8
    ];
    
    $die = $hitDie[$class] ?? 8;
    $constitutionModifier = floor(($constitution - 10) / 2);
    
    // Calcul D&D avec tirages aléatoires
    $totalHp = 0;
    
    // Premier niveau = dé maximum + modificateur Constitution
    $firstLevelHp = $die + $constitutionModifier;
    $totalHp += $firstLevelHp;
    
    // Niveaux suivants = tirage aléatoire + modificateur Constitution
    for ($i = 2; $i <= $level; $i++) {
        $roll = rand(1, $die); // Tirage aléatoire du dé
        $levelHp = $roll + $constitutionModifier;
        $totalHp += $levelHp;
    }
    
    return $totalHp;
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

// Fonction pour générer l'équipement de départ
function generateStartingEquipment($class) {
    $equipment = [
        'Guerrier' => 'Épée longue, bouclier, armure de cuir, arc court, carquois avec 20 flèches',
        'Magicien' => 'Bâton, sac à composants, grimoire, dague, bourse',
        'Clerc' => 'Masse de guerre, bouclier, armure d\'écailles, croix sainte, bourse',
        'Voleur' => 'Rapière, arc court, carquois avec 20 flèches, sac de voleur, outils de voleur',
        'Barde' => 'Rapière, sacoche à composants, instrument de musique, armure de cuir, bourse',
        'Barbare' => 'Hache de guerre, javelines (4), sac d\'aventurier, bourse',
        'Moine' => 'Dague, arc court, carquois avec 20 flèches, sac d\'aventurier, bourse',
        'Rôdeur' => 'Épée courte, arc long, carquois avec 20 flèches, armure de cuir, bourse',
        'Paladin' => 'Épée longue, bouclier, armure de chaînes, croix sainte, bourse',
        'Ensorceleur' => 'Dague, sac à composants, bourse',
        'Druide' => 'Bouclier, cimeterre, armure de cuir, bourse',
        'Occultiste' => 'Dague, sac à composants, bourse'
    ];
    
    return $equipment[$class] ?? $equipment['Guerrier'];
}

// Fonction pour calculer les points d'expérience selon le niveau
function calculateExperiencePoints($level) {
    $xpTable = [
        1 => 0, 2 => 300, 3 => 900, 4 => 2700, 5 => 6500,
        6 => 14000, 7 => 23000, 8 => 34000, 9 => 48000, 10 => 64000,
        11 => 85000, 12 => 100000, 13 => 120000, 14 => 140000, 15 => 165000,
        16 => 195000, 17 => 225000, 18 => 265000, 19 => 305000, 20 => 355000
    ];
    
    return $xpTable[$level] ?? 0;
}

// Fonction pour créer un PNJ automatiquement
function createAutomaticNPC($race_id, $class_id, $level, $user_id, $custom_name, $place_id, $is_visible, $is_identified, $world_id, $country_id) {
    global $pdo;
    
    // Convertir class_id vide ou 0 en NULL
    if (empty($class_id) || $class_id <= 0) {
        $class_id = null;
    }
    
    // DEBUG COMPLET
    error_log("=== DEBUG NPC CREATION ===");
    error_log("Variables reçues:");
    error_log("- race_id: " . $race_id);
    error_log("- class_id: " . ($class_id === null ? 'NULL' : $class_id));
    error_log("- level: " . $level);
    error_log("- user_id: " . $user_id);
    error_log("- custom_name: " . $custom_name);
    error_log("- place_id: " . $place_id);
    error_log("- world_id: " . $world_id);
    error_log("- country_id: " . $country_id);
    
    // Vérifier que toutes les fonctions existent
    if (!function_exists('getRaceName')) {
        error_log("ERREUR: getRaceName() n'existe pas");
        return false;
    }
    if (!function_exists('getClassName')) {
        error_log("ERREUR: getClassName() n'existe pas");
        return false;
    }
    
    // Récupérer les noms de race et classe
    $race_name = getRaceName($race_id);
    $class_name = $class_id ? getClassName($class_id) : 'Sans classe';
    error_log("- race_name: " . $race_name);
    error_log("- class_name: " . $class_name);
    
    // Utiliser le nom personnalisé ou générer un nom automatiquement
    $name = !empty(trim($custom_name)) ? trim($custom_name) : generateRandomName($race_name);
    error_log("- name: " . $name);
    
    // Sélectionner un historique aléatoire
    $background_name = $class_id ? selectRandomBackground($class_name) : 'Commun';
    $background_id = $class_id ? selectRandomBackgroundId($class_name) : 1;
    error_log("- background_name: " . $background_name);
    error_log("- background_id: " . $background_id);
    
    // Sélectionner un archétype aléatoire pour la classe
    $archetype_id = $class_id ? selectRandomArchetypeId($class_id) : null;
    error_log("- archetype_id: " . ($archetype_id === null ? 'NULL' : $archetype_id));
    
    // Générer les caractéristiques selon les recommandations D&D
    if ($class_id) {
        $stats = generateRecommendedStats($class_name);
    } else {
        // Valeurs par défaut si pas de classe
        $stats = [
            'strength' => 10,
            'dexterity' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'wisdom' => 10,
            'charisma' => 10,
            'hit_points' => 4,
            'armor_class' => 10,
            'speed' => 30
        ];
    }
    error_log("- stats: " . print_r($stats, true));
    
    // Générer l'alignement aléatoire
    $alignment = selectRandomAlignment();
    error_log("- alignment: " . $alignment);
    
    // Générer les traits de personnalité
    $personality_traits = $class_id ? generatePersonalityTraits($class_name) : 'Personnage ordinaire';
    $ideals = generateIdeals($alignment);
    $bonds = generateBonds($race_name, $class_name);
    $flaws = $class_id ? generateFlaws($class_name) : 'Aucun défaut notable';
    error_log("- personality_traits: " . $personality_traits);
    
    // Générer l'équipement de départ
    $equipment = $class_id ? generateStartingEquipment($class_name) : 'Vêtements simples, bourse';
    error_log("- equipment: " . $equipment);
    
    // Générer l'or de départ selon la classe
    $starting_gold = $class_id ? generateStartingGold($class_name) : 50;
    error_log("- starting_gold: " . $starting_gold);
    
    // Générer l'historique
    $backstory = $class_id ? "PNJ de niveau $level - $race_name $class_name. " . $personality_traits : "PNJ $race_name sans classe particulière. " . $personality_traits;
    
    // Calculer les points d'expérience selon le niveau D&D
    $experience_points = calculateExperiencePoints($level);
    
    // Créer d'abord le personnage dans la table npcs avec toutes les caractéristiques
    $stmt = $pdo->prepare("
        INSERT INTO npcs (
            name, race_id, class_id, background_id, archetype_id, level, experience,
            strength, dexterity, constitution, intelligence, wisdom, charisma,
            hit_points_current, hit_points_max, armor_class, speed, alignment, backstory, personality_traits,
            ideals, bonds, flaws, starting_equipment, gold, created_by, world_id, location_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Vérification finale des valeurs critiques
    if ($world_id !== null && ($world_id <= 0)) {
        error_log("ERREUR: world_id invalide: " . $world_id);
        throw new Exception("world_id invalide: " . $world_id);
    }
    if (empty($place_id) || $place_id <= 0) {
        error_log("ERREUR: place_id invalide: " . $place_id);
        throw new Exception("place_id invalide: " . $place_id);
    }
    
    // Debug des paramètres SQL avec toutes les caractéristiques
    $sql_params = [
        $name, $race_id, $class_id, $background_id, $archetype_id, $level, $experience_points,
        $stats['strength'], $stats['dexterity'], $stats['constitution'], 
        $stats['intelligence'], $stats['wisdom'], $stats['charisma'],
        $stats['hit_points'], $stats['hit_points'], $stats['armor_class'], $stats['speed'], $alignment,
        $backstory, $personality_traits, $ideals, $bonds, $flaws, $equipment, 0,
        $user_id, $world_id, $place_id
    ];
    
    error_log("Paramètres SQL (" . count($sql_params) . "):");
    for ($i = 0; $i < count($sql_params); $i++) {
        error_log("  [$i] = " . (is_null($sql_params[$i]) ? 'NULL' : $sql_params[$i]));
    }
    
    // Debug lines removed for production

    
    try {
        $stmt->execute($sql_params);
        error_log("SUCCESS: Requête SQL exécutée avec succès");
    } catch (Exception $e) {
        error_log("ERREUR SQL: " . $e->getMessage());
        throw $e;
    }
    
    $npc_id = $pdo->lastInsertId();
    
    // Récupérer l'instance NPC pour utiliser les méthodes d'instance
    $npc = NPC::findById($npc_id, $pdo);
    if ($npc) {
        // Ajouter automatiquement les capacités, langues et compétences de base
        $npc->addBaseCapabilities();
        $npc->addBaseLanguages();
        $npc->addBaseSkills();
        
        // Ajouter les sorts de base si la classe peut lancer des sorts
        NPC::addBaseSpells($npc_id);
        
        // Ajouter les améliorations de caractéristiques selon le niveau
        NPC::addAbilityImprovements($npc_id);
        
        // Calculer les points de vie selon les règles D&D
        if ($class_id) {
            $calculatedHp = calculateHitPoints($class_name, $stats['constitution'], $level);
            $stmt = $pdo->prepare("UPDATE npcs SET hit_points_current = ?, hit_points_max = ? WHERE id = ?");
            $stmt->execute([$calculatedHp, $calculatedHp, $npc_id]);
        }
        
        // Ajouter l'équipement de départ et l'or
        $npc->addStartingEquipment($equipment, $starting_gold);
    }
    
    // Créer ensuite l'entrée dans place_npcs
    $description = "PNJ de niveau $level - $race_name $class_name. " . $personality_traits;
    
    $stmt = $pdo->prepare("
        INSERT INTO place_npcs (name, description, profile_photo, is_visible, is_identified, place_id, monster_id, npc_character_id) 
        VALUES (?, ?, NULL, ?, ?, ?, NULL, ?)
    ");
    $stmt->execute([$name, $description, $is_visible, $is_identified, $place_id, $npc_id]);
    
    return [
        'id' => $npc_id,
        'name' => $name,
        'race' => $race_name,
        'class' => $class_name,
        'level' => $level
    ];
}

/**
 * Générer l'or de départ selon la classe
 * 
 * @param string $class_name Nom de la classe
 * @return int Montant d'or de départ
 */
function generateStartingGold($class_name) {
    $gold_amounts = [
        'Barbare' => 200,
        'Barde' => 150,
        'Clerc' => 100,
        'Druide' => 100,
        'Guerrier' => 200,
        'Moine' => 50,
        'Paladin' => 150,
        'Magicien' => 100,
        'Ensorceleur' => 100,
        'Occultiste' => 100,
        'Roublard' => 150,
        'Rôdeur' => 150
    ];
    
    return $gold_amounts[$class_name] ?? 100;
}

/**
 * Sélectionne un background aléatoire et retourne son ID
 */
function selectRandomBackgroundId($class_name) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM backgrounds ORDER BY RAND() LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 1; // Fallback sur Acolyte si aucun background trouvé
    } catch (Exception $e) {
        error_log("Erreur lors de la sélection du background: " . $e->getMessage());
        return 1; // Fallback sur Acolyte
    }
}

/**
 * Sélectionne un archétype aléatoire pour une classe donnée
 */
function selectRandomArchetypeId($class_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM class_archetypes WHERE class_id = ? ORDER BY RAND() LIMIT 1");
        $stmt->execute([$class_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null; // Pas d'archétype si aucun trouvé
    } catch (Exception $e) {
        error_log("Erreur lors de la sélection de l'archétype: " . $e->getMessage());
        return null; // Pas d'archétype en cas d'erreur
    }
}
?>
