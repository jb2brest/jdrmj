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
    'background_name' => $backgroundDetails['name'] ?? '',
    'background_description' => $backgroundDetails['description'] ?? '',
    'background_skills' => $backgroundDetails['skill_proficiencies'] ?? '',
    'background_tools' => $backgroundDetails['tool_proficiencies'] ?? '',
    'background_languages' => $backgroundDetails['languages'] ?? '',
    'background_feature' => $backgroundDetails['feature'] ?? ''
];

// Calculer les modificateurs de caractéristiques
$strengthMod = floor(($character['strength'] - 10) / 2);
$dexterityMod = floor(($character['dexterity'] - 10) / 2);
$constitutionMod = floor(($character['constitution'] - 10) / 2);
$intelligenceMod = floor(($character['intelligence'] - 10) / 2);
$wisdomMod = floor(($character['wisdom'] - 10) / 2);
$charismaMod = floor(($character['charisma'] - 10) / 2);

// Ajouter les modificateurs au tableau character
$character['strength_modifier'] = $strengthMod;
$character['dexterity_modifier'] = $dexterityMod;
$character['constitution_modifier'] = $constitutionMod;
$character['intelligence_modifier'] = $intelligenceMod;
$character['wisdom_modifier'] = $wisdomMod;
$character['charisma_modifier'] = $charismaMod;

// Récupérer l'équipement du personnage
$equipment = Character::getCharacterEquipment($character_id);

// Récupérer les capacités du personnage
$capabilities = Character::getCharacterCapabilities($character_id);

// Vérifier les permissions de modification
$canModifyHP = ($character['user_id'] == $_SESSION['user_id']);
if (!$canModifyHP && User::isDMOrAdmin() && $dm_campaign_id) {
    if (User::isAdmin()) {
        $canModifyHP = true;
    } else {
        $campaign = Campaign::findById($dm_campaign_id);
        if ($campaign && $campaign->canModify($_SESSION['user_id'], User::getRole())) {
            $canModifyHP = true;
        }
    }
}

// Inclure le template de la page
include 'templates/view_character_template.php';
?>
