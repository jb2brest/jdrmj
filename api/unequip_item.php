<?php
/**
 * API universelle pour déséquiper un objet
 * Gère les personnages, PNJ et monstres
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);
$itemId = (int)($input['item_id'] ?? 0);

// Validation
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID d\'objet invalide']);
    exit();
}

try {
    $pdo = Database::getInstance()->getPdo();
    
    // Récupérer les informations de l'objet
    $stmt = $pdo->prepare("
        SELECT id, display_name, object_type, owner_type, owner_id, is_equipped
        FROM items 
        WHERE id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objet non trouvé']);
        exit();
    }

    if (!$item['is_equipped']) {
        echo json_encode(['success' => false, 'message' => 'L\'objet n\'est pas équipé']);
        exit();
    }

    $ownerType = $item['owner_type'];
    $ownerId = $item['owner_id'];

    // Vérifier les permissions selon le type de propriétaire
    $hasPermission = false;

    if ($ownerType === 'player') {
        // Pour les personnages, vérifier que l'utilisateur est le propriétaire
        $character = Character::findById($ownerId);
        if ($character) {
            $isOwner = $character->belongsToUser($_SESSION['user_id']);
            $isDM = isDM();
            $isAdmin = User::isAdmin();
            $hasPermission = $isOwner || $isDM || $isAdmin;
        }
    } elseif ($ownerType === 'npc') {
        // Pour les PNJ, vérifier que l'utilisateur est MJ ou Admin
        $isDM = isDM();
        $isAdmin = User::isAdmin();
        $hasPermission = $isDM || $isAdmin;
    } elseif ($ownerType === 'monster') {
        // Pour les monstres, vérifier que l'utilisateur est MJ ou Admin
        $isDM = isDM();
        $isAdmin = User::isAdmin();
        $hasPermission = $isDM || $isAdmin;
    }

    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
        exit();
    }

    // Déséquiper l'objet selon le type de propriétaire
    $result = null;

    if ($ownerType === 'player') {
        // Utiliser la méthode existante pour les personnages
        $result = Character::unequipItemStatic($ownerId, $item['display_name']);
        if ($result) {
            $result = ['success' => true, 'message' => 'Objet déséquipé avec succès'];
        } else {
            $result = ['success' => false, 'message' => 'Erreur lors du déséquipement'];
        }
    } else {
        // Pour les PNJ et monstres - déséquiper directement en base
        $stmt = $pdo->prepare("
            UPDATE items 
            SET is_equipped = 0, equipped_slot = NULL 
            WHERE id = ?
        ");
        $result = $stmt->execute([$itemId]);
        
        if ($result) {
            $result = ['success' => true, 'message' => 'Objet déséquipé avec succès'];
        } else {
            $result = ['success' => false, 'message' => 'Erreur lors du déséquipement'];
        }
    }

    // Retourner le résultat
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(500);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Erreur dans unequip_item.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>