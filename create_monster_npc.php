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
    $scene_id = (int)($_POST['scene_id'] ?? 0);
    $npc_name = trim($_POST['npc_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($monster_id <= 0 || $scene_id <= 0 || $npc_name === '') {
        header('Location: my_monsters.php?error=invalid_parameters');
        exit();
    }
    
    // Vérifier que le monstre existe et est dans la collection du MJ
    $stmt = $pdo->prepare("SELECT m.* FROM dnd_monsters m 
                          JOIN user_monster_collection umc ON m.id = umc.monster_id 
                          WHERE m.id = ? AND umc.user_id = ?");
    $stmt->execute([$monster_id, $_SESSION['user_id']]);
    $monster = $stmt->fetch();
    
    if (!$monster) {
        header('Location: my_monsters.php?error=monster_not_found');
        exit();
    }
    
    // Vérifier que la scène existe et appartient au MJ
    $stmt = $pdo->prepare("SELECT s.id, s.title, gs.title AS session_title FROM scenes s 
                          JOIN game_sessions gs ON s.session_id = gs.id 
                          WHERE s.id = ? AND gs.dm_id = ?");
    $stmt->execute([$scene_id, $_SESSION['user_id']]);
    $scene = $stmt->fetch();
    
    if (!$scene) {
        header('Location: my_monsters.php?error=scene_not_found');
        exit();
    }
    
    // Créer le MNJ dans la scène
    $stmt = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, description, monster_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$scene_id, $npc_name, $description, $monster_id]);
    
    // Rediriger vers la scène avec un message de succès
    header('Location: view_scene.php?id=' . $scene_id . '&success=monster_npc_created');
    exit();
}

// Si accès direct, rediriger vers la collection
header('Location: my_monsters.php');
exit();
