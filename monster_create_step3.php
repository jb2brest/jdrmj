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

// Vérifier que les paramètres sont fournis
if (!isset($_POST['world_id']) || !isset($_POST['country_id']) || !isset($_POST['place_id'])) {
    header('Location: monster_create_step1.php?error=missing_params');
    exit();
}

$world_id = (int)$_POST['world_id'];
$country_id = (int)$_POST['country_id'];
$place_id = (int)$_POST['place_id'];
$region_id = !empty($_POST['region_id']) ? (int)$_POST['region_id'] : null;

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
    
    // Vérifier le lieu
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
    
} catch (Exception $e) {
    header('Location: monster_create_step1.php?error=database_error');
    exit();
}

// Récupérer les monstres disponibles
try {
    $stmt = $pdo->prepare("SELECT id, name, type, size, challenge_rating, hit_points, armor_class FROM dnd_monsters ORDER BY name");
    $stmt->execute();
    $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $monsters = [];
    $error_message = "Erreur lors de la récupération des monstres.";
}

$page_title = "Création de Monstre - Étape 3";
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-dragon me-2"></i>Création de Monstre - Étape 3
                            </h4>
                            <a href="monster_create_step2.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Localisation :</strong> 
                            <?php echo htmlspecialchars($place['title']); ?> 
                            <?php if ($region_id): ?>
                                (<?php echo htmlspecialchars($region['name']); ?>)
                            <?php endif; ?>
                            - <?php echo htmlspecialchars($country['name']); ?> 
                            - <?php echo htmlspecialchars($world['name']); ?>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <?php echo displayMessage($error_message, 'error'); ?>
                        <?php endif; ?>
                        
                        <?php if (empty($monsters)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-dragon fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun monstre disponible</h5>
                                <p class="text-muted">Aucun monstre n'est disponible dans la base de données.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="monster_create_step4.php">
                                <input type="hidden" name="world_id" value="<?php echo $world_id; ?>">
                                <input type="hidden" name="country_id" value="<?php echo $country_id; ?>">
                                <input type="hidden" name="region_id" value="<?php echo $region_id; ?>">
                                <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                                
                                <div class="mb-4">
                                    <h5><i class="fas fa-dragon me-2"></i>Sélection du Monstre</h5>
                                    <p class="text-muted">Choisissez le type de monstre que vous souhaitez placer dans ce lieu.</p>
                                    
                                    <div class="row">
                                        <?php foreach ($monsters as $monster): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="monster_id" 
                                                                   id="monster_<?php echo $monster['id']; ?>" 
                                                                   value="<?php echo $monster['id']; ?>" required>
                                                            <label class="form-check-label w-100" for="monster_<?php echo $monster['id']; ?>">
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($monster['name']); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php if ($monster['type']): ?>
                                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($monster['type']); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($monster['size']): ?>
                                                                        <span class="badge bg-info me-1"><?php echo htmlspecialchars($monster['size']); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($monster['challenge_rating']): ?>
                                                                        <span class="badge bg-warning me-1">DD <?php echo htmlspecialchars($monster['challenge_rating']); ?></span>
                                                                    <?php endif; ?>
                                                                </small>
                                                                <div class="mt-1">
                                                                    <?php if ($monster['hit_points']): ?>
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-heart me-1"></i><?php echo $monster['hit_points']; ?> PV
                                                                        </small>
                                                                    <?php endif; ?>
                                                                    <?php if ($monster['armor_class']): ?>
                                                                        <br><small class="text-muted">
                                                                            <i class="fas fa-shield-alt me-1"></i>CA <?php echo $monster['armor_class']; ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Quantité *</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   min="1" max="100" value="1" required>
                                            <div class="form-text">Nombre de monstres de ce type à placer.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="current_hit_points" class="form-label">Points de vie actuels</label>
                                            <input type="number" class="form-control" id="current_hit_points" name="current_hit_points" 
                                                   min="0" placeholder="Laisser vide pour utiliser les PV max">
                                            <div class="form-text">PV actuels (optionnel, utilise les PV max par défaut).</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" checked>
                                                <label class="form-check-label" for="is_visible">
                                                    Visible par les joueurs
                                                </label>
                                            </div>
                                            <div class="form-text">Si coché, les joueurs pourront voir ce monstre.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_identified" name="is_identified" checked>
                                                <label class="form-check-label" for="is_identified">
                                                    Identifié par les joueurs
                                                </label>
                                            </div>
                                            <div class="form-text">Si coché, les joueurs connaîtront l'identité de ce monstre.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="monster_create_step2.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Étape précédente
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
                                <small class="text-muted">Pays, région, lieu</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                                     style="width: 40px; height: 40px; background-color: #007bff; color: white;">
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





