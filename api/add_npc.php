<?php
/**
 * API Endpoint: Ajouter un PNJ à un lieu
 */

require_once '../includes/functions.php';
require_once '../classes/Lieu.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $placeId = (int)($_POST['place_id'] ?? 0);
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $characterId = (int)($_POST['character_id'] ?? 0);
    
    if (!$placeId || !$name) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Ajouter le PNJ
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO place_npcs (place_id, name, description, npc_character_id, is_visible, is_identified)
        VALUES (?, ?, ?, ?, 1, 0)
    ");
    
    $success = $stmt->execute([$placeId, $name, $description, $characterId ?: null]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'PNJ ajouté avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de l\'ajout du PNJ');
    }
    
} catch (Exception $e) {
    error_log("Erreur add_npc.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
