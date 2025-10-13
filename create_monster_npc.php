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
    
    // Créer le MNJ dans la lieu
    $stmt = $pdo->prepare("INSERT INTO place_npcs (place_id, name, description, monster_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$place_id, $npc_name, $description, $monster_id]);
    
    // Rediriger vers la lieu avec un message de succès
    header('Location: view_place.php?id=' . $place_id . '&success=monster_npc_created');
    exit();
}

// Si accès direct, rediriger vers la collection
header('Location: bestiary.php');
exit();
