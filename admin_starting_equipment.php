<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/starting_equipment_functions.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

// Récupérer les filtres
$filter_class = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$filter_race = isset($_GET['race']) ? (int)$_GET['race'] : 0;
$filter_background = isset($_GET['background']) ? (int)$_GET['background'] : 0;
$filter_src = isset($_GET['src']) ? $_GET['src'] : '';

// Récupérer les listes pour les filtres
$classes = [];
$races = [];
$backgrounds = [];

try {
    // Récupérer toutes les classes
    $stmt = $pdo->query("SELECT id, name FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les races
    $stmt = $pdo->query("SELECT id, name FROM races ORDER BY name");
    $races = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer tous les backgrounds
    $stmt = $pdo->query("SELECT id, name FROM backgrounds ORDER BY name");
    $backgrounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération listes: " . $e->getMessage());
}

// Construire la requête de filtrage
$whereConditions = [];
$params = [];

if ($filter_class > 0) {
    $whereConditions[] = "(src = 'class' AND src_id = ?)";
    $params[] = $filter_class;
}

if ($filter_race > 0) {
    $whereConditions[] = "(src = 'race' AND src_id = ?)";
    $params[] = $filter_race;
}

if ($filter_background > 0) {
    $whereConditions[] = "(src = 'background' AND src_id = ?)";
    $params[] = $filter_background;
}

if (!empty($filter_src)) {
    $whereConditions[] = "src = ?";
    $params[] = $filter_src;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' OR ', $whereConditions);
}

// Récupérer les équipements de départ avec les noms d'objets
$equipment = [];
try {
    $sql = "
        SELECT se.*, 
               CASE 
                   WHEN se.src = 'class' THEN c.name
                   WHEN se.src = 'race' THEN r.name
                   WHEN se.src = 'background' THEN b.name
                   ELSE 'Inconnu'
               END as source_name,
               CASE 
                   WHEN se.type = 'weapon' AND se.type_id IS NOT NULL THEN w.name
                   WHEN se.type = 'armor' AND se.type_id IS NOT NULL THEN a.name
                   WHEN se.type = 'bouclier' AND se.type_id IS NOT NULL THEN a.name
                   WHEN se.type = 'sac' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'outils' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'nourriture' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'accessoire' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'instrument' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type_filter IS NOT NULL THEN se.type_filter
                   ELSE NULL
               END as object_name
        FROM starting_equipment se
        LEFT JOIN classes c ON se.src = 'class' AND se.src_id = c.id
        LEFT JOIN races r ON se.src = 'race' AND se.src_id = r.id
        LEFT JOIN backgrounds b ON se.src = 'background' AND se.src_id = b.id
        LEFT JOIN weapons w ON se.type = 'weapon' AND se.type_id = w.id
        LEFT JOIN armor a ON (se.type = 'armor' OR se.type = 'bouclier') AND se.type_id = a.id
        LEFT JOIN Object o ON (se.type = 'sac' OR se.type = 'outils' OR se.type = 'nourriture' OR se.type = 'accessoire' OR se.type = 'instrument') AND se.type_id = o.id
        $whereClause
        ORDER BY se.src, se.src_id, se.groupe_id, se.no_choix, se.option_letter, se.id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération équipement: " . $e->getMessage());
    $error = "Erreur lors de la récupération des données.";
}

// Statistiques
$stats = [];
try {
    $stmt = $pdo->query("
        SELECT 
            src,
            COUNT(*) as total,
            COUNT(DISTINCT src_id) as unique_sources,
            COUNT(DISTINCT type) as unique_types
        FROM starting_equipment 
        GROUP BY src
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur statistiques: " . $e->getMessage());
}

$page_title = "Gestion des Équipements de Départ";
$current_page = "admin";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .equipment-card {
            transition: transform 0.2s;
        }
        .equipment-card:hover {
            transform: translateY(-2px);
        }
        .filter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .equipment-type {
            font-weight: bold;
        }
        .source-badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-shopping-bag"></i> Gestion des Équipements de Départ
                    <span class="badge bg-danger">Admin</span>
                </h1>
            </div>
        </div>

        <!-- Statistiques -->
        <?php if (!empty($stats)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($stats as $stat): ?>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h3><?php echo $stat['total']; ?></h3>
                                    <p class="mb-0">
                                        <strong><?php echo ucfirst($stat['src']); ?>s</strong><br>
                                        <small><?php echo $stat['unique_sources']; ?> sources, <?php echo $stat['unique_types']; ?> types</small>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filter-section">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-filter"></i> Filtres
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="src" class="form-label">Source</label>
                                <select class="form-select" id="src" name="src">
                                    <option value="">Toutes les sources</option>
                                    <option value="class" <?php echo $filter_src === 'class' ? 'selected' : ''; ?>>Classes</option>
                                    <option value="race" <?php echo $filter_src === 'race' ? 'selected' : ''; ?>>Races</option>
                                    <option value="background" <?php echo $filter_src === 'background' ? 'selected' : ''; ?>>Backgrounds</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="class" class="form-label">Classe</label>
                                <select class="form-select" id="class" name="class">
                                    <option value="">Toutes les classes</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="race" class="form-label">Race</label>
                                <select class="form-select" id="race" name="race">
                                    <option value="">Toutes les races</option>
                                    <?php foreach ($races as $race): ?>
                                    <option value="<?php echo $race['id']; ?>" <?php echo $filter_race == $race['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($race['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="background" class="form-label">Background</label>
                                <select class="form-select" id="background" name="background">
                                    <option value="">Tous les backgrounds</option>
                                    <?php foreach ($backgrounds as $background): ?>
                                    <option value="<?php echo $background['id']; ?>" <?php echo $filter_background == $background['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($background['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-light">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                                <a href="admin_starting_equipment.php" class="btn btn-outline-light">
                                    <i class="fas fa-times"></i> Effacer les filtres
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résultats -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Équipements de Départ
                            <span class="badge bg-primary"><?php echo count($equipment); ?> items</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php elseif (empty($equipment)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Aucun équipement de départ trouvé avec les filtres sélectionnés.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Source</th>
                                            <th>Type</th>
                                            <th>Nom de l'objet</th>
                                            <th>Type ID</th>
                                            <th>Type Filter</th>
                                            <th>No Choix</th>
                                            <th>Option</th>
                                            <th>Quantité</th>
                                            <th>Groupe</th>
                                            <th>Choix</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($equipment as $item): ?>
                                        <tr>
                                            <td><code><?php echo $item['id']; ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['src'] === 'class' ? 'primary' : ($item['src'] === 'race' ? 'success' : 'info'); ?> source-badge">
                                                    <?php echo ucfirst($item['src']); ?>
                                                </span>
                                                <br>
                                                <small><?php echo htmlspecialchars($item['source_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="equipment-type text-<?php 
                                                    echo $item['type'] === 'weapon' ? 'danger' : 
                                                        ($item['type'] === 'armor' ? 'warning' : 
                                                        ($item['type'] === 'bouclier' ? 'info' : 
                                                        ($item['type'] === 'sac' ? 'success' :
                                                        ($item['type'] === 'outils' ? 'primary' :
                                                        ($item['type'] === 'nourriture' ? 'warning' :
                                                        ($item['type'] === 'instrument' ? 'info' : 'secondary')))))); 
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($item['type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($item['object_name']): ?>
                                                    <strong class="text-primary"><?php echo htmlspecialchars($item['object_name']); ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <?php if ($item['type'] === 'accessoire'): ?>
                                                            Accessoire générique
                                                        <?php elseif ($item['type'] === 'outils'): ?>
                                                            Outils génériques
                                                        <?php elseif ($item['type'] === 'instrument'): ?>
                                                            Instrument générique
                                                        <?php else: ?>
                                                            Non spécifié
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['type_id']): ?>
                                                    <code><?php echo $item['type_id']; ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['type_filter']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($item['type_filter']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['no_choix']): ?>
                                                    <span class="badge bg-light text-dark"><?php echo $item['no_choix']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['option_letter']): ?>
                                                    <span class="badge bg-secondary"><?php echo strtoupper($item['option_letter']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['nb'] && $item['nb'] > 1): ?>
                                                    <span class="badge bg-warning"><?php echo $item['nb']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">1</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['groupe_id']): ?>
                                                    <span class="badge bg-light text-dark"><?php echo $item['groupe_id']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['type_choix'] === 'obligatoire' ? 'success' : 'warning'; ?>">
                                                    <?php echo $item['type_choix'] === 'obligatoire' ? 'Obligatoire' : 'À choisir'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Voir les détails" onclick="viewEquipment(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" title="Modifier" onclick="editEquipment(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Supprimer" onclick="deleteEquipment(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Admin -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-tools"></i> Actions Administrateur
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                                    <i class="fas fa-plus"></i> Ajouter un équipement
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_versions.php" class="btn btn-primary w-100">
                                    <i class="fas fa-arrow-left"></i> Retour Admin
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-info w-100" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i> Actualiser
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-secondary w-100" onclick="exportData()">
                                    <i class="fas fa-download"></i> Exporter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout d'équipement -->
    <div class="modal fade" id="addEquipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Ajouter un équipement de départ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEquipmentForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="add_src" class="form-label">Source *</label>
                                <select class="form-select" id="add_src" name="src" required>
                                    <option value="">Sélectionner une source</option>
                                    <option value="class">Classe</option>
                                    <option value="race">Race</option>
                                    <option value="background">Background</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="add_src_id" class="form-label">Source ID *</label>
                                <select class="form-select" id="add_src_id" name="src_id" required>
                                    <option value="">Sélectionner d'abord une source</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="add_type" class="form-label">Type d'équipement *</label>
                                <select class="form-select" id="add_type" name="type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="weapon">Arme</option>
                                    <option value="armor">Armure</option>
                                    <option value="bouclier">Bouclier</option>
                                    <option value="outils">Outils</option>
                                    <option value="accessoire">Accessoire</option>
                                    <option value="sac">Sac</option>
                                    <option value="nourriture">Nourriture</option>
                                    <option value="instrument">Instrument de musique</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="add_type_id" class="form-label">Type ID</label>
                                <input type="number" class="form-control" id="add_type_id" name="type_id" placeholder="ID de l'équipement">
                            </div>
                            <div class="col-md-6">
                                <label for="add_object_name" class="form-label">Nom de l'objet</label>
                                <input type="text" class="form-control" id="add_object_name" name="object_name" placeholder="Nom de l'objet (pour auto-insertion)">
                                <small class="form-text text-muted">Utilisé pour créer automatiquement l'objet dans la table Object</small>
                            </div>
                            <div class="col-md-3">
                                <label for="add_type_filter" class="form-label">Type Filter</label>
                                <input type="text" class="form-control" id="add_type_filter" name="type_filter" placeholder="Filtre de type">
                            </div>
                            <div class="col-md-3">
                                <label for="add_no_choix" class="form-label">No Choix</label>
                                <input type="number" class="form-control" id="add_no_choix" name="no_choix" placeholder="Numéro du choix">
                            </div>
                            <div class="col-md-3">
                                <label for="add_option_letter" class="form-label">Option</label>
                                <select class="form-select" id="add_option_letter" name="option_letter">
                                    <option value="">Aucune</option>
                                    <option value="a">A</option>
                                    <option value="b">B</option>
                                    <option value="c">C</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="add_nb" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="add_nb" name="nb" value="1" min="1">
                            </div>
                            <div class="col-md-6">
                                <label for="add_groupe_id" class="form-label">Groupe ID</label>
                                <input type="number" class="form-control" id="add_groupe_id" name="groupe_id" placeholder="ID du groupe">
                            </div>
                            <div class="col-md-6">
                                <label for="add_type_choix" class="form-label">Type de choix *</label>
                                <select class="form-select" id="add_type_choix" name="type_choix" required>
                                    <option value="obligatoire">Obligatoire</option>
                                    <option value="à_choisir">À choisir</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" onclick="addEquipment()">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des listes dynamiques dans le modal
        document.getElementById('add_src').addEventListener('change', function() {
            const src = this.value;
            const srcIdSelect = document.getElementById('add_src_id');
            srcIdSelect.innerHTML = '<option value="">Chargement...</option>';
            
            if (src === 'class') {
                <?php
                echo 'const classes = ' . json_encode($classes) . ';';
                echo 'const races = ' . json_encode($races) . ';';
                echo 'const backgrounds = ' . json_encode($backgrounds) . ';';
                ?>
                
                let options = '<option value="">Sélectionner une classe</option>';
                if (src === 'class') {
                    classes.forEach(function(cls) {
                        options += '<option value="' + cls.id + '">' + cls.name + '</option>';
                    });
                } else if (src === 'race') {
                    races.forEach(function(race) {
                        options += '<option value="' + race.id + '">' + race.name + '</option>';
                    });
                } else if (src === 'background') {
                    backgrounds.forEach(function(bg) {
                        options += '<option value="' + bg.id + '">' + bg.name + '</option>';
                    });
                }
                srcIdSelect.innerHTML = options;
            }
        });

        function addEquipment() {
            const form = document.getElementById('addEquipmentForm');
            const formData = new FormData(form);
            formData.append('action', 'add');
            
            fetch('admin_equipment_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Équipement ajouté avec succès!');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout de l\'équipement');
            });
        }

        function viewEquipment(id) {
            const formData = new FormData();
            formData.append('action', 'get_details');
            formData.append('id', id);
            
            fetch('admin_equipment_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const eq = data.equipment;
                    alert(`Détails de l'équipement:\n\n` +
                          `ID: ${eq.id}\n` +
                          `Source: ${eq.src} (${eq.source_name})\n` +
                          `Type: ${eq.type}\n` +
                          `Nom de l'objet: ${eq.object_name || 'Non spécifié'}\n` +
                          `Type ID: ${eq.type_id || 'N/A'}\n` +
                          `Type Filter: ${eq.type_filter || 'N/A'}\n` +
                          `No Choix: ${eq.no_choix || 'N/A'}\n` +
                          `Option: ${eq.option_letter || 'N/A'}\n` +
                          `Quantité: ${eq.nb || 1}\n` +
                          `Groupe: ${eq.groupe_id || 'N/A'}\n` +
                          `Choix: ${eq.type_choix}`);
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la récupération des détails');
            });
        }

        function editEquipment(id) {
            alert('Fonctionnalité de modification à implémenter pour l\'ID: ' + id);
        }

        function deleteEquipment(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet équipement?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('admin_equipment_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Équipement supprimé avec succès!');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression');
                });
            }
        }

        function exportData() {
            // Ici, vous pouvez ajouter la logique pour exporter les données
            alert('Fonctionnalité d\'export à implémenter');
        }
    </script>
</body>
</html>
