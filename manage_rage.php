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

if (!isset($input['character_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$characterId = (int)$input['character_id'];
$action = $input['action'];

try {
    // Vérifier que le personnage appartient à l'utilisateur
    require_once 'classes/init.php';
    $character = Character::findById($characterId);
    
    if (!$character || $character->user_id != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }
    
    switch ($action) {
        case 'use':
            $success = $character->useRage();
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Rage utilisée']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Aucune rage disponible']);
            }
            break;
            
        case 'free':
            $success = $character->freeRage();
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Rage libérée']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Aucune rage utilisée à libérer']);
            }
            break;
            
        case 'reset':
            $success = $character->resetRageUsage();
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Rages réinitialisées']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la réinitialisation']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>



















