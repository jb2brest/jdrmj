<?php
/**
 * API Endpoint: Mettre à jour un accès entre deux pièces
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/init.php';
require_once dirname(__DIR__) . '/classes/Access.php';
require_once dirname(__DIR__) . '/classes/Room.php';

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
    
    // Vérifier que l'utilisateur est DM ou Admin
    if (!User::isDMOrAdmin()) {
        throw new Exception('Accès refusé : Vous n\'êtes pas le MJ ou un administrateur');
    }
    
    // Récupérer les données du formulaire
    $access_id = (int)($_POST['access_id'] ?? 0);
    $from_place_id = (int)($_POST['from_place_id'] ?? 0);
    $to_place_id = (int)($_POST['to_place_id'] ?? 0);
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $is_open = isset($_POST['is_open']) ? 1 : 0;
    $is_trapped = isset($_POST['is_trapped']) ? 1 : 0;
    $trap_description = sanitizeInput($_POST['trap_description'] ?? '');
    $trap_difficulty = !empty($_POST['trap_difficulty']) ? (int)$_POST['trap_difficulty'] : null;
    $trap_damage = sanitizeInput($_POST['trap_damage'] ?? '');
    $position_x = (int)($_POST['position_x'] ?? 0);
    $position_y = (int)($_POST['position_y'] ?? 0);
    $is_on_map = isset($_POST['is_on_map']) ? 1 : 0;
    
    // Validation
    if ($access_id === 0) {
        throw new Exception('ID de l\'accès manquant.');
    }
    
    if (empty($name)) {
        throw new Exception('Le nom de l\'accès est requis.');
    }
    
    if ($to_place_id === 0) {
        throw new Exception('La pièce de destination est requis.');
    }
    
    if ($from_place_id === 0) {
        throw new Exception('La pièce d\'origine est requis.');
    }
    
    if ($from_place_id === $to_place_id) {
        throw new Exception('Une pièce ne peut pas avoir d\'accès vers lui-même.');
    }
    
    // Récupérer l'accès existant
    $access = Access::findById($access_id);
    if (!$access) {
        throw new Exception('Accès non trouvé.');
    }
    
    // Vérifier que l'accès appartient à la pièce d'origine
    if ($access->from_place_id !== $from_place_id) {
        throw new Exception('L\'accès n\'appartient pas à la pièce d\'origine spécifié.');
    }
    
    // Vérifier que l'utilisateur a les permissions sur la pièce d'origine
    $lieu = Room::findById($from_place_id);
    if (!$lieu) {
        throw new Exception('Pièce d\'origine non trouvé.');
    }
    
    $place = $lieu->toArray();
    $campaigns = $lieu->getCampaigns();
    if (!empty($campaigns)) {
        $campaign = $campaigns[0];
        $place['dm_id'] = $campaign['dm_id'];
    } else {
        $place['dm_id'] = null;
    }
    
    $user_id = $_SESSION['user_id'];
    $isOwnerDM = User::isDMOrAdmin() && ($place['dm_id'] === null || $user_id === (int)$place['dm_id']);
    
    if (!User::isAdmin() && !$isOwnerDM) {
        throw new Exception('Vous n\'avez pas les permissions pour modifier cet accès.');
    }
    
    // Vérifier qu'un autre accès avec le même nom n'existe pas déjà (sauf celui qu'on modifie)
    if (Access::existsBetween($from_place_id, $to_place_id, $name) && $access->to_place_id !== $to_place_id) {
        throw new Exception('Un accès avec ce nom existe déjà vers cette pièce de destination.');
    }
    
    // Mettre à jour l'accès
    $access->to_place_id = $to_place_id;
    $access->name = $name;
    $access->description = $description;
    $access->is_visible = $is_visible;
    $access->is_open = $is_open;
    $access->is_trapped = $is_trapped;
    $access->trap_description = $trap_description;
    $access->trap_difficulty = $trap_difficulty;
    $access->trap_damage = $trap_damage;
    $access->position_x = $position_x;
    $access->position_y = $position_y;
    $access->is_on_map = $is_on_map;
    
    if ($access->save()) {
        echo json_encode([
            'success' => true,
            'message' => 'Accès modifié avec succès.',
            'access_id' => $access->id
        ]);
    } else {
        throw new Exception('Erreur lors de la sauvegarde de l\'accès.');
    }
    
} catch (Exception $e) {
    error_log("Erreur update_access.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

