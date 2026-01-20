<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Gestion des Groupes";
$current_page = "manage_groups";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $headquarters_place_id = (int)($_POST['headquarters_place_id'] ?? 0);
            $is_secret = isset($_POST['is_secret']) && $_POST['is_secret'] === '1';
            $max_hierarchy_levels = (int)($_POST['max_hierarchy_levels'] ?? 5);
            
            // Valider le nombre de niveaux (entre 1 et 20)
            if ($max_hierarchy_levels < 1 || $max_hierarchy_levels > 20) {
                $max_hierarchy_levels = 5;
            }
            
            if (empty($name)) {
                $error_message = "Le nom du groupe est obligatoire.";
            } elseif ($headquarters_place_id <= 0) {
                $error_message = "Veuillez sélectionner une pièce pour le QG.";
            } else {
                $groupe = new Groupe([
                    'name' => $name,
                    'description' => $description,
                    'is_secret' => $is_secret,
                    'headquarters_place_id' => $headquarters_place_id,
                    'max_hierarchy_levels' => $max_hierarchy_levels,
                    'created_by' => $user_id
                ]);
                
                if ($groupe->save()) {
                    // Gérer l'upload du blason si un fichier a été fourni
                    if (isset($_FILES['crest']) && $_FILES['crest']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $uploadResult = $groupe->uploadCrest($_FILES['crest']);
                        if ($uploadResult['success']) {
                            $groupe->save(); // Sauvegarder le chemin du blason
                            $success_message = "Groupe créé avec succès et blason uploadé !";
                        } else {
                            $success_message = "Groupe créé avec succès, mais erreur lors de l'upload du blason: " . $uploadResult['error'];
                        }
                    } else {
                        $success_message = "Groupe créé avec succès !";
                    }
                } else {
                    $error_message = "Erreur lors de la création du groupe.";
                }
            }
            break;
            
        case 'delete':
            $groupe_id = (int)($_POST['groupe_id'] ?? 0);
            if ($groupe_id > 0) {
                $groupe = Groupe::findById($groupe_id);
                if ($groupe && $groupe->created_by == $user_id) {
                    if ($groupe->delete()) {
                        $success_message = "Groupe supprimé avec succès !";
                    } else {
                        $error_message = "Erreur lors de la suppression du groupe.";
                    }
                } else {
                    $error_message = "Groupe non trouvé ou vous n'avez pas les permissions.";
                }
            }
            break;
    }
}

// Récupérer tous les groupes de l'utilisateur
$groupes = Groupe::findByUser($user_id);

// Récupérer tous les pièces pour le formulaire de création
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, co.name as country_name, reg.name as region_name
        FROM places p
        LEFT JOIN countries co ON p.country_id = co.id
        LEFT JOIN regions reg ON p.region_id = reg.id
        WHERE co.world_id IN (
            SELECT id FROM worlds WHERE created_by = ?
        )
        ORDER BY co.name, reg.name, p.title
    ");
    $stmt->execute([$user_id]);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $places = [];
    $error_message = "Erreur lors de la récupération des pièces.";
}
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
    <link href="css/style.css" rel="stylesheet">
    <style>
        .group-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .group-card.border-success {
            border: 2px solid #28a745 !important;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }
        .group-card.border-success:hover {
            box-shadow: 0 8px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-dnd {
            background: linear-gradient(45deg, #8B4513, #D2691E);
            border: none;
            color: white;
        }
        .btn-dnd:hover {
            background: linear-gradient(45deg, #A0522D, #CD853F);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        .crest-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>
                    <i class="fas fa-users me-2"></i>Gestion des Groupes
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Organisez vos PNJ, PJ et Monstres en groupes pour faciliter leur gestion.
                </p>
            </div>
            <div class="btn-group" role="group">
                <button class="btn btn-dnd" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="fas fa-plus me-2"></i>Créer un Groupe
                </button>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if ($success_message): ?>
            <?php echo displayMessage($success_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <?php echo displayMessage($error_message, 'error'); ?>
        <?php endif; ?>
        
        <!-- Liste des groupes -->
        <?php if (empty($groupes)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Aucun groupe créé</h3>
                <p class="lead">Créez votre premier groupe pour organiser vos PNJ, PJ et Monstres.</p>
                <button class="btn btn-dnd btn-lg" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="fas fa-plus me-2"></i>Créer un Groupe
                </button>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($groupes as $groupe): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card group-card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="me-3">
                                        <?php if (!empty($groupe['crest_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($groupe['crest_image']); ?>" 
                                                 alt="Blason" class="crest-img">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-users text-white" style="font-size: 1.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title mb-0">
                                                <?php echo htmlspecialchars($groupe['name']); ?>
                                                <?php if ($groupe['is_secret']): ?>
                                                    <i class="fas fa-eye-slash text-warning ms-1" title="Groupe secret"></i>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($groupe['description']): ?>
                                    <p class="card-text text-muted mb-3">
                                        <?php echo htmlspecialchars(substr($groupe['description'], 0, 150)) . (strlen($groupe['description']) > 150 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <strong>QG:</strong> <?php echo htmlspecialchars($groupe['headquarters_name'] ?? 'Non défini'); ?>
                                    </p>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Créé le <?php echo date('d/m/Y', strtotime($groupe['created_at'])); ?>
                                    </small>
                                    <div class="btn-group" role="group">
                                        <a href="view_group.php?id=<?php echo $groupe['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_group.php?id=<?php echo $groupe['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $groupe['id']; ?>, '<?php echo htmlspecialchars($groupe['name']); ?>')"
                                                title="Supprimer">
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
    
    <!-- Modal de création de groupe -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Créer un Nouveau Groupe</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du groupe *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="crest" class="form-label">Blason du groupe</label>
                            <input type="file" class="form-control" id="crest" name="crest" accept="image/*">
                            <div class="form-text">Formats acceptés: JPEG, PNG, GIF, WebP (max 2MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_secret" name="is_secret" value="1">
                                <label class="form-check-label" for="is_secret">
                                    <i class="fas fa-eye-slash me-1"></i>Groupe secret
                                </label>
                            </div>
                            <div class="form-text">Un groupe secret ne sera visible que par son créateur et les membres secrets auront leur appartenance cachée par défaut.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="headquarters_place_id" class="form-label">Quartier Général *</label>
                            <select class="form-select" id="headquarters_place_id" name="headquarters_place_id" required>
                                <option value="">Sélectionner une pièce</option>
                                <?php foreach ($places as $place): ?>
                                    <option value="<?php echo $place['id']; ?>">
                                        <?php echo htmlspecialchars($place['title']); ?>
                                        <?php if ($place['country_name']): ?>
                                            (<?php echo htmlspecialchars($place['country_name']); ?>
                                            <?php if ($place['region_name']): ?>
                                                - <?php echo htmlspecialchars($place['region_name']); ?>
                                            <?php endif; ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_hierarchy_levels" class="form-label">Nombre de niveaux hiérarchiques *</label>
                            <input type="number" class="form-control" id="max_hierarchy_levels" name="max_hierarchy_levels" 
                                   value="5" min="1" max="20" required>
                            <div class="form-text">Le niveau 1 correspond au dirigeant. Nombre de niveaux disponibles pour ce groupe (entre 1 et 20).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer le Groupe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulaire de suppression caché -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="groupe_id" id="deleteGroupeId">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(groupeId, groupeName) {
            if (confirm('Êtes-vous sûr de vouloir supprimer le groupe "' + groupeName + '" ?\n\nCette action supprimera également tous les membres du groupe.')) {
                document.getElementById('deleteGroupeId').value = groupeId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
