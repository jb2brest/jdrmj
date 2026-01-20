<?php
/**
 * API Endpoint: Mettre à jour une pièce
 */

require_once '../includes/functions.php';
require_once '../classes/Room.php';
require_once '../classes/Location.php';

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
    
    // Gestion du location_id
    $locationId = null;
    if (isset($_POST['location_id']) && $_POST['location_id'] !== '') {
        $locationId = (int)$_POST['location_id'];
    }
    
    if (!$placeId || !$title) {
        throw new Exception('Données manquantes');
    }
    
    // Créer l'instance de la pièce
    $lieu = Room::findById($placeId);
    if (!$lieu) {
        throw new Exception('Pièce non trouvée');
    }
    
    // Mise à jour des propriétés
    $lieu->title = $title;
    $lieu->notes = $notes;
    
    // Mise à jour du location_id seulement si envoyé (pour éviter d'écraser si le formulaire n'est pas à jour)
    if (isset($_POST['location_id'])) {
        $lieu->location_id = $locationId;
    }
    
    // Sauvegarde
    if ($lieu->save()) {
        // Rediriger vers la page de la pièce pour recharger
        // Note: On pourrait renvoyer du JSON et laisser le JS recharger, 
        // mais le code existant semble rediriger ou le JS gère la redirection si c'est un submit classique ?
        // Le code JS dans view_place_template.php semble ne pas êter présent pour ce formulaire spécifique (appelé via form action direct ?)
        // Ah, je vois dans view_place_template que c'est un form standard action="api/update_place.php"
        // SAUF QUE, le modal s'attend peut-être à une redirection PHP classique si pas intercepté par JS.
        // Mais l'entête JSON suggère une réponse AJAX. 
        // Vérifions le js dans view_place_template.php ligne 1401... Ah non c'était pour updateAccess.
        
        // Comme c'est un appel API qui redirigeait avant:
        header('Location: ../view_place.php?id=' . $placeId . '&updated=1');
        exit();
    } else {
        throw new Exception('Erreur lors de la sauvegarde de la pièce');
    }
    
} catch (Exception $e) {
    error_log("Erreur update_place.php: " . $e->getMessage());
    // Si c'est une requête AJAX, renvoyer JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        // Sinon afficher erreur et retour
        die("Erreur: " . $e->getMessage() . " <a href='javascript:history.back()'>Retour</a>");
    }
}
?>
