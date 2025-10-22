<?php
/**
 * API Endpoint: Supprimer un objet
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
    
    // Supprimer l'objet
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $success = $stmt->execute([$objectId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Objet supprimé avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression de l\'objet');
    }
    
} catch (Exception $e) {
    error_log("Erreur remove_object.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
