<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isDM()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monster_id = (int)($_POST['monster_id'] ?? 0);
    $place_id = (int)($_POST['place_id'] ?? 0);
    $npc_name = trim($_POST['npc_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($monster_id <= 0 || $place_id <= 0 || $npc_name === '') {
        header('Location: bestiary.php?error=invalid_parameters');
        exit();
    }
    
    // Vérifier que le monstre existe
    $stmt = $pdo->prepare("SELECT * FROM dnd_monsters WHERE id = ?");
    $stmt->execute([$monster_id]);
    $monster = $stmt->fetch();
    
    if (!$monster) {
        header('Location: bestiary.php?error=monster_not_found');
        exit();
    }
    
    // Vérifier que le lieu existe et appartient au MJ
    $stmt = $pdo->prepare("SELECT p.id, p.name, c.title AS campaign_title FROM places p 
                          JOIN campaigns c ON p.campaign_id = c.id 
                          WHERE p.id = ? AND c.dm_id = ?");
    $stmt->execute([$place_id, $_SESSION['user_id']]);
    $place = $stmt->fetch();
    
    if (!$place) {
        header('Location: bestiary.php?error=place_not_found');
        exit();
    }
    
    // Créer le MNJ en utilisant la classe NPC
    require_once 'classes/NPC.php';
    
    // Créer d'abord le PNJ dans la table npcs
    $npc = new NPC([]);
    $npcData = [
        'name' => $npc_name,
        'class_id' => null, // Pas de classe pour un monstre
        'race_id' => null,  // Pas de race pour un monstre
        'background_id' => null,
        'archetype_id' => null,
        'level' => 1,
        'experience' => 0,
        'strength' => $monster['strength'] ?? 10,
        'dexterity' => $monster['dexterity'] ?? 10,
        'constitution' => $monster['constitution'] ?? 10,
        'intelligence' => $monster['intelligence'] ?? 10,
        'wisdom' => $monster['wisdom'] ?? 10,
        'charisma' => $monster['charisma'] ?? 10,
        'hit_points' => $monster['hit_points'] ?? 1,
        'armor_class' => $monster['armor_class'] ?? 10,
        'speed' => $monster['speed'] ?? 30,
        'alignment' => $monster['alignment'] ?? 'Neutre',
        'age' => null,
        'height' => null,
        'weight' => null,
        'eyes' => null,
        'skin' => null,
        'hair' => null,
        'backstory' => $description,
        'personality_traits' => null,
        'ideals' => null,
        'bonds' => null,
        'flaws' => null,
        'starting_equipment' => null,
        'gold' => 0,
        'spells' => null,
        'skills' => null,
        'languages' => null,
        'profile_photo' => null,
        'created_by' => $_SESSION['user_id'],
        'world_id' => $place['campaign_id'] ?? 1, // Utiliser l'ID de la campagne comme world_id
        'location_id' => $place_id,
        'is_active' => true
    ];
    
    $npc_id = $npc->create($npcData);
    
    if ($npc_id) {
        // Créer l'entrée dans place_npcs
        $stmt = $pdo->prepare("INSERT INTO place_npcs (place_id, name, description, monster_id, npc_character_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$place_id, $npc_name, $description, $monster_id, $npc_id]);
    } else {
        header('Location: bestiary.php?error=npc_creation_failed');
        exit();
    }
    
    // Rediriger vers la lieu avec un message de succès
    header('Location: view_place.php?id=' . $place_id . '&success=monster_npc_created');
    exit();
}

// Si accès direct, rediriger vers la collection
header('Location: bestiary.php');
exit();
