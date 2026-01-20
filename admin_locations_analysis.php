<?php
require_once 'classes/init.php';

// Vérification des droits admin
User::requireAdmin();

$pdo = getPDO();
$message = '';
$error = '';

/**
 * Traitement des formulaires
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Création d'un nouveau lieu
    if (isset($_POST['action']) && $_POST['action'] === 'create_location') {
        $name = trim($_POST['location_name'] ?? '');
        $regionId = (int)($_POST['region_id'] ?? 0);
        $description = trim($_POST['location_description'] ?? '');
        
        if ($name && $regionId) {
            $location = Location::create($name, $regionId, $description, $pdo);
            if ($location) {
                $message = "Lieu '{$location->getName()}' créé avec succès.";
            } else {
                $error = "Erreur lors de la création du lieu.";
            }
        } else {
            $error = "Nom et Région sont obligatoires.";
        }
    }
    
    // Assignation des pièces à un lieu
    if (isset($_POST['action']) && $_POST['action'] === 'assign_rooms') {
        $locationId = (int)($_POST['target_location_id'] ?? 0);
        $roomIds = $_POST['room_ids'] ?? [];
        
        if ($locationId && !empty($roomIds)) {
            $location = Location::findById($locationId, $pdo);
            if ($location) {
                $count = 0;
                foreach ($roomIds as $roomId) {
                    $room = Room::findById($roomId, $pdo);
                    if ($room) {
                        $room->location_id = $locationId;
                        // On garde la region_id du lieu pour la cohérence, ou on laisse celle de la room ? 
                        // Idéalement la room prend la région du lieu.
                        $room->region_id = $location->getRegionId(); 
                        if ($room->save()) {
                            $count++;
                        }
                    }
                }
                $message = "$count pièce(s) déplacée(s) vers '{$location->getName()}'.";
            } else {
                $error = "Lieu cible introuvable.";
            }
        } else {
            $error = "Veuillez sélectionner un lieu et au moins une pièce.";
        }
    }
}

/**
 * Récupération des données
 */

// 1. Pièces sans lieu (Orphelines)
// On peut grouper par région actuelle pour aider le tri
$stmt = $pdo->query("
    SELECT p.*, r.name as region_name, c.name as country_name 
    FROM places p
    LEFT JOIN regions r ON p.region_id = r.id
    LEFT JOIN countries c ON p.country_id = c.id
    WHERE p.location_id IS NULL
    ORDER BY c.name, r.name, p.title
");
$orphanedRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Liste des Régions (pour création de lieu)
$stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
$allRegions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Liste des Lieux existants (pour assignation)
// On récupère aussi la région pour afficher "Lieu (Région)"
$stmt = $pdo->query("
    SELECT l.*, r.name as region_name 
    FROM locations l
    JOIN regions r ON l.region_id = r.id
    ORDER BY r.name, l.name
");
$allLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Analyse des Lieux";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h1><i class="fas fa-map-signs"></i> Analyse des Lieux</h1>
        <p class="lead">Outil de migration pour regrouper les pièces orphelines dans des Lieux.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Colonne Gauche : Créer un Lieu -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Créer un nouveau Lieu</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_location">
                            
                            <div class="mb-3">
                                <label class="form-label">Nom du Lieu</label>
                                <input type="text" name="location_name" class="form-control" required placeholder="ex: Château de Stormwind">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Région Parente</label>
                                <select name="region_id" class="form-select" required>
                                    <option value="">-- Choisir une région --</option>
                                    <?php foreach ($allRegions as $reg): ?>
                                        <option value="<?php echo $reg['id']; ?>"><?php echo htmlspecialchars($reg['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description (optionnelle)</label>
                                <textarea name="location_description" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Créer le Lieu</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Colonne Droite : Assigner les pièces -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">2. Assigner des pièces orphelines</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="assignForm">
                            <input type="hidden" name="action" value="assign_rooms">
                            
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">Destination (Lieu existant)</label>
                                    <select name="target_location_id" class="form-select" required>
                                        <option value="">-- Choisir un lieu cible --</option>
                                        <?php foreach ($allLocations as $loc): ?>
                                            <option value="<?php echo $loc['id']; ?>">
                                                <?php echo htmlspecialchars($loc['name'] . ' (' . $loc['region_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-arrow-right"></i> Déplacer la sélection
                                    </button>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6>Pièces sans Lieu (<?php echo count($orphanedRooms); ?>)</h6>
                            
                            <?php if (empty($orphanedRooms)): ?>
                                <div class="alert alert-info">Toutes les pièces sont assignées à un lieu. Bravo !</div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-hover table-sm">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th width="40"><input type="checkbox" id="selectAll"></th>
                                                <th>Pièce</th>
                                                <th>Région Actuelle</th>
                                                <th>Pays</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orphanedRooms as $room): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="room_ids[]" value="<?php echo $room['id']; ?>" class="room-check">
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($room['title']); ?></strong>
                                                        <a href="view_place.php?id=<?php echo $room['id']; ?>" target="_blank" class="ms-1 text-muted"><i class="fas fa-external-link-alt small"></i></a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($room['region_name'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($room['country_name'] ?? '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.room-check');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
</body>
</html>
