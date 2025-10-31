<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/init.php';

requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['character_id']) || !isset($input['action']) || !isset($input['level'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$character_id = (int)$input['character_id'];
$action = $input['action'];
$level = (int)$input['level'];

// Vérifier que le personnage appartient à l'utilisateur
$character = Character::findById($character_id);
if (!$character || $character->user_id != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé ou non autorisé']);
    exit;
}

try {
    if ($action === 'use') {
        // Utiliser un emplacement de sort
        if ($character->useSpellSlot($level)) {
            echo json_encode(['success' => true, 'message' => 'Emplacement de sort utilisé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Impossible d\'utiliser l\'emplacement de sort']);
        }
    } elseif ($action === 'free') {
        // Libérer un emplacement de sort
        if ($character->freeSpellSlot($level)) {
            echo json_encode(['success' => true, 'message' => 'Emplacement de sort libéré']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Impossible de libérer l\'emplacement de sort']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur manage_spell_slots: " . $e->getMessage());
}
?>
