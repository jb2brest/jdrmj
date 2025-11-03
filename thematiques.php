<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Gestion des Thématiques";
$current_page = "thematiques";

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
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($nom)) {
                $error_message = "Le nom de la thématique est obligatoire.";
            } else {
                $thematique = new Thematique([
                    'nom' => $nom,
                    'description' => $description,
                    'created_by' => $user_id
                ]);
                
                if ($thematique->save()) {
                    $success_message = "Thématique créée avec succès !";
                    header('Location: thematiques.php');
                    exit();
                } else {
                    $error_message = "Erreur lors de la création de la thématique.";
                }
            }
            break;
            
        case 'update':
            $thematique_id = (int)($_POST['thematique_id'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($thematique_id <= 0 || empty($nom)) {
                $error_message = "Données invalides.";
            } else {
                $thematique = Thematique::findById($thematique_id);
                if ($thematique && $thematique->created_by == $user_id) {
                    $thematique->nom = $nom;
                    $thematique->description = $description;
                    
                    if ($thematique->save()) {
                        $success_message = "Thématique modifiée avec succès !";
                        header('Location: thematiques.php');
                        exit();
                    } else {
                        $error_message = "Erreur lors de la modification de la thématique.";
                    }
                } else {
                    $error_message = "Thématique non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
            
        case 'delete':
            $thematique_id = (int)($_POST['thematique_id'] ?? 0);
            if ($thematique_id > 0) {
                $thematique = Thematique::findById($thematique_id);
                if ($thematique && $thematique->created_by == $user_id) {
                    if ($thematique->delete()) {
                        $success_message = "Thématique supprimée avec succès !";
                    } else {
                        $error_message = "Erreur lors de la suppression de la thématique.";
                    }
                } else {
                    $error_message = "Thématique non trouvée ou vous n'avez pas les permissions.";
                }
            }
            break;
    }
}

// Récupérer toutes les thématiques de l'utilisateur
$thematiques = Thematique::getByUser($user_id);

// Thématique en cours d'édition
$editing_thematique = null;
if (isset($_GET['edit'])) {
    $editing_thematique = Thematique::findById((int)$_GET['edit']);
    if (!$editing_thematique || $editing_thematique->created_by != $user_id) {
        $editing_thematique = null;
    }
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
        .thematique-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .thematique-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
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
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>
                    <i class="fas fa-palette me-2"></i>Gestion des Thématiques
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Organisez vos thématiques pour structurer votre univers.
                </p>
            </div>
            <div class="btn-group" role="group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createThematiqueModal">
                    <i class="fas fa-plus me-2"></i>Créer une Thématique
                </button>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Liste des thématiques -->
        <?php if (empty($thematiques)): ?>
            <div class="empty-state">
                <i class="fas fa-palette"></i>
                <h3>Aucune thématique créée</h3>
                <p class="lead">Créez votre première thématique pour commencer.</p>
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createThematiqueModal">
                    <i class="fas fa-plus me-2"></i>Créer une Thématique
                </button>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($thematiques as $thematique): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card thematique-card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tag text-primary me-2"></i>
                                        <?php echo htmlspecialchars($thematique->nom); ?>
                                    </h5>
                                </div>

                                <?php if ($thematique->description): ?>
                                    <p class="card-text text-muted mb-3 flex-grow-1">
                                        <?php echo htmlspecialchars(substr($thematique->description, 0, 150)) . (strlen($thematique->description) > 150 ? '...' : ''); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="card-text text-muted mb-3 flex-grow-1">
                                        <em>Aucune description</em>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Créé le <?php echo date('d/m/Y', strtotime($thematique->created_at)); ?>
                                    </small>
                                    <div class="btn-group" role="group">
                                        <a href="view_thematique.php?id=<?php echo $thematique->id; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="thematiques.php?edit=<?php echo $thematique->id; ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $thematique->id; ?>, '<?php echo htmlspecialchars(addslashes($thematique->nom)); ?>')"
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
    
    <!-- Modal Création/Modification Thématique -->
    <div class="modal fade" id="createThematiqueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <?php echo $editing_thematique ? 'Modifier la Thématique' : 'Créer une Nouvelle Thématique'; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editing_thematique ? 'update' : 'create'; ?>">
                        <?php if ($editing_thematique): ?>
                            <input type="hidden" name="thematique_id" value="<?php echo $editing_thematique->id; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?php echo $editing_thematique ? htmlspecialchars($editing_thematique->nom) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo $editing_thematique ? htmlspecialchars($editing_thematique->description) : ''; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $editing_thematique ? 'Enregistrer' : 'Créer'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulaire de suppression (caché) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="thematique_id" id="deleteThematiqueId">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, nom) {
            if (confirm('Êtes-vous sûr de vouloir supprimer la thématique "' + nom + '" ?')) {
                document.getElementById('deleteThematiqueId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Ouvrir automatiquement le modal d'édition si présent
        <?php if ($editing_thematique): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = new bootstrap.Modal(document.getElementById('createThematiqueModal'));
            editModal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>

