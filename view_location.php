<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
require_once 'classes/Region.php';
require_once 'classes/Location.php';
require_once 'classes/Room.php';

// Vérifier que l'utilisateur est connecté
User::requireLogin();

// Récupérer l'ID du lieu
$locationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// DEBUG TRACES
error_log("DEBUG view_location: ID received = " . $locationId);
error_log("DEBUG view_location: User ID = " . ($_SESSION['user_id'] ?? 'not set'));

$location = Location::findById($locationId);

if (!$location) {
    error_log("DEBUG view_location: Location not found for ID " . $locationId);
    // Masquer la redirection pour voir l'erreur
    // header('Location: index.php?error=location_not_found');
    die("DEBUG: Location not found. ID requested: " . $locationId . ". User: " . ($_SESSION['user_id'] ?? 'guest'));
    exit();
}
error_log("DEBUG view_location: Location found: " . $location->getName());

$region = Region::findById($location->getRegionId());
$isCreator = false;

// Vérifier les droits d'accès
if ($region) {
    $monde = $region->getMonde();
    // Le créateur du monde a les droits
    if ($monde && $monde['created_by'] == $_SESSION['user_id']) {
        $isCreator = true;
    }
}

// Gestion des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isCreator) {
    $action = $_POST['action'] ?? '';
    
    // Création d'une pièce directement dans ce lieu
    if ($action === 'create_place_in_location') {
        $title = sanitizeInput($_POST['title']);
        $notes = sanitizeInput($_POST['notes']);
        
        if (!empty($title)) {
            // Créer la pièce (Room)
            $room = new Room();
            $room->title = $title;
            // $room->description = $notes; // Note: Room utilise 'notes' ou 'description' ? Check Room class.
            // Vérifions la classe Room plus tard, assumons 'notes' pour l'instant comme dans view_region
            $room->notes = $notes;
            $room->region_id = $region->getId();
            $room->location_id = $locationId;
            $room->country_id = $region->getCountryId(); // Si nécessaire
            $room->created_by = $_SESSION['user_id'];
            
            if ($room->save()) {
                $success_message = "Pièce créée avec succès dans ce lieu.";
                // Refresh to avoid resubmission
                header("Location: view_location.php?id=" . $locationId . "&success=1");
                exit();
            } else {
                $error_message = "Erreur lors de la création de la pièce.";
            }
        } else {
            $error_message = "Le titre est requis.";
        }
    }
}

// Récupérer les pièces du lieu
$rooms = $location->getRooms();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($location->getName()); ?> - JDR MJ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .entity-card {
            transition: transform 0.2s;
        }
        .entity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <?php if ($region): ?>
                    <li class="breadcrumb-item"><a href="view_region.php?id=<?php echo $region->getId(); ?>"><?php echo htmlspecialchars($region->getName()); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($location->getName()); ?></li>
            </ol>
        </nav>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card bg-light border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 class="display-6 mb-2">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i><?php echo htmlspecialchars($location->getName()); ?>
                                </h1>
                                <?php if ($location->getDescription()): ?>
                                    <p class="lead text-muted mb-0"><?php echo nl2br(htmlspecialchars($location->getDescription())); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($isCreator): ?>
                                <div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlaceModal">
                                        <i class="fas fa-plus me-1"></i>Nouvelle Pièce
                                    </button>
                                    <button class="btn btn-outline-secondary ms-2" title="Modifier le lieu">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h3 class="mb-3 border-bottom pb-2">Pièces dans ce lieu</h3>
                
                <?php if (empty($rooms)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Aucune pièce n'a encore été créée dans ce lieu.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php foreach ($rooms as $place): 
                            // Convert object to array if needed for the template, or adapt template.
                            // The Place class usually works as an object, but our template might expect array.
                            // Let's check place_card.php expectations. It uses $place['title'].
                            // So we need to ensure $place acts like an array or object.
                            // If Room class does not implement ArrayAccess, we might need to convert.
                            // For safety, let's assume we pass the object if the template handles objects, 
                            // OR we convert to array. looking at Place Card, it uses $place['id'].
                            // Room objects likely don't support array access unless implemented.
                            // We should probably convert room object to array here.
                            $placeArray = is_object($place) && method_exists($place, 'toArray') ? $place->toArray() : (array)$place;
                            // Also need to ensure map_url, title, id, notes exist.
                            // If toArray() is not implemented or different, we must map manually.
                            // Let's rely on Room object properties being public or mapped.
                            // Actually, let's mock the array structure cleanly.
                            $placeData = [
                                'id' => $place->id,
                                'title' => $place->title,
                                'map_url' => $place->map_url,
                                'notes' => $place->notes,
                                // Add other fields if needed
                            ];
                            $place = $placeData; // Override for template
                            include 'templates/place_card.php'; 
                        ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Création Pièce -->
    <?php if ($isCreator): ?>
    <div class="modal fade" id="createPlaceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="create_place_in_location">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle Pièce dans <?php echo htmlspecialchars($location->getName()); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Nom de la pièce *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Description / Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function openMapFullscreen(url, title) {
            // Placeholder for map function
             window.open(url, '_blank');
        }
        function drag(ev, elem) {
            // Placeholder to prevent errors if place_card uses it
            // Dragging might not be needed INSIDE view_location unless we move rooms OUT?
            // For now, no drag logic needed here.
        }
    </script>
</body>
</html>
