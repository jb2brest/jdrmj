<?php
/**
 * API Endpoint: Rechercher des objets magiques
 */

require_once '../includes/functions.php';

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
            'items' => []
        ]);
        exit;
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id, nom, type, description
        FROM magical_items 
        WHERE nom LIKE ? 
        ORDER BY nom 
        LIMIT 20
    ");
    $stmt->execute(['%' . $query . '%']);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log("Erreur search_magical_items.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
