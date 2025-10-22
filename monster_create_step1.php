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
$error_message = '';

// Récupérer les mondes de l'utilisateur
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($worlds)) {
        $error_message = "Vous devez d'abord créer un monde avant de pouvoir créer des monstres.";
    }
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des mondes.";
    $worlds = [];
}

$page_title = "Création de Monstre - Étape 1";
$current_page = "manage_npcs";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-dragon me-2"></i>Création de Monstre - Étape 1
                            </h4>
                            <a href="manage_npcs.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <?php echo displayMessage($error_message, 'error'); ?>
                        <?php endif; ?>
                        
                        <?php if (empty($worlds)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun monde créé</h5>
                                <p class="text-muted">Vous devez d'abord créer un monde avant de pouvoir créer des monstres.</p>
                                <a href="manage_worlds.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Créer un Monde
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="monster_create_step2.php">
                                <div class="mb-4">
                                    <h5><i class="fas fa-globe me-2"></i>Sélection du Monde</h5>
                                    <p class="text-muted">Choisissez le monde dans lequel vous souhaitez créer votre monstre.</p>
                                    
                                    <div class="row">
                                        <?php foreach ($worlds as $world): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="world_id" 
                                                                   id="world_<?php echo $world['id']; ?>" 
                                                                   value="<?php echo $world['id']; ?>" required>
                                                            <label class="form-check-label w-100" for="world_<?php echo $world['id']; ?>">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($world['name']); ?></h6>
                                                                <small class="text-muted">Monde ID: <?php echo $world['id']; ?></small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="manage_npcs.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-arrow-right me-1"></i>Étape suivante
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Informations sur le processus -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Processus de création
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #007bff; color: white;">
                                    <strong>1</strong>
                                </div>
                                <h6>Monde</h6>
                                <small class="text-muted">Sélection du monde</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d;">
                                    <strong>2</strong>
                                </div>
                                <h6>Localisation</h6>
                                <small class="text-muted">Pays, région, lieu</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d;">
                                    <strong>3</strong>
                                </div>
                                <h6>Monstre</h6>
                                <small class="text-muted">Type, quantité, stats</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #e9ecef; color: #6c757d;">
                                    <strong>4</strong>
                                </div>
                                <h6>Finalisation</h6>
                                <small class="text-muted">Confirmation</small>
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


