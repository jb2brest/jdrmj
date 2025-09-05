<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$monster_npc_id = (int)$_GET['id'];
$scene_id = (int)$_GET['scene_id'];

// Récupérer les informations du monstre dans la scène
$stmt = $pdo->prepare("
    SELECT sn.*, m.name as monster_name, m.type, m.size, m.challenge_rating, 
           m.hit_points as max_hit_points, m.armor_class, gs.dm_id, gs.campaign_id
    FROM scene_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    JOIN scenes s ON sn.scene_id = s.id
    JOIN game_sessions gs ON s.session_id = gs.id
    WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NOT NULL
");
$stmt->execute([$monster_npc_id, $scene_id]);
$monster = $stmt->fetch();

if (!$monster) {
    header('Location: index.php');
    exit();
}

// Vérifier que l'utilisateur est le MJ de cette scène
if ($monster['dm_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

// Traitement des actions POST
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_hp':
                $new_hp = (int)$_POST['current_hp'];
                $max_hp = (int)$_POST['max_hp'];
                
                // Valider les points de vie
                if ($new_hp < 0) {
                    $new_hp = 0;
                }
                if ($new_hp > $max_hp) {
                    $new_hp = $max_hp;
                }
                
                // Mettre à jour les points de vie actuels
                $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                $stmt->execute([$new_hp, $monster_npc_id]);
                
                $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
                break;
                
            case 'damage':
                $damage = (int)$_POST['damage'];
                if ($damage > 0) {
                    $new_hp = max(0, $monster['current_hit_points'] - $damage);
                    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $monster_npc_id]);
                    
                    $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
                }
                break;
                
            case 'heal':
                $healing = (int)$_POST['healing'];
                if ($healing > 0) {
                    $new_hp = min($monster['max_hit_points'], $monster['current_hit_points'] + $healing);
                    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $monster_npc_id]);
                    
                    $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
                }
                break;
                
            case 'reset_hp':
                $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                $stmt->execute([$monster['max_hit_points'], $monster_npc_id]);
                
                $success_message = "Points de vie réinitialisés au maximum : {$monster['max_hit_points']}";
                break;
        }
        
        // Recharger les données du monstre
        $stmt = $pdo->prepare("
            SELECT sn.*, m.name as monster_name, m.type, m.size, m.challenge_rating, 
                   m.hit_points as max_hit_points, m.armor_class, gs.dm_id, gs.campaign_id
            FROM scene_npcs sn 
            JOIN dnd_monsters m ON sn.monster_id = m.id 
            JOIN scenes s ON sn.scene_id = s.id
            JOIN game_sessions gs ON s.session_id = gs.id
            WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NOT NULL
        ");
        $stmt->execute([$monster_npc_id, $scene_id]);
        $monster = $stmt->fetch();
    }
}

// Initialiser les points de vie actuels s'ils ne sont pas définis
if ($monster['current_hit_points'] === null) {
    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
    $stmt->execute([$monster['max_hit_points'], $monster_npc_id]);
    $monster['current_hit_points'] = $monster['max_hit_points'];
}

$page_title = "Feuille de Monstre - " . $monster['name'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .monster-header {
            background: linear-gradient(135deg, #8B0000, #DC143C);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .hp-bar {
            height: 30px;
            background-color: #dc3545;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .hp-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc3545, #ffc107, #28a745);
            transition: width 0.3s ease;
        }
        .hp-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        }
        .stat-card {
            border-left: 4px solid #dc3545;
        }
        .action-btn {
            margin: 0.25rem;
        }
        .monster-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="monster-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-dragon me-3"></i>
                        <?php echo htmlspecialchars($monster['name']); ?>
                    </h1>
                    <p class="mb-0 mt-2">
                        <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($monster['type']); ?></span>
                        <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($monster['size']); ?></span>
                        <span class="badge bg-light text-dark">CR <?php echo htmlspecialchars($monster['challenge_rating']); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="view_scene.php?id=<?php echo (int)$scene_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la Scène
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
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

        <div class="row">
            <!-- Points de Vie -->
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heart me-2"></i>Points de Vie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="hp-bar mb-3">
                            <div class="hp-fill" style="width: <?php echo ($monster['current_hit_points'] / $monster['max_hit_points']) * 100; ?>%"></div>
                            <div class="hp-text">
                                <?php echo (int)$monster['current_hit_points']; ?> / <?php echo (int)$monster['max_hit_points']; ?>
                            </div>
                        </div>
                        
                        <!-- Actions rapides -->
                        <div class="row">
                            <div class="col-6">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="damage">
                                    <div class="input-group">
                                        <input type="number" name="damage" class="form-control" placeholder="Dégâts" min="1" required>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-sword"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-6">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="heal">
                                    <div class="input-group">
                                        <input type="number" name="healing" class="form-control" placeholder="Soins" min="1" required>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-warning btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#hpModal">
                                <i class="fas fa-edit me-1"></i>Modifier PV
                            </button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="reset_hp">
                                <button type="submit" class="btn btn-info btn-sm action-btn" onclick="return confirm('Réinitialiser les points de vie au maximum ?')">
                                    <i class="fas fa-redo me-1"></i>Reset PV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Statistiques
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Classe d'Armure :</strong></p>
                                <h4 class="text-primary"><?php echo (int)$monster['armor_class']; ?></h4>
                            </div>
                            <div class="col-6">
                                <p><strong>Points de Vie Max :</strong></p>
                                <h4 class="text-danger"><?php echo (int)$monster['max_hit_points']; ?></h4>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Type :</strong></p>
                                <p><?php echo htmlspecialchars($monster['type']); ?></p>
                            </div>
                            <div class="col-6">
                                <p><strong>Taille :</strong></p>
                                <p><?php echo htmlspecialchars($monster['size']); ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <p><strong>Facteur de Puissance :</strong></p>
                                <p><?php echo htmlspecialchars($monster['challenge_rating']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <?php if (!empty($monster['description'])): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-scroll me-2"></i>Description
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($monster['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Actions Rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-danger w-100" onclick="quickDamage(1)">
                                    <i class="fas fa-sword me-1"></i>-1 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-danger w-100" onclick="quickDamage(5)">
                                    <i class="fas fa-sword me-1"></i>-5 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-danger w-100" onclick="quickDamage(10)">
                                    <i class="fas fa-sword me-1"></i>-10 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-danger w-100" onclick="quickDamage(20)">
                                    <i class="fas fa-sword me-1"></i>-20 PV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier les PV -->
    <div class="modal fade" id="hpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier les Points de Vie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_hp">
                        <input type="hidden" name="max_hp" value="<?php echo (int)$monster['max_hit_points']; ?>">
                        <div class="mb-3">
                            <label for="current_hp" class="form-label">Points de Vie Actuels</label>
                            <input type="number" class="form-control" id="current_hp" name="current_hp" 
                                   value="<?php echo (int)$monster['current_hit_points']; ?>" 
                                   min="0" max="<?php echo (int)$monster['max_hit_points']; ?>" required>
                            <div class="form-text">Maximum : <?php echo (int)$monster['max_hit_points']; ?> PV</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function quickDamage(amount) {
            if (confirm(`Infliger ${amount} points de dégâts ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="damage">
                    <input type="hidden" name="damage" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
