<?php
/**
 * API Endpoint: Récupérer les positions des pièces dans la cartographie de la région
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Region.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }
    
    if (!isset($_GET['region_id'])) {
        throw new Exception('region_id manquant');
    }
    
    $regionId = (int)$_GET['region_id'];
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    // Vérifier que l'utilisateur a accès à cette région
    $region = Region::findById($regionId);
    if (!$region) {
        throw new Exception('Région non trouvée');
    }
    
    $monde = $region->getMonde();
    if (!$monde || $monde['created_by'] != $_SESSION['user_id']) {
        throw new Exception('Accès refusé');
    }
    
    // Récupérer les positions
    $pdo = getPDO();
    
    // Créer la table si elle n'existe pas
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS region_cartography_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                region_id INT NOT NULL,
                place_id INT NOT NULL,
                position_x INT NOT NULL DEFAULT 0,
                position_y INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_region_place (region_id, place_id),
                INDEX idx_region_id (region_id),
                INDEX idx_place_id (place_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        // La table existe peut-être déjà, continuer
        error_log("Note: Table region_cartography_positions peut déjà exister: " . $e->getMessage());
    }
    
    $stmt = $pdo->prepare("
        SELECT place_id, position_x, position_y
        FROM region_cartography_positions 
        WHERE region_id = ?
    ");
    $stmt->execute([$regionId]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir en format clé-valeur pour faciliter l'accès
    $positionsMap = [];
    foreach ($positions as $pos) {
        $positionsMap[$pos['place_id']] = [
            'x' => (int)$pos['position_x'],
            'y' => (int)$pos['position_y']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'positions' => $positionsMap
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_region_cartography_positions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'positions' => []
    ]);
}
?>

