<?php
/**
 * API pour récupérer la liste des participants (PNJ et monstres) d'un lieu
 * Utilisé par view_scene_player.php pour la mise à jour automatique
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if (!isset($_GET['place_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID du lieu manquant']);
    exit;
}

$place_id = (int)$_GET['place_id'];
$user_id = $_SESSION['user_id'];

try {
    // Vérifier que l'utilisateur est membre de la campagne
    $stmt = $pdo->prepare("
        SELECT c.id as campaign_id 
        FROM places p 
        JOIN campaigns c ON p.campaign_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();
    
    if (!$place) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Lieu introuvable']);
        exit;
    }
    
    // Vérifier l'appartenance à la campagne
    $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
    $stmt->execute([$place['campaign_id'], $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
        exit;
    }
    
    // Récupérer les PNJ visibles
    $stmt = $pdo->prepare("
        SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo
        FROM place_npcs sn 
        LEFT JOIN characters c ON sn.npc_character_id = c.id
        WHERE sn.place_id = ? AND sn.monster_id IS NULL AND sn.is_visible = 1
        ORDER BY sn.name ASC
    ");
    $stmt->execute([$place_id]);
    $npcs = $stmt->fetchAll();
    
    // Récupérer les monstres visibles
    $stmt = $pdo->prepare("
        SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, sn.is_identified,
               m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class, m.csv_id
        FROM place_npcs sn 
        JOIN dnd_monsters m ON sn.monster_id = m.id 
        WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL AND sn.is_visible = 1
        ORDER BY sn.name ASC
    ");
    $stmt->execute([$place_id]);
    $monsters = $stmt->fetchAll();
    
    // Retourner la réponse JSON
    echo json_encode([
        'success' => true,
        'npcs' => $npcs,
        'monsters' => $monsters
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
