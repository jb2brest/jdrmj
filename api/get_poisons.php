<?php
/**
 * API Endpoint: Récupérer la liste des poisons
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    
    $stmt = $pdo->query("
        SELECT id, nom as name, description, type, source
        FROM poisons 
        ORDER BY nom ASC
    ");
    
    $poisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $poisons
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_poisons.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
