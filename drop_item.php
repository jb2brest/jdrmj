<?php
/**
 * Script pour déposer un objet d'équipement dans le lieu actuel
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_id']) || !isset($input['item_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$item_id = (int)$input['item_id'];
$item_name = $input['item_name'];
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Récupérer les informations de l'objet à déposer
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as character_name, pp.place_id
        FROM items i
        JOIN characters c ON i.owner_id = c.id
        LEFT JOIN place_players pp ON c.id = pp.character_id
        WHERE i.id = ? AND i.owner_type = 'player' AND c.user_id = ?
    ");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Objet non trouvé ou non autorisé');
    }
    
    // Vérifier que le personnage est dans un lieu
    if (!$item['place_id']) {
        throw new Exception('Le personnage n\'est dans aucun lieu');
    }
    
    // Insérer l'objet dans items
    $stmt = $pdo->prepare("
        INSERT INTO items (
            place_id, 
            display_name, 
            object_type, 
            description, 
            is_visible, 
            is_equipped, 
            position_x, 
            position_y, 
            is_on_map,
            owner_type,
            owner_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $item['place_id'],
        $item['item_name'], // display_name
        $item['item_type'], // object_type
        $item['item_description'], // description
        1, // is_visible (l'objet devient visible sur la carte)
        0, // is_equipped (toujours non équipé lors du dépôt)
        0, // position_x
        0, // position_y
        0, // is_on_map (pas sur la carte par défaut)
        'place', // owner_type (appartient au lieu)
        $item['place_id'] // owner_id (ID du lieu)
    ]);
    
    // Supprimer l'objet de l'inventaire du personnage
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND owner_type = 'player'");
    $stmt->execute([$item_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "L'objet '{$item_name}' a été déposé dans le lieu actuel"
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
