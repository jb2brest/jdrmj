<?php
/**
 * Vue d'un lieu - Version refactorisée
 * Utilise les classes et API pour une meilleure séparation des responsabilités
 */

require_once 'includes/functions.php';
require_once 'classes/Access.php';
require_once 'classes/Lieu.php';

$page_title = "Scène de Jeu";
$current_page = "view_place";

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$place_id = (int)$_GET['id'];
$isModal = isset($_GET['modal']);

// Gestion des messages de succès depuis l'URL
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Charger le lieu et sa campagne avec hiérarchie géographique
$lieu = Lieu::findById($place_id);
if (!$lieu) {
    header('Location: index.php');
    exit();
}

$place = $lieu->toArray();

// Récupérer les campagnes associées à ce lieu
$campaigns = $lieu->getCampaigns();
if (!empty($campaigns)) {
    $campaign = $campaigns[0];
    $place['campaign_id'] = $campaign['id'];
    $place['campaign_title'] = $campaign['title'];
    $place['dm_id'] = $campaign['dm_id'];
    
    $dm_user = User::findById($campaign['dm_id']);
    $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
} else {
    $place['campaign_id'] = null;
    $place['campaign_title'] = null;
    $place['dm_id'] = null;
    $place['dm_username'] = null;
}

// Fonction utilitaire pour vérifier si campaign_id est défini
function hasCampaignId($place) {
    return isset($place['campaign_id']) && !empty($place['campaign_id']);
}

$dm_id = (int)$place['dm_id'];
$isOwnerDM = User::isDMOrAdmin() && ($dm_id === 0 || $_SESSION['user_id'] === $dm_id);

// Autoriser les admins, les DM propriétaires et les membres de la campagne à voir le lieu
$canView = User::isAdmin() || $isOwnerDM;
if (!$canView && isset($place['campaign_id']) && $place['campaign_id']) {
    $campaign = Campaign::findById($place['campaign_id']);
    $canView = $campaign ? $campaign->isMember($_SESSION['user_id']) : false;
} elseif (!$canView && (!isset($place['campaign_id']) || !$place['campaign_id'])) {
    $canView = true;
}

// Seuls les admins et les DM propriétaires peuvent éditer le lieu
$canEdit = User::isAdmin() || $isOwnerDM;

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Traitement des actions sur les accès (maintenant géré via API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $action = $_POST['action'] ?? '';
    $access_id = (int)($_POST['access_id'] ?? 0);

    switch ($action) {
        case 'create_access':
        case 'update_access':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $to_place_id = (int)($_POST['to_place_id'] ?? 0);
            $is_visible = isset($_POST['is_visible']) ? 1 : 0;
            $is_open = isset($_POST['is_open']) ? 1 : 0;
            $is_trapped = isset($_POST['is_trapped']) ? 1 : 0;
            $trap_description = sanitizeInput($_POST['trap_description'] ?? '');
            $trap_difficulty = (int)($_POST['trap_difficulty'] ?? 0);
            $trap_damage = sanitizeInput($_POST['trap_damage'] ?? '');
            $position_x = (int)($_POST['position_x'] ?? 0);
            $position_y = (int)($_POST['position_y'] ?? 0);
            $is_on_map = isset($_POST['is_on_map']) ? 1 : 0;

            if (empty($name) || $to_place_id === 0) {
                $_SESSION['error_message'] = "Le nom de l'accès et le lieu de destination sont requis.";
            } elseif (Access::existsBetween($place_id, $to_place_id, $name)) {
                $_SESSION['error_message'] = "Un accès avec ce nom existe déjà vers ce lieu de destination.";
            } else {
                $access = ($action === 'update_access' && $access_id) ? Access::findById($access_id) : new Access();
                if (!$access || ($action === 'update_access' && $access->from_place_id !== $place_id)) {
                    $_SESSION['error_message'] = "Accès introuvable ou vous n'avez pas la permission de le modifier.";
                    break;
                }

                $access->from_place_id = $place_id;
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
                    $_SESSION['success_message'] = "Accès " . ($action === 'create_access' ? "créé" : "mis à jour") . " avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la sauvegarde de l'accès.";
                }
            }
            break;

        case 'delete_access':
            $access = Access::findById($access_id);
            if ($access && $access->from_place_id === $place_id) {
                if ($access->delete()) {
                    $_SESSION['success_message'] = "Accès supprimé avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la suppression de l'accès.";
                }
            } else {
                $_SESSION['error_message'] = "Accès introuvable ou vous n'avez pas la permission de le supprimer.";
            }
            break;
    }
    
    header('Location: view_place.php?id=' . $place_id);
    exit();
}

// Mettre à jour le plan du lieu
if (isset($_POST['action']) && $_POST['action'] === 'update_map') {
    if (!$canEdit) {
        $_SESSION['error_message'] = "Vous n'avez pas les droits pour modifier ce lieu.";
    } else {
        $notes = trim($_POST['notes'] ?? '');
        
        // Upload de plan si fourni
        $newMapUrl = $place['map_url']; // Conserver l'ancien plan par défaut
        if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['plan_file']['tmp_name'];
            $size = (int)$_FILES['plan_file']['size'];
            $originalName = $_FILES['plan_file']['name'];
            
            // Vérifier la taille (limite à 10M pour correspondre à la config PHP)
            if ($size > 10 * 1024 * 1024) {
                $_SESSION['error_message'] = "Image trop volumineuse (max 10 Mo).";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmp);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                
                if (!isset($allowed[$mime])) {
                    $_SESSION['error_message'] = "Format d'image non supporté. Formats acceptés: JPG, PNG, GIF, WebP.";
                } else {
                    $ext = $allowed[$mime];
                    $subdir = 'uploads/plans/' . date('Y/m');
                    $diskDir = __DIR__ . '/' . $subdir;
                    
                    // Créer le dossier s'il n'existe pas
                    if (!is_dir($diskDir)) {
                        if (!mkdir($diskDir, 0755, true)) {
                            $_SESSION['error_message'] = "Impossible de créer le dossier d'upload.";
                        }
                    }
                    
                    if (!isset($_SESSION['error_message'])) {
                        $basename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $diskPath = $diskDir . '/' . $basename;
                        $webPath = $subdir . '/' . $basename;
                        
                        if (move_uploaded_file($tmp, $diskPath)) {
                            $newMapUrl = $webPath;
                        } else {
                            $_SESSION['error_message'] = "Échec de l'upload du plan. Vérifiez les permissions du dossier.";
                        }
                    }
                }
            }
        } elseif (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Gérer les erreurs d'upload
            switch ($_FILES['plan_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $_SESSION['error_message'] = "Le fichier est trop volumineux (max 2 Mo).";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $_SESSION['error_message'] = "Le fichier n'a été que partiellement uploadé.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $_SESSION['error_message'] = "Dossier temporaire manquant.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $_SESSION['error_message'] = "Impossible d'écrire le fichier sur le disque.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $_SESSION['error_message'] = "Une extension PHP a arrêté l'upload.";
                    break;
                default:
                    $_SESSION['error_message'] = "Erreur lors de l'upload du fichier.";
                    break;
            }
        }
        
        if (!isset($_SESSION['error_message'])) {
            $result = $lieu->updateMapUrl($newMapUrl, $notes);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                
                // Recharger les données du lieu
                $place = $lieu->toArray();
                $campaigns = $lieu->getCampaigns();
                if (!empty($campaigns)) {
                    $campaign = $campaigns[0];
                    $place['campaign_id'] = $campaign['id'];
                    $place['campaign_title'] = $campaign['title'];
                    $place['dm_id'] = $campaign['dm_id'];
                    
                    $dm_user = User::findById($campaign['dm_id']);
                    $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
                }
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
        }
    }
    
    header('Location: view_place.php?id=' . $place_id);
    exit();
}

// Récupérer tous les lieux pour le sélecteur "vers quel lieu"
$all_places = Lieu::getAllPlaces();
$other_places = array_filter($all_places, function($place) use ($place_id) {
    return $place['id'] != $place_id;
});

// Récupérer les pays et régions pour les sélecteurs
$countries = Pays::getAllCountries();
$regions = Region::getAllRegions();

// Récupérer le pays et la région du lieu actuel
$current_region = null;
$current_country = null;
if ($place['region_id']) {
    $current_region = Region::findById($place['region_id']);
    if ($current_region && $current_region->getCountryId()) {
        $current_country = Pays::findById($current_region->getCountryId());
    }
}

// Récupérer les données du lieu via les classes
$placeAccesses = Access::getFromPlace($place_id);
$placePlayers = $lieu->getAllPlayersDetailed();
$placeNpcs = $lieu->getAllNpcsDetailed();
$placeMonsters = $lieu->getAllMonsters();
$placeObjects = $lieu->getAllObjects();
$tokenPositions = $lieu->getTokenPositions();

// Récupérer les positions des objets depuis items (seulement les non attribués)
foreach ($placeObjects as $object) {
    $tokenKey = 'object_' . $object['id'];
    $tokenPositions[$tokenKey] = [
        'x' => (int)$object['position_x'],
        'y' => (int)$object['position_y'],
        'is_on_map' => (bool)$object['is_on_map']
    ];
}

// Récupérer les joueurs disponibles pour l'ajout
$availablePlayers = [];
if ($canEdit && hasCampaignId($place)) {
    $campaign = Campaign::findById($place['campaign_id']);
    if ($campaign) {
        $availablePlayers = $campaign->getMembers();
    }
}

// Récupérer les personnages du MJ pour l'ajout comme PNJ
$dmCharacters = [];
if ($isOwnerDM && hasCampaignId($place)) {
    $dmCharacters = Character::getByUserId($dm_id);
}

// Récupérer l'historique des lancers de dés
$diceRolls = [];
if (hasCampaignId($place)) {
    $diceRolls = DiceRoll::getByPlaceId($place_id);
}

// Récupérer le joueur actuel et son personnage
$currentPlayer = null;
if (hasCampaignId($place)) {
    foreach ($placePlayers as $player) {
        if ($player['player_id'] == $_SESSION['user_id']) {
            $currentPlayer = $player;
            break;
        }
    }
}

// Variables JavaScript pour l'API
$js_vars = [
    'placeId' => $place_id,
    'canEdit' => $canEdit,
    'isOwnerDM' => $isOwnerDM,
    'tokenPositions' => $tokenPositions,
    'campaignId' => $place['campaign_id']
];

// Variables pour le template
$template_vars = [
    'placePlayers' => $placePlayers,
    'placeNpcs' => $placeNpcs,
    'placeMonsters' => $placeMonsters,
    'placeAccesses' => $placeAccesses,
    'tokenPositions' => $tokenPositions
];

// Inclure le template HTML
include 'templates/view_place_template.php';
?>
