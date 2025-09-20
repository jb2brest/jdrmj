<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

try {
    // Récupérer toutes les armes
    $stmt = $pdo->query("SELECT id, name as nom, CONCAT('Dégâts: ', damage, ' | Poids: ', weight, ' | Prix: ', price, ' | Type: ', type) as description FROM weapons ORDER BY name ASC");
    $weapons = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'weapons' => $weapons
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>
