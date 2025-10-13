<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
requireLogin();

$response = ['success' => false, 'message' => ''];

try {
    $stmt = $pdo->query("SELECT * FROM languages ORDER BY type, name");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['languages'] = $languages;
    
} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    error_log("Erreur get_languages.php: " . $e->getMessage());
}

echo json_encode($response);
?>
