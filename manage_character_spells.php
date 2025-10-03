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

if (!$input || !isset($input['action']) || !isset($input['character_id']) || !isset($input['spell_id'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$action = $input['action'];
$character_id = (int)$input['character_id'];
$spell_id = (int)$input['spell_id'];

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $user_id]);
$character = $stmt->fetch();

if (!$character) {
    echo json_encode(['success' => false, 'message' => 'Personnage non trouvé']);
    exit;
}

// Vérifier que la classe peut lancer des sorts
if (!Character::canCastSpells($character['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Cette classe ne peut pas lancer de sorts']);
    exit;
}

try {
    switch ($action) {
        case 'add':
            $prepared = isset($input['prepared']) ? (bool)$input['prepared'] : false;
            if (Character::addSpellToCharacter($character_id, $spell_id, $prepared)) {
                echo json_encode(['success' => true, 'message' => 'Sort ajouté avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du sort']);
            }
            break;
            
        case 'remove':
            if (Character::removeSpellFromCharacter($character_id, $spell_id)) {
                echo json_encode(['success' => true, 'message' => 'Sort retiré avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du sort']);
            }
            break;
            
        case 'toggle_prepared':
            $prepared = isset($input['prepared']) ? (bool)$input['prepared'] : false;
            if (Character::updateSpellPrepared($character_id, $spell_id, $prepared)) {
                echo json_encode(['success' => true, 'message' => 'État du sort mis à jour']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            }
            break;
            
        case 'unprepare':
            if (Character::updateSpellPrepared($character_id, $spell_id, false)) {
                echo json_encode(['success' => true, 'message' => 'Sort dépréparé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la dépréparation']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur manage_character_spells: " . $e->getMessage());
}
?>

