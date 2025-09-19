<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Détails de la Région";
$current_page = "manage_worlds"; // Pour garder le bouton "Mondes" actif dans la navbar

requireLogin();
requireDMOrAdmin();

$region_id = (int)($_GET['id'] ?? 0);
if ($region_id === 0) {
    header('Location: manage_worlds.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer la région avec ses informations de pays et monde
$stmt = $pdo->prepare("SELECT r.*, c.name as country_name, c.world_id, w.name as world_name, w.created_by 
                      FROM regions r 
                      JOIN countries c ON r.country_id = c.id 
                      JOIN worlds w ON c.world_id = w.id 
                      WHERE r.id = ? AND w.created_by = ?");
$stmt->execute([$region_id, $user_id]);
$region = $stmt->fetch();

if (!$region) {
    header('Location: manage_worlds.php?error=region_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Fonction helper pour tronquer le texte
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Fonction helper pour l'upload d'image de lieu
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
        case 'create_place':
            $title = sanitizeInput($_POST['title'] ?? '');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $map_url = '';
            
            if (empty($title)) {
                $error_message = "Le nom du lieu est requis.";
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
                        $stmt = $pdo->prepare("INSERT INTO places (title, notes, map_url, region_id, country_id, campaign_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $notes, $map_url, $region_id, $region['country_id'], null]);
                        $success_message = "Lieu '$title' créé avec succès.";
                    } catch (PDOException $e) {
                        $error_message = "Erreur lors de la création du lieu: " . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'update_place':
            $place_id = (int)($_POST['place_id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            if (empty($title)) {
                $error_message = "Le nom du lieu est requis.";
            } else {
                // Récupérer l'URL actuelle
                $stmt = $pdo->prepare("SELECT map_url FROM places WHERE id = ? AND region_id = ?");
                $stmt->execute([$place_id, $region_id]);
                $current_place = $stmt->fetch();
                $map_url = $current_place['map_url'] ?? '';
                
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
                        $stmt = $pdo->prepare("UPDATE places SET title = ?, notes = ?, map_url = ? WHERE id = ? AND region_id = ?");
                        $stmt->execute([$title, $notes, $map_url, $place_id, $region_id]);
                        if ($stmt->rowCount() > 0) {
                            $success_message = "Lieu '$title' mis à jour avec succès.";
                        } else {
                            $error_message = "Lieu non trouvé.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'delete_place':
            $place_id = (int)($_POST['place_id'] ?? 0);
            
            try {
                // Récupérer l'URL de l'image avant suppression
                $stmt = $pdo->prepare("SELECT map_url FROM places WHERE id = ? AND region_id = ?");
                $stmt->execute([$place_id, $region_id]);
                $place = $stmt->fetch();
                
                // Supprimer le lieu
                $stmt = $pdo->prepare("DELETE FROM places WHERE id = ? AND region_id = ?");
                $stmt->execute([$place_id, $region_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Supprimer l'image associée si elle existe
                    if (!empty($place['map_url']) && file_exists($place['map_url'])) {
                        unlink($place['map_url']);
                    }
                    $success_message = "Lieu supprimé avec succès.";
                } else {
                    $error_message = "Lieu non trouvé.";
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            break;
    }
}

// Récupérer les lieux de la région
$stmt = $pdo->prepare("SELECT * FROM places WHERE region_id = ? ORDER BY title");
$stmt->execute([$region_id]);
$places = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($region['name']); ?> - JDR 4 MJ</title>
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
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

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
                                <?php echo htmlspecialchars($region['name']); ?>
                            </h1>
                            <p class="text-muted mb-1">
                                <i class="fas fa-flag me-1"></i>
                                Pays: <?php echo htmlspecialchars($region['country_name']); ?>
                            </p>
                            <p class="text-muted mb-1">
                                <i class="fas fa-globe-americas me-1"></i>
                                Monde: <?php echo htmlspecialchars($region['world_name']); ?>
                            </p>
                            <?php if (!empty($region['description'])): ?>
                                <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($region['description'])); ?></p>
                            <?php endif; ?>
                            <div class="d-flex gap-3">
                                <a href="view_country.php?id=<?php echo (int)$region['country_id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour au pays
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($region['map_url'])): ?>
                            <div class="text-end">
                                <img src="<?php echo htmlspecialchars($region['map_url']); ?>" 
                                     alt="Carte de <?php echo htmlspecialchars($region['name']); ?>" 
                                     class="img-fluid rounded cursor-pointer" 
                                     style="max-height: 200px; max-width: 300px;"
                                     onclick="openMapFullscreen('<?php echo htmlspecialchars($region['map_url']); ?>', '<?php echo htmlspecialchars($region['name']); ?>')"
                                     title="Cliquer pour voir en plein écran">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lieux de la région -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-map-pin me-2"></i>Lieux de cette région</h5>
            <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createPlaceModal">
                <i class="fas fa-plus me-2"></i>Nouveau Lieu
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($places)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Aucun lieu n'a encore été créé dans cette région.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($places as $place): ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if (!empty($place['map_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($place['map_url']); ?>" 
                                         alt="Carte de <?php echo htmlspecialchars($place['title']); ?>" 
                                         class="card-img-top cursor-pointer" 
                                         style="height: 200px; object-fit: cover;"
                                         onclick="openMapFullscreen('<?php echo htmlspecialchars($place['map_url']); ?>', '<?php echo htmlspecialchars($place['title']); ?>')"
                                         title="Cliquer pour voir en plein écran">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background-color: #f8f9fa;">
                                        <i class="fas fa-map-pin fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($place['title']); ?></h5>
                                    
                                    <?php if (!empty($place['notes'])): ?>
                                        <p class="card-text text-muted small flex-grow-1"><?php echo nl2br(htmlspecialchars(truncateText($place['notes'], 100))); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <a href="view_place.php?id=<?php echo (int)$place['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Voir le Lieu
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPlaceModal"
                                                    onclick="editPlace(<?php echo htmlspecialchars(json_encode($place)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer le lieu <?php echo htmlspecialchars($place['title']); ?> ?');">
                                                <input type="hidden" name="action" value="delete_place">
                                                <input type="hidden" name="place_id" value="<?php echo (int)$place['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Création Lieu -->
<div class="modal fade" id="createPlaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer un nouveau lieu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_place">
                    
                    <div class="mb-3">
                        <label for="createPlaceTitle" class="form-label">Nom du lieu *</label>
                        <input type="text" class="form-control" id="createPlaceTitle" name="title" required placeholder="Ex: Taverne du Dragon, Château de la Montagne...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="createPlaceNotes" class="form-label">Description</label>
                        <textarea class="form-control" id="createPlaceNotes" name="notes" rows="3" placeholder="Décrivez ce lieu..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="createPlaceMap" class="form-label">Carte du lieu</label>
                        <input type="file" class="form-control" id="createPlaceMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        <div id="createPlaceMapPreview" class="mt-2" style="display: none;">
                            <img id="createPlaceMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-brown">Créer le lieu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Lieu -->
<div class="modal fade" id="editPlaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le lieu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_place">
                    <input type="hidden" name="place_id" id="editPlaceId">
                    
                    <div class="mb-3">
                        <label for="editPlaceTitle" class="form-label">Nom du lieu *</label>
                        <input type="text" class="form-control" id="editPlaceTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPlaceNotes" class="form-label">Description</label>
                        <textarea class="form-control" id="editPlaceNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editPlaceMap" class="form-label">Carte du lieu</label>
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
    // Gestion de l'aperçu d'image pour la création de lieu
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

    // Gestion de l'aperçu d'image pour l'édition de lieu
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
</script>

<!-- Modal Plein Écran pour la Carte -->
<div class="modal fade" id="fullscreenMapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="fullscreenMapTitle">
                    <i class="fas fa-map me-2"></i>Carte du Lieu
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
