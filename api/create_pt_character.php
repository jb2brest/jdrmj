<?php
/**
 * API Endpoint pour créer un personnage temporaire
 */

require_once '../classes/init.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'pt_character_id' => null];

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée';
    echo json_encode($response);
    exit();
}

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Non authentifié';
    echo json_encode($response);
    exit();
}

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);
$class_id = (int)($input['class_id'] ?? 0);
$character_type = $input['character_type'] ?? 'player';

// Validation
if ($class_id <= 0) {
    $response['message'] = 'ID de classe invalide';
    echo json_encode($response);
    exit();
}

if (!in_array($character_type, ['player', 'npc'])) {
    $response['message'] = 'Type de personnage invalide';
    echo json_encode($response);
    exit();
}

// Vérifier les permissions pour les PNJ
if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    $response['message'] = 'Permissions insuffisantes';
    echo json_encode($response);
    exit();
}

try {
    // Créer le personnage temporaire
    $ptCharacter = new PTCharacter();
    $ptCharacter->user_id = $_SESSION['user_id'];
    $ptCharacter->character_type = $character_type;
    $ptCharacter->step = 1;
    $ptCharacter->class_id = $class_id;
    
    if ($ptCharacter->create()) {
        $response['success'] = true;
        $response['message'] = 'Personnage temporaire créé avec succès';
        $response['pt_character_id'] = $ptCharacter->id;
    } else {
        $response['message'] = 'Erreur lors de la création du personnage temporaire';
    }
    
} catch (Exception $e) {
    error_log("Erreur create_pt_character: " . $e->getMessage());
    $response['message'] = 'Erreur serveur';
}

echo json_encode($response);
?>
