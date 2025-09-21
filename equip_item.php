<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['character_id']) || !isset($input['item_name']) || !isset($input['item_type']) || !isset($input['slot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$characterId = (int)$input['character_id'];
$itemName = $input['item_name'];
$itemType = $input['item_type'];
$slot = $input['slot'];

try {
    // Vérifier que le personnage appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT user_id FROM characters WHERE id = ?");
    $stmt->execute([$characterId]);
    $character = $stmt->fetch();
    
    if (!$character || $character['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
    
    // Vérifier les règles d'équipement
    if ($itemType === 'weapon') {
        // Récupérer les informations de l'arme
        $stmt = $pdo->prepare("SELECT hands FROM weapons WHERE name = ?");
        $stmt->execute([$itemName]);
        $weapon = $stmt->fetch();
        
        if (!$weapon) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Arme non trouvée']);
            exit;
        }
        
        // Vérifier les règles d'équipement
        if ($weapon['hands'] == 2) {
            // Arme à deux mains : libérer les deux slots
            $stmt = $pdo->prepare("
                UPDATE place_objects 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot IN ('main_hand', 'off_hand')
            ");
            $stmt->execute([$characterId]);
        } else {
            // Arme à une main : libérer seulement le slot principal
            $stmt = $pdo->prepare("
                UPDATE place_objects 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = 'main_hand'
            ");
            $stmt->execute([$characterId]);
        }
    } elseif ($itemType === 'shield') {
        // Bouclier : libérer le slot off_hand
        $stmt = $pdo->prepare("
            UPDATE place_objects 
            SET is_equipped = 0, equipped_slot = NULL 
            WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = 'off_hand'
        ");
        $stmt->execute([$characterId]);
    } elseif ($itemType === 'armor') {
        // Armure : libérer le slot armor
        $stmt = $pdo->prepare("
            UPDATE place_objects 
            SET is_equipped = 0, equipped_slot = NULL 
            WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = 'armor'
        ");
        $stmt->execute([$characterId]);
    }
    
    // Équiper l'objet
    $success = equipItem($characterId, $itemName, $itemType, $slot);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Objet équipé avec succès']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'équipement']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
