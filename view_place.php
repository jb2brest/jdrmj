<?php
/**
 * Vue d'un lieu - Version refactorisée
 * Utilise les classes et API pour une meilleure séparation des responsabilités
 */

require_once 'includes/functions.php';
require_once 'classes/init.php';
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

// Gestion des messages de confirmation d'ajout de monstre
if (isset($_GET['monster_added'])) {
    $success_message = 'Monstre ajouté avec succès au lieu.';
}

// Gestion des messages de confirmation de mise à jour du lieu
if (isset($_GET['updated'])) {
    $success_message = 'Lieu mis à jour avec succès.';
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

// Récupérer le world_id du lieu actuel pour la téléportation (avant le traitement POST)
$current_world_id_for_action = null;
if ($place['region_id']) {
    $temp_region = Region::findById($place['region_id']);
    if ($temp_region && $temp_region->getCountryId()) {
        $temp_country = Pays::findById($temp_region->getCountryId());
        if ($temp_country) {
            $current_world_id_for_action = $temp_country->getWorldId();
        }
    }
}

// Récupérer tous les accès (sortants et entrants) pour ce lieu (avant le traitement POST)
$placeAccesses = Access::getAllForPlace($place_id);

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
            
        case 'move_entities':
            $to_place_id = (int)($_POST['to_place_id'] ?? 0);
            $entities = $_POST['entities'] ?? [];
            
            if ($to_place_id === 0 || $to_place_id === $place_id) {
                $_SESSION['error_message'] = "Lieu de destination invalide.";
            } elseif (empty($entities)) {
                $_SESSION['error_message'] = "Aucune entité sélectionnée.";
            } else {
                // Vérifier que le lieu de destination a un accès direct
                // Construire la liste des lieux accessibles (même logique que dans le template)
                $accessiblePlaceIds = [];
                foreach ($placeAccesses as $access) {
                    $from_id = (int)$access->from_place_id;
                    $to_id = (int)$access->to_place_id;
                    if ($from_id === $place_id) {
                        // Accès sortant
                        $accessiblePlaceIds[] = $to_id;
                    } elseif ($to_id === $place_id) {
                        // Accès entrant
                        $accessiblePlaceIds[] = $from_id;
                    }
                }
                
                if (!in_array($to_place_id, $accessiblePlaceIds, true)) {
                    error_log("Déplacement refusé: place_id=$place_id, to_place_id=$to_place_id, accessiblePlaceIds=" . implode(',', $accessiblePlaceIds));
                    $_SESSION['error_message'] = "Le lieu de destination n'a pas d'accès direct avec ce lieu.";
                } else {
                    $successCount = 0;
                    $errorCount = 0;
                    $errors = [];
                    
                    foreach ($entities as $entityStr) {
                        $parts = explode('_', $entityStr, 2);
                        if (count($parts) !== 2) {
                            $errorCount++;
                            continue;
                        }
                        
                        $entityType = $parts[0];
                        $entityId = (int)$parts[1];
                        
                        if (!in_array($entityType, ['player', 'npc', 'monster']) || $entityId === 0) {
                            $errorCount++;
                            continue;
                        }
                        
                        $result = Lieu::transferEntity($entityType, $place_id, $to_place_id, $entityId);
                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            $errors[] = $result['message'];
                        }
                    }
                    
                    if ($successCount > 0) {
                        $message = "$successCount entité(s) déplacée(s) avec succès.";
                        if ($errorCount > 0) {
                            $message .= " $errorCount erreur(s) lors du déplacement.";
                        }
                        $_SESSION['success_message'] = $message;
                    } else {
                        $_SESSION['error_message'] = "Erreur lors du déplacement des entités. " . implode(' ', $errors);
                    }
                }
            }
            break;
            
        case 'teleport_entities':
            $to_place_id = (int)($_POST['to_place_id'] ?? 0);
            $entities = $_POST['entities'] ?? [];
            
            if ($to_place_id === 0 || $to_place_id === $place_id) {
                $_SESSION['error_message'] = "Lieu de destination invalide.";
            } elseif (empty($entities)) {
                $_SESSION['error_message'] = "Aucune entité sélectionnée.";
            } elseif (!$current_world_id_for_action) {
                $_SESSION['error_message'] = "Impossible de déterminer le monde du lieu actuel.";
            } else {
                // Vérifier que le lieu de destination est dans le même monde
                try {
                    $pdo = getPDO();
                    $stmt = $pdo->prepare("
                        SELECT p.id 
                        FROM places p
                        LEFT JOIN countries c ON p.country_id = c.id
                        WHERE p.id = ? AND c.world_id = ?
                    ");
                    $stmt->execute([$to_place_id, $current_world_id_for_action]);
                    $destinationPlace = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$destinationPlace) {
                        $_SESSION['error_message'] = "Le lieu de destination doit être dans le même monde que le lieu actuel.";
                    } else {
                        $successCount = 0;
                        $errorCount = 0;
                        $errors = [];
                        
                        foreach ($entities as $entityStr) {
                            $parts = explode('_', $entityStr, 2);
                            if (count($parts) !== 2) {
                                $errorCount++;
                                continue;
                            }
                            
                            $entityType = $parts[0];
                            $entityId = (int)$parts[1];
                            
                            if (!in_array($entityType, ['player', 'npc', 'monster']) || $entityId === 0) {
                                $errorCount++;
                                continue;
                            }
                            
                            $result = Lieu::transferEntity($entityType, $place_id, $to_place_id, $entityId);
                            if ($result['success']) {
                                $successCount++;
                            } else {
                                $errorCount++;
                                $errors[] = $result['message'];
                            }
                        }
                        
                        if ($successCount > 0) {
                            $message = "$successCount entité(s) téléportée(s) avec succès.";
                            if ($errorCount > 0) {
                                $message .= " $errorCount erreur(s) lors de la téléportation.";
                            }
                            $_SESSION['success_message'] = $message;
                        } else {
                            $_SESSION['error_message'] = "Erreur lors de la téléportation des entités. " . implode(' ', $errors);
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erreur lors de la vérification du lieu de destination: " . $e->getMessage());
                    $_SESSION['error_message'] = "Erreur lors de la vérification du lieu de destination.";
                }
            }
            break;
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
$current_world_id = null;
if ($place['region_id']) {
    $current_region = Region::findById($place['region_id']);
    if ($current_region && $current_region->getCountryId()) {
        $current_country = Pays::findById($current_region->getCountryId());
        if ($current_country) {
            $current_world_id = $current_country->getWorldId();
        }
    }
}

// Récupérer tous les lieux du monde du lieu actuel pour la téléportation
$worldPlaces = [];
if ($current_world_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, c.name as country_name, r.name as region_name
            FROM places p
            LEFT JOIN countries c ON p.country_id = c.id
            LEFT JOIN regions r ON p.region_id = r.id
            WHERE c.world_id = ? AND p.id != ?
            ORDER BY c.name, r.name, p.title
        ");
        $stmt->execute([$current_world_id, $place_id]);
        $worldPlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des lieux du monde: " . $e->getMessage());
        $worldPlaces = [];
    }
}

// Récupérer les données du lieu via les classes
$placePlayers = $lieu->getAllPlayersDetailed();
$placeNpcs = $lieu->getAllNpcsDetailed();
$placeMonsters = $lieu->getAllMonsters();
$placeObjects = $lieu->getUnassignedObjects();
// Récupérer uniquement les objets visibles pour l'affichage sur la carte
$visibleObjectsForMap = $lieu->getVisibleObjects();
$tokenPositions = $lieu->getTokenPositions();

// Récupérer les positions des objets depuis place_tokens (système unifié)
foreach ($visibleObjectsForMap as $object) {
    $tokenKey = 'object_' . $object['id'];
    // Les positions des objets sont maintenant gérées par place_tokens
    // Elles sont déjà incluses dans $tokenPositions via $lieu->getTokenPositions()
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

// Récupérer toutes les campagnes accessibles pour les lancers de dés
$userRole = User::getCurrentUserRole();
$accessibleCampaigns = Campaign::getAccessibleCampaigns($_SESSION['user_id'], $userRole);

// Déterminer la campagne par défaut : campagne du lieu si elle existe, sinon la dernière campagne
$defaultCampaignId = null;
if (hasCampaignId($place) && $place['campaign_id']) {
    $defaultCampaignId = $place['campaign_id'];
} elseif (!empty($accessibleCampaigns)) {
    // Prendre la dernière campagne (la plus récente)
    $defaultCampaignId = $accessibleCampaigns[0]['id'];
}

// Récupérer l'historique des lancers de dés
$diceRolls = [];
if ($defaultCampaignId) {
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
    'campaignId' => $defaultCampaignId,
    'defaultCampaignId' => $defaultCampaignId
];

// Variables pour le template
$template_vars = [
    'placePlayers' => $placePlayers,
    'placeNpcs' => $placeNpcs,
    'placeMonsters' => $placeMonsters,
    'placeAccesses' => $placeAccesses,
    'tokenPositions' => $tokenPositions,
    'visibleObjectsForMap' => $visibleObjectsForMap,  // Objets visibles pour les pions sur la carte
    'worldPlaces' => $worldPlaces,  // Lieux du monde pour la téléportation
    'accessibleCampaigns' => $accessibleCampaigns,  // Campagnes accessibles pour les lancers de dés
    'defaultCampaignId' => $defaultCampaignId  // Campagne par défaut
];

// Inclure le template HTML
include_once 'templates/view_place_template.php';
?>
