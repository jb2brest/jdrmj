<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Gestion des Mondes";
$current_page = "manage_worlds";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fonction helper pour l'upload d'image
function uploadWorldImage($file) {
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
    $uploadDir = 'uploads/worlds/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'world_' . time() . '_' . uniqid() . '.' . $extension;
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
        case 'create_world':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $map_url = '';
            
            if (empty($name)) {
                $error_message = "Le nom du monde est requis.";
            } else {
                // Gérer l'upload d'image si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadWorldImage($_FILES['map_image']);
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO worlds (name, description, map_url, created_by) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $description, $map_url, $user_id]);
                        $success_message = "Monde '$name' créé avec succès.";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Un monde avec ce nom existe déjà.";
                        } else {
                            $error_message = "Erreur lors de la création du monde: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'update_world':
            $world_id = (int)($_POST['world_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom du monde est requis.";
            } else {
                // Récupérer l'URL actuelle de la carte
                $stmt = $pdo->prepare("SELECT map_url FROM worlds WHERE id = ? AND created_by = ?");
                $stmt->execute([$world_id, $user_id]);
                $current_world = $stmt->fetch();
                $map_url = $current_world['map_url'] ?? '';
                
                // Gérer l'upload d'image si un nouveau fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadWorldImage($_FILES['map_image']);
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        // Supprimer l'ancienne image si elle existe
                        if (!empty($map_url) && file_exists($map_url)) {
                            unlink($map_url);
                        }
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE worlds SET name = ?, description = ?, map_url = ? WHERE id = ? AND created_by = ?");
                        $stmt->execute([$name, $description, $map_url, $world_id, $user_id]);
                        if ($stmt->rowCount() > 0) {
                            $success_message = "Monde '$name' mis à jour avec succès.";
                        } else {
                            $error_message = "Monde non trouvé ou vous n'avez pas les droits.";
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Un monde avec ce nom existe déjà.";
                        } else {
                            $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'delete_world':
            $world_id = (int)($_POST['world_id'] ?? 0);
            
            try {
                // Vérifier s'il y a des pays dans ce monde
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE world_id = ?");
                $stmt->execute([$world_id]);
                $country_count = $stmt->fetchColumn();
                
                if ($country_count > 0) {
                    $error_message = "Impossible de supprimer ce monde car il contient $country_count pays. Supprimez d'abord les pays.";
                } else {
                    // Récupérer l'URL de la carte avant suppression
                    $stmt = $pdo->prepare("SELECT map_url FROM worlds WHERE id = ? AND created_by = ?");
                    $stmt->execute([$world_id, $user_id]);
                    $world = $stmt->fetch();
                    
                    // Supprimer le monde
                    $stmt = $pdo->prepare("DELETE FROM worlds WHERE id = ? AND created_by = ?");
                    $stmt->execute([$world_id, $user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Supprimer l'image associée si elle existe
                        if (!empty($world['map_url']) && file_exists($world['map_url'])) {
                            unlink($world['map_url']);
                        }
                        $success_message = "Monde supprimé avec succès.";
                    } else {
                        $error_message = "Monde non trouvé ou vous n'avez pas les droits.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            break;
    }
}

// Récupérer les mondes de l'utilisateur
$stmt = $pdo->prepare("SELECT w.*, 
    (SELECT COUNT(*) FROM countries WHERE world_id = w.id) as country_count
    FROM worlds w 
    WHERE w.created_by = ? 
    ORDER BY w.name");
$stmt->execute([$user_id]);
$worlds = $stmt->fetchAll();

// Récupérer un monde spécifique pour l'édition
$edit_world = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $world_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM worlds WHERE id = ? AND created_by = ?");
    $stmt->execute([$world_id, $user_id]);
    $edit_world = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
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
            background-color: #000;
        }
        #fullscreenMapImg {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-globe-americas me-2"></i>Gestion des Mondes</h1>
            <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createWorldModal">
                <i class="fas fa-plus me-2"></i>Nouveau Monde
            </button>
        </div>

        <!-- Liste des mondes -->
        <div class="row">
            <?php if (empty($worlds)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-globe-americas fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun monde créé</h4>
                            <p class="text-muted">Commencez par créer votre premier monde de campagne.</p>
                            <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createWorldModal">
                                <i class="fas fa-plus me-2"></i>Créer un monde
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($worlds as $world): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($world['map_url'])): ?>
                                <img src="<?php echo htmlspecialchars($world['map_url']); ?>" 
                                     alt="Carte de <?php echo htmlspecialchars($world['name']); ?>" 
                                     class="card-img-top cursor-pointer" 
                                     style="height: 200px; object-fit: cover;"
                                     onclick="openMapFullscreen('<?php echo htmlspecialchars($world['map_url']); ?>', '<?php echo htmlspecialchars($world['name']); ?>')"
                                     title="Cliquer pour voir en plein écran">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background-color: #f8f9fa;">
                                    <i class="fas fa-globe-americas fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($world['name']); ?></h5>
                                
                                <?php if (!empty($world['description'])): ?>
                                    <p class="card-text text-muted"><?php echo nl2br(htmlspecialchars($world['description'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-flag me-1"></i>
                                            <?php echo $world['country_count']; ?> pays
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($world['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <a href="view_world.php?id=<?php echo $world['id']; ?>" class="btn btn-outline-brown btn-sm">
                                            <i class="fas fa-eye me-1"></i>Voir
                                        </a>
                                        <button class="btn btn-outline-primary btn-sm" onclick="editWorld(<?php echo htmlspecialchars(json_encode($world)); ?>)">
                                            <i class="fas fa-edit me-1"></i>Modifier
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteWorld(<?php echo $world['id']; ?>, '<?php echo htmlspecialchars($world['name']); ?>')">
                                            <i class="fas fa-trash me-1"></i>Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Création Monde -->
    <div class="modal fade" id="createWorldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer un nouveau monde</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_world">
                        
                        <div class="mb-3">
                            <label for="createName" class="form-label">Nom du monde *</label>
                            <input type="text" class="form-control" id="createName" name="name" required placeholder="Ex: Terre du Milieu, Westeros...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="createDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="createDescription" name="description" rows="4" placeholder="Décrivez votre monde, son histoire, sa géographie..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="createMapImage" class="form-label">Carte du monde</label>
                            <input type="file" class="form-control" id="createMapImage" name="map_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="createMapPreview" class="mt-2" style="display: none;">
                                <img id="createMapPreviewImg" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-brown">Créer le monde</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Monde -->
    <div class="modal fade" id="editWorldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le monde</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_world">
                        <input type="hidden" name="world_id" id="editWorldId">
                        
                        <div class="mb-3">
                            <label for="editName" class="form-label">Nom du monde *</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editMapImage" class="form-label">Carte du monde</label>
                            <input type="file" class="form-control" id="editMapImage" name="map_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="editMapPreview" class="mt-2" style="display: none;">
                                <img id="editMapPreviewImg" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                            <div id="editCurrentMap" class="mt-2" style="display: none;">
                                <label class="form-label">Carte actuelle:</label>
                                <img id="editCurrentMapImg" src="" alt="Carte actuelle" class="img-fluid rounded" style="max-height: 200px;">
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

    <!-- Modal Confirmation Suppression -->
    <div class="modal fade" id="deleteWorldModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le monde <strong id="deleteWorldName"></strong> ?</p>
                    <p class="text-danger"><small>Cette action est irréversible. Assurez-vous qu'aucun pays n'est associé à ce monde.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete_world">
                        <input type="hidden" name="world_id" id="deleteWorldId">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'aperçu d'image pour la création
        document.getElementById('createMapImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('createMapPreviewImg').src = e.target.result;
                    document.getElementById('createMapPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('createMapPreview').style.display = 'none';
            }
        });

        // Gestion de l'aperçu d'image pour l'édition
        document.getElementById('editMapImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editMapPreviewImg').src = e.target.result;
                    document.getElementById('editMapPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('editMapPreview').style.display = 'none';
            }
        });

        function editWorld(world) {
            document.getElementById('editWorldId').value = world.id;
            document.getElementById('editName').value = world.name;
            document.getElementById('editDescription').value = world.description || '';
            
            // Afficher la carte actuelle si elle existe
            if (world.map_url) {
                document.getElementById('editCurrentMapImg').src = world.map_url;
                document.getElementById('editCurrentMap').style.display = 'block';
            } else {
                document.getElementById('editCurrentMap').style.display = 'none';
            }
            
            // Réinitialiser l'aperçu de la nouvelle image
            document.getElementById('editMapImage').value = '';
            document.getElementById('editMapPreview').style.display = 'none';
            
            var editModal = new bootstrap.Modal(document.getElementById('editWorldModal'));
            editModal.show();
        }
        
        function deleteWorld(worldId, worldName) {
            document.getElementById('deleteWorldId').value = worldId;
            document.getElementById('deleteWorldName').textContent = worldName;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteWorldModal'));
            deleteModal.show();
        }
        
        // Fonction pour ouvrir la carte en plein écran
        function openMapFullscreen(mapUrl, worldName) {
            document.getElementById('fullscreenMapImg').src = mapUrl;
            document.getElementById('fullscreenMapTitle').textContent = 'Carte de ' + worldName;
            
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
                        <i class="fas fa-map me-2"></i>Carte du Monde
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
</body>
</html>
