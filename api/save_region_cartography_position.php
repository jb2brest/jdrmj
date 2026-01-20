<?php
/**
 * API Endpoint: Sauvegarder la position d'une pièce dans la cartographie de la région
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Region.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['region_id']) || !isset($input['place_id'])) {
        throw new Exception('Données manquantes');
    }
    
    $regionId = (int)$input['region_id'];
    $placeId = (int)$input['place_id'];
    $positionX = (int)($input['position_x'] ?? 0);
    $positionY = (int)($input['position_y'] ?? 0);
    
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
        throw new Exception('Accès refusé - Vous n\'avez pas la permission de modifier cette région');
    }
    
    // Sauvegarder la position
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
    
    // Vérifier si la position existe déjà
    $stmt = $pdo->prepare("
        SELECT id FROM region_cartography_positions 
        WHERE region_id = ? AND place_id = ?
    ");
    $stmt->execute([$regionId, $placeId]);
    $existingPosition = $stmt->fetch();
    
    if ($existingPosition) {
        // Mettre à jour la position existante
        $stmt = $pdo->prepare("
            UPDATE region_cartography_positions 
            SET position_x = ?, position_y = ?, updated_at = NOW()
            WHERE region_id = ? AND place_id = ?
        ");
        $stmt->execute([$positionX, $positionY, $regionId, $placeId]);
    } else {
        // Créer une nouvelle entrée
        $stmt = $pdo->prepare("
            INSERT INTO region_cartography_positions (region_id, place_id, position_x, position_y, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$regionId, $placeId, $positionX, $positionY]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Position sauvegardée avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur save_region_cartography_position.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

