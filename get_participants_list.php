<?php
/**
 * API pour récupérer la liste des participants (PNJ et monstres) d'une pièce
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
    echo json_encode(['success' => false, 'error' => 'ID de la pièce manquant']);
    exit;
}

$place_id = (int)$_GET['place_id'];
$user_id = $_SESSION['user_id'];

try {
    require_once 'classes/init.php';
    
    // Vérifier que la pièce existe et récupérer la campagne associée
    $lieu = Room::findById($place_id);
    if (!$lieu) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pièce introuvable']);
        exit;
    }
    
    // Récupérer les campagnes associées à la pièce
    $campaigns = $lieu->getCampaigns();
    if (empty($campaigns)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Pièce non associé à une campagne']);
        exit;
    }
    
    $campaign_id = $campaigns[0]['id'];
    
    // Vérifier l'appartenance à la campagne
    $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
    $stmt->execute([$campaign_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
        exit;
    }
    
    // Récupérer les PNJ visibles
    $stmt = $pdo->prepare("
        SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, 
               COALESCE(n.profile_photo, c.profile_photo) AS character_profile_photo
        FROM place_npcs sn 
        LEFT JOIN characters c ON sn.npc_character_id = c.id
        LEFT JOIN npcs n ON sn.npc_character_id = n.id
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
