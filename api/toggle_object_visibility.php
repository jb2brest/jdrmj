<?php
/**
 * API Endpoint: Basculer la visibilité d'un objet
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Lieu.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['object_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $objectId = (int)$input['object_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur est DM ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé : Vous n\'êtes pas le MJ ou un administrateur');
    }
    
    $pdo = getPDO();
    
    // Basculer la visibilité de l'objet
    $stmt = $pdo->prepare("
        UPDATE items 
        SET is_visible = NOT is_visible 
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$objectId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Visibilité de l\'objet mise à jour'
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour de la visibilité');
    }
    
} catch (Exception $e) {
    error_log("Erreur toggle_object_visibility.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
