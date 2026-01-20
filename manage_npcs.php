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
        case 'monsters_created':
            $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
            $success_message = $count > 1 
                ? "$count monstres créés avec succès !"
                : "Monstre créé avec succès !";
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
    $entities = Room::getEntitiesByUser($user_id, $filters);
    
    // Récupérer les données pour les filtres
    $worlds = Room::getAllWorlds();
    $countries = Pays::getAllCountries();
    $regions = Region::getAllRegions();
    $places = Room::getAllPlaces();
    
    // Filtrer les données selon les sélections
    $filtered_countries = [];
    $filtered_regions = [];
    $filtered_places = [];
    
    if (!empty($filters['world'])) {
        $filtered_countries = array_filter($countries, function($c) use ($filters) {
            return isset($c['world_id']) && $c['world_id'] == $filters['world'];
        });
        $filtered_countries = array_values($filtered_countries); // Réindexer pour éviter les objets JSON
    }
    
    if (!empty($filters['country'])) {
        $filtered_regions = array_filter($regions, function($r) use ($filters) {
            return isset($r['country_id']) && $r['country_id'] == $filters['country'];
        });
        $filtered_regions = array_values($filtered_regions);
    }
    
    if (!empty($filters['region'])) {
        $filtered_places = array_filter($places, function($p) use ($filters) {
            return isset($p['region_id']) && $p['region_id'] == $filters['region'];
        });
        $filtered_places = array_values($filtered_places);
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

// Récupérer les créations PNJ en cours (PT_characters type npc)
$ptDraftsAll = PTCharacter::findByUserId($user_id);
$ptNpcDrafts = array_values(array_filter($ptDraftsAll, function ($pt) {
    return isset($pt->character_type) && $pt->character_type === 'npc';
}));

// Enrichir pour le template (nom de classe/race et URL de reprise)
$ptNpcDraftCards = [];
foreach ($ptNpcDrafts as $ptc) {
    $className = '';
    $raceName = '';
    if (!empty($ptc->class_id)) {
        $cls = Classe::findById($ptc->class_id);
        $className = $cls ? $cls->name : '';
    }
    if (!empty($ptc->race_id)) {
        $rc = Race::findById($ptc->race_id);
        $raceName = $rc ? $rc->name : '';
    }
    $step = (int)($ptc->step ?? 1);
    if ($step <= 1 || empty($ptc->class_id)) {
        $resumeUrl = 'cc01_class_selection.php?type=npc';
    } elseif ($step === 2) {
        $resumeUrl = 'cc02_race_selection.php?pt_id=' . $ptc->id . '&type=npc';
    } elseif ($step === 3) {
        $resumeUrl = 'cc03_background_selection.php?pt_id=' . $ptc->id . '&type=npc';
    } else {
        $resumeUrl = 'cc04_characteristics.php?pt_id=' . $ptc->id . '&type=npc';
    }
    $ptNpcDraftCards[] = [
        'id' => $ptc->id,
        'name' => $ptc->name ?: 'Sans nom',
        'class_name' => $className,
        'race_name' => $raceName,
        'step' => max(1, $step),
        'profile_photo' => $ptc->profile_photo,
        'updated_at' => $ptc->updated_at ?: $ptc->created_at,
        'resume_url' => $resumeUrl
    ];
}

$template_vars['pt_npc_drafts'] = $ptNpcDraftCards;

// Inclure le template
include_once 'templates/manage_npcs_template.php';
?>
