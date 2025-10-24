<?php
/**
 * manage_npcs.php - Version refactorisée selon les règles
 * - SQL géré dans des méthodes de classes
 * - JavaScript dans jdrmj.js
 * - Modifications via API endpoints
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Gérer les messages de succès
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'npc_created':
            $npc_name = $_GET['name'] ?? '';
            $success_message = "PNJ créé avec succès : " . htmlspecialchars($npc_name);
            break;
        default:
            $success_message = "Opération réussie.";
    }
}

// Récupérer les filtres
$filters = [
    'type' => $_GET['type'] ?? '',
    'world' => $_GET['world'] ?? '',
    'country' => $_GET['country'] ?? '',
    'region' => $_GET['region'] ?? '',
    'place' => $_GET['place'] ?? ''
];

// Utiliser les méthodes de classe pour récupérer les données
try {
    // Récupérer les entités avec filtres
    $entities = Lieu::getEntitiesByUser($user_id, $filters);
    
    // Récupérer les données pour les filtres
    $worlds = Lieu::getAllWorlds();
    $countries = Pays::getAllCountries();
    $regions = Region::getAllRegions();
    $places = Lieu::getAllPlaces();
    
    // Filtrer les données selon les sélections
    $filtered_countries = [];
    $filtered_regions = [];
    $filtered_places = [];
    
    if (!empty($filters['world'])) {
        $filtered_countries = array_filter($countries, function($c) use ($filters) {
            return $c['world_id'] == $filters['world'];
        });
    }
    
    if (!empty($filters['country'])) {
        $filtered_regions = array_filter($regions, function($r) use ($filters) {
            return $r['country_id'] == $filters['country'];
        });
    }
    
    if (!empty($filters['region'])) {
        $filtered_places = array_filter($places, function($p) use ($filters) {
            return $p['region_id'] == $filters['region'];
        });
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $entities = [];
    $worlds = [];
    $countries = [];
    $regions = [];
    $places = [];
    $error_message = "Erreur lors du chargement des données.";
}

// Préparer les variables pour le template
$template_vars = [
    'entities' => $entities,
    'worlds' => $worlds,
    'countries' => $countries,
    'regions' => $regions,
    'places' => $places,
    'filtered_countries' => $filtered_countries,
    'filtered_regions' => $filtered_regions,
    'filtered_places' => $filtered_places,
    'filter_type' => $filters['type'],
    'filter_world' => $filters['world'],
    'filter_country' => $filters['country'],
    'filter_region' => $filters['region'],
    'filter_place' => $filters['place'],
    'success_message' => $success_message,
    'error_message' => $error_message
];

// Inclure le template
include 'templates/manage_npcs_template.php';
?>
