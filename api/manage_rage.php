<?php
/**
 * API endpoint pour gérer les rages individuelles (utiliser/libérer)
 */

require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isLoggedIn()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

// Récupérer les données POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si les données JSON ne sont pas disponibles, essayer $_POST
if (!$data && isset($_POST['character_id'])) {
    $data = $_POST;
}

// Debug: afficher les données reçues
error_log("Manage rage - Input: " . $input);
error_log("Manage rage - Data: " . print_r($data, true));

$characterId = $data['character_id'] ?? null;
$action = $data['action'] ?? null;

if (!$characterId) {
    $response['message'] = 'Character ID is missing.';
    echo json_encode($response);
    exit();
}

if (!$action || $action !== 'use') {
    $response['message'] = 'Invalid action. Only "use" is allowed.';
    echo json_encode($response);
    exit();
}

// Vérifier les permissions
$character = Character::findById($characterId);
if (!$character || ($character->getUserId() != $_SESSION['user_id'] && !User::isDMOrAdmin())) {
    $response['message'] = 'Access denied.';
    echo json_encode($response);
    exit();
}

// Utiliser une rage
$pdo = \Database::getInstance()->getPdo();
try {
    $result = Character::useRageStatic($characterId);
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Rage utilisée avec succès.';
    } else {
        $response['message'] = 'Impossible d\'utiliser une rage.';
    }
} catch (Exception $e) {
    error_log("Erreur lors de l'utilisation de la rage: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
}

echo json_encode($response);
?>
