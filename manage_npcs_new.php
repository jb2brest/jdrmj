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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_npc':
            $npc_id = (int)($_POST['npc_id'] ?? 0);
            if ($npc_id > 0) {
                try {
                    $npc = new NPC();
                    if ($npc->load($npc_id)) {
                        // Vérifier que l'utilisateur a le droit de supprimer ce PNJ
                        if ($npc->getCreatedBy() == $user_id || User::isAdmin()) {
                            if ($npc->delete()) {
                                $success_message = "PNJ supprimé avec succès !";
                            } else {
                                $error_message = "Erreur lors de la suppression du PNJ.";
                            }
                        } else {
                            $error_message = "Permissions insuffisantes pour supprimer ce PNJ.";
                        }
                    } else {
                        $error_message = "PNJ non trouvé.";
                    }
                } catch (Exception $e) {
                    $error_message = "Erreur lors de la suppression du PNJ.";
                }
            }
            break;
    }
}

// Récupérer les filtres
$filter_world = $_GET['world'] ?? '';
$filter_search = $_GET['search'] ?? '';

// Récupérer les PNJ
try {
    $filters = [];
    if (!User::isAdmin()) {
        $filters['created_by'] = $user_id;
    }
    if ($filter_world) {
        $filters['world_id'] = $filter_world;
    }
    if ($filter_search) {
        $filters['search'] = $filter_search;
    }
    
    $npcs = NPC::getAll($pdo, $filters);
    
    // Récupérer les mondes pour le filtre
    $worlds = Monde::getAll($pdo);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des PNJ.";
    $npcs = [];
    $worlds = [];
}

$page_title = "Gestion des PNJ";
$current_page = "npcs";
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
    <style>
        .npc-card {
            transition: transform 0.2s ease-in-out;
        }
        .npc-card:hover {
            transform: translateY(-2px);
        }
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-user-tie me-3"></i>Gestion des PNJ</h1>
                <p class="text-muted">Créez et gérez vos Personnages Non-Joueurs</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <a href="npc_create_step1.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Créer un PNJ
                    </a>
                    <a href="npc_create_automatic.php" class="btn btn-outline-primary">
                        <i class="fas fa-magic me-2"></i>Création automatique
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($filter_search); ?>" 
                           placeholder="Nom, classe, race...">
                </div>
                <div class="col-md-4">
                    <label for="world" class="form-label">Monde</label>
                    <select class="form-select" id="world" name="world">
                        <option value="">Tous les mondes</option>
                        <?php foreach ($worlds as $world): ?>
                            <option value="<?php echo $world['id']; ?>" 
                                    <?php echo $filter_world == $world['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($world['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Liste des PNJ -->
        <div class="row">
            <?php if (empty($npcs)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucun PNJ trouvé</h4>
                        <p class="text-muted">Commencez par créer votre premier PNJ !</p>
                        <a href="npc_create_step1.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer un PNJ
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($npcs as $npc): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card npc-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <?php echo htmlspecialchars($npc['name']); ?>
                                </h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="view_npc.php?id=<?php echo $npc['id']; ?>">
                                                <i class="fas fa-eye me-2"></i>Voir
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="edit_npc.php?id=<?php echo $npc['id']; ?>">
                                                <i class="fas fa-edit me-2"></i>Modifier
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" 
                                                    onclick="confirmDelete(<?php echo $npc['id']; ?>, '<?php echo htmlspecialchars($npc['name']); ?>')">
                                                <i class="fas fa-trash me-2"></i>Supprimer
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Niveau</small><br>
                                        <strong><?php echo $npc['level']; ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Classe</small><br>
                                        <strong><?php echo htmlspecialchars($npc['class_name']); ?></strong>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Race</small><br>
                                        <strong><?php echo htmlspecialchars($npc['race_name']); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Alignement</small><br>
                                        <strong><?php echo htmlspecialchars($npc['alignment']); ?></strong>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">PV</small><br>
                                        <strong><?php echo $npc['hit_points']; ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">CA</small><br>
                                        <strong><?php echo $npc['armor_class']; ?></strong>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Monde</small><br>
                                        <strong><?php echo htmlspecialchars($npc['world_name']); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Créé par</small><br>
                                        <strong><?php echo htmlspecialchars($npc['created_by_name']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        Créé le <?php echo date('d/m/Y', strtotime($npc['created_at'])); ?>
                                    </small>
                                    <span class="badge bg-<?php echo $npc['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $npc['is_active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Statistiques -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary"><?php echo count($npcs); ?></h4>
                                <p class="text-muted mb-0">PNJ total</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success"><?php echo count(array_filter($npcs, function($npc) { return $npc['is_active']; })); ?></h4>
                                <p class="text-muted mb-0">PNJ actifs</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-info"><?php echo count(array_unique(array_column($npcs, 'class_id'))); ?></h4>
                                <p class="text-muted mb-0">Classes différentes</p>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning"><?php echo count(array_unique(array_column($npcs, 'race_id'))); ?></h4>
                                <p class="text-muted mb-0">Races différentes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le PNJ <strong id="npcName"></strong> ?</p>
                    <p class="text-danger"><strong>Cette action est irréversible !</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete_npc">
                        <input type="hidden" name="npc_id" id="npcId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(npcId, npcName) {
            document.getElementById('npcId').value = npcId;
            document.getElementById('npcName').textContent = npcName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>

