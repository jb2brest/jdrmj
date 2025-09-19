<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Détails du Pays";
$current_page = "manage_worlds"; // Pour garder le bouton "Mondes" actif dans la navbar

requireLogin();
requireDMOrAdmin();

$country_id = (int)($_GET['id'] ?? 0);
if ($country_id === 0) {
    header('Location: manage_worlds.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer le pays avec ses informations de monde
$stmt = $pdo->prepare("SELECT c.*, w.name as world_name, w.created_by 
                      FROM countries c 
                      JOIN worlds w ON c.world_id = w.id 
                      WHERE c.id = ? AND w.created_by = ?");
$stmt->execute([$country_id, $user_id]);
$country = $stmt->fetch();

if (!$country) {
    header('Location: manage_worlds.php?error=country_not_found');
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

// Fonction helper pour l'upload d'image de région
function uploadRegionImage($file, $type = 'map') {
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
    $uploadDir = 'uploads/regions/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'region_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
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
        case 'create_region':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $map_url = '';
            
            if (empty($name)) {
                $error_message = "Le nom de la région est requis.";
            } else {
                // Gérer l'upload de la carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadRegionImage($_FILES['map_image'], 'map');
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                
                if (empty($error_message)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO regions (name, description, map_url, country_id) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $description, $map_url, $country_id]);
                        $success_message = "Région '$name' créée avec succès.";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Une région avec ce nom existe déjà dans ce pays.";
                        } else {
                            $error_message = "Erreur lors de la création de la région: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'update_region':
            $region_id = (int)($_POST['region_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom de la région est requis.";
            } else {
                // Récupérer l'URL actuelle
                $stmt = $pdo->prepare("SELECT map_url FROM regions WHERE id = ? AND country_id = ?");
                $stmt->execute([$region_id, $country_id]);
                $current_region = $stmt->fetch();
                $map_url = $current_region['map_url'] ?? '';
                
                // Gérer l'upload de la nouvelle carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadRegionImage($_FILES['map_image'], 'map');
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
                        $stmt = $pdo->prepare("UPDATE regions SET name = ?, description = ?, map_url = ? WHERE id = ? AND country_id = ?");
                        $stmt->execute([$name, $description, $map_url, $region_id, $country_id]);
                        if ($stmt->rowCount() > 0) {
                            $success_message = "Région '$name' mise à jour avec succès.";
                        } else {
                            $error_message = "Région non trouvée.";
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Une région avec ce nom existe déjà dans ce pays.";
                        } else {
                            $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'delete_region':
            $region_id = (int)($_POST['region_id'] ?? 0);
            
            try {
                // Vérifier s'il y a des lieux dans cette région
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM places WHERE region_id = ?");
                $stmt->execute([$region_id]);
                $place_count = $stmt->fetchColumn();
                
                if ($place_count > 0) {
                    $error_message = "Impossible de supprimer cette région car elle contient $place_count lieux. Supprimez d'abord les lieux.";
                } else {
                    // Récupérer l'URL de l'image avant suppression
                    $stmt = $pdo->prepare("SELECT map_url FROM regions WHERE id = ? AND country_id = ?");
                    $stmt->execute([$region_id, $country_id]);
                    $region = $stmt->fetch();
                    
                    // Supprimer la région
                    $stmt = $pdo->prepare("DELETE FROM regions WHERE id = ? AND country_id = ?");
                    $stmt->execute([$region_id, $country_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Supprimer l'image associée si elle existe
                        if (!empty($region['map_url']) && file_exists($region['map_url'])) {
                            unlink($region['map_url']);
                        }
                        $success_message = "Région supprimée avec succès.";
                    } else {
                        $error_message = "Région non trouvée.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            break;
    }
}

// Récupérer les régions du pays
$stmt = $pdo->prepare("SELECT * FROM regions WHERE country_id = ? ORDER BY name");
$stmt->execute([$country_id]);
$regions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($country['name']); ?> - JDR 4 MJ</title>
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

    <!-- En-tête du pays -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="mb-2">
                                <i class="fas fa-flag me-2"></i>
                                <?php echo htmlspecialchars($country['name']); ?>
                            </h1>
                            <p class="text-muted mb-1">
                                <i class="fas fa-globe-americas me-1"></i>
                                Monde: <?php echo htmlspecialchars($country['world_name']); ?>
                            </p>
                            <?php if (!empty($country['description'])): ?>
                                <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($country['description'])); ?></p>
                            <?php endif; ?>
                            <div class="d-flex gap-3">
                                <a href="view_world.php?id=<?php echo (int)$country['world_id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour au monde
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($country['map_url'])): ?>
                            <div class="text-end">
                                <img src="<?php echo htmlspecialchars($country['map_url']); ?>" 
                                     alt="Carte de <?php echo htmlspecialchars($country['name']); ?>" 
                                     class="img-fluid rounded cursor-pointer" 
                                     style="max-height: 200px; max-width: 300px;"
                                     onclick="openMapFullscreen('<?php echo htmlspecialchars($country['map_url']); ?>', '<?php echo htmlspecialchars($country['name']); ?>')"
                                     title="Cliquer pour voir en plein écran">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Régions du pays -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Régions de ce pays</h5>
            <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createRegionModal">
                <i class="fas fa-plus me-2"></i>Nouvelle Région
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($regions)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Aucune région n'a encore été créée dans ce pays.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($regions as $region): ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if (!empty($region['map_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($region['map_url']); ?>" 
                                         alt="Carte de <?php echo htmlspecialchars($region['name']); ?>" 
                                         class="card-img-top cursor-pointer" 
                                         style="height: 200px; object-fit: cover;"
                                         onclick="openMapFullscreen('<?php echo htmlspecialchars($region['map_url']); ?>', '<?php echo htmlspecialchars($region['name']); ?>')"
                                         title="Cliquer pour voir en plein écran">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background-color: #f8f9fa;">
                                        <i class="fas fa-map-marked-alt fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($region['name']); ?></h5>
                                    
                                    <?php if (!empty($region['description'])): ?>
                                        <p class="card-text text-muted small flex-grow-1"><?php echo nl2br(htmlspecialchars(truncateText($region['description'], 100))); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <a href="view_region.php?id=<?php echo (int)$region['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Voir la Région
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRegionModal"
                                                    onclick="editRegion(<?php echo htmlspecialchars(json_encode($region)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la région <?php echo htmlspecialchars($region['name']); ?> ? Tous les lieux associés seront également supprimés.');">
                                                <input type="hidden" name="action" value="delete_region">
                                                <input type="hidden" name="region_id" value="<?php echo (int)$region['id']; ?>">
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

<!-- Modal Création Région -->
<div class="modal fade" id="createRegionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer une nouvelle région</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_region">
                    
                    <div class="mb-3">
                        <label for="createRegionName" class="form-label">Nom de la région *</label>
                        <input type="text" class="form-control" id="createRegionName" name="name" required placeholder="Ex: Comté de la Marche, Province du Nord...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="createRegionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="createRegionDescription" name="description" rows="3" placeholder="Décrivez cette région..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="createRegionMap" class="form-label">Carte de la région</label>
                        <input type="file" class="form-control" id="createRegionMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        <div id="createRegionMapPreview" class="mt-2" style="display: none;">
                            <img id="createRegionMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-brown">Créer la région</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Région -->
<div class="modal fade" id="editRegionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier la région</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_region">
                    <input type="hidden" name="region_id" id="editRegionId">
                    
                    <div class="mb-3">
                        <label for="editRegionName" class="form-label">Nom de la région *</label>
                        <input type="text" class="form-control" id="editRegionName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editRegionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editRegionDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editRegionMap" class="form-label">Carte de la région</label>
                        <input type="file" class="form-control" id="editRegionMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        <div id="editRegionMapPreview" class="mt-2" style="display: none;">
                            <img id="editRegionMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                        <div id="editRegionCurrentMap" class="mt-2" style="display: none;">
                            <label class="form-label">Carte actuelle:</label>
                            <img id="editRegionCurrentMapImg" src="" alt="Carte actuelle" class="img-fluid rounded" style="max-height: 150px;">
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
    // Gestion de l'aperçu d'image pour la création de région
    document.getElementById('createRegionMap').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('createRegionMapPreviewImg').src = e.target.result;
                document.getElementById('createRegionMapPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('createRegionMapPreview').style.display = 'none';
        }
    });


    // Gestion de l'aperçu d'image pour l'édition de région
    document.getElementById('editRegionMap').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editRegionMapPreviewImg').src = e.target.result;
                document.getElementById('editRegionMapPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('editRegionMapPreview').style.display = 'none';
        }
    });


    function editRegion(region) {
        document.getElementById('editRegionId').value = region.id;
        document.getElementById('editRegionName').value = region.name;
        document.getElementById('editRegionDescription').value = region.description || '';
        
        // Afficher l'image actuelle si elle existe
        if (region.map_url) {
            document.getElementById('editRegionCurrentMapImg').src = region.map_url;
            document.getElementById('editRegionCurrentMap').style.display = 'block';
        } else {
            document.getElementById('editRegionCurrentMap').style.display = 'none';
        }
        
        // Réinitialiser l'aperçu de nouvelle image
        document.getElementById('editRegionMap').value = '';
        document.getElementById('editRegionMapPreview').style.display = 'none';
        
        var editModal = new bootstrap.Modal(document.getElementById('editRegionModal'));
        editModal.show();
    }
    
    // Fonction pour ouvrir la carte en plein écran
    function openMapFullscreen(mapUrl, regionName) {
        document.getElementById('fullscreenMapImg').src = mapUrl;
        document.getElementById('fullscreenMapTitle').textContent = 'Carte de ' + regionName;
        
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
                    <i class="fas fa-map me-2"></i>Carte de la Région
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
