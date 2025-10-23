<?php
/**
 * API pour récupérer les cibles d'attribution d'objets
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/User.php';

header('Content-Type: application/json');

try {
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    if (!User::isDMOrAdmin()) {
        throw new Exception('Permissions insuffisantes');
    }
    
    $placeId = (int)($_GET['place_id'] ?? 0);
    
    if (!$placeId) {
        throw new Exception('ID du lieu requis');
    }
    
    $pdo = getPDO();
    $targets = [];
    
    // Récupérer tous les personnages joueurs présents dans le lieu
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, u.username, 'player' as type
        FROM characters c 
        JOIN users u ON c.user_id = u.id 
        JOIN place_players pp ON c.id = pp.character_id 
        WHERE pp.place_id = ?
        ORDER BY c.name ASC
    ");
    $stmt->execute([$placeId]);
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($characters as $char) {
        $targets[] = [
            'id' => $char['id'],
            'name' => $char['name'] . ' (' . $char['username'] . ') - PJ',
            'type' => 'player'
        ];
    }
    
    // Récupérer tous les PNJ présents dans le lieu
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, 'npc' as type
        FROM place_npcs pn
        WHERE pn.place_id = ?
        ORDER BY pn.name ASC
    ");
    $stmt->execute([$placeId]);
    $npcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($npcs as $npc) {
        $targets[] = [
            'id' => $npc['id'],
            'name' => $npc['name'] . ' - PNJ',
            'type' => 'npc'
        ];
    }
    
    // Récupérer tous les monstres présents dans le lieu
    $stmt = $pdo->prepare("
        SELECT pm.id, m.name, 'monster' as type
        FROM place_monsters pm
        JOIN monsters m ON pm.monster_id = m.id
        WHERE pm.place_id = ?
        ORDER BY m.name ASC
    ");
    $stmt->execute([$placeId]);
    $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($monsters as $monster) {
        $targets[] = [
            'id' => $monster['id'],
            'name' => $monster['name'] . ' - Monstre',
            'type' => 'monster'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'targets' => $targets
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_assign_targets.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
