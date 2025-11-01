<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Modifier le Groupe";
$current_page = "edit_group";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_groups.php');
    exit();
}

$groupe_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer le groupe
$groupe = Groupe::findById($groupe_id);

if (!$groupe || $groupe->created_by != $user_id) {
    header('Location: manage_groups.php?error=group_not_found');
    exit();
}

$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $headquarters_place_id = (int)($_POST['headquarters_place_id'] ?? 0);
    
    if (empty($name)) {
        $error_message = "Le nom du groupe est obligatoire.";
    } elseif ($headquarters_place_id <= 0) {
        $error_message = "Veuillez sélectionner un lieu pour le QG.";
    } else {
        $groupe->name = $name;
        $groupe->description = $description;
        $groupe->is_secret = isset($_POST['is_secret']) && $_POST['is_secret'] === '1';
        $groupe->headquarters_place_id = $headquarters_place_id;
        
        // Gérer l'upload du blason si un fichier a été fourni
        if (isset($_FILES['crest']) && $_FILES['crest']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $groupe->uploadCrest($_FILES['crest']);
            if (!$uploadResult['success']) {
                $error_message = "Erreur lors de l'upload du blason: " . $uploadResult['error'];
            }
        }
        
        // Gérer la suppression du blason si demandée
        if (isset($_POST['delete_crest']) && $_POST['delete_crest'] === '1') {
            $groupe->deleteCrest();
        }
        
        if (empty($error_message)) {
            if ($groupe->save()) {
                $success_message = "Groupe modifié avec succès !";
            } else {
                $error_message = "Erreur lors de la modification du groupe.";
            }
        }
    }
}

// Récupérer tous les lieux pour le formulaire
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
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-edit me-2"></i>Modifier le Groupe</h1>
                    <div>
                        <a href="view_group.php?id=<?php echo $groupe->id; ?>" class="btn btn-light">
                            <i class="fas fa-eye me-2"></i>Voir le Groupe
                        </a>
                        <a href="manage_groups.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux Groupes
                        </a>
                    </div>
                </div>
                
                <?php if ($success_message): ?>
                    <?php echo displayMessage($success_message, 'success'); ?>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <?php echo displayMessage($error_message, 'error'); ?>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit me-2"></i>Informations du Groupe
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nom du groupe *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($groupe->name); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($groupe->description); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="crest" class="form-label">Blason du groupe</label>
                                        <?php if ($groupe->crest_image): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo htmlspecialchars($groupe->crest_image); ?>" 
                                                     alt="Blason actuel" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_crest" name="delete_crest" value="1">
                                                    <label class="form-check-label" for="delete_crest">
                                                        Supprimer le blason actuel
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="crest" name="crest" accept="image/*">
                                        <div class="form-text">Formats acceptés: JPEG, PNG, GIF, WebP (max 2MB)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_secret" name="is_secret" value="1" 
                                                   <?php echo $groupe->is_secret ? 'checked' : ''; ?>>
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
                                                <option value="<?php echo $place['id']; ?>" 
                                                        <?php echo $place['id'] == $groupe->headquarters_place_id ? 'selected' : ''; ?>>
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
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="view_group.php?id=<?php echo $groupe->id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Sauvegarder
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Informations
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>ID du groupe :</strong><br>
                                    <code><?php echo $groupe->id; ?></code>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Créé le :</strong><br>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y à H:i', strtotime($groupe->created_at)); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Dernière modification :</strong><br>
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('d/m/Y à H:i', strtotime($groupe->updated_at)); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Créateur :</strong><br>
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </div>
                                
                                <hr>
                                
                                <div class="d-grid gap-2">
                                    <a href="view_group.php?id=<?php echo $groupe->id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>Voir le Groupe
                                    </a>
                                    <a href="manage_groups.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-list me-2"></i>Liste des Groupes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
