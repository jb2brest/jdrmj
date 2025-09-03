<?php
require_once 'config/database.php';
header('Content-Type: application/json');

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès non autorisé']));
}

// Récupérer le paramètre de recherche
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Recherche dans la base de données avec recherche fulltext
    $stmt = $pdo->prepare("
        SELECT id, csv_id, nom, cle, description, type, source 
        FROM magical_items 
        WHERE MATCH(nom, cle, description, type) AGAINST(? IN BOOLEAN MODE)
        OR nom LIKE ? OR cle LIKE ? OR type LIKE ?
        ORDER BY nom ASC
        LIMIT 50
    ");
    
    $searchTerm = "%$query%";
    $stmt->execute([$query, $searchTerm, $searchTerm, $searchTerm]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les résultats pour la compatibilité
    $formattedItems = [];
    foreach ($items as $item) {
        $formattedItems[] = [
            'id' => $item['csv_id'], // Utiliser csv_id pour la compatibilité
            'nom' => $item['nom'],
            'cle' => $item['cle'],
            'description' => $item['description'],
            'type' => $item['type'],
            'source' => $item['source']
        ];
    }
    
    echo json_encode($formattedItems);
    
} catch (PDOException $e) {
    // En cas d'erreur, fallback vers la recherche CSV
    echo json_encode([]);
}
?>
