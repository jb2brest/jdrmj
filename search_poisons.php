<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que la requête est AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Requête invalide');
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// Récupérer le terme de recherche
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Rechercher les poisons
    $stmt = $pdo->prepare("
        SELECT csv_id, nom, type, description, source 
        FROM poisons 
        WHERE MATCH(nom, cle, description, type) AGAINST(? IN NATURAL LANGUAGE MODE)
        OR nom LIKE ? 
        OR type LIKE ?
        ORDER BY 
            CASE 
                WHEN nom LIKE ? THEN 1
                WHEN type LIKE ? THEN 2
                ELSE 3
            END,
            nom ASC
        LIMIT 20
    ");
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$query, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    
    $poisons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nettoyer les données pour la sécurité
    foreach ($poisons as &$poison) {
        $poison['nom'] = htmlspecialchars($poison['nom']);
        $poison['type'] = htmlspecialchars($poison['type']);
        $poison['description'] = htmlspecialchars($poison['description']);
        $poison['source'] = htmlspecialchars($poison['source']);
    }
    
    header('Content-Type: application/json');
    echo json_encode($poisons);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la recherche de poisons: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche']);
}
?>