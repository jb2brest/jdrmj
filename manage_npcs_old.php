<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Initialiser les variables pour éviter les erreurs
$entities = [];
$worlds = [];
$countries = [];
$regions = [];
$places = [];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_npc':
            $npc_id = (int)($_POST['npc_id'] ?? 0);
            if ($npc_id > 0) {
                try {
                    $pdo = getPdo();
                    
                    // Vérifier que le PNJ appartient à l'utilisateur
                    $stmt = $pdo->prepare("
                        SELECT pn.id FROM place_npcs pn
                        JOIN places p ON pn.place_id = p.id
                        JOIN countries c ON p.country_id = c.id
                        JOIN worlds w ON c.world_id = w.id
                        WHERE pn.id = ? AND w.created_by = ?
                    ");
                    $stmt->execute([$npc_id, $user_id]);
                    
                    if ($stmt->fetch()) {
                        // Supprimer l'équipement du PNJ
                        $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE npc_id = ?");
                        $stmt->execute([$npc_id]);
                        
                        // Supprimer le PNJ
                        $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE id = ?");
                        $stmt->execute([$npc_id]);
                        
                        $success_message = "PNJ supprimé avec succès !";
                    } else {
                        $error_message = "PNJ non trouvé ou permissions insuffisantes.";
                    }
                } catch (Exception $e) {
                    $error_message = "Erreur lors de la suppression du PNJ.";
                }
            }
            break;
            
        case 'delete_monster':
            $monster_id = (int)($_POST['monster_id'] ?? 0);
            if ($monster_id > 0) {
                try {
                    $pdo = getPdo();
                    
                    // Vérifier que le monstre appartient à l'utilisateur
                    $stmt = $pdo->prepare("
                        SELECT pn.id FROM place_npcs pn
                        JOIN places p ON pn.place_id = p.id
                        JOIN countries c ON p.country_id = c.id
                        JOIN worlds w ON c.world_id = w.id
                        WHERE pn.id = ? AND w.created_by = ? AND pn.monster_id IS NOT NULL
                    ");
                    $stmt->execute([$monster_id, $user_id]);
                    
                    if ($stmt->fetch()) {
                        // Supprimer l'équipement du monstre (si la table existe)
                        try {
                            $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE monster_id = ?");
                            $stmt->execute([$monster_id]);
                        } catch (Exception $e) {
                            // Table peut ne pas exister, ignorer l'erreur
                        }
                        
                        // Supprimer le monstre de place_npcs
                        $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE id = ?");
                        $stmt->execute([$monster_id]);
                        
                        $success_message = "Monstre supprimé avec succès !";
                    } else {
                        $error_message = "Monstre non trouvé ou permissions insuffisantes.";
                    }
                } catch (Exception $e) {
                    $error_message = "Erreur lors de la suppression du monstre.";
                }
            }
            break;
    }
}

// Récupérer les filtres
$filter_type = $_GET['type'] ?? '';
$filter_world = $_GET['world'] ?? '';
$filter_country = $_GET['country'] ?? '';
$filter_region = $_GET['region'] ?? '';
$filter_place = $_GET['place'] ?? '';

// Récupérer tous les PNJ et Monstres de l'utilisateur dans une seule liste
try {
    $pdo = getPdo();
    
    // Construire la requête avec filtres
    $where_conditions = ["w.created_by = ?"];
    $params = [$user_id];
    
    if ($filter_type) {
        if ($filter_type === 'pnj') {
            $where_conditions[] = "pn.monster_id IS NULL";
        } elseif ($filter_type === 'monster') {
            $where_conditions[] = "pn.monster_id IS NOT NULL";
        }
    }
    
    if ($filter_world) {
        $where_conditions[] = "w.id = ?";
        $params[] = $filter_world;
    }
    
    if ($filter_country) {
        $where_conditions[] = "c.id = ?";
        $params[] = $filter_country;
    }
    
    if ($filter_region) {
        $where_conditions[] = "reg.id = ?";
        $params[] = $filter_region;
    }
    
    if ($filter_place) {
        $where_conditions[] = "p.id = ?";
        $params[] = $filter_place;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
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
            pn.monster_id,
            pn.npc_character_id,
            dm.name as monster_name,
            dm.type as monster_type,
            dm.challenge_rating,
            p.title as place_name,
            p.id as place_id,
            c.name as country_name,
            c.id as country_id,
            reg.name as region_name,
            reg.id as region_id,
            w.name as world_name,
            w.id as world_id,
            CASE 
                WHEN pn.monster_id IS NULL THEN 'PNJ'
                ELSE 'Monstre'
            END as entity_type
        FROM place_npcs pn
        LEFT JOIN dnd_monsters dm ON pn.monster_id = dm.id
        JOIN places p ON pn.place_id = p.id
        JOIN countries c ON p.country_id = c.id
        LEFT JOIN regions reg ON p.region_id = reg.id
        JOIN worlds w ON c.world_id = w.id
        WHERE $where_clause
        ORDER BY w.name, c.name, p.title, pn.name
    ");
    $stmt->execute($params);
    $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    
    // Récupérer les données pour les filtres
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name, w.id as world_id, w.name as world_name
        FROM countries c
        JOIN worlds w ON c.world_id = w.id
        WHERE w.created_by = ?
        ORDER BY w.name, c.name
    ");
    $stmt->execute([$user_id]);
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT reg.id, reg.name, c.id as country_id, c.name as country_name, w.id as world_id, w.name as world_name
        FROM regions reg
        JOIN countries c ON reg.country_id = c.id
        JOIN worlds w ON c.world_id = w.id
        WHERE w.created_by = ?
        ORDER BY w.name, c.name, reg.name
    ");
    $stmt->execute([$user_id]);
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.title, c.id as country_id, c.name as country_name, reg.id as region_id, reg.name as region_name, w.id as world_id, w.name as world_name
        FROM places p
        JOIN countries c ON p.country_id = c.id
        LEFT JOIN regions reg ON p.region_id = reg.id
        JOIN worlds w ON c.world_id = w.id
        WHERE w.created_by = ?
        ORDER BY w.name, c.name, p.title
    ");
    $stmt->execute([$user_id]);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $entities = [];
    $worlds = [];
    $countries = [];
    $regions = [];
    $places = [];
    $error_message = "Erreur lors de la récupération des PNJ et Monstres.";
}

$page_title = "Gestion des PNJ et Monstres";
$current_page = "manage_npcs";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-user-tie me-2"></i>Gestion des PNJ et Monstres</h1>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-muted">
                            <i class="fas fa-list me-1"></i><?php echo count($entities); ?> entité(s) trouvée(s)
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-plus me-1"></i>Créer
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">PNJ</h6></li>
                                <li><a class="dropdown-item" href="npc_create_step1.php">
                                    <i class="fas fa-user-tie me-2"></i>Création par étapes
                                </a></li>
                                <li><a class="dropdown-item" href="npc_create_automatic.php">
                                    <i class="fas fa-magic me-2"></i>Création automatique
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Monstres</h6></li>
                                <li><a class="dropdown-item" href="monster_create_step1.php">
                                    <i class="fas fa-dragon me-2"></i>Création par étapes
                                </a></li>
                                <li><a class="dropdown-item" href="monster_create_automatic.php">
                                    <i class="fas fa-magic me-2"></i>Création automatique
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <?php if ($success_message): ?>
                    <?php echo displayMessage($success_message, 'success'); ?>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <?php echo displayMessage($error_message, 'error'); ?>
                <?php endif; ?>
                
                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filtres
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Tous</option>
                                    <option value="pnj" <?php echo $filter_type === 'pnj' ? 'selected' : ''; ?>>PNJ</option>
                                    <option value="monster" <?php echo $filter_type === 'monster' ? 'selected' : ''; ?>>Monstres</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="world" class="form-label">Monde</label>
                                <select class="form-select" id="world" name="world">
                                    <option value="">Tous</option>
                                    <?php foreach ($worlds as $world): ?>
                                        <option value="<?php echo $world['id']; ?>" <?php echo $filter_world == $world['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($world['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="country" class="form-label">Pays</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Tous</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?php echo $country['id']; ?>" <?php echo $filter_country == $country['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($country['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="region" class="form-label">Région</label>
                                <select class="form-select" id="region" name="region">
                                    <option value="">Toutes</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo $region['id']; ?>" <?php echo $filter_region == $region['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="place" class="form-label">Lieu</label>
                                <select class="form-select" id="place" name="place">
                                    <option value="">Tous</option>
                                    <?php foreach ($places as $place): ?>
                                        <option value="<?php echo $place['id']; ?>" <?php echo $filter_place == $place['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($place['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filtrer
                                    </button>
                                    <a href="manage_npcs.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Effacer
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste unifiée des entités -->
                <?php if (empty($entities)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucune entité trouvée</h4>
                        <p class="text-muted">
                            <?php if ($filter_type || $filter_world || $filter_country || $filter_region || $filter_place): ?>
                                Aucune entité ne correspond aux filtres sélectionnés.
                            <?php else: ?>
                                Créez vos premiers PNJ et monstres dans vos mondes.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($entities as $entity): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($entity['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($entity['profile_photo']); ?>" 
                                                     alt="Photo" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php else: ?>
                                                <?php if ($entity['entity_type'] === 'PNJ'): ?>
                                                    <i class="fas fa-user-tie me-2"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-dragon me-2"></i>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div>
                                                <?php if ($entity['entity_type'] === 'PNJ'): ?>
                                                    <h6 class="card-title mb-0">
                                                        <a href="view_npc.php?id=<?php echo $entity['npc_character_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($entity['name']); ?>
                                                        </a>
                                                    </h6>
                                                <?php else: ?>
                                                    <h6 class="card-title mb-0">
                                                        <a href="view_monster.php?id=<?php echo $entity['monster_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($entity['name']); ?>
                                                        </a>
                                                    </h6>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <?php if ($entity['entity_type'] === 'PNJ'): ?>
                                                        <span class="badge bg-primary">PNJ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Monstre</span>
                                                        <?php if ($entity['monster_type']): ?>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($entity['monster_type']); ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($entity['entity_type'] === 'PNJ'): ?>
                                                <a href="view_npc.php?id=<?php echo $entity['npc_character_id']; ?>" 
                                                   class="btn btn-outline-primary" title="Voir la fiche">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="view_monster.php?id=<?php echo $entity['monster_id']; ?>" 
                                                   class="btn btn-outline-primary" title="Voir la fiche">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteEntity(<?php echo $entity['id']; ?>, '<?php echo htmlspecialchars($entity['name']); ?>', '<?php echo $entity['entity_type']; ?>')"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($entity['description']): ?>
                                            <p class="card-text"><?php echo htmlspecialchars(substr($entity['description'], 0, 100)) . (strlen($entity['description']) > 100 ? '...' : ''); ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- Informations spécifiques aux monstres -->
                                        <?php if ($entity['entity_type'] === 'Monstre'): ?>
                                            <div class="mb-2">
                                                <?php if ($entity['quantity']): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-users me-1"></i>Quantité: <?php echo $entity['quantity']; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if ($entity['current_hit_points']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-heart me-1"></i>PV: <?php echo $entity['current_hit_points']; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if ($entity['challenge_rating']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-star me-1"></i>DD: <?php echo htmlspecialchars($entity['challenge_rating']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($entity['place_name']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-globe me-1"></i>
                                                <?php echo htmlspecialchars($entity['world_name']); ?>
                                                <?php if ($entity['country_name']): ?>
                                                    - <?php echo htmlspecialchars($entity['country_name']); ?>
                                                <?php endif; ?>
                                                <?php if ($entity['region_name']): ?>
                                                    - <?php echo htmlspecialchars($entity['region_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex gap-1">
                                            <?php if ($entity['is_visible']): ?>
                                                <span class="badge bg-success">Visible</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Caché</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($entity['is_identified']): ?>
                                                <span class="badge bg-info">Identifié</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Non identifié</span>
                                            <?php endif; ?>
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
    
    <!-- Formulaires cachés pour les suppressions -->
    <form id="deleteEntityForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="deleteAction">
        <input type="hidden" name="npc_id" id="deleteNpcId">
        <input type="hidden" name="monster_id" id="deleteMonsterId">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteEntity(entityId, entityName, entityType) {
            let message = 'Êtes-vous sûr de vouloir supprimer ' + entityType.toLowerCase() + ' "' + entityName + '" ?\n\nCette action supprimera également son équipement.';
            
            if (confirm(message)) {
                document.getElementById('deleteAction').value = entityType === 'PNJ' ? 'delete_npc' : 'delete_monster';
                
                if (entityType === 'PNJ') {
                    document.getElementById('deleteNpcId').value = entityId;
                    document.getElementById('deleteMonsterId').value = '';
                } else {
                    document.getElementById('deleteMonsterId').value = entityId;
                    document.getElementById('deleteNpcId').value = '';
                }
                
                document.getElementById('deleteEntityForm').submit();
            }
        }
        
        // Filtres en cascade
        document.addEventListener('DOMContentLoaded', function() {
            const worldSelect = document.getElementById('world');
            const countrySelect = document.getElementById('country');
            const regionSelect = document.getElementById('region');
            const placeSelect = document.getElementById('place');
            
            // Données des options (récupérées depuis PHP)
            const countries = <?php echo json_encode($countries); ?>;
            const regions = <?php echo json_encode($regions); ?>;
            const places = <?php echo json_encode($places); ?>;
            
            function filterOptions(select, data, filterField, filterValue) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">' + (select.id === 'region' ? 'Toutes' : 'Tous') + '</option>';
                
                data.forEach(item => {
                    if (!filterValue || item[filterField] == filterValue) {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name || item.title;
                        if (item.id == currentValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    }
                });
            }
            
            worldSelect.addEventListener('change', function() {
                filterOptions(countrySelect, countries, 'world_id', this.value);
                filterOptions(regionSelect, regions, 'world_id', this.value);
                filterOptions(placeSelect, places, 'world_id', this.value);
            });
            
            countrySelect.addEventListener('change', function() {
                filterOptions(regionSelect, regions, 'country_id', this.value);
                filterOptions(placeSelect, places, 'country_id', this.value);
            });
            
            regionSelect.addEventListener('change', function() {
                filterOptions(placeSelect, places, 'region_id', this.value);
            });
        });
    </script>
</body>
</html>
