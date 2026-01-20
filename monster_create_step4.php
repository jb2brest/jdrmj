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

// Vérifier que les paramètres sont fournis
if (!isset($_POST['world_id']) || !isset($_POST['country_id']) || !isset($_POST['place_id']) || !isset($_POST['monster_id'])) {
    header('Location: monster_create_step1.php?error=missing_params');
    exit();
}

$world_id = (int)$_POST['world_id'];
$country_id = (int)$_POST['country_id'];
$place_id = (int)$_POST['place_id'];
$region_id = !empty($_POST['region_id']) ? (int)$_POST['region_id'] : null;
$monster_id = (int)$_POST['monster_id'];
$quantity = (int)($_POST['quantity'] ?? 1);
$current_hit_points = !empty($_POST['current_hit_points']) ? (int)$_POST['current_hit_points'] : null;
$is_visible = isset($_POST['is_visible']) ? 1 : 0;
$is_identified = isset($_POST['is_identified']) ? 1 : 0;

// Validation
if ($quantity <= 0 || $quantity > 100) {
    $error_message = "Quantité invalide (1-100).";
} else {
    // Vérifier que les données sont valides
    try {
        $pdo = getPdo();
        
        // Vérifier le monde
        $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE id = ? AND created_by = ?");
        $stmt->execute([$world_id, $user_id]);
        $world = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$world) {
            header('Location: monster_create_step1.php?error=invalid_world');
            exit();
        }
        
        // Vérifier le pays
        $stmt = $pdo->prepare("SELECT id, name FROM countries WHERE id = ? AND world_id = ?");
        $stmt->execute([$country_id, $world_id]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$country) {
            header('Location: monster_create_step2.php?error=invalid_country');
            exit();
        }
        
        // Vérifier la pièce
        $stmt = $pdo->prepare("SELECT id, title FROM places WHERE id = ? AND country_id = ?");
        $stmt->execute([$place_id, $country_id]);
        $place = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$place) {
            header('Location: monster_create_step2.php?error=invalid_place');
            exit();
        }
        
        // Vérifier la région si fournie
        if ($region_id) {
            $stmt = $pdo->prepare("SELECT id, name FROM regions WHERE id = ? AND country_id = ?");
            $stmt->execute([$region_id, $country_id]);
            $region = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$region) {
                header('Location: monster_create_step2.php?error=invalid_region');
                exit();
            }
        }
        
        // Vérifier le monstre
        $stmt = $pdo->prepare("SELECT id, name, type, size, challenge_rating, hit_points, armor_class FROM dnd_monsters WHERE id = ?");
        $stmt->execute([$monster_id]);
        $monster = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$monster) {
            header('Location: monster_create_step3.php?error=invalid_monster');
            exit();
        }
        
    } catch (Exception $e) {
        header('Location: monster_create_step1.php?error=database_error');
        exit();
    }
    
    // Créer le(s) monstre(s)
    try {
        $created_count = 0;
        
        for ($i = 0; $i < $quantity; $i++) {
            $name = $monster['name'];
            if ($quantity > 1) {
                $name .= ' ' . ($i + 1);
            }
            
            $description = "Monstre de type " . $monster['type'] . " (" . $monster['size'] . ").";
            
            // Utiliser les PV actuels si fournis, sinon les PV max du monstre
            $hp = $current_hit_points ?: $monster['hit_points'];
            
            $stmt = $pdo->prepare("
                INSERT INTO place_npcs (name, description, profile_photo, is_visible, is_identified, place_id, monster_id, quantity, current_hit_points) 
                VALUES (?, ?, NULL, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$name, $description, $is_visible, $is_identified, $place_id, $monster_id, $hp]);
            $created_count++;
        }
        
        $success_message = "$created_count monstre(s) créé(s) avec succès !";
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création du monstre : " . $e->getMessage();
    }
}

$page_title = "Création de Monstre - Finalisation";
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
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-check-circle me-2"></i>Création de Monstre - Finalisation
                            </h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-dragon me-2"></i>Résumé du monstre créé
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5><?php echo htmlspecialchars($monster['name']); ?></h5>
                                            <p class="text-muted"><?php echo htmlspecialchars($description); ?></p>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($place['title']); ?>
                                                    <?php if ($region_id): ?>
                                                        (<?php echo htmlspecialchars($region['name']); ?>)
                                                    <?php endif; ?>
                                                    - <?php echo htmlspecialchars($country['name']); ?>
                                                    - <?php echo htmlspecialchars($world['name']); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>Quantité: <?php echo $quantity; ?>
                                                </small>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-heart me-1"></i>Points de vie: <?php echo $hp; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <?php if ($monster['type']): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($monster['type']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($monster['size']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($monster['size']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($monster['challenge_rating']): ?>
                                                    <span class="badge bg-warning">DD <?php echo htmlspecialchars($monster['challenge_rating']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($is_visible): ?>
                                                    <span class="badge bg-success">Visible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Caché</span>
                                                <?php endif; ?>
                                                <?php if ($is_identified): ?>
                                                    <span class="badge bg-info">Identifié</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Non identifié</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                 style="height: 150px;">
                                                <i class="fas fa-dragon fa-3x text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="monster_create_step1.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i>Créer un autre monstre
                                </a>
                                <a href="manage_npcs.php" class="btn btn-primary">
                                    <i class="fas fa-list me-1"></i>Voir tous les monstres
                                </a>
                            </div>
                            
                        <?php elseif ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="monster_create_step3.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour à l'étape précédente
                                </a>
                                <a href="manage_npcs.php" class="btn btn-primary">
                                    <i class="fas fa-list me-1"></i>Voir tous les monstres
                                </a>
                            </div>
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
                                     style="width: 40px; height: 40px; background-color: #28a745; color: white;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h6>Monde</h6>
                                <small class="text-muted">Sélection du monde</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #28a745; color: white;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h6>Localisation</h6>
                                <small class="text-muted">Pays, région, pièce</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #28a745; color: white;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h6>Monstre</h6>
                                <small class="text-muted">Type, quantité, stats</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #28a745; color: white;">
                                    <i class="fas fa-check"></i>
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





