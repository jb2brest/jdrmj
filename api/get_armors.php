<?php
/**
 * API Endpoint: Récupérer la liste des armures
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    
    $stmt = $pdo->query("
        SELECT id, name, type, ac_formula, strength_requirement, stealth_disadvantage
        FROM armor 
        ORDER BY name ASC
    ");
    
    $armors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $armors
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_armors.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
