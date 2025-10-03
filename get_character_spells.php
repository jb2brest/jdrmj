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

// Si pas de données JSON, essayer POST
if (!$input) {
    $input = $_POST;
}

if (!$input || !isset($input['character_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du personnage manquant']);
    exit;
}

$character_id = (int)$input['character_id'];

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT id FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $user_id]);
$character = $stmt->fetch();

if (!$character) {
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé ou non autorisé']);
    exit;
}

try {
    // Récupérer les sorts du personnage
    $spells = Character::getCharacterSpells($character_id);
    
    echo json_encode([
        'success' => true,
        'spells' => $spells
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_character_spells: " . $e->getMessage());
}
?>
