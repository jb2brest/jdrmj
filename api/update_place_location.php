<?php
require_once '../classes/init.php';
require_once '../includes/functions.php';
require_once '../classes/Room.php';
require_once '../classes/Region.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

// Récupérer les données
$place_id = (int)($_POST['place_id'] ?? 0);
$location_id = (int)($_POST['location_id'] ?? 0);

if ($place_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de pièce invalide']);
    exit();
}

try {
    // Récupérer la pièce
    $place = Room::findById($place_id);
    
    if (!$place) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pièce non trouvée']);
        exit();
    }
    
    // Vérifier les droits (le créateur du monde/région)
    $region = Region::findById($place->region_id);
    if (!$region) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Région introuvable']);
        exit();
    }
    
    $monde = $region->getMonde();
    if (!$monde || $monde['created_by'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        exit();
    }
    
    // Si location_id > 0, vérifier que le lieu existe et appartient à la même région
    if ($location_id > 0) {
        require_once '../classes/Location.php';
        $location = Location::findById($location_id);
        
        if (!$location) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Lieu introuvable']);
            exit();
        }
        
        if ($location->getRegionId() != $place->region_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Le lieu n\'appartient pas à la même région']);
            exit();
        }
    }
    
    // Mettre à jour la location
    // Si location_id est 0, Room traite cela comme NULL si on passe null, ou on adapte Room::save
    // Regardons Room.php : $lieu->location_id = $location_id ? $location_id : null; 
    // Donc on doit passer null si 0.
    
    $place->location_id = $location_id > 0 ? $location_id : null;
    $place->save();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Lieu mis à jour avec succès',
        'place_id' => $place_id,
        'location_id' => $location_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
