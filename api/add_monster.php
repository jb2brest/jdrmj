<?php
/**
 * API Endpoint: Ajouter un monstre à un lieu
 */

require_once '../includes/functions.php';
require_once '../classes/Lieu.php';
require_once '../classes/Monster.php';

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
    $monsterTypeId = (int)($_POST['monster_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (!$placeId || !$monsterTypeId) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance du lieu
    $lieu = Lieu::findById($placeId);
    if (!$lieu) {
        throw new Exception('Lieu non trouvé');
    }
    
    // Ajouter le monstre
    $result = $lieu->addMonster($monsterTypeId, $quantity);
    
    if ($result['success']) {
        // Rediriger vers la page du lieu pour recharger
        header('Location: ../view_place.php?id=' . $placeId . '&monster_added=1');
        exit();
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Erreur add_monster.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
