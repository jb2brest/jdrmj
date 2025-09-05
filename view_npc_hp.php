<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$npc_id = (int)($_GET['id'] ?? 0);
$scene_id = (int)($_GET['scene_id'] ?? 0);

if ($npc_id <= 0 || $scene_id <= 0) {
    header('Location: index.php');
    exit();
}

// Récupérer les informations du PNJ et vérifier les permissions
$stmt = $pdo->prepare("
    SELECT sn.*, c.hit_points_max, c.hit_points_current, c.armor_class, c.speed, c.initiative, 
           gs.dm_id, gs.campaign_id
    FROM scene_npcs sn 
    LEFT JOIN characters c ON sn.npc_character_id = c.id
    JOIN scenes s ON sn.scene_id = s.id
    JOIN game_sessions gs ON s.session_id = gs.id
    WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NULL
");
$stmt->execute([$npc_id, $scene_id]);
$npc = $stmt->fetch();

if (!$npc) {
    header('Location: index.php');
    exit();
}

// Vérifier que l'utilisateur est le MJ de la scène
$isDM = ($npc['dm_id'] == $_SESSION['user_id']);

if (!$isDM) {
    header('Location: index.php');
    exit();
}

// Initialiser les points de vie si c'est un PNJ sans personnage associé
if (!$npc['npc_character_id']) {
    // PNJ simple - utiliser des valeurs par défaut
    $npc['hit_points_max'] = $npc['hit_points_max'] ?? 50;
    $npc['hit_points_current'] = $npc['hit_points_current'] ?? $npc['hit_points_max'];
    $npc['armor_class'] = $npc['armor_class'] ?? 10;
    $npc['speed'] = $npc['speed'] ?? 30;
    $npc['initiative'] = $npc['initiative'] ?? 0;
}

$success_message = '';
$error_message = '';

// Traitement des actions POST
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
                if ($npc['npc_character_id']) {
                    // PNJ avec personnage associé
                    $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $npc['npc_character_id']]);
                } else {
                    // PNJ simple - stocker dans scene_npcs
                    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $npc_id]);
                }
                
                $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
                break;
                
            case 'damage':
                $damage = (int)$_POST['damage'];
                if ($damage > 0) {
                    $new_hp = max(0, $npc['hit_points_current'] - $damage);
                    
                    if ($npc['npc_character_id']) {
                        $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                        $stmt->execute([$new_hp, $npc['npc_character_id']]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                        $stmt->execute([$new_hp, $npc_id]);
                    }
                    
                    $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
                }
                break;
                
            case 'heal':
                $healing = (int)$_POST['healing'];
                if ($healing > 0) {
                    $new_hp = min($npc['hit_points_max'], $npc['hit_points_current'] + $healing);
                    
                    if ($npc['npc_character_id']) {
                        $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                        $stmt->execute([$new_hp, $npc['npc_character_id']]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                        $stmt->execute([$new_hp, $npc_id]);
                    }
                    
                    $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
                }
                break;
                
            case 'reset_hp':
                if ($npc['npc_character_id']) {
                    $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
                    $stmt->execute([$npc['hit_points_max'], $npc['npc_character_id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE scene_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$npc['hit_points_max'], $npc_id]);
                }
                
                $success_message = "Points de vie réinitialisés au maximum : {$npc['hit_points_max']}";
                break;
        }
        
        // Recharger les données du PNJ
        $stmt = $pdo->prepare("
            SELECT sn.*, c.hit_points_max, c.hit_points_current, c.armor_class, c.speed, c.initiative, 
                   gs.dm_id, gs.campaign_id
            FROM scene_npcs sn 
            LEFT JOIN characters c ON sn.npc_character_id = c.id
            JOIN scenes s ON sn.scene_id = s.id
            JOIN game_sessions gs ON s.session_id = gs.id
            WHERE sn.id = ? AND sn.scene_id = ? AND sn.monster_id IS NULL
        ");
        $stmt->execute([$npc_id, $scene_id]);
        $npc = $stmt->fetch();
        
        // Recalculer les valeurs si nécessaire
        if (!$npc['npc_character_id']) {
            $npc['hit_points_max'] = $npc['hit_points_max'] ?? 50;
            $npc['hit_points_current'] = $npc['hit_points_current'] ?? $npc['hit_points_max'];
            $npc['armor_class'] = $npc['armor_class'] ?? 10;
            $npc['speed'] = $npc['speed'] ?? 30;
            $npc['initiative'] = $npc['initiative'] ?? 0;
        }
    }
}

$page_title = "Gestion des Points de Vie - " . $npc['name'];
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
        .npc-header {
            background: linear-gradient(135deg, #6f42c1, #5a2d91);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .hp-bar {
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hp-fill {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .hp-high { background: linear-gradient(90deg, #28a745, #20c997); }
        .hp-medium { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .hp-low { background: linear-gradient(90deg, #dc3545, #e83e8c); }
        
        .action-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
        }
        
        .quick-action-btn {
            min-width: 60px;
        }
    </style>
</head>
<body>
    <div class="npc-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-user-tie me-3"></i>
                        <?php echo htmlspecialchars($npc['name']); ?>
                    </h1>
                    <p class="mb-0 mt-2">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo $npc['npc_character_id'] ? 'PNJ avec personnage associé' : 'PNJ simple'; ?>
                        <span class="ms-3">
                            <i class="fas fa-shield-alt me-2"></i>
                            CA <?php echo htmlspecialchars($npc['armor_class']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="view_scene.php?id=<?php echo (int)$scene_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour à la Scène
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
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Barre de Points de Vie -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-heart me-2"></i>
                            Points de Vie
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $current_hp = $npc['hit_points_current'];
                        $max_hp = $npc['hit_points_max'];
                        $hp_percentage = ($current_hp / $max_hp) * 100;
                        $hp_class = $hp_percentage > 50 ? 'hp-high' : ($hp_percentage > 25 ? 'hp-medium' : 'hp-low');
                        ?>
                        <div class="hp-bar bg-light">
                            <div class="hp-fill <?php echo $hp_class; ?>" style="width: <?php echo $hp_percentage; ?>%">
                                <?php echo $current_hp; ?>/<?php echo $max_hp; ?>
                            </div>
                        </div>
                        <div class="mt-2 text-center">
                            <small class="text-muted">
                                <?php echo round($hp_percentage, 1); ?>% des points de vie restants
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions de Gestion des Points de Vie -->
        <div class="row">
            <!-- Dégâts -->
            <div class="col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-sword me-2"></i>
                            Infliger des Dégâts
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="damage">
                            <div class="input-group">
                                <input type="number" name="damage" class="form-control" placeholder="Montant des dégâts" min="1" required>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Actions Rapides -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-danger btn-sm quick-action-btn" onclick="quickDamage(1)">-1</button>
                            <button class="btn btn-outline-danger btn-sm quick-action-btn" onclick="quickDamage(5)">-5</button>
                            <button class="btn btn-outline-danger btn-sm quick-action-btn" onclick="quickDamage(10)">-10</button>
                            <button class="btn btn-outline-danger btn-sm quick-action-btn" onclick="quickDamage(20)">-20</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Soins -->
            <div class="col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heart me-2"></i>
                            Appliquer des Soins
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="heal">
                            <div class="input-group">
                                <input type="number" name="healing" class="form-control" placeholder="Montant des soins" min="1" required>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Actions Rapides -->
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-success btn-sm quick-action-btn" onclick="quickHeal(1)">+1</button>
                            <button class="btn btn-outline-success btn-sm quick-action-btn" onclick="quickHeal(5)">+5</button>
                            <button class="btn btn-outline-success btn-sm quick-action-btn" onclick="quickHeal(10)">+10</button>
                            <button class="btn btn-outline-success btn-sm quick-action-btn" onclick="quickHeal(20)">+20</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Avancées -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card action-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Modifier les Points de Vie
                        </h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#hpModal">
                            <i class="fas fa-edit me-2"></i>
                            Modifier Directement
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card action-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-redo me-2"></i>
                            Réinitialiser
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="reset_hp">
                            <button type="submit" class="btn btn-info" onclick="return confirm('Réinitialiser les points de vie au maximum ?')">
                                <i class="fas fa-redo me-2"></i>
                                Remettre au Maximum
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations du PNJ -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations du PNJ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Classe d'Armure :</strong> <?php echo htmlspecialchars($npc['armor_class']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Vitesse :</strong> <?php echo htmlspecialchars($npc['speed']); ?> ft
                            </div>
                            <div class="col-md-3">
                                <strong>Initiative :</strong> <?php echo htmlspecialchars($npc['initiative']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Type :</strong> <?php echo $npc['npc_character_id'] ? 'Personnage associé' : 'PNJ simple'; ?>
                            </div>
                        </div>
                        <?php if ($npc['description']): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Description :</strong>
                                <p class="mt-1"><?php echo nl2br(htmlspecialchars($npc['description'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour Modification Directe des PV -->
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
                        <input type="hidden" name="max_hp" value="<?php echo $npc['hit_points_max']; ?>">
                        <div class="mb-3">
                            <label for="current_hp" class="form-label">Points de Vie Actuels</label>
                            <input type="number" class="form-control" id="current_hp" name="current_hp" 
                                   value="<?php echo $npc['hit_points_current']; ?>" 
                                   min="0" max="<?php echo $npc['hit_points_max']; ?>" required>
                            <div class="form-text">Maximum : <?php echo $npc['hit_points_max']; ?> PV</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
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

        function quickHeal(amount) {
            if (confirm(`Appliquer ${amount} points de soins ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="heal">
                    <input type="hidden" name="healing" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
