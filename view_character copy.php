<?php
/**
 * Page de visualisation d'une feuille de personnage
 * Refactorisée selon les nouvelles règles d'architecture
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'includes/capabilities_functions.php';
require_once 'includes/upload_config.php';

$page_title = "Fiche de Personnage";
$current_page = "view_character";

requireLogin();

// Validation des paramètres
if (!isset($_GET['id'])) {
    header('Location: characters.php');
    exit();
}

$character_id = (int)$_GET['id'];
$dm_campaign_id = isset($_GET['dm_campaign_id']) ? (int)$_GET['dm_campaign_id'] : null;
$character_created = isset($_GET['created']) && $_GET['created'] == '1';

// Récupération et validation du personnage
$characterObject = Character::findById($character_id);

if (!$characterObject) {
    header('Location: characters.php');
    exit();
}

// Vérifier les permissions
$isOwner = $characterObject->belongsToUser($_SESSION['user_id']);
$isDM = isDM();
$isAdmin = User::isAdmin();

if (!$isOwner && !$isDM && !$isAdmin) {
    header('Location: characters.php');
    exit();
}

// Convertir l'objet Character en tableau
$character = $characterObject->toArray();

// Récupérer les détails de la race, classe et background
$raceObject = Race::findById($character['race_id']);
$classObject = Classe::findById($character['class_id']);
$backgroundDetails = $character['background_id'] ? Character::getBackgroundById($character['background_id']) : null;

$raceDetails = $raceObject ? $raceObject->toArray() : [];
$classDetails = $classObject ? $classObject->toArray() : [];

// Récupérer les détails de l'archétype
$archetypeDetails = null;
if ($character['class_archetype_id']) {
    $archetypeDetails = Character::getArchetypeById($character['class_archetype_id']);
}

// Construire le tableau characterDetails
$characterDetails = [
    'race_name' => $raceDetails['name'] ?? '',
    'race_description' => $raceDetails['description'] ?? '',
    'strength_bonus' => $raceDetails['strength_bonus'] ?? 0,
    'dexterity_bonus' => $raceDetails['dexterity_bonus'] ?? 0,
    'constitution_bonus' => $raceDetails['constitution_bonus'] ?? 0,
    'intelligence_bonus' => $raceDetails['intelligence_bonus'] ?? 0,
    'wisdom_bonus' => $raceDetails['wisdom_bonus'] ?? 0,
    'charisma_bonus' => $raceDetails['charisma_bonus'] ?? 0,
    'traits' => $raceDetails['traits'] ?? '',
    'race_languages' => $raceDetails['languages'] ?? '',
    'class_name' => $classDetails['name'] ?? '',
    'class_description' => $classDetails['description'] ?? '',
    'hit_dice' => $classDetails['hit_dice'] ?? '',
    'class_skill_choices' => $classDetails['skill_choices'] ?? '',
    'background_name' => $backgroundDetails['name'] ?? '',
    'background_description' => $backgroundDetails['description'] ?? '',
    'background_skills' => $backgroundDetails['skill_proficiencies'] ?? '',
    'background_tools' => $backgroundDetails['tool_proficiencies'] ?? '',
    'background_languages' => $backgroundDetails['languages'] ?? '',
    'background_feature' => $backgroundDetails['feature'] ?? ''
];

// Les modificateurs seront calculés plus tard à partir des totaux

// Récupérer l'équipement du personnage
$equipment = Character::getCharacterEquipment($character_id);

// Récupérer les capacités du personnage
$capabilities = Character::getCharacterCapabilities($character_id);

// Récupérer les données de bourse du personnage
$moneyData = [
    'gold' => $character['money_gold'] ?? 0,
    'silver' => $character['money_silver'] ?? 0,
    'copper' => $character['money_copper'] ?? 0
];

// Récupérer les compétences et langues du personnage
$characterSkills = $character['skills'] ? json_decode($character['skills'], true) : [];
$characterLanguages = $character['languages'] ? json_decode($character['languages'], true) : [];

// Parser les données de l'historique
$backgroundSkills = $characterDetails['background_skills'] ? json_decode($characterDetails['background_skills'], true) : [];
$backgroundLanguages = $characterDetails['background_languages'] ? json_decode($characterDetails['background_languages'], true) : [];

// Parser les données de la classe
$classSkills = $characterDetails['class_skill_choices'] ? json_decode($characterDetails['class_skill_choices'], true) : [];
if (!is_array($classSkills)) $classSkills = [];

// Parser les langues de race
$raceLanguages = $characterDetails['race_languages'] ? json_decode($characterDetails['race_languages'], true) : [];
if (!is_array($raceLanguages)) $raceLanguages = [];

// S'assurer que tous les tableaux sont des tableaux
if (!is_array($characterSkills)) $characterSkills = [];
if (!is_array($backgroundSkills)) $backgroundSkills = [];
if (!is_array($characterLanguages)) $characterLanguages = [];
if (!is_array($backgroundLanguages)) $backgroundLanguages = [];

// Combiner les compétences et langues
$allSkills = array_unique(array_merge($characterSkills, $backgroundSkills, $classSkills));
$allLanguages = array_unique(array_merge($characterLanguages, $backgroundLanguages, $raceLanguages));

// Vérifier si c'est un barbare pour les rages
$isBarbarian = strpos(strtolower($characterDetails['class_name']), 'barbare') !== false;
$rageData = null;
if ($isBarbarian) {
    $maxRages = Character::getMaxRages($character['class_id'], $character['level']);
    $rageUsage = Character::getRageUsageStatic($character_id);
    $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;
    
    $rageData = [
        'max' => $maxRages,
        'used' => $usedRages,
        'available' => $maxRages - $usedRages
    ];
}

// La classe d'armure sera calculée plus tard après les modificateurs

// Récupérer les améliorations de caractéristiques du personnage
$abilityImprovements = $characterObject->getAbilityImprovements();

// Calculer les points d'amélioration disponibles selon le niveau
// Dans D&D 5e, les améliorations sont disponibles aux niveaux 4, 8, 12, 16, 19
$availableImprovements = 0;
if ($character['level'] >= 4) $availableImprovements += 2;
if ($character['level'] >= 8) $availableImprovements += 2;
if ($character['level'] >= 12) $availableImprovements += 2;
if ($character['level'] >= 16) $availableImprovements += 2;
if ($character['level'] >= 19) $availableImprovements += 2;

// Calculer les points d'amélioration utilisés
$totalImprovements = 0;
foreach ($abilityImprovements as $stat => $value) {
    $totalImprovements += $value;
}

// Calculer les points restants
$remainingPoints = max(0, $availableImprovements - $totalImprovements);

// Bonus d'équipements (pour l'instant à 0, peut être calculé plus tard)
$equipmentBonuses = [
    'strength' => 0,
    'dexterity' => 0,
    'constitution' => 0,
    'intelligence' => 0,
    'wisdom' => 0,
    'charisma' => 0
];

// Bonus temporaires (pour l'instant à 0, peut être calculé plus tard)
$temporaryBonuses = [
    'strength' => 0,
    'dexterity' => 0,
    'constitution' => 0,
    'intelligence' => 0,
    'wisdom' => 0,
    'charisma' => 0
];

// Calculer les totaux (caractéristiques de base + bonus raciaux + bonus de niveau + bonus d'équipements + bonus temporaires)
$totalAbilities = [
    'strength' => $character['strength'] + $characterDetails['strength_bonus'] + $abilityImprovements['strength'] + $equipmentBonuses['strength'] + $temporaryBonuses['strength'],
    'dexterity' => $character['dexterity'] + $characterDetails['dexterity_bonus'] + $abilityImprovements['dexterity'] + $equipmentBonuses['dexterity'] + $temporaryBonuses['dexterity'],
    'constitution' => $character['constitution'] + $characterDetails['constitution_bonus'] + $abilityImprovements['constitution'] + $equipmentBonuses['constitution'] + $temporaryBonuses['constitution'],
    'intelligence' => $character['intelligence'] + $characterDetails['intelligence_bonus'] + $abilityImprovements['intelligence'] + $equipmentBonuses['intelligence'] + $temporaryBonuses['intelligence'],
    'wisdom' => $character['wisdom'] + $characterDetails['wisdom_bonus'] + $abilityImprovements['wisdom'] + $equipmentBonuses['wisdom'] + $temporaryBonuses['wisdom'],
    'charisma' => $character['charisma'] + $characterDetails['charisma_bonus'] + $abilityImprovements['charisma'] + $equipmentBonuses['charisma'] + $temporaryBonuses['charisma']
];

// Calculer les modificateurs à partir des totaux
$strengthMod = floor(($totalAbilities['strength'] - 10) / 2);
$dexterityMod = floor(($totalAbilities['dexterity'] - 10) / 2);
$constitutionMod = floor(($totalAbilities['constitution'] - 10) / 2);
$intelligenceMod = floor(($totalAbilities['intelligence'] - 10) / 2);
$wisdomMod = floor(($totalAbilities['wisdom'] - 10) / 2);
$charismaMod = floor(($totalAbilities['charisma'] - 10) / 2);

// Ajouter les modificateurs au tableau character
$character['strength_modifier'] = $strengthMod;
$character['dexterity_modifier'] = $dexterityMod;
$character['constitution_modifier'] = $constitutionMod;
$character['intelligence_modifier'] = $intelligenceMod;
$character['wisdom_modifier'] = $wisdomMod;
$character['charisma_modifier'] = $charismaMod;

// Calculer la classe d'armure (base AC + modificateur de dextérité)
$armorClass = 10 + $dexterityMod;

// Récupérer les attaques du personnage
$attacks = Character::getCharacterAttacks($character_id);

// Vérifier les permissions de modification
$canModifyHP = ($character['user_id'] == $_SESSION['user_id']);
$canModifyAsDM = false;
$canModifyXP = false; // Seuls les MJ et admins peuvent modifier l'XP

// Les MJ et admins peuvent modifier les PV et XP
if (User::isDMOrAdmin()) {
    $canModifyAsDM = true;
    $canModifyHP = true; // Permettre la modification des PV
    $canModifyXP = true; // Seuls les MJ/Admin peuvent modifier l'XP
}

// Vérification spécifique pour les MJ de campagne
if (!$canModifyAsDM && User::isDMOrAdmin() && $dm_campaign_id) {
    if (User::isAdmin()) {
        $canModifyAsDM = true;
        $canModifyHP = true;
        $canModifyXP = true;
    } else {
        $campaign = Campaign::findById($dm_campaign_id);
        if ($campaign && $campaign->canModify($_SESSION['user_id'], User::getRole())) {
            $canModifyAsDM = true;
            $canModifyHP = true;
            $canModifyXP = true;
        }
    }
}

// Pour les joueurs normaux, seuls les propriétaires peuvent modifier les PV (pas l'XP)
if (!User::isDMOrAdmin()) {
    $canModifyAsDM = false;
    $canModifyHP = ($character['user_id'] == $_SESSION['user_id']);
    $canModifyXP = false; // Les joueurs ne peuvent jamais modifier l'XP
}

// Inclure le template de la page
include 'templates/view_character_template.php';
?>
