<?php
/**
 * Endpoint simple pour basculer l'identification d'un PNJ
 * Usage: POST avec npc_id et place_id
 */

session_start();
require_once 'classes/init.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier que npc_id est fourni
if (!isset($_POST['npc_id']) || empty($_POST['npc_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du PNJ manquant']);
    exit;
}

// Vérifier que place_id est fourni
if (!isset($_POST['place_id']) || empty($_POST['place_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de la pièce manquant']);
    exit;
}

try {
    $npc_id = (int)$_POST['npc_id'];
    $place_id = (int)$_POST['place_id'];
    
    // Créer l'objet Pièce
    $lieu = Room::findById($place_id);
    
    if (!$lieu) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pièce non trouvé']);
        exit;
    }
    
    // Récupérer les informations de la pièce
    $place = $lieu->toArray();
    
    // Récupérer les campagnes associées à cette pièce
    $campaigns = $lieu->getCampaigns();
    if (!empty($campaigns)) {
        $campaign = $campaigns[0];
        $place['dm_id'] = $campaign['dm_id'];
    } else {
        $place['dm_id'] = null;
    }
    
    // Vérifier que l'utilisateur a le droit de modifier cette pièce
    $dm_id = (int)$place['dm_id'];
    $isOwnerDM = User::isDMOrAdmin() && ($dm_id === 0 || $_SESSION['user_id'] === $dm_id);
    
    if (!$isOwnerDM) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Droits insuffisants']);
        exit;
    }
    
    // Basculer l'identification du PNJ
    $result = $lieu->toggleNpcIdentification($npc_id);
    
    // Retourner la réponse JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur dans toggle_npc_identification.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
}
?>
