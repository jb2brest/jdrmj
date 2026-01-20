<?php
// API pour ajouter un membre à un groupe
require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Groupe.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération des données
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$target_type = isset($_POST['target_type']) ? $_POST['target_type'] : '';
$hierarchy_level = isset($_POST['hierarchy_level']) ? (int)$_POST['hierarchy_level'] : 2;

// Validation basique
if ($group_id <= 0 || $target_id <= 0 || empty($target_type)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    // Vérifier si le groupe existe
    $group = Groupe::findById($group_id);
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Groupe introuvable']);
        exit;
    }

    // TODO: Vérifier les permissions (si l'utilisateur a le droit de modifier le groupe)
    // Pour l'instant on suppose que si connecté, c'est OK (comme dans le reste de l'app pour l'instant)

    // Si c'est un PNJ, il faut récupérer l'ID correspondant dans place_npcs
    // car le système de groupes semble utiliser les IDs de place_npcs pour les membres de type PNJ
    // (basé sur la logique de read dans Groupe::getGroupMemberships)
    if ($target_type === 'PNJ' || $target_type === 'pnj') {
        $stmt = Database::getInstance()->getPdo()->prepare("SELECT id FROM place_npcs WHERE npc_character_id = ? LIMIT 1");
        $stmt->execute([$target_id]);
        $placeNpc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($placeNpc) {
            $target_id = $placeNpc['id'];
        } else {
            // Si pas trouvé dans place_npcs, erreur
             echo json_encode(['success' => false, 'message' => 'PNJ non trouvé dans une pièce (place_npcs)']);
             exit;
        }
    }

    // Ajouter le membre
    if ($group->addMember($target_id, $target_type, $hierarchy_level)) {
        echo json_encode(['success' => true, 'message' => 'Membre ajouté au groupe']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout (peut-être déjà membre ?)']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
