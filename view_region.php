<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'classes/Access.php';

$page_title = "Détails de la Région";
$current_page = "manage_worlds"; // Pour garder le bouton "Mondes" actif dans la navbar

requireLogin();
User::requireDMOrAdmin();

require_once 'classes/Location.php';

$region_id = (int)($_GET['id'] ?? 0);
if ($region_id === 0) {
    header('Location: manage_worlds.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer la région via la classe Region
$region = Region::findById($region_id);

if (!$region) {
    header('Location: manage_worlds.php?error=region_not_found');
    exit();
}

// Vérifier que l'utilisateur a le droit d'accéder à cette région
$monde = $region->getMonde(); // Returns associative array
if (!$monde || $monde['created_by'] != $user_id) { // Access as array
    header('Location: manage_worlds.php?error=access_denied');
    exit();
}

$success_message = '';
$error_message = '';

// Fonction helper pour l'upload d'image de pièce
function uploadPlaceImage($file) {
    // Vérifier qu'un fichier a été uploadé
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Aucun fichier uploadé ou erreur d\'upload'];
    }

    // Vérifier la taille du fichier (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
    }

    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP'];
    }

    // Créer le dossier d'upload s'il n'existe pas
    $uploadDir = 'uploads/places/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'place_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement du fichier'];
    }

    return ['success' => true, 'file_path' => $filePath];
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_location':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom du lieu est requis.";
            } else {
                try {
                    $location = Location::create($name, $region_id, $description);
                    if ($location) {
                        $success_message = "Lieu '$name' créé avec succès.";
                    } else {
                        $error_message = "Erreur lors de la création du lieu.";
                    }
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
            }
            break;

        case 'create_place':
            $title = sanitizeInput($_POST['title'] ?? '');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $location_id = (int)($_POST['location_id'] ?? 0);
            $map_url = '';
            
            if (empty($title)) {
                $error_message = "Le nom de la pièce est requis.";
            } else {
                // Gérer l'upload de la carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadPlaceImage($_FILES['map_image']);
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $lieu = new Room(null, [
                            'title' => $title,
                            'notes' => $notes,
                            'map_url' => $map_url,
                            'region_id' => $region_id,
                            'location_id' => !empty($location_id) ? $location_id : null,
                            'country_id' => $region->getCountryId()
                        ]);
                        $lieu->save();
                        $success_message = "Pièce '$title' créé avec succès.";
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                }
            }
            break;
            
        case 'update_place':
            $place_id = (int)($_POST['place_id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $location_id = (int)($_POST['location_id'] ?? 0); // Nouveau champ
            
            if (empty($title)) {
                $error_message = "Le nom de la pièce est requis.";
            } else {
                // Récupérer la pièce via la classe Pièce
                $lieu = Room::findById($place_id);
                if (!$lieu || $lieu->getRegionId() != $region_id) {
                    $error_message = "Pièce non trouvé.";
                } else {
                    $map_url = $lieu->getMapUrl() ?? '';
                
                    // Gérer l'upload de la nouvelle carte si un fichier est fourni
                    if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = uploadPlaceImage($_FILES['map_image']);
                        if (!$uploadResult['success']) {
                            $error_message = $uploadResult['error'];
                        } else {
                            // Supprimer l'ancienne carte si elle existe
                            if (!empty($map_url) && file_exists($map_url)) {
                                unlink($map_url);
                            }
                            $map_url = $uploadResult['file_path'];
                        }
                    }
                    
                    if (empty($error_message)) {
                        try {
                            $lieu->setTitle($title);
                            $lieu->setNotes($notes);
                            $lieu->setMapUrl($map_url);
                            $lieu->location_id = $location_id ? $location_id : null; // Mise à jour location
                            $lieu->save();
                            $success_message = "Pièce '$title' mis à jour avec succès.";
                        } catch (Exception $e) {
                            $error_message = $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'delete_place':
            $place_id = (int)($_POST['place_id'] ?? 0);
            
            try {
                $lieu = Room::findById($place_id);
                if ($lieu && $lieu->getRegionId() == $region_id) {
                    $lieu->delete();
                    $success_message = "Pièce supprimé avec succès.";
                } else {
                    $error_message = "Pièce non trouvé.";
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            break;
    }
}

// Récupérer les lieux (Locations) de la région
$locations = $region->getLocations();

// Récupérer les pièces de la région via la classe Region
// Note: getPlaces() a été mis à jour pour trier par location_id
$places = $region->getPlaces();

// Organiser les pièces par Location ID
// Key 0 will hold orphaned rooms (location_id IS NULL or 0)
$placesByLocation = [];
$placesByLocation[0] = []; 
foreach ($locations as $loc) {
    // Note: $loc is an object of class Location
    $placesByLocation[$loc->getId()] = [];
}

foreach ($places as $place) {
    $locId = $place['location_id'] ? (int)$place['location_id'] : 0;
    if (!isset($placesByLocation[$locId])) {
        // Fallback for foreign keys that might point to deleted locations, though DB constraints should prevent this
        $locId = 0; 
    }
    $placesByLocation[$locId][] = $place;
}

// Récupérer les accès entre les pièces de la région (y compris vers d'autres régions)
$region_accesses = [];
$external_places = [];
if (!empty($places)) {
    $place_ids = array_column($places, 'id');
    $place_ids_str = implode(',', $place_ids);
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT a.*, 
               fp.title as from_place_name, 
               fp.region_id as from_region_id,
               tp.title as to_place_name,
               tp.region_id as to_region_id,
               tr.name as to_region_name
        FROM accesses a
        JOIN places fp ON a.from_place_id = fp.id
        JOIN places tp ON a.to_place_id = tp.id
        LEFT JOIN regions tr ON tp.region_id = tr.id
        WHERE a.from_place_id IN ($place_ids_str) 
           OR a.to_place_id IN ($place_ids_str)
        ORDER BY a.from_place_id, a.name
    ");
    $stmt->execute();
    $region_accesses = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    // Récupérer les pièces externes (d'autres régions) connectés à cette région
    $external_place_ids = [];
    foreach ($region_accesses as $access) {
        if ($access->from_place_id && !in_array($access->from_place_id, $place_ids)) {
            $external_place_ids[] = $access->from_place_id;
        }
        if ($access->to_place_id && !in_array($access->to_place_id, $place_ids)) {
            $external_place_ids[] = $access->to_place_id;
        }
    }
    
    if (!empty($external_place_ids)) {
        $external_place_ids_str = implode(',', array_unique($external_place_ids));
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.region_id, r.name as region_name
            FROM places p
            LEFT JOIN regions r ON p.region_id = r.id
            WHERE p.id IN ($external_place_ids_str)
        ");
        $stmt->execute();
        $external_places = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

// Récupérer tous les PNJs et monstres de la région via la classe Region
$region_npcs = $region->getNpcs();
$region_monsters = $region->getMonsters();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($region->getName()); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .cursor-pointer {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .cursor-pointer:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .modal-fullscreen .modal-body {
            padding: 0;
        }
        
        /* Styles pour les listes d'entités */
        .sortable {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease;
        }
        
        .sortable:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .entity-row {
            transition: background-color 0.2s ease;
        }
        
        .entity-row:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .badge {
            font-size: 0.8em;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .form-select, .form-control {
            border-radius: 6px;
        }
        
        .btn-group-sm .btn {
            border-radius: 4px;
        }
        
        /* Animation pour les filtres */
        .entity-row[style*="display: none"] {
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        
        .entity-row:not([style*="display: none"]) {
            transition: opacity 0.3s ease;
            opacity: 1;
        }

        /* Drag and Drop Styles */
        .draggable-place {
            cursor: move;
        }
        
        .droppable-area {
            transition: all 0.2s ease;
        }
        
        .droppable-area.drag-over {
            background-color: rgba(40, 167, 69, 0.1) !important;
            border: 2px dashed #28a745 !important;
            border-radius: 8px;
        }

        .droppable-orphan.drag-over {
            background-color: rgba(255, 193, 7, 0.2) !important;
            border: 2px dashed #ffc107 !important;
        }
    </style>
    <script>
        // Global state
        let draggedPlaceId = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Attach dragstart event to all draggable places
            const draggables = document.querySelectorAll('.draggable-place');
            console.log("Found " + draggables.length + " draggable elements.");
            
            draggables.forEach(elem => {
                elem.addEventListener('dragstart', function(ev) {
                    var placeId = this.getAttribute('data-place-id');
                    console.log("Event: dragstart, PlaceID:", placeId);
                    
                    if (!placeId) {
                        console.error("Error: No data-place-id found on element", this);
                        return;
                    }
                    
                    draggedPlaceId = placeId;
                    
                    // Set dataTransfer
                    ev.dataTransfer.effectAllowed = "move";
                    ev.dataTransfer.setData("text/plain", placeId);
                    // Required for Firefox
                    ev.dataTransfer.dropEffect = "move";
                });
                
                // Optional: visual feedback
                elem.addEventListener('dragend', function(ev) {
                   console.log("Event: dragend");
                   draggedPlaceId = null; 
                });
            });
        });

        function allowDrop(ev) {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = "move"; // Visual feedback
            const locationCard = ev.target.closest('.card-body');
            if (locationCard) {
                locationCard.classList.add('drag-over');
            }
        }

        function dragLeave(ev) {
            const locationCard = ev.target.closest('.card-body');
            if (locationCard) {
                locationCard.classList.remove('drag-over');
            }
        }

        function drop(ev, locationId) {
            ev.preventDefault();
            console.log("Event: drop, LocationID:", locationId);
            
            const locationCard = ev.target.closest('.card-body');
            if (locationCard) {
                locationCard.classList.remove('drag-over');
            }
            
            // Try getting data from dataTransfer
            var placeId = ev.dataTransfer.getData("text/plain");
            console.log("DataTransfer ID:", placeId);
            
            // Fallback to global
            if (!placeId && draggedPlaceId) {
                placeId = draggedPlaceId;
                console.log("Fallback global ID:", placeId);
            }

            if (!placeId) {
                console.error("CRITICAL: Failed to retrieve place ID.");
                alert("Erreur: Impossible de récupérer l'ID de la pièce. (Debug: ID is empty)");
                return;
            }

            // Call API
            movePlaceToLocation(placeId, locationId);
            
            // Allow immediate next move logic if needed (page reload handles it anyway)
            draggedPlaceId = null;
        }

        function movePlaceToLocation(placeId, locationId) {
            console.log("Sending API Request:", {place_id: placeId, location_id: locationId});
            
            const formData = new FormData();
            formData.append('place_id', placeId);
            formData.append('location_id', locationId);

            fetch('api/update_place_location.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Server API returned non-JSON:", text);
                        throw new Error("Réponse serveur invalide (Non-JSON).");
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    console.log("Success:", data);
                    // Force a GET reload to avoid resubmitting forms if the user had just POSTed
                    window.location.href = window.location.pathname + window.location.search;
                } else {
                    console.error("API Error:", data.error);
                    alert('Erreur: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Une erreur est survenue lors du déplacement: ' + error.message);
            });
        }
    </script>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

<div class="container mt-4">
    <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
    <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>

    <!-- En-tête de la région -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="mb-2">
                                <i class="fas fa-map-marked-alt me-2"></i>
                                <?php echo htmlspecialchars($region->getName()); ?>
                            </h1>
                            <p class="text-muted mb-1">
                                <i class="fas fa-flag me-1"></i>
                                Pays: <?php echo htmlspecialchars($region->getCountryName()); ?>
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-globe-americas me-1"></i>
                                Monde: <?php echo htmlspecialchars($region->getWorldName()); ?>
                            </p>
                            <?php if (!empty($region->getDescription())): ?>
                                <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($region->getDescription())); ?></p>
                            <?php endif; ?>
                            <div class="d-flex gap-3">
                                <a href="view_country.php?id=<?php echo (int)$region->getCountryId(); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour au pays
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($region->getMapUrl())): ?>
                            <div class="text-end">
                                <img src="<?php echo htmlspecialchars($region->getMapUrl()); ?>" 
                                     alt="Carte de <?php echo htmlspecialchars($region->getName()); ?>" 
                                     class="img-fluid rounded cursor-pointer" 
                                     style="max-height: 200px; max-width: 300px;"
                                     onclick="openMapFullscreen('<?php echo htmlspecialchars($region->getMapUrl()); ?>', '<?php echo htmlspecialchars($region->getName()); ?>')"
                                     title="Cliquer pour voir en plein écran">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pièces de la région (Groupées par Lieu) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-map-pin me-2"></i>Lieux et Pièces</h5>
            <div class="btn-group">
                <?php if (!empty($places) && (!empty($region_accesses) || !empty($external_places))): ?>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#cartographyModal">
                        <i class="fas fa-project-diagram me-2"></i>Cartographie
                    </button>
                <?php endif; ?>
                <!-- Bouton Créer Lieu -->
                <button class="btn btn-secondary me-1" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                    <i class="fas fa-map-signs me-2"></i>Nouveau Lieu
                </button>
                <!-- Bouton Créer Pièce -->
                <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createPlaceModal">
                    <i class="fas fa-plus me-2"></i>Nouvelle Pièce
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($places) && empty($locations)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Aucun lieu ni pièce n'a encore été créé dans cette région.
                </div>
            <?php else: ?>
                
                <!-- 1. Affichage des Lieux (Locations) -->
                <?php if (!empty($locations)): ?>
                    <h5 class="mb-3 border-bottom pb-2">Lieux</h5>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
                        <?php foreach ($locations as $location): ?>
                            <?php 
                                $locId = $location->getId(); // Variables used by template
                                // Get rooms for this location to display count
                                $locRooms = isset($placesByLocation[$locId]) ? $placesByLocation[$locId] : [];
                                include 'templates/location_card.php'; 
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- 2. Affichage des Pièces Orphelines (Hors Lieu) -->
                <?php if (!empty($placesByLocation[0])): ?>
                    <div class="card mb-3 border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h6 class="mb-0 text-dark"><i class="fas fa-question-circle me-2"></i>Pièces hors lieux (Région directe)</h6>
                        </div>
                        <div class="card-body p-3 droppable-area droppable-orphan"
                             ondrop="drop(event, 0)" 
                             ondragover="allowDrop(event)"
                             ondragleave="dragLeave(event)">
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                <?php foreach ($placesByLocation[0] as $place): ?>
                                    <?php include 'templates/place_card.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Section PNJs et Monstres -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>PNJs et Monstres de la Région</h5>
                </div>
                <div class="card-body">
                    <!-- Filtres et contrôles -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="typeFilter" class="form-label">Filtrer par type :</label>
                            <select class="form-select" id="typeFilter">
                                <option value="">Tous</option>
                                <option value="PNJ">PNJ</option>
                                <option value="Monstre">Monstre</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="locationFilter" class="form-label">Filtrer par pièce :</label>
                            <select class="form-select" id="locationFilter">
                                <option value="">Tous les pièces</option>
                                <?php
                                $unique_places = [];
                                foreach (array_merge($region_npcs, $region_monsters) as $entity) {
                                    $place_key = $entity['place_name'];
                                    if (!in_array($place_key, $unique_places)) {
                                        $unique_places[] = $place_key;
                                        echo '<option value="' . htmlspecialchars($place_key) . '">' . htmlspecialchars($place_key) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="searchFilter" class="form-label">Rechercher :</label>
                            <input type="text" class="form-control" id="searchFilter" placeholder="Nom, classe, race...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-outline-secondary" id="clearFilters">
                                <i class="fas fa-times me-1"></i>Effacer les filtres
                            </button>
                        </div>
                    </div>

                    <!-- Tableau des entités -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="entitiesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="sortable" data-column="type">
                                        Type <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="name">
                                        Nom <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="class">
                                        Classe <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="race">
                                        Race <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="location">
                                        Pièce <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_entities = array_merge($region_npcs, $region_monsters);
                                if (empty($all_entities)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Aucun PNJ ou monstre trouvé</h5>
                                            <p class="text-muted">Les PNJs et monstres apparaîtront ici une fois qu'ils seront ajoutés aux pièces de cette région.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_entities as $entity): ?>
                                        <tr class="entity-row" 
                                            data-type="<?php echo htmlspecialchars($entity['type']); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($entity['name'])); ?>"
                                            data-class="<?php echo htmlspecialchars(strtolower($entity['class_name'] ?? $entity['type'] ?? '')); ?>"
                                            data-race="<?php echo htmlspecialchars(strtolower($entity['race_name'] ?? $entity['size'] ?? '')); ?>"
                                            data-location="<?php echo htmlspecialchars(strtolower($entity['place_name'])); ?>"
                                            data-place-key="<?php echo htmlspecialchars($entity['place_name']); ?>">
                                            <td>
                                                <span class="badge <?php echo $entity['type'] === 'PNJ' ? 'bg-primary' : 'bg-danger'; ?>">
                                                    <i class="fas <?php echo $entity['type'] === 'PNJ' ? 'fa-user' : 'fa-dragon'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($entity['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($entity['profile_photo']) || !empty($entity['character_profile_photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($entity['profile_photo'] ?: $entity['character_profile_photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($entity['name']); ?>" 
                                                             class="rounded-circle me-2" 
                                                             style="width: 32px; height: 32px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="fas <?php echo $entity['type'] === 'PNJ' ? 'fa-user' : 'fa-dragon'; ?> text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($entity['name']); ?></strong>
                                                        <?php if ($entity['type'] === 'Monstre' && !empty($entity['monster_name']) && $entity['monster_name'] !== $entity['name']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($entity['monster_name']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($entity['type'] === 'PNJ'): ?>
                                                    <?php echo htmlspecialchars($entity['class_name'] ?? 'N/A'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo htmlspecialchars($entity['type'] ?? 'N/A'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($entity['type'] === 'PNJ'): ?>
                                                    <?php echo htmlspecialchars($entity['race_name'] ?? 'N/A'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo htmlspecialchars($entity['size'] ?? 'N/A'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($entity['place_name']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($entity['type'] === 'PNJ'): ?>
                                                        <a href="view_character.php?id=<?php echo (int)$entity['id']; ?>" 
                                                           class="btn btn-outline-info btn-sm" title="Voir le PNJ">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="view_monster.php?id=<?php echo (int)$entity['monster_id']; ?>" 
                                                           class="btn btn-outline-info btn-sm" title="Voir le monstre">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création Lieu -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-signs me-2"></i>Nouveau Lieu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_location">
                    <div class="mb-3">
                        <label class="form-label">Nom du Lieu *</label>
                        <input type="text" class="form-control" name="name" required placeholder="ex: Cité de Port-Réal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-secondary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Création Pièce -->
<div class="modal fade" id="createPlaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer un nouvelle pièce</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_place">
                    
                    <div class="mb-3">
                        <label for="createPlaceTitle" class="form-label">Nom de la pièce *</label>
                        <input type="text" class="form-control" id="createPlaceTitle" name="title" required placeholder="Ex: Taverne du Dragon, Château de la Montagne...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="createPlaceLocation" class="form-label">Lieu (Optionnel)</label>
                        <select class="form-select" id="createPlaceLocation" name="location_id">
                            <option value="">-- Aucun (Pièce orpheline) --</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc->getId(); ?>"><?php echo htmlspecialchars($loc->getName()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="createPlaceNotes" class="form-label">Description</label>
                        <textarea class="form-control" id="createPlaceNotes" name="notes" rows="3" placeholder="Décrivez cette pièce..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="createPlaceMap" class="form-label">Carte de la pièce</label>
                        <input type="file" class="form-control" id="createPlaceMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        <div id="createPlaceMapPreview" class="mt-2" style="display: none;">
                            <img id="createPlaceMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-brown">Créer la pièce</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Pièce -->
<div class="modal fade" id="editPlaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier la pièce</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_place">
                    <input type="hidden" name="place_id" id="editPlaceId">
                    
                    <div class="mb-3">
                        <label for="editPlaceTitle" class="form-label">Nom de la pièce *</label>
                        <input type="text" class="form-control" id="editPlaceTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPlaceLocation" class="form-label">Lieu (Optionnel)</label>
                        <select class="form-select" id="editPlaceLocation" name="location_id">
                            <option value="">-- Aucun (Pièce orpheline) --</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc->getId(); ?>"><?php echo htmlspecialchars($loc->getName()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editPlaceNotes" class="form-label">Description</label>
                        <textarea class="form-control" id="editPlaceNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPlaceMap" class="form-label">Carte de la pièce</label>
                        <input type="file" class="form-control" id="editPlaceMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        <div id="editPlaceMapPreview" class="mt-2" style="display: none;">
                            <img id="editPlaceMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                        <div id="editPlaceCurrentMap" class="mt-2" style="display: none;">
                            <label class="form-label">Carte actuelle:</label>
                            <img id="editPlaceCurrentMapImg" src="" alt="Carte actuelle" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-brown">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Gestion de l'aperçu d'image pour la création de pièce
    document.getElementById('createPlaceMap').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('createPlaceMapPreviewImg').src = e.target.result;
                document.getElementById('createPlaceMapPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('createPlaceMapPreview').style.display = 'none';
        }
    });

    // Gestion de l'aperçu d'image pour l'édition de pièce
    document.getElementById('editPlaceMap').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editPlaceMapPreviewImg').src = e.target.result;
                document.getElementById('editPlaceMapPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('editPlaceMapPreview').style.display = 'none';
        }
    });

    function editPlace(place) {
        document.getElementById('editPlaceId').value = place.id;
        document.getElementById('editPlaceTitle').value = place.title;
        document.getElementById('editPlaceNotes').value = place.notes || '';
        document.getElementById('editPlaceLocation').value = place.location_id || ''; // Pre-select location
        
        // Afficher l'image actuelle si elle existe
        if (place.map_url) {
            document.getElementById('editPlaceCurrentMapImg').src = place.map_url;
            document.getElementById('editPlaceCurrentMap').style.display = 'block';
        } else {
            document.getElementById('editPlaceCurrentMap').style.display = 'none';
        }
        
        // Réinitialiser l'aperçu de nouvelle image
        document.getElementById('editPlaceMap').value = '';
        document.getElementById('editPlaceMapPreview').style.display = 'none';
        
        var editModal = new bootstrap.Modal(document.getElementById('editPlaceModal'));
        editModal.show();
    }
    
    // Fonction pour ouvrir la carte en plein écran
    function openMapFullscreen(mapUrl, placeName) {
        document.getElementById('fullscreenMapImg').src = mapUrl;
        document.getElementById('fullscreenMapTitle').textContent = 'Carte de ' + placeName;
        
        var fullscreenModal = new bootstrap.Modal(document.getElementById('fullscreenMapModal'));
        fullscreenModal.show();
    }

    // Gestion du tri et filtrage des entités
    let currentSort = { column: null, direction: 'asc' };
    let allRows = [];

    // Initialiser les données au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('entitiesTable');
        if (table) {
            allRows = Array.from(table.querySelectorAll('tbody tr.entity-row'));
            setupSorting();
            setupFiltering();
        }
    });

    function setupSorting() {
        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.dataset.column;
                const icon = this.querySelector('i');
                
                // Déterminer la direction du tri
                if (currentSort.column === column) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.direction = 'asc';
                }
                currentSort.column = column;
                
                // Mettre à jour les icônes
                sortableHeaders.forEach(h => {
                    const i = h.querySelector('i');
                    i.className = 'fas fa-sort ms-1';
                });
                icon.className = currentSort.direction === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
                
                // Trier les lignes
                sortRows();
            });
        });
    }

    function setupFiltering() {
        const typeFilter = document.getElementById('typeFilter');
        const locationFilter = document.getElementById('locationFilter');
        const searchFilter = document.getElementById('searchFilter');
        const clearFilters = document.getElementById('clearFilters');

        [typeFilter, locationFilter, searchFilter].forEach(filter => {
            filter.addEventListener('change', filterRows);
            filter.addEventListener('input', filterRows);
        });

        clearFilters.addEventListener('click', function() {
            typeFilter.value = '';
            locationFilter.value = '';
            searchFilter.value = '';
            filterRows();
        });
    }

    function sortRows() {
        const tbody = document.querySelector('#entitiesTable tbody');
        const visibleRows = Array.from(tbody.querySelectorAll('tr.entity-row:not([style*="display: none"])'));
        
        visibleRows.sort((a, b) => {
            let aValue, bValue;
            
            switch (currentSort.column) {
                case 'type':
                    aValue = a.dataset.type;
                    bValue = b.dataset.type;
                    break;
                case 'name':
                    aValue = a.dataset.name;
                    bValue = b.dataset.name;
                    break;
                case 'class':
                    aValue = a.dataset.class;
                    bValue = b.dataset.class;
                    break;
                case 'race':
                    aValue = a.dataset.race;
                    bValue = b.dataset.race;
                    break;
                case 'location':
                    aValue = a.dataset.location;
                    bValue = b.dataset.location;
                    break;
                default:
                    return 0;
            }
            
            if (aValue < bValue) return currentSort.direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });
        
        // Réorganiser les lignes dans le DOM
        visibleRows.forEach(row => tbody.appendChild(row));
    }

    function filterRows() {
        const typeFilter = document.getElementById('typeFilter').value;
        const locationFilter = document.getElementById('locationFilter').value;
        const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
        
        allRows.forEach(row => {
            let show = true;
            
            // Filtre par type
            if (typeFilter && row.dataset.type !== typeFilter) {
                show = false;
            }
            
            // Filtre par pièce
            if (locationFilter && row.dataset.placeKey !== locationFilter) {
                show = false;
            }
            
            // Filtre par recherche
            if (searchFilter) {
                const searchText = (
                    row.dataset.name + ' ' +
                    row.dataset.class + ' ' +
                    row.dataset.race + ' ' +
                    row.dataset.location
                ).toLowerCase();
                
                if (!searchText.includes(searchFilter)) {
                    show = false;
                }
            }
            
            row.style.display = show ? '' : 'none';
        });
        
        // Appliquer le tri aux lignes visibles
        if (currentSort.column) {
            sortRows();
        }
    }
</script>

<!-- Modal Plein Écran pour la Carte -->
<div class="modal fade" id="fullscreenMapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="fullscreenMapTitle">
                    <i class="fas fa-map me-2"></i>Carte du Pièce
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center">
                <img id="fullscreenMapImg" 
                     src="" 
                     alt="Carte en plein écran" 
                     class="img-fluid" 
                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cartographie -->
<div class="modal fade" id="cartographyModal" tabindex="-1" aria-labelledby="cartographyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cartographyModalLabel">
                    <i class="fas fa-project-diagram me-2"></i>Cartographie de la Région
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="cartographyCanvas" style="height: 600px; border: 2px solid #dee2e6; border-radius: 8px; position: relative; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <!-- La carte sera générée ici par JavaScript -->
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Légende</h6>
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-hand-paper me-1"></i>
                            <strong>Astuce :</strong> Cliquez et glissez les pièces pour les déplacer et éviter les superpositions.
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 20px; background: #007bff; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
                                <small>Pièce de cette région</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 20px; background: #6c757d; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
                                <small>Pièce d'une autre région</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 2px; background: #28a745;"></div>
                                <small>Accès ouvert</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 2px; background: #ffc107;"></div>
                                <small>Accès fermé</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 2px; background: #dc3545;"></div>
                                <small>Accès piégé</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2" style="width: 20px; height: 2px; background: #6c757d; border-style: dashed;"></div>
                                <small>Accès caché</small>
                            </div>
                        </div>
                        
                        <h6><i class="fas fa-list me-2"></i>Accès de la région</h6>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($region_accesses)): ?>
                                <p class="text-muted small">Aucun accès configuré entre les pièces de cette région.</p>
                            <?php else: ?>
                                <?php foreach ($region_accesses as $access): ?>
                                    <div class="card mb-2">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong class="small"><?= htmlspecialchars($access->name) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($access->from_place_name) ?> 
                                                        <?php if ($access->from_region_id != $region_id): ?>
                                                            <span class="badge bg-secondary small">Autre région</span>
                                                        <?php endif; ?>
                                                        → 
                                                        <?= htmlspecialchars($access->to_place_name) ?>
                                                        <?php if ($access->to_region_id && $access->to_region_id != $region_id): ?>
                                                            <span class="badge bg-secondary small"><?= htmlspecialchars($access->to_region_name) ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($access->is_visible): ?>
                                                        <span class="badge bg-success small">Visible</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary small">Caché</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <?php if ($access->is_open): ?>
                                                        <span class="badge bg-success small">Ouvert</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning small">Fermé</span>
                                                    <?php endif; ?>
                                                    <?php if ($access->is_trapped): ?>
                                                        <span class="badge bg-danger small">Piégé</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-warning" onclick="resetCartographyPositions()">
                    <i class="fas fa-sync-alt me-1"></i>Réorganiser (réinitialiser les positions)
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Données pour la cartographie
const cartographyData = {
    places: <?= json_encode($places) ?>,
    externalPlaces: <?= json_encode($external_places) ?>,
    accesses: <?= json_encode($region_accesses) ?>,
    currentRegionId: <?= $region_id ?>
};

// Variables globales pour la cartographie interactive
let placePositions = {};
let isDragging = false;
let draggedElement = null;
let dragOffset = { x: 0, y: 0 };

// Fonction pour détecter les collisions entre éléments
function detectCollision(pos1, pos2, minDistance = 80) {
    const dx = pos1.x - pos2.x;
    const dy = pos1.y - pos2.y;
    return Math.sqrt(dx * dx + dy * dy) < minDistance;
}

// Fonction pour ajuster la position en cas de collision
function adjustPosition(newPos, existingPositions, minDistance = 80) {
    let adjustedPos = { ...newPos };
    let attempts = 0;
    const maxAttempts = 50;
    
    while (attempts < maxAttempts) {
        let hasCollision = false;
        
        for (const [id, pos] of Object.entries(existingPositions)) {
            if (detectCollision(adjustedPos, pos, minDistance)) {
                hasCollision = true;
                break;
            }
        }
        
        if (!hasCollision) {
            break;
        }
        
        // Déplacer légèrement la position
        const angle = Math.random() * 2 * Math.PI;
        adjustedPos.x += Math.cos(angle) * minDistance * 0.5;
        adjustedPos.y += Math.sin(angle) * minDistance * 0.5;
        
        // S'assurer que la position reste dans les limites
        const canvas = document.getElementById('cartographyCanvas');
        const margin = 50;
        adjustedPos.x = Math.max(margin, Math.min(canvas.offsetWidth - margin, adjustedPos.x));
        adjustedPos.y = Math.max(margin, Math.min(canvas.offsetHeight - margin, adjustedPos.y));
        
        attempts++;
    }
    
    return adjustedPos;
}

// Fonction pour charger les positions sauvegardées
async function loadSavedPositions() {
    try {
        const response = await fetch(`api/get_region_cartography_positions.php?region_id=${cartographyData.currentRegionId}`);
        const data = await response.json();
        
        if (data.success && data.positions) {
            return data.positions;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des positions:', error);
    }
    return {};
}

// Fonction pour sauvegarder la position d'une pièce
async function savePlacePosition(placeId, x, y) {
    try {
        const response = await fetch('api/save_region_cartography_position.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                region_id: cartographyData.currentRegionId,
                place_id: placeId,
                position_x: x,
                position_y: y
            })
        });
        
        const data = await response.json();
        if (!data.success) {
            console.error('Erreur lors de la sauvegarde:', data.error);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde de la position:', error);
    }
}

// Fonction pour réinitialiser les positions (réorganiser)
async function resetCartographyPositions() {
    if (!confirm('Voulez-vous vraiment réinitialiser toutes les positions ? Les positions actuelles seront perdues et les pièces seront réorganisés automatiquement.')) {
        return;
    }
    
    // Supprimer toutes les positions sauvegardées pour cette région
    try {
        const response = await fetch('api/delete_region_cartography_positions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                region_id: cartographyData.currentRegionId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            // Régénérer la carte sans positions sauvegardées
            await generateCartography();
        } else {
            alert('Erreur lors de la réinitialisation: ' + (data.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de la réinitialisation:', error);
        alert('Erreur lors de la réinitialisation des positions');
    }
}

// Fonction pour générer la carte
async function generateCartography() {
    const canvas = document.getElementById('cartographyCanvas');
    if (!canvas || cartographyData.places.length === 0) return;
    
    // Nettoyer le canvas
    canvas.innerHTML = '';
    
    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;
    const margin = 50;
    const availableWidth = width - (margin * 2);
    const availableHeight = height - (margin * 2);
    
    // Charger les positions sauvegardées
    const savedPositions = await loadSavedPositions();
    
    // Réinitialiser les positions
    placePositions = {};
    
    // Calculer les positions des pièces de la région en cercle
    const regionPlaces = cartographyData.places;
    regionPlaces.forEach((place, index) => {
        let x, y;
        
        // Utiliser la position sauvegardée si elle existe
        if (savedPositions[place.id]) {
            x = savedPositions[place.id].x;
            y = savedPositions[place.id].y;
        } else {
            // Sinon, calculer une position en cercle
            const angle = (index * 2 * Math.PI) / regionPlaces.length;
            x = margin + (availableWidth / 2) + (Math.cos(angle) * (availableWidth / 3));
            y = margin + (availableHeight / 2) + (Math.sin(angle) * (availableHeight / 3));
        }
        
        const initialPos = { x, y };
        const adjustedPos = adjustPosition(initialPos, placePositions);
        
        placePositions[place.id] = { 
            x: adjustedPos.x, 
            y: adjustedPos.y, 
            place, 
            isExternal: false,
            isDragging: false
        };
    });
    
    // Calculer les positions des pièces externes autour du cercle principal
    const externalPlaces = cartographyData.externalPlaces;
    externalPlaces.forEach((place, index) => {
        let x, y;
        
        // Utiliser la position sauvegardée si elle existe
        if (savedPositions[place.id]) {
            x = savedPositions[place.id].x;
            y = savedPositions[place.id].y;
        } else {
            // Sinon, calculer une position en cercle
            const angle = (index * 2 * Math.PI) / Math.max(externalPlaces.length, 1);
            x = margin + (availableWidth / 2) + (Math.cos(angle) * (availableWidth / 2.2));
            y = margin + (availableHeight / 2) + (Math.sin(angle) * (availableHeight / 2.2));
        }
        
        const initialPos = { x, y };
        const adjustedPos = adjustPosition(initialPos, placePositions);
        
        placePositions[place.id] = { 
            x: adjustedPos.x, 
            y: adjustedPos.y, 
            place, 
            isExternal: true,
            isDragging: false
        };
    });
    
    // Dessiner les connexions
    redrawConnections();
    
    // Dessiner les pièces
    Object.values(placePositions).forEach(({ x, y, place, isExternal }) => {
        const placeElement = document.createElement('div');
        placeElement.style.position = 'absolute';
        placeElement.style.left = (x - 15) + 'px';
        placeElement.style.top = (y - 15) + 'px';
        placeElement.style.width = '30px';
        placeElement.style.height = '30px';
        placeElement.style.backgroundColor = isExternal ? '#6c757d' : '#007bff';
        placeElement.style.border = '2px solid #fff';
        placeElement.style.borderRadius = '50%';
        placeElement.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
        placeElement.style.cursor = 'move';
        placeElement.style.zIndex = '10';
        placeElement.title = place.title + (isExternal ? ' (Autre région)' : '') + ' - Cliquer et glisser pour déplacer';
        placeElement.dataset.placeId = place.id;
        
        // Ajouter le nom de la pièce
        const label = document.createElement('div');
        label.style.position = 'absolute';
        label.style.left = '35px';
        label.style.top = '5px';
        label.style.fontSize = '12px';
        label.style.fontWeight = 'bold';
        label.style.color = '#333';
        label.style.backgroundColor = 'rgba(255,255,255,0.9)';
        label.style.padding = '2px 6px';
        label.style.borderRadius = '4px';
        label.style.whiteSpace = 'nowrap';
        label.style.pointerEvents = 'none';
        label.style.zIndex = '11';
        label.textContent = place.title;
        
        // Ajouter un indicateur pour les pièces externes
        if (isExternal && place.region_name) {
            const regionLabel = document.createElement('div');
            regionLabel.style.position = 'absolute';
            regionLabel.style.left = '35px';
            regionLabel.style.top = '20px';
            regionLabel.style.fontSize = '10px';
            regionLabel.style.color = '#666';
            regionLabel.style.backgroundColor = 'rgba(255,255,255,0.8)';
            regionLabel.style.padding = '1px 4px';
            regionLabel.style.borderRadius = '3px';
            regionLabel.style.whiteSpace = 'nowrap';
            regionLabel.style.pointerEvents = 'none';
            regionLabel.style.zIndex = '11';
            regionLabel.textContent = place.region_name;
            placeElement.appendChild(regionLabel);
        }
        
        placeElement.appendChild(label);
        canvas.appendChild(placeElement);
    });
}

// Fonction pour redessiner les connexions
function redrawConnections() {
    const canvas = document.getElementById('cartographyCanvas');
    if (!canvas) return;
    
    // Supprimer les anciennes connexions
    const oldConnections = canvas.querySelectorAll('.connection-line, .connection-arrow');
    oldConnections.forEach(el => el.remove());
    
    // Redessiner les accès
    cartographyData.accesses.forEach(access => {
        const fromPos = placePositions[access.from_place_id];
        const toPos = placePositions[access.to_place_id];
        
        if (fromPos && toPos) {
            const line = document.createElement('div');
            line.className = 'connection-line';
            line.style.position = 'absolute';
            line.style.left = fromPos.x + 'px';
            line.style.top = fromPos.y + 'px';
            line.style.width = Math.sqrt(Math.pow(toPos.x - fromPos.x, 2) + Math.pow(toPos.y - fromPos.y, 2)) + 'px';
            line.style.height = '2px';
            line.style.transformOrigin = '0 0';
            line.style.transform = `rotate(${Math.atan2(toPos.y - fromPos.y, toPos.x - fromPos.x)}rad)`;
            line.style.zIndex = '5';
            
            // Couleur selon le statut
            if (!access.is_visible) {
                line.style.borderTop = '2px dashed #6c757d';
            } else if (access.is_trapped) {
                line.style.borderTop = '2px solid #dc3545';
            } else if (access.is_open) {
                line.style.borderTop = '2px solid #28a745';
            } else {
                line.style.borderTop = '2px solid #ffc107';
            }
            
            canvas.appendChild(line);
            
            // Ajouter une flèche au milieu
            const arrow = document.createElement('div');
            arrow.className = 'connection-arrow';
            arrow.style.position = 'absolute';
            arrow.style.left = (fromPos.x + toPos.x) / 2 + 'px';
            arrow.style.top = (fromPos.y + toPos.y) / 2 + 'px';
            arrow.style.width = '0';
            arrow.style.height = '0';
            arrow.style.borderLeft = '5px solid transparent';
            arrow.style.borderRight = '5px solid transparent';
            arrow.style.borderBottom = '8px solid ' + (access.is_visible ? (access.is_trapped ? '#dc3545' : (access.is_open ? '#28a745' : '#ffc107')) : '#6c757d');
            arrow.style.transform = `rotate(${Math.atan2(toPos.y - fromPos.y, toPos.x - fromPos.x) + Math.PI/2}rad)`;
            arrow.style.transformOrigin = 'center';
            arrow.style.zIndex = '6';
            
            canvas.appendChild(arrow);
        }
    });
}

// Fonction pour démarrer le déplacement
function startDrag(event) {
    if (event.target.dataset.placeId) {
        isDragging = true;
        draggedElement = event.target;
        draggedElement.style.zIndex = '20';
        draggedElement.style.opacity = '0.8';
        
        const rect = draggedElement.getBoundingClientRect();
        const canvas = document.getElementById('cartographyCanvas');
        const canvasRect = canvas.getBoundingClientRect();
        
        dragOffset.x = event.clientX - rect.left - 15;
        dragOffset.y = event.clientY - rect.top - 15;
        
        event.preventDefault();
    }
}

// Fonction pour continuer le déplacement
function drag(event) {
    if (!isDragging || !draggedElement) return;
    
    const canvas = document.getElementById('cartographyCanvas');
    const canvasRect = canvas.getBoundingClientRect();
    
    const newX = event.clientX - canvasRect.left - dragOffset.x;
    const newY = event.clientY - canvasRect.top - dragOffset.y;
    
    // Limiter aux limites du canvas
    const margin = 15;
    const maxX = canvas.offsetWidth - margin;
    const maxY = canvas.offsetHeight - margin;
    
    const clampedX = Math.max(margin, Math.min(maxX, newX));
    const clampedY = Math.max(margin, Math.min(maxY, newY));
    
    draggedElement.style.left = clampedX + 'px';
    draggedElement.style.top = clampedY + 'px';
    
    // Mettre à jour la position dans placePositions
    const placeId = parseInt(draggedElement.dataset.placeId);
    if (placePositions[placeId]) {
        placePositions[placeId].x = clampedX + 15;
        placePositions[placeId].y = clampedY + 15;
    }
    
    // Redessiner les connexions
    redrawConnections();
    
    event.preventDefault();
}

// Fonction pour arrêter le déplacement
function stopDrag(event) {
    if (isDragging && draggedElement) {
        const placeId = parseInt(draggedElement.dataset.placeId);
        if (placePositions[placeId]) {
            // Sauvegarder la position
            const x = placePositions[placeId].x;
            const y = placePositions[placeId].y;
            savePlacePosition(placeId, x, y);
        }
        
        draggedElement.style.zIndex = '10';
        draggedElement.style.opacity = '1';
        draggedElement = null;
        isDragging = false;
    }
}

// Initialiser la cartographie quand le modal s'ouvre
document.getElementById('cartographyModal').addEventListener('shown.bs.modal', function () {
    setTimeout(async () => {
        await generateCartography();
        
        // Ajouter les événements de déplacement
        const canvas = document.getElementById('cartographyCanvas');
        if (canvas) {
            canvas.addEventListener('mousedown', startDrag);
            canvas.addEventListener('mousemove', drag);
            canvas.addEventListener('mouseup', stopDrag);
            canvas.addEventListener('mouseleave', stopDrag);
            
            // Support tactile pour les appareils mobiles
            canvas.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchmove', function(e) {
                e.preventDefault();
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            });
            
            canvas.addEventListener('touchend', function(e) {
                e.preventDefault();
                const mouseEvent = new MouseEvent('mouseup', {});
                canvas.dispatchEvent(mouseEvent);
            });
        }
    }, 100);
});

// Régénérer la carte si la fenêtre est redimensionnée
window.addEventListener('resize', async function() {
    if (document.getElementById('cartographyModal').classList.contains('show')) {
        await generateCartography();
    }
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
