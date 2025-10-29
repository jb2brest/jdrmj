<?php
/**
 * API Endpoint pour mettre à jour les caractéristiques d'un personnage temporaire
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

// Validation
if ($pt_id <= 0) {
    $response['message'] = 'ID de personnage temporaire invalide';
    echo json_encode($response);
    exit();
}

// Récupérer les caractéristiques
$strength = (int)($input['strength'] ?? 0);
$dexterity = (int)($input['dexterity'] ?? 0);
$constitution = (int)($input['constitution'] ?? 0);
$intelligence = (int)($input['intelligence'] ?? 0);
$wisdom = (int)($input['wisdom'] ?? 0);
$charisma = (int)($input['charisma'] ?? 0);

// Validation des valeurs
$stats = [$strength, $dexterity, $constitution, $intelligence, $wisdom, $charisma];
foreach ($stats as $stat) {
    if ($stat < 1 || $stat > 20) {
        $response['message'] = 'Les caractéristiques doivent être entre 1 et 20';
        echo json_encode($response);
        exit();
    }
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
    
    // Mettre à jour les caractéristiques
    $ptCharacter->strength = $strength;
    $ptCharacter->dexterity = $dexterity;
    $ptCharacter->constitution = $constitution;
    $ptCharacter->intelligence = $intelligence;
    $ptCharacter->wisdom = $wisdom;
    $ptCharacter->charisma = $charisma;
    $ptCharacter->step = 4;
    
    if ($ptCharacter->update()) {
        $response['success'] = true;
        $response['message'] = 'Caractéristiques mises à jour avec succès';
    } else {
        $response['message'] = 'Erreur lors de la mise à jour des caractéristiques';
    }
    
} catch (Exception $e) {
    error_log("Erreur update_pt_characteristics: " . $e->getMessage());
    $response['message'] = 'Erreur serveur';
}

echo json_encode($response);
?>
