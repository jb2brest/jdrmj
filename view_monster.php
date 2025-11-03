<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'includes/capabilities_functions.php';
$page_title = "Fiche de Monstre";
$current_page = "view_monster";

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: manage_npcs.php');
    exit();
}

$npc_id = (int)$_GET['id'];
$monster_id = $npc_id; // Pour la compatibilité avec le code existant
$npc_created = isset($_GET['created']) && $_GET['created'] == '1';

// Récupération du monstre avec ses détails depuis la table monsters
$monsterObj = Monster::findById($npc_id);

if (!$monsterObj) {
    header('Location: manage_npcs.php');
    exit();
}

// Récupérer les détails du type de monstre depuis dnd_monsters
$stmt = $pdo->prepare("
    SELECT dt.*, dt.type as monster_type_name, dt.image_url
    FROM dnd_monsters dt
    WHERE dt.id = ?
");
$stmt->execute([$monsterObj->getMonsterTypeId()]);
$monsterTypeData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$monsterTypeData) {
    header('Location: manage_npcs.php');
    exit();
}

// Vérifier les permissions (DM seulement pour l'instant)
$isOwner = false; // Pas de système de propriétaire pour les monstres
$isDM = isDM();

if (!$isOwner && !$isDM) {
    header('Location: manage_npcs.php');
    exit();
}

// Créer un tableau de caractère pour la compatibilité avec le code existant
$monster = [
    'id' => $monsterObj->getId(),
    'name' => $monsterObj->getName(),
    'description' => $monsterObj->getDescription(),
    'hit_points_current' => $monsterObj->getCurrentHitPoints(),
    'hit_points_max' => $monsterObj->getMaxHitPoints(),
    'quantity' => $monsterObj->getQuantity(),
    'is_visible' => $monsterObj->isVisible(),
    'is_identified' => $monsterObj->isIdentified(),
    'created_by' => $monsterObj->getCreatedBy(),
    'created_at' => $monsterObj->getCreatedAt(),
    'updated_at' => $monsterObj->getUpdatedAt(),
    // Données du type de monstre
    'type' => $monsterTypeData['type'],
    'monster_type_name' => $monsterTypeData['monster_type_name'],
    'image_url' => $monsterTypeData['image_url'],
    'challenge_rating' => $monsterTypeData['challenge_rating'],
    'size' => $monsterTypeData['size'],
    'armor_class' => $monsterTypeData['armor_class'],
    'csv_id' => $monsterTypeData['csv_id'],
    'special_abilities' => $monsterTypeData['special_abilities'] ?? '',
    'actions' => $monsterTypeData['actions'] ?? '',
    'legendary_actions' => $monsterTypeData['legendary_actions'] ?? '',
    'lair_actions' => $monsterTypeData['lair_actions'] ?? '',
    'languages' => $monsterTypeData['languages'] ?? '',
    'skills' => $monsterTypeData['skills'] ?? '',
    'saving_throws' => $monsterTypeData['saving_throws'] ?? '',
    'damage_resistances' => $monsterTypeData['damage_resistances'] ?? '',
    'damage_immunities' => $monsterTypeData['damage_immunities'] ?? '',
    'condition_immunities' => $monsterTypeData['condition_immunities'] ?? '',
    'senses' => $monsterTypeData['senses'] ?? '',
    'speed' => $monsterTypeData['speed'] ?? '',
    'strength' => $monsterTypeData['strength'] ?? 10,
    'dexterity' => $monsterTypeData['dexterity'] ?? 10,
    'constitution' => $monsterTypeData['constitution'] ?? 10,
    'intelligence' => $monsterTypeData['intelligence'] ?? 10,
    'wisdom' => $monsterTypeData['wisdom'] ?? 10,
    'charisma' => $monsterTypeData['charisma'] ?? 10,
    // Valeurs par défaut pour la compatibilité
    'class_id' => null,
    'level' => 1,
    'race_id' => null,
    'background_id' => null,
    'user_id' => $monsterObj->getCreatedBy(),
    'experience_points' => 0,
    'gold' => 0,
    'silver' => 0,
    'copper' => 0,
    'personality_traits' => '',
    'ideals' => '',
    'bonds' => '',
    'flaws' => '',
    'alignment' => $monsterTypeData['alignment'] ?? '',
    'proficiency_bonus' => $monsterTypeData['proficiency_bonus'] ?? 0
];

// Les monstres n'ont pas de race, classe ou background comme les personnages
// Ils ont des caractéristiques spécifiques définies dans dnd_monsters

// Construire le tableau monsterDetails pour la compatibilité avec le template
$monsterDetails = [
    'race_name' => $monsterTypeData['type'] ?? '',
    'race_description' => $monsterObj->getDescription() ?? '',
    'strength_bonus' => 0,
    'dexterity_bonus' => 0,
    'constitution_bonus' => 0,
    'intelligence_bonus' => 0,
    'wisdom_bonus' => 0,
    'charisma_bonus' => 0,
    'traits' => $monsterTypeData['special_abilities'] ?? '',
    'race_languages' => $monsterTypeData['languages'] ?? '',
    'class_name' => 'Monster',
    'class_description' => 'Creature',
    'hit_dice' => 'd' . ($monsterTypeData['hit_points'] ?? 8),
    'background_name' => '',
    'background_description' => '',
    'background_skills' => '',
    'background_tools' => '',
    'background_languages' => '',
    'background_feature' => ''
];

// $monsterDetails est toujours défini pour les monstres

// Parser les données JSON du monstre
$monsterSkills = (!empty($monsterTypeData['skills'])) ? json_decode($monsterTypeData['skills'], true) : [];

// Parser les données de l'historique depuis la table backgrounds (pour compatibilité)
$backgroundSkills = $monsterDetails['background_skills'] ? json_decode($monsterDetails['background_skills'], true) : [];

// Traiter les compétences du monstre
$allSkills = [];
if (is_array($monsterSkills)) {
    foreach ($monsterSkills as $skill) {
        $allSkills[] = $skill;
    }
}

// Combiner les compétences avec celles de l'historique (pour compatibilité)
$allSkills = array_unique(array_merge($allSkills, $backgroundSkills));

// Récupérer les données de rage pour les barbares
$isBarbarian = strpos(strtolower($monsterDetails['class_name']), 'barbare') !== false;
$isBard = strpos(strtolower($monsterDetails['class_name']), 'barde') !== false;
$isCleric = strpos(strtolower($monsterDetails['class_name']), 'clerc') !== false;
$isDruid = strpos(strtolower($monsterDetails['class_name']), 'druide') !== false;
$isSorcerer = strpos(strtolower($monsterDetails['class_name']), 'ensorceleur') !== false;
$isFighter = strpos(strtolower($monsterDetails['class_name']), 'guerrier') !== false;
$isWizard = strpos(strtolower($monsterDetails['class_name']), 'magicien') !== false;
$isMonk = strpos(strtolower($monsterDetails['class_name']), 'moine') !== false;
$isWarlock = strpos(strtolower($monsterDetails['class_name']), 'occultiste') !== false;
$isPaladin = strpos(strtolower($monsterDetails['class_name']), 'paladin') !== false;
$isRanger = strpos(strtolower($monsterDetails['class_name']), 'rôdeur') !== false;
$isRogue = strpos(strtolower($monsterDetails['class_name']), 'roublard') !== false;
$rageData = null;
if ($isBarbarian) {
    // Récupérer le nombre maximum de rages pour ce niveau
    require_once 'classes/Classe.php';
    $classObj = Classe::findById($monster['class_id']);
    $maxRages = $classObj ? $classObj->getMaxRages($monster['level']) : 0;
    
    // Récupérer le nombre de rages utilisées
    $rageUsage = Monster::getRageUsageStatic($monster_id);
    $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;
    
    $rageData = [
        'max' => $maxRages,
        'used' => $usedRages,
        'available' => $maxRages - $usedRages
    ];
}

// Récupérer les capacités du monstre (les monstres n'ont pas le même système que les personnages)
$allCapabilities = []; // Les monstres n'ont pas de capacités dans character_capabilities

// Séparer les capacités par type pour l'affichage
$classCapabilities = [];
$raceCapabilities = [];
$backgroundCapabilities = [];

foreach ($allCapabilities as $capability) {
    switch ($capability['source_type']) {
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

// Les monstres n'ont pas d'archetype comme les personnages
$monsterArchetype = null;

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

if ($monsterArchetype) {
    switch ($monsterArchetype['class_name']) {
        case 'Barbare':
            $barbarianPath = $monsterArchetype;
            break;
        case 'Paladin':
            $paladinOath = $monsterArchetype;
            break;
        case 'Rôdeur':
            $rangerArchetype = $monsterArchetype;
            break;
        case 'Roublard':
            $rogueArchetype = $monsterArchetype;
            break;
        case 'Barde':
            $bardCollege = $monsterArchetype;
            break;
        case 'Clerc':
            $clericDomain = $monsterArchetype;
            break;
        case 'Druide':
            $druidCircle = $monsterArchetype;
            break;
        case 'Ensorceleur':
            $sorcererOrigin = $monsterArchetype;
            break;
        case 'Guerrier':
            $fighterArchetype = $monsterArchetype;
            break;
        case 'Magicien':
            $wizardTradition = $monsterArchetype;
            break;
        case 'Moine':
            $monkTradition = $monsterArchetype;
            break;
        case 'Occultiste':
            $warlockPact = $monsterArchetype;
            break;
    }
}


// Calculer les points d'amélioration restants (les monstres n'ont pas de niveau)
$remainingPoints = 0; // Les monstres n'ont pas de points d'amélioration


// Calcul des modificateurs (nécessaire pour le calcul de la CA)
// Utiliser les valeurs totales incluant les bonus raciaux
// Calculer directement les modificateurs pour les monstres
$strengthMod = floor((($monster['strength'] + $monsterDetails['strength_bonus']) - 10) / 2);
$dexterityMod = floor((($monster['dexterity'] + $monsterDetails['dexterity_bonus']) - 10) / 2);
$constitutionMod = floor((($monster['constitution'] + $monsterDetails['constitution_bonus']) - 10) / 2);
$intelligenceMod = floor((($monster['intelligence'] + $monsterDetails['intelligence_bonus']) - 10) / 2);
$wisdomMod = floor((($monster['wisdom'] + $monsterDetails['wisdom_bonus']) - 10) / 2);
$charismaMod = floor((($monster['charisma'] + $monsterDetails['charisma_bonus']) - 10) / 2);

// Les monstres n'ont pas d'équipement - utiliser la CA de base
$equippedArmor = null;
$equippedShield = null;

// Ajouter les modificateurs de caractéristiques au tableau monster pour la fonction
$monster['strength_modifier'] = $strengthMod;
$monster['dexterity_modifier'] = $dexterityMod;
$monster['constitution_modifier'] = $constitutionMod;
$monster['intelligence_modifier'] = $intelligenceMod;
$monster['wisdom_modifier'] = $wisdomMod;
$monster['charisma_modifier'] = $charismaMod;

// Récupérer les actions du monstre depuis la table monster_actions
// Les actions sont liées au type de monstre (dnd_monsters.id), pas à l'instance
$monsterActions = $monsterObj->getActions();

// Récupérer les attaques spéciales du monstre depuis la table monster_special_attacks
$monsterSpecialAttacks = $monsterObj->getSpecialAttacks();

// Récupérer les actions légendaires du monstre depuis la table monster_legendary_actions
$monsterLegendaryActions = $monsterObj->getLegendaryActions();


// Contrôle d'accès: les monstres sont visibles par tous les DM
$canView = isDM(); // Seuls les DM peuvent voir les monstres

// Vérifier si l'utilisateur peut modifier les points de vie (seuls les DM)
$canModifyHP = isDM();

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
            $monsterObj = Monster::findById($monster_id);
            if ($monsterObj) {
                $monsterObj->updateHitPoints($new_hp);
            }
            
            $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
            break;
            
        case 'damage':
            $damage = (int)$_POST['damage'];
            if ($damage > 0) {
                $new_hp = max(0, $monster['hit_points_current'] - $damage);
                $monsterObj = Monster::findById($monster_id);
                if ($monsterObj) {
                    $monsterObj->updateHitPoints($new_hp);
                }
                
                $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
            }
            break;
            
        case 'heal':
            $healing = (int)$_POST['healing'];
            if ($healing > 0) {
                $new_hp = min($monster['hit_points_max'], $monster['hit_points_current'] + $healing);
                $monsterObj = Monster::findById($monster_id);
                if ($monsterObj) {
                    $monsterObj->updateHitPoints($new_hp);
                }
                
                $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
            }
            break;
            
        case 'reset_hp':
            $monsterObj = Monster::findById($monster_id);
            if ($monsterObj) {
                $monsterObj->updateHitPoints($monster['hit_points_max']);
            }
            
            $success_message = "Points de vie réinitialisés au maximum : {$monster['hit_points_max']}";
            break;
    }
    
    // Recharger les données du monstre
    // Récupérer les détails du monstre via la classe Monster
    $monsterObj = Monster::findById($monster_id);
    if (!$monsterObj) {
        header('Location: manage_npcs.php?error=monster_not_found');
        exit;
    }

    // Reconstruire le tableau monster pour la compatibilité
    // Recharger les données du type de monstre
    $stmt = $pdo->prepare("
        SELECT dt.*, dt.type as monster_type_name, dt.image_url
        FROM dnd_monsters dt
        WHERE dt.id = ?
    ");
    $stmt->execute([$monsterObj->getMonsterTypeId()]);
    $monsterTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Reconstruire le tableau monster
    $monster = [
        'id' => $monsterObj->getId(),
        'name' => $monsterObj->getName(),
        'description' => $monsterObj->getDescription(),
        'hit_points_current' => $monsterObj->getCurrentHitPoints(),
        'hit_points_max' => $monsterObj->getMaxHitPoints(),
        'quantity' => $monsterObj->getQuantity(),
        'is_visible' => $monsterObj->isVisible(),
        'is_identified' => $monsterObj->isIdentified(),
        'created_by' => $monsterObj->getCreatedBy(),
        'created_at' => $monsterObj->getCreatedAt(),
        'updated_at' => $monsterObj->getUpdatedAt(),
        'image_url' => $monsterObj->getImageUrl(),
        'type' => $monsterTypeData['type'],
        'monster_type_name' => $monsterTypeData['monster_type_name'],
        'challenge_rating' => $monsterTypeData['challenge_rating'],
        'size' => $monsterTypeData['size'],
        'armor_class' => $monsterTypeData['armor_class'],
        'csv_id' => $monsterTypeData['csv_id'],
        'special_abilities' => $monsterTypeData['special_abilities'] ?? '',
        'actions' => $monsterTypeData['actions'] ?? '',
        'legendary_actions' => $monsterTypeData['legendary_actions'] ?? '',
        'lair_actions' => $monsterTypeData['lair_actions'] ?? '',
        'languages' => $monsterTypeData['languages'] ?? '',
        'skills' => $monsterTypeData['skills'] ?? '',
        'saving_throws' => $monsterTypeData['saving_throws'] ?? '',
        'damage_resistances' => $monsterTypeData['damage_resistances'] ?? '',
        'damage_immunities' => $monsterTypeData['damage_immunities'] ?? '',
        'condition_immunities' => $monsterTypeData['condition_immunities'] ?? '',
        'senses' => $monsterTypeData['senses'] ?? '',
        'speed' => $monsterTypeData['speed'] ?? '',
        'strength' => $monsterTypeData['strength'] ?? 10,
        'dexterity' => $monsterTypeData['dexterity'] ?? 10,
        'constitution' => $monsterTypeData['constitution'] ?? 10,
        'intelligence' => $monsterTypeData['intelligence'] ?? 10,
        'wisdom' => $monsterTypeData['wisdom'] ?? 10,
        'charisma' => $monsterTypeData['charisma'] ?? 10,
        'class_id' => null,
        'level' => 1,
        'race_id' => null,
        'background_id' => null,
        'user_id' => $monsterObj->getCreatedBy(),
        'alignment' => $monsterTypeData['alignment'] ?? '',
        'proficiency_bonus' => $monsterTypeData['proficiency_bonus'] ?? 0,
        'experience_points' => 0,
        'gold' => 0,
        'silver' => 0,
        'copper' => 0,
        'personality_traits' => '',
        'ideals' => '',
        'bonds' => '',
        'flaws' => ''
    ];
}

// Traitement des actions POST pour la gestion des points d'expérience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['xp_action'])) {
    switch ($_POST['xp_action']) {
        case 'add':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = $monster['experience_points'] + $xp_amount;
                $monsterObj = Monster::findById($monster_id);
                if ($monsterObj) {
                    // Les monstres n'ont pas de points d'expérience, ignorer cette action
                }
                
                $success_message = "Points d'expérience ajoutés : +{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'remove':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = max(0, $monster['experience_points'] - $xp_amount);
                $monsterObj = Monster::findById($monster_id);
                if ($monsterObj) {
                    // Les monstres n'ont pas de points d'expérience, ignorer cette action
                }
                
                $success_message = "Points d'expérience retirés : -{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'set':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount >= 0) {
                $monsterObj = Monster::findById($monster_id);
                if ($monsterObj) {
                    // Les monstres n'ont pas de points d'expérience, ignorer cette action
                }
                
                $success_message = "Points d'expérience définis à : " . number_format($xp_amount) . " XP";
            }
            break;
    }
    
    // Les monstres n'ont pas de points d'expérience, cette section est ignorée
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
        $item = NPC::getNpcEquipmentWithDetails($item_id, $monster_id);
    } else {
        // Récupérer depuis items via la classe Item
        $itemObj = Item::findByIdAndOwner($item_id, 'player', $monster_id);
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
            case 'monster':
                // Transférer vers un autre personnage
                $target_char_obj = Monster::findById($target_id);
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
                        'obtained_from' => 'Transfert depuis ' . $monster['name']
                    ];
                    
                    Item::createExtended($itemData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        NPC::removeEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_char['name'];
                }
                break;
                
            case 'monster':
                // Transférer vers un monstre
                $target_monster = NPC::getNpcInfoInPlace($target_id);
                
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
                        'obtained_from' => 'Transfert depuis ' . $monster['name']
                    ];
                    
                    Monstre::addMonsterEquipment($target_id, $target_monster['place_id'], $equipmentData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        NPC::removeEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_monster['name'];
                }
                break;
                
            case 'npc':
                // Transférer vers un PNJ
                $target_npc = NPC::getNpcInfoInPlace($target_id);
                
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
                        'obtained_from' => 'Transfert depuis ' . $monster['name']
                    ];
                    
                    NPC::addEquipmentToNpc($target_id, $target_npc['place_id'], $equipmentData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        NPC::removeEquipmentFromNpc($item_id);
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
    
    // Recharger les données du monstre
    $monsterObj = Monster::findById($monster_id);
    if ($monsterObj) {
        // Mettre à jour les points de vie dans le tableau monster
        $monster['hit_points_current'] = $monsterObj->getCurrentHitPoints();
        $monster['hit_points_max'] = $monsterObj->getMaxHitPoints();
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
                $new_filename = 'profile_' . $monster_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne photo si elle existe
                    if (!empty($monsterObj->getImageUrl()) && file_exists($monsterObj->getImageUrl())) {
                        unlink($monsterObj->getImageUrl());
                    }
                    
                    // Mettre à jour la base de données pour les monstres
                    $db = Database::getInstance();
                    $stmt = $db->prepare("UPDATE monsters SET image_url = ? WHERE id = ?");
                    if ($stmt->execute([$upload_path, $monster_id])) {
                        $success_message = "Image du monstre mise à jour avec succès.";
                        // Recharger les données du monstre
                        $monsterObj = Monster::findById($monster_id);
                        if ($monsterObj) {
                            // Recharger les données du type de monstre
                            $stmt = $pdo->prepare("
                                SELECT dt.*, dt.type as monster_type_name, dt.image_url
                                FROM dnd_monsters dt
                                WHERE dt.id = ?
                            ");
                            $stmt->execute([$monsterObj->getMonsterTypeId()]);
                            $monsterTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Reconstruire le tableau monster
                            $monster = [
                                'id' => $monsterObj->getId(),
                                'name' => $monsterObj->getName(),
                                'description' => $monsterObj->getDescription(),
                                'hit_points_current' => $monsterObj->getCurrentHitPoints(),
                                'hit_points_max' => $monsterObj->getMaxHitPoints(),
                                'quantity' => $monsterObj->getQuantity(),
                                'is_visible' => $monsterObj->isVisible(),
                                'is_identified' => $monsterObj->isIdentified(),
                                'created_by' => $monsterObj->getCreatedBy(),
                                'created_at' => $monsterObj->getCreatedAt(),
                                'updated_at' => $monsterObj->getUpdatedAt(),
                                'image_url' => $monsterObj->getImageUrl(),
                                'type' => $monsterTypeData['type'],
                                'monster_type_name' => $monsterTypeData['monster_type_name'],
                                'challenge_rating' => $monsterTypeData['challenge_rating'],
                                'size' => $monsterTypeData['size'],
                                'armor_class' => $monsterTypeData['armor_class'],
                                'csv_id' => $monsterTypeData['csv_id'],
                                'special_abilities' => $monsterTypeData['special_abilities'] ?? '',
                                'actions' => $monsterTypeData['actions'] ?? '',
                                'legendary_actions' => $monsterTypeData['legendary_actions'] ?? '',
                                'lair_actions' => $monsterTypeData['lair_actions'] ?? '',
                                'languages' => $monsterTypeData['languages'] ?? '',
                                'skills' => $monsterTypeData['skills'] ?? '',
                                'saving_throws' => $monsterTypeData['saving_throws'] ?? '',
                                'damage_resistances' => $monsterTypeData['damage_resistances'] ?? '',
                                'damage_immunities' => $monsterTypeData['damage_immunities'] ?? '',
                                'condition_immunities' => $monsterTypeData['condition_immunities'] ?? '',
                                'senses' => $monsterTypeData['senses'] ?? '',
                                'speed' => $monsterTypeData['speed'] ?? '',
                                'strength' => $monsterTypeData['strength'] ?? 10,
                                'dexterity' => $monsterTypeData['dexterity'] ?? 10,
                                'constitution' => $monsterTypeData['constitution'] ?? 10,
                                'intelligence' => $monsterTypeData['intelligence'] ?? 10,
                                'wisdom' => $monsterTypeData['wisdom'] ?? 10,
                                'charisma' => $monsterTypeData['charisma'] ?? 10,
                                'class_id' => null,
                                'level' => 1,
                                'race_id' => null,
                                'background_id' => null,
                                'user_id' => $monsterObj->getCreatedBy(),
                                'alignment' => $monsterTypeData['alignment'] ?? '',
                                'proficiency_bonus' => $monsterTypeData['proficiency_bonus'] ?? 0,
                                'experience_points' => 0,
                                'gold' => 0,
                                'silver' => 0,
                                'copper' => 0,
                                'personality_traits' => '',
                                'ideals' => '',
                                'bonds' => '',
                                'flaws' => ''
                            ];
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

// Les monstres n'ont pas d'équipement ni de poisons

// Les modificateurs sont déjà calculés plus haut dans le fichier

// Calcul de l'initiative
$initiative = $dexterityMod;

// La classe d'armure n'est plus affichée, mais on peut la récupérer si nécessaire
$armorClass = $monster['armor_class'] ?? 10;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($monster['name']); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <style>
        .monster-sheet {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-modifier {
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-dnd {
            background: linear-gradient(45deg, #8B4513, #D2691E);
            border: none;
            color: white;
        }
        .btn-dnd:hover {
            background: linear-gradient(45deg, #A0522D, #CD853F);
            color: white;
        }
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .hp-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e74c3c;
        }
        .ac-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .xp-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f39c12;
        }
        .clickable-xp:hover {
            color: #e67e22;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        /* Styles pour les rages */
        .rage-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .rage-symbols {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .rage-symbol {
            width: 50px;
            height: 50px;
            border: 3px solid #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .rage-symbol:hover {
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
    <?php include_once 'includes/navbar.php'; ?>

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
                <i class="fas fa-dragon me-2"></i><?php echo htmlspecialchars($monster['name']); ?>
            </h1>
            <div>
                <?php if (isset($dm_campaign_id) && $dm_campaign_id): ?>
                    <a href="view_campaign.php?id=<?php echo $dm_campaign_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                    </a>
                <?php else: ?>
                    <a href="manage_npcs.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="monster-sheet">
            <!-- En-tête du personnage -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <div class="me-3 position-relative">
                            <?php 
                            // Logique d'image pour les monstres basée sur le type
                            $imageUrl = 'images/default_monster.png';
                            
                            // Essayer d'abord l'image personnalisée si elle existe
                            if (!empty($monsterObj->getImageUrl()) && file_exists($monsterObj->getImageUrl())) {
                                $imageUrl = $monsterObj->getImageUrl();
                            } else {
                                // Sinon, utiliser l'image du monstre par csv_id
                                if (isset($monsterTypeData['csv_id']) && !empty($monsterTypeData['csv_id'])) {
                                    $monsterImagePath = 'images/monstres/' . $monsterTypeData['csv_id'] . '.jpg';
                                    if (file_exists($monsterImagePath)) {
                                        $imageUrl = $monsterImagePath;
                                    } else {
                                        // Fallback vers l'image par défaut
                                        $imageUrl = 'images/default_monster.png';
                                    }
                                } else {
                                    // Fallback vers l'image par défaut
                                    $imageUrl = 'images/default_monster.png';
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>?t=<?php echo time(); ?>" alt="Image de <?php echo htmlspecialchars($monster['name']); ?>" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            
                            <?php if ($canModifyHP): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary position-absolute" style="bottom: -5px; right: -5px;" data-bs-toggle="modal" data-bs-target="#photoModal" title="Changer la photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($monster['name']); ?></h2>
                            <p class="text-muted">
                                <?php echo htmlspecialchars($monsterDetails['race_name']); ?> 
                                <?php echo htmlspecialchars($monsterDetails['class_name']); ?> 
                                niveau <?php echo $monster['level']; ?>
                            </p>
                            <?php if ($monsterDetails['background_name']): ?>
                                <p><strong>Historique:</strong> <?php echo htmlspecialchars($monsterDetails['background_name']); ?></p>
                            <?php endif; ?>
                            <?php if ($monster['alignment']): ?>
                                <p><strong>Alignement:</strong> <?php echo htmlspecialchars($monster['alignment']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($monsterArchetype): ?>
                                <p><strong><?php echo htmlspecialchars($monsterArchetype['archetype_type']); ?>:</strong> <?php echo htmlspecialchars($monsterArchetype['name']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="hp-display"><?php echo $monster['hit_points_current']; ?>/<?php echo $monster['hit_points_max']; ?></div>
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
                                <div class="xp-display">&nbsp;<?php echo $monsterTypeData['challenge_rating']; ?></div>
                                <div class="stat-label">Facteur de Puissance</div>
                                <small class="text-muted">CR <?php echo $monsterTypeData['challenge_rating']; ?></small>
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
                                <td><strong><?php echo $monster['strength']; ?></strong></td>
                                <td><strong><?php echo $monster['dexterity']; ?></strong></td>
                                <td><strong><?php echo $monster['constitution']; ?></strong></td>
                                <td><strong><?php echo $monster['intelligence']; ?></strong></td>
                                <td><strong><?php echo $monster['wisdom']; ?></strong></td>
                                <td><strong><?php echo $monster['charisma']; ?></strong></td>
                            </tr>
                            <!-- Modificateurs de caractéristiques -->
                            <tr class="table-primary">
                                <td><strong>Modificateurs de caractéristiques</strong></td>
                                <?php 
                                // Calculer les modificateurs directement à partir des caractéristiques de base
                                $strMod = $strengthMod;
                                $dexMod = $dexterityMod;
                                $conMod = $constitutionMod;
                                $intMod = $intelligenceMod;
                                $wisMod = $wisdomMod;
                                $chaMod = $charismaMod;
                                ?>
                                <td><strong><?php echo ($strMod >= 0 ? '+' : '') . $strMod; ?></strong></td>
                                <td><strong><?php echo ($dexMod >= 0 ? '+' : '') . $dexMod; ?></strong></td>
                                <td><strong><?php echo ($conMod >= 0 ? '+' : '') . $conMod; ?></strong></td>
                                <td><strong><?php echo ($intMod >= 0 ? '+' : '') . $intMod; ?></strong></td>
                                <td><strong><?php echo ($wisMod >= 0 ? '+' : '') . $wisMod; ?></strong></td>
                                <td><strong><?php echo ($chaMod >= 0 ? '+' : '') . $chaMod; ?></strong></td>
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
                                         onclick="toggleRage(<?php echo $monster_id; ?>, <?php echo $i; ?>)"
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
                        <button class="btn btn-warning" onclick="resetRages(<?php echo $monster_id; ?>)">
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
                        <strong>Bonus de maîtrise:</strong> &nbsp;+<?php echo $monster['proficiency_bonus']; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-fist-raised me-2"></i>Actions</h5>
                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($monsterActions)): ?>
                                    <?php foreach ($monsterActions as $action): ?>
                                        <div class="mb-3">
                                            <strong><?php echo htmlspecialchars($action['name']); ?></strong>
                                            <?php if (!empty($action['description'])): ?>
                                                <p class="mb-0 mt-1 text-muted"><?php echo nl2br(htmlspecialchars($action['description'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($action !== end($monsterActions)): ?>
                                            <hr class="my-2">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-hand-paper fa-2x mb-2"></i>
                                        <p>Aucune action disponible</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attaques spéciales -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-magic me-2"></i>Attaques spéciales</h5>
                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($monsterSpecialAttacks)): ?>
                                    <?php foreach ($monsterSpecialAttacks as $specialAttack): ?>
                                        <div class="mb-3">
                                            <strong><?php echo htmlspecialchars($specialAttack['name']); ?></strong>
                                            <?php if (!empty($specialAttack['description'])): ?>
                                                <p class="mb-0 mt-1 text-muted"><?php echo nl2br(htmlspecialchars($specialAttack['description'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($specialAttack !== end($monsterSpecialAttacks)): ?>
                                            <hr class="my-2">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-ban fa-2x mb-2"></i>
                                        <p>Aucune attaque spéciale disponible</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions légendaires -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-crown me-2"></i>Actions légendaires</h5>
                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($monsterLegendaryActions)): ?>
                                    <?php foreach ($monsterLegendaryActions as $legendaryAction): ?>
                                        <div class="mb-3">
                                            <strong><?php echo htmlspecialchars($legendaryAction['name']); ?></strong>
                                            <?php if (!empty($legendaryAction['description'])): ?>
                                                <p class="mb-0 mt-1 text-muted"><?php echo nl2br(htmlspecialchars($legendaryAction['description'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($legendaryAction !== end($monsterLegendaryActions)): ?>
                                            <hr class="my-2">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-crown fa-2x mb-2"></i>
                                        <p>Aucune action légendaire disponible</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Jets de sauvegarde et Compétences -->
                <?php if (!empty($monster['saving_throws']) || !empty($monster['skills'])): ?>
                <div class="row mt-3">
                    <!-- Jets de sauvegarde -->
                    <?php if (!empty($monster['saving_throws'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-shield-alt me-2"></i>Jets de sauvegarde</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['saving_throws']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Compétences -->
                    <?php if (!empty($monster['skills'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-star me-2"></i>Compétences</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['skills']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Immunités aux dégâts et Résistances aux dégâts -->
                <?php if (!empty($monster['damage_immunities']) || !empty($monster['damage_resistances'])): ?>
                <div class="row mt-3">
                    <!-- Immunités aux dégâts -->
                    <?php if (!empty($monster['damage_immunities'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-shield-virus me-2"></i>Immunités aux dégâts</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['damage_immunities']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Résistances aux dégâts -->
                    <?php if (!empty($monster['damage_resistances'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-shield-alt me-2"></i>Résistances aux dégâts</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['damage_resistances']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Immunités aux états et Sens -->
                <?php if (!empty($monster['condition_immunities']) || !empty($monster['senses'])): ?>
                <div class="row mt-3">
                    <!-- Immunités aux états -->
                    <?php if (!empty($monster['condition_immunities'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-user-shield me-2"></i>Immunités aux états</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['condition_immunities']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Sens -->
                    <?php if (!empty($monster['senses'])): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-eye me-2"></i>Sens</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['senses']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Langues -->
                <?php if (!empty($monster['languages'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-language me-2"></i>Langues</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0"><?php echo htmlspecialchars($monster['languages']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Bouton Grimoire pour les classes de sorts -->
                <?php 
                // Classes qui peuvent lancer des sorts
                $spellcastingClasses = [2, 3, 4, 5, 7, 9, 10, 11]; // Barde, Clerc, Druide, Ensorceleur, Magicien, Occultiste, Paladin, Rôdeur
                $canCastSpells = in_array($monster['class_id'], $spellcastingClasses);
                ?>
                <?php if ($canCastSpells): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <a href="grimoire.php?id=<?php echo $monster_id; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-book-open me-2"></i>Grimoire
                            </a>
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
                                    $ismonsterSkill = in_array($skill, $monsterSkills);
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

            <!-- Informations de race et classe -->
            <div class="row">
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-dragon me-2"></i>Race: <?php echo htmlspecialchars($monsterDetails['race_name']); ?></h3>
                        <p><?php echo htmlspecialchars($monsterDetails['race_description']); ?></p>
                        <p><strong>Bonus de caractéristiques:</strong> 
                            Force: +<?php echo $monsterDetails['strength_bonus']; ?> | 
                            Dextérité: +<?php echo $monsterDetails['dexterity_bonus']; ?> | 
                            Constitution: +<?php echo $monsterDetails['constitution_bonus']; ?> | 
                            Intelligence: +<?php echo $monsterDetails['intelligence_bonus']; ?> | 
                            Sagesse: +<?php echo $monsterDetails['wisdom_bonus']; ?> | 
                            Charisme: +<?php echo $monsterDetails['charisma_bonus']; ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-shield-alt me-2"></i>Classe: <?php echo htmlspecialchars($monsterDetails['class_name']); ?></h3>
                        <p><?php echo htmlspecialchars($monsterDetails['class_description']); ?></p>
                        <p><strong>Dé de vie:</strong> &nbsp;<?php echo $monsterDetails['hit_dice']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <?php if ($monsterDetails['background_name']): ?>
            <div class="info-section">
                <h3><i class="fas fa-book me-2"></i>Historique: <?php echo htmlspecialchars($monsterDetails['background_name']); ?></h3>
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
                                            <h4 class="text-warning mb-1"><?php echo $monster['gold']; ?></h4>
                                            <small class="text-muted">PO</small>
                                            <br><small>Pièces d'or</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3 bg-secondary bg-opacity-10">
                                            <h4 class="text-secondary mb-1"><?php echo $monster['silver']; ?></h4>
                                            <small class="text-muted">PA</small>
                                            <br><small>Pièces d'argent</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-3 bg-danger bg-opacity-10">
                                            <h4 class="text-danger mb-1"><?php echo $monster['copper']; ?></h4>
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
                                $totalCopper = ($monster['gold'] * 100) + ($monster['silver'] * 10) + $monster['copper'];
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



            <!-- Informations personnelles -->
            <?php if ($monster['personality_traits'] || $monster['ideals'] || $monster['bonds'] || $monster['flaws']): ?>
                <div class="info-section">
                    <h3><i class="fas fa-user-edit me-2"></i>Informations Personnelles</h3>
                    <div class="row">
                        <?php if ($monster['personality_traits']): ?>
                            <div class="col-md-6">
                                <p><strong>Traits de personnalité:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($monster['personality_traits'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($monster['ideals']): ?>
                            <div class="col-md-6">
                                <p><strong>Idéaux:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($monster['ideals'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($monster['bonds']): ?>
                            <div class="col-md-6">
                                <p><strong>Liens:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($monster['bonds'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($monster['flaws']): ?>
                            <div class="col-md-6">
                                <p><strong>Défauts:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($monster['flaws'])); ?></p>
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
                        Gestion des Points de Vie - <?php echo htmlspecialchars($monster['name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Barre de Points de Vie -->
                    <div class="mb-4">
                        <h6>Points de Vie Actuels</h6>
                        <?php
                        $current_hp = $monster['hit_points_current'] ?? 0;
                        $max_hp = $monster['hit_points_max'] ?? 1; // Éviter la division par zéro
                        $hp_percentage = $max_hp > 0 ? ($current_hp / $max_hp) * 100 : 0;
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
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(1)">-1</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(5)">-5</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(10)">-10</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(20)">-20</button>
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
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(1)">+1</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(5)">+5</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(10)">+10</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickHeal(20)">+20</button>
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
                                <input type="hidden" name="max_hp" value="<?php echo $monster['hit_points_max']; ?>">
                                <div class="d-flex gap-2">
                                    <input type="number" name="current_hp" class="form-control form-control-sm" 
                                           value="<?php echo $monster['hit_points_current']; ?>" 
                                           min="0" max="<?php echo $monster['hit_points_max']; ?>" required>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Maximum : <?php echo $monster['hit_points_max']; ?> PV</small>
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
        function quickDamage(amount) {
            if (confirm(`Infliger ${amount} points de dégâts à <?php echo htmlspecialchars($monster['name']); ?> ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="hp_action" value="damage">
                    <input type="hidden" name="damage" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function quickHeal(amount) {
            if (confirm(`Appliquer ${amount} points de soins à <?php echo htmlspecialchars($monster['name']); ?> ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="hp_action" value="heal">
                    <input type="hidden" name="healing" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }


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
                select.innerHTML += '<option value="monster_1">Hyphrédicte (Robin)</option>';
                select.innerHTML += '<option value="monster_2">Lieutenant Cameron (MJ)</option>';
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

        // Fonctions pour gérer les rages
        function toggleRage(monsterId, rageNumber) {
            const rageSymbol = document.querySelector(`[data-rage="${rageNumber}"]`);
            const isUsed = rageSymbol.classList.contains('used');
            
            const action = isUsed ? 'free' : 'use';
            
            fetch('manage_rage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    monster_id: monsterId,
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

        function resetRages(monsterId) {
            if (confirm('Effectuer un long repos ? Cela récupérera toutes les rages.')) {
                fetch('manage_rage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        monster_id: monsterId,
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


