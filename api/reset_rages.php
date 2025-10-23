<?php
/**
 * API endpoint pour réinitialiser les rages d'un personnage (long repos)
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
error_log("Reset rages - Input: " . $input);
error_log("Reset rages - Data: " . print_r($data, true));

$characterId = $data['character_id'] ?? null;

if (!$characterId) {
    $response['message'] = 'Character ID is missing.';
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

// Réinitialiser les rages
$pdo = \Database::getInstance()->getPdo();
try {
    $stmt = $pdo->prepare("UPDATE character_rage_usage SET used = 0 WHERE character_id = ?");
    $success = $stmt->execute([$characterId]);
    
    if ($success) {
        $response['success'] = true;
        $response['message'] = 'Rages réinitialisées avec succès.';
    } else {
        $response['message'] = 'Failed to reset rages.';
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la réinitialisation des rages: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
}

echo json_encode($response);
?>
