<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Fiche de Personnage";
$current_page = "view_character";


requireLogin();

if (!isset($_GET['id'])) {
    header('Location: characters.php');
    exit();
}

$character_id = (int)$_GET['id'];
$dm_campaign_id = isset($_GET['dm_campaign_id']) ? (int)$_GET['dm_campaign_id'] : null;

// Récupération du personnage avec ses détails (sans filtrer par propriétaire)
$stmt = $pdo->prepare("
    SELECT c.*, r.name as race_name, r.description as race_description, 
           r.strength_bonus, r.dexterity_bonus, r.constitution_bonus, 
           r.intelligence_bonus, r.wisdom_bonus, r.charisma_bonus, r.traits,
           r.languages as race_languages,
           cl.name as class_name, cl.description as class_description, cl.hit_dice,
           b.name as background_name, b.description as background_description, 
           b.skill_proficiencies as background_skills, b.tool_proficiencies as background_tools,
           b.languages as background_languages, b.feature as background_feature
    FROM characters c 
    JOIN races r ON c.race_id = r.id 
    JOIN classes cl ON c.class_id = cl.id 
    LEFT JOIN backgrounds b ON c.background_id = b.id
    WHERE c.id = ?
");
$stmt->execute([$character_id]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit();
}

// L'équipement final est déjà stocké dans le champ equipment du personnage
// Plus besoin de parser l'équipement de départ séparément

// Parser les données JSON du personnage
$characterSkills = $character['skills'] ? json_decode($character['skills'], true) : [];
$characterLanguages = $character['languages'] ? json_decode($character['languages'], true) : [];

// Parser les données de l'historique
$backgroundSkills = $character['background_skills'] ? json_decode($character['background_skills'], true) : [];
$backgroundTools = $character['background_tools'] ? json_decode($character['background_tools'], true) : [];
$backgroundLanguages = $character['background_languages'] ? json_decode($character['background_languages'], true) : [];

// Récupérer les données de rage pour les barbares
$isBarbarian = strpos(strtolower($character['class_name']), 'barbare') !== false;
$isBard = strpos(strtolower($character['class_name']), 'barde') !== false;
$isCleric = strpos(strtolower($character['class_name']), 'clerc') !== false;
$isDruid = strpos(strtolower($character['class_name']), 'druide') !== false;
$isSorcerer = strpos(strtolower($character['class_name']), 'ensorceleur') !== false;
$isFighter = strpos(strtolower($character['class_name']), 'guerrier') !== false;
$isWizard = strpos(strtolower($character['class_name']), 'magicien') !== false;
$isMonk = strpos(strtolower($character['class_name']), 'moine') !== false;
$isWarlock = strpos(strtolower($character['class_name']), 'occultiste') !== false;
$isPaladin = strpos(strtolower($character['class_name']), 'paladin') !== false;
$isRanger = strpos(strtolower($character['class_name']), 'rôdeur') !== false;
$isRogue = strpos(strtolower($character['class_name']), 'roublard') !== false;
$rageData = null;
if ($isBarbarian) {
    // Récupérer le nombre maximum de rages pour ce niveau
    $stmt = $pdo->prepare("SELECT rages FROM class_evolution WHERE class_id = ? AND level = ?");
    $stmt->execute([$character['class_id'], $character['level']]);
    $evolution = $stmt->fetch();
    $maxRages = $evolution ? $evolution['rages'] : 0;
    
    // Récupérer le nombre de rages utilisées
    $usedRages = getRageUsage($character_id);
    
    $rageData = [
        'max' => $maxRages,
        'used' => $usedRages,
        'available' => $maxRages - $usedRages
    ];
}

// Récupérer les capacités de classe et de race
$classCapabilities = [];
$raceCapabilities = [];

// Capacités de classe basées sur le niveau
if ($isBarbarian) {
    $classCapabilities = getBarbarianCapabilities($character['level']);
} elseif ($isBard) {
    $classCapabilities = getBardCapabilities($character['level']);
} elseif ($isCleric) {
    $classCapabilities = getClericCapabilities($character['level']);
} elseif ($isDruid) {
    $classCapabilities = getDruidCapabilities($character['level']);
} elseif ($isSorcerer) {
    $classCapabilities = getSorcererCapabilities($character['level']);
} elseif ($isFighter) {
    $classCapabilities = getFighterCapabilities($character['level']);
} elseif ($isWizard) {
    $classCapabilities = getWizardCapabilities($character['level']);
} elseif ($isMonk) {
    $classCapabilities = getMonkCapabilities($character['level']);
} elseif ($isWarlock) {
    $classCapabilities = getWarlockCapabilities($character['level']);
} elseif ($isPaladin) {
    $classCapabilities = getPaladinCapabilities($character['level']);
} elseif ($isRanger) {
    $classCapabilities = getRangerCapabilities($character['level']);
} elseif ($isRogue) {
    $classCapabilities = getRogueCapabilities($character['level']);
}

// Capacités raciales
if ($character['traits']) {
    $raceCapabilities[] = [
        'name' => 'Traits raciaux',
        'description' => $character['traits']
    ];
}

// Récupérer la voie primitive du barbare
$barbarianPath = null;
if ($isBarbarian) {
    $barbarianPath = getCharacterBarbarianPath($character_id);
}

// Récupérer le serment sacré du paladin
$paladinOath = null;
if ($isPaladin) {
    $paladinOath = getCharacterPaladinOath($character_id);
}

// Récupérer l'archétype de rôdeur
$rangerArchetype = null;
if ($isRanger) {
    $rangerArchetype = getCharacterRangerArchetype($character_id);
}

// Récupérer l'archétype de roublard
$rogueArchetype = null;
if ($isRogue) {
    $rogueArchetype = getCharacterRogueArchetype($character_id);
}

// Récupérer le collège bardique du barde
$bardCollege = null;
if ($isBard) {
    $bardCollege = getCharacterBardCollege($character_id);
}

// Récupérer le domaine divin du clerc
$clericDomain = null;
if ($isCleric) {
    $clericDomain = getCharacterClericDomain($character_id);
}

// Récupérer le cercle druidique du druide
$druidCircle = null;
if ($isDruid) {
    $druidCircle = getCharacterDruidCircle($character_id);
}

// Récupérer l'origine magique de l'ensorceleur
$sorcererOrigin = null;
if ($isSorcerer) {
    $sorcererOrigin = getCharacterSorcererOrigin($character_id);
}

// Récupérer l'archétype martial du guerrier
$fighterArchetype = null;
if ($isFighter) {
    $fighterArchetype = getCharacterFighterArchetype($character_id);
}

// Récupérer la tradition arcanique du magicien
$wizardTradition = null;
if ($isWizard) {
    $wizardTradition = getCharacterWizardTradition($character_id);
}

// Récupérer la tradition monastique du moine
$monkTradition = null;
if ($isMonk) {
    $monkTradition = getCharacterMonkTradition($character_id);
}

// Récupérer la faveur de pacte de l'occultiste
$warlockPact = null;
if ($isWarlock) {
    $warlockPact = getCharacterWarlockPact($character_id);
}

// Récupérer les améliorations de caractéristiques
$abilityImprovements = getCharacterAbilityImprovements($character_id);

// Calculer les caractéristiques finales
$finalAbilities = calculateFinalAbilities($character, $abilityImprovements);

// Calculer les points d'amélioration restants
$remainingPoints = getRemainingAbilityPoints($character['level'], $abilityImprovements);

// Parser les langues raciales
$raceLanguages = [];
if ($character['race_languages']) {
    // Les langues raciales sont stockées comme texte, pas JSON
    $raceLanguages = array_map('trim', explode(',', $character['race_languages']));
}

// Filtrer les mentions génériques de choix de langues
$filteredRaceLanguages = array_filter($raceLanguages, function($lang) {
    return !preg_match('/une? (langue )?de votre choix/i', $lang);
});

$filteredBackgroundLanguages = array_filter($backgroundLanguages, function($lang) {
    return !preg_match('/une? (langue )?de votre choix/i', $lang);
});

$filteredCharacterLanguages = array_filter($characterLanguages, function($lang) {
    return !preg_match('/une? (langue )?de votre choix/i', $lang);
});

// Combiner toutes les langues (sans les mentions génériques)
$allLanguages = array_unique(array_merge($filteredCharacterLanguages, $filteredRaceLanguages, $filteredBackgroundLanguages));

// Calcul des modificateurs (nécessaire pour le calcul de la CA)
// Utiliser les valeurs totales incluant les bonus raciaux
$strengthMod = getAbilityModifier($character['strength'] + $character['strength_bonus']);
$dexterityMod = getAbilityModifier($character['dexterity'] + $character['dexterity_bonus']);
$constitutionMod = getAbilityModifier($character['constitution'] + $character['constitution_bonus']);
$intelligenceMod = getAbilityModifier($character['intelligence'] + $character['intelligence_bonus']);
$wisdomMod = getAbilityModifier($character['wisdom'] + $character['wisdom_bonus']);
$charismaMod = getAbilityModifier($character['charisma'] + $character['charisma_bonus']);

// Synchroniser l'équipement de base vers character_equipment
syncBaseEquipmentToCharacterEquipment($character_id);

// Récupérer l'équipement équipé du personnage
$equippedItems = getCharacterEquippedItems($character_id);

// Détecter les armes, armures et boucliers dans l'équipement
$detectedWeapons = detectWeaponsInEquipment($character['equipment']);
$detectedArmor = detectArmorInEquipment($character['equipment']);
$detectedShields = detectShieldsInEquipment($character['equipment']);

// Calculer la classe d'armure
$equippedArmor = null;
$equippedShield = null;

if ($equippedItems['armor']) {
    foreach ($detectedArmor as $armor) {
        if ($armor['name'] === $equippedItems['armor']) {
            $equippedArmor = $armor;
            break;
        }
    }
}

if ($equippedItems['shield']) {
    foreach ($detectedShields as $shield) {
        if ($shield['name'] === $equippedItems['shield']) {
            $equippedShield = $shield;
            break;
        }
    }
}

// Ajouter le modificateur de Dextérité au tableau character pour la fonction
$character['dexterity_modifier'] = $dexterityMod;
$armorClass = calculateArmorClassExtended($character, $equippedArmor, $equippedShield);

// Contrôle d'accès: propriétaire OU MJ de la campagne liée
$canView = ($character['user_id'] == $_SESSION['user_id']);

if (!$canView && isDMOrAdmin() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté ou que l'utilisateur est admin
    $ownsCampaign = false;
    if (isAdmin()) {
        $ownsCampaign = true; // Les admins peuvent voir toutes les feuilles
    } else {
        $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
        $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
        $ownsCampaign = (bool)$stmt->fetch();
    }

    if ($ownsCampaign) {
        // Vérifier que le joueur propriétaire du personnage est membre ou a candidaté à cette campagne
        $owner_user_id = (int)$character['user_id'];
        $isMember = false;
        $hasApplied = false;

        $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$dm_campaign_id, $owner_user_id]);
        $isMember = (bool)$stmt->fetch();

        if (!$isMember) {
            $stmt = $pdo->prepare("SELECT 1 FROM campaign_applications WHERE campaign_id = ? AND player_id = ? LIMIT 1");
            $stmt->execute([$dm_campaign_id, $owner_user_id]);
            $hasApplied = (bool)$stmt->fetch();
        }

        $canView = ($isMember || $hasApplied);
    }
}

if (!$canView) {
    header('Location: characters.php');
    exit();
}

// Vérifier si l'utilisateur peut modifier les points de vie (propriétaire ou MJ)
$canModifyHP = ($character['user_id'] == $_SESSION['user_id']);
if (!$canModifyHP && isDMOrAdmin() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté ou que l'utilisateur est admin
    if (isAdmin()) {
        $canModifyHP = true; // Les admins peuvent modifier toutes les feuilles
    } else {
        $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
        $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
        $canModifyHP = (bool)$stmt->fetch();
    }
    
    // Si c'est un MJ et qu'il a accès à la campagne, il peut modifier les PV
    if ($canModifyHP) {
        // Vérifier que le propriétaire du personnage est membre de la campagne
        $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$dm_campaign_id, $character['user_id']]);
        $isMember = (bool)$stmt->fetch();
        
        if (!$isMember) {
            // Vérifier si le propriétaire a candidaté à la campagne
            $stmt = $pdo->prepare("SELECT 1 FROM campaign_applications WHERE campaign_id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$dm_campaign_id, $character['user_id']]);
            $hasApplied = (bool)$stmt->fetch();
            
            $canModifyHP = $hasApplied;
        }
    }
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
            $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
            $stmt->execute([$new_hp, $character_id]);
            
            $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
            break;
            
        case 'damage':
            $damage = (int)$_POST['damage'];
            if ($damage > 0) {
                $new_hp = max(0, $character['hit_points_current'] - $damage);
                $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                $stmt->execute([$new_hp, $character_id]);
                
                $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
            }
            break;
            
        case 'heal':
            $healing = (int)$_POST['healing'];
            if ($healing > 0) {
                $new_hp = min($character['hit_points_max'], $character['hit_points_current'] + $healing);
                $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                $stmt->execute([$new_hp, $character_id]);
                
                $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
            }
            break;
            
        case 'reset_hp':
            $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
            $stmt->execute([$character['hit_points_max'], $character_id]);
            
            $success_message = "Points de vie réinitialisés au maximum : {$character['hit_points_max']}";
            break;
    }
    
    // Recharger les données du personnage
    $stmt = $pdo->prepare("
        SELECT c.*, r.name as race_name, r.description as race_description, r.strength_bonus, r.dexterity_bonus, r.constitution_bonus, 
               r.intelligence_bonus, r.wisdom_bonus, r.charisma_bonus, r.traits,
               cl.name as class_name, cl.description as class_description, cl.hit_dice
        FROM characters c 
        LEFT JOIN races r ON c.race_id = r.id 
        LEFT JOIN classes cl ON c.class_id = cl.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$character_id]);
    $character = $stmt->fetch();
}

// Traitement des actions POST pour la gestion des points d'expérience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['xp_action'])) {
    switch ($_POST['xp_action']) {
        case 'add':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = $character['experience_points'] + $xp_amount;
                $stmt = $pdo->prepare("UPDATE characters SET experience_points = ? WHERE id = ?");
                $stmt->execute([$new_xp, $character_id]);
                
                $success_message = "Points d'expérience ajoutés : +{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'remove':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount > 0) {
                $new_xp = max(0, $character['experience_points'] - $xp_amount);
                $stmt = $pdo->prepare("UPDATE characters SET experience_points = ? WHERE id = ?");
                $stmt->execute([$new_xp, $character_id]);
                
                $success_message = "Points d'expérience retirés : -{$xp_amount} XP. Total : " . number_format($new_xp) . " XP";
            }
            break;
            
        case 'set':
            $xp_amount = (int)$_POST['xp_amount'];
            if ($xp_amount >= 0) {
                $stmt = $pdo->prepare("UPDATE characters SET experience_points = ? WHERE id = ?");
                $stmt->execute([$xp_amount, $character_id]);
                
                $success_message = "Points d'expérience définis à : " . number_format($xp_amount) . " XP";
            }
            break;
    }
    
    // Recharger les données du personnage après modification des XP
    if (isset($success_message)) {
        $stmt = $pdo->prepare("
            SELECT c.*, r.name as race_name, r.description as race_description, r.strength_bonus, r.dexterity_bonus, r.constitution_bonus, 
                   r.intelligence_bonus, r.wisdom_bonus, r.charisma_bonus, r.traits,
                   cl.name as class_name, cl.description as class_description, cl.hit_dice
            FROM characters c 
            LEFT JOIN races r ON c.race_id = r.id 
            LEFT JOIN classes cl ON c.class_id = cl.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$character_id]);
        $character = $stmt->fetch();
    }
}

// Recharger les données du personnage
    $stmt = $pdo->prepare("
        SELECT c.*, r.name as race_name, r.description as race_description, r.strength_bonus, r.dexterity_bonus, r.constitution_bonus, 
               r.intelligence_bonus, r.wisdom_bonus, r.charisma_bonus, r.traits,
               cl.name as class_name, cl.description as class_description, cl.hit_dice
        FROM characters c 
        JOIN races r ON c.race_id = r.id 
        JOIN classes cl ON c.class_id = cl.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$character_id]);
    $character = $stmt->fetch();

// Traitement du transfert d'objets magiques
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canModifyHP && isset($_POST['action']) && $_POST['action'] === 'transfer_item') {
    $item_id = (int)$_POST['item_id'];
    $target = $_POST['target'];
    $notes = $_POST['notes'] ?? '';
    $source = $_POST['source'] ?? 'character_equipment';
    
    // Récupérer les informations de l'objet à transférer selon la source
    $item = null;
    if ($source === 'npc_equipment') {
        // Récupérer depuis npc_equipment via le personnage associé
        $stmt = $pdo->prepare("
            SELECT ne.*, sn.name as npc_name, sn.place_id, s.title as scene_title
            FROM npc_equipment ne
            JOIN place_npcs sn ON ne.npc_id = sn.id AND ne.scene_id = sn.place_id
            JOIN places s ON sn.place_id = s.id
            WHERE ne.id = ? AND sn.npc_character_id = ?
        ");
        $stmt->execute([$item_id, $character_id]);
        $item = $stmt->fetch();
    } else {
        // Récupérer depuis character_equipment
        $stmt = $pdo->prepare("SELECT * FROM character_equipment WHERE id = ? AND character_id = ?");
        $stmt->execute([$item_id, $character_id]);
        $item = $stmt->fetch();
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
                $stmt = $pdo->prepare("SELECT name FROM characters WHERE id = ?");
                $stmt->execute([$target_id]);
                $target_char = $stmt->fetch();
                
                if ($target_char) {
                    // Insérer dans character_equipment du nouveau propriétaire
                    $stmt = $pdo->prepare("INSERT INTO character_equipment (character_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $target_id,
                        $item['magical_item_id'],
                        $item['item_name'],
                        $item['item_type'],
                        $item['item_description'],
                        $item['item_source'],
                        $item['quantity'],
                        0, // Toujours non équipé lors du transfert (0 = false)
                        $notes ?: $item['notes'],
                        'Transfert depuis ' . $character['name']
                    ]);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ?");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM character_equipment WHERE id = ?");
                    }
                    $stmt->execute([$item_id]);
                    
                    $transfer_success = true;
                    $target_name = $target_char['name'];
                }
                break;
                
            case 'monster':
                // Transférer vers un monstre
                $stmt = $pdo->prepare("SELECT sn.name, sn.place_id FROM place_npcs sn WHERE sn.id = ?");
                $stmt->execute([$target_id]);
                $target_monster = $stmt->fetch();
                
                if ($target_monster) {
                    // Insérer dans monster_equipment
                    $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $target_id,
                        $target_monster['place_id'],
                        $item['magical_item_id'],
                        $item['item_name'],
                        $item['item_type'],
                        $item['item_description'],
                        $item['item_source'],
                        $item['quantity'],
                        0, // Toujours non équipé lors du transfert (0 = false)
                        $notes ?: $item['notes'],
                        'Transfert depuis ' . $character['name']
                    ]);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ?");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM character_equipment WHERE id = ?");
                    }
                    $stmt->execute([$item_id]);
                    
                    $transfer_success = true;
                    $target_name = $target_monster['name'];
                }
                break;
                
            case 'npc':
                // Transférer vers un PNJ
                $stmt = $pdo->prepare("SELECT sn.name, sn.place_id FROM place_npcs sn WHERE sn.id = ?");
                $stmt->execute([$target_id]);
                $target_npc = $stmt->fetch();
                
                if ($target_npc) {
                    // Insérer dans npc_equipment
                    $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $target_id,
                        $target_npc['place_id'],
                        $item['magical_item_id'],
                        $item['item_name'],
                        $item['item_type'],
                        $item['item_description'],
                        $item['item_source'],
                        $item['quantity'],
                        0, // Toujours non équipé lors du transfert (0 = false)
                        $notes ?: $item['notes'],
                        'Transfert depuis ' . $character['name']
                    ]);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ?");
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM character_equipment WHERE id = ?");
                    }
                    $stmt->execute([$item_id]);
                    
                    $transfer_success = true;
                    $target_name = $target_npc['name'];
                }
                break;
        }
        
        if ($transfer_success) {
            $success_message = "Objet '{$item['item_name']}' transféré vers {$target_name} avec succès.";
        } else {
            $error_message = "Erreur lors du transfert de l'objet.";
        }
    }
    
    // Recharger les données du personnage
    $stmt = $pdo->prepare("
        SELECT c.*, r.name as race_name, r.description as race_description, r.strength_bonus, r.dexterity_bonus, r.constitution_bonus, 
               r.intelligence_bonus, r.wisdom_bonus, r.charisma_bonus, r.traits,
               cl.name as class_name, cl.description as class_description, cl.hit_dice
        FROM characters c 
        JOIN races r ON c.race_id = r.id 
        JOIN classes cl ON c.class_id = cl.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$character_id]);
    $character = $stmt->fetch();
}

// Récupérer l'équipement magique du personnage (incluant les objets attribués depuis les lieux, excluant les poisons)
$stmt = $pdo->prepare("
    SELECT ce.*, mi.nom as magical_item_nom, mi.type as magical_item_type, mi.description as magical_item_description, mi.source as magical_item_source
    FROM character_equipment ce
    LEFT JOIN magical_items mi ON ce.magical_item_id = mi.csv_id
    WHERE ce.character_id = ? 
    AND (ce.magical_item_id IS NULL OR ce.magical_item_id NOT IN (SELECT csv_id FROM poisons))
    ORDER BY ce.obtained_at DESC
");
$stmt->execute([$character_id]);
$magicalEquipment = $stmt->fetchAll();

// Récupérer les poisons du personnage (stockés dans character_equipment avec magical_item_id correspondant à un poison)
$stmt = $pdo->prepare("
    SELECT ce.*, p.nom as poison_nom, p.type as poison_type, p.description as poison_description, p.source as poison_source
    FROM character_equipment ce
    JOIN poisons p ON ce.magical_item_id = p.csv_id
    WHERE ce.character_id = ? 
    ORDER BY ce.obtained_at DESC
");
$stmt->execute([$character_id]);
$characterPoisons = $stmt->fetchAll();

// Récupérer l'équipement attribué aux PNJ associés à ce personnage
$stmt = $pdo->prepare("
    SELECT ne.*, sn.name as npc_name, sn.place_id, s.title as scene_title
    FROM npc_equipment ne
    JOIN place_npcs sn ON ne.npc_id = sn.id AND ne.scene_id = sn.place_id
    JOIN places s ON sn.place_id = s.id
    WHERE sn.npc_character_id = ?
    ORDER BY ne.obtained_at DESC
");
$stmt->execute([$character_id]);
$npcEquipment = $stmt->fetchAll();

// Séparer les objets magiques et poisons des PNJ
$npcMagicalEquipment = [];
$npcPoisons = [];

foreach ($npcEquipment as $item) {
    // Vérifier d'abord si c'est un poison
    $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
    $stmt->execute([$item['magical_item_id']]);
    $poison_info = $stmt->fetch();
    
    if ($poison_info) {
        // C'est un poison
        $item['poison_nom'] = $poison_info['nom'];
        $item['poison_type'] = $poison_info['type'];
        $item['poison_description'] = $poison_info['description'];
        $item['poison_source'] = $poison_info['source'];
        $npcPoisons[] = $item;
    } else {
        // Vérifier si c'est un objet magique
        $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
        $stmt->execute([$item['magical_item_id']]);
        $magical_info = $stmt->fetch();
        
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
$allMagicalEquipment = array_merge($magicalEquipment, $npcMagicalEquipment);
$allPoisons = array_merge($characterPoisons, $npcPoisons);

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
    <style>
        .character-sheet {
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
    </style>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($character['name']); ?>
            </h1>
            <div>
                <?php if ($dm_campaign_id): ?>
                    <a href="view_campaign.php?id=<?php echo $dm_campaign_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                    </a>
                <?php else: ?>
                    <a href="characters.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="character-sheet">
            <!-- En-tête du personnage -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-start">
                        <div class="me-4">
                            <?php if (!empty($character['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($character['name']); ?>" class="rounded" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                    <i class="fas fa-user text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($character['name']); ?></h2>
                            <p class="text-muted">
                                <?php echo htmlspecialchars($character['race_name']); ?> 
                                <?php echo htmlspecialchars($character['class_name']); ?> 
                                niveau <?php echo $character['level']; ?>
                            </p>
                            <?php if ($character['background']): ?>
                                <p><strong>Historique:</strong> <?php echo htmlspecialchars($character['background']); ?></p>
                            <?php endif; ?>
                            <?php if ($character['alignment']): ?>
                                <p><strong>Alignement:</strong> <?php echo htmlspecialchars($character['alignment']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="hp-display">&nbsp;<?php echo $character['hit_points_current']; ?></div>
                                <div class="stat-label">Points de Vie</div>
                                <small class="text-muted">/ <?php echo $character['hit_points_max']; ?></small>
                                <?php if ($canModifyHP): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#hpModal" title="Gérer les points de vie">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="ac-display">&nbsp;<?php echo $armorClass; ?></div>
                                <div class="stat-label">Classe d'Armure</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box">
                                <?php if ($canModifyHP): ?>
                                    <div class="xp-display clickable-xp" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience" style="cursor: pointer;">&nbsp;<?php echo number_format($character['experience_points']); ?></div>
                                <?php else: ?>
                                    <div class="xp-display">&nbsp;<?php echo number_format($character['experience_points']); ?></div>
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
                                <td><span class="text-success"><?php echo ($character['strength_bonus'] > 0 ? '+' : '') . $character['strength_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($character['dexterity_bonus'] > 0 ? '+' : '') . $character['dexterity_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($character['constitution_bonus'] > 0 ? '+' : '') . $character['constitution_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($character['intelligence_bonus'] > 0 ? '+' : '') . $character['intelligence_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($character['wisdom_bonus'] > 0 ? '+' : '') . $character['wisdom_bonus']; ?></span></td>
                                <td><span class="text-success"><?php echo ($character['charisma_bonus'] > 0 ? '+' : '') . $character['charisma_bonus']; ?></span></td>
                            </tr>
                            <!-- Bonus de niveau -->
                            <tr>
                                <td><strong>Bonus de niveau (<?php echo $remainingPoints; ?> pts restants)</strong></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['strength_bonus'] > 0 ? '+' : '') . $abilityImprovements['strength_bonus']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['dexterity_bonus'] > 0 ? '+' : '') . $abilityImprovements['dexterity_bonus']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['constitution_bonus'] > 0 ? '+' : '') . $abilityImprovements['constitution_bonus']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['intelligence_bonus'] > 0 ? '+' : '') . $abilityImprovements['intelligence_bonus']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['wisdom_bonus'] > 0 ? '+' : '') . $abilityImprovements['wisdom_bonus']; ?></span></td>
                                <td><span class="text-warning"><?php echo ($abilityImprovements['charisma_bonus'] > 0 ? '+' : '') . $abilityImprovements['charisma_bonus']; ?></span></td>
                            </tr>
                            <!-- Bonus d'équipements -->
                            <tr>
                                <td><strong>Bonus d'équipements</strong></td>
                                <td><span class="text-info">+0</span></td>
                                <td><span class="text-info">+0</span></td>
                                <td><span class="text-info">+0</span></td>
                                <td><span class="text-info">+0</span></td>
                                <td><span class="text-info">+0</span></td>
                                <td><span class="text-info">+0</span></td>
                            </tr>
                            <!-- Bonus temporaires -->
                            <tr>
                                <td><strong>Bonus temporaires</strong></td>
                                <td><span class="text-warning">+0</span></td>
                                <td><span class="text-warning">+0</span></td>
                                <td><span class="text-warning">+0</span></td>
                                <td><span class="text-warning">+0</span></td>
                                <td><span class="text-warning">+0</span></td>
                                <td><span class="text-warning">+0</span></td>
                            </tr>
                            <!-- Total -->
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $finalAbilities['strength']; ?> (<?php echo (getAbilityModifier($finalAbilities['strength']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['strength']); ?>)</strong></td>
                                <td><strong><?php echo $finalAbilities['dexterity']; ?> (<?php echo (getAbilityModifier($finalAbilities['dexterity']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['dexterity']); ?>)</strong></td>
                                <td><strong><?php echo $finalAbilities['constitution']; ?> (<?php echo (getAbilityModifier($finalAbilities['constitution']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['constitution']); ?>)</strong></td>
                                <td><strong><?php echo $finalAbilities['intelligence']; ?> (<?php echo (getAbilityModifier($finalAbilities['intelligence']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['intelligence']); ?>)</strong></td>
                                <td><strong><?php echo $finalAbilities['wisdom']; ?> (<?php echo (getAbilityModifier($finalAbilities['wisdom']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['wisdom']); ?>)</strong></td>
                                <td><strong><?php echo $finalAbilities['charisma']; ?> (<?php echo (getAbilityModifier($finalAbilities['charisma']) >= 0 ? '+' : '') . getAbilityModifier($finalAbilities['charisma']); ?>)</strong></td>
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
                                         onclick="toggleRage(<?php echo $character_id; ?>, <?php echo $i; ?>)"
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
                        <button class="btn btn-warning" onclick="resetRages(<?php echo $character_id; ?>)">
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
                        <strong>Bonus de maîtrise:</strong> &nbsp;+<?php echo $character['proficiency_bonus']; ?>
                    </div>
                </div>
                
                <!-- Classe d'armure -->
                <div class="row mt-3">
                    <div class="col-12">
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
                </div>
                
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

            <!-- Traits et capacités -->
            <div class="info-section">
                <h3><i class="fas fa-magic me-2"></i>Traits et Capacités</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-dragon me-2"></i>Traits raciaux</h5>
                        <?php if ($character['traits']): ?>
                            <p><?php echo nl2br(htmlspecialchars($character['traits'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Aucun trait racial</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-gift me-2"></i>Capacité spéciale</h5>
                        <?php if ($character['background_feature']): ?>
                            <p><strong><?php echo htmlspecialchars($character['background_name']); ?> :</strong></p>
                            <p><?php echo htmlspecialchars($character['background_feature']); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Aucune capacité spéciale</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Capacités -->
            <div class="info-section">
                <h3><i class="fas fa-star me-2"></i>Capacités</h3>
                <div class="row">
                    <!-- Capacités de classe -->
                    <?php if (!empty($classCapabilities)): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-shield-alt me-2"></i>Capacités de classe</h5>
                        <div class="capabilities-list">
                            <?php foreach ($classCapabilities as $capability): ?>
                                <div class="capability-item mb-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-primary">
                                            <i class="fas fa-fire me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Capacités raciales -->
                    <?php if (!empty($raceCapabilities)): ?>
                    <div class="col-md-6">
                        <h5><i class="fas fa-dragon me-2"></i>Capacités raciales</h5>
                        <div class="capabilities-list">
                            <?php foreach ($raceCapabilities as $capability): ?>
                                <div class="capability-item mb-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-success">
                                            <i class="fas fa-magic me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Message si aucune capacité -->
                <?php if (empty($classCapabilities) && empty($raceCapabilities) && !$barbarianPath && !$bardCollege && !$clericDomain && !$druidCircle && !$sorcererOrigin && !$fighterArchetype && !$wizardTradition && !$monkTradition && !$warlockPact): ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>Aucune capacité spéciale
                    </div>
                <?php endif; ?>
                
                <!-- Voie primitive du barbare -->
                <?php if ($barbarianPath): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-route me-2"></i>Voie primitive</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-fire me-1"></i><?php echo htmlspecialchars($barbarianPath['path_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($barbarianPath['path_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de voie primitive par niveau -->
                            <?php
                            $pathCapabilities = [];
                            
                            // Niveau 3 - Capacité de voie primitive
                            if ($character['level'] >= 3 && !empty($barbarianPath['level_3_feature'])) {
                                $pathCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $barbarianPath['level_3_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de voie primitive
                            if ($character['level'] >= 6 && !empty($barbarianPath['level_6_feature'])) {
                                $pathCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $barbarianPath['level_6_feature']
                                ];
                            }
                            
                            // Niveau 10 - Capacité de voie primitive
                            if ($character['level'] >= 10 && !empty($barbarianPath['level_10_feature'])) {
                                $pathCapabilities[] = [
                                    'name' => 'Capacité de niveau 10',
                                    'description' => $barbarianPath['level_10_feature']
                                ];
                            }
                            
                            // Niveau 14 - Capacité de voie primitive
                            if ($character['level'] >= 14 && !empty($barbarianPath['level_14_feature'])) {
                                $pathCapabilities[] = [
                                    'name' => 'Capacité de niveau 14',
                                    'description' => $barbarianPath['level_14_feature']
                                ];
                            }
                            
                            // Afficher les capacités de voie primitive
                            foreach ($pathCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Serment sacré du paladin -->
                <?php if ($paladinOath): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-cross me-2"></i>Serment sacré</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-cross me-1"></i><?php echo htmlspecialchars($paladinOath['oath_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($paladinOath['oath_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de serment sacré par niveau -->
                            <?php
                            $oathCapabilities = [];
                            
                            // Niveau 3 - Capacité de serment sacré
                            if ($character['level'] >= 3 && !empty($paladinOath['level_3_feature'])) {
                                $oathCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $paladinOath['level_3_feature']
                                ];
                            }
                            
                            // Niveau 7 - Capacité de serment sacré
                            if ($character['level'] >= 7 && !empty($paladinOath['level_7_feature'])) {
                                $oathCapabilities[] = [
                                    'name' => 'Capacité de niveau 7',
                                    'description' => $paladinOath['level_7_feature']
                                ];
                            }
                            
                            // Niveau 15 - Capacité de serment sacré
                            if ($character['level'] >= 15 && !empty($paladinOath['level_15_feature'])) {
                                $oathCapabilities[] = [
                                    'name' => 'Capacité de niveau 15',
                                    'description' => $paladinOath['level_15_feature']
                                ];
                            }
                            
                            // Niveau 20 - Capacité de serment sacré
                            if ($character['level'] >= 20 && !empty($paladinOath['level_20_feature'])) {
                                $oathCapabilities[] = [
                                    'name' => 'Capacité de niveau 20',
                                    'description' => $paladinOath['level_20_feature']
                                ];
                            }
                            
                            // Afficher les capacités de serment sacré
                            foreach ($oathCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Archétype de rôdeur -->
                <?php if ($rangerArchetype): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-leaf me-2"></i>Archétype de rôdeur</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-leaf me-1"></i><?php echo htmlspecialchars($rangerArchetype['archetype_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($rangerArchetype['archetype_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités d'archétype de rôdeur par niveau -->
                            <?php
                            $archetypeCapabilities = [];
                            
                            // Niveau 3 - Capacité d'archétype de rôdeur
                            if ($character['level'] >= 3 && !empty($rangerArchetype['level_3_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $rangerArchetype['level_3_feature']
                                ];
                            }
                            
                            // Niveau 7 - Capacité d'archétype de rôdeur
                            if ($character['level'] >= 7 && !empty($rangerArchetype['level_7_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 7',
                                    'description' => $rangerArchetype['level_7_feature']
                                ];
                            }
                            
                            // Niveau 11 - Capacité d'archétype de rôdeur
                            if ($character['level'] >= 11 && !empty($rangerArchetype['level_11_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 11',
                                    'description' => $rangerArchetype['level_11_feature']
                                ];
                            }
                            
                            // Niveau 15 - Capacité d'archétype de rôdeur
                            if ($character['level'] >= 15 && !empty($rangerArchetype['level_15_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 15',
                                    'description' => $rangerArchetype['level_15_feature']
                                ];
                            }
                            
                            // Afficher les capacités d'archétype de rôdeur
                            foreach ($archetypeCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Archétype de roublard -->
                <?php if ($rogueArchetype): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-mask me-2"></i>Archétype de roublard</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-mask me-1"></i><?php echo htmlspecialchars($rogueArchetype['archetype_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($rogueArchetype['archetype_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités d'archétype de roublard par niveau -->
                            <?php
                            $rogueArchetypeCapabilities = [];
                            
                            // Niveau 3 - Capacité d'archétype de roublard
                            if ($character['level'] >= 3 && !empty($rogueArchetype['level_3_feature'])) {
                                $rogueArchetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $rogueArchetype['level_3_feature']
                                ];
                            }
                            
                            // Niveau 9 - Capacité d'archétype de roublard
                            if ($character['level'] >= 9 && !empty($rogueArchetype['level_9_feature'])) {
                                $rogueArchetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 9',
                                    'description' => $rogueArchetype['level_9_feature']
                                ];
                            }
                            
                            // Niveau 13 - Capacité d'archétype de roublard
                            if ($character['level'] >= 13 && !empty($rogueArchetype['level_13_feature'])) {
                                $rogueArchetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 13',
                                    'description' => $rogueArchetype['level_13_feature']
                                ];
                            }
                            
                            // Niveau 17 - Capacité d'archétype de roublard
                            if ($character['level'] >= 17 && !empty($rogueArchetype['level_17_feature'])) {
                                $rogueArchetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 17',
                                    'description' => $rogueArchetype['level_17_feature']
                                ];
                            }
                            
                            // Afficher les capacités d'archétype de roublard
                            foreach ($rogueArchetypeCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Collège bardique du barde -->
                <?php if ($bardCollege): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-music me-2"></i>Collège bardique</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($bardCollege['college_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($bardCollege['college_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de collège bardique par niveau -->
                            <?php
                            $collegeCapabilities = [];
                            
                            // Niveau 3 - Capacité de collège bardique
                            if ($character['level'] >= 3 && !empty($bardCollege['level_3_feature'])) {
                                $collegeCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $bardCollege['level_3_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de collège bardique
                            if ($character['level'] >= 6 && !empty($bardCollege['level_6_feature'])) {
                                $collegeCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $bardCollege['level_6_feature']
                                ];
                            }
                            
                            // Niveau 14 - Capacité de collège bardique
                            if ($character['level'] >= 14 && !empty($bardCollege['level_14_feature'])) {
                                $collegeCapabilities[] = [
                                    'name' => 'Capacité de niveau 14',
                                    'description' => $bardCollege['level_14_feature']
                                ];
                            }
                            
                            // Afficher les capacités de collège bardique
                            foreach ($collegeCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Domaine divin du clerc -->
                <?php if ($clericDomain): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-cross me-2"></i>Domaine divin</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($clericDomain['domain_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($clericDomain['domain_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de domaine divin par niveau -->
                            <?php
                            $domainCapabilities = [];
                            
                            // Niveau 1 - Capacité de domaine divin
                            if ($character['level'] >= 1 && !empty($clericDomain['level_1_feature'])) {
                                $domainCapabilities[] = [
                                    'name' => 'Capacité de niveau 1',
                                    'description' => $clericDomain['level_1_feature']
                                ];
                            }
                            
                            // Niveau 2 - Capacité de domaine divin
                            if ($character['level'] >= 2 && !empty($clericDomain['level_2_feature'])) {
                                $domainCapabilities[] = [
                                    'name' => 'Capacité de niveau 2',
                                    'description' => $clericDomain['level_2_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de domaine divin
                            if ($character['level'] >= 6 && !empty($clericDomain['level_6_feature'])) {
                                $domainCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $clericDomain['level_6_feature']
                                ];
                            }
                            
                            // Niveau 8 - Capacité de domaine divin
                            if ($character['level'] >= 8 && !empty($clericDomain['level_8_feature'])) {
                                $domainCapabilities[] = [
                                    'name' => 'Capacité de niveau 8',
                                    'description' => $clericDomain['level_8_feature']
                                ];
                            }
                            
                            // Niveau 17 - Capacité de domaine divin
                            if ($character['level'] >= 17 && !empty($clericDomain['level_17_feature'])) {
                                $domainCapabilities[] = [
                                    'name' => 'Capacité de niveau 17',
                                    'description' => $clericDomain['level_17_feature']
                                ];
                            }
                            
                            // Afficher les capacités de domaine divin
                            foreach ($domainCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Cercle druidique du druide -->
                <?php if ($druidCircle): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-leaf me-2"></i>Cercle druidique</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($druidCircle['circle_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($druidCircle['circle_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de cercle druidique par niveau -->
                            <?php
                            $circleCapabilities = [];
                            
                            // Niveau 2 - Capacité de cercle druidique
                            if ($character['level'] >= 2 && !empty($druidCircle['level_2_feature'])) {
                                $circleCapabilities[] = [
                                    'name' => 'Capacité de niveau 2',
                                    'description' => $druidCircle['level_2_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de cercle druidique
                            if ($character['level'] >= 6 && !empty($druidCircle['level_6_feature'])) {
                                $circleCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $druidCircle['level_6_feature']
                                ];
                            }
                            
                            // Niveau 10 - Capacité de cercle druidique
                            if ($character['level'] >= 10 && !empty($druidCircle['level_10_feature'])) {
                                $circleCapabilities[] = [
                                    'name' => 'Capacité de niveau 10',
                                    'description' => $druidCircle['level_10_feature']
                                ];
                            }
                            
                            // Niveau 14 - Capacité de cercle druidique
                            if ($character['level'] >= 14 && !empty($druidCircle['level_14_feature'])) {
                                $circleCapabilities[] = [
                                    'name' => 'Capacité de niveau 14',
                                    'description' => $druidCircle['level_14_feature']
                                ];
                            }
                            
                            // Afficher les capacités de cercle druidique
                            foreach ($circleCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Origine magique de l'ensorceleur -->
                <?php if ($sorcererOrigin): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-magic me-2"></i>Origine magique</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($sorcererOrigin['origin_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($sorcererOrigin['origin_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités d'origine magique par niveau -->
                            <?php
                            $originCapabilities = [];
                            
                            // Niveau 1 - Capacité d'origine magique
                            if ($character['level'] >= 1 && !empty($sorcererOrigin['level_1_feature'])) {
                                $originCapabilities[] = [
                                    'name' => 'Capacité de niveau 1',
                                    'description' => $sorcererOrigin['level_1_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité d'origine magique
                            if ($character['level'] >= 6 && !empty($sorcererOrigin['level_6_feature'])) {
                                $originCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $sorcererOrigin['level_6_feature']
                                ];
                            }
                            
                            // Niveau 14 - Capacité d'origine magique
                            if ($character['level'] >= 14 && !empty($sorcererOrigin['level_14_feature'])) {
                                $originCapabilities[] = [
                                    'name' => 'Capacité de niveau 14',
                                    'description' => $sorcererOrigin['level_14_feature']
                                ];
                            }
                            
                            // Niveau 18 - Capacité d'origine magique
                            if ($character['level'] >= 18 && !empty($sorcererOrigin['level_18_feature'])) {
                                $originCapabilities[] = [
                                    'name' => 'Capacité de niveau 18',
                                    'description' => $sorcererOrigin['level_18_feature']
                                ];
                            }
                            
                            // Afficher les capacités d'origine magique
                            foreach ($originCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Archétype martial du guerrier -->
                <?php if ($fighterArchetype): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-sword me-2"></i>Archétype martial</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($fighterArchetype['archetype_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($fighterArchetype['archetype_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités d'archétype martial par niveau -->
                            <?php
                            $archetypeCapabilities = [];
                            
                            // Niveau 3 - Capacité d'archétype martial
                            if ($character['level'] >= 3 && !empty($fighterArchetype['level_3_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $fighterArchetype['level_3_feature']
                                ];
                            }
                            
                            // Niveau 7 - Capacité d'archétype martial
                            if ($character['level'] >= 7 && !empty($fighterArchetype['level_7_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 7',
                                    'description' => $fighterArchetype['level_7_feature']
                                ];
                            }
                            
                            // Niveau 10 - Capacité d'archétype martial
                            if ($character['level'] >= 10 && !empty($fighterArchetype['level_10_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 10',
                                    'description' => $fighterArchetype['level_10_feature']
                                ];
                            }
                            
                            // Niveau 15 - Capacité d'archétype martial
                            if ($character['level'] >= 15 && !empty($fighterArchetype['level_15_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 15',
                                    'description' => $fighterArchetype['level_15_feature']
                                ];
                            }
                            
                            // Niveau 18 - Capacité d'archétype martial
                            if ($character['level'] >= 18 && !empty($fighterArchetype['level_18_feature'])) {
                                $archetypeCapabilities[] = [
                                    'name' => 'Capacité de niveau 18',
                                    'description' => $fighterArchetype['level_18_feature']
                                ];
                            }
                            
                            // Afficher les capacités d'archétype martial
                            foreach ($archetypeCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tradition arcanique du magicien -->
                <?php if ($wizardTradition): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-hat-wizard me-2"></i>Tradition arcanique</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($wizardTradition['tradition_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($wizardTradition['tradition_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de tradition arcanique par niveau -->
                            <?php
                            $traditionCapabilities = [];
                            
                            // Niveau 2 - Capacité de tradition arcanique
                            if ($character['level'] >= 2 && !empty($wizardTradition['level_2_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 2',
                                    'description' => $wizardTradition['level_2_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de tradition arcanique
                            if ($character['level'] >= 6 && !empty($wizardTradition['level_6_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $wizardTradition['level_6_feature']
                                ];
                            }
                            
                            // Niveau 10 - Capacité de tradition arcanique
                            if ($character['level'] >= 10 && !empty($wizardTradition['level_10_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 10',
                                    'description' => $wizardTradition['level_10_feature']
                                ];
                            }
                            
                            // Niveau 14 - Capacité de tradition arcanique
                            if ($character['level'] >= 14 && !empty($wizardTradition['level_14_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 14',
                                    'description' => $wizardTradition['level_14_feature']
                                ];
                            }
                            
                            // Afficher les capacités de tradition arcanique
                            foreach ($traditionCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tradition monastique du moine -->
                <?php if ($monkTradition): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-fist-raised me-2"></i>Tradition monastique</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($monkTradition['tradition_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($monkTradition['tradition_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de tradition monastique par niveau -->
                            <?php
                            $traditionCapabilities = [];
                            
                            // Niveau 3 - Capacité de tradition monastique
                            if ($character['level'] >= 3 && !empty($monkTradition['level_3_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $monkTradition['level_3_feature']
                                ];
                            }
                            
                            // Niveau 6 - Capacité de tradition monastique
                            if ($character['level'] >= 6 && !empty($monkTradition['level_6_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 6',
                                    'description' => $monkTradition['level_6_feature']
                                ];
                            }
                            
                            // Niveau 11 - Capacité de tradition monastique
                            if ($character['level'] >= 11 && !empty($monkTradition['level_11_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 11',
                                    'description' => $monkTradition['level_11_feature']
                                ];
                            }
                            
                            // Niveau 17 - Capacité de tradition monastique
                            if ($character['level'] >= 17 && !empty($monkTradition['level_17_feature'])) {
                                $traditionCapabilities[] = [
                                    'name' => 'Capacité de niveau 17',
                                    'description' => $monkTradition['level_17_feature']
                                ];
                            }
                            
                            // Afficher les capacités de tradition monastique
                            foreach ($traditionCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Faveur de pacte de l'occultiste -->
                <?php if ($warlockPact): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-handshake me-2"></i>Faveur de pacte</h5>
                            <div class="capability-item">
                                <div class="capability-header">
                                    <h6 class="mb-1 text-warning">
                                        <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($warlockPact['pact_name']); ?>
                                    </h6>
                                </div>
                                <div class="capability-description">
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($warlockPact['pact_description'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Capacités de faveur de pacte par niveau -->
                            <?php
                            $pactCapabilities = [];
                            
                            // Niveau 3 - Capacité de faveur de pacte
                            if ($character['level'] >= 3 && !empty($warlockPact['level_3_feature'])) {
                                $pactCapabilities[] = [
                                    'name' => 'Capacité de niveau 3',
                                    'description' => $warlockPact['level_3_feature']
                                ];
                            }
                            
                            // Niveau 7 - Capacité de faveur de pacte
                            if ($character['level'] >= 7 && !empty($warlockPact['level_7_feature'])) {
                                $pactCapabilities[] = [
                                    'name' => 'Capacité de niveau 7',
                                    'description' => $warlockPact['level_7_feature']
                                ];
                            }
                            
                            // Niveau 15 - Capacité de faveur de pacte
                            if ($character['level'] >= 15 && !empty($warlockPact['level_15_feature'])) {
                                $pactCapabilities[] = [
                                    'name' => 'Capacité de niveau 15',
                                    'description' => $warlockPact['level_15_feature']
                                ];
                            }
                            
                            // Niveau 20 - Capacité de faveur de pacte
                            if ($character['level'] >= 20 && !empty($warlockPact['level_20_feature'])) {
                                $pactCapabilities[] = [
                                    'name' => 'Capacité de niveau 20',
                                    'description' => $warlockPact['level_20_feature']
                                ];
                            }
                            
                            // Afficher les capacités de faveur de pacte
                            foreach ($pactCapabilities as $capability):
                            ?>
                                <div class="capability-item mt-3">
                                    <div class="capability-header">
                                        <h6 class="mb-1 text-info">
                                            <i class="fas fa-star me-1"></i><?php echo htmlspecialchars($capability['name']); ?>
                                        </h6>
                                    </div>
                                    <div class="capability-description">
                                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($capability['description'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Compétences -->
            <div class="info-section">
                <h3><i class="fas fa-dice me-2"></i>Compétences</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-check-circle me-2"></i>Compétences maîtrisées</h5>
                        <?php if (!empty($characterSkills)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($characterSkills as $skill): ?>
                                    <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune compétence maîtrisée</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-tools me-2"></i>Outils maîtrisés</h5>
                        <?php if (!empty($backgroundTools)): ?>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($backgroundTools as $tool): ?>
                                    <span class="badge bg-success me-2 mb-2"><?php echo htmlspecialchars($tool); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucun outil maîtrisé</p>
                        <?php endif; ?>
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
                                    <?php
                                    // Déterminer le type de langue pour le style du badge
                                    $isRacial = in_array($language, $filteredRaceLanguages);
                                    $isBackground = in_array($language, $filteredBackgroundLanguages);
                                    $isCharacter = in_array($language, $filteredCharacterLanguages);
                                    
                                    $badgeClass = 'bg-info'; // Par défaut
                                    $badgeText = $language;
                                    
                                    if ($isRacial) {
                                        $badgeClass = 'bg-primary';
                                        $badgeText = $language . ' (Raciale)';
                                    } elseif ($isBackground) {
                                        $badgeClass = 'bg-success';
                                        $badgeText = $language . ' (Historique)';
                                    } elseif ($isCharacter) {
                                        $badgeClass = 'bg-info';
                                        $badgeText = $language . ' (Choix)';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> me-2 mb-2"><?php echo htmlspecialchars($badgeText); ?></span>
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
                        <h3><i class="fas fa-dragon me-2"></i>Race: <?php echo htmlspecialchars($character['race_name']); ?></h3>
                        <p><?php echo htmlspecialchars($character['race_description']); ?></p>
                        <p><strong>Bonus de caractéristiques:</strong> 
                            Force: +<?php echo $character['strength_bonus']; ?> | 
                            Dextérité: +<?php echo $character['dexterity_bonus']; ?> | 
                            Constitution: +<?php echo $character['constitution_bonus']; ?> | 
                            Intelligence: +<?php echo $character['intelligence_bonus']; ?> | 
                            Sagesse: +<?php echo $character['wisdom_bonus']; ?> | 
                            Charisme: +<?php echo $character['charisma_bonus']; ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-section">
                        <h3><i class="fas fa-shield-alt me-2"></i>Classe: <?php echo htmlspecialchars($character['class_name']); ?></h3>
                        <p><?php echo htmlspecialchars($character['class_description']); ?></p>
                        <p><strong>Dé de vie:</strong> &nbsp;<?php echo $character['hit_dice']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <?php if ($character['background_name']): ?>
            <div class="info-section">
                <h3><i class="fas fa-book me-2"></i>Historique: <?php echo htmlspecialchars($character['background_name']); ?></h3>
                <div class="row">
                    <div class="col-12">
                        <p><?php echo htmlspecialchars($character['background_description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Équipement détecté -->
            <?php if (!empty($detectedWeapons) || !empty($detectedArmor) || !empty($detectedShields)): ?>
            <div class="info-section">
                <h3><i class="fas fa-sword me-2"></i>Équipement de Combat</h3>
                
                <!-- Armes -->
                <?php if (!empty($detectedWeapons)): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-sword me-2"></i>Armes</h5>
                    <div class="row">
                        <?php foreach ($detectedWeapons as $weapon): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($weapon['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $weapon['hands']; ?> main(s) - <?php echo htmlspecialchars($weapon['type']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php
                                            $isEquipped = false;
                                            $equippedSlot = null;
                                            
                                            if ($weapon['hands'] == 2) {
                                                $isEquipped = ($equippedItems['main_hand'] === $weapon['name'] && $equippedItems['off_hand'] === $weapon['name']);
                                                $equippedSlot = 'main_hand';
                                            } else {
                                                $isEquipped = ($equippedItems['main_hand'] === $weapon['name']);
                                                $equippedSlot = 'main_hand';
                                            }
                                            ?>
                                            
                                            <?php if ($isEquipped): ?>
                                                <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $character_id; ?>, '<?php echo addslashes($weapon['name']); ?>')">
                                                    <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $character_id; ?>, '<?php echo addslashes($weapon['name']); ?>', 'weapon', '<?php echo $equippedSlot; ?>')">
                                                    <i class="fas fa-hand-rock me-1"></i>Équiper
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Armures -->
                <?php if (!empty($detectedArmor)): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-shield-alt me-2"></i>Armures</h5>
                    <div class="row">
                        <?php foreach ($detectedArmor as $armor): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($armor['name']); ?></h6>
                                            <small class="text-muted">
                                                CA: <?php echo htmlspecialchars($armor['ac_formula']); ?> - <?php echo htmlspecialchars($armor['type']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php $isEquipped = ($equippedItems['armor'] === $armor['name']); ?>
                                            
                                            <?php if ($isEquipped): ?>
                                                <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $character_id; ?>, '<?php echo addslashes($armor['name']); ?>')">
                                                    <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $character_id; ?>, '<?php echo addslashes($armor['name']); ?>', 'armor', 'armor')">
                                                    <i class="fas fa-hand-rock me-1"></i>Équiper
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Boucliers -->
                <?php if (!empty($detectedShields)): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-shield me-2"></i>Boucliers</h5>
                    <div class="row">
                        <?php foreach ($detectedShields as $shield): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($shield['name']); ?></h6>
                                            <small class="text-muted">
                                                Bonus de CA: +<?php echo $shield['ac_bonus']; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php $isEquipped = ($equippedItems['shield'] === $shield['name']); ?>
                                            
                                            <?php if ($isEquipped): ?>
                                                <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $character_id; ?>, '<?php echo addslashes($shield['name']); ?>')">
                                                    <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $character_id; ?>, '<?php echo addslashes($shield['name']); ?>', 'shield', 'off_hand')">
                                                    <i class="fas fa-hand-rock me-1"></i>Équiper
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Équipement et trésor -->
            <div class="info-section">
                <h3><i class="fas fa-backpack me-2"></i>Équipement et Trésor</h3>
                <div class="row">
                    <div class="col-md-8">
                        <?php if ($character['equipment']): ?>
                            <p><strong>Équipement:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($character['equipment'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Aucun équipement enregistré</p>
                        <?php endif; ?>
                        
                        <!-- Objets magiques attribués par le MJ -->
                        <?php if (!empty($allMagicalEquipment)): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-gem me-2"></i>Objets Magiques</h5>
                                <div class="row">
                                    <?php foreach ($allMagicalEquipment as $item): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 <?php echo $item['equipped'] ? 'border-success' : 'border-secondary'; ?>">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        <?php if ($item['equipped']): ?>
                                                            <i class="fas fa-check-circle text-success me-2"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </h6>
                                                    <span class="badge bg-<?php echo $item['equipped'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $item['equipped'] ? 'Équipé' : 'Non équipé'; ?>
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        <strong>Type:</strong> <?php echo htmlspecialchars($item['item_type']); ?><br>
                                                        <strong>Source:</strong> <?php echo htmlspecialchars($item['item_source']); ?><br>
                                                        <strong>Quantité:</strong> <?php echo (int)$item['quantity']; ?><br>
                                                        <strong>Obtenu:</strong> <?php echo date('d/m/Y', strtotime($item['obtained_at'])); ?><br>
                                                        <strong>Provenance:</strong> <?php echo htmlspecialchars($item['obtained_from']); ?>
                                                        <?php if (isset($item['npc_name'])): ?>
                                                            <br><strong>Via PNJ:</strong> <?php echo htmlspecialchars($item['npc_name']); ?> (<?php echo htmlspecialchars($item['scene_title']); ?>)
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if (!empty($item['item_description'])): ?>
                                                        <p class="card-text">
                                                            <strong>Description:</strong><br>
                                                            <small><?php echo nl2br(htmlspecialchars($item['item_description'])); ?></small>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['notes'])): ?>
                                                        <p class="card-text">
                                                            <strong>Notes:</strong><br>
                                                            <small><em><?php echo nl2br(htmlspecialchars($item['notes'])); ?></em></small>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer">
                                                    <?php if ($canModifyHP): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#transferModal" 
                                                                data-item-id="<?php echo $item['id']; ?>"
                                                                data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                                data-current-owner="character_<?php echo $character_id; ?>"
                                                                data-current-owner-name="<?php echo htmlspecialchars($character['name']); ?>"
                                                                data-source="<?php echo isset($item['npc_name']) ? 'npc_equipment' : 'character_equipment'; ?>"
                                                                title="Transférer cet objet">
                                                            <i class="fas fa-exchange-alt me-1"></i>Transférer à
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Poisons attribués par le MJ -->
                        <?php if (!empty($allPoisons)): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-skull-crossbones me-2"></i>Poisons</h5>
                                <div class="row">
                                    <?php foreach ($allPoisons as $poison): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 border-danger">
                                                <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-skull-crossbones me-1"></i>
                                                        <?php echo htmlspecialchars($poison['poison_nom']); ?>
                                                    </h6>
                                                    <small class="text-light">
                                                        <?php echo htmlspecialchars($poison['poison_type']); ?>
                                                    </small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text small">
                                                        <?php echo htmlspecialchars($poison['poison_description']); ?>
                                                    </p>
                                                    <?php if ($poison['notes']): ?>
                                                        <p class="card-text small text-muted">
                                                            <strong>Notes:</strong> <?php echo htmlspecialchars($poison['notes']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p class="card-text small text-muted">
                                                        <strong>Source:</strong> <?php echo htmlspecialchars($poison['poison_source']); ?><br>
                                                        <strong>Obtenu:</strong> <?php echo htmlspecialchars($poison['obtained_from']); ?><br>
                                                        <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($poison['obtained_at'])); ?>
                                                        <?php if (isset($poison['npc_name'])): ?>
                                                            <br><strong>Via PNJ:</strong> <?php echo htmlspecialchars($poison['npc_name']); ?> (<?php echo htmlspecialchars($poison['scene_title']); ?>)
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="card-footer">
                                                    <?php if ($canModifyHP): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#transferModal" 
                                                                data-item-id="<?php echo $poison['id']; ?>"
                                                                data-item-name="<?php echo htmlspecialchars($poison['poison_nom']); ?>"
                                                                data-current-owner="character_<?php echo $character_id; ?>"
                                                                data-current-owner-name="<?php echo htmlspecialchars($character['name']); ?>"
                                                                data-source="<?php echo isset($poison['npc_name']) ? 'npc_equipment' : 'character_equipment'; ?>"
                                                                title="Transférer ce poison">
                                                            <i class="fas fa-exchange-alt me-1"></i>Transférer à
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="view_character_equipment.php?id=<?php echo (int)$character_id; ?>" class="btn btn-primary">
                                <i class="fas fa-cog me-2"></i>Gérer l'équipement détaillé
                            </a>
                            <?php if (canCastSpells($character['class_id'])): ?>
                                <a href="grimoire.php?id=<?php echo (int)$character_id; ?>" class="btn btn-info ms-2">
                                    <i class="fas fa-book-open me-2"></i>Grimoire
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Argent:</strong></p>
                        <ul class="list-unstyled">
                            <li><?php echo $character['money_gold']; ?> PO (pièces d'or)</li>
                            <li><?php echo $character['money_silver']; ?> PA (pièces d'argent)</li>
                            <li><?php echo $character['money_copper']; ?> PC (pièces de cuivre)</li>
                        </ul>
                        
                        <?php if (!empty($allMagicalEquipment)): ?>
                            <div class="mt-3">
                                <p><strong>Objets Magiques:</strong></p>
                                <ul class="list-unstyled">
                                    <li><span class="badge bg-success"><?php echo count(array_filter($allMagicalEquipment, function($item) { return $item['equipped']; })); ?> Équipé(s)</span></li>
                                    <li><span class="badge bg-secondary"><?php echo count(array_filter($allMagicalEquipment, function($item) { return !$item['equipped']; })); ?> Non équipé(s)</span></li>
                                    <li><span class="badge bg-primary"><?php echo count($allMagicalEquipment); ?> Total</span></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
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
                        $current_hp = $character['hit_points_current'];
                        $max_hp = $character['hit_points_max'];
                        $hp_percentage = ($current_hp / $max_hp) * 100;
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
                                <input type="hidden" name="max_hp" value="<?php echo $character['hit_points_max']; ?>">
                                <div class="d-flex gap-2">
                                    <input type="number" name="current_hp" class="form-control form-control-sm" 
                                           value="<?php echo $character['hit_points_current']; ?>" 
                                           min="0" max="<?php echo $character['hit_points_max']; ?>" required>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Maximum : <?php echo $character['hit_points_max']; ?> PV</small>
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
                                    <strong><?php echo number_format($character['experience_points']); ?> XP</strong>
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
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-100)">-100</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-500)">-500</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="quickXpChange(-1000)">-1000</button>
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
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(100)">+100</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(500)">+500</button>
                                <button class="btn btn-outline-success btn-sm" onclick="quickXpChange(1000)">+1000</button>
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
                                           value="<?php echo $character['experience_points']; ?>" 
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function quickDamage(amount) {
            if (confirm(`Infliger ${amount} points de dégâts à <?php echo htmlspecialchars($character['name']); ?> ?`)) {
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
            if (confirm(`Appliquer ${amount} points de soins à <?php echo htmlspecialchars($character['name']); ?> ?`)) {
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

        function quickXpChange(amount) {
            const action = amount > 0 ? 'ajouter' : 'retirer';
            const absAmount = Math.abs(amount);
            if (confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${absAmount} points d'expérience à <?php echo htmlspecialchars($character['name']); ?> ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="xp_action" value="${amount > 0 ? 'add' : 'remove'}">
                    <input type="hidden" name="xp_amount" value="${absAmount}">
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

        // Fonctions pour l'équipement
        function equipItem(characterId, itemName, itemType, slot) {
            fetch('equip_item.php', {
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
            fetch('unequip_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    character_id: characterId,
                    item_name: itemName
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
                alert('Erreur lors du déséquipement');
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
    </script>
</body>
</html>


