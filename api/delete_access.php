<?php
/**
 * API Endpoint: Supprimer un accès entre deux lieux
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/classes/Access.php';
require_once dirname(__DIR__) . '/classes/Lieu.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur est DM ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé : Vous n\'êtes pas le MJ ou un administrateur');
    }
    
    // Récupérer les données du formulaire
    $access_id = (int)($_POST['access_id'] ?? 0);
    $from_place_id = (int)($_POST['from_place_id'] ?? 0);
    
    // Validation
    if ($access_id === 0) {
        throw new Exception('ID de l\'accès manquant.');
    }
    
    if ($from_place_id === 0) {
        throw new Exception('ID du lieu d\'origine manquant.');
    }
    
    // Récupérer l'accès existant
    $access = Access::findById($access_id);
    if (!$access) {
        throw new Exception('Accès non trouvé.');
    }
    
    // Vérifier que l'accès appartient au lieu d'origine
    if ($access->from_place_id !== $from_place_id) {
        throw new Exception('L\'accès n\'appartient pas au lieu d\'origine spécifié.');
    }
    
    // Vérifier que l'utilisateur a les permissions sur le lieu d'origine
    $lieu = Lieu::findById($from_place_id);
    if (!$lieu) {
        throw new Exception('Lieu d\'origine non trouvé.');
    }
    
    $place = $lieu->toArray();
    $campaigns = $lieu->getCampaigns();
    if (!empty($campaigns)) {
        $campaign = $campaigns[0];
        $place['dm_id'] = $campaign['dm_id'];
    } else {
        $place['dm_id'] = null;
    }
    
    $user_id = $_SESSION['user_id'];
    $isOwnerDM = User::isDMOrAdmin() && ($place['dm_id'] === null || $user_id === (int)$place['dm_id']);
    
    if (!User::isAdmin() && !$isOwnerDM) {
        throw new Exception('Vous n\'avez pas les permissions pour supprimer cet accès.');
    }
    
    // Supprimer l'accès
    if ($access->delete()) {
        echo json_encode([
            'success' => true,
            'message' => 'Accès supprimé avec succès.'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression de l\'accès.');
    }
    
} catch (Exception $e) {
    error_log("Erreur delete_access.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

