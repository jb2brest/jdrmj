<?php
require_once 'classes/init.php';
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

// Récupérer la région via la classe Region
$region = Region::findById($region_id);

if (!$region || $region->getMonde()->getCreatedBy() != $user_id) {
    header('Location: manage_worlds.php?error=region_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Obtenir l'instance PDO
$pdo = getPDO();

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
                        $stmt = $pdo->prepare("INSERT INTO places (title, notes, map_url, region_id, country_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $notes, $map_url, $region_id, $region['country_id']]);
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

// Récupérer les lieux de la région via la classe Region
$places = $region->getPlaces();

// Récupérer tous les PNJs de la région (via la hiérarchie région → lieux)
$stmt = $pdo->prepare("
    SELECT 
        pn.id,
        pn.name,
        pn.description,
        pn.profile_photo,
        pn.is_visible,
        pn.is_identified,
        c.name AS character_name,
        c.profile_photo AS character_profile_photo,
        cl.name AS class_name,
        r.name AS race_name,
        pl.title AS place_name,
        'PNJ' AS type
    FROM place_npcs pn
    JOIN places pl ON pn.place_id = pl.id
    LEFT JOIN characters c ON pn.npc_character_id = c.id
    LEFT JOIN classes cl ON c.class_id = cl.id
    LEFT JOIN races r ON c.race_id = r.id
    WHERE pl.region_id = ? AND pn.monster_id IS NULL
    ORDER BY pn.name ASC
");
$stmt->execute([$region_id]);
$region_npcs = $stmt->fetchAll();

// Récupérer tous les monstres de la région (via la hiérarchie région → lieux)
$stmt = $pdo->prepare("
    SELECT 
        pn.id,
        pn.name,
        pn.description,
        pn.profile_photo,
        pn.is_visible,
        pn.is_identified,
        pn.quantity,
        pn.current_hit_points,
        dm.name AS monster_name,
        dm.type,
        dm.size,
        dm.challenge_rating,
        dm.hit_points,
        dm.armor_class,
        pl.title AS place_name,
        'Monstre' AS type
    FROM place_npcs pn
    JOIN places pl ON pn.place_id = pl.id
    JOIN dnd_monsters dm ON pn.monster_id = dm.id
    WHERE pl.region_id = ? AND pn.monster_id IS NOT NULL
    ORDER BY pn.name ASC
");
$stmt->execute([$region_id]);
$region_monsters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($region->getName()); ?> - JDR 4 MJ</title>
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
                            <label for="locationFilter" class="form-label">Filtrer par lieu :</label>
                            <select class="form-select" id="locationFilter">
                                <option value="">Tous les lieux</option>
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
                                        Lieu <i class="fas fa-sort ms-1"></i>
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
                                            <p class="text-muted">Les PNJs et monstres apparaîtront ici une fois qu'ils seront ajoutés aux lieux de cette région.</p>
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
