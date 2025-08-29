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
    $action = $_POST['action'] ?? '';
    
    if ($monster_id <= 0) {
        header('Location: bestiary.php?error=invalid_monster');
        exit();
    }
    
    // Vérifier que le monstre existe
    $stmt = $pdo->prepare("SELECT id, name FROM dnd_monsters WHERE id = ?");
    $stmt->execute([$monster_id]);
    $monster = $stmt->fetch();
    
    if (!$monster) {
        header('Location: bestiary.php?error=monster_not_found');
        exit();
    }
    
    if ($action === 'add') {
        // Vérifier si déjà dans la collection
        $stmt = $pdo->prepare("SELECT 1 FROM user_monster_collection WHERE user_id = ? AND monster_id = ?");
        $stmt->execute([$_SESSION['user_id'], $monster_id]);
        
        if (!$stmt->fetch()) {
            // Ajouter à la collection
            $stmt = $pdo->prepare("INSERT INTO user_monster_collection (user_id, monster_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $monster_id]);
        }
        
        // Rediriger vers la page d'origine
        $referer = $_SERVER['HTTP_REFERER'] ?? 'bestiary.php';
        header('Location: ' . $referer . '?success=monster_added');
        
    } elseif ($action === 'remove') {
        // Retirer de la collection
        $stmt = $pdo->prepare("DELETE FROM user_monster_collection WHERE user_id = ? AND monster_id = ?");
        $stmt->execute([$_SESSION['user_id'], $monster_id]);
        
        // Rediriger vers la page d'origine
        $referer = $_SERVER['HTTP_REFERER'] ?? 'my_monsters.php';
        header('Location: ' . $referer . '?success=monster_removed');
        
    } else {
        header('Location: bestiary.php?error=invalid_action');
    }
    
    exit();
}

// Si accès direct, rediriger vers le bestiaire
header('Location: bestiary.php');
exit();
