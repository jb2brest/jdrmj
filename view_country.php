<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

$page_title = "Détails du Pays";
$current_page = "manage_worlds"; // Pour garder le bouton "Mondes" actif dans la navbar

requireLogin();
User::requireDMOrAdmin();

$country_id = (int)($_GET['id'] ?? 0);
if ($country_id === 0) {
    header('Location: manage_worlds.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer le pays via la classe Pays
$pays = Pays::findById($country_id);

if (!$pays) {
    header('Location: manage_worlds.php?error=country_not_found');
    exit();
}

// Vérifier que l'utilisateur a le droit d'accéder à ce pays
$monde = $pays->getMonde();
if (!$monde || $monde['created_by'] != $user_id) {
    header('Location: manage_worlds.php?error=access_denied');
    exit();
}

$success_message = '';
$error_message = '';


// Fonction helper pour tronquer le texte


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
                        $region = new Region([
                            'name' => $name,
                            'description' => $description,
                            'map_url' => $map_url,
                            'country_id' => $country_id
                        ]);
                        $region->save();
                        $success_message = "Région '$name' créée avec succès.";
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
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
                // Récupérer l'URL actuelle via la classe Region
                $current_region = Region::findById($region_id);
                $map_url = $current_region ? $current_region->getMapUrl() : '';
                
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
                        $region = Region::findById($region_id);
                        if ($region && $region->getCountryId() == $country_id) {
                            $region->setName($name);
                            $region->setDescription($description);
                            $region->setMapUrl($map_url);
                            $region->save();
                            $success_message = "Région '$name' mise à jour avec succès.";
                        } else {
                            $error_message = "Région non trouvée.";
                        }
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                }
            }
            break;
            
        case 'update_country':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom du pays est requis.";
            } else {
                // Récupérer l'URL actuelle
                $current_map_url = $pays->getMapUrl();
                $map_url = $current_map_url;
                
                // Gérer l'upload de la nouvelle carte si un fichier est fourni
                if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCountryImage($_FILES['map_image'], 'map');
                    if (!$uploadResult['success']) {
                        $error_message = $uploadResult['error'];
                    } else {
                        // Supprimer l'ancienne carte si elle existe
                        if (!empty($current_map_url) && file_exists($current_map_url)) {
                            unlink($current_map_url);
                        }
                        $map_url = $uploadResult['file_path'];
                    }
                }
                
                if (empty($error_message)) {
                    try {
                        $pays->setName($name);
                        $pays->setDescription($description);
                        $pays->setMapUrl($map_url);
                        // Pas de changement de world_id ici, on garde le même
                        
                        if ($pays->save()) {
                            $success_message = "Pays mis à jour avec succès.";
                            // Recharger l'objet pays pour afficher les nouvelles données
                            $pays = Pays::findById($country_id);
                        } else {
                            $error_message = "Erreur lors de la mise à jour du pays.";
                        }
                    } catch (Exception $e) {
                        $error_message = $e->getMessage();
                    }
                }
            }
            break;

        case 'delete_region':
            $region_id = (int)($_POST['region_id'] ?? 0);
            
            try {
                $region = Region::findById($region_id);
                if ($region && $region->getCountryId() == $country_id) {
                    $region->delete();
                    $success_message = "Région supprimée avec succès.";
                } else {
                    $error_message = "Région non trouvée.";
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            break;
    }
}

// Récupérer les régions du pays via la classe Pays
$regions = $pays->getRegions();

// Récupérer tous les PNJs et monstres du monde
$monde = $pays->getMonde();
$world_id = $monde['id'];

// Récupérer les PNJs et monstres du monde (si les tables existent)
$all_npcs = [];
$all_monsters = [];

try {
    $pdo = getPDO();
    
    // Vérifier si la table npcs existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'npcs'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name as country_name, r.name as region_name, p.title as place_name
            FROM npcs n
            LEFT JOIN places p ON n.place_id = p.id
            LEFT JOIN regions r ON p.region_id = r.id
            LEFT JOIN countries c ON r.country_id = c.id
            WHERE n.world_id = ?
            ORDER BY c.name, r.name, p.title, n.name
        ");
        $stmt->execute([$world_id]);
        $all_npcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Vérifier si la table monsters existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'monsters'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT m.*, c.name as country_name, r.name as region_name, p.title as place_name
            FROM monsters m
            LEFT JOIN places p ON m.place_id = p.id
            LEFT JOIN regions r ON p.region_id = r.id
            LEFT JOIN countries c ON r.country_id = c.id
            WHERE m.world_id = ?
            ORDER BY c.name, r.name, p.title, m.name
        ");
        $stmt->execute([$world_id]);
        $all_monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur, initialiser avec des tableaux vides
    $all_npcs = [];
    $all_monsters = [];
}

// Récupérer les données pour la cartographie interrégionale
// Utiliser les nouvelles méthodes de la classe Pays
$country_accesses = $pays->getInterRegionAccesses();
$external_places = $pays->getExternalPlaces();

// Filtrer les PNJs et monstres pour ne garder que ceux du pays actuel
// Utiliser le champ country_name qui est déjà inclus dans les résultats de la classe Monde
$country_npcs = array_filter($all_npcs, function($npc) use ($pays) {
    return $npc['country_name'] === $pays->getName();
});

$country_monsters = array_filter($all_monsters, function($monster) use ($pays) {
    return $monster['country_name'] === $pays->getName();
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pays->getName()); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

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
                                <?php echo htmlspecialchars($pays->getName()); ?>
                                <a href="print_sheet.php?type=country&id=<?php echo $country_id; ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-2" title="Imprimer la carte">
                                    <i class="fas fa-print"></i>
                                </a>
                            </h1>
                            <p class="text-muted mb-1">
                                <i class="fas fa-globe-americas me-1"></i>
                                Monde: <?php echo htmlspecialchars($pays->getWorldName()); ?>
                            </p>
                            <?php if (!empty($pays->getDescription())): ?>
                                <p class="text-muted mb-3"><?php echo nl2br(htmlspecialchars($pays->getDescription())); ?></p>
                            <?php endif; ?>
                            <div class="d-flex gap-3">
                                <a href="view_world.php?id=<?php echo (int)$pays->getWorldId(); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour au monde
                                </a>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editCountryModal">
                                    <i class="fas fa-edit me-1"></i>Modifier le pays
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($pays->getMapUrl())): ?>
                            <div class="text-end">
                                <img src="<?php echo htmlspecialchars($pays->getMapUrl()); ?>" 
                                     alt="Carte de <?php echo htmlspecialchars($pays->getName()); ?>" 
                                     class="img-fluid rounded cursor-pointer" 
                                     style="max-height: 200px; max-width: 300px;"
                                     onclick="openMapFullscreen('<?php echo htmlspecialchars($pays->getMapUrl()); ?>', '<?php echo htmlspecialchars($pays->getName()); ?>')"
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
            <div class="btn-group">
                <?php if (!empty($regions) && (!empty($country_accesses) || !empty($external_places))): ?>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#cartographyModal">
                        <i class="fas fa-map me-2"></i>Cartographie
                    </button>
                <?php endif; ?>
                <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#createRegionModal">
                    <i class="fas fa-plus me-2"></i>Nouvelle Région
                </button>
            </div>
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
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la région <?php echo htmlspecialchars($region['name']); ?> ? Tous les pièces associés seront également supprimés.');">
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

    <!-- Section PNJs et Monstres -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>PNJs et Monstres du Pays</h5>
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
                                foreach (array_merge($country_npcs, $country_monsters) as $entity) {
                                    $place_key = $entity['place_name'] . '|' . $entity['region_name'];
                                    if (!in_array($place_key, $unique_places)) {
                                        $unique_places[] = $place_key;
                                        $location_display = $entity['place_name'];
                                        if ($entity['region_name']) {
                                            $location_display .= ' (' . $entity['region_name'] . ')';
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
                                        Pièce <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_entities = array_merge($country_npcs, $country_monsters);
                                if (empty($all_entities)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Aucun PNJ ou monstre trouvé</h5>
                                            <p class="text-muted">Les PNJs et monstres apparaîtront ici une fois qu'ils seront ajoutés aux pièces de ce pays.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_entities as $entity): ?>
                                        <tr class="entity-row" 
                                            data-type="<?php echo htmlspecialchars($entity['type']); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($entity['name'])); ?>"
                                            data-class="<?php echo htmlspecialchars(strtolower($entity['class_name'] ?? $entity['type'] ?? '')); ?>"
                                            data-race="<?php echo htmlspecialchars(strtolower($entity['race_name'] ?? $entity['size'] ?? '')); ?>"
                                            data-location="<?php echo htmlspecialchars(strtolower($entity['place_name'] . ' ' . ($entity['region_name'] ?? ''))); ?>"
                                            data-place-key="<?php echo htmlspecialchars($entity['place_name'] . '|' . ($entity['region_name'] ?? '')); ?>">
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
                                                    <?php if ($entity['region_name']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($entity['region_name']); ?></small>
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
                                                        <a href="view_monster.php?id=<?php echo (int)($entity['monster_id'] ?? 0); ?>" 
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

<!-- Modal Modifier Pays -->
<div class="modal fade" id="editCountryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le pays</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_country">
                    
                    <div class="mb-3">
                        <label for="editCountryName" class="form-label">Nom du pays *</label>
                        <input type="text" class="form-control" id="editCountryName" name="name" value="<?php echo htmlspecialchars($pays->getName()); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCountryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCountryDescription" name="description" rows="5"><?php echo htmlspecialchars($pays->getDescription()); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCountryMap" class="form-label">Carte du pays</label>
                        <input type="file" class="form-control" id="editCountryMap" name="map_image" accept="image/*">
                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <?php if ($pays->getMapUrl()): ?>
                                    <label class="form-label">Carte actuelle:</label>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($pays->getMapUrl()); ?>" class="img-fluid rounded" style="max-height: 150px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div id="editCountryMapPreview" style="display: none;">
                                    <label class="form-label">Nouvelle carte:</label>
                                    <div>
                                        <img id="editCountryMapPreviewImg" src="" class="img-fluid rounded" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Gestion de l'aperçu d'image pour l'édition du pays
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

    // Gestion de l'aperçu d'image pour la création de région
    function openMapFullscreen(mapUrl, regionName) {
        document.getElementById('fullscreenMapImg').src = mapUrl;
        document.getElementById('fullscreenMapTitle').textContent = 'Carte de ' + regionName;
        
        var fullscreenModal = new bootstrap.Modal(document.getElementById('fullscreenMapModal'));
        fullscreenModal.show();
    }

    // Gestion de l'aperçu d'image pour la création de région

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

    // === CARTographie INTERRÉGIONALE ===
    const cartographyData = {
        regions: <?php echo json_encode($regions) ?>,
        externalPlaces: <?php echo json_encode($external_places) ?>,
        accesses: <?php echo json_encode($country_accesses) ?>,
        currentCountryId: <?php echo $country_id ?>
    };

    let placePositions = {};
    let isDragging = false;
    let draggedElement = null;
    let dragOffset = { x: 0, y: 0 };

    let cartographyInitialized = false;
    
    function generateCartography() {
        console.log('generateCartography() called');
        const canvas = document.getElementById('cartographyCanvas');
        if (!canvas) {
            console.log('Canvas not found!');
            return;
        }
        
        // Éviter les appels multiples
        if (cartographyInitialized) {
            console.log('Cartography already initialized, skipping...');
            return;
        }
        cartographyInitialized = true;
        
        console.log('Canvas dimensions:', canvas.offsetWidth, 'x', canvas.offsetHeight);
        
        // Nettoyer le canvas
        canvas.innerHTML = '';
        
        const width = canvas.offsetWidth;
        const height = canvas.offsetHeight;
        const margin = 50;
        const availableWidth = width - (margin * 2);
        const availableHeight = height - (margin * 2);
        
        // Réinitialiser les positions
        placePositions = {};
        console.log('Place positions reset');
        
        // Récupérer toutes les régions (pour la cartographie de pays)
        const allPlaces = [];
        
        // Ajouter les régions du pays
        cartographyData.regions.forEach(region => {
            allPlaces.push({
                id: 'region_' + region.id,
                title: region.name,
                isRegion: true,
                regionId: region.id
            });
        });
        
        // Ajouter les régions externes (si des accès vont vers d'autres pays)
        cartographyData.externalPlaces.forEach(place => {
            // Vérifier si cette région externe n'est pas déjà ajoutée
            const existingRegion = allPlaces.find(p => p.regionId === place.region_id);
            if (!existingRegion) {
                allPlaces.push({
                    id: 'region_' + place.region_id,
                    title: place.region_name,
                    isRegion: true,
                    regionId: place.region_id,
                    countryName: place.country_name
                });
            }
        });
        
        if (allPlaces.length === 0) {
            console.log('No places to position');
            return;
        }
        
        console.log('Regions to position:', allPlaces.length);
        
        // Calculer les positions des régions en cercle
        allPlaces.forEach((place, index) => {
            console.log('Positioning region:', place.title, 'at index', index);
            const angle = (index * 2 * Math.PI) / Math.max(allPlaces.length, 1);
            const x = margin + (availableWidth / 2) + (Math.cos(angle) * (availableWidth / 3));
            const y = margin + (availableHeight / 2) + (Math.sin(angle) * (availableHeight / 3));
            
            const initialPos = { x, y };
            const adjustedPos = adjustPosition(initialPos, placePositions);
            
            placePositions[place.id] = { 
                x: adjustedPos.x, 
                y: adjustedPos.y, 
                place, 
                isExternal: place.countryName !== undefined, // Région externe si elle a un countryName
                isDragging: false
            };
        });
        
        // Dessiner les connexions
        redrawConnections();
        
        // Dessiner les pièces
        console.log('Final place positions:', Object.keys(placePositions));
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
            if (isExternal && place.regionName) {
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
                regionLabel.textContent = '(' + place.regionName + ')';
                placeElement.appendChild(regionLabel);
            }
            
            placeElement.appendChild(label);
            canvas.appendChild(placeElement);
        });
    }

    function redrawConnections() {
        const canvas = document.getElementById('cartographyCanvas');
        if (!canvas) return;
        
        // Supprimer les anciennes connexions
        const existingConnections = canvas.querySelectorAll('.connection-line');
        existingConnections.forEach(conn => conn.remove());
        
        // Dessiner les accès entre régions
        console.log('Drawing connections for', cartographyData.accesses.length, 'accesses');
        cartographyData.accesses.forEach(access => {
            console.log('Processing access:', access.name, 'from region', access.from_region_id, 'to region', access.to_region_id);
            
            // Trouver les positions des régions connectées
            let fromPos = null;
            let toPos = null;
            
            // Chercher par region_id (logique pour la cartographie de pays)
            fromPos = placePositions['region_' + access.from_region_id];
            toPos = placePositions['region_' + access.to_region_id];
            
            console.log('From position (region):', fromPos ? 'FOUND' : 'NOT FOUND', 'region_' + access.from_region_id);
            console.log('To position (region):', toPos ? 'FOUND' : 'NOT FOUND', 'region_' + access.to_region_id);
            
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

    function detectCollision(pos1, pos2, minDistance = 80) {
        const dx = pos1.x - pos2.x;
        const dy = pos1.y - pos2.y;
        return Math.sqrt(dx * dx + dy * dy) < minDistance;
    }

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

    // Fonctions de glisser-déposer
    function startDrag(event) {
        if (event.target.dataset.placeId) {
            isDragging = true;
            draggedElement = event.target.dataset.placeId;
            placePositions[draggedElement].isDragging = true;
            
            const rect = event.target.getBoundingClientRect();
            dragOffset.x = event.clientX - rect.left;
            dragOffset.y = event.clientY - rect.top;
            
            event.target.style.cursor = 'grabbing';
        }
    }

    function drag(event) {
        if (!isDragging || !draggedElement) return;
        
        const canvas = document.getElementById('cartographyCanvas');
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        placePositions[draggedElement].x = x - dragOffset.x;
        placePositions[draggedElement].y = y - dragOffset.y;
        
        // Vérifier les limites
        placePositions[draggedElement].x = Math.max(30, Math.min(canvas.offsetWidth - 30, placePositions[draggedElement].x));
        placePositions[draggedElement].y = Math.max(30, Math.min(canvas.offsetHeight - 30, placePositions[draggedElement].y));
        
        // Mettre à jour la position de l'élément DOM
        const element = canvas.querySelector(`[data-place-id="${draggedElement}"]`);
        if (element) {
            element.style.left = (placePositions[draggedElement].x - 15) + 'px';
            element.style.top = (placePositions[draggedElement].y - 15) + 'px';
        }
        
        redrawConnections();
    }

    function stopDrag(event) {
        if (isDragging && draggedElement) {
            placePositions[draggedElement].isDragging = false;
            
            const element = document.querySelector(`[data-place-id="${draggedElement}"]`);
            if (element) {
                element.style.cursor = 'move';
            }
        }
        isDragging = false;
        draggedElement = null;
    }

    // Initialiser la cartographie quand le modal s'ouvre
    const cartographyModal = document.getElementById('cartographyModal');
    if (cartographyModal) {
        cartographyModal.addEventListener('shown.bs.modal', function () {
            console.log('Modal cartographie ouvert');
            setTimeout(() => {
                console.log('Génération de la cartographie...');
                generateCartography();
                
                const canvas = document.getElementById('cartographyCanvas');
                if (canvas) {
                    console.log('Canvas trouvé, ajout des événements');
                    
                    // Ajouter les événements de glisser-déposer aux éléments
                    canvas.addEventListener('mousedown', startDrag);
                    canvas.addEventListener('mousemove', drag);
                    canvas.addEventListener('mouseup', stopDrag);
                    canvas.addEventListener('mouseleave', stopDrag);
                    
                    // Support tactile
                    canvas.addEventListener('touchstart', (e) => {
                        e.preventDefault();
                        const touch = e.touches[0];
                        const mouseEvent = new MouseEvent('mousedown', {
                            clientX: touch.clientX,
                            clientY: touch.clientY
                        });
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    canvas.addEventListener('touchmove', (e) => {
                        e.preventDefault();
                        const touch = e.touches[0];
                        const mouseEvent = new MouseEvent('mousemove', {
                            clientX: touch.clientX,
                            clientY: touch.clientY
                        });
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    canvas.addEventListener('touchend', (e) => {
                        e.preventDefault();
                        const mouseEvent = new MouseEvent('mouseup', {});
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    console.log('Événements de glisser-déposer attachés');
                } else {
                    console.error('Canvas non trouvé');
                }
            }, 100);
        });
    } else {
        console.error('Modal cartographie non trouvé');
    }

    // Initialisation alternative au cas où l'événement ne se déclenche pas
    if (cartographyModal) {
        cartographyModal.addEventListener('show.bs.modal', function () {
            console.log('Modal cartographie en cours d\'ouverture');
        });

        // Initialisation de secours - forcer la génération après un délai plus long
        cartographyModal.addEventListener('shown.bs.modal', function () {
            setTimeout(() => {
                const canvas = document.getElementById('cartographyCanvas');
                if (canvas && Object.keys(placePositions).length === 0) {
                    console.log('Initialisation de secours - génération forcée');
                    generateCartography();
                }
            }, 500);
        });
    }

    // Ignorer les erreurs 404 d'images pour éviter qu'elles bloquent le JavaScript
    window.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            console.log('Image manquante ignorée:', e.target.src);
            e.preventDefault();
            return false;
        }
    }, true);

    // Initialisation forcée au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé, initialisation de la cartographie');
        
        // Attendre que Bootstrap soit chargé
        setTimeout(() => {
            const modal = document.getElementById('cartographyModal');
            if (modal) {
                console.log('Modal trouvé, ajout des événements');
                
                // Forcer l'ajout des événements
                modal.addEventListener('shown.bs.modal', function () {
                    console.log('Modal ouvert (événement forcé)');
                    setTimeout(() => {
                        console.log('Génération forcée de la cartographie');
                        generateCartography();
                    }, 100);
                });
            } else {
                console.error('Modal non trouvé au chargement');
            }
        }, 1000);
    });
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

<!-- Modal Cartographie Interrégionale -->
<div class="modal fade" id="cartographyModal" tabindex="-1" aria-labelledby="cartographyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cartographyModalLabel">
                    <i class="fas fa-map me-2"></i>Cartographie Interrégionale - <?php echo htmlspecialchars($pays->getName()); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="position-relative" style="height: 600px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                            <div id="cartographyCanvas" style="width: 100%; height: 100%; position: relative; cursor: grab;"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Légende</h6>
                            <div class="small">
                                <div class="mb-2">
                                    <span class="badge bg-primary me-2">●</span>
                                    <strong>Région de ce pays</strong>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-secondary me-2">●</span>
                                    <strong>Région d'un autre pays</strong>
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
                            <strong>Interaction :</strong> Vous pouvez déplacer les pièces en les faisant glisser pour une meilleure visibilité.
                        </div>
                        
                        <?php if (!empty($country_accesses)): ?>
                            <div class="mb-3">
                                <h6><i class="fas fa-route me-2"></i>Accès Interrégionaux</h6>
                                <div class="small" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($country_accesses as $access): ?>
                                        <div class="mb-2 p-2 border rounded">
                                            <div class="fw-bold"><?php echo htmlspecialchars($access->name); ?></div>
                                            <div class="text-muted">
                                                <?php echo htmlspecialchars($access->from_place_name); ?> 
                                                (<?php echo htmlspecialchars($access->from_region_name); ?>)
                                                <i class="fas fa-arrow-right mx-1"></i>
                                                <?php echo htmlspecialchars($access->to_place_name); ?>
                                                (<?php echo htmlspecialchars($access->to_region_name); ?>)
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
                <button type="button" class="btn btn-secondary" onclick="generateCartography()">
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
</body>
</html>
