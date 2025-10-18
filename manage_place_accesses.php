<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/Lieu.php';
require_once 'classes/Access.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$place_id = isset($_GET['place_id']) ? (int)$_GET['place_id'] : 0;
$error_message = '';
$success_message = '';

// Charger le lieu
$place = Lieu::findById($place_id);
if (!$place) {
    $error_message = "Lieu non trouvé.";
} else {
    // Charger les accès du lieu
    $accesses_from = Access::getFromPlace($place_id);
    $accesses_to = Access::getToPlace($place_id);
    $all_accesses = array_merge($accesses_from, $accesses_to);
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $place) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_access':
            $to_place_id = (int)($_POST['to_place_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $is_visible = isset($_POST['is_visible']);
            $is_open = isset($_POST['is_open']);
            $is_trapped = isset($_POST['is_trapped']);
            $trap_description = sanitizeInput($_POST['trap_description'] ?? '');
            $trap_difficulty = !empty($_POST['trap_difficulty']) ? (int)$_POST['trap_difficulty'] : null;
            $trap_damage = sanitizeInput($_POST['trap_damage'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom de l'accès est requis.";
            } elseif ($to_place_id <= 0) {
                $error_message = "Veuillez sélectionner un lieu de destination.";
            } elseif ($to_place_id == $place_id) {
                $error_message = "Un lieu ne peut pas avoir d'accès vers lui-même.";
            } elseif (Access::existsBetween($place_id, $to_place_id, $name)) {
                $error_message = "Un accès avec ce nom existe déjà vers ce lieu.";
            } else {
                $access_id = Access::create(
                    $place_id, $to_place_id, $name, $description,
                    $is_visible, $is_open, $is_trapped,
                    $trap_description, $trap_difficulty, $trap_damage
                );
                
                if ($access_id) {
                    $success_message = "Accès créé avec succès.";
                    // Recharger les accès
                    $accesses_from = Access::getFromPlace($place_id);
                    $accesses_to = Access::getToPlace($place_id);
                    $all_accesses = array_merge($accesses_from, $accesses_to);
                } else {
                    $error_message = "Erreur lors de la création de l'accès.";
                }
            }
            break;
            
        case 'update_access':
            $access_id = (int)($_POST['access_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $is_visible = isset($_POST['is_visible']);
            $is_open = isset($_POST['is_open']);
            $is_trapped = isset($_POST['is_trapped']);
            $trap_description = sanitizeInput($_POST['trap_description'] ?? '');
            $trap_difficulty = !empty($_POST['trap_difficulty']) ? (int)$_POST['trap_difficulty'] : null;
            $trap_damage = sanitizeInput($_POST['trap_damage'] ?? '');
            
            if (empty($name)) {
                $error_message = "Le nom de l'accès est requis.";
            } else {
                $access = Access::findById($access_id);
                if ($access) {
                    $access->name = $name;
                    $access->description = $description;
                    $access->is_visible = $is_visible;
                    $access->is_open = $is_open;
                    $access->is_trapped = $is_trapped;
                    $access->trap_description = $trap_description;
                    $access->trap_difficulty = $trap_difficulty;
                    $access->trap_damage = $trap_damage;
                    
                    if ($access->save()) {
                        $success_message = "Accès modifié avec succès.";
                        // Recharger les accès
                        $accesses_from = Access::getFromPlace($place_id);
                        $accesses_to = Access::getToPlace($place_id);
                        $all_accesses = array_merge($accesses_from, $accesses_to);
                    } else {
                        $error_message = "Erreur lors de la modification de l'accès.";
                    }
                } else {
                    $error_message = "Accès non trouvé.";
                }
            }
            break;
            
        case 'delete_access':
            $access_id = (int)($_POST['access_id'] ?? 0);
            $access = Access::findById($access_id);
            if ($access) {
                if ($access->delete()) {
                    $success_message = "Accès supprimé avec succès.";
                    // Recharger les accès
                    $accesses_from = Access::getFromPlace($place_id);
                    $accesses_to = Access::getToPlace($place_id);
                    $all_accesses = array_merge($accesses_from, $accesses_to);
                } else {
                    $error_message = "Erreur lors de la suppression de l'accès.";
                }
            } else {
                $error_message = "Accès non trouvé.";
            }
            break;
    }
}

// Charger les lieux disponibles pour créer des accès
$available_places = Access::getAvailablePlaces($place_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Accès - <?= htmlspecialchars($place->title ?? 'Lieu') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($place): ?>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1><i class="fas fa-door-open me-2"></i>Gestion des Accès</h1>
                            <p class="text-muted mb-0">Lieu: <strong><?= htmlspecialchars($place->title) ?></strong></p>
                        </div>
                        <div>
                            <a href="view_place.php?place_id=<?= $place_id ?>" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Retour au lieu
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccessModal">
                                <i class="fas fa-plus me-1"></i>Ajouter un accès
                            </button>
                        </div>
                    </div>

                    <!-- Liste des accès -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Accès du lieu</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($all_accesses)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-door-closed fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun accès configuré pour ce lieu.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccessModal">
                                        <i class="fas fa-plus me-1"></i>Créer le premier accès
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($all_accesses as $access): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0">
                                                            <i class="<?= $access->getStatusIcon() ?> me-1 <?= $access->getStatusClass() ?>"></i>
                                                            <?= htmlspecialchars($access->name) ?>
                                                        </h6>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <button class="dropdown-item" onclick="editAccess(<?= $access->id ?>)">
                                                                        <i class="fas fa-edit me-2"></i>Modifier
                                                                    </button>
                                                                </li>
                                                                <li>
                                                                    <button class="dropdown-item text-danger" onclick="deleteAccess(<?= $access->id ?>, '<?= htmlspecialchars($access->name) ?>')">
                                                                        <i class="fas fa-trash me-2"></i>Supprimer
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    
                                                    <p class="card-text text-muted small mb-2">
                                                        <strong>De:</strong> <?= htmlspecialchars($access->from_place_name) ?><br>
                                                        <strong>Vers:</strong> <?= htmlspecialchars($access->to_place_name) ?>
                                                    </p>
                                                    
                                                    <?php if ($access->description): ?>
                                                        <p class="card-text small"><?= htmlspecialchars($access->description) ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge <?= $access->is_visible ? 'bg-success' : 'bg-secondary' ?>">
                                                            <i class="fas fa-eye me-1"></i><?= $access->is_visible ? 'Visible' : 'Caché' ?>
                                                        </span>
                                                        <span class="badge <?= $access->is_open ? 'bg-success' : 'bg-warning' ?>">
                                                            <i class="fas fa-<?= $access->is_open ? 'unlock' : 'lock' ?> me-1"></i><?= $access->is_open ? 'Ouvert' : 'Fermé' ?>
                                                        </span>
                                                        <?php if ($access->is_trapped): ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Piégé
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($access->is_trapped && $access->trap_description): ?>
                                                        <div class="mt-2">
                                                            <small class="text-danger">
                                                                <i class="fas fa-bomb me-1"></i>
                                                                <strong>Piège:</strong> <?= htmlspecialchars($access->trap_description) ?>
                                                                <?php if ($access->trap_difficulty): ?>
                                                                    (DD <?= $access->trap_difficulty ?>)
                                                                <?php endif; ?>
                                                                <?php if ($access->trap_damage): ?>
                                                                    - <?= htmlspecialchars($access->trap_damage) ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
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
        <?php endif; ?>
    </div>

    <!-- Modal Création Accès -->
    <div class="modal fade" id="createAccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Créer un nouvel accès</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_access">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="createAccessName" class="form-label">Nom de l'accès *</label>
                                    <input type="text" class="form-control" id="createAccessName" name="name" required 
                                           placeholder="Ex: Porte principale, Passage secret, Pont...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="createAccessToPlace" class="form-label">Lieu de destination *</label>
                                    <select class="form-select" id="createAccessToPlace" name="to_place_id" required>
                                        <option value="">Sélectionner un lieu...</option>
                                        <?php foreach ($available_places as $available_place): ?>
                                            <option value="<?= $available_place['id'] ?>">
                                                <?= htmlspecialchars($available_place['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="createAccessDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="createAccessDescription" name="description" rows="3" 
                                      placeholder="Décrivez cet accès..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createAccessVisible" name="is_visible" checked>
                                    <label class="form-check-label" for="createAccessVisible">
                                        <i class="fas fa-eye me-1"></i>Visible des joueurs
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createAccessOpen" name="is_open" checked>
                                    <label class="form-check-label" for="createAccessOpen">
                                        <i class="fas fa-unlock me-1"></i>Ouvert
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="createAccessTrapped" name="is_trapped">
                                    <label class="form-check-label" for="createAccessTrapped">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Piégé
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="trapDetails" style="display: none;">
                            <hr>
                            <h6><i class="fas fa-bomb me-2"></i>Détails du piège</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="createAccessTrapDescription" class="form-label">Description du piège</label>
                                        <textarea class="form-control" id="createAccessTrapDescription" name="trap_description" rows="2" 
                                                  placeholder="Décrivez le piège..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="createAccessTrapDifficulty" class="form-label">Difficulté (DD)</label>
                                        <input type="number" class="form-control" id="createAccessTrapDifficulty" name="trap_difficulty" 
                                               min="1" max="30" placeholder="Ex: 15">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="createAccessTrapDamage" class="form-label">Dégâts</label>
                                        <input type="text" class="form-control" id="createAccessTrapDamage" name="trap_damage" 
                                               placeholder="Ex: 2d6+3">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer l'accès</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Accès -->
    <div class="modal fade" id="editAccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier l'accès</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_access">
                        <input type="hidden" name="access_id" id="editAccessId">
                        
                        <div class="mb-3">
                            <label for="editAccessName" class="form-label">Nom de l'accès *</label>
                            <input type="text" class="form-control" id="editAccessName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editAccessDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editAccessDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editAccessVisible" name="is_visible">
                                    <label class="form-check-label" for="editAccessVisible">
                                        <i class="fas fa-eye me-1"></i>Visible des joueurs
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editAccessOpen" name="is_open">
                                    <label class="form-check-label" for="editAccessOpen">
                                        <i class="fas fa-unlock me-1"></i>Ouvert
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editAccessTrapped" name="is_trapped">
                                    <label class="form-check-label" for="editAccessTrapped">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Piégé
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="editTrapDetails" style="display: none;">
                            <hr>
                            <h6><i class="fas fa-bomb me-2"></i>Détails du piège</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="editAccessTrapDescription" class="form-label">Description du piège</label>
                                        <textarea class="form-control" id="editAccessTrapDescription" name="trap_description" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="editAccessTrapDifficulty" class="form-label">Difficulté (DD)</label>
                                        <input type="number" class="form-control" id="editAccessTrapDifficulty" name="trap_difficulty" 
                                               min="1" max="30">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="editAccessTrapDamage" class="form-label">Dégâts</label>
                                        <input type="text" class="form-control" id="editAccessTrapDamage" name="trap_damage">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier l'accès</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Accès -->
    <div class="modal fade" id="deleteAccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Supprimer l'accès</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_access">
                        <input type="hidden" name="access_id" id="deleteAccessId">
                        
                        <p>Êtes-vous sûr de vouloir supprimer l'accès <strong id="deleteAccessName"></strong> ?</p>
                        <p class="text-muted small">Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'affichage des détails du piège
        document.getElementById('createAccessTrapped').addEventListener('change', function() {
            const trapDetails = document.getElementById('trapDetails');
            trapDetails.style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('editAccessTrapped').addEventListener('change', function() {
            const trapDetails = document.getElementById('editTrapDetails');
            trapDetails.style.display = this.checked ? 'block' : 'none';
        });
        
        // Fonction pour éditer un accès
        function editAccess(accessId) {
            // Ici, vous devriez charger les données de l'accès via AJAX
            // Pour simplifier, on va juste ouvrir le modal
            document.getElementById('editAccessId').value = accessId;
            new bootstrap.Modal(document.getElementById('editAccessModal')).show();
        }
        
        // Fonction pour supprimer un accès
        function deleteAccess(accessId, accessName) {
            document.getElementById('deleteAccessId').value = accessId;
            document.getElementById('deleteAccessName').textContent = accessName;
            new bootstrap.Modal(document.getElementById('deleteAccessModal')).show();
        }
    </script>
</body>
</html>
