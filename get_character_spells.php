<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/init.php';

User::requireLogin();

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
$character = Character::findById($character_id);
if (!$character || $character->getUserId() != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé ou non autorisé']);
    exit;
}

try {
    // Récupérer les sorts du personnage via la classe Sort
    $spells = Sort::getCharacterSpells($character_id);
    
    echo json_encode([
        'success' => true,
        'spells' => $spells
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_character_spells: " . $e->getMessage());
}
?>
