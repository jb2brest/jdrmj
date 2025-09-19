<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Monde";
$current_page = "view_world";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_worlds.php');
    exit();
}

$world_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer le monde
$stmt = $pdo->prepare("SELECT * FROM worlds WHERE id = ? AND created_by = ?");
$stmt->execute([$world_id, $user_id]);
$world = $stmt->fetch();

if (!$world) {
    header('Location: manage_worlds.php?error=world_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Fonction helper pour l'upload d'image de pays
function uploadCountryImage($file, $type = 'map') {
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
    $uploadDir = 'uploads/countries/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'country_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
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
        case 'create_country':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $map_url = '';
            $coat_of_arms_url = '';
            
            if (empty($name)) {
                $error_message = "Le nom du pays est requis.";
            } else {
                // Gérer l'upload de la carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCountryImage($_FILES['map_image'], 'map');
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                // Gérer l'upload du blason si un fichier est fourni
                if (empty($error_message) && isset($_FILES['coat_of_arms_image']) && $_FILES['coat_of_arms_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCountryImage($_FILES['coat_of_arms_image'], 'coat_of_arms');
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        $coat_of_arms_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO countries (name, description, map_url, coat_of_arms_url, world_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $description, $map_url, $coat_of_arms_url, $world_id]);
                        $success_message = "Pays '$name' créé avec succès.";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Un pays avec ce nom existe déjà dans ce monde.";
                        } else {
                            $error_message = "Erreur lors de la création du pays: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'update_country':
            $country_id = (int)($_POST['country_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom du pays est requis.";
            } else {
                // Récupérer les URLs actuelles
                $stmt = $pdo->prepare("SELECT map_url, coat_of_arms_url FROM countries WHERE id = ? AND world_id = ?");
                $stmt->execute([$country_id, $world_id]);
                $current_country = $stmt->fetch();
                $map_url = $current_country['map_url'] ?? '';
                $coat_of_arms_url = $current_country['coat_of_arms_url'] ?? '';
                
                // Gérer l'upload de la nouvelle carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCountryImage($_FILES['map_image'], 'map');
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
                
                // Gérer l'upload du nouveau blason si un fichier est fourni
                if (empty($error_message) && isset($_FILES['coat_of_arms_image']) && $_FILES['coat_of_arms_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCountryImage($_FILES['coat_of_arms_image'], 'coat_of_arms');
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        // Supprimer l'ancien blason s'il existe
                        if (!empty($coat_of_arms_url) && file_exists($coat_of_arms_url)) {
                            unlink($coat_of_arms_url);
                        }
                        $coat_of_arms_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE countries SET name = ?, description = ?, map_url = ?, coat_of_arms_url = ? WHERE id = ? AND world_id = ?");
                        $stmt->execute([$name, $description, $map_url, $coat_of_arms_url, $country_id, $world_id]);
                        if ($stmt->rowCount() > 0) {
                            $success_message = "Pays '$name' mis à jour avec succès.";
                        } else {
                            $error_message = "Pays non trouvé.";
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Un pays avec ce nom existe déjà dans ce monde.";
                        } else {
                            $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'delete_country':
            $country_id = (int)($_POST['country_id'] ?? 0);
            
            try {
                // Vérifier s'il y a des régions dans ce pays
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM regions WHERE country_id = ?");
                $stmt->execute([$country_id]);
                $region_count = $stmt->fetchColumn();
                
                if ($region_count > 0) {
                    $error_message = "Impossible de supprimer ce pays car il contient $region_count régions. Supprimez d'abord les régions.";
                } else {
                    // Récupérer les URLs des images avant suppression
                    $stmt = $pdo->prepare("SELECT map_url, coat_of_arms_url FROM countries WHERE id = ? AND world_id = ?");
                    $stmt->execute([$country_id, $world_id]);
                    $country = $stmt->fetch();
                    
                    // Supprimer le pays
                    $stmt = $pdo->prepare("DELETE FROM countries WHERE id = ? AND world_id = ?");
                    $stmt->execute([$country_id, $world_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Supprimer les images associées si elles existent
                        if (!empty($country['map_url']) && file_exists($country['map_url'])) {
                            unlink($country['map_url']);
                        }
                        if (!empty($country['coat_of_arms_url']) && file_exists($country['coat_of_arms_url'])) {
                            unlink($country['coat_of_arms_url']);
                        }
                        $success_message = "Pays supprimé avec succès.";
                    } else {
                        $error_message = "Pays non trouvé.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
            break;
    }
}

// Récupérer les pays du monde
$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM regions WHERE country_id = c.id) as region_count
    FROM countries c 
    WHERE c.world_id = ? 
    ORDER BY c.name");
$stmt->execute([$world_id]);
$countries = $stmt->fetchAll();

// Récupérer un pays spécifique pour l'édition
$edit_country = null;
if (isset($_GET['edit_country']) && is_numeric($_GET['edit_country'])) {
    $country_id = (int)$_GET['edit_country'];
    $stmt = $pdo->prepare("SELECT * FROM countries WHERE id = ? AND world_id = ?");
    $stmt->execute([$country_id, $world_id]);
    $edit_country = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($world['name']); ?> - JDR 4 MJ</title>
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

        <!-- En-tête du monde -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 class="mb-2">
                                    <i class="fas fa-globe-americas me-2"></i>
                                    <?php echo htmlspecialchars($world['name']); ?>
                                </h1>
                                <?php if (!empty($world['description'])): ?>
                                    <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($world['description'])); ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-3">
                                    <a href="manage_worlds.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Retour aux mondes
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="editWorld()">
                                        <i class="fas fa-edit me-1"></i>Modifier le monde
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($world['map_url'])): ?>
                                <div class="text-end">
                                    <img src="<?php echo htmlspecialchars($world['map_url']); ?>" 
                                         alt="Carte de <?php echo htmlspecialchars($world['name']); ?>" 
                                         class="img-fluid rounded cursor-pointer" 
                                         style="max-height: 200px; max-width: 300px;"
                                         onclick="openMapFullscreen('<?php echo htmlspecialchars($world['map_url']); ?>', '<?php echo htmlspecialchars($world['name']); ?>')"
                                         title="Cliquer pour voir en plein écran">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Pays -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-flag me-2"></i>Pays</h5>
                        <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#createCountryModal">
                            <i class="fas fa-plus me-1"></i>Nouveau Pays
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($countries)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun pays créé</h5>
                                <p class="text-muted">Commencez par créer le premier pays de votre monde.</p>
                                <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createCountryModal">
                                    <i class="fas fa-plus me-2"></i>Créer un pays
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($countries as $country): ?>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($country['name']); ?></h6>
                                                <?php if (!empty($country['description'])): ?>
                                                    <p class="card-text text-muted small"><?php echo nl2br(htmlspecialchars($country['description'])); ?></p>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marked-alt me-1"></i>
                                                        <?php echo $country['region_count']; ?> régions
                                                    </small>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_country.php?id=<?php echo (int)$country['id']; ?>" class="btn btn-outline-info btn-sm" title="Voir les régions">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button class="btn btn-outline-primary btn-sm" onclick="editCountry(<?php echo htmlspecialchars(json_encode($country)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteCountry(<?php echo $country['id']; ?>, '<?php echo htmlspecialchars($country['name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
        </div>
    </div>

    <!-- Modal Création Pays -->
    <div class="modal fade" id="createCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer un nouveau pays</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_country">
                        
                        <div class="mb-3">
                            <label for="createCountryName" class="form-label">Nom du pays *</label>
                            <input type="text" class="form-control" id="createCountryName" name="name" required placeholder="Ex: Gondor, Royaume du Nord...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="createCountryDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="createCountryDescription" name="description" rows="3" placeholder="Décrivez ce pays..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="createCountryMap" class="form-label">Carte du pays</label>
                            <input type="file" class="form-control" id="createCountryMap" name="map_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="createCountryMapPreview" class="mt-2" style="display: none;">
                                <img id="createCountryMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="createCountryCoatOfArms" class="form-label">Blason du pays</label>
                            <input type="file" class="form-control" id="createCountryCoatOfArms" name="coat_of_arms_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="createCountryCoatOfArmsPreview" class="mt-2" style="display: none;">
                                <img id="createCountryCoatOfArmsPreviewImg" src="" alt="Aperçu blason" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-brown">Créer le pays</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Pays -->
    <div class="modal fade" id="editCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le pays</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_country">
                        <input type="hidden" name="country_id" id="editCountryId">
                        
                        <div class="mb-3">
                            <label for="editCountryName" class="form-label">Nom du pays *</label>
                            <input type="text" class="form-control" id="editCountryName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCountryDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editCountryDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCountryMap" class="form-label">Carte du pays</label>
                            <input type="file" class="form-control" id="editCountryMap" name="map_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="editCountryMapPreview" class="mt-2" style="display: none;">
                                <img id="editCountryMapPreviewImg" src="" alt="Aperçu carte" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                            <div id="editCountryCurrentMap" class="mt-2" style="display: none;">
                                <label class="form-label">Carte actuelle:</label>
                                <img id="editCountryCurrentMapImg" src="" alt="Carte actuelle" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCountryCoatOfArms" class="form-label">Blason du pays</label>
                            <input type="file" class="form-control" id="editCountryCoatOfArms" name="coat_of_arms_image" accept="image/*">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                            <div id="editCountryCoatOfArmsPreview" class="mt-2" style="display: none;">
                                <img id="editCountryCoatOfArmsPreviewImg" src="" alt="Aperçu blason" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                            <div id="editCountryCurrentCoatOfArms" class="mt-2" style="display: none;">
                                <label class="form-label">Blason actuel:</label>
                                <img id="editCountryCurrentCoatOfArmsImg" src="" alt="Blason actuel" class="img-fluid rounded" style="max-height: 150px;">
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

    <!-- Modal Confirmation Suppression Pays -->
    <div class="modal fade" id="deleteCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le pays <strong id="deleteCountryName"></strong> ?</p>
                    <p class="text-danger"><small>Cette action est irréversible. Assurez-vous qu'aucune région n'est associée à ce pays.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete_country">
                        <input type="hidden" name="country_id" id="deleteCountryId">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'aperçu d'image pour la création de pays
        document.getElementById('createCountryMap').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('createCountryMapPreviewImg').src = e.target.result;
                    document.getElementById('createCountryMapPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('createCountryMapPreview').style.display = 'none';
            }
        });

        document.getElementById('createCountryCoatOfArms').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('createCountryCoatOfArmsPreviewImg').src = e.target.result;
                    document.getElementById('createCountryCoatOfArmsPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('createCountryCoatOfArmsPreview').style.display = 'none';
            }
        });

        // Gestion de l'aperçu d'image pour l'édition de pays
        document.getElementById('editCountryMap').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editCountryMapPreviewImg').src = e.target.result;
                    document.getElementById('editCountryMapPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('editCountryMapPreview').style.display = 'none';
            }
        });

        document.getElementById('editCountryCoatOfArms').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editCountryCoatOfArmsPreviewImg').src = e.target.result;
                    document.getElementById('editCountryCoatOfArmsPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('editCountryCoatOfArmsPreview').style.display = 'none';
            }
        });

        function editWorld() {
            // Rediriger vers la page de gestion des mondes avec l'ID en paramètre
            window.location.href = 'manage_worlds.php?edit=<?php echo $world_id; ?>';
        }
        
        function editCountry(country) {
            document.getElementById('editCountryId').value = country.id;
            document.getElementById('editCountryName').value = country.name;
            document.getElementById('editCountryDescription').value = country.description || '';
            
            // Afficher les images actuelles si elles existent
            if (country.map_url) {
                document.getElementById('editCountryCurrentMapImg').src = country.map_url;
                document.getElementById('editCountryCurrentMap').style.display = 'block';
            } else {
                document.getElementById('editCountryCurrentMap').style.display = 'none';
            }
            
            if (country.coat_of_arms_url) {
                document.getElementById('editCountryCurrentCoatOfArmsImg').src = country.coat_of_arms_url;
                document.getElementById('editCountryCurrentCoatOfArms').style.display = 'block';
            } else {
                document.getElementById('editCountryCurrentCoatOfArms').style.display = 'none';
            }
            
            // Réinitialiser les aperçus de nouvelles images
            document.getElementById('editCountryMap').value = '';
            document.getElementById('editCountryCoatOfArms').value = '';
            document.getElementById('editCountryMapPreview').style.display = 'none';
            document.getElementById('editCountryCoatOfArmsPreview').style.display = 'none';
            
            var editModal = new bootstrap.Modal(document.getElementById('editCountryModal'));
            editModal.show();
        }
        
        function deleteCountry(countryId, countryName) {
            document.getElementById('deleteCountryId').value = countryId;
            document.getElementById('deleteCountryName').textContent = countryName;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteCountryModal'));
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
