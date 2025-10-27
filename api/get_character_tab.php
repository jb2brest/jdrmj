<?php
/**
 * API endpoint pour récupérer le contenu d'un onglet de personnage
 */

require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false, 'content' => '', 'message' => ''];

if (!isLoggedIn()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

// Récupérer les paramètres
$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : null;

if (!$character_id || !$tab) {
    $response['message'] = 'Character ID and tab are required.';
    echo json_encode($response);
    exit();
}

// Récupération et validation du personnage
$character = Character::findById($character_id);
if (!$character) {
    $response['message'] = 'Character not found.';
    echo json_encode($response);
    exit();
}

// Vérifier les permissions
$isOwner = $character->belongsToUser($_SESSION['user_id']);
$isDM = isDM();
$isAdmin = User::isAdmin();

if (!$isOwner && !$isDM && !$isAdmin) {
    $response['message'] = 'Access denied.';
    echo json_encode($response);
    exit();
}

try {
    // Définir toutes les variables nécessaires (comme dans view_character.php)
    $raceObject = Race::findById($character->race_id);
    $classObject = Classe::findById($character->class_id);
    $backgroundDetails = $character->background_id ? Character::getBackgroundById($character->background_id) : null;
    
    // Récupérer les détails de l'archétype
    $archetypeDetails = null;
    if ($character->class_archetype_id) {
        $archetypeDetails = Character::getArchetypeById($character->class_archetype_id);
    }
    
    // Récupérer l'équipement du personnage
    $equipment = Character::getCharacterEquipment($character_id);
    
    // Récupérer les capacités du personnage
    $allCapabilities = $character->getCapabilities();
    
    // Récupérer les compétences et langues du personnage
    $characterSkills = $character->skills ? json_decode($character->skills, true) : [];
    $characterLanguages = $character->languages ? json_decode($character->languages, true) : [];
    
    // Si les compétences ne sont pas définies dans la base de données, les générer automatiquement
    if (empty($characterSkills)) {
        $characterSkills = $character->generateBaseSkills();
    }
    
    // Si les langues ne sont pas définies dans la base de données, les générer automatiquement
    if (empty($characterLanguages)) {
        $characterLanguages = $character->generateBaseLanguages();
    }
    
    // Parser les données de l'historique
    $backgroundSkills = $backgroundDetails && isset($backgroundDetails['skill_proficiencies']) ? json_decode($backgroundDetails['skill_proficiencies'], true) : [];
    $backgroundLanguages = $backgroundDetails && isset($backgroundDetails['languages']) ? json_decode($backgroundDetails['languages'], true) : [];
    
    // Parser les données de la classe
    $classSkills = $classObject && $classObject->skill_proficiencies ? json_decode($classObject->skill_proficiencies, true) : [];
    if (!is_array($classSkills)) $classSkills = [];
    
    // Parser les langues de race
    $raceLanguages = $raceObject && $raceObject->languages ? json_decode($raceObject->languages, true) : [];
    if (!is_array($raceLanguages)) $raceLanguages = [];
    
    // S'assurer que tous les tableaux sont des tableaux
    if (!is_array($characterSkills)) $characterSkills = [];
    if (!is_array($backgroundSkills)) $backgroundSkills = [];
    if (!is_array($characterLanguages)) $characterLanguages = [];
    if (!is_array($backgroundLanguages)) $backgroundLanguages = [];
    
    // Combiner les compétences et langues
    $allSkills = array_unique(array_merge($characterSkills, $backgroundSkills, $classSkills));
    $allLanguages = array_unique(array_merge($characterLanguages, $backgroundLanguages, $raceLanguages));
    
    // Définir $allTools comme tableau vide pour l'instant
    $allTools = [];
    
    // Vérifier si c'est un barbare pour les rages
    $isBarbarian = $classObject && strpos(strtolower($classObject->name), 'barbare') !== false;
    $rageData = null;
    if ($isBarbarian) {
        $maxRages = Character::getMaxRages($character->class_id, $character->level);
        $rageUsage = Character::getRageUsageStatic($character_id);
        $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;
        
        $rageData = [
            'max' => $maxRages,
            'used' => $usedRages,
            'available' => $maxRages - $usedRages
        ];
    }
    
    // Récupérer les améliorations de caractéristiques du personnage
    $abilityImprovements = $characterObject->getAbilityImprovements();
    
    // Calculer les points d'amélioration disponibles selon le niveau
    $availableImprovements = 0;
    if ($character->level >= 4) $availableImprovements += 2;
    if ($character->level >= 8) $availableImprovements += 2;
    if ($character->level >= 12) $availableImprovements += 2;
    if ($character->level >= 16) $availableImprovements += 2;
    if ($character->level >= 19) $availableImprovements += 2;
    
    // Calculer les points d'amélioration utilisés
    $totalImprovements = 0;
    foreach ($abilityImprovements as $stat => $value) {
        $totalImprovements += $value;
    }
    
    // Calculer les points restants
    $remainingPoints = max(0, $availableImprovements - $totalImprovements);
    
    // Variables pour les bonus d'équipement et temporaires
    $equipmentBonuses = $character->getMyEquipmentBonuses();
    $temporaryBonuses = $character->getMyTemporaryBonuses();
    
    // Variables pour les caractéristiques totales et modificateurs
    $totalAbilities = $character->getMyTotalAbilities();
    $abilityModifiers = $character->getMyAbilityModifiers();
    
    // Variables pour les modificateurs individuels
    $strengthModifier = $abilityModifiers['strength'];
    $dexterityModifier = $abilityModifiers['dexterity'];
    $constitutionModifier = $abilityModifiers['constitution'];
    $intelligenceModifier = $abilityModifiers['intelligence'];
    $wisdomModifier = $abilityModifiers['wisdom'];
    $charismaModifier = $abilityModifiers['charisma'];
    
    // Calculer la classe d'armure (base AC + modificateur de dextérité)
    $armorClass = 10 + $dexterityModifier;
    
    // Récupérer les attaques du personnage
    $attacks = Character::getCharacterAttacks($character_id);
    $characterAttacks = $attacks; // Alias pour compatibilité avec les templates
    
    // Variables pour les permissions de modification
    $canModifyHP = ($character->user_id == $_SESSION['user_id']);
    $canModifyAsDM = false;
    $canModifyXP = false;
    
    // Les MJ et admins peuvent modifier les PV et XP
    if (User::isDMOrAdmin()) {
        $canModifyAsDM = true;
        $canModifyHP = true;
        $canModifyXP = true;
    }
    
    // Variables de base
    $profile_photo = $character->profile_photo;
    $name = $character->name;
    $level = $character->level;
    $hit_points_current = $character->hit_points_current;
    $hit_points_max = $character->hit_points_max;
    $experience = $character->experience_points;
    $alignment = $character->alignment;
    $speed = $character->speed;
    $strength = $character->strength;
    $dexterity = $character->dexterity;
    $constitution = $character->constitution;
    $intelligence = $character->intelligence;
    $wisdom = $character->wisdom;
    $charisma = $character->charisma;
    $gold = $character->gold;
    $silver = $character->silver;
    $copper = $character->copper;
    $personality_traits = $character->personality_traits;
    $ideals = $character->ideals;
    $bonds = $character->bonds;
    $flaws = $character->flaws;
    $target_id = $character->id;
    $target_type = 'PJ';
    
    // Variables pour l'initiative et l'armure
    $initiative = $dexterityModifier;
    $armor_class = $character->armor_class;
    
    // Variables pour l'équipement (initialisées à null par défaut)
    $equippedArmor = null;
    $equippedShield = null;
    
    // Variables pour les améliorations de caractéristiques (initialisées à null par défaut)
    $abilityImprovementsArray = $abilityImprovements;
    
    // Récupérer l'équipement du personnage via la classe Character
    $magicalEquipment = [];
    $characterPoisons = $character->getMyCharacterPoisons();
    
    // Récupérer l'équipement du personnage via la méthode d'instance
    $characterItems = $character->getMyEquipment();
    
    // Traiter les équipements du personnage
    foreach ($characterItems as $item) {
        $magicalEquipment[] = $item;
    }
    
    // Variables pour l'équipement magique et poisons
    $allMagicalEquipment = $magicalEquipment;
    $allPoisons = $characterPoisons;
    
    // Objet background
    $backgroundObject = Background::findById($character->background_id);
    
    // Variables pour les messages (initialisées à null par défaut)
    $success_message = null;
    $error_message = null;
    
    // Variables pour le module de combat
    $spellcastingClasses = [2, 3, 4, 7, 9, 10, 11]; // Barde, Clerc, Druide, Magicien, Occultiste, Paladin, Rôdeur
    $canCastSpells = in_array($character->class_id, $spellcastingClasses);
    
    // Charger le template correspondant
    $template_file = "templates/p_{$tab}_module.php";
    
    if (!file_exists($template_file)) {
        $response['message'] = "Template not found: $template_file";
        echo json_encode($response);
        exit();
    }
    
    // Capturer le contenu du template
    ob_start();
    include $template_file;
    $content = ob_get_clean();
    
    $response['success'] = true;
    $response['content'] = $content;
    
} catch (Exception $e) {
    $response['message'] = 'Error loading tab content: ' . $e->getMessage();
}

echo json_encode($response);
?>
