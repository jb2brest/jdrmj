<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Monde";
$current_page = "view_world";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_worlds.php');
    exit();
}

$world_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer le monde via la classe Monde
$monde = Monde::findById($world_id);

if (!$monde || $monde->getCreatedBy() != $user_id) {
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
                        $univers = getUnivers();
                        $pays = $univers->createPays($world_id, $name, $description, $map_url, $coat_of_arms_url);
                        $success_message = "Pays '$name' créé avec succès.";
                    } catch (Exception $e) {
                        $error_message = "Erreur lors de la création du pays: " . $e->getMessage();
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
                // Récupérer le pays via la classe Pays
                $pays = Pays::findById($country_id);
                if (!$pays || $pays->getWorldId() != $world_id) {
                    $error_message = "Pays non trouvé.";
                } else {
                    $map_url = $pays->getMapUrl();
                    $coat_of_arms_url = $pays->getCoatOfArmsUrl();
                
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
                            // Mettre à jour les propriétés du pays
                            $pays->setName($name);
                            $pays->setDescription($description);
                            $pays->setMapUrl($map_url);
                            $pays->setCoatOfArmsUrl($coat_of_arms_url);
                            
                            if ($pays->save()) {
                                $success_message = "Pays '$name' mis à jour avec succès.";
                            } else {
                                $error_message = "Erreur lors de la mise à jour du pays.";
                            }
                        } catch (Exception $e) {
                            $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
                        }
                    }
                }
            }
            break;
            
        case 'delete_country':
            $country_id = (int)($_POST['country_id'] ?? 0);
            
            try {
                // Récupérer le pays via la classe Pays
                $pays = Pays::findById($country_id);
                
                if ($pays && $pays->getWorldId() == $world_id) {
                    $pays->delete();
                    $success_message = "Pays supprimé avec succès.";
                } else {
                    $error_message = "Pays non trouvé.";
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            break;
    }
}

// Récupérer les pays du monde via la classe Monde
$countries = $monde->getCountries();

// Récupérer un pays spécifique pour l'édition
$edit_country = null;
if (isset($_GET['edit_country']) && is_numeric($_GET['edit_country'])) {
    $country_id = (int)$_GET['edit_country'];
    $edit_country = Pays::findById($country_id);
    if (!$edit_country || $edit_country->getWorldId() != $world_id) {
        $edit_country = null;
    }
}

// Récupérer tous les PNJs du monde via la classe Monde
$world_npcs = $monde->getNpcs();

// Récupérer tous les monstres du monde via la classe Monde
$world_monsters = $monde->getMonsters();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($monde->getName()); ?> - JDR 4 MJ</title>
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
                                    <?php echo htmlspecialchars($monde->getName()); ?>
                                </h1>
                                <?php if (!empty($monde->getDescription())): ?>
                                    <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($monde->getDescription())); ?></p>
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
                            <?php if (!empty($monde->getMapUrl())): ?>
                                <div class="text-end">
                                    <img src="<?php echo htmlspecialchars($monde->getMapUrl()); ?>" 
                                         alt="Carte de <?php echo htmlspecialchars($monde->getName()); ?>" 
                                         class="img-fluid rounded cursor-pointer" 
                                         style="max-height: 200px; max-width: 300px;"
                                         onclick="openMapFullscreen('<?php echo htmlspecialchars($monde->getMapUrl()); ?>', '<?php echo htmlspecialchars($monde->getName()); ?>')"
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
                                                <h6 class="card-title"><?php echo htmlspecialchars($country->getName()); ?></h6>
                                                <?php if (!empty($country->getDescription())): ?>
                                                    <p class="card-text text-muted small"><?php echo nl2br(htmlspecialchars($country->getDescription())); ?></p>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marked-alt me-1"></i>
                                                        <?php echo $country->getRegionCount(); ?> régions
                                                    </small>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_country.php?id=<?php echo (int)$country->getId(); ?>" class="btn btn-outline-info btn-sm" title="Voir les régions">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button class="btn btn-outline-primary btn-sm" onclick="editCountry(<?php echo htmlspecialchars(json_encode($country->toArray())); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteCountry(<?php echo $country->getId(); ?>, '<?php echo htmlspecialchars($country->getName()); ?>')">
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

        <!-- Section PNJs et Monstres -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>PNJs et Monstres du Monde</h5>
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
                                <label for="locationFilter" class="form-label">Filtrer par lieu :</label>
                                <select class="form-select" id="locationFilter">
                                    <option value="">Tous les lieux</option>
                                    <?php
                                    $unique_places = [];
                                    foreach (array_merge($world_npcs, $world_monsters) as $entity) {
                                        $place_key = $entity['place_name'] . '|' . $entity['country_name'] . '|' . $entity['region_name'];
                                        if (!in_array($place_key, $unique_places)) {
                                            $unique_places[] = $place_key;
                                            $location_display = $entity['place_name'];
                                            if ($entity['country_name']) {
                                                $location_display .= ' (' . $entity['country_name'];
                                                if ($entity['region_name']) {
                                                    $location_display .= ', ' . $entity['region_name'];
                                                }
                                                $location_display .= ')';
                                            }
                                            echo '<option value="' . htmlspecialchars($place_key) . '">' . htmlspecialchars($location_display) . '</option>';
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
                                            Lieu <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $all_entities = array_merge($world_npcs, $world_monsters);
                                    if (empty($all_entities)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun PNJ ou monstre trouvé</h5>
                                                <p class="text-muted">Les PNJs et monstres apparaîtront ici une fois qu'ils seront ajoutés aux lieux de ce monde.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_entities as $entity): ?>
                                            <tr class="entity-row" 
                                                data-type="<?php echo htmlspecialchars($entity['type']); ?>"
                                                data-name="<?php echo htmlspecialchars(strtolower($entity['name'])); ?>"
                                                data-class="<?php echo htmlspecialchars(strtolower($entity['class_name'] ?? $entity['type'] ?? '')); ?>"
                                                data-race="<?php echo htmlspecialchars(strtolower($entity['race_name'] ?? $entity['size'] ?? '')); ?>"
                                                data-location="<?php echo htmlspecialchars(strtolower($entity['place_name'] . ' ' . ($entity['country_name'] ?? '') . ' ' . ($entity['region_name'] ?? ''))); ?>"
                                                data-place-key="<?php echo htmlspecialchars($entity['place_name'] . '|' . ($entity['country_name'] ?? '') . '|' . ($entity['region_name'] ?? '')); ?>">
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
                                                        <?php if ($entity['country_name'] || $entity['region_name']): ?>
                                                            <br><small class="text-muted">
                                                                <?php if ($entity['country_name']): ?>
                                                                    <?php echo htmlspecialchars($entity['country_name']); ?>
                                                                    <?php if ($entity['region_name']): ?>
                                                                        , <?php echo htmlspecialchars($entity['region_name']); ?>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php endif; ?>
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
                                                            <a href="view_monster_sheet.php?id=<?php echo (int)$entity['monster_id']; ?>" 
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
            window.location.href = 'manage_worlds.php?edit=<?php echo $monde->getId(); ?>';
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
                
                // Filtre par lieu
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
