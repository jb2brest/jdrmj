<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['character_id'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$character_id = (int)$input['character_id'];

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $user_id]);
$character = $stmt->fetch();

if (!$character) {
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé']);
    exit;
}

// Vérifier que la classe peut lancer des sorts
if (!canCastSpells($character['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Cette classe ne peut pas lancer de sorts']);
    exit;
}

try {
    if (resetSpellSlotsUsage($character_id)) {
        echo json_encode(['success' => true, 'message' => 'Long repos effectué avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du long repos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du long repos']);
    error_log("Erreur reset_spell_slots: " . $e->getMessage());
}
?>
