<?php
/**
 * API Endpoint pour mettre à jour la race d'un personnage temporaire
 */

require_once '../classes/init.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

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
$pt_id = (int)($input['pt_id'] ?? 0);
$race_id = (int)($input['race_id'] ?? 0);

// Validation
if ($pt_id <= 0) {
    $response['message'] = 'ID de personnage temporaire invalide';
    echo json_encode($response);
    exit();
}

if ($race_id <= 0) {
    $response['message'] = 'ID de race invalide';
    echo json_encode($response);
    exit();
}

try {
    // Récupérer le personnage temporaire
    $ptCharacter = PTCharacter::findById($pt_id);
    
    if (!$ptCharacter) {
        $response['message'] = 'Personnage temporaire non trouvé';
        echo json_encode($response);
        exit();
    }
    
    // Vérifier que l'utilisateur est propriétaire
    if ($ptCharacter->user_id != $_SESSION['user_id']) {
        $response['message'] = 'Permissions insuffisantes';
        echo json_encode($response);
        exit();
    }
    
    // Mettre à jour la race
    $ptCharacter->race_id = $race_id;
    $ptCharacter->step = 2;
    
    if ($ptCharacter->update()) {
        $response['success'] = true;
        $response['message'] = 'Race mise à jour avec succès';
    } else {
        $response['message'] = 'Erreur lors de la mise à jour de la race';
    }
    
} catch (Exception $e) {
    error_log("Erreur update_pt_race: " . $e->getMessage());
    $response['message'] = 'Erreur serveur';
}

echo json_encode($response);
?>
