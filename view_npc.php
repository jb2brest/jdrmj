<?php
require_once 'classes/init.php';
require_once 'classes/Background.php';
require_once 'includes/functions.php';
require_once 'includes/capabilities_functions.php';
$page_title = "Fiche de PNJ";
$current_page = "view_npc";

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: manage_npcs.php');
    exit();
}

$npc_id = (int)$_GET['id'];
$npc_created = isset($_GET['created']) && $_GET['created'] == '1';

// Récupération du PNJ avec ses détails
$npc = NPC::findById($npc_id, $pdo);

if (!$npc) {
    header('Location: manage_npcs.php');
    exit();
}

// Vérifier les permissions (créateur ou DM)
$isOwner = ($npc->created_by == $_SESSION['user_id']);
$isDM = isDM();

if (!$isOwner && !$isDM) {
    header('Location: manage_npcs.php');
    exit();
}


// Récupérer les objets directement
$raceObject = Race::findById($npc->race_id);
$classObject = Classe::findById($npc->class_id);
$backgroundObject = Background::findById($npc->background_id);


// Vérifier que les objets essentiels existent
if (!$raceObject || !$classObject) {
    header('Location: characters.php');
    exit();
}

// Utiliser les méthodes d'instance
$characterSkills = $npc->getMySkills();
$characterLanguages = $npc->getMyLanguages();

// Utiliser les méthodes d'instance du background
$backgroundSkills = $backgroundObject ? $backgroundObject->getSkillProficiencies() : [];
$backgroundTools = $backgroundObject ? $backgroundObject->getToolProficiencies() : [];
$backgroundLanguages = $backgroundObject ? $backgroundObject->getLanguages() : [];

// Séparer les compétences des outils/instruments
$allSkills = [];
$allTools = [];

// Liste des outils et instruments connus
$knownTools = [
    'Chalemie', 'Cor', 'Cornemuse', 'Flûte', 'Flûte de pan', 'Luth', 'Lyre', 'Tambour', 'Tympanon', 'Viole',
    'Outils de forgeron', 'Outils de charpentier', 'Outils de cuisinier', 'Outils de tanneur', 'Outils de tisserand',
    'Outils de verrier', 'Outils de potier', 'Outils de cordonnier', 'Outils de bijoutier', 'Outils de calligraphe',
    'Outils de cartographe', 'Outils de navigateur', 'Outils de herboriste', 'Outils d\'alchimiste', 'Outils de mécanicien',
    'Outils de voleur', 'Outils d\'artisan', 'Instruments de musique', 'Jeux', 'Véhicules'
];

// Traiter les compétences du personnage
foreach ($characterSkills as $skill) {
    if (in_array($skill, $knownTools)) {
        $allTools[] = $skill;
    } else {
        $allSkills[] = $skill;
    }
}

// Ajouter les outils de l'historique, mais filtrer les mentions génériques
$filteredBackgroundTools = [];
foreach ($backgroundTools as $tool) {
    // Filtrer les mentions génériques qui ont été remplacées par des choix spécifiques
    if (strpos($tool, 'un type d') === false && strpos($tool, 'n\'importe quel') === false) {
        $filteredBackgroundTools[] = $tool;
    }
}
$allTools = array_unique(array_merge($allTools, $filteredBackgroundTools));

// Combiner les compétences du personnage avec celles de l'historique
$allSkills = array_unique(array_merge($allSkills, $backgroundSkills));

// Récupérer les données de rage pour les barbares
$isBarbarian = strpos(strtolower($classObject->name), 'barbare') !== false;
$isBard = strpos(strtolower($classObject->name), 'barde') !== false;
$isCleric = strpos(strtolower($classObject->name), 'clerc') !== false;
$isDruid = strpos(strtolower($classObject->name), 'druide') !== false;
$isSorcerer = strpos(strtolower($classObject->name), 'ensorceleur') !== false;
$isFighter = strpos(strtolower($classObject->name), 'guerrier') !== false;
$isWizard = strpos(strtolower($classObject->name), 'magicien') !== false;
$isMonk = strpos(strtolower($classObject->name), 'moine') !== false;
$isWarlock = strpos(strtolower($classObject->name), 'occultiste') !== false;
$isPaladin = strpos(strtolower($classObject->name), 'paladin') !== false;
$isRanger = strpos(strtolower($classObject->name), 'rôdeur') !== false;
$isRogue = strpos(strtolower($classObject->name), 'roublard') !== false;
$rageData = null;
if ($isBarbarian) {
    // Récupérer le nombre maximum de rages pour ce niveau
    $maxRages = $npc->getMyMaxRages();
    
    // Récupérer le nombre de rages utilisées
    $rageUsage = $npc->getMyRageUsage();
    $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;
    
    $rageData = [
        'max' => $maxRages,
        'used' => $usedRages,
        'available' => $maxRages - $usedRages
    ];
}

// Ajouter automatiquement les capacités de base si elles n'existent pas
$npc->addBaseCapabilities();

// Ajouter automatiquement les langues de base si elles n'existent pas
$npc->addBaseLanguages();

// Ajouter automatiquement les compétences de base si elles n'existent pas
$npc->addBaseSkills();

// Récupérer les capacités du PNJ depuis le système NPC
$allCapabilities = $npc->getCapabilities();

// Récupérer les langues du PNJ
$npcLanguages = $npc->getNpcLanguages();

// Récupérer les compétences du PNJ
$npcSkills = $npc->getNpcSkills();

if (!empty($allCapabilities)) {
    error_log("Debug view_npc.php - First capability: " . print_r($allCapabilities[0], true));
}

// Séparer les capacités par type pour l'affichage
$classCapabilities = [];
$raceCapabilities = [];
$backgroundCapabilities = [];

foreach ($allCapabilities as $capability) {
    $sourceType = $capability['source_type'] ?? 'unknown';
    switch ($sourceType) {
        case 'class':
            $classCapabilities[] = $capability;
            break;
        case 'race':
            $raceCapabilities[] = $capability;
            break;
        case 'background':
            $backgroundCapabilities[] = $capability;
            break;
    }
}

// Les capacités raciales sont maintenant récupérées depuis le nouveau système

// Récupérer l'archetype choisi depuis les données du PNJ
$characterArchetype = null;
if ($npc->archetype_id) {
    // Debug: vérifier si la classe Character est disponible
    if (!class_exists('Character')) {
        error_log("ERREUR: Classe Character non trouvée - " . date('Y-m-d H:i:s'));
        throw new Exception("Classe Character non trouvée");
    }
    $characterArchetype = Character::getArchetypeById($npc->archetype_id);
}

// Définir les variables d'archetype pour la compatibilité avec le code HTML existant
$barbarianPath = null;
$paladinOath = null;
$rangerArchetype = null;
$rogueArchetype = null;
$bardCollege = null;
$clericDomain = null;
$druidCircle = null;
$sorcererOrigin = null;
$fighterArchetype = null;
$wizardTradition = null;
$monkTradition = null;
$warlockPact = null;

if ($characterArchetype) {
    switch ($characterArchetype['class_name']) {
        case 'Barbare':
            $barbarianPath = $characterArchetype;
            break;
        case 'Paladin':
            $paladinOath = $characterArchetype;
            break;
        case 'Rôdeur':
            $rangerArchetype = $characterArchetype;
            break;
        case 'Roublard':
            $rogueArchetype = $characterArchetype;
            break;
        case 'Barde':
            $bardCollege = $characterArchetype;
            break;
        case 'Clerc':
            $clericDomain = $characterArchetype;
            break;
        case 'Druide':
            $druidCircle = $characterArchetype;
            break;
        case 'Ensorceleur':
            $sorcererOrigin = $characterArchetype;
            break;
        case 'Guerrier':
            $fighterArchetype = $characterArchetype;
            break;
        case 'Magicien':
            $wizardTradition = $characterArchetype;
            break;
        case 'Moine':
            $monkTradition = $characterArchetype;
            break;
        case 'Occultiste':
            $warlockPact = $characterArchetype;
            break;
    }
}

// Récupérer les améliorations de caractéristiques
$abilityImprovements = $npc->getCharacterAbilityImprovements();

// Convertir les améliorations en format associatif pour l'affichage
$abilityImprovementsArray = [
    'strength' => 0,
    'dexterity' => 0,
    'constitution' => 0,
    'intelligence' => 0,
    'wisdom' => 0,
    'charisma' => 0
];

foreach ($abilityImprovements as $improvement) {
    if (isset($improvement['ability']) && isset($improvement['improvement'])) {
        $abilityImprovementsArray[$improvement['ability']] = $improvement['improvement'];
    }
}

// Calculer les caractéristiques finales
$finalAbilities = $npc->calculateMyFinalAbilities($abilityImprovements);

// Calculer les points d'amélioration restants
$remainingPoints = $npc->getMyRemainingAbilityPoints($abilityImprovements);

// Les langues du personnage sont déjà stockées dans le champ 'languages' 
// et incluent toutes les langues (race + historique + choix)
$allLanguages = $npcLanguages;


// Les modificateurs seront calculés plus tard avec les totaux complets

// Synchroniser l'équipement de base vers items
$npc->syncMyBaseEquipmentToCharacterEquipment();

// Récupérer l'équipement du PNJ depuis les données JSON
$magicalEquipment = [];
$equippedItems = [
    'main_hand' => '',
    'off_hand' => '',
    'armor' => '',
    'shield' => '',
    'helmet' => '',
    'gloves' => '',
    'boots' => '',
    'ring1' => '',
    'ring2' => '',
    'amulet' => ''
];

// Récupérer l'équipement du PNJ via la classe NPC
$npcItems = $npc->getMyEquipment();

// Traiter les équipements du PNJ
foreach ($npcItems as $item) {
    $item['is_equipped'] = true; // Pour les PNJ, tous les équipements sont considérés comme équipés
    $magicalEquipment[] = $item;
    
    // Structurer les équipements par slot
    if ($item['object_type'] === 'weapon') {
        if (empty($equippedItems['main_hand'])) {
            $equippedItems['main_hand'] = $item['item_name'];
        } else {
            $equippedItems['off_hand'] = $item['item_name'];
        }
    } elseif ($item['object_type'] === 'armor') {
        $equippedItems['armor'] = $item['item_name'];
    } elseif ($item['object_type'] === 'shield') {
        $equippedItems['shield'] = $item['item_name'];
    }
}
// Récupérer l'or du PNJ
$npcGold = $npc->gold ?? 0;

// Récupérer l'or depuis les données JSON (pour compatibilité)
if (!empty($npc->starting_equipment)) {
    $equipmentData = json_decode($npc->starting_equipment, true);
    if ($equipmentData && isset($equipmentData['gold'])) {
        $npcGold = $equipmentData['gold'];
    }
}

// Construire le texte d'équipement à partir des équipements du PNJ
$equipmentText = '';
foreach ($magicalEquipment as $item) {
    if (isset($item['is_equipped']) && $item['is_equipped']) {
        $equipmentText .= $item['item_name'] . ', ';
    }
}
$equipmentText = rtrim($equipmentText, ', ');

// Détecter les armes, armures et boucliers dans l'équipement
$detectedWeapons = Item::detectWeaponsInEquipment($equipmentText);
$detectedArmor = Item::detectArmorInEquipment($equipmentText);
$detectedShields = Item::detectShieldsInEquipment($equipmentText);

// Calculer la classe d'armure en utilisant la méthode d'instance
$equippedArmorAndShield = $npc->getMyEquippedArmorAndShield();
$equippedArmor = $equippedArmorAndShield['armor'];
$equippedShield = $equippedArmorAndShield['shield'];

// Récupérer les bonus d'équipement via la méthode d'instance
$equipmentBonuses = $npc->getMyEquipmentBonuses();

// Récupérer les bonus temporaires via la méthode d'instance
$temporaryBonuses = $npc->getMyTemporaryBonuses();

// Récupérer les caractéristiques totales via la méthode d'instance
$totalAbilities = $npc->getMyTotalAbilities();

// Récupérer les modificateurs via la méthode d'instance
$abilityModifiers = $npc->getMyAbilityModifiers();

// Assigner les modificateurs aux variables locales pour la compatibilité
$strengthModifier = $abilityModifiers['strength'];
$dexterityModifier = $abilityModifiers['dexterity'];
$constitutionModifier = $abilityModifiers['constitution'];
$intelligenceModifier = $abilityModifiers['intelligence'];
$wisdomModifier = $abilityModifiers['wisdom'];
$charismaModifier = $abilityModifiers['charisma'];

// Les modificateurs sont déjà calculés plus haut

// Calculer les attaques du personnage
$characterAttacks = $npc->calculateMyCharacterAttacks();
$armorClass = $npc->calculateMyArmorClass($equippedArmor, $equippedShield);


// Contrôle d'accès: propriétaire OU MJ
$canView = ($npc->created_by == $_SESSION['user_id']);

if (!$canView && User::isDMOrAdmin()) {
    // Les MJ et admins peuvent voir tous les PNJ
    $canView = true;
}

if (!$canView) {
    header('Location: manage_npcs.php');
    exit();
}

// Vérifier si l'utilisateur peut modifier les points de vie (propriétaire ou MJ)
$canModifyHP = ($npc->created_by == $_SESSION['user_id']);
if (!$canModifyHP && User::isDMOrAdmin()) {
    // Les MJ et admins peuvent modifier tous les PNJ
    $canModifyHP = true;
}

$success_message = '';
$error_message = '';

// La gestion des points de vie est maintenant gérée via AJAX dans les APIs :
// - api/update_hp.php (mise à jour manuelle)
// - api/damage.php (dégâts)
// - api/heal.php (soins)
// - api/reset_hp.php (réinitialisation)

// La gestion des points d'expérience est maintenant gérée via AJAX dans l'API :
// - api/update_xp.php (ajout, retrait, définition d'XP)

// Le transfert d'objets est maintenant géré via AJAX dans l'API transferObject.php

// L'upload de photo de profil est maintenant géré via AJAX dans l'API :
// - api/upload_photo.php (upload de photo de profil)

// Récupérer l'équipement depuis la table items pour les nouveaux PNJ via la classe NPC
$npcMagicalEquipment = [];
$npcItems = $npc->getMyEquipment();

foreach ($npcItems as $item) {
    $item['is_equipped'] = true; // Pour les PNJ, tous les équipements sont considérés comme équipés
    $npcMagicalEquipment[] = $item;
}

// Récupérer les poisons du personnage via la classe NPC
$characterPoisons = $npc->getMyCharacterPoisons();

// Récupérer l'équipement de ce NPC via la méthode d'instance
$npcEquipment = $npc->getMyNpcEquipment();

// Séparer les objets magiques et poisons des PNJ
$npcMagicalEquipment = [];
$npcPoisons = [];

foreach ($npcEquipment as $item) {
    // Vérifier d'abord si c'est un poison
    $poison_info = $npc->getMyPoisonInfo($item['magical_item_id']);
    
    if ($poison_info) {
        // C'est un poison
        $item['poison_nom'] = $poison_info['nom'];
        $item['poison_type'] = $poison_info['type'];
        $item['poison_description'] = $poison_info['description'];
        $item['poison_source'] = $poison_info['source'];
        $npcPoisons[] = $item;
    } else {
        // Vérifier si c'est un objet magique
        $magical_info = $npc->getMyMagicalItemInfo($item['magical_item_id']);
        
        if ($magical_info) {
            // C'est un objet magique
            $item['magical_item_nom'] = $magical_info['nom'];
            $item['magical_item_type'] = $magical_info['type'];
            $item['magical_item_description'] = $magical_info['description'];
            $item['magical_item_source'] = $magical_info['source'];
            $npcMagicalEquipment[] = $item;
        }
    }
}

// Combiner les équipements du personnage et des PNJ
// Pour les NPCs, $npcMagicalEquipment n'est pas défini, donc on utilise seulement $magicalEquipment
if (isset($npcMagicalEquipment)) {
    $allMagicalEquipment = array_merge($magicalEquipment, $npcMagicalEquipment);
} else {
    $allMagicalEquipment = $magicalEquipment;
}

if (isset($npcPoisons)) {
    $allPoisons = array_merge($characterPoisons, $npcPoisons);
} else {
    $allPoisons = $characterPoisons;
}

// Les modificateurs sont déjà calculés plus haut dans le fichier

// Calcul de l'initiative
$initiative = $dexterityModifier;

// La classe d'armure est déjà calculée plus haut avec calculateArmorClassExtended()
// $armorClass = $character['armor_class']; // Cette ligne écrasait le calcul correct
// Préparation de l'affichage : 
$profile_photo = $npc->profile_photo;
$name = $npc->name;
$level = $npc->level;
$hit_points_current = $npc->hit_points_current;
$hit_points_max = $npc->hit_points_max;
$experience = $npc->experience;
$alignment = $npc->alignment;
$speed = $npc->speed;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($npc->name); ?> - JDR 4 MJ</title>
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
                <i class="fas fa-user-ninja me-2"></i><?php echo htmlspecialchars($npc->name); ?>
            </h1>
            <div>
                    <a href="manage_npcs.php" class="btn-txt">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
                </div>
            </div>
        </div>

        <!-- Zone d'en-tête -->
        <div class="zone-d-entete">
            <?php include 'templates/entete_personnage.php'; ?>
            </div>

        <!-- Zone des onglets -->
        <div class="tabs-section">
            <div class="card border-0 shadow">
                <div class="card-header p-0 npc-tabs-header">
                    <ul class="nav nav-tabs border-0" id="npcTabs" role="tablist" data-bs-toggle="tab">
                        <li class="nav-item" role="presentation">
                            <button class="btn-txt active" id="combat-tab" data-bs-toggle="tab" data-bs-target="#combat" type="button" role="tab" aria-controls="combat" aria-selected="true">
                                <i class="fas fa-sword me-2"></i>Combat
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

                <div class="tab-content npc-tab-content" id="npcTabContent">
                    <?php include 'templates/npc_combat_tab.php'; ?>

                    <?php include 'templates/npc_characteristics_tab.php'; ?>

                    <?php include 'templates/npc_capabilities_tab.php'; ?>

                    <?php include 'templates/npc_skills_tab.php'; ?>

                    <?php include 'templates/npc_languages_tab.php'; ?>

                    <?php include 'templates/npc_treasury_tab.php'; ?>

                    <?php include 'templates/npc_equipment_tab.php'; ?>

                    <?php include 'templates/npc_personal_info_tab.php'; ?>
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
        <?php include 'templates/modal_edit_xp.php'; ?>
    <?php endif; ?>

    <!-- Modal pour Transfert d'Objets -->
    <?php include 'templates/modal_transfert_object.php'; ?>

    <!-- Modal pour Upload de Photo de Profil -->
    <?php if ($canModifyHP): ?>
        <?php include 'templates/modal_change_profil_photo.php'; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/jdrmj.js"></script>
    
    
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
    </script>
</body>
</html>
