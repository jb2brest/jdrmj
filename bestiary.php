<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Bestiaire";
$current_page = "bestiary";


requireLogin();

// Paramètres de recherche et filtrage
$search = trim($_GET['search'] ?? '');
$cr_min = isset($_GET['cr_min']) ? (float)$_GET['cr_min'] : null;
$cr_max = isset($_GET['cr_max']) ? (float)$_GET['cr_max'] : null;
$type = trim($_GET['type'] ?? '');
$size = trim($_GET['size'] ?? '');

// Construire la requête SQL
$sql = "SELECT * FROM dnd_monsters WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR type LIKE ? OR alignment LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($cr_min !== null) {
    $sql .= " AND challenge_rating >= ?";
    $params[] = $cr_min;
}

if ($cr_max !== null) {
    $sql .= " AND challenge_rating <= ?";
    $params[] = $cr_max;
}

if ($type !== '') {
    $sql .= " AND type = ?";
    $params[] = $type;
}

if ($size !== '') {
    $sql .= " AND size = ?";
    $params[] = $size;
}

$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$monsters = $stmt->fetchAll();

// Récupérer les types et tailles pour les filtres
$stmt = $pdo->prepare("SELECT DISTINCT type FROM dnd_monsters WHERE type IS NOT NULL ORDER BY type");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT size FROM dnd_monsters WHERE size IS NOT NULL ORDER BY size");
$stmt->execute();
$sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Collection supprimée - fonctionnalité non disponible
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-dragon me-2"></i>Bestiaire D&D</h1>
        </div>

        <!-- Filtres de recherche -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Recherche et filtres</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, type, alignement...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">CR min</label>
                        <input type="number" class="form-control" name="cr_min" value="<?php echo $cr_min; ?>" step="0.25" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">CR max</label>
                        <input type="number" class="form-control" name="cr_max" value="<?php echo $cr_max; ?>" step="0.25" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">Tous</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Taille</label>
                        <select class="form-select" name="size">
                            <option value="">Toutes</option>
                            <?php foreach ($sizes as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $size === $s ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                        <a href="bestiary.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Effacer
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Résultats -->
        <div class="row">
            <?php foreach ($monsters as $monster): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <!-- Image du monstre -->
                        <div class="text-center p-3" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                            <?php 
                            $image_path = "images/monstres/{$monster['csv_id']}.jpg";
                            if (file_exists($image_path)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                     alt="<?php echo htmlspecialchars($monster['name']); ?>" 
                                     class="img-fluid rounded" 
                                     style="max-height: 150px; max-width: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-secondary rounded" 
                                     style="height: 150px; width: 100%;">
                                    <i class="fas fa-dragon fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-header">
                            <h6 class="mb-0"><?php echo htmlspecialchars($monster['name']); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted">Type</small><br>
                                    <strong><?php echo htmlspecialchars($monster['type']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Taille</small><br>
                                    <strong><?php echo htmlspecialchars($monster['size']); ?></strong>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted">CR</small><br>
                                    <strong><?php echo htmlspecialchars($monster['challenge_rating']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Alignement</small><br>
                                    <strong><?php echo htmlspecialchars($monster['alignment']); ?></strong>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">PV</small><br>
                                    <strong><?php echo htmlspecialchars($monster['hit_points']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">CA</small><br>
                                    <strong><?php echo htmlspecialchars($monster['armor_class']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-sm btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#monsterModal<?php echo $monster['id']; ?>">
                                <i class="fas fa-eye me-1"></i>Voir détails
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal pour les détails -->
                <div class="modal fade" id="monsterModal<?php echo $monster['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($monster['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Type:</strong> <?php echo htmlspecialchars($monster['type']); ?><br>
                                        <strong>Taille:</strong> <?php echo htmlspecialchars($monster['size']); ?><br>
                                        <strong>CR:</strong> <?php echo htmlspecialchars($monster['challenge_rating']); ?><br>
                                        <strong>Alignement:</strong> <?php echo htmlspecialchars($monster['alignment']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>PV:</strong> <?php echo htmlspecialchars($monster['hit_points']); ?><br>
                                        <strong>CA:</strong> <?php echo htmlspecialchars($monster['armor_class']); ?><br>
                                        <strong>Vitesse:</strong> <?php echo htmlspecialchars($monster['speed']); ?><br>
                                        <strong>Bonus de prof:</strong> <?php echo htmlspecialchars($monster['proficiency_bonus']); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($monster['description'])): ?>
                                    <div class="mb-3">
                                        <strong>Description:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($monster['description'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($monster['actions'])): ?>
                                    <div class="mb-3">
                                        <strong>Actions:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($monster['actions'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($monster['special_abilities'])): ?>
                                    <div class="mb-3">
                                        <strong>Capacités spéciales:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($monster['special_abilities'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($monsters)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun monstre trouvé</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
