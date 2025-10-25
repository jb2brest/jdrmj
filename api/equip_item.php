<?php
/**
 * API endpoint universelle pour équiper un objet
 * Supporte les personnages, PNJ et monstres
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
$slot = $input['slot'] ?? '';

// Validation
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID d\'objet invalide']);
    exit();
}

if (empty($slot)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Slot manquant']);
    exit();
}

try {
    // Récupérer les informations de l'item
    $pdo = \Database::getInstance()->getPdo();
    $stmt = $pdo->prepare("
        SELECT owner_type, owner_id, display_name, object_type 
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
    
    $ownerType = $item['owner_type'];
    $ownerId = $item['owner_id'];
    
    // Vérifier les permissions selon le type de propriétaire
    $hasPermission = false;
    
    if ($ownerType === 'player') {
        // Pour les personnages
        $character = Character::findById($ownerId);
        if ($character) {
            $isOwner = $character->belongsToUser($_SESSION['user_id']);
            $isDM = isDM();
            $isAdmin = User::isAdmin();
            $hasPermission = $isOwner || $isDM || $isAdmin;
        }
    } elseif ($ownerType === 'npc') {
        // Pour les PNJ
        $npc = NPC::findById($ownerId);
        if ($npc) {
            $isOwner = ($npc->created_by == $_SESSION['user_id']);
            $isDM = isDM();
            $isAdmin = User::isAdmin();
            $hasPermission = $isOwner || $isDM || $isAdmin;
        }
    } elseif ($ownerType === 'monster') {
        // Pour les monstres
        $isDM = isDM();
        $isAdmin = User::isAdmin();
        $hasPermission = $isDM || $isAdmin;
    }
    
    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
        exit();
    }
    
    // Équiper l'objet selon le type de propriétaire
    $result = null;
    
    if ($ownerType === 'player') {
        // Utiliser la méthode existante pour les personnages
        $result = Character::equipItemStatic($ownerId, $item['display_name'], $item['object_type'], $slot);
        if ($result) {
            $result = ['success' => true, 'message' => 'Objet équipé avec succès'];
        } else {
            $result = ['success' => false, 'message' => 'Erreur lors de l\'équipement'];
        }
    } else {
        // Utiliser la méthode universelle pour PNJ et monstres
        $result = NPC::equipItemById($itemId, $slot);
    }
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
