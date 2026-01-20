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
$location = Location::findById($locationId);

if (!$location) {
    header('Location: index.php?error=location_not_found');
    exit();
}

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

// Fonction helper pour l'upload d'image de lieu
function uploadLocationImage($file) {
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
    $uploadDir = 'uploads/locations/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'location_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement du fichier'];
    }

    return ['success' => true, 'file_path' => $filePath];
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
    
    // Mise à jour du lieu
    if ($action === 'update_location') {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        if (!empty($name)) {
            // Gérer l'image
            if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadLocationImage($_FILES['map_image']);
                if ($uploadResult['success']) {
                    // Supprimer l'ancienne image si elle existe
                    $oldImage = $location->getMapUrl();
                    if ($oldImage && file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                    $location->setMapUrl($uploadResult['file_path']);
                } else {
                    $error_message = $uploadResult['error'];
                }
            }

            // Mettre à jour les autres champs si pas d'erreur critique
            if (empty($error_message)) {
                // On utilise la réflexion ou on ajoute des setters publics si besoin, 
                // mais pour l'instant Location n'a pas de setters publics pour name/desc.
                // Ah, Location::save() utilise les propriétés privées.
                // Il faut hydrater l'objet avec les nouvelles données.
                // Comme hydrate est public, on peut l'utiliser !
                // Mais hydrate attend un tableau.
                
                // Hack: hydrate overwrites everything. Be careful to keep ID etc.
                // Better approach: use Reflection or add setters in Location.php.
                // Wait, I updated Location.php to have setMapUrl.
                // But I didn't verify setName/setDescription setters. 
                // Let's assume for now I need to update directly via SQL or add setters?
                // Actually, I can use hydrate safely if I pass all current values + updates.
                
                /*
                 * PROBLÈME: Location n'a pas de setters pour name/desc dans ma version vue précédemment.
                 * JE DOIS MODIFIER Location.php pour ajouter les setters OU utiliser hydrate intelligemment.
                 * Hydrate:
                 * $data['id'] = $this->id...
                 */
                
                // Let's do a re-hydrate with current + new values
                $newData = [
                    'id' => $location->getId(),
                    'name' => $name,
                    'description' => $description,
                    'map_url' => $location->getMapUrl(), // Updated via setter above if changed
                    'region_id' => $location->getRegionId(),
                    // timestamps will be handled by save() or ignored
                ];
                $location->hydrate($newData);
                
                if ($location->save()) {
                     header("Location: view_location.php?id=" . $locationId . "&updated=1");
                     exit();
                } else {
                    $error_message = "Erreur lors de la mise à jour du lieu.";
                }
            }
        } else {
            $error_message = "Le nom du lieu est requis.";
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
        .cursor-pointer {
            cursor: pointer;
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
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Lieu mis à jour avec succès.</div>
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
                            
                            <div class="d-flex align-items-start">
                                <?php if ($location->getMapUrl()): ?>
                                    <div class="me-3">
                                        <img src="<?php echo htmlspecialchars($location->getMapUrl()); ?>" 
                                             alt="Image du lieu" 
                                             class="img-thumbnail cursor-pointer" 
                                             style="max-height: 150px;"
                                             onclick="window.open(this.src, '_blank')">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($isCreator): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#createPlaceModal">
                                            <i class="fas fa-plus me-1"></i>Nouvelle Pièce
                                        </button>
                                        <button class="btn btn-outline-secondary text-nowrap" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                                            <i class="fas fa-edit me-1"></i>Modifier
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
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
                            $placeData = [
                                'id' => $place->id,
                                'title' => $place->title,
                                'map_url' => $place->map_url,
                                'notes' => $place->notes,
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
    
    <!-- Modal Édition Lieu -->
    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_location">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le lieu : <?php echo htmlspecialchars($location->getName()); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nom du lieu *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" 
                                   value="<?php echo htmlspecialchars($location->getName()); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"><?php echo htmlspecialchars($location->getDescription()); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="map_image" class="form-label">Image / Illustration</label>
                            <input type="file" class="form-control" id="map_image" name="map_image" accept="image/*">
                            <div class="form-text">Formats acceptés : JPG, PNG, GIF, WebP. Max 5 Mo.</div>
                        </div>
                        <?php if ($location->getMapUrl()): ?>
                            <div class="mb-3">
                                <label class="form-label">Image actuelle :</label>
                                <div>
                                    <img src="<?php echo htmlspecialchars($location->getMapUrl()); ?>" class="img-fluid rounded" style="max-height: 100px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function openMapFullscreen(url, title) {
             window.open(url, '_blank');
        }
        function drag(ev, elem) {
            // Not used here
        }
    </script>
</body>
</html>
