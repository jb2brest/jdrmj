<?php
require_once 'classes/init.php';
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

// Convertir en tableau pour compatibilité
$npcData = $npc->toArray();

// Vérifier les permissions (créateur ou DM)
$isOwner = ($npcData['created_by'] == $_SESSION['user_id']);
$isDM = isDM();

if (!$isOwner && !$isDM) {
    header('Location: manage_npcs.php');
    exit();
}

// Utiliser directement les données du PNJ
$character = $npcData;


// Récupérer les détails de la race via la classe Race
$raceObject = Race::findById($character['race_id']);
$raceDetails = $raceObject ? $raceObject->toArray() : [];

// Récupérer les détails de la classe via la classe Classe
$classObject = Classe::findById($character['class_id']);
$classDetails = $classObject ? $classObject->toArray() : [];

// Récupérer les détails du background
$backgroundDetails = null;
if ($character['background_id']) {
    $backgroundDetails = NPC::getBackgroundById($character['background_id']);
}

// Construire le tableau characterDetails pour la compatibilité
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

if (!$characterDetails) {
    header('Location: characters.php');
    exit();
}

// Parser les données JSON du personnage
$characterSkills = $character['skills'] ? json_decode($character['skills'], true) : [];
$characterLanguages = $character['languages'] ? json_decode($character['languages'], true) : [];

// Parser les données de l'historique depuis la table backgrounds
$backgroundSkills = $characterDetails['background_skills'] ? json_decode($characterDetails['background_skills'], true) : [];
$backgroundTools = $characterDetails['background_tools'] ? json_decode($characterDetails['background_tools'], true) : [];
$backgroundLanguages = $characterDetails['background_languages'] ? json_decode($characterDetails['background_languages'], true) : [];

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
$isBarbarian = strpos(strtolower($characterDetails['class_name']), 'barbare') !== false;
$isBard = strpos(strtolower($characterDetails['class_name']), 'barde') !== false;
$isCleric = strpos(strtolower($characterDetails['class_name']), 'clerc') !== false;
$isDruid = strpos(strtolower($characterDetails['class_name']), 'druide') !== false;
$isSorcerer = strpos(strtolower($characterDetails['class_name']), 'ensorceleur') !== false;
$isFighter = strpos(strtolower($characterDetails['class_name']), 'guerrier') !== false;
$isWizard = strpos(strtolower($characterDetails['class_name']), 'magicien') !== false;
$isMonk = strpos(strtolower($characterDetails['class_name']), 'moine') !== false;
$isWarlock = strpos(strtolower($characterDetails['class_name']), 'occultiste') !== false;
$isPaladin = strpos(strtolower($characterDetails['class_name']), 'paladin') !== false;
$isRanger = strpos(strtolower($characterDetails['class_name']), 'rôdeur') !== false;
$isRogue = strpos(strtolower($characterDetails['class_name']), 'roublard') !== false;
$rageData = null;
if ($isBarbarian) {
    // Récupérer le nombre maximum de rages pour ce niveau
    $maxRages = NPC::getMaxRages($character['class_id'], $character['level']);
    
    // Récupérer le nombre de rages utilisées
    $rageUsage = NPC::getRageUsageStatic($npc_id);
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
if ($character['archetype_id']) {
    $archetypeObject = ClassArchetype::findById($character['archetype_id']);
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
$abilityImprovements = NPC::getCharacterAbilityImprovements($npc_id);

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
$finalAbilities = NPC::calculateFinalAbilitiesStatic($character, $abilityImprovements);

// Calculer les points d'amélioration restants
$remainingPoints = NPC::getRemainingAbilityPoints($character['level'], $abilityImprovements);

// Les langues du personnage sont déjà stockées dans le champ 'languages' 
// et incluent toutes les langues (race + historique + choix)
$allLanguages = $npcLanguages;

// Calcul des modificateurs (nécessaire pour le calcul de la CA)
// Utiliser les valeurs totales incluant les bonus raciaux
$tempCharacter = new Character();
$tempCharacter->strength = $character['strength'] + $characterDetails['strength_bonus'];
$tempCharacter->dexterity = $character['dexterity'] + $characterDetails['dexterity_bonus'];
$tempCharacter->constitution = $character['constitution'] + $characterDetails['constitution_bonus'];
$tempCharacter->intelligence = $character['intelligence'] + $characterDetails['intelligence_bonus'];
$tempCharacter->wisdom = $character['wisdom'] + $characterDetails['wisdom_bonus'];
$tempCharacter->charisma = $character['charisma'] + $characterDetails['charisma_bonus'];

// Les modificateurs seront calculés plus tard avec les totaux complets

// Synchroniser l'équipement de base vers items
NPC::syncBaseEquipmentToCharacterEquipment($npc_id);

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
$npcItems = NPC::getEquipment($npc_id);

// Traiter les équipements du PNJ
foreach ($npcItems as $item) {
    $item['equipped'] = true; // Pour les PNJ, tous les équipements sont considérés comme équipés
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

// Décoder l'équipement de départ du PNJ (format JSON - pour compatibilité)
if (!empty($character['starting_equipment'])) {
    $equipmentData = json_decode($character['starting_equipment'], true);
    if ($equipmentData && isset($equipmentData['equipment'])) {
        // Récupérer les détails des équipements
        foreach ($equipmentData['equipment'] as $equipmentId) {
            $stmt = $pdo->prepare("
                SELECT i.*, 
                       i.display_name as item_name,
                       i.description as item_description,
                       i.object_type as item_type,
                       i.is_equipped as equipped
                FROM items i
                WHERE i.id = ?
            ");
            $stmt->execute([$equipmentId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($item) {
                $item['equipped'] = true; // Pour les PNJ, tous les équipements sont considérés comme équipés
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
        }
    }
}

// Récupérer l'or du PNJ
$npcGold = 0;
if (isset($character['gold'])) {
    $npcGold = $character['gold'];
}

// Récupérer l'or depuis les données JSON (pour compatibilité)
if (!empty($character['starting_equipment'])) {
    $equipmentData = json_decode($character['starting_equipment'], true);
    if ($equipmentData && isset($equipmentData['gold'])) {
        $npcGold = $equipmentData['gold'];
    }
}

// Construire le texte d'équipement à partir des équipements du PNJ
$equipmentText = '';
foreach ($magicalEquipment as $item) {
    if (isset($item['equipped']) && $item['equipped']) {
        $equipmentText .= $item['item_name'] . ', ';
    }
}
$equipmentText = rtrim($equipmentText, ', ');

// Détecter les armes, armures et boucliers dans l'équipement
$detectedWeapons = Item::detectWeaponsInEquipment($equipmentText);
$detectedArmor = Item::detectArmorInEquipment($equipmentText);
$detectedShields = Item::detectShieldsInEquipment($equipmentText);

// Calculer la classe d'armure
$equippedArmor = null;
$equippedShield = null;

// Chercher l'armure équipée dans character_equipment
foreach ($magicalEquipment as $item) {
    if ($item['equipped'] && ($item['item_type'] ?? '') === 'armor') {
        foreach ($detectedArmor as $armor) {
            if (stripos($item['item_name'], $armor['name']) !== false) {
                $equippedArmor = $armor;
                break 2;
            }
        }
    }
}

// Chercher le bouclier équipé dans character_equipment
foreach ($magicalEquipment as $item) {
    if ($item['equipped'] && ($item['item_type'] ?? '') === 'shield') {
        foreach ($detectedShields as $shield) {
            if (stripos($item['item_name'], $shield['name']) !== false) {
                $equippedShield = $shield;
                break 2;
            }
        }
    }
}

// Ajouter les modificateurs de caractéristiques au tableau character pour la fonction
$character['strength_modifier'] = $strengthMod;
$character['dexterity_modifier'] = $dexterityMod;
$character['constitution_modifier'] = $constitutionMod;
$character['intelligence_modifier'] = $intelligenceMod;
$character['wisdom_modifier'] = $wisdomMod;
$character['charisma_modifier'] = $charismaMod;

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
    'strength' => $character['strength'] + $characterDetails['strength_bonus'] + $abilityImprovementsArray['strength'] + $equipmentBonuses['strength'] + $temporaryBonuses['strength'],
    'dexterity' => $character['dexterity'] + $characterDetails['dexterity_bonus'] + $abilityImprovementsArray['dexterity'] + $equipmentBonuses['dexterity'] + $temporaryBonuses['dexterity'],
    'constitution' => $character['constitution'] + $characterDetails['constitution_bonus'] + $abilityImprovementsArray['constitution'] + $equipmentBonuses['constitution'] + $temporaryBonuses['constitution'],
    'intelligence' => $character['intelligence'] + $characterDetails['intelligence_bonus'] + $abilityImprovementsArray['intelligence'] + $equipmentBonuses['intelligence'] + $temporaryBonuses['intelligence'],
    'wisdom' => $character['wisdom'] + $characterDetails['wisdom_bonus'] + $abilityImprovementsArray['wisdom'] + $equipmentBonuses['wisdom'] + $temporaryBonuses['wisdom'],
    'charisma' => $character['charisma'] + $characterDetails['charisma_bonus'] + $abilityImprovementsArray['charisma'] + $equipmentBonuses['charisma'] + $temporaryBonuses['charisma']
];

// Calculer les modificateurs avec les totaux complets
$tempCharacter->strength = $totalAbilities['strength'];
$tempCharacter->dexterity = $totalAbilities['dexterity'];
$tempCharacter->constitution = $totalAbilities['constitution'];
$tempCharacter->intelligence = $totalAbilities['intelligence'];
$tempCharacter->wisdom = $totalAbilities['wisdom'];
$tempCharacter->charisma = $totalAbilities['charisma'];

$strengthMod = $tempCharacter->getAbilityModifier('strength');
$dexterityMod = $tempCharacter->getAbilityModifier('dexterity');
$constitutionMod = $tempCharacter->getAbilityModifier('constitution');
$intelligenceMod = $tempCharacter->getAbilityModifier('intelligence');
$wisdomMod = $tempCharacter->getAbilityModifier('wisdom');
$charismaMod = $tempCharacter->getAbilityModifier('charisma');

// Calculer les attaques du personnage
$characterAttacks = NPC::calculateCharacterAttacks($npc_id, $character);
$armorClass = NPC::calculateArmorClassExtended($character, $equippedArmor, $equippedShield);


// Contrôle d'accès: propriétaire OU MJ
$canView = ($character['created_by'] == $_SESSION['user_id']);

if (!$canView && User::isDMOrAdmin()) {
    // Les MJ et admins peuvent voir tous les PNJ
    $canView = true;
}

if (!$canView) {
    header('Location: manage_npcs.php');
    exit();
}

// Vérifier si l'utilisateur peut modifier les points de vie (propriétaire ou MJ)
$canModifyHP = ($character['created_by'] == $_SESSION['user_id']);
if (!$canModifyHP && User::isDMOrAdmin()) {
    // Les MJ et admins peuvent modifier tous les PNJ
    $canModifyHP = true;
}

$success_message = '';
$error_message = '';

// Traitement des actions POST pour la gestion des points de vie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['hp_action'])) {
    switch ($_POST['hp_action']) {
        case 'update_hp':
            $new_hp = (int)$_POST['current_hp'];
            $max_hp = (int)$_POST['max_hp'];
            
            // Valider les points de vie
            if ($new_hp < 0) {
                $new_hp = 0;
            }
            if ($new_hp > $max_hp) {
                $new_hp = $max_hp;
            }
            
            // Mettre à jour les points de vie actuels
            NPC::updateHitPoints($npc_id, $new_hp);
            
            $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
            break;
            
        case 'damage':
            $damage = (int)$_POST['damage'];
            if ($damage > 0) {
                $new_hp = max(0, $character['hit_points_current'] - $damage);
                NPC::updateHitPoints($npc_id, $new_hp);
                
                $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
            }
            break;
            
        case 'heal':
            $healing = (int)$_POST['healing'];
            if ($healing > 0) {
                $new_hp = min($character['hit_points_max'], $character['hit_points_current'] + $healing);
                NPC::updateHitPoints($npc_id, $new_hp);
                
                $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
            }
            break;
            
        case 'reset_hp':
            NPC::updateHitPoints($npc_id, $character['hit_points_max']);
            
            $success_message = "Points de vie réinitialisés au maximum : {$character['hit_points_max']}";
            break;
    }
    
    // Recharger les données du personnage
    // Récupérer les détails du personnage via la classe NPC
    $characterObj = NPC::findById($npc_id, $pdo);
    if (!$characterObj) {
        header('Location: characters.php?error=character_not_found');
        exit;
    }

    // Convertir l'objet en tableau pour la compatibilité
    $character = $characterObj->toArray();
}

// Traitement des actions POST pour la gestion des points d'expérience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['xp_action'])) {
    switch ($_POST['xp_action']) {
        case 'add':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = ($character['experience'] ?? 0) + $xp_amount;
                NPC::updateExperiencePoints($npc_id, $new_xp);
                
                $success_message = "Points d'expérience ajoutés : +{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'remove':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = max(0, ($character['experience'] ?? 0) - $xp_amount);
                NPC::updateExperiencePoints($npc_id, $new_xp);
                
                $success_message = "Points d'expérience retirés : -{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'set':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount >= 0) {
                NPC::updateExperiencePoints($npc_id, $xp_amount);
                
                $success_message = "Points d'expérience définis à : " . number_format($xp_amount) . " XP";
            }
            break;
    }
    
    // Recharger les données du personnage après modification des XP
    if (isset($success_message)) {
        $characterObj = NPC::findById($npc_id, $pdo);
        if ($characterObj) {
            $character = $characterObj->toArray();
        }
    }
}

// Traitement du transfert d'objets magiques
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['action']) && $_POST['action'] === 'transfer_item') {
    $item_id = (int)$_POST['item_id'];
    $target = $_POST['target'];
    $notes = $_POST['notes'] ?? '';
    $source = $_POST['source'] ?? 'items';
    
    // Récupérer les informations de l'objet à transférer selon la source
    $item = null;
    if ($source === 'npc_equipment') {
        // Récupérer depuis npc_equipment via le personnage associé
        // Récupérer l'équipement du PNJ via la classe PNJ
        $item = PNJ::getNpcEquipmentWithDetails($item_id, $npc_id);
    } else {
        // Récupérer depuis items via la classe Item
        $itemObj = Item::findByIdAndOwner($item_id, 'player', $npc_id);
        $item = $itemObj ? $itemObj->toArray() : null;
    }
    
    if (!$item) {
        $error_message = "Objet introuvable.";
    } else {
        // Analyser la cible
        $target_parts = explode('_', $target);
        $target_type = $target_parts[0];
        $target_id = (int)$target_parts[1];
        
        $transfer_success = false;
        $target_name = '';
        
        switch ($target_type) {
            case 'character':
                // Transférer vers un autre personnage
                $target_char_obj = NPC::findById($target_id, $pdo);
                $target_char = $target_char_obj ? ['name' => $target_char_obj->name] : null;
                
                if ($target_char) {
                    // Insérer dans items du nouveau propriétaire via la classe Item
                    $itemData = [
                        'place_id' => null,
                        'display_name' => $item['display_name'],
                        'object_type' => $item['object_type'],
                        'type_precis' => $item['type_precis'],
                        'description' => $item['description'],
                        'is_identified' => $item['is_identified'],
                        'is_visible' => false, // Les objets d'équipement ne sont pas visibles sur la carte
                        'is_equipped' => false, // Toujours non équipé lors du transfert
                        'position_x' => 0,
                        'position_y' => 0,
                        'is_on_map' => false,
                        'owner_type' => 'player',
                        'owner_id' => $target_id,
                        'poison_id' => $item['poison_id'] ?: null,
                        'weapon_id' => $item['weapon_id'] ?: null,
                        'armor_id' => $item['armor_id'] ?: null,
                        'gold_coins' => (int)($item['gold_coins'] ?: 0),
                        'silver_coins' => (int)($item['silver_coins'] ?: 0),
                        'copper_coins' => (int)($item['copper_coins'] ?: 0),
                        'letter_content' => $item['letter_content'],
                        'is_sealed' => $item['is_sealed'] ?: false,
                        'magical_item_id' => $item['magical_item_id'],
                        'item_source' => $item['item_source'],
                        'quantity' => (int)($item['quantity'] ?: 1),
                        'equipped_slot' => $item['equipped_slot'],
                        'notes' => $notes ?: $item['notes'],
                        'obtained_at' => $item['obtained_at'],
                        'obtained_from' => 'Transfert depuis ' . $character['name']
                    ];
                    
                    Item::createExtended($itemData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        PNJ::removeEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_char['name'];
                }
                break;
                
            case 'monster':
                // Transférer vers un monstre
                $target_monster = PNJ::getNpcInfoInPlace($target_id);
                
                if ($target_monster) {
                    // Insérer dans monster_equipment via la classe Monstre
                    $equipmentData = [
                        'magical_item_id' => $item['magical_item_id'],
                        'item_name' => $item['display_name'],
                        'item_type' => $item['object_type'],
                        'item_description' => $item['description'],
                        'item_source' => $item['item_source'],
                        'quantity' => $item['quantity'],
                        'equipped' => false, // Toujours non équipé lors du transfert
                        'notes' => $notes ?: $item['notes'],
                        'obtained_from' => 'Transfert depuis ' . $character['name']
                    ];
                    
                    Monstre::addMonsterEquipment($target_id, $target_monster['place_id'], $equipmentData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        PNJ::removeEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_monster['name'];
                }
                break;
                
            case 'npc':
                // Transférer vers un PNJ
                $target_npc = PNJ::getNpcInfoInPlace($target_id);
                
                if ($target_npc) {
                    // Insérer dans npc_equipment via la classe PNJ
                    $equipmentData = [
                        'magical_item_id' => $item['magical_item_id'],
                        'item_name' => $item['display_name'],
                        'item_type' => $item['object_type'],
                        'item_description' => $item['description'],
                        'item_source' => $item['item_source'],
                        'quantity' => $item['quantity'],
                        'equipped' => 0, // Toujours non équipé lors du transfert
                        'notes' => $notes ?: $item['notes'],
                        'obtained_from' => 'Transfert depuis ' . $character['name']
                    ];
                    
                    PNJ::addEquipmentToNpc($target_id, $target_npc['place_id'], $equipmentData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        PNJ::removeEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_npc['name'];
                }
                break;
        }
        
        if ($transfer_success) {
            $success_message = "Objet '{$item['display_name']}' transféré vers {$target_name} avec succès.";
        } else {
            $error_message = "Erreur lors du transfert de l'objet.";
        }
    }
    
    // Recharger les données du personnage
    $characterObj = NPC::findById($npc_id, $pdo);
    if ($characterObj) {
        $character = $characterObj->toArray();
    }
}

// Traitement de l'upload de photo de profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_size = $_FILES['profile_photo']['size'];
            if ($file_size <= 10 * 1024 * 1024) { // 10MB max
                $new_filename = 'profile_' . $npc_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne photo si elle existe
                    if (!empty($character['profile_photo']) && file_exists($character['profile_photo'])) {
                        unlink($character['profile_photo']);
                    }
                    
                    // Mettre à jour la base de données
                    $db = Database::getInstance();
                    $stmt = $db->prepare("UPDATE npcs SET profile_photo = ? WHERE id = ?");
                    if ($stmt->execute([$upload_path, $npc_id])) {
                        $success_message = "Photo de profil mise à jour avec succès.";
                        // Recharger les données du PNJ
                        $npc = NPC::findById($npc_id, $pdo);
                        if ($npc) {
                            $character = $npc->toArray();
                        }
                    } else {
                        $error_message = "Erreur lors de la mise à jour de la base de données.";
                    }
                } else {
                    $error_message = "Erreur lors de l'upload de la photo.";
                }
            } else {
                $error_message = "La photo est trop volumineuse (max 10MB).";
            }
        } else {
            $error_message = "Format de fichier non supporté. Utilisez JPG, PNG ou GIF.";
        }
    } else {
        $error_message = "Aucun fichier sélectionné ou erreur lors de l'upload.";
    }
}

// Récupérer l'équipement depuis la table items pour les nouveaux PNJ via la classe NPC
$npcMagicalEquipment = [];
$npcItems = NPC::getEquipment($npc_id);

foreach ($npcItems as $item) {
    $item['equipped'] = true; // Pour les PNJ, tous les équipements sont considérés comme équipés
    $npcMagicalEquipment[] = $item;
}

// Récupérer les poisons du personnage via la classe NPC
$characterPoisons = NPC::getCharacterPoisons($npc_id);

// Récupérer l'équipement attribué aux PNJ associés à ce personnage via la classe PNJ
$npcEquipment = PNJ::getNpcEquipmentByCharacter($npc_id);

// Séparer les objets magiques et poisons des PNJ
$npcMagicalEquipment = [];
$npcPoisons = [];

foreach ($npcEquipment as $item) {
    // Vérifier d'abord si c'est un poison
    $poison_info = NPC::getPoisonInfo($item['magical_item_id']);
    
    if ($poison_info) {
        // C'est un poison
        $item['poison_nom'] = $poison_info['nom'];
        $item['poison_type'] = $poison_info['type'];
        $item['poison_description'] = $poison_info['description'];
        $item['poison_source'] = $poison_info['source'];
        $npcPoisons[] = $item;
    } else {
        // Vérifier si c'est un objet magique
        $magical_info = NPC::getMagicalItemInfo($item['magical_item_id']);
        
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
$initiative = $dexterityMod;

// La classe d'armure est déjà calculée plus haut avec calculateArmorClassExtended()
// $armorClass = $character['armor_class']; // Cette ligne écrasait le calcul correct
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($character['name']); ?> - JDR 4 MJ</title>
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
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        
        .rage-symbol.available {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border-color: #dc3545;
            color: white;
        }
        
        .rage-symbol.used {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-color: #6c757d;
            color: #adb5bd;
            opacity: 0.6;
        }
        
        .rage-symbol.used:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
        }
        
        .rage-info {
            text-align: center;
        }
        
        /* Styles pour les capacités */
        .capabilities-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .capability-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .capability-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .capability-header h6 {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .capability-description {
            line-height: 1.5;
        }
        
        .capability-item .text-primary {
            color: #007bff !important;
        }
        
        .capability-item .text-success {
            color: #28a745 !important;
        }
        
        .capability-item .text-warning {
            color: #ffc107 !important;
        }
        
        /* Styles pour les compétences */
        .skills-list .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .skills-list .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
            transform: translateX(5px);
        }
        
        .skills-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .skills-list .list-group-item.active h6 {
            color: white;
        }
        
        .skills-list .list-group-item.active small {
            color: rgba(255, 255, 255, 0.8);
        }
        
        #skill-detail {
            min-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        #skill-detail .card-body {
            padding: 20px;
        }
        
        #skill-detail h6 {
            color: #0d6efd;
            margin-bottom: 15px;
        }
        
        #skill-detail ul {
            padding-left: 20px;
        }
        
        #skill-detail li {
            margin-bottom: 5px;
        }
        
        /* Styles pour les capacités */
        .capabilities-list .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .capabilities-list .list-group-item:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
            transform: translateX(5px);
        }
        
        .capabilities-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .capabilities-list .list-group-item.active h6 {
            color: white;
        }
        
        .capabilities-list .list-group-item.active small {
            color: rgba(255, 255, 255, 0.8);
        }
        
        #capability-detail {
            min-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        #capability-detail .card-body {
            padding: 20px;
        }
        
        #capability-detail h6 {
            color: #0d6efd;
            margin-bottom: 15px;
        }
    </style>
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
                <i class="fas fa-user-ninja me-2"></i><?php echo htmlspecialchars($character['name']); ?>
            </h1>
            <div>
                <a href="manage_npcs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux PNJ
                </a>
            </div>
        </div>

        <div class="character-sheet">
            <!-- En-tête du personnage -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="me-3 position-relative">
                            <?php if (!empty($character['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($character['name']); ?>" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user text-white" style="font-size: 2.5rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($canModifyHP): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="bottom: -5px; right: -5px;" data-bs-toggle="modal" data-bs-target="#photoModal" title="Changer la photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($character['name']); ?></h2>
                            <p class="text-muted">
                                <?php echo htmlspecialchars($characterDetails['race_name']); ?> 
                                <?php echo htmlspecialchars($characterDetails['class_name']); ?> 
                                niveau <?php echo $character['level']; ?>
                            </p>
                            <?php if ($characterDetails['background_name']): ?>
                                <p><strong>Historique:</strong> <?php echo htmlspecialchars($characterDetails['background_name']); ?></p>
                            <?php endif; ?>
                            <?php if ($character['alignment']): ?>
                                <p><strong>Alignement:</strong> <?php echo htmlspecialchars($character['alignment']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($characterArchetype): ?>
                                <p><strong><?php echo htmlspecialchars($characterArchetype['archetype_type']); ?>:</strong> <?php echo htmlspecialchars($characterArchetype['name']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="hp-display"><?php echo $character['hit_points']; ?>/<?php echo $character['hit_points']; ?></div>
                                <div class="stat-label">Points de Vie</div>
                                <?php if ($canModifyHP): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#hpModal" title="Gérer les points de vie">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="ac-display">&nbsp;<?php echo $armorClass; ?></div>
                                <div class="stat-label">Classe d'Armure</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <?php if ($canModifyHP): ?>
                                    <div class="xp-display clickable-xp" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience" style="cursor: pointer;">&nbsp;<?php echo number_format($character['experience'] ?? 0); ?></div>
                                <?php else: ?>
                                    <div class="xp-display">&nbsp;<?php echo number_format($character['experience'] ?? 0); ?></div>
                                <?php endif; ?>
                                <div class="stat-label">Exp.</div>
                                <small class="text-muted">Niveau <?php echo $character['level']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caractéristiques -->
            <div class="info-section">
                <h3><i class="fas fa-dumbbell me-2"></i>Caractéristiques</h3>
                
                <!-- Tableau des caractéristiques -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 20%;">Type</th>
                                <th style="width: 13.33%;">Force</th>
                                <th style="width: 13.33%;">Dextérité</th>
                                <th style="width: 13.33%;">Constitution</th>
                                <th style="width: 13.33%;">Intelligence</th>
                                <th style="width: 13.33%;">Sagesse</th>
                                <th style="width: 13.33%;">Charisme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Caractéristiques de base -->
                            <tr>
                                <td><strong>Caractéristiques de base</strong></td>
                                <td><strong><?php echo $character['strength']; ?></strong></td>
                                <td><strong><?php echo $character['dexterity']; ?></strong></td>
                                <td><strong><?php echo $character['constitution']; ?></strong></td>
                                <td><strong><?php echo $character['intelligence']; ?></strong></td>
                                <td><strong><?php echo $character['wisdom']; ?></strong></td>
                                <td><strong><?php echo $character['charisma']; ?></strong></td>
                            </tr>
                            <!-- Bonus raciaux -->
                            <tr>
                                <td><strong>Bonus raciaux</strong></td>
                                <td><span class="text-success"><?php echo ($characterDetails['strength_bonus'] > 0 ? '+' : '') . $characterDetails['strength_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['dexterity_bonus'] > 0 ? '+' : '') . $characterDetails['dexterity_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['constitution_bonus'] > 0 ? '+' : '') . $characterDetails['constitution_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['intelligence_bonus'] > 0 ? '+' : '') . $characterDetails['intelligence_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['wisdom_bonus'] > 0 ? '+' : '') . $characterDetails['wisdom_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($characterDetails['charisma_bonus'] > 0 ? '+' : '') . $characterDetails['charisma_bonus']; ?></span></td>
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
                                <td><strong><?php echo ($strengthMod >= 0 ? '+' : '') . $strengthMod; ?></strong></td>
                                <td><strong><?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?></strong></td>
                                <td><strong><?php echo ($constitutionMod >= 0 ? '+' : '') . $constitutionMod; ?></strong></td>
                                <td><strong><?php echo ($intelligenceMod >= 0 ? '+' : '') . $intelligenceMod; ?></strong></td>
                                <td><strong><?php echo ($wisdomMod >= 0 ? '+' : '') . $wisdomMod; ?></strong></td>
                                <td><strong><?php echo ($charismaMod >= 0 ? '+' : '') . $charismaMod; ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rages (pour les barbares) -->
            <?php if ($isBarbarian && $rageData): ?>
            <div class="info-section">
                <h3><i class="fas fa-fire me-2"></i>Rages</h3>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="rage-container">
                            <div class="rage-symbols">
                                <?php for ($i = 1; $i <= $rageData['max']; $i++): ?>
                                    <div class="rage-symbol <?php echo $i <= $rageData['used'] ? 'used' : 'available'; ?>" 
                                         onclick="toggleRage(<?php echo $npc_id; ?>, <?php echo $i; ?>)"
                                         data-rage="<?php echo $i; ?>"
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
                        <button class="btn btn-warning" onclick="resetRages(<?php echo $npc_id; ?>)">
                            <i class="fas fa-moon me-1"></i>Long repos
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Combat -->
            <div class="info-section">
                <h3><i class="fas fa-sword me-2"></i>Combat</h3>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Initiative:</strong> &nbsp;<?php echo ($initiative >= 0 ? '+' : '') . $initiative; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Vitesse:</strong> &nbsp;<?php echo $character['speed']; ?> pieds
                    </div>
                    <div class="col-md-3">
                        <strong>Bonus de maîtrise:</strong> &nbsp;+<?php echo ceil($character['level'] / 4) + 1; ?>
                    </div>
                </div>
                
                <!-- Classe d'armure et Attaques -->
                <div class="row mt-3">
                    <div class="col-md-6">
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
                                            
                                            <strong>Modificateur de Dextérité:</strong> <?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?>
                                            <?php if ($isBarbarian && !$equippedArmor): ?>
                                                <br><strong>Modificateur de Constitution:</strong> <?php echo ($constitutionMod >= 0 ? '+' : '') . $constitutionMod; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
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
                </div>
                
                <!-- Bouton Grimoire pour les classes de sorts -->
                <?php 
                // Classes qui peuvent lancer des sorts
                $spellcastingClasses = [2, 3, 4, 5, 7, 9, 10, 11]; // Barde, Clerc, Druide, Ensorceleur, Magicien, Occultiste, Paladin, Rôdeur
                $canCastSpells = in_array($character['class_id'], $spellcastingClasses);
                ?>
                <?php if ($canCastSpells): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <a href="grimoire_npc.php?id=<?php echo $npc_id; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-book-open me-2"></i>Grimoire
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Arme équipée -->
                <?php if ($equippedItems['main_hand']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5><i class="fas fa-sword me-2"></i>Arme équipée</h5>
                        <div class="card">
                            <div class="card-body">
                                <?php
                                // Trouver l'arme équipée dans les armes détectées
                                $equippedWeapon = null;
                                foreach ($detectedWeapons as $weapon) {
                                    if ($weapon['name'] === $equippedItems['main_hand']) {
                                        $equippedWeapon = $weapon;
                                        break;
                                    }
                                }
                                ?>
                                
                                <?php if ($equippedWeapon): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="text-success"><?php echo htmlspecialchars($equippedWeapon['name']); ?></h4>
                                        <p class="mb-1">
                                            <strong>Dégâts:</strong> <?php echo htmlspecialchars($equippedWeapon['damage']); ?><br>
                                            <strong>Type:</strong> <?php echo htmlspecialchars($equippedWeapon['type']); ?><br>
                                            <strong>Mains:</strong> <?php echo $equippedWeapon['hands']; ?> main(s)
                                        </p>
                                        <?php if ($equippedWeapon['properties']): ?>
                                        <p class="mb-1">
                                            <strong>Propriétés:</strong> <?php echo htmlspecialchars($equippedWeapon['properties']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Arme équipée non trouvée dans la base de données</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>


            <!-- Capacités -->
            <div class="info-section">
                <h3><i class="fas fa-star me-2"></i>Capacités</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-star me-2"></i>Liste des capacités</h5>
                        <?php 
                        // Utiliser les capacités du nouveau système homogène
                        $displayCapabilities = [];
                        
                        // Ajouter les capacités de classe
                            foreach ($classCapabilities as $capability) {
                            $displayCapabilities[] = [
                                    'name' => $capability['name'],
                                    'description' => $capability['description'],
                                'type' => $capability['type_name'],
                                'icon' => $capability['icon'],
                                'color' => $capability['color'],
                                'source_type' => 'Classe'
                            ];
                        }
                        
                        // Ajouter les capacités raciales
                            foreach ($raceCapabilities as $capability) {
                            $displayCapabilities[] = [
                                    'name' => $capability['name'],
                                    'description' => $capability['description'],
                                'type' => $capability['type_name'],
                                'icon' => $capability['icon'],
                                'color' => $capability['color'],
                                'source_type' => 'Race'
                            ];
                        }
                        
                        // Ajouter les capacités d'historique
                        foreach ($backgroundCapabilities as $capability) {
                            $displayCapabilities[] = [
                                'name' => $capability['name'],
                                'description' => $capability['description'],
                                'type' => $capability['type_name'],
                                'icon' => $capability['icon'],
                                'color' => $capability['color'],
                                'source_type' => 'Historique'
                            ];
                        }
                        
                        // Ajouter les capacités spécialisées (voie primitive, etc.)
                        if ($barbarianPath) {
                            $displayCapabilities[] = [
                                'name' => $barbarianPath['name'],
                                'description' => $barbarianPath['description'],
                                'type' => 'Voie primitive',
                                'icon' => 'fas fa-route',
                                'color' => 'warning',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        // Ajouter les autres archetypes
                        if ($paladinOath) {
                            $displayCapabilities[] = [
                                'name' => $paladinOath['name'],
                                'description' => $paladinOath['description'],
                                'type' => 'Serment sacré',
                                'icon' => 'fas fa-shield-alt',
                                'color' => 'primary',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($rangerArchetype) {
                            $displayCapabilities[] = [
                                'name' => $rangerArchetype['name'],
                                'description' => $rangerArchetype['description'],
                                'type' => 'Archétype de rôdeur',
                                'icon' => 'fas fa-bow-arrow',
                                'color' => 'success',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($rogueArchetype) {
                            $displayCapabilities[] = [
                                'name' => $rogueArchetype['name'],
                                'description' => $rogueArchetype['description'],
                                'type' => 'Archétype de roublard',
                                'icon' => 'fas fa-user-ninja',
                                'color' => 'dark',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($bardCollege) {
                            $displayCapabilities[] = [
                                'name' => $bardCollege['name'],
                                'description' => $bardCollege['description'],
                                'type' => 'Collège bardique',
                                'icon' => 'fas fa-music',
                                'color' => 'info',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($clericDomain) {
                            $displayCapabilities[] = [
                                'name' => $clericDomain['name'],
                                'description' => $clericDomain['description'],
                                'type' => 'Domaine divin',
                                'icon' => 'fas fa-cross',
                                'color' => 'light',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($druidCircle) {
                            $displayCapabilities[] = [
                                'name' => $druidCircle['name'],
                                'description' => $druidCircle['description'],
                                'type' => 'Cercle druidique',
                                'icon' => 'fas fa-leaf',
                                'color' => 'success',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($sorcererOrigin) {
                            $displayCapabilities[] = [
                                'name' => $sorcererOrigin['name'],
                                'description' => $sorcererOrigin['description'],
                                'type' => 'Origine magique',
                                'icon' => 'fas fa-bolt',
                                'color' => 'warning',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($fighterArchetype) {
                            $displayCapabilities[] = [
                                'name' => $fighterArchetype['name'],
                                'description' => $fighterArchetype['description'],
                                'type' => 'Archétype martial',
                                'icon' => 'fas fa-sword',
                                'color' => 'danger',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($wizardTradition) {
                            $displayCapabilities[] = [
                                'name' => $wizardTradition['name'],
                                'description' => $wizardTradition['description'],
                                'type' => 'Tradition arcanique',
                                'icon' => 'fas fa-hat-wizard',
                                'color' => 'primary',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($monkTradition) {
                            $displayCapabilities[] = [
                                'name' => $monkTradition['name'],
                                'description' => $monkTradition['description'],
                                'type' => 'Tradition monastique',
                                'icon' => 'fas fa-fist-raised',
                                'color' => 'secondary',
                                'source_type' => 'Spécial'
                            ];
                        }
                        
                        if ($warlockPact) {
                            $displayCapabilities[] = [
                                'name' => $warlockPact['name'],
                                'description' => $warlockPact['description'],
                                'type' => 'Faveur de pacte',
                                'icon' => 'fas fa-handshake',
                                'color' => 'dark',
                                'source_type' => 'Spécial'
                            ];
                        }
                        ?>
                        
                        <?php 
                        // Debug temporaire pour voir les capacités à afficher
                        error_log("Debug view_npc.php - Display capabilities count: " . count($displayCapabilities));
                        error_log("Debug view_npc.php - Class capabilities count: " . count($classCapabilities));
                        error_log("Debug view_npc.php - Race capabilities count: " . count($raceCapabilities));
                        error_log("Debug view_npc.php - Background capabilities count: " . count($backgroundCapabilities));
                        ?>
                        
                        <?php if (!empty($displayCapabilities)): ?>
                            <div class="list-group capabilities-list">
                                <?php foreach ($displayCapabilities as $capability): ?>
                                    <a href="#" class="list-group-item list-group-item-action capability-item" 
                                       data-capability='<?php echo htmlspecialchars(json_encode($capability)); ?>'>
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <i class="<?php echo $capability['icon']; ?> me-1"></i>
                                                <?php echo htmlspecialchars($capability['name']); ?>
                                            </h6>
                                            <small class="text-muted">Cliquez pour voir les détails</small>
                                        </div>
                                        <small class="text-<?php echo $capability['color']; ?>"><?php echo $capability['source_type']; ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune capacité spéciale</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle me-2"></i>Détail de la capacité</h5>
                        <div id="capability-detail" class="card">
                            <div class="card-body">
                                <p class="text-muted mb-0">Sélectionnez une capacité pour voir ses détails.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compétences -->
            <div class="info-section">
                <h3><i class="fas fa-dice me-2"></i>Compétences</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-check-circle me-2"></i>Compétences maîtrisées</h5>
                        <?php if (!empty($allSkills)): ?>
                            <div class="list-group skills-list">
                                <?php foreach ($allSkills as $skill): ?>
                                    <?php 
                                    // Déterminer la source de la compétence
                                    $isBackgroundSkill = in_array($skill, $backgroundSkills);
                                    $isCharacterSkill = in_array($skill, $characterSkills);
                                    $sourceClass = $isBackgroundSkill ? 'text-success' : 'text-primary';
                                    $sourceIcon = $isBackgroundSkill ? 'fas fa-book' : 'fas fa-user';
                                    $sourceText = $isBackgroundSkill ? 'Historique' : 'Classe/Race';
                                    ?>
                                    <a href="#" class="list-group-item list-group-item-action skill-item" data-skill="<?php echo htmlspecialchars($skill); ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($skill); ?></h6>
                                            <small class="text-muted">Cliquez pour voir les détails</small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="<?php echo $sourceClass; ?>">
                                                <i class="<?php echo $sourceIcon; ?> me-1"></i><?php echo $sourceText; ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune compétence maîtrisée</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-info-circle me-2"></i>Détail de la compétence</h5>
                        <div id="skill-detail" class="card">
                            <div class="card-body">
                                <p class="text-muted mb-0">Sélectionnez une compétence pour voir ses détails.</p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>

            <!-- Langues -->
            <div class="info-section">
                <h3><i class="fas fa-language me-2"></i>Langues</h3>
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-comments me-2"></i>Langues parlées</h5>
                        <?php if (!empty($allLanguages)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($allLanguages as $language): ?>
                                    <span class="badge bg-info me-2 mb-2"><?php echo htmlspecialchars($language['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune langue parlée</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Informations de race et classe -->
            <div class="row">
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-dragon me-2"></i>Race: <?php echo htmlspecialchars($characterDetails['race_name']); ?></h3>
                        <p><?php echo htmlspecialchars($characterDetails['race_description']); ?></p>
                        <p><strong>Bonus de caractéristiques:</strong> 
                            Force: +<?php echo $characterDetails['strength_bonus']; ?> | 
                            Dextérité: +<?php echo $characterDetails['dexterity_bonus']; ?> | 
                            Constitution: +<?php echo $characterDetails['constitution_bonus']; ?> | 
                            Intelligence: +<?php echo $characterDetails['intelligence_bonus']; ?> | 
                            Sagesse: +<?php echo $characterDetails['wisdom_bonus']; ?> | 
                            Charisme: +<?php echo $characterDetails['charisma_bonus']; ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-shield-alt me-2"></i>Classe: <?php echo htmlspecialchars($characterDetails['class_name']); ?></h3>
                        <p><?php echo htmlspecialchars($characterDetails['class_description']); ?></p>
                        <p><strong>Dé de vie:</strong> &nbsp;<?php echo $characterDetails['hit_dice']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <?php if ($characterDetails['background_name']): ?>
            <div class="info-section">
                <h3><i class="fas fa-book me-2"></i>Historique: <?php echo htmlspecialchars($characterDetails['background_name']); ?></h3>
            </div>
            <?php endif; ?>

            <!-- Bourse -->
            <div class="info-section">
                <h3><i class="fas fa-coins me-2"></i>Bourse</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-coins text-warning me-2"></i>Argent du personnage</h5>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border rounded p-3 bg-warning bg-opacity-10">
                                            <h4 class="text-warning mb-1"><?php echo $npcGold; ?></h4>
                                            <small class="text-muted">PO</small>
                                            <br><small>Pièces d'or</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3 bg-secondary bg-opacity-10">
                                            <h4 class="text-secondary mb-1">0</h4>
                                            <small class="text-muted">PA</small>
                                            <br><small>Pièces d'argent</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3 bg-danger bg-opacity-10">
                                            <h4 class="text-danger mb-1">0</h4>
                                            <small class="text-muted">PC</small>
                                            <br><small>Pièces de cuivre</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-calculator me-2"></i>Valeur totale</h5>
                                <?php 
                                $totalCopper = $npcGold * 100;
                                $totalGold = floor($totalCopper / 100);
                                $remainingSilver = floor(($totalCopper % 100) / 10);
                                $remainingCopper = $totalCopper % 10;
                                ?>
                                <p class="mb-2"><strong>En pièces de cuivre:</strong> <?php echo $totalCopper; ?> PC</p>
                                <p class="mb-0"><strong>Équivalent:</strong> <?php echo $totalGold; ?> PO, <?php echo $remainingSilver; ?> PA, <?php echo $remainingCopper; ?> PC</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outils et Instruments -->
            <?php if (!empty($allTools)): ?>
            <div class="info-section">
                <h3><i class="fas fa-tools me-2"></i>Outils et Instruments</h3>
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-check-circle me-2"></i>Outils maîtrisés</h5>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($allTools as $tool): ?>
                                <span class="badge bg-success me-2 mb-2">
                                    <i class="fas fa-tools me-1"></i><?php echo htmlspecialchars($tool); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Équipement -->
            <div class="info-section">
                <h3><i class="fas fa-backpack me-2"></i>Équipement</h3>
                
                <!-- Filtres et tri -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="equipmentSearch" class="form-control" placeholder="Rechercher un objet...">
                    </div>
                    <div class="col-md-3">
                        <select id="typeFilter" class="form-select">
                            <option value="">Tous les types</option>
                            <option value="weapon">Arme</option>
                            <option value="armor">Armure</option>
                            <option value="magical_item">Objet magique</option>
                            <option value="poison">Poison</option>
                            <option value="bourse">Bourse</option>
                            <option value="letter">Lettre</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="equippedFilter" class="form-select">
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
                                    Type précis <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th>État</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Combiner tous les objets du personnage
                            $allCharacterItems = array_merge($allMagicalEquipment, $characterPoisons);
                            
                            // Fonction pour vérifier si un objet existe déjà
                            function itemExists($items, $name, $type) {
                                foreach ($items as $item) {
                                    if (($item['item_name'] ?? '') === $name && ($item['item_type'] ?? '') === $type) {
                                        return true;
                                    }
                                }
                                return false;
                            }
                            
                            // Ajouter les objets de base détectés seulement s'ils n'existent pas déjà
                            if (!empty($detectedWeapons)) {
                                foreach ($detectedWeapons as $weapon) {
                                    // Vérifier si cette arme existe déjà dans les objets du personnage
                                    if (!itemExists($allCharacterItems, $weapon['name'], 'weapon')) {
                                        $isEquipped = false;
                                        if ($weapon['hands'] == 2) {
                                            $isEquipped = ($equippedItems['main_hand'] === $weapon['name'] && $equippedItems['off_hand'] === $weapon['name']);
                                        } else {
                                            $isEquipped = ($equippedItems['main_hand'] === $weapon['name']);
                                        }
                                        
                                        $allCharacterItems[] = [
                                            'id' => 'base_' . $weapon['name'],
                                            'item_name' => $weapon['name'],
                                            'item_type' => 'weapon',
                                            'type_precis' => $weapon['name'],
                                            'equipped' => $isEquipped,
                                            'equipped_slot' => $isEquipped ? 'main_hand' : null,
                                            'item_source' => 'Équipement de base',
                                            'quantity' => 1,
                                            'obtained_at' => date('Y-m-d H:i:s'),
                                            'obtained_from' => 'Équipement de base',
                                            'item_description' => "Arme: {$weapon['type']}, {$weapon['hands']} main(s), Dégâts: {$weapon['damage']}",
                                            'notes' => null
                                        ];
                                    }
                                }
                            }
                            
                            if (!empty($detectedArmor)) {
                                foreach ($detectedArmor as $armor) {
                                    // Vérifier si cette armure existe déjà dans les objets du personnage
                                    if (!itemExists($allCharacterItems, $armor['name'], 'armor')) {
                                        $isEquipped = ($equippedItems['armor'] === $armor['name']);
                                        
                                        $allCharacterItems[] = [
                                            'id' => 'base_' . $armor['name'],
                                            'item_name' => $armor['name'],
                                            'item_type' => 'armor',
                                            'type_precis' => $armor['name'],
                                            'equipped' => $isEquipped,
                                            'equipped_slot' => $isEquipped ? 'armor' : null,
                                            'item_source' => 'Équipement de base',
                                            'quantity' => 1,
                                            'obtained_at' => date('Y-m-d H:i:s'),
                                            'obtained_from' => 'Équipement de base',
                                            'item_description' => "Armure: CA {$armor['ac_formula']}, Type: {$armor['type']}",
                                            'notes' => null
                                        ];
                                    }
                                }
                            }
                            
                            if (!empty($detectedShields)) {
                                foreach ($detectedShields as $shield) {
                                    // Vérifier si ce bouclier existe déjà dans les objets du personnage
                                    if (!itemExists($allCharacterItems, $shield['name'], 'shield')) {
                                        $isEquipped = ($equippedItems['shield'] === $shield['name']);
                                        
                                        $allCharacterItems[] = [
                                            'id' => 'base_' . $shield['name'],
                                            'item_name' => $shield['name'],
                                            'item_type' => 'shield',
                                            'type_precis' => $shield['name'],
                                            'equipped' => $isEquipped,
                                            'equipped_slot' => $isEquipped ? 'off_hand' : null,
                                            'item_source' => 'Équipement de base',
                                            'quantity' => 1,
                                            'obtained_at' => date('Y-m-d H:i:s'),
                                            'obtained_from' => 'Équipement de base',
                                            'item_description' => "Bouclier: Bonus CA +{$shield['ac_bonus']}",
                                            'notes' => null
                                        ];
                                    }
                                }
                            }
                            
                            foreach ($allCharacterItems as $item): 
                                // Utiliser les champs standardisés
                                $itemName = $item['item_name'] ?? $item['display_name'] ?? 'Objet inconnu';
                                $itemType = $item['item_type'] ?? 'unknown';
                                $displayName = htmlspecialchars($itemName);
                                $typeLabel = ucfirst(str_replace('_', ' ', $itemType));
                            ?>
                            <tr data-type="<?php echo $itemType; ?>" data-equipped="<?php echo $item['equipped'] ? 'equipped' : 'unequipped'; ?>">
                                <td>
                                    <strong><?php echo $displayName; ?></strong>
                                    <?php if ($item['quantity'] > 1): ?>
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
                                    <small class="text-muted"><?php echo htmlspecialchars($item['item_description'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php if ($item['equipped']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Équipé
                                        </span>
                                        <?php if ($item['equipped_slot']): ?>
                                            <br><small class="text-muted">
                                                <?php 
                                                // Gérer les slots multiples (ex: "main_hand,off_hand")
                                                $slots = explode(',', $item['equipped_slot']);
                                                $slotLabels = array_map(function($slot) {
                                                    return ucfirst(str_replace('_', ' ', trim($slot)));
                                                }, $slots);
                                                echo implode(' + ', $slotLabels);
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times-circle me-1"></i>Non équipé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width: 300px; white-space: nowrap; overflow: visible;">
                                    <?php if ($itemType === 'weapon' || $itemType === 'armor' || $itemType === 'shield'): ?>
                                        <?php if ($item['equipped']): ?>
                                            <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $npc_id; ?>, '<?php echo addslashes($itemName); ?>')"
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
                                            <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $npc_id; ?>, '<?php echo addslashes($itemName); ?>', '<?php echo $itemType; ?>', '<?php echo $slot; ?>')"
                                                    style="white-space: nowrap; min-width: 80px;">
                                                <i class="fas fa-hand-rock me-1"></i>Équiper
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non équipable</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($canModifyHP && !str_starts_with($item['id'], 'base_')): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm ms-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#transferModal" 
                                                data-item-id="<?php echo $item['id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($itemName); ?>"
                                                data-item-type="<?php echo htmlspecialchars($itemType); ?>"
                                                data-source="character_equipment"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-exchange-alt me-1"></i>Transférer
                                        </button>
                                        <?php if (!$item['equipped']): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm ms-1" 
                                                    onclick="dropItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>')"
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


            <!-- Informations personnelles -->
            <?php if ($character['personality_traits'] || $character['ideals'] || $character['bonds'] || $character['flaws']): ?>
                <div class="info-section">
                    <h3><i class="fas fa-user-edit me-2"></i>Informations Personnelles</h3>
                    <div class="row">
                        <?php if ($character['personality_traits']): ?>
                            <div class="col-md-6">
                                <p><strong>Traits de personnalité:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['personality_traits'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['ideals']): ?>
                            <div class="col-md-6">
                                <p><strong>Idéaux:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['ideals'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['bonds']): ?>
                            <div class="col-md-6">
                                <p><strong>Liens:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['bonds'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($character['flaws']): ?>
                            <div class="col-md-6">
                                <p><strong>Défauts:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($character['flaws'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal pour Gestion des Points de Vie -->
    <?php if ($canModifyHP): ?>
    <div class="modal fade" id="hpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-heart me-2"></i>
                        Gestion des Points de Vie - <?php echo htmlspecialchars($character['name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Barre de Points de Vie -->
                    <div class="mb-4">
                        <h6>Points de Vie Actuels</h6>
                        <?php
                        $current_hp = $character['hit_points'];
                        $max_hp = $character['hit_points'];
                        $hp_percentage = $max_hp > 0 ? ($current_hp / $max_hp) * 100 : 100;
                        $hp_class = $hp_percentage > 50 ? 'bg-success' : ($hp_percentage > 25 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="progress mb-2" style="height: 30px;">
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
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(1, '<?php echo htmlspecialchars($character['name']); ?>')">-1</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(5, '<?php echo htmlspecialchars($character['name']); ?>')">-5</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(10, '<?php echo htmlspecialchars($character['name']); ?>')">-10</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(20, '<?php echo htmlspecialchars($character['name']); ?>')">-20</button>
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
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(1, '<?php echo htmlspecialchars($character['name']); ?>')">+1</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(5, '<?php echo htmlspecialchars($character['name']); ?>')">+5</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(10, '<?php echo htmlspecialchars($character['name']); ?>')">+10</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(20, '<?php echo htmlspecialchars($character['name']); ?>')">+20</button>
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
                                <input type="hidden" name="max_hp" value="<?php echo $character['hit_points']; ?>">
                                <div class="d-flex gap-2">
                                    <input type="number" name="current_hp" class="form-control form-control-sm" 
                                           value="<?php echo $character['hit_points']; ?>" 
                                           min="0" max="<?php echo $character['hit_points']; ?>" required>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Maximum : <?php echo $character['hit_points']; ?> PV</small>
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
                        Gestion des Points d'Expérience - <?php echo htmlspecialchars($character['name']); ?>
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
                                    <strong><?php echo number_format($character['experience'] ?? 0); ?> XP</strong>
                                    <br>
                                    <small class="text-muted">Niveau <?php echo $character['level']; ?></small>
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
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-100, '<?php echo htmlspecialchars($character['name']); ?>')">-100</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-500, '<?php echo htmlspecialchars($character['name']); ?>')">-500</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-1000, '<?php echo htmlspecialchars($character['name']); ?>')">-1000</button>
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
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(100, '<?php echo htmlspecialchars($character['name']); ?>')">+100</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(500, '<?php echo htmlspecialchars($character['name']); ?>')">+500</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(1000, '<?php echo htmlspecialchars($character['name']); ?>')">+1000</button>
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
                                           value="<?php echo $character['experience'] ?? 0; ?>" 
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
                    <button type="button" class="btn btn-primary" onclick="confirmTransfer()">
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
                    <button type="button" class="btn btn-primary" onclick="uploadPhoto()">
                        <i class="fas fa-upload me-1"></i>Uploader
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Les fonctions quickDamage, quickHeal et quickXpChange sont maintenant dans jdrmj.js
        // Elles sont appelées avec le nom du NPC en paramètre

        // Gestion du modal de transfert
        document.getElementById('transferModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            const currentOwner = button.getAttribute('data-current-owner');
            const currentOwnerName = button.getAttribute('data-current-owner-name');
            const source = button.getAttribute('data-source');
            
            // Remplir les informations de base
            document.getElementById('transferItemName').textContent = itemName;
            document.getElementById('transferCurrentOwner').textContent = currentOwnerName;
            document.getElementById('transferItemId').value = itemId;
            document.getElementById('transferCurrentOwnerType').value = currentOwner;
            document.getElementById('transferSource').value = source;
            
            // Charger les cibles disponibles
            loadTransferTargets(currentOwner);
        });

        function loadTransferTargets(currentOwner) {
            const select = document.getElementById('transferTarget');
            select.innerHTML = '<option value="">Chargement...</option>';
            
            // Simuler le chargement des cibles (à remplacer par un appel AJAX)
            setTimeout(() => {
                select.innerHTML = '<option value="">Sélectionner une cible...</option>';
                
                // Ajouter les personnages joueurs
                select.innerHTML += '<optgroup label="Personnages Joueurs">';
                select.innerHTML += '<option value="character_1">Hyphrédicte (Robin)</option>';
                select.innerHTML += '<option value="character_2">Lieutenant Cameron (MJ)</option>';
                select.innerHTML += '</optgroup>';
                
                // Ajouter les PNJ
                select.innerHTML += '<optgroup label="PNJ">';
                select.innerHTML += '<option value="npc_1">PNJ Test</option>';
                select.innerHTML += '</optgroup>';
                
                // Ajouter les monstres
                select.innerHTML += '<optgroup label="Monstres">';
                select.innerHTML += '<option value="monster_10">Aboleth #1</option>';
                select.innerHTML += '<option value="monster_11">Aboleth #2</option>';
                select.innerHTML += '</optgroup>';
            }, 500);
        }

        function confirmTransfer() {
            const form = document.getElementById('transferForm');
            const target = document.getElementById('transferTarget').value;
            const itemName = document.getElementById('transferItemName').textContent;
            
            if (!target) {
                alert('Veuillez sélectionner une cible pour le transfert.');
                return;
            }
            
            const targetName = document.getElementById('transferTarget').selectedOptions[0].text;
            
            if (confirm(`Confirmer le transfert de "${itemName}" vers "${targetName}" ?`)) {
                form.submit();
            }
        }

        // Fonction pour l'upload de photo
        function uploadPhoto() {
            const form = document.getElementById('photoForm');
            const fileInput = document.getElementById('profile_photo');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Veuillez sélectionner un fichier image.');
                return;
            }
            
            const file = fileInput.files[0];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (file.size > maxSize) {
                alert('Le fichier est trop volumineux. Taille maximale : 10MB.');
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format de fichier non supporté. Utilisez JPG, PNG ou GIF.');
                return;
            }
            
            if (confirm('Confirmer l\'upload de cette photo de profil ?')) {
                form.submit();
            }
        }

        // Fonctions pour l'équipement
        function equipItem(characterId, itemName, itemType, slot) {
            fetch('api/equip_npc_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    character_id: characterId,
                    item_name: itemName,
                    item_type: itemType,
                    slot: slot
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'équipement');
            });
        }

        function unequipItem(characterId, itemName) {
            console.log('Debug unequipItem - characterId:', characterId, 'itemName:', itemName);
            fetch('api/unequip_npc_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    character_id: characterId,
                    item_name: itemName
                })
            })
            .then(response => {
                console.log('Debug unequipItem - Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Debug unequipItem - Response data:', data);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du déséquipement');
            });
        }

        function dropItem(itemId, itemName) {
            if (!confirm(`Êtes-vous sûr de vouloir déposer "${itemName}" dans le lieu actuel ?`)) {
                return;
            }

            fetch('drop_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    item_name: itemName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Objet déposé avec succès dans le lieu actuel !');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du dépôt de l\'objet');
            });
        }

        // Fonctions pour gérer les rages
        function toggleRage(characterId, rageNumber) {
            const rageSymbol = document.querySelector(`[data-rage="${rageNumber}"]`);
            const isUsed = rageSymbol.classList.contains('used');
            
            const action = isUsed ? 'free' : 'use';
            
            fetch('manage_rage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    character_id: characterId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    updateRageDisplay();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour de la rage');
            });
        }

        function resetRages(characterId) {
            if (confirm('Effectuer un long repos ? Cela récupérera toutes les rages.')) {
                fetch('manage_rage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        character_id: characterId,
                        action: 'reset'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour l'affichage
                        updateRageDisplay();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la réinitialisation des rages');
                });
            }
        }

        function updateRageDisplay() {
            // Recharger la page pour mettre à jour l'affichage
            window.location.reload();
        }

        // Variables pour le tri
        let currentSortColumn = -1;
        let currentSortDirection = 'asc';

        // Fonction de tri du tableau
        function sortTable(columnIndex) {
            const table = document.getElementById('equipmentTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Déterminer la direction du tri
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirection = 'asc';
                currentSortColumn = columnIndex;
            }
            
            // Trier les lignes
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim().toLowerCase();
                const bText = b.cells[columnIndex].textContent.trim().toLowerCase();
                
                if (currentSortDirection === 'asc') {
                    return aText.localeCompare(bText);
                } else {
                    return bText.localeCompare(aText);
                }
            });
            
            // Réorganiser les lignes dans le DOM
            rows.forEach(row => tbody.appendChild(row));
            
            // Mettre à jour les icônes de tri
            updateSortIcons(columnIndex);
        }

        // Fonction pour mettre à jour les icônes de tri
        function updateSortIcons(activeColumn) {
            const headers = document.querySelectorAll('#equipmentTable th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (index === activeColumn) {
                    icon.className = currentSortDirection === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
                } else {
                    icon.className = 'fas fa-sort ms-1';
                }
            });
        }

        // Fonction de filtrage
        function filterTable() {
            const searchTerm = document.getElementById('equipmentSearch').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const equippedFilter = document.getElementById('equippedFilter').value;
            
            const table = document.getElementById('equipmentTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const type = row.dataset.type;
                const equipped = row.dataset.equipped;
                
                let showRow = true;
                
                // Filtre de recherche
                if (searchTerm && !name.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Filtre de type
                if (typeFilter && type !== typeFilter) {
                    showRow = false;
                }
                
                // Filtre d'état d'équipement
                if (equippedFilter && equipped !== equippedFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Fonction pour réinitialiser les filtres
        function resetFilters() {
            document.getElementById('equipmentSearch').value = '';
            document.getElementById('typeFilter').value = '';
            document.getElementById('equippedFilter').value = '';
            filterTable();
        }

        // Ajouter les événements de filtrage
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('equipmentSearch');
            const typeSelect = document.getElementById('typeFilter');
            const equippedSelect = document.getElementById('equippedFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterTable);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', filterTable);
            }
            if (equippedSelect) {
                equippedSelect.addEventListener('change', filterTable);
            }
        });

        // Gestion des compétences
        document.addEventListener('DOMContentLoaded', function() {
            const skillItems = document.querySelectorAll('.skill-item');
            const skillDetail = document.getElementById('skill-detail');
            
            // Base de données des compétences
            const skillsData = {
                'Athlétisme': {
                    'caracteristic': 'Force',
                    'description': 'Votre test d\'Athlétisme couvre les situations difficiles que vous rencontrez en escaladant, en sautant ou en nageant.',
                    'examples': [
                        'Escalader une falaise escarpée',
                        'Sauter par-dessus un ravin',
                        'Nager contre un courant fort',
                        'Pousser une lourde pierre'
                    ]
                },
                'Intimidation': {
                    'caracteristic': 'Charisme',
                    'description': 'Quand vous tentez d\'influencer quelqu\'un par la menace, l\'hostilité ou la violence, le MJ peut vous demander de faire un test d\'Intimidation.',
                    'examples': [
                        'Menacer un garde pour qu\'il vous laisse passer',
                        'Faire parler un prisonnier',
                        'Impressionner des bandits',
                        'Obtenir des informations par la peur'
                    ]
                },
                'Nature': {
                    'caracteristic': 'Intelligence',
                    'description': 'Votre test d\'Intelligence (Nature) mesure votre capacité à vous rappeler des informations utiles sur le terrain, les plantes et les animaux, le temps et les cycles naturels.',
                    'examples': [
                        'Identifier une plante vénéneuse',
                        'Prédire le temps qu\'il va faire',
                        'Reconnaître les traces d\'un animal',
                        'Trouver de l\'eau potable'
                    ]
                },
                'Perception': {
                    'caracteristic': 'Sagesse',
                    'description': 'Votre test de Sagesse (Perception) vous permet de repérer, entendre ou détecter la présence de quelque chose. Il mesure votre conscience générale de votre environnement et la perspicacité de vos sens.',
                    'examples': [
                        'Entendre des pas furtifs',
                        'Repérer une embuscade',
                        'Détecter un piège caché',
                        'Voir des détails importants'
                    ]
                },
                'Acrobaties': {
                    'caracteristic': 'Dextérité',
                    'description': 'Votre test de Dextérité (Acrobaties) détermine votre capacité à effectuer des tâches nécessitant agilité, équilibre et contrôle.',
                    'examples': [
                        'Marcher sur une corde raide',
                        'Atterrir sur vos pieds après une chute',
                        'Effectuer des acrobaties',
                        'Se faufiler dans un espace étroit'
                    ]
                },
                'Discrétion': {
                    'caracteristic': 'Dextérité',
                    'description': 'Votre test de Dextérité (Discrétion) détermine si vous pouvez vous déplacer silencieusement et vous cacher dans l\'ombre ou derrière des éléments de décor.',
                    'examples': [
                        'Se cacher des gardes',
                        'Se déplacer silencieusement',
                        'Échapper à une poursuite',
                        'Surprendre un ennemi'
                    ]
                },
                'Escamotage': {
                    'caracteristic': 'Dextérité',
                    'description': 'Chaque fois que vous tentez d\'effectuer un acte de prestidigitation ou de vol à la tire, le MJ peut vous demander de faire un test de Dextérité (Escamotage).',
                    'examples': [
                        'Voler la bourse d\'un passant',
                        'Cacher un objet sur votre personne',
                        'Faire des tours de passe-passe',
                        'Désarmer un piège'
                    ]
                },
                'Histoire': {
                    'caracteristic': 'Intelligence',
                    'description': 'Votre test d\'Intelligence (Histoire) mesure votre capacité à vous rappeler des légendes, des histoires, des événements historiques, des traditions royales, des guerres, des religions et des langues.',
                    'examples': [
                        'Reconnaître un symbole ancien',
                        'Se rappeler d\'un événement historique',
                        'Identifier l\'origine d\'un artefact',
                        'Connaître les traditions d\'une région'
                    ]
                },
                'Investigation': {
                    'caracteristic': 'Intelligence',
                    'description': 'Quand vous regardez autour de vous pour des indices et tirez des conclusions basées sur ce que vous trouvez, vous faites un test d\'Intelligence (Investigation).',
                    'examples': [
                        'Examiner une scène de crime',
                        'Analyser un mécanisme complexe',
                        'Trouver des indices cachés',
                        'Résoudre une énigme'
                    ]
                },
                'Médecine': {
                    'caracteristic': 'Sagesse',
                    'description': 'Un test de Sagesse (Médecine) vous permet d\'essayer de stabiliser un compagnon mourant ou de diagnostiquer une maladie.',
                    'examples': [
                        'Stabiliser un compagnon mourant',
                        'Diagnostiquer une maladie',
                        'Identifier un poison',
                        'Soigner des blessures'
                    ]
                },
                'Perspicacité': {
                    'caracteristic': 'Sagesse',
                    'description': 'Votre test de Sagesse (Perspicacité) détermine si vous pouvez déterminer les vraies intentions d\'une créature, comme lors de la recherche d\'un mensonge ou de la prédiction du prochain mouvement de quelqu\'un.',
                    'examples': [
                        'Détecter un mensonge',
                        'Lire les expressions faciales',
                        'Prédire les intentions',
                        'Évaluer la sincérité'
                    ]
                },
                'Persuasion': {
                    'caracteristic': 'Charisme',
                    'description': 'Quand vous tentez d\'influencer quelqu\'un ou un groupe de personnes avec tact, charme social ou bonne nature, le MJ peut vous demander de faire un test de Charisme (Persuasion).',
                    'examples': [
                        'Convaincre un marchand de baisser ses prix',
                        'Négocier une trêve',
                        'Obtenir des informations par la diplomatie',
                        'Rallier des alliés'
                    ]
                },
                'Religion': {
                    'caracteristic': 'Intelligence',
                    'description': 'Votre test d\'Intelligence (Religion) mesure votre capacité à vous rappeler des rituels, des prières, des déités et des enseignements religieux.',
                    'examples': [
                        'Identifier un symbole religieux',
                        'Connaître les rituels d\'une religion',
                        'Reconnaître un artefact sacré',
                        'Comprendre les enseignements religieux'
                    ]
                },
                'Représentation': {
                    'caracteristic': 'Charisme',
                    'description': 'Votre test de Charisme (Représentation) détermine votre capacité à divertir un public avec de la musique, de la danse, de l\'acting, de la narration ou une autre forme de divertissement.',
                    'examples': [
                        'Jouer d\'un instrument',
                        'Réciter de la poésie',
                        'Danser pour divertir',
                        'Faire des tours de magie'
                    ]
                },
                'Survie': {
                    'caracteristic': 'Sagesse',
                    'description': 'Le MJ peut vous demander de faire un test de Sagesse (Survie) pour suivre des traces, chasser, guider votre groupe sur un terrain difficile, identifier des signes de prédateurs ou prédire le temps.',
                    'examples': [
                        'Suivre des traces d\'animaux',
                        'Trouver de la nourriture',
                        'S\'orienter dans la nature',
                        'Construire un abri'
                    ]
                },
                'Tromperie': {
                    'caracteristic': 'Charisme',
                    'description': 'Votre test de Charisme (Tromperie) détermine si vous pouvez convaincre quelqu\'un de la véracité d\'un mensonge.',
                    'examples': [
                        'Mentir à un garde',
                        'Faire croire une fausse identité',
                        'Bluffer lors d\'un jeu',
                        'Cacher ses vraies intentions'
                    ]
                }
            };
            
            skillItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Retirer la classe active de tous les éléments
                    skillItems.forEach(skill => skill.classList.remove('active'));
                    
                    // Ajouter la classe active à l'élément cliqué
                    this.classList.add('active');
                    
                    // Récupérer le nom de la compétence
                    const skillName = this.getAttribute('data-skill');
                    
                    // Afficher les détails
                    if (skillsData[skillName]) {
                        const skill = skillsData[skillName];
                        skillDetail.innerHTML = `
                            <div class="card-body">
                                <h6 class="card-title">${skillName}</h6>
                                <p class="card-text"><strong>Caractéristique :</strong> ${skill.caracteristic}</p>
                                <p class="card-text">${skill.description}</p>
                                <h6>Exemples d'utilisation :</h6>
                                <ul class="mb-0">
                                    ${skill.examples.map(example => `<li>${example}</li>`).join('')}
                                </ul>
                            </div>
                        `;
                    } else {
                        skillDetail.innerHTML = `
                            <div class="card-body">
                                <h6 class="card-title">${skillName}</h6>
                                <p class="text-muted mb-0">Aucun détail disponible pour cette compétence.</p>
                            </div>
                        `;
                    }
                });
            });
        });

        // Gestion des capacités
        document.addEventListener('DOMContentLoaded', function() {
            const capabilityItems = document.querySelectorAll('.capability-item');
            const capabilityDetail = document.getElementById('capability-detail');
            
            capabilityItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Retirer la classe active de tous les éléments
                    capabilityItems.forEach(capability => capability.classList.remove('active'));
                    
                    // Ajouter la classe active à l'élément cliqué
                    this.classList.add('active');
                    
                    // Récupérer les données de la capacité
                    const capabilityData = JSON.parse(this.getAttribute('data-capability'));
                    
                    // Afficher les détails
                    capabilityDetail.innerHTML = `
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="${capabilityData.icon} me-2"></i>${capabilityData.name}
                            </h6>
                            <p class="card-text">
                                <span class="badge bg-${capabilityData.color} me-2">${capabilityData.type}</span>
                            </p>
                            <p class="card-text">${capabilityData.description}</p>
                        </div>
                    `;
                });
            });
        });
    </script>
</body>
</html>


