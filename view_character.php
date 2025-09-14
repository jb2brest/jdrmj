<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

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
$strengthMod = getAbilityModifier($character['strength']);
$dexterityMod = getAbilityModifier($character['dexterity']);
$constitutionMod = getAbilityModifier($character['constitution']);
$intelligenceMod = getAbilityModifier($character['intelligence']);
$wisdomMod = getAbilityModifier($character['wisdom']);
$charismaMod = getAbilityModifier($character['charisma']);

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

if (!$canView && isDM() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
    $ownsCampaign = (bool)$stmt->fetch();

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
if (!$canModifyHP && isDM() && $dm_campaign_id) {
    // Vérifier que la campagne appartient au MJ connecté
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$dm_campaign_id, $_SESSION['user_id']]);
    $canModifyHP = (bool)$stmt->fetch();
    
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
        JOIN races r ON c.race_id = r.id 
        JOIN classes cl ON c.class_id = cl.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$character_id]);
    $character = $stmt->fetch();
}

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

// Récupérer l'équipement magique du personnage (exclure les poisons)
$stmt = $pdo->prepare("
    SELECT ce.*, mi.nom as magical_item_nom, mi.type as magical_item_type, mi.description as magical_item_description, mi.source as magical_item_source
    FROM character_equipment ce
    LEFT JOIN magical_items mi ON ce.magical_item_id = mi.csv_id
    WHERE ce.character_id = ? 
    AND ce.magical_item_id NOT IN (SELECT csv_id FROM poisons)
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="characters.php">Mes Personnages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_character.php">Créer un Personnage</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                                <td><strong><?php echo $character['strength'] + $character['strength_bonus']; ?> (<?php echo ($strengthMod >= 0 ? '+' : '') . $strengthMod; ?>)</strong></td>
                                <td><strong><?php echo $character['dexterity'] + $character['dexterity_bonus']; ?> (<?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?>)</strong></td>
                                <td><strong><?php echo $character['constitution'] + $character['constitution_bonus']; ?> (<?php echo ($constitutionMod >= 0 ? '+' : '') . $constitutionMod; ?>)</strong></td>
                                <td><strong><?php echo $character['intelligence'] + $character['intelligence_bonus']; ?> (<?php echo ($intelligenceMod >= 0 ? '+' : '') . $intelligenceMod; ?>)</strong></td>
                                <td><strong><?php echo $character['wisdom'] + $character['wisdom_bonus']; ?> (<?php echo ($wisdomMod >= 0 ? '+' : '') . $wisdomMod; ?>)</strong></td>
                                <td><strong><?php echo $character['charisma'] + $character['charisma_bonus']; ?> (<?php echo ($charismaMod >= 0 ? '+' : '') . $charismaMod; ?>)</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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
                    <div class="col-md-3">
                        <strong>Points d'expérience:</strong> &nbsp;<?php echo number_format($character['experience_points']); ?>
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
                                                <strong>Armure:</strong> Aucune (10 + modificateur de Dextérité)<br>
                                            <?php endif; ?>
                                            
                                            <?php if ($equippedShield): ?>
                                                <strong>Bouclier:</strong> <?php echo $equippedShield['name']; ?> (+<?php echo $equippedShield['ac_bonus']; ?>)<br>
                                            <?php else: ?>
                                                <strong>Bouclier:</strong> Aucun<br>
                                            <?php endif; ?>
                                            
                                            <strong>Modificateur de Dextérité:</strong> <?php echo ($dexterityMod >= 0 ? '+' : '') . $dexterityMod; ?>
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
                                    <div class="col-md-6">
                                        <h6>Lancer les dégâts</h6>
                                        <button class="btn btn-danger btn-sm" onclick="rollDamage('<?php echo addslashes($equippedWeapon['damage']); ?>', '<?php echo addslashes($equippedWeapon['name']); ?>')">
                                            <i class="fas fa-dice me-1"></i>Lancer <?php echo htmlspecialchars($equippedWeapon['damage']); ?>
                                        </button>
                                        <div id="damage-result" class="mt-2"></div>
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

        // Fonction pour lancer les dés de dégâts avec animation
        function rollDamage(damageFormula, weaponName) {
            const resultDiv = document.getElementById('damage-result');
            
            // Parser la formule de dégâts (ex: "1d6+2" ou "2d4")
            const match = damageFormula.match(/(\d+)d(\d+)([+-]\d+)?/);
            if (!match) {
                resultDiv.innerHTML = '<span class="text-danger">Formule de dégâts invalide: ' + damageFormula + '</span>';
                return;
            }
            
            const numDice = parseInt(match[1]);
            const diceSize = parseInt(match[2]);
            const modifier = match[3] ? parseInt(match[3]) : 0;
            
            // Désactiver le bouton pendant l'animation
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Lancement...';
            
            // Créer l'animation des dés
            resultDiv.innerHTML = createDiceAnimation(numDice, diceSize);
            
            // Lancer les dés après l'animation
            setTimeout(() => {
                let total = 0;
                let rolls = [];
                
                for (let i = 0; i < numDice; i++) {
                    const roll = Math.floor(Math.random() * diceSize) + 1;
                    rolls.push(roll);
                    total += roll;
                }
                
                total += modifier;
                
                // Afficher le résultat final
                resultDiv.innerHTML = createResultDisplay(weaponName, total, rolls, modifier, diceSize);
                
                // Réactiver le bouton
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-dice me-1"></i>Lancer ' + damageFormula;
            }, 2000); // Animation de 2 secondes
        }
        
        // Fonction pour créer l'animation des dés
        function createDiceAnimation(numDice, diceSize) {
            let animationHtml = '<div class="dice-animation-container">';
            animationHtml += '<h6 class="mb-3"><i class="fas fa-dice me-2"></i>Lancement des dés...</h6>';
            animationHtml += '<div class="dice-rolling-area">';
            
            for (let i = 0; i < numDice; i++) {
                animationHtml += '<div class="dice rolling dice-' + diceSize + '" data-dice-size="' + diceSize + '">';
                animationHtml += '<div class="dice-face">?</div>';
                animationHtml += '</div>';
            }
            
            animationHtml += '</div></div>';
            
            // Ajouter les styles CSS pour l'animation
            if (!document.getElementById('dice-animation-styles')) {
                const style = document.createElement('style');
                style.id = 'dice-animation-styles';
                style.textContent = `
                    .dice-animation-container {
                        text-align: center;
                        padding: 20px;
                    }
                    
                    .dice-rolling-area {
                        display: flex;
                        justify-content: center;
                        gap: 15px;
                        flex-wrap: wrap;
                        margin-bottom: 30px;
                        min-height: 80px;
                    }
                    
                    .dice {
                        width: 60px;
                        height: 60px;
                        background: linear-gradient(145deg, #ffcccc, #ff9999);
                        border: 2px solid #cc0000;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                        font-weight: bold;
                        color: #cc0000;
                        box-shadow: 0 4px 8px rgba(204,0,0,0.3);
                        position: relative;
                        transition: all 0.3s ease;
                    }
                    
                    /* D4 - Tétraèdre */
                    .dice-4 {
                        width: 0;
                        height: 0;
                        border-left: 30px solid transparent;
                        border-right: 30px solid transparent;
                        border-bottom: 52px solid #ff9999;
                        background: none;
                        border-radius: 0;
                        position: relative;
                    }
                    
                    .dice-4 .dice-face {
                        position: absolute;
                        top: 35px;
                        left: -15px;
                        width: 30px;
                        text-align: center;
                        color: #cc0000;
                    }
                    
                    /* D6 - Cube */
                    .dice-6 {
                        border-radius: 8px;
                    }
                    
                    /* D8 - Octaèdre */
                    .dice-8 {
                        width: 0;
                        height: 0;
                        border-left: 25px solid transparent;
                        border-right: 25px solid transparent;
                        border-bottom: 35px solid #ff9999;
                        background: none;
                        border-radius: 0;
                        position: relative;
                    }
                    
                    .dice-8::after {
                        content: '';
                        position: absolute;
                        top: 35px;
                        left: -25px;
                        width: 0;
                        height: 0;
                        border-left: 25px solid transparent;
                        border-right: 25px solid transparent;
                        border-top: 35px solid #ff6666;
                    }
                    
                    .dice-8 .dice-face {
                        position: absolute;
                        top: 15px;
                        left: -15px;
                        width: 30px;
                        text-align: center;
                        color: #cc0000;
                        z-index: 2;
                    }
                    
                    /* D10 - Pentagone */
                    .dice-10 {
                        width: 50px;
                        height: 50px;
                        background: linear-gradient(145deg, #ffcccc, #ff9999);
                        clip-path: polygon(50% 0%, 100% 38%, 82% 100%, 18% 100%, 0% 38%);
                        border-radius: 0;
                    }
                    
                    /* D12 - Dodécaèdre */
                    .dice-12 {
                        width: 50px;
                        height: 50px;
                        background: linear-gradient(145deg, #ffcccc, #ff9999);
                        clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
                        border-radius: 0;
                    }
                    
                    /* D20 - Icosaèdre */
                    .dice-20 {
                        width: 50px;
                        height: 50px;
                        background: linear-gradient(145deg, #ffcccc, #ff9999);
                        clip-path: polygon(50% 0%, 100% 20%, 100% 80%, 50% 100%, 0% 80%, 0% 20%);
                        border-radius: 0;
                    }
                    
                    .dice.rolling {
                        animation: diceRoll 0.5s infinite;
                    }
                    
                    .dice.rolling .dice-face {
                        animation: diceFaceChange 0.2s infinite;
                    }
                    
                    @keyframes diceRoll {
                        0% { transform: rotate(0deg) scale(1); }
                        25% { transform: rotate(90deg) scale(1.1); }
                        50% { transform: rotate(180deg) scale(1); }
                        75% { transform: rotate(270deg) scale(1.1); }
                        100% { transform: rotate(360deg) scale(1); }
                    }
                    
                    @keyframes diceFaceChange {
                        0% { content: '?'; }
                        25% { content: '1'; }
                        50% { content: '?'; }
                        75% { content: '2'; }
                        100% { content: '?'; }
                    }
                    
                    .dice-face::before {
                        content: '?';
                        animation: diceFaceChange 0.2s infinite;
                    }
                    
                    .dice-face.dice-final::before {
                        content: '';
                        animation: none;
                    }
                    
                    .dice-result {
                        animation: diceResult 0.5s ease-out;
                    }
                    
                    .dice-result .alert {
                        padding: 20px;
                    }
                    
                    @keyframes diceResult {
                        0% { transform: scale(1.5); opacity: 0; }
                        50% { transform: scale(1.2); opacity: 0.8; }
                        100% { transform: scale(1); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            return animationHtml;
        }
        
        // Fonction pour créer l'affichage du résultat
        function createResultDisplay(weaponName, total, rolls, modifier, diceSize) {
            
            let resultHtml = '<div class="dice-result">';
            resultHtml += '<div class="alert alert-success">';
            resultHtml += '<h6><strong>' + weaponName + '</strong></h6>';
            resultHtml += '<div class="dice-rolling-area">';
            
            // Afficher les dés avec leurs résultats
            rolls.forEach((roll, index) => {
                resultHtml += '<div class="dice dice-result dice-' + diceSize + '" style="animation-delay: ' + (index * 0.1) + 's">';
                resultHtml += '<div class="dice-face dice-final">' + roll + '</div>';
                resultHtml += '</div>';
            });
            
            resultHtml += '</div>';
            resultHtml += '<div class="text-center" style="margin-top: 40px; padding-top: 20px;">';
            resultHtml += '<h5 class="text-success mb-1">Total: <strong>' + total + '</strong></h5>';
            
            if (rolls.length > 1) {
                resultHtml += '<small class="text-muted">(' + rolls.join(' + ') + ')';
                if (modifier !== 0) {
                    resultHtml += ' ' + (modifier > 0 ? '+' : '') + modifier;
                }
                resultHtml += ')</small>';
            } else {
                resultHtml += '<small class="text-muted">(' + rolls[0];
                if (modifier !== 0) {
                    resultHtml += ' ' + (modifier > 0 ? '+' : '') + modifier;
                }
                resultHtml += ')</small>';
            }
            
            resultHtml += '</div></div></div>';
            
            return resultHtml;
        }
    </script>
</body>
</html>


