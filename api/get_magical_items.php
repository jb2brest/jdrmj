<?php
/**
 * API Endpoint: Récupérer la liste des objets magiques
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    
    $stmt = $pdo->query("
        SELECT id, nom as name, description, type, source
        FROM magical_items 
        ORDER BY nom ASC
    ");
    
    $magicalItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $magicalItems
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_magical_items.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
