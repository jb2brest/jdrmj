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

// Récupérer les données pour la cartographie inter-pays
$world_accesses = $monde->getInterCountryAccesses();
$external_countries = $monde->getExternalCountries();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($monde->getName()); ?> - JDR 4 MJ</title>
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
    <?php include_once 'includes/navbar.php'; ?>

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
                                    <?php if (!empty($countries)): ?>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#worldCartographyModal">
                                            <i class="fas fa-map me-2"></i>Cartographie
                                        </button>
                                    <?php endif; ?>
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
                                                            <?php if (!empty($entity['npc_character_id'])): ?>
                                                                <a href="view_character.php?id=<?php echo (int)$entity['npc_character_id']; ?>" 
                                                                   class="btn btn-outline-info btn-sm" title="Voir le PNJ">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="btn btn-outline-secondary btn-sm disabled" title="PNJ sans personnage associé">
                                                                    <i class="fas fa-eye-slash"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if (!empty($entity['monster_id'])): ?>
                                                                <a href="view_monster.php?id=<?php echo (int)$entity['id']; ?>" 
                                                                   class="btn btn-outline-info btn-sm" title="Voir le monstre">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="btn btn-outline-secondary btn-sm disabled" title="Monstre sans fiche associée">
                                                                    <i class="fas fa-eye-slash"></i>
                                                                </span>
                                                            <?php endif; ?>
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

    <!-- Modal Cartographie Inter-pays -->
    <div class="modal fade" id="worldCartographyModal" tabindex="-1" aria-labelledby="worldCartographyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="worldCartographyModalLabel">
                        <i class="fas fa-map me-2"></i>Cartographie Inter-pays - <?php echo htmlspecialchars($monde->getName()); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="position-relative" style="height: 600px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                                <div id="worldCartographyCanvas" style="width: 100%; height: 100%; position: relative; cursor: grab;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6><i class="fas fa-info-circle me-2"></i>Légende</h6>
                                <div class="small">
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2">●</span>
                                        <strong>Pays de ce monde</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-secondary me-2">●</span>
                                        <strong>Pays d'un autre monde</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-success me-2">━━━</span>
                                        <strong>Accès ouvert</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-warning me-2">━━━</span>
                                        <strong>Accès fermé</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-danger me-2">━━━</span>
                                        <strong>Accès piégé</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-secondary me-2">┅┅┅</span>
                                        <strong>Accès caché</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info small">
                                <i class="fas fa-mouse-pointer me-2"></i>
                                <strong>Interaction :</strong> Vous pouvez déplacer les pays en les faisant glisser pour une meilleure visibilité.
                            </div>
                            
                            <?php if (!empty($world_accesses)): ?>
                                <div class="mb-3">
                                    <h6><i class="fas fa-route me-2"></i>Accès Inter-pays</h6>
                                    <div class="small" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($world_accesses as $access): ?>
                                            <div class="mb-2 p-2 border rounded">
                                                <div class="fw-bold"><?php echo htmlspecialchars($access->name); ?></div>
                                                <div class="text-muted">
                                                    <?php echo htmlspecialchars($access->from_place_name); ?> 
                                                    (<?php echo htmlspecialchars($access->from_country_name); ?>)
                                                    <i class="fas fa-arrow-right mx-1"></i>
                                                    <?php echo htmlspecialchars($access->to_place_name); ?>
                                                    (<?php echo htmlspecialchars($access->to_country_name); ?>)
                                                </div>
                                                <div class="small">
                                                    <?php if (!$access->is_visible): ?>
                                                        <span class="badge bg-secondary">Caché</span>
                                                    <?php elseif ($access->is_trapped): ?>
                                                        <span class="badge bg-danger">Piégé</span>
                                                    <?php elseif ($access->is_open): ?>
                                                        <span class="badge bg-success">Ouvert</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Fermé</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="generateWorldCartography()">
                        <i class="fas fa-sync-alt me-2"></i>Réorganiser
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // === CARTographie INTER-PAYS ===
        const worldCartographyData = {
            countries: <?php echo json_encode(array_map(function($country) { return $country->toArray(); }, $countries)) ?>,
            externalCountries: <?php echo json_encode($external_countries) ?>,
            accesses: <?php echo json_encode($world_accesses) ?>,
            currentWorldId: <?php echo $world_id ?>
        };

        let worldPlacePositions = {};
        let worldIsDragging = false;
        let worldDraggedElement = null;
        let worldDragOffset = { x: 0, y: 0 };
        let worldCartographyInitialized = false;

        function generateWorldCartography() {
            console.log('generateWorldCartography() called');
            const canvas = document.getElementById('worldCartographyCanvas');
            if (!canvas) {
                console.log('Canvas not found!');
                return;
            }
            
            // Éviter les appels multiples
            if (worldCartographyInitialized) {
                console.log('World cartography already initialized, skipping...');
                return;
            }
            worldCartographyInitialized = true;
            
            console.log('Canvas dimensions:', canvas.offsetWidth, 'x', canvas.offsetHeight);
            
            // Nettoyer le canvas
            canvas.innerHTML = '';
            
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            const margin = 50;
            const availableWidth = width - (margin * 2);
            const availableHeight = height - (margin * 2);
            
            // Réinitialiser les positions
            worldPlacePositions = {};
            console.log('Place positions reset');
            
            // Récupérer tous les pays (pour la cartographie de monde)
            const allPlaces = [];
            
            // Ajouter les pays du monde
            worldCartographyData.countries.forEach(country => {
                allPlaces.push({
                    id: 'country_' + country.id,
                    title: country.name,
                    isCountry: true,
                    countryId: country.id
                });
            });
            
            // Ajouter les pays externes (si des accès vont vers d'autres mondes)
            worldCartographyData.externalCountries.forEach(country => {
                // Vérifier si ce pays externe n'est pas déjà ajouté
                const existingCountry = allPlaces.find(p => p.countryId === country.id);
                if (!existingCountry) {
                    allPlaces.push({
                        id: 'country_' + country.id,
                        title: country.name,
                        isCountry: true,
                        countryId: country.id,
                        worldName: country.world_name
                    });
                }
            });
            
            if (allPlaces.length === 0) {
                console.log('No countries to position');
                return;
            }
            
            console.log('Countries to position:', allPlaces.length);
            
            // Calculer les positions des pays en cercle
            allPlaces.forEach((place, index) => {
                console.log('Positioning country:', place.title, 'at index', index);
                const angle = (index * 2 * Math.PI) / Math.max(allPlaces.length, 1);
                const x = margin + (availableWidth / 2) + (Math.cos(angle) * (availableWidth / 3));
                const y = margin + (availableHeight / 2) + (Math.sin(angle) * (availableHeight / 3));
                
                const initialPos = { x, y };
                const adjustedPos = adjustWorldPosition(initialPos, worldPlacePositions);
                
                worldPlacePositions[place.id] = { 
                    x: adjustedPos.x, 
                    y: adjustedPos.y, 
                    place, 
                    isExternal: place.worldName !== undefined, // Pays externe s'il a un worldName
                    isDragging: false
                };
            });
            
            // Dessiner les connexions
            redrawWorldConnections();
            
            // Dessiner les pays
            console.log('Final place positions:', Object.keys(worldPlacePositions));
            Object.values(worldPlacePositions).forEach(({ x, y, place, isExternal }) => {
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
                placeElement.title = place.title + (isExternal ? ' (Autre monde)' : '') + ' - Cliquer et glisser pour déplacer';
                placeElement.dataset.placeId = place.id;
                
                // Ajouter le nom du pays
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
                
                // Ajouter le nom du monde pour les pays externes
                if (isExternal && place.worldName) {
                    const worldLabel = document.createElement('div');
                    worldLabel.style.position = 'absolute';
                    worldLabel.style.left = '35px';
                    worldLabel.style.top = '20px';
                    worldLabel.style.fontSize = '10px';
                    worldLabel.style.color = '#666';
                    worldLabel.style.backgroundColor = 'rgba(255,255,255,0.9)';
                    worldLabel.style.padding = '1px 4px';
                    worldLabel.style.borderRadius = '3px';
                    worldLabel.style.whiteSpace = 'nowrap';
                    worldLabel.style.pointerEvents = 'none';
                    worldLabel.style.zIndex = '11';
                    worldLabel.textContent = '(' + place.worldName + ')';
                    placeElement.appendChild(worldLabel);
                }
                
                placeElement.appendChild(label);
                canvas.appendChild(placeElement);
            });
        }

        function redrawWorldConnections() {
            const canvas = document.getElementById('worldCartographyCanvas');
            if (!canvas) return;
            
            // Supprimer les anciennes connexions
            const existingConnections = canvas.querySelectorAll('.connection-line');
            existingConnections.forEach(conn => conn.remove());
            
            // Dessiner les accès entre pays
            console.log('Drawing connections for', worldCartographyData.accesses.length, 'accesses');
            worldCartographyData.accesses.forEach(access => {
                console.log('Processing access:', access.name, 'from country', access.from_country_name, 'to country', access.to_country_name);
                
                // Trouver les positions des pays connectés
                let fromPos = null;
                let toPos = null;
                
                // Chercher par country_id (logique pour la cartographie de monde)
                Object.values(worldPlacePositions).forEach(({ place, x, y }) => {
                    if (place.title === access.from_country_name) {
                        fromPos = { x, y };
                    }
                    if (place.title === access.to_country_name) {
                        toPos = { x, y };
                    }
                });
                
                console.log('From position (country):', fromPos ? 'FOUND' : 'NOT FOUND', access.from_country_name);
                console.log('To position (country):', toPos ? 'FOUND' : 'NOT FOUND', access.to_country_name);
                
                console.log('Final positions - From:', fromPos, 'To:', toPos);
                
                if (fromPos && toPos) {
                    console.log('Drawing connection for access:', access.name);
                    // Créer une ligne SVG
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.style.position = 'absolute';
                    svg.style.top = '0';
                    svg.style.left = '0';
                    svg.style.width = '100%';
                    svg.style.height = '100%';
                    svg.style.pointerEvents = 'none';
                    svg.style.zIndex = '5';
                    svg.classList.add('connection-line');
                    
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', fromPos.x);
                    line.setAttribute('y1', fromPos.y);
                    line.setAttribute('x2', toPos.x);
                    line.setAttribute('y2', toPos.y);
                    
                    // Style selon le statut
                    if (!access.is_visible) {
                        line.setAttribute('stroke-dasharray', '5,5');
                        line.setAttribute('stroke', '#6c757d');
                    } else if (access.is_trapped) {
                        line.setAttribute('stroke', '#dc3545');
                    } else if (access.is_open) {
                        line.setAttribute('stroke', '#28a745');
                    } else {
                        line.setAttribute('stroke', '#ffc107');
                    }
                    
                    line.setAttribute('stroke-width', '3');
                    svg.appendChild(line);
                    canvas.appendChild(svg);
                } else {
                    console.log('Cannot draw connection - missing positions');
                }
            });
        }

        function adjustWorldPosition(newPos, existingPositions, minDistance = 80) {
            let adjustedPos = { ...newPos };
            let attempts = 0;
            const maxAttempts = 50;
            
            while (attempts < maxAttempts) {
                let hasCollision = false;
                for (const [id, pos] of Object.entries(existingPositions)) {
                    if (detectWorldCollision(adjustedPos, pos, minDistance)) {
                        hasCollision = true;
                        break;
                    }
                }
                if (!hasCollision) {
                    break;
                }
                const angle = Math.random() * 2 * Math.PI;
                adjustedPos.x += Math.cos(angle) * minDistance * 0.5;
                adjustedPos.y += Math.sin(angle) * minDistance * 0.5;
                attempts++;
            }
            return adjustedPos;
        }

        function detectWorldCollision(pos1, pos2, minDistance = 80) {
            const dx = pos1.x - pos2.x;
            const dy = pos1.y - pos2.y;
            return Math.sqrt(dx * dx + dy * dy) < minDistance;
        }

        // Initialiser la cartographie quand le modal s'ouvre
        document.getElementById('worldCartographyModal').addEventListener('shown.bs.modal', function () {
            setTimeout(() => {
                generateWorldCartography();
            }, 100);
        });
    </script>
</body>
</html>
