<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de sort invalide']);
    exit;
}

$spellId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM spells WHERE id = ?");
    $stmt->execute([$spellId]);
    $spell = $stmt->fetch();
    
    if (!$spell) {
        echo json_encode(['success' => false, 'message' => 'Sort non trouvé']);
        exit;
    }
    
    echo json_encode(['success' => true, 'spell' => $spell]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
    error_log("Erreur get_spell_details: " . $e->getMessage());
}
?>
