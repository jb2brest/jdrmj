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
            
            if (empty($name)) {
                $error_message = "Le nom du groupe est obligatoire.";
            } elseif ($headquarters_place_id <= 0) {
                $error_message = "Veuillez sélectionner un lieu pour le QG.";
            } else {
                $groupe = new Groupe([
                    'name' => $name,
                    'description' => $description,
                    'is_secret' => $is_secret,
                    'headquarters_place_id' => $headquarters_place_id,
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

// Récupérer tous les lieux pour le formulaire de création
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
    $error_message = "Erreur lors de la récupération des lieux.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR MJ</title>
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
                    <h1><i class="fas fa-users me-2"></i>Gestion des Groupes</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="fas fa-plus me-2"></i>Créer un Groupe
                    </button>
                </div>
                
                <?php if ($success_message): ?>
                    <?php echo displayMessage($success_message, 'success'); ?>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <?php echo displayMessage($error_message, 'error'); ?>
                <?php endif; ?>
                
                <?php if (empty($groupes)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucun groupe créé</h4>
                        <p class="text-muted">Créez votre premier groupe pour organiser vos PNJ, PJ et Monstres.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($groupes as $groupe): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($groupe['crest_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($groupe['crest_image']); ?>" 
                                                     alt="Blason" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-users me-2"></i>
                                            <?php endif; ?>
                                            <h5 class="card-title mb-0">
                                                <?php echo htmlspecialchars($groupe['name']); ?>
                                                <?php if ($groupe['is_secret']): ?>
                                                    <i class="fas fa-eye-slash text-warning ms-1" title="Groupe secret"></i>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_group.php?id=<?php echo $groupe['id']; ?>" 
                                               class="btn btn-outline-primary" title="Voir le groupe">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_group.php?id=<?php echo $groupe['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $groupe['id']; ?>, '<?php echo htmlspecialchars($groupe['name']); ?>')"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($groupe['description']): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($groupe['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                QG: <?php echo htmlspecialchars($groupe['headquarters_name'] ?? 'Non défini'); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Créé le <?php echo date('d/m/Y', strtotime($groupe['created_at'])); ?>
                                            </small>
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
                                <option value="">Sélectionner un lieu</option>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
