<?php
/**
 * API Endpoint: Mettre à jour un lieu
 */

require_once '../includes/functions.php';
require_once '../classes/Lieu.php';

header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    $placeId = (int)($_POST['place_id'] ?? 0);
    $title = sanitizeInput($_POST['title'] ?? '');
    $mapUrl = sanitizeInput($_POST['map_url'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (!$placeId || !$title) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Mettre à jour le lieu
    $result = $lieu->updateMapUrl($mapUrl, $notes);
    if ($result['success']) {
        $result = $lieu->updateTitle($title);
    }
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Lieu mis à jour avec succès'
        ]);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Erreur update_place.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
