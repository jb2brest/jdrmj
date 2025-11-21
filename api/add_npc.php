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
    
    // Vérifier les permissions (même logique que view_place.php)
    require_once '../classes/User.php';
    require_once '../classes/Campaign.php';
    
    $campaigns = $lieu->getCampaigns();
    $dm_id = 0;
    if (!empty($campaigns)) {
        $dm_id = (int)$campaigns[0]['dm_id'];
    }
    
    $isOwnerDM = User::isDMOrAdmin() && ($dm_id === 0 || $_SESSION['user_id'] === $dm_id);
    
    if (!$isOwnerDM) {
        throw new Exception('Vous n\'avez pas la permission d\'ajouter un PNJ à ce lieu');
    }
    
    // Ajouter le PNJ
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        INSERT INTO place_npcs (place_id, name, description, npc_character_id, is_visible, is_identified, monster_id)
        VALUES (?, ?, ?, ?, 1, 0, NULL)
    ");
    
    $success = $stmt->execute([$placeId, $name, $description, $characterId ?: null]);
    
    if ($success) {
        // Rediriger vers la page du lieu pour recharger
        header('Location: ../view_place.php?id=' . $placeId . '&npc_added=1');
        exit();
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
