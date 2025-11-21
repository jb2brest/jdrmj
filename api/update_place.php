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
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (!$placeId || !$title) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Mettre à jour le lieu (conserver l'URL de la carte existante si elle existe)
    $currentMapUrl = $lieu->getMapUrl() ?? '';
    $resultMap = $lieu->updateMapUrl($currentMapUrl, $notes);
    $resultTitle = $lieu->updateTitle($title);
    
    // Si les deux mises à jour ont réussi, rediriger
    if ($resultMap['success'] && $resultTitle['success']) {
        // Rediriger vers la page du lieu pour recharger
        header('Location: ../view_place.php?id=' . $placeId . '&updated=1');
        exit();
    } else {
        // Si une mise à jour a échoué, vérifier les messages d'erreur
        $errorMessage = !$resultTitle['success'] ? $resultTitle['message'] : ($resultMap['message'] ?? 'Erreur lors de la mise à jour');
        throw new Exception($errorMessage);
    }
    
} catch (Exception $e) {
    error_log("Erreur update_place.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
