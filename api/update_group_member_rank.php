<?php
// API pour mettre à jour le rang d'un membre de groupe
require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Groupe.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$target_type = isset($_POST['target_type']) ? $_POST['target_type'] : '';
$new_rank = isset($_POST['new_rank']) ? (int)$_POST['new_rank'] : 0;

if ($group_id <= 0 || $target_id <= 0 || empty($target_type) || $new_rank <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $group = Groupe::findById($group_id);
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Groupe introuvable']);
        exit;
    }

    // Conversion ID pour PNJ (comme dans add/remove)
    if ($target_type === 'PNJ' || $target_type === 'pnj') {
        $stmt = Database::getInstance()->getPdo()->prepare("SELECT id FROM place_npcs WHERE npc_character_id = ? LIMIT 1");
        $stmt->execute([$target_id]);
        $placeNpc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($placeNpc) {
            $target_id = $placeNpc['id'];
        }
    }

    if ($group->updateMemberHierarchy($target_id, $target_type, $new_rank)) {
        echo json_encode(['success' => true, 'message' => 'Rang mis à jour']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
