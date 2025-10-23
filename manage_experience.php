<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Gestion de l'Expérience";
$current_page = "manage_experience";


requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';

// Récupérer les personnages de l'utilisateur
$stmt = $pdo->prepare("
    SELECT c.*, r.name as race_name, cl.name as class_name 
    FROM characters c 
    LEFT JOIN races r ON c.race_id = r.id 
    LEFT JOIN classes cl ON c.class_id = cl.id 
    WHERE c.user_id = ? 
    ORDER BY c.name
");
$stmt->execute([$user_id]);
$characters = $stmt->fetchAll();

// Traitement de l'ajout d'expérience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_experience'])) {
    $character_id = (int)$_POST['character_id'];
    $experience_to_add = (int)$_POST['experience_to_add'];
    
    // Vérifier que le personnage appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM characters WHERE id = ? AND user_id = ?");
    $stmt->execute([$character_id, $user_id]);
    $character = $stmt->fetch();
    
    if ($character && $experience_to_add > 0) {
        $new_experience = $character['experience_points'] + $experience_to_add;
        $new_level = Character::calculateLevelFromExperienceStatic($new_experience);
        $new_proficiency_bonus = Character::calculateProficiencyBonusFromExperience($new_experience);
        
        $stmt = $pdo->prepare("
            UPDATE characters 
            SET experience_points = ?, level = ?, proficiency_bonus = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$new_experience, $new_level, $new_proficiency_bonus, $character_id])) {
            $level_gained = $new_level - $character['level'];
            $message = displayMessage(
                "Expérience ajoutée avec succès ! " . 
                ($level_gained > 0 ? "Le personnage a gagné " . $level_gained . " niveau(s) !" : ""), 
                "success"
            );
            
            // Recharger les personnages
            $stmt = $pdo->prepare("
                SELECT c.*, r.name as race_name, cl.name as class_name 
                FROM characters c 
                LEFT JOIN races r ON c.race_id = r.id 
                LEFT JOIN classes cl ON c.class_id = cl.id 
                WHERE c.user_id = ? 
                ORDER BY c.name
            ");
            $stmt->execute([$user_id]);
            $characters = $stmt->fetchAll();
        } else {
            $message = displayMessage("Erreur lors de l'ajout d'expérience.", "error");
        }
    } else {
        $message = displayMessage("Personnage introuvable ou montant d'expérience invalide.", "error");
    }
}

// Récupérer les seuils d'expérience pour l'affichage
$stmt = $pdo->query("SELECT * FROM experience_levels ORDER BY level");
$experience_levels = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-dnd {
            background: linear-gradient(45deg, #8B4513, #D2691E);
            border: none;
            color: white;
        }
        .btn-dnd:hover {
            background: linear-gradient(45deg, #A0522D, #CD853F);
            color: white;
        }
        .character-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .xp-bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .xp-progress {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-star me-2"></i>Gestion de l'Expérience</h2>
                <p class="text-muted">Ajoutez de l'expérience à vos personnages et suivez leur progression.</p>
                
                <?php echo $message; ?>
                
                <div class="row">
                    <!-- Formulaire d'ajout d'expérience -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus me-2"></i>Ajouter de l'Expérience</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="character_id" class="form-label">Personnage</label>
                                        <select class="form-select" id="character_id" name="character_id" required>
                                            <option value="">Sélectionner un personnage</option>
                                            <?php foreach ($characters as $char): ?>
                                                <option value="<?php echo $char['id']; ?>">
                                                    <?php echo htmlspecialchars($char['name']); ?> 
                                                    (Niveau <?php echo $char['level']; ?>, <?php echo $char['experience_points']; ?> XP)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience_to_add" class="form-label">Points d'expérience à ajouter</label>
                                        <input type="number" class="form-control" id="experience_to_add" name="experience_to_add" 
                                               min="1" required placeholder="Ex: 1000">
                                    </div>
                                    <button type="submit" name="add_experience" class="btn btn-dnd w-100">
                                        <i class="fas fa-plus me-2"></i>Ajouter l'Expérience
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liste des personnages -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Vos Personnages</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($characters)): ?>
                                    <p class="text-muted">Aucun personnage trouvé.</p>
                                <?php else: ?>
                                    <?php foreach ($characters as $char): ?>
                                        <div class="character-card">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6><?php echo htmlspecialchars($char['name']); ?></h6>
                                                    <p class="text-muted mb-1">
                                                        <?php echo htmlspecialchars($char['race_name']); ?> 
                                                        <?php echo htmlspecialchars($char['class_name']); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Niveau <?php echo $char['level']; ?></strong> 
                                                        (<?php echo $char['experience_points']; ?> XP)
                                                    </p>
                                                    
                                                    <!-- Barre de progression XP -->
                                                    <?php
                                                    $current_xp = $char['experience_points'];
                                                    $current_level = $char['level'];
                                                    
                                                    // XP requis pour le niveau actuel
                                                    $xp_for_current_level = 0;
                                                    if ($current_level > 1) {
                                                        $stmt = $pdo->prepare("SELECT experience_points_required FROM experience_levels WHERE level = ?");
                                                        $stmt->execute([$current_level - 1]);
                                                        $result = $stmt->fetch();
                                                        $xp_for_current_level = $result ? $result['experience_points_required'] : 0;
                                                    }
                                                    
                                                    // XP requis pour le niveau suivant
                                                    $xp_for_next_level = 0;
                                                    if ($current_level < 20) {
                                                        $stmt = $pdo->prepare("SELECT experience_points_required FROM experience_levels WHERE level = ?");
                                                        $stmt->execute([$current_level + 1]);
                                                        $result = $stmt->fetch();
                                                        $xp_for_next_level = $result ? $result['experience_points_required'] : 0;
                                                    }
                                                    
                                                    $xp_needed = $xp_for_next_level - $xp_for_current_level;
                                                    $xp_progress = $xp_for_next_level > 0 ? (($current_xp - $xp_for_current_level) / $xp_needed) * 100 : 100;
                                                    $xp_progress = max(0, min(100, $xp_progress));
                                                    ?>
                                                    
                                                    <div class="xp-bar">
                                                        <div class="xp-progress" style="width: <?php echo $xp_progress; ?>%"></div>
                                                    </div>
                                                    
                                                    <?php if ($current_level < 20): ?>
                                                        <small class="text-muted">
                                                            <?php echo $xp_for_next_level - $current_xp; ?> XP pour le niveau <?php echo $current_level + 1; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-success">Niveau maximum atteint !</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <a href="edit_character.php?id=<?php echo $char['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit me-1"></i>Modifier
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tableau des seuils d'expérience -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-table me-2"></i>Tableau des Seuils d'Expérience</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Niveau</th>
                                                <th>XP Requis</th>
                                                <th>Bonus de Maîtrise</th>
                                                <th>XP pour Niveau Suivant</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($experience_levels as $level): ?>
                                                <?php
                                                $next_level = $level['level'] + 1;
                                                $xp_for_next = 0;
                                                if ($next_level <= 20) {
                                                    $stmt = $pdo->prepare("SELECT experience_points_required FROM experience_levels WHERE level = ?");
                                                    $stmt->execute([$next_level]);
                                                    $result = $stmt->fetch();
                                                    $xp_for_next = $result ? $result['experience_points_required'] : 0;
                                                }
                                                $xp_needed = $xp_for_next - $level['experience_points_required'];
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo $level['level']; ?></strong></td>
                                                    <td><?php echo number_format($level['experience_points_required']); ?></td>
                                                    <td>+<?php echo $level['proficiency_bonus']; ?></td>
                                                    <td>
                                                        <?php if ($level['level'] < 20): ?>
                                                            <?php echo number_format($xp_needed); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Niveau max</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

