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

// Utiliser directement l'objet Character
$character = $characterObject;

// Récupérer les détails de la race, classe et background
$raceObject = Race::findById($character->race_id);
$classObject = Classe::findById($character->class_id);
$backgroundDetails = $character->background_id ? Character::getBackgroundById($character->background_id) : null;

// Récupérer les détails de l'archétype
$archetypeDetails = null;
if ($character->class_archetype_id) {
    $archetypeDetails = Character::getArchetypeById($character->class_archetype_id);
}

// Les modificateurs seront calculés plus tard à partir des totaux

// Récupérer l'équipement du personnage
$equipment = Character::getCharacterEquipment($character_id);

// Récupérer les capacités du personnage
$allCapabilities = $character->getCapabilities();

// Les données de bourse sont directement disponibles via les propriétés de l'objet Character

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

// La classe d'armure sera calculée plus tard après les modificateurs

// Récupérer les améliorations de caractéristiques du personnage
$abilityImprovements = $characterObject->getAbilityImprovements();

// Calculer les points d'amélioration disponibles selon le niveau
// Dans D&D 5e, les améliorations sont disponibles aux niveaux 4, 8, 12, 16, 19
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

// Vérifier les permissions de modification
$canModifyHP = ($character->user_id == $_SESSION['user_id']);
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
    $canModifyHP = ($character->user_id == $_SESSION['user_id']);
    $canModifyXP = false; // Les joueurs ne peuvent jamais modifier l'XP
}

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

// Variables pour l'équipement magique et poisons (initialisées à null par défaut)
$allMagicalEquipment = null;
$allPoisons = null;

// Objet background
$backgroundObject = Background::findById($character->background_id);

// Variables pour les messages (initialisées à null par défaut)
$success_message = null;
$error_message = null;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($character->name); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


        <div class="zone-de-titre">
            <div class="zone-titre-container">
                <h1 class="titre-zone">
                <i class="fas fa-user-ninja me-2"></i><?php echo htmlspecialchars($character->name); ?>
            </h1>
            <div>
                    <a href="characters.php" class="btn-txt">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
                </div>
            </div>
        </div>

        <!-- Zone d'en-tête -->
        <div class="zone-d-entete">
            <?php include 'templates/p_entete.php'; ?>
            </div>

        <!-- Zone des onglets -->
        <div class="tabs-section">
            <div class="card border-0 shadow">
                <div class="card-header p-0 tabs-header">
                    <ul class="nav nav-tabs border-0" id="npcTabs" role="tablist" data-bs-toggle="tab">
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt active" id="combat-tab" data-bs-toggle="tab" data-bs-target="#combat" type="button" role="tab" aria-controls="combat" aria-selected="true">
                                <i class="fas fa-shield-alt me-2"></i>Combat
                        </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="characteristics-tab" data-bs-toggle="tab" data-bs-target="#characteristics" type="button" role="tab" aria-controls="characteristics" aria-selected="false">
                                <i class="fas fa-dumbbell me-2"></i>Caractéristiques
                        </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="capabilities-tab" data-bs-toggle="tab" data-bs-target="#capabilities" type="button" role="tab" aria-controls="capabilities" aria-selected="false">
                                <i class="fas fa-star me-2"></i>Capacités
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab" aria-controls="skills" aria-selected="false">
                                <i class="fas fa-dice me-2"></i>Compétences
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="languages-tab" data-bs-toggle="tab" data-bs-target="#languages" type="button" role="tab" aria-controls="languages" aria-selected="false">
                                <i class="fas fa-language me-2"></i>Langues
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="treasury-tab" data-bs-toggle="tab" data-bs-target="#treasury" type="button" role="tab" aria-controls="treasury" aria-selected="false">
                                <i class="fas fa-coins me-2"></i>Bourse
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="equipment-tab" data-bs-toggle="tab" data-bs-target="#equipment" type="button" role="tab" aria-controls="equipment" aria-selected="false">
                                <i class="fas fa-backpack me-2"></i>Equipement
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt " id="personal-info-tab" data-bs-toggle="tab" data-bs-target="#personal-info" type="button" role="tab" aria-controls="personal-info" aria-selected="false">
                                <i class="fas fa-user-edit me-2"></i>Info perso.
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content tab-content" id="npcTabContent">
                    <?php 
                    // Variables pour le module de combat
                    $spellcastingClasses = [2, 3, 4, 7, 9, 10, 11]; // Barde, Clerc, Druide, Magicien, Occultiste, Paladin, Rôdeur
                    $canCastSpells = in_array($character->class_id, $spellcastingClasses);
                    ?>
                    <?php include 'templates/p_combat_module.php'; ?>

                    <?php include 'templates/p_characteristics_module.php'; ?>

                    <?php include 'templates/p_capabilities_module.php'; ?>

                    <?php include 'templates/p_skills_module.php'; ?>

                    <?php include 'templates/p_languages_module.php'; ?>

                    <?php include 'templates/p_treasury_module.php'; ?>

                    <?php include 'templates/p_equipment_module.php'; ?>

                    <?php include 'templates/p_personal_info_module.php'; ?>
                </div>
            </div>
                            </div>
        </div>
    </div>

<!-- Modals existants -->
    <?php if ($canModifyHP): ?>
        <?php include 'templates/modal_edit_hp.php'; ?>
    <?php endif; ?>

    <!-- Modal pour Gestion des Points d'Expérience -->
    <?php if ($canModifyHP): ?>
        <?php
        // Variables pour le modal de gestion des XP
        $target_id = $character->id;
        $target_type = 'PJ';
        ?>
        <?php include 'templates/modal_edit_xp.php'; ?>
    <?php endif; ?>

    <!-- Modal pour Long Repos -->
    <?php if ($canModifyHP): ?>
        <?php
        // Variables pour le modal de gestion des longs repos
        $target_id = $character->id;
        $target_type = 'PJ';
        $name = $character->name;
        ?>
        <?php include 'templates/modal_long_rest.php'; ?>
    <?php endif; ?>

    <!-- Modal pour Transfert d'Objets -->
    <?php include 'templates/modal_transfert_object.php'; ?>

    <!-- Modal pour Upload de Photo de Profil -->
    <?php if ($canModifyHP): ?>
        <?php include 'templates/modal_change_profil_photo.php'; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jdrmj.js"></script>
    <script src="js/hp-management.js"></script>
    <script src="js/xp-management.js"></script>
    <script src="js/long-rest-management.js"></script>
    
    
    <!-- Script pour l'initialisation des onglets -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser les onglets Bootstrap
            var tabTriggerList = [].slice.call(document.querySelectorAll('#npcTabs button'));
            
            tabTriggerList.forEach(function (triggerEl) {
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    
                    // Désactiver tous les onglets
                    var allTabs = document.querySelectorAll('#npcTabs .nav-link');
                    allTabs.forEach(function(tab) {
                        tab.classList.remove('active');
                        tab.setAttribute('aria-selected', 'false');
                    });
                    
                    // Masquer tous les contenus
                    var allPanes = document.querySelectorAll('#npcTabContent .tab-pane');
                    allPanes.forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });
                    
                    // Activer l'onglet cliqué
                    triggerEl.classList.add('active');
                    triggerEl.setAttribute('aria-selected', 'true');
                    
                    // Afficher le contenu correspondant
                    var targetId = triggerEl.getAttribute('data-bs-target');
                    var targetPane = document.querySelector(targetId);
                    if (targetPane) {
                        targetPane.classList.add('show', 'active');
                    }
                });
            });
        });
        
        // Initialiser les gestionnaires d'événements pour les NPCs
        initializeNpcEventHandlers();
    </script>
</body>
</html>