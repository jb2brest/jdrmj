<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

try {
    // Récupérer tous les objets magiques
    $stmt = $pdo->query("SELECT id, nom, description FROM magical_items ORDER BY nom ASC");
    $items = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
