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

// Debug temporaire pour voir les capacités
error_log("Debug view_npc.php - NPC ID: " . $npc_id);
error_log("Debug view_npc.php - All capabilities count: " . count($allCapabilities));
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
    $archetypeObject = ClassArchetype::findById($npc->archetype_id);
    $characterArchetype = $archetypeObject ? $archetypeObject->toArray() : null;
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
        <?php if ($npc_created): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-user-plus me-2"></i>
                <strong>PNJ créé avec succès !</strong> Votre PNJ a été créé et équipé. Il est maintenant disponible dans le monde.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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


        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-user-ninja me-2"></i><?php echo htmlspecialchars($npc->name); ?>
            </h1>
            <div>
                <a href="manage_npcs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux PNJ
                </a>
            </div>
        </div>

        <!-- Zone d'entête -->
        <div class="npc-header-section mb-4">
            <div class="card border-0 shadow-lg">
                <div class="card-header" style="background: linear-gradient(135deg, var(--dnd-primary-darker) 0%, var(--dnd-secondary-darker) 100%); color: var(--dnd-neutral-light);">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <div class="me-3 position-relative">
                                    <?php if (!empty($npc->profile_photo)): ?>
                                        <img id="npc-profile-photo" src="<?php echo htmlspecialchars($npc->profile_photo); ?>" alt="Photo de <?php echo htmlspecialchars($npc->name); ?>" class="rounded npc-profile-photo border border-3 border-light shadow">
                                    <?php else: ?>
                                        <div class="bg-white rounded d-flex align-items-center justify-content-center npc-profile-placeholder border border-3 border-light shadow">
                                            <i class="fas fa-user text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($canModifyHP): ?>
                                        <button type="button" class="btn btn-sm btn-light position-absolute photo-edit-button shadow" data-bs-toggle="modal" data-bs-target="#photoModal" title="Changer la photo">
                                            <i class="fas fa-camera text-primary"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h2 class="text-white mb-2">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <?php echo htmlspecialchars($npc->name); ?>
                                    </h2>
                                    <p class="text-white-50 mb-1">
                                        <i class="fas fa-tag me-1"></i>
                                        <strong>Race :</strong> <?php echo htmlspecialchars($raceObject->name); ?>
                                    </p>
                                    <p class="text-white-50 mb-1">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <strong>Classe :</strong> <?php echo htmlspecialchars($classObject->name); ?>
                                    </p>
                                    <p class="text-white-50 mb-1">
                                        <i class="fas fa-star me-1"></i>
                                        <strong>Niveau :</strong> <?php echo $npc->level; ?>
                                    </p>
                                    <?php if ($backgroundObject && $backgroundObject->name): ?>
                                        <p class="text-white-50 mb-1">
                                            <i class="fas fa-book me-1"></i>
                                            <strong>Historique:</strong> <?php echo htmlspecialchars($backgroundObject->name); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($npc->alignment): ?>
                                        <p class="text-white-50 mb-1">
                                            <i class="fas fa-balance-scale me-1"></i>
                                            <strong>Alignement:</strong> <?php echo htmlspecialchars($npc->alignment); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($characterArchetype): ?>
                                        <p class="text-white-50 mb-1">
                                            <i class="fas fa-magic me-1"></i>
                                            <strong><?php echo htmlspecialchars($characterArchetype['archetype_type']); ?>:</strong> <?php echo htmlspecialchars($characterArchetype['name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-4">
                                    <div class="stat-box text-center p-3 bg-white bg-opacity-20 rounded">
                                        <div class="hp-display text-white h5 mb-1"><?php echo $npc->hit_points_current; ?>/<?php echo $npc->hit_points_max; ?></div>
                                        <div class="stat-label text-white-50 small">Points de Vie</div>
                                        <?php if ($canModifyHP): ?>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#hpModal" title="Gérer les points de vie">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box text-center p-3 bg-white bg-opacity-20 rounded">
                                        <div class="ac-display text-white h5 mb-1"><?php echo $armorClass; ?></div>
                                        <div class="stat-label text-white-50 small">Classe d'Armure</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-box text-center p-3 bg-white bg-opacity-20 rounded">
                                        <?php if ($canModifyHP): ?>
                                            <div class="xp-display clickable-xp text-white h5 mb-1" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience"><?php echo number_format($npc->experience ?? 0); ?></div>
                                        <?php else: ?>
                                            <div class="xp-display text-white h5 mb-1"><?php echo number_format($npc->experience ?? 0); ?></div>
                                        <?php endif; ?>
                                        <div class="stat-label text-white-50 small">Exp.</div>
                                        <small class="text-white-50">Niveau <?php echo $npc->level; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone des onglets -->
        <div class="npc-tabs-section">
            <div class="card border-0 shadow">
                <div class="card-header p-0" style="background: linear-gradient(135deg, var(--dnd-primary-darker) 0%, var(--dnd-secondary-darker) 100%);">
                    <ul class="nav nav-tabs border-0" id="npcTabs" role="tablist" data-bs-toggle="tab">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white" id="combat-tab" data-bs-toggle="tab" data-bs-target="#combat" type="button" role="tab" aria-controls="combat" aria-selected="true" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-sword me-2"></i>Combat
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="characteristics-tab" data-bs-toggle="tab" data-bs-target="#characteristics" type="button" role="tab" aria-controls="characteristics" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-dumbbell me-2"></i>Caractéristiques
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="capabilities-tab" data-bs-toggle="tab" data-bs-target="#capabilities" type="button" role="tab" aria-controls="capabilities" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-star me-2"></i>Capacités
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab" aria-controls="skills" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-dice me-2"></i>Compétences
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="languages-tab" data-bs-toggle="tab" data-bs-target="#languages" type="button" role="tab" aria-controls="languages" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-language me-2"></i>Langues
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="treasury-tab" data-bs-toggle="tab" data-bs-target="#treasury" type="button" role="tab" aria-controls="treasury" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-coins me-2"></i>Bourse
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="equipment-tab" data-bs-toggle="tab" data-bs-target="#equipment" type="button" role="tab" aria-controls="equipment" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-backpack me-2"></i>Equipement
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="personal-info-tab" data-bs-toggle="tab" data-bs-target="#personal-info" type="button" role="tab" aria-controls="personal-info" aria-selected="false" style="background: transparent; border: none; color: var(--dnd-neutral-light);">
                                <i class="fas fa-user-edit me-2"></i>Info perso.
                            </button>
                        </li>
                    </ul>
                </div>
            
                <div class="tab-content" id="npcTabContent" style="background: linear-gradient(135deg, var(--dnd-bg-light) 0%, var(--dnd-bg) 100%);">
                <!-- Onglet Combat -->
                <div class="tab-pane fade show active" id="combat" role="tabpanel" aria-labelledby="combat-tab">
                    <div class="p-4">
                        <!-- Informations de combat -->
                        <div class="info-section mb-4">
                            <h4><i class="fas fa-sword me-2"></i>Informations de Combat</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="stat-value"><?php echo ($initiative >= 0 ? '+' : '') . $initiative; ?></div>
                                        <div class="stat-label">Initiative</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="stat-value"><?php echo $npc->speed; ?></div>
                                        <div class="stat-label">Vitesse (pieds)</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="stat-value">+<?php echo ceil($npc->level / 4) + 1; ?></div>
                                        <div class="stat-label">Bonus de maîtrise</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="stat-value"><?php echo $armorClass; ?></div>
                                        <div class="stat-label">Classe d'Armure</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Classe d'armure détaillée -->
                        <div class="info-section mb-4">
                            <h5><i class="fas fa-shield-alt me-2"></i>Classe d'armure</h5>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4 class="text-primary">CA: <?php echo $armorClass; ?></h4>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <?php if ($equippedArmor): ?>
                                                    <strong>Armure:</strong> <?php echo $equippedArmor['name']; ?> (<?php echo $equippedArmor['ac_formula']; ?>)<br>
                                                <?php else: ?>
                                                    <?php if ($isBarbarian): ?>
                                                        <strong>Armure:</strong> Aucune (10 + modificateur de Dextérité + modificateur de Constitution)<br>
                                                    <?php else: ?>
                                                        <strong>Armure:</strong> Aucune (10 + modificateur de Dextérité)<br>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if ($equippedShield): ?>
                                                    <strong>Bouclier:</strong> <?php echo $equippedShield['name']; ?> (+<?php echo $equippedShield['ac_bonus']; ?>)<br>
                                                <?php else: ?>
                                                    <strong>Bouclier:</strong> Aucun<br>
                                                <?php endif; ?>
                                                
                                                <strong>Modificateur de Dextérité:</strong> <?php echo ($dexterityModifier >= 0 ? '+' : '') . $dexterityModifier; ?>
                                                <?php if ($isBarbarian && !$equippedArmor): ?>
                                                    <br><strong>Modificateur de Constitution:</strong> <?php echo ($constitutionModifier >= 0 ? '+' : '') . $constitutionModifier; ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attaques -->
                        <div class="info-section mb-4">
                            <h5><i class="fas fa-sword me-2"></i>Attaques</h5>
                            <div class="card">
                                <div class="card-body">
                                    <?php if (!empty($characterAttacks)): ?>
                                        <?php foreach ($characterAttacks as $attack): ?>
                                            <div class="row mb-2">
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($attack['name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($attack['damage']); ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-<?php echo $attack['type'] === 'two_handed' ? 'danger' : ($attack['type'] === 'main_hand' ? 'success' : 'info'); ?> fs-6">
                                                                <?php echo (($attack['attack_bonus'] ?? 0) >= 0 ? '+' : '') . ($attack['attack_bonus'] ?? 0); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($attack !== end($characterAttacks)): ?>
                                                <hr class="my-2">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-hand-paper fa-2x mb-2"></i>
                                            <p>Aucune arme équipée</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rages (pour les barbares) -->
                        <?php if ($isBarbarian && $rageData): ?>
                        <div class="info-section mb-4">
                            <h5><i class="fas fa-fire me-2"></i>Gestion des Rages</h5>
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="rage-container">
                                        <div class="rage-symbols">
                                            <?php for ($i = 1; $i <= $rageData['max']; $i++): ?>
                                                <div class="rage-symbol <?php echo $i <= $rageData['used'] ? 'used' : 'available'; ?>" 
                                                     data-rage="<?php echo $i; ?>" data-npc-id="<?php echo $npc_id; ?>" data-action="toggle"
                                                     title="<?php echo $i <= $rageData['used'] ? 'Rage utilisée' : 'Rage disponible'; ?>">
                                                    <i class="fas fa-fire"></i>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rage-info mt-2">
                                            <span class="badge bg-info"><?php echo $rageData['available']; ?>/<?php echo $rageData['max']; ?> rages disponibles</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-warning" data-npc-id="<?php echo $npc_id; ?>" data-action="reset">
                                        <i class="fas fa-moon me-1"></i>Long repos
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Bouton Grimoire pour les classes de sorts -->
                        <?php 
                        // Classes qui peuvent lancer des sorts
                        $spellcastingClasses = [2, 3, 4, 5, 7, 9, 10, 11]; // Barde, Clerc, Druide, Ensorceleur, Magicien, Occultiste, Paladin, Rôdeur
                        $canCastSpells = in_array($npc->class_id, $spellcastingClasses);
                        ?>
                        <?php if ($canCastSpells): ?>
                        <div class="info-section">
                            <div class="d-flex justify-content-center">
                                <a href="grimoire_npc.php?id=<?php echo $npc_id; ?>" class="btn btn-primary btn-lg">
                                    <i class="fas fa-book-open me-2"></i>Grimoire
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Onglet Caractéristiques -->
                <div class="tab-pane fade" id="characteristics" role="tabpanel" aria-labelledby="characteristics-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h4>
                
                <!-- Tableau des caractéristiques -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="table-header-narrow">Type</th>
                                <th class="table-header-medium">Force</th>
                                <th class="table-header-medium">Dextérité</th>
                                <th class="table-header-medium">Constitution</th>
                                <th class="table-header-medium">Intelligence</th>
                                <th class="table-header-medium">Sagesse</th>
                                <th class="table-header-medium">Charisme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Caractéristiques de base -->
                            <tr>
                                <td><strong>Caractéristiques de base</strong></td>
                                <td><strong><?php echo $npc->strength; ?></strong></td>
                                <td><strong><?php echo $npc->dexterity; ?></strong></td>
                                <td><strong><?php echo $npc->constitution; ?></strong></td>
                                <td><strong><?php echo $npc->intelligence; ?></strong></td>
                                <td><strong><?php echo $npc->wisdom; ?></strong></td>
                                <td><strong><?php echo $npc->charisma; ?></strong></td>
                            </tr>
                            <!-- Bonus raciaux -->
                            <tr>
                                <td><strong>Bonus raciaux</strong></td>
                                <td><span class="text-success"><?php echo ($raceObject->strength_bonus > 0 ? '+' : '') . $raceObject->strength_bonus; ?></span></td>
                                <td><span class="text-success"><?php echo ($raceObject->dexterity_bonus > 0 ? '+' : '') . $raceObject->dexterity_bonus; ?></span></td>
                                <td><span class="text-success"><?php echo ($raceObject->constitution_bonus > 0 ? '+' : '') . $raceObject->constitution_bonus; ?></span></td>
                                <td><span class="text-success"><?php echo ($raceObject->intelligence_bonus > 0 ? '+' : '') . $raceObject->intelligence_bonus; ?></span></td>
                                <td><span class="text-success"><?php echo ($raceObject->wisdom_bonus > 0 ? '+' : '') . $raceObject->wisdom_bonus; ?></span></td>
                                <td><span class="text-success"><?php echo ($raceObject->charisma_bonus > 0 ? '+' : '') . $raceObject->charisma_bonus; ?></span></td>
                            </tr>
                            <!-- Bonus de niveau -->
                            <tr>
                                <td><strong>Bonus de niveau (<?php echo $remainingPoints; ?> pts restants)</strong></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['strength'] > 0 ? '+' : '') . $abilityImprovementsArray['strength']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['dexterity'] > 0 ? '+' : '') . $abilityImprovementsArray['dexterity']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['constitution'] > 0 ? '+' : '') . $abilityImprovementsArray['constitution']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['intelligence'] > 0 ? '+' : '') . $abilityImprovementsArray['intelligence']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['wisdom'] > 0 ? '+' : '') . $abilityImprovementsArray['wisdom']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovementsArray['charisma'] > 0 ? '+' : '') . $abilityImprovementsArray['charisma']; ?></span></td>
                            </tr>
                            <!-- Bonus d'équipements -->
                            <tr>
                                <td><strong>Bonus d'équipements</strong></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['strength'] > 0 ? '+' : '') . $equipmentBonuses['strength']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['dexterity'] > 0 ? '+' : '') . $equipmentBonuses['dexterity']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['constitution'] > 0 ? '+' : '') . $equipmentBonuses['constitution']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['intelligence'] > 0 ? '+' : '') . $equipmentBonuses['intelligence']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['wisdom'] > 0 ? '+' : '') . $equipmentBonuses['wisdom']; ?></span></td>
                                <td><span class="text-info"><?php echo ($equipmentBonuses['charisma'] > 0 ? '+' : '') . $equipmentBonuses['charisma']; ?></span></td>
                            </tr>
                            <!-- Bonus temporaires -->
                            <tr>
                                <td><strong>Bonus temporaires</strong></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['strength'] > 0 ? '+' : '') . $temporaryBonuses['strength']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['dexterity'] > 0 ? '+' : '') . $temporaryBonuses['dexterity']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['constitution'] > 0 ? '+' : '') . $temporaryBonuses['constitution']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['intelligence'] > 0 ? '+' : '') . $temporaryBonuses['intelligence']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['wisdom'] > 0 ? '+' : '') . $temporaryBonuses['wisdom']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($temporaryBonuses['charisma'] > 0 ? '+' : '') . $temporaryBonuses['charisma']; ?></span></td>
                            </tr>
                            <!-- Total -->
                            <tr class="table-success">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $totalAbilities['strength']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['dexterity']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['constitution']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['intelligence']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['wisdom']; ?></strong></td>
                                <td><strong><?php echo $totalAbilities['charisma']; ?></strong></td>
                            </tr>
                            <!-- Modificateurs -->
                            <tr class="table-primary">
                                <td><strong>Modificateurs</strong></td>
                                <td><strong><?php echo ($strengthModifier >= 0 ? '+' : '') . $strengthModifier; ?></strong></td>
                                <td><strong><?php echo ($dexterityModifier >= 0 ? '+' : '') . $dexterityModifier; ?></strong></td>
                                <td><strong><?php echo ($constitutionModifier >= 0 ? '+' : '') . $constitutionModifier; ?></strong></td>
                                <td><strong><?php echo ($intelligenceModifier >= 0 ? '+' : '') . $intelligenceModifier; ?></strong></td>
                                <td><strong><?php echo ($wisdomModifier >= 0 ? '+' : '') . $wisdomModifier; ?></strong></td>
                                <td><strong><?php echo ($charismaModifier >= 0 ? '+' : '') . $charismaModifier; ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Capacités -->
                <div class="tab-pane fade" id="capabilities" role="tabpanel" aria-labelledby="capabilities-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-star me-2"></i>Capacités</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Toutes les capacités</h5>
                                    <?php if (!empty($allCapabilities)): ?>
                                        <div class="capabilities-list">
                                            <?php foreach ($allCapabilities as $capability): ?>
                                                <div class="capability-item">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <strong class="text-primary"><?php echo htmlspecialchars($capability['name'] ?? 'Capacité inconnue'); ?></strong>
                                                        <?php if (!empty($capability['type_name'])): ?>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($capability['type_name']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($capability['description'])): ?>
                                                        <p class="mb-2 text-muted"><?php echo htmlspecialchars($capability['description']); ?></p>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <?php if (!empty($capability['source'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-tag me-1"></i>Source: <?php echo htmlspecialchars($capability['source']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($capability['learned_at'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($capability['learned_at'])); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-star text-muted fa-3x mb-3"></i>
                                            <p class="text-muted">Aucune capacité assignée à ce PNJ.</p>
                                            <small class="text-muted">Les capacités peuvent être ajoutées via la gestion des PNJ.</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Compétences -->
                <div class="tab-pane fade" id="skills" role="tabpanel" aria-labelledby="skills-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-dice me-2"></i>Compétences</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Compétences maîtrisées</h5>
                                    <?php if (!empty($allSkills)): ?>
                                        <div class="skills-list">
                                            <?php foreach ($allSkills as $skill): ?>
                                                <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars($skill); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucune compétence maîtrisée.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5>Outils et instruments</h5>
                                    <?php if (!empty($allTools)): ?>
                                        <div class="tools-list">
                                            <?php foreach ($allTools as $tool): ?>
                                                <span class="badge bg-secondary me-2 mb-2"><?php echo htmlspecialchars($tool); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucun outil ou instrument maîtrisé.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Langues -->
                <div class="tab-pane fade" id="languages" role="tabpanel" aria-labelledby="languages-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-language me-2"></i>Langues</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Langues parlées</h5>
                                    <?php if (!empty($characterLanguages)): ?>
                                        <div class="languages-list">
                                            <?php foreach ($characterLanguages as $language): ?>
                                                <span class="badge bg-info me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucune langue supplémentaire.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5>Langues de l'historique</h5>
                                    <?php if (!empty($backgroundLanguages)): ?>
                                        <div class="languages-list">
                                            <?php foreach ($backgroundLanguages as $language): ?>
                                                <span class="badge bg-warning me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucune langue de l'historique.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Bourse -->
                <div class="tab-pane fade" id="treasury" role="tabpanel" aria-labelledby="treasury-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-coins me-2"></i>Bourse</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="currency-display"><?php echo $npc->gold ?? 0; ?></div>
                                        <div class="stat-label">Pièces d'or</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="currency-display"><?php echo $npc->silver ?? 0; ?></div>
                                        <div class="stat-label">Pièces d'argent</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="currency-display"><?php echo $npc->copper ?? 0; ?></div>
                                        <div class="stat-label">Pièces de cuivre</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box text-center">
                                        <div class="currency-display"><?php echo $npc->platinum ?? 0; ?></div>
                                        <div class="stat-label">Pièces de platine</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Equipement -->
                <div class="tab-pane fade" id="equipment" role="tabpanel" aria-labelledby="equipment-tab">
                    <div class="p-4">
                        <div class="info-section">
                            <h4><i class="fas fa-backpack me-2"></i>Équipement</h4>
                            
                            <!-- Filtres et contrôles -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un objet..." onkeyup="filterTable()">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="typeFilter" onchange="filterTable()">
                                        <option value="">Tous les types</option>
                                        <option value="weapon">Armes</option>
                                        <option value="armor">Armures</option>
                                        <option value="shield">Boucliers</option>
                                        <option value="magical_item">Objets magiques</option>
                                        <option value="poison">Poisons</option>
                                        <option value="misc">Divers</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="statusFilter" onchange="filterTable()">
                                        <option value="">Tous</option>
                                        <option value="equipped">Équipés</option>
                                        <option value="unequipped">Non équipés</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                        <i class="fas fa-undo me-1"></i>Réinitialiser
                                    </button>
                                </div>
                            </div>

                            <!-- Tableau d'équipement -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="equipmentTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th onclick="sortTable(0)" style="cursor: pointer;">
                                                Nom <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th onclick="sortTable(1)" style="cursor: pointer;">
                                                Type <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th onclick="sortTable(2)" style="cursor: pointer;">
                                                Description <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th>État</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Combiner tous les objets du personnage
                                        $allCharacterItems = array_merge($allMagicalEquipment, $allPoisons);
                                        
                                        // Fonction pour vérifier si un objet existe déjà
                                        function itemExists($items, $name, $type) {
                                            foreach ($items as $item) {
                                                if (($item['item_name'] ?? '') === $name && ($item['item_type'] ?? '') === $type) {
                                                    return true;
                                                }
                                            }
                                            return false;
                                        }
                                        
                                        foreach ($allCharacterItems as $item): 
                                            // Utiliser les champs standardisés
                                            $itemName = $item['item_name'] ?? $item['display_name'] ?? 'Objet inconnu';
                                            $itemType = $item['item_type'] ?? $item['object_type'] ?? 'unknown';
                                            $displayName = htmlspecialchars($itemName);
                                            $typeLabel = ucfirst(str_replace('_', ' ', $itemType));
                                        ?>
                                        <tr data-type="<?php echo $itemType; ?>" data-equipped="<?php echo ($item['is_equipped'] ?? $item['equipped'] ?? false) ? 'equipped' : 'unequipped'; ?>">
                                            <td>
                                                <strong><?php echo $displayName; ?></strong>
                                                <?php if (($item['quantity'] ?? 1) > 1): ?>
                                                    <span class="badge bg-info ms-1">x<?php echo $item['quantity']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($itemType) {
                                                        'weapon' => 'danger',
                                                        'armor' => 'primary', 
                                                        'shield' => 'info',
                                                        'magical_item' => 'success',
                                                        'poison' => 'warning',
                                                        'bag' => 'secondary',
                                                        'tool' => 'info',
                                                        'clothing' => 'light text-dark',
                                                        'consumable' => 'warning',
                                                        'misc' => 'secondary',
                                                        default => 'light text-dark'
                                                    };
                                                ?>">
                                                    <?php echo $typeLabel; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['item_description'] ?? $item['description'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($item['is_equipped'] ?? $item['equipped'] ?? false): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Équipé
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle me-1"></i>Non équipé
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="min-width: 300px; white-space: nowrap; overflow: visible;">
                                                <?php if ($itemType === 'weapon' || $itemType === 'armor' || $itemType === 'shield'): ?>
                                                    <?php if ($item['is_equipped'] ?? $item['equipped'] ?? false): ?>
                                                        <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $npc->id; ?>, '<?php echo addslashes($itemName); ?>')"
                                                                style="white-space: nowrap; min-width: 80px;">
                                                            <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                                        </button>
                                                    <?php else: ?>
                                                        <?php 
                                                        $slot = match($itemType) {
                                                            'weapon' => 'main_hand',
                                                            'armor' => 'armor',
                                                            'shield' => 'off_hand',
                                                            default => 'main_hand'
                                                        };
                                                        ?>
                                                        <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $npc->id; ?>, '<?php echo addslashes($itemName); ?>', '<?php echo $itemType; ?>', '<?php echo $slot; ?>')"
                                                                style="white-space: nowrap; min-width: 80px;">
                                                            <i class="fas fa-hand-rock me-1"></i>Équiper
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Non équipable</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($canModifyHP && !str_starts_with($item['id'] ?? '', 'base_')): ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm ms-1" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#transferModal" 
                                                            data-item-id="<?php echo $item['id'] ?? ''; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($itemName); ?>"
                                                            data-item-type="<?php echo htmlspecialchars($itemType); ?>"
                                                            data-source="character_equipment"
                                                            style="white-space: nowrap; min-width: 80px;">
                                                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                                                    </button>
                                                    <?php if (!($item['is_equipped'] ?? $item['equipped'] ?? false)): ?>
                                                        <button type="button" class="btn btn-outline-warning btn-sm ms-1" 
                                                                onclick="dropItem(<?php echo $item['id'] ?? 0; ?>, '<?php echo addslashes($item['item_name'] ?? ''); ?>')"
                                                                title="Déposer l'objet dans le lieu actuel"
                                                                style="white-space: nowrap; min-width: 80px;">
                                                            <i class="fas fa-hand-holding me-1"></i>Déposer
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($allCharacterItems)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                Aucun objet trouvé
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Info perso. -->
                <div class="tab-pane fade" id="personal-info" role="tabpanel" aria-labelledby="personal-info-tab">
                    <div class="p-4">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-book me-2"></i>Histoire du Personnage</h3>
                                <p class="mb-0 text-muted">L'histoire, la personnalité et les traits de ce PNJ</p>
                            </div>
                            <div class="card-body">
                                <?php if ($npc->personality_traits || $npc->ideals || $npc->bonds || $npc->flaws): ?>
                                    <!-- Traits de personnalité -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5><i class="fas fa-heart me-2"></i>Traits de personnalité</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted mb-3">
                                                Les traits de personnalité de ce PNJ qui l'aident à prendre des décisions 
                                                et à interagir avec les autres.
                                            </p>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Traits de personnalité</label>
                                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                                            <?php if ($npc->personality_traits): ?>
                                                                <?php echo nl2br(htmlspecialchars($npc->personality_traits)); ?>
                                                            <?php else: ?>
                                                                <em class="text-muted">Aucun trait de personnalité défini</em>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="form-text">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Comment ce PNJ se comporte-t-il ? Qu'est-ce qui le caractérise ?
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Idéaux</label>
                                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                                            <?php if ($npc->ideals): ?>
                                                                <?php echo nl2br(htmlspecialchars($npc->ideals)); ?>
                                                            <?php else: ?>
                                                                <em class="text-muted">Aucun idéal défini</em>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="form-text">
                                                            <i class="fas fa-star me-1"></i>
                                                            Quelles valeurs sont importantes pour ce PNJ ?
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Liens</label>
                                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                                            <?php if ($npc->bonds): ?>
                                                                <?php echo nl2br(htmlspecialchars($npc->bonds)); ?>
                                                            <?php else: ?>
                                                                <em class="text-muted">Aucun lien défini</em>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="form-text">
                                                            <i class="fas fa-link me-1"></i>
                                                            À quoi ou à qui ce PNJ est-il attaché ?
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Défauts</label>
                                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                                            <?php if ($npc->flaws): ?>
                                                                <?php echo nl2br(htmlspecialchars($npc->flaws)); ?>
                                                            <?php else: ?>
                                                                <em class="text-muted">Aucun défaut défini</em>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="form-text">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Quelles sont les faiblesses ou les vices de ce PNJ ?
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-user-edit text-muted fa-3x mb-3"></i>
                                        <p class="text-muted">Aucune information personnelle définie pour ce PNJ.</p>
                                        <small class="text-muted">Les traits de personnalité, idéaux, liens et défauts peuvent être ajoutés lors de la création ou modification du PNJ.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals existants -->
    <?php if ($canModifyHP): ?>
    <div class="modal fade" id="hpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-heart me-2"></i>
                        Gestion des Points de Vie - <?php echo htmlspecialchars($npc->name); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Barre de Points de Vie -->
                    <div class="mb-4">
                        <h6>Points de Vie Actuels</h6>
                        <?php
                        $current_hp = $npc->hit_points_current;
                        $max_hp = $npc->hit_points_max;
                        $hp_percentage = $max_hp > 0 ? ($current_hp / $max_hp) * 100 : 100;
                        $hp_class = $hp_percentage > 50 ? 'bg-success' : ($hp_percentage > 25 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="progress mb-2 progress-bar-custom">
                            <div class="progress-bar <?php echo $hp_class; ?>" role="progressbar" style="width: <?php echo $hp_percentage; ?>%">
                                <?php echo $current_hp; ?>/<?php echo $max_hp; ?>
                            </div>
                        </div>
                        <small class="text-muted"><?php echo round($hp_percentage, 1); ?>% des points de vie restants</small>
                    </div>

                    <!-- Actions Rapides -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-sword text-danger me-2"></i>Infliger des Dégâts</h6>
                            <div class="d-flex gap-2 mb-2">
                                <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="1" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-1</button>
                                <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="5" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-5</button>
                                <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="10" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-10</button>
                                <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="20" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-20</button>
                            </div>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="hp_action" value="damage">
                                <input type="number" name="damage" class="form-control form-control-sm" placeholder="Dégâts" min="1" required>
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-heart text-success me-2"></i>Appliquer des Soins</h6>
                            <div class="d-flex gap-2 mb-2">
                                <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="1" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+1</button>
                                <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="5" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+5</button>
                                <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="10" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+10</button>
                                <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="20" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+20</button>
                            </div>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="hp_action" value="heal">
                                <input type="number" name="healing" class="form-control form-control-sm" placeholder="Soins" min="1" required>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Actions Avancées -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                            <form method="POST">
                                <input type="hidden" name="hp_action" value="update_hp">
                                <input type="hidden" name="max_hp" value="<?php echo $npc->hit_points_max; ?>">
                                <div class="d-flex gap-2">
                                    <input type="number" name="current_hp" class="form-control form-control-sm" 
                                           value="<?php echo $npc->hit_points_current; ?>" 
                                           min="0" max="<?php echo $npc->hit_points_max; ?>" required>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Maximum : <?php echo $npc->hit_points_max; ?> PV</small>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-redo text-info me-2"></i>Réinitialiser</h6>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="hp_action" value="reset_hp">
                                <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Réinitialiser les points de vie au maximum ?')">
                                    <i class="fas fa-redo me-2"></i>
                                    Remettre au Maximum
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal pour Gestion des Points d'Expérience -->
    <?php if ($canModifyHP): ?>
    <div class="modal fade" id="xpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2"></i>
                        Gestion des Points d'Expérience - <?php echo htmlspecialchars($npc->name); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Affichage des Points d'Expérience Actuels -->
                    <div class="mb-4">
                        <h6>Points d'Expérience Actuels</h6>
                        <div class="alert alert-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo number_format($npc->experience ?? 0); ?> XP</strong>
                                    <br>
                                    <small class="text-muted">Niveau <?php echo $npc->level; ?></small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-star fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Rapides -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-minus text-danger me-2"></i>Retirer des Points d'Expérience</h6>
                            <div class="d-flex gap-2 mb-2">
                                <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-100" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-100</button>
                                <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-500" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-500</button>
                                <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-1000" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-1000</button>
                            </div>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="xp_action" value="remove">
                                <input type="number" name="xp_amount" class="form-control form-control-sm" placeholder="Points à retirer" min="1" required>
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-plus text-success me-2"></i>Ajouter des Points d'Expérience</h6>
                            <div class="d-flex gap-2 mb-2">
                                <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="100" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+100</button>
                                <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="500" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+500</button>
                                <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="1000" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+1000</button>
                            </div>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="xp_action" value="add">
                                <input type="number" name="xp_amount" class="form-control form-control-sm" placeholder="Points à ajouter" min="1" required>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Action Avancée -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                            <form method="POST">
                                <input type="hidden" name="xp_action" value="set">
                                <div class="d-flex gap-2">
                                    <input type="number" name="xp_amount" class="form-control" 
                                           value="<?php echo $npc->experience ?? 0; ?>" 
                                           min="0" required>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>
                                        Définir
                                    </button>
                                </div>
                                <small class="text-muted">Définir directement le nombre total de points d'expérience</small>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal pour Transfert d'Objets -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Transférer un Objet Magique
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Objet :</strong> <span id="transferItemName"></span><br>
                        <strong>Propriétaire actuel :</strong> <span id="transferCurrentOwner"></span>
                    </div>
                    
                    <form id="transferForm" method="POST">
                        <input type="hidden" name="action" value="transfer_item">
                        <input type="hidden" name="item_id" id="transferItemId">
                        <input type="hidden" name="current_owner" id="transferCurrentOwnerType">
                        <input type="hidden" name="source" id="transferSource">
                        
                        <div class="mb-3">
                            <label for="transferTarget" class="form-label">Transférer vers :</label>
                            <select class="form-select" name="target" id="transferTarget" required>
                                <option value="">Sélectionner une cible...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transferNotes" class="form-label">Notes (optionnel) :</label>
                            <textarea class="form-control" name="notes" id="transferNotes" rows="3" placeholder="Raison du transfert, conditions, etc."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" data-action="confirm-transfer">
                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour Upload de Photo de Profil -->
    <?php if ($canModifyHP): ?>
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera me-2"></i>
                        Changer la Photo de Profil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="photoForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_photo">
                        
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Sélectionner une nouvelle photo :</label>
                            <input type="file" class="form-control" name="profile_photo" id="profile_photo" accept="image/*" required>
                            <div class="form-text">
                                Formats acceptés : JPG, PNG, GIF (max 10MB)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Conseil :</strong> Pour un meilleur rendu, utilisez une image carrée ou rectangulaire.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" data-action="upload-photo" data-npc-id="<?php echo $npc_id; ?>" data-entity-type="PNJ">
                        <i class="fas fa-upload me-1"></i>Uploader
                    </button>
                </div>
            </div>
        </div>
    </div>
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
