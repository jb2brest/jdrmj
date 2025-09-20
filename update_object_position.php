<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit();
}

$place_id = (int)($input['place_id'] ?? 0);
$object_id = (int)($input['object_id'] ?? 0);
$position_x = (int)($input['position_x'] ?? 0);
$position_y = (int)($input['position_y'] ?? 0);
$is_on_map = (bool)($input['is_on_map'] ?? false);

if ($place_id <= 0 || $object_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit();
}

try {
    // Vérifier que l'objet appartient au lieu et que l'utilisateur a les droits
    $stmt = $pdo->prepare("
        SELECT po.id, p.campaign_id, c.dm_id 
        FROM place_objects po 
        JOIN places p ON po.place_id = p.id 
        JOIN campaigns c ON p.campaign_id = c.id 
        WHERE po.id = ? AND po.place_id = ?
    ");
    $stmt->execute([$object_id, $place_id]);
    $object = $stmt->fetch();
    
    if (!$object) {
        echo json_encode(['success' => false, 'error' => 'Objet non trouvé']);
        exit();
    }
    
    // Vérifier que l'utilisateur est le MJ de la campagne ou un admin
    if (!isAdmin() && $_SESSION['user_id'] != $object['dm_id']) {
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit();
    }
    
    // Mettre à jour la position de l'objet
    $stmt = $pdo->prepare("
        UPDATE place_objects 
        SET position_x = ?, position_y = ?, is_on_map = ? 
        WHERE id = ? AND place_id = ?
    ");
    $stmt->execute([$position_x, $position_y, $is_on_map, $object_id, $place_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune modification effectuée']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
