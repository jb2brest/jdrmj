<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Accès refusé');
}

// Récupérer le paramètre de recherche
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Rechercher les monstres
    $sql = "SELECT id, name, type, size, challenge_rating, hit_points, armor_class 
            FROM dnd_monsters 
            WHERE name LIKE ? OR type LIKE ? 
            ORDER BY name ASC 
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $searchParam = "%{$query}%";
    $stmt->execute([$searchParam, $searchParam]);
    $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats en JSON
    header('Content-Type: application/json');
    echo json_encode($monsters);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche']);
}
?>
