<?php
/**
 * API Endpoint: Récupérer la liste des armes
 */

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();
    
    $stmt = $pdo->query("
        SELECT id, name, type, damage, properties
        FROM weapons 
        ORDER BY name ASC
    ");
    
    $weapons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $weapons
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_weapons.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
