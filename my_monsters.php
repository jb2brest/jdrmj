<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Mes Monstres";
$current_page = "my_monsters";


requireLogin();

if (!isDM()) {
    header('Location: index.php');
    exit();
}

// Paramètres de recherche
$search = trim($_GET['search'] ?? '');
$cr_min = isset($_GET['cr_min']) ? (float)$_GET['cr_min'] : null;
$cr_max = isset($_GET['cr_max']) ? (float)$_GET['cr_max'] : null;
$type = trim($_GET['type'] ?? '');

// Construire la requête SQL pour la collection
$sql = "SELECT m.* FROM dnd_monsters m 
        JOIN user_monster_collection umc ON m.id = umc.monster_id 
        WHERE umc.user_id = ?";
$params = [$_SESSION['user_id']];

if ($search !== '') {
    $sql .= " AND (m.name LIKE ? OR m.type LIKE ? OR m.alignment LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($cr_min !== null) {
    $sql .= " AND m.challenge_rating >= ?";
    $params[] = $cr_min;
}

if ($cr_max !== null) {
    $sql .= " AND m.challenge_rating <= ?";
    $params[] = $cr_max;
}

if ($type !== '') {
    $sql .= " AND m.type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY m.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$myMonsters = $stmt->fetchAll();

// Récupérer les types pour les filtres
$stmt = $pdo->prepare("SELECT DISTINCT m.type FROM dnd_monsters m 
                       JOIN user_monster_collection umc ON m.id = umc.monster_id 
                       WHERE umc.user_id = ? AND m.type IS NOT NULL 
                       ORDER BY m.type");
$stmt->execute([$_SESSION['user_id']]);
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-bookmark me-2"></i>Ma Collection de Monstres</h1>
            <a href="bestiary.php" class="btn btn-outline-primary">
                <i class="fas fa-plus me-1"></i>Ajouter des monstres
            </a>
        </div>

        <!-- Filtres de recherche -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Recherche dans ma collection</h5>
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
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Rechercher
                            </button>
                            <a href="my_monsters.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Effacer
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo count($myMonsters); ?></h5>
                        <p class="card-text">Monstres en collection</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php 
                            $avgCR = array_reduce($myMonsters, function($carry, $monster) {
                                return $carry + (float)$monster['challenge_rating'];
                            }, 0);
                            echo count($myMonsters) > 0 ? round($avgCR / count($myMonsters), 1) : 0;
                            ?>
                        </h5>
                        <p class="card-text">CR moyen</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php 
                            $maxCR = array_reduce($myMonsters, function($carry, $monster) {
                                return max($carry, (float)$monster['challenge_rating']);
                            }, 0);
                            echo $maxCR;
                            ?>
                        </h5>
                        <p class="card-text">CR maximum</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php 
                            $types = array_unique(array_column($myMonsters, 'type'));
                            echo count($types);
                            ?>
                        </h5>
                        <p class="card-text">Types différents</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des monstres -->
        <div class="row">
            <?php foreach ($myMonsters as $monster): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo htmlspecialchars($monster['name']); ?></h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#monsterModal<?php echo $monster['id']; ?>" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#createNpcModal<?php echo $monster['id']; ?>" title="Créer un MNJ">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <form method="POST" action="add_to_collection.php" class="d-inline">
                                    <input type="hidden" name="monster_id" value="<?php echo (int)$monster['id']; ?>">
                                    <button type="submit" name="action" value="remove" class="btn btn-outline-danger" title="Retirer de ma collection" onclick="return confirm('Retirer <?php echo htmlspecialchars($monster['name']); ?> de votre collection ?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
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
                    </div>
                </div>

                <!-- Modal pour créer un MNJ -->
                <div class="modal fade" id="createNpcModal<?php echo $monster['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Créer un MNJ : <?php echo htmlspecialchars($monster['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="create_monster_npc.php">
                                <div class="modal-body">
                                    <input type="hidden" name="monster_id" value="<?php echo (int)$monster['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Nom du MNJ</label>
                                        <input type="text" class="form-control" name="npc_name" value="<?php echo htmlspecialchars($monster['name']); ?>" required>
                                        <div class="form-text">Vous pouvez modifier le nom si nécessaire</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description (optionnel)</label>
                                        <textarea class="form-control" name="description" rows="3" placeholder="Description personnalisée du MNJ..."><?php echo htmlspecialchars($monster['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Lieu où l'ajouter</label>
                                        <select class="form-select" name="place_id" required>
                                            <option value="">Sélectionner un lieu</option>
                                            <?php
                                            // Récupérer les lieux du MJ
                                            $stmt = $pdo->prepare("SELECT p.id, p.name, c.title AS campaign_title FROM places p JOIN campaigns c ON p.campaign_id = c.id WHERE c.dm_id = ? ORDER BY c.title, p.name");
                                            $stmt->execute([$_SESSION['user_id']]);
                                            $places = $stmt->fetchAll();
                                            foreach ($places as $scene):
                                            ?>
                                                <option value="<?php echo (int)$scene['id']; ?>">
                                                    <?php echo htmlspecialchars($scene['campaign_title'] . ' - ' . $scene['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-user-plus me-1"></i>Créer le MNJ
                                    </button>
                                </div>
                            </form>
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
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createNpcModal<?php echo $monster['id']; ?>" data-bs-dismiss="modal">
                                    <i class="fas fa-user-plus me-1"></i>Créer un MNJ
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($myMonsters)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Votre collection est vide</h4>
                <p class="text-muted">Ajoutez des monstres depuis le bestiaire pour commencer votre collection.</p>
                <a href="bestiary.php" class="btn btn-primary">
                    <i class="fas fa-dragon me-1"></i>Parcourir le bestiaire
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
