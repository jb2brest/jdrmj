<?php
/**
 * API Endpoint: Rechercher des monstres
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'monsters' => []
        ]);
        exit;
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id, name, type, size, challenge_rating, hit_points, armor_class
        FROM dnd_monsters 
        WHERE name LIKE ? 
        ORDER BY name 
        LIMIT 20
    ");
    $stmt->execute(['%' . $query . '%']);
    $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'monsters' => $monsters
    ]);
    
} catch (Exception $e) {
    error_log("Erreur search_monsters.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
