<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Fiche de Monstre";
$current_page = "view_monster_sheet";


// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$monster_npc_id = (int)$_GET['id'];
$campaign_id = (int)$_GET['campaign_id'];

// Récupérer les informations du monstre dans la lieu
$stmt = $pdo->prepare("
    SELECT sn.*, m.id as monster_db_id, m.name as monster_name, m.type, m.size, m.challenge_rating, 
           m.hit_points as max_hit_points, m.armor_class, m.csv_id, c.dm_id, c.id as campaign_id, s.id as place_id,
           m.strength, m.dexterity, m.constitution, m.intelligence, m.wisdom, m.charisma, m.competences, m.saving_throws, m.damage_immunities, m.damage_resistances, m.condition_immunities, m.senses, m.languages
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    JOIN places s ON sn.place_id = s.id
    JOIN campaigns c ON s.campaign_id = c.id
    WHERE sn.id = ? AND s.campaign_id = ? AND sn.monster_id IS NOT NULL
");
$stmt->execute([$monster_npc_id, $campaign_id]);
$monster = $stmt->fetch();

if (!$monster) {
    header('Location: index.php');
    exit();
}

// Récupérer les données de combat du monstre
$monster_db_id = $monster['monster_db_id'];

// Récupérer les actions
$stmt = $pdo->prepare("SELECT name, description FROM monster_actions WHERE monster_id = ? ORDER BY name");
$stmt->execute([$monster_db_id]);
$monster_actions = $stmt->fetchAll();

// Récupérer les actions légendaires
$stmt = $pdo->prepare("SELECT name, description FROM monster_legendary_actions WHERE monster_id = ? ORDER BY name");
$stmt->execute([$monster_db_id]);
$monster_legendary_actions = $stmt->fetchAll();

// Récupérer les attaques spéciales
$stmt = $pdo->prepare("SELECT name, description FROM monster_special_attacks WHERE monster_id = ? ORDER BY name");
$stmt->execute([$monster_db_id]);
$monster_special_attacks = $stmt->fetchAll();

// Récupérer les sorts
$stmt = $pdo->prepare("SELECT name, description FROM monster_spells WHERE monster_id = ? ORDER BY name");
$stmt->execute([$monster_db_id]);
$monster_spells = $stmt->fetchAll();

// Récupérer l'équipement magique du monstre (exclure les poisons)
$stmt = $pdo->prepare("
    SELECT me.*, mi.nom as magical_item_nom, mi.type as magical_item_type, mi.description as magical_item_description, mi.source as magical_item_source
    FROM monster_equipment me
    LEFT JOIN magical_items mi ON me.magical_item_id = mi.csv_id
    WHERE me.monster_id = ? AND me.campaign_id = ? 
    AND me.magical_item_id NOT IN (SELECT csv_id FROM poisons)
    ORDER BY me.obtained_at DESC
");
$stmt->execute([$monster_npc_id, $campaign_id]);
$magicalEquipment = $stmt->fetchAll();

// Récupérer les poisons du monstre (stockés dans monster_equipment avec magical_item_id correspondant à un poison)
$stmt = $pdo->prepare("
    SELECT me.*, p.nom as poison_nom, p.type as poison_type, p.description as poison_description, p.source as poison_source
    FROM monster_equipment me
    JOIN poisons p ON me.magical_item_id = p.csv_id
    WHERE me.monster_id = ? AND me.campaign_id = ?
    ORDER BY me.obtained_at DESC
");
$stmt->execute([$monster_npc_id, $campaign_id]);
$monsterPoisons = $stmt->fetchAll();

// Vérifier que l'utilisateur est le MJ de cette lieu
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
                $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
                $stmt->execute([$new_hp, $monster_npc_id]);
                
                $success_message = "Points de vie mis à jour : {$new_hp}/{$max_hp}";
                break;
                
            case 'damage':
                $damage = (int)$_POST['damage'];
                if ($damage > 0) {
                    $new_hp = max(0, $monster['current_hit_points'] - $damage);
                    $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $monster_npc_id]);
                    
                    $success_message = "Dégâts infligés : {$damage} PV. Points de vie restants : {$new_hp}";
                }
                break;
                
            case 'heal':
                $healing = (int)$_POST['healing'];
                if ($healing > 0) {
                    $new_hp = min($monster['max_hit_points'], $monster['current_hit_points'] + $healing);
                    $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
                    $stmt->execute([$new_hp, $monster_npc_id]);
                    
                    $success_message = "Soins appliqués : {$healing} PV. Points de vie actuels : {$new_hp}";
                }
                break;
                
            case 'reset_hp':
                $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
                $stmt->execute([$monster['max_hit_points'], $monster_npc_id]);
                
                $success_message = "Points de vie réinitialisés au maximum : {$monster['max_hit_points']}";
                break;
                
            case 'transfer_item':
                $item_id = (int)$_POST['item_id'];
                $target = $_POST['target'];
                $notes = $_POST['notes'] ?? '';
                
                // Récupérer les informations de l'objet à transférer
                $stmt = $pdo->prepare("SELECT * FROM monster_equipment WHERE id = ? AND monster_id = ? AND campaign_id = ?");
                $stmt->execute([$item_id, $monster_npc_id, $campaign_id]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    $error_message = "Objet introuvable.";
                } else {
                    // Analyser la cible
                    $target_parts = explode('_', $target);
                    $target_type = $target_parts[0];
                    $target_id = (int)$target_parts[1];
                    
                    $transfer_success = false;
                    $target_name = '';
                    
                    switch ($target_type) {
                        case 'character':
                            // Transférer vers un personnage
                            $stmt = $pdo->prepare("SELECT name FROM characters WHERE id = ?");
                            $stmt->execute([$target_id]);
                            $target_char = $stmt->fetch();
                            
                            if ($target_char) {
                                // Insérer dans place_objects
                                $stmt = $pdo->prepare("INSERT INTO place_objects (place_id, display_name, object_type, type_precis, description, is_identified, is_visible, is_equipped, position_x, position_y, is_on_map, owner_type, owner_id, poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed, magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from) VALUES (NULL, ?, ?, ?, ?, 1, 0, 0, 0, 0, 0, 'player', ?, NULL, NULL, NULL, 0, 0, 0, NULL, 0, ?, 'Objet du monstre', ?, NULL, ?, NOW(), ?)");
                                $stmt->execute([
                                    $item['item_name'],
                                    $item['item_type'],
                                    $item['item_name'],
                                    $item['item_description'],
                                    $target_id,
                                    $item['magical_item_id'],
                                    $item['quantity'],
                                    $notes ?: $item['notes'],
                                    'Transfert depuis ' . $monster['name']
                                ]);
                                
                                // Supprimer de l'ancien propriétaire
                                $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE id = ?");
                                $stmt->execute([$item_id]);
                                
                                $transfer_success = true;
                                $target_name = $target_char['name'];
                            }
                            break;
                            
                        case 'monster':
                            // Transférer vers un autre monstre
                            $stmt = $pdo->prepare("SELECT sn.name FROM place_npcs sn WHERE sn.id = ?");
                            $stmt->execute([$target_id]);
                            $target_monster = $stmt->fetch();
                            
                            if ($target_monster) {
                                // Insérer dans monster_equipment du nouveau propriétaire
                                $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, campaign_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([
                                    $target_id,
                                    $campaign_id,
                                    $item['magical_item_id'],
                                    $item['item_name'],
                                    $item['item_type'],
                                    $item['item_description'],
                                    $item['item_source'],
                                    $item['quantity'],
                                    0, // Toujours non équipé lors du transfert (0 = false)
                                    $notes ?: $item['notes'],
                                    'Transfert depuis ' . $monster['name']
                                ]);
                                
                                // Supprimer de l'ancien propriétaire
                                $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE id = ?");
                                $stmt->execute([$item_id]);
                                
                                $transfer_success = true;
                                $target_name = $target_monster['name'];
                            }
                            break;
                            
                        case 'npc':
                            // Transférer vers un PNJ
                            $stmt = $pdo->prepare("SELECT sn.name FROM place_npcs sn WHERE sn.id = ?");
                            $stmt->execute([$target_id]);
                            $target_npc = $stmt->fetch();
                            
                            if ($target_npc) {
                                // Insérer dans npc_equipment
                                $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, campaign_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([
                                    $target_id,
                                    $campaign_id,
                                    $item['magical_item_id'],
                                    $item['item_name'],
                                    $item['item_type'],
                                    $item['item_description'],
                                    $item['item_source'],
                                    $item['quantity'],
                                    0, // Toujours non équipé lors du transfert (0 = false)
                                    $notes ?: $item['notes'],
                                    'Transfert depuis ' . $monster['name']
                                ]);
                                
                                // Supprimer de l'ancien propriétaire
                                $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE id = ?");
                                $stmt->execute([$item_id]);
                                
                                $transfer_success = true;
                                $target_name = $target_npc['name'];
                            }
                            break;
                    }
                    
                    if ($transfer_success) {
                        $success_message = "Objet '{$item['item_name']}' transféré vers {$target_name} avec succès.";
                    } else {
                        $error_message = "Erreur lors du transfert de l'objet.";
                    }
                }
                break;
        }
        
        // Recharger les données du monstre
        $stmt = $pdo->prepare("
            SELECT sn.*, m.id as monster_db_id, m.name as monster_name, m.type, m.size, m.challenge_rating, 
                   m.hit_points as max_hit_points, m.armor_class, m.csv_id, c.dm_id, c.id as campaign_id, s.id as place_id,
                   m.strength, m.dexterity, m.constitution, m.intelligence, m.wisdom, m.charisma, m.competences, m.saving_throws, m.damage_immunities, m.damage_resistances, m.condition_immunities, m.senses, m.languages
            FROM place_npcs sn 
            JOIN dnd_monsters m ON sn.monster_id = m.id 
            JOIN places s ON sn.place_id = s.id
            JOIN campaigns c ON s.campaign_id = c.id
            WHERE sn.id = ? AND s.campaign_id = ? AND sn.monster_id IS NOT NULL
        ");
        $stmt->execute([$monster_npc_id, $campaign_id]);
        $monster = $stmt->fetch();
    }
}

// Initialiser les points de vie actuels s'ils ne sont pas définis
if ($monster['current_hit_points'] === null) {
    $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
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
        /* Styles personnalisés pour les dés */
        .dice-btn {
            transition: all 0.3s ease;
            min-width: 60px;
        }

        .dice-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .dice-btn.btn-brown, .dice-btn.btn-success {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        #dice-results {
            transition: all 0.3s ease;
        }

        #dice-results .badge {
            font-size: 1.1em;
            padding: 0.5em 0.75em;
            margin: 0.2em;
            animation: bounceIn 0.5s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .dice-rolling {
            animation: spin 0.1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Amélioration de l'apparence des résultats */
        .alert {
            border-radius: 0.5rem;
            border: none;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }

        /* Styles pour les barres de points de vie */
        .progress {
            border-radius: 3px;
            background-color: rgba(0,0,0,0.1);
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        .progress-bar.bg-success {
            background-color: #28a745 !important;
        }

        .progress-bar.bg-warning {
            background-color: #ffc107 !important;
        }

        .progress-bar.bg-brown {
            background-color: #8B4513 !important;
        }

        /* Animation pour les barres de PV */
        .progress-bar {
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            0% {
                background-position-x: 1rem;
            }
        }

        /* Styles spécifiques pour la fiche de monstre */
        .monster-header {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border-left: 4px solid #8B4513;
        }
        
        .action-btn {
            margin: 0.25rem;
        }
        
        .monster-image {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .monster-image.bg-secondary {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        /* Classes personnalisées marron */
        .btn-brown {
            background-color: #8B4513;
            border-color: #8B4513;
            color: white;
        }

        .btn-brown:hover {
            background-color: #A0522D;
            border-color: #A0522D;
            color: white;
        }

        .btn-outline-brown {
            color: #8B4513;
            border-color: #8B4513;
        }

        .btn-outline-brown:hover {
            background-color: #8B4513;
            border-color: #8B4513;
            color: white;
        }

        .bg-brown {
            background-color: #8B4513 !important;
        }

        .text-brown {
            color: #8B4513 !important;
        }

        .border-brown {
            border-color: #8B4513 !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="monster-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php 
                    // Utiliser le csv_id pour le nom de fichier
                    $image_path = "images/monstres/{$monster['csv_id']}.jpg";
                    
                    if (file_exists($image_path)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($monster['name']); ?>" 
                             class="monster-image img-fluid">
                    <?php else: ?>
                        <div class="monster-image bg-secondary d-flex align-items-center justify-content-center text-white">
                            <i class="fas fa-dragon fa-3x"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
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
                    <a href="view_campaign.php?id=<?php echo (int)$campaign_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la Campagne
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
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heart me-2"></i>Points de Vie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-heart text-brown me-1"></i>PV
                                </small>
                                <small class="text-muted">
                                    <?php echo (int)$monster['current_hit_points']; ?> / <?php echo (int)$monster['max_hit_points']; ?>
                                </small>
                            </div>
                            <?php 
                            $hp_percentage = ($monster['current_hit_points'] / $monster['max_hit_points']) * 100;
                            $hp_class = 'bg-success';
                            if ($hp_percentage <= 25) {
                                $hp_class = 'bg-brown';
                            } elseif ($hp_percentage <= 50) {
                                $hp_class = 'bg-warning';
                            }
                            ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php echo $hp_class; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $hp_percentage; ?>%"
                                     aria-valuenow="<?php echo $hp_percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"
                                     title="<?php echo (int)$monster['current_hit_points']; ?> / <?php echo (int)$monster['max_hit_points']; ?> PV">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions rapides -->
                        <div class="row">
                            <div class="col-6">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="damage">
                                    <div class="input-group">
                                        <input type="number" name="damage" class="form-control" placeholder="Dégâts" min="1" required>
                                        <button type="submit" class="btn btn-brown">
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
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Statistiques
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Classe d'Armure :</strong></p>
                                <h4 class="text-brown"><?php echo (int)$monster['armor_class']; ?></h4>
                            </div>
                            <div class="col-6">
                                <p><strong>Points de Vie Max :</strong></p>
                                <h4 class="text-brown"><?php echo (int)$monster['max_hit_points']; ?></h4>
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

        <!-- Caractéristiques -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-dice-d20 me-2"></i>Caractéristiques
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Force</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['strength']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['strength'] >= 10 ? '+' : '') . floor(($monster['strength'] - 10) / 2); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Dextérité</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['dexterity']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['dexterity'] >= 10 ? '+' : '') . floor(($monster['dexterity'] - 10) / 2); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Constitution</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['constitution']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['constitution'] >= 10 ? '+' : '') . floor(($monster['constitution'] - 10) / 2); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Intelligence</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['intelligence']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['intelligence'] >= 10 ? '+' : '') . floor(($monster['intelligence'] - 10) / 2); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Sagesse</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['wisdom']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['wisdom'] >= 10 ? '+' : '') . floor(($monster['wisdom'] - 10) / 2); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Charisme</strong></p>
                                    <h4 class="text-brown mb-1"><?php echo (int)$monster['charisma']; ?></h4>
                                    <small class="text-muted"><?php echo ($monster['charisma'] >= 10 ? '+' : '') . floor(($monster['charisma'] - 10) / 2); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combat -->
        <?php if (!empty($monster_actions) || !empty($monster_legendary_actions) || !empty($monster_special_attacks) || !empty($monster_spells)): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-sword me-2"></i>Combat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Actions -->
                            <?php if (!empty($monster_actions)): ?>
                            <div class="col-md-6 mb-4">
                                <h6 class="text-brown mb-3">
                                    <i class="fas fa-fist-raised me-2"></i>Actions
                                </h6>
                                <?php foreach ($monster_actions as $action): ?>
                                <div class="mb-3">
                                    <strong class="text-brown"><?php echo htmlspecialchars($action['name']); ?></strong>
                                    <?php if (!empty($action['description'])): ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($action['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Actions légendaires -->
                            <?php if (!empty($monster_legendary_actions)): ?>
                            <div class="col-md-6 mb-4">
                                <h6 class="text-brown mb-3">
                                    <i class="fas fa-crown me-2"></i>Actions légendaires
                                </h6>
                                <?php foreach ($monster_legendary_actions as $action): ?>
                                <div class="mb-3">
                                    <strong class="text-brown"><?php echo htmlspecialchars($action['name']); ?></strong>
                                    <?php if (!empty($action['description'])): ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($action['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Attaques spéciales -->
                            <?php if (!empty($monster_special_attacks)): ?>
                            <div class="col-md-6 mb-4">
                                <h6 class="text-brown mb-3">
                                    <i class="fas fa-magic me-2"></i>Attaques spéciales
                                </h6>
                                <?php foreach ($monster_special_attacks as $attack): ?>
                                <div class="mb-3">
                                    <strong class="text-brown"><?php echo htmlspecialchars($attack['name']); ?></strong>
                                    <?php if (!empty($attack['description'])): ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($attack['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Sorts -->
                            <?php if (!empty($monster_spells)): ?>
                            <div class="col-md-6 mb-4">
                                <h6 class="text-brown mb-3">
                                    <i class="fas fa-hat-wizard me-2"></i>Sorts
                                </h6>
                                <?php foreach ($monster_spells as $spell): ?>
                                <div class="mb-3">
                                    <strong class="text-brown"><?php echo htmlspecialchars($spell['name']); ?></strong>
                                    <?php if (!empty($spell['description'])): ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($spell['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Compétences et Jets de sauvegarde -->
        <?php if (!empty($monster['competences']) || !empty($monster['saving_throws'])): ?>
        <div class="row">
            <!-- Compétences -->
            <?php if (!empty($monster['competences'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>Compétences
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['competences']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Jets de sauvegarde -->
            <?php if (!empty($monster['saving_throws'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Jets de sauvegarde
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['saving_throws']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Immunités aux dégâts et Résistances aux dégâts -->
        <?php if (!empty($monster['damage_immunities']) || !empty($monster['damage_resistances'])): ?>
        <div class="row">
            <!-- Immunités aux dégâts -->
            <?php if (!empty($monster['damage_immunities'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-virus me-2"></i>Immunités aux dégâts
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['damage_immunities']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Résistances aux dégâts -->
            <?php if (!empty($monster['damage_resistances'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Résistances aux dégâts
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['damage_resistances']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Immunités aux états et Sens -->
        <?php if (!empty($monster['condition_immunities']) || !empty($monster['senses'])): ?>
        <div class="row">
            <!-- Immunités aux états -->
            <?php if (!empty($monster['condition_immunities'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>Immunités aux états
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['condition_immunities']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sens -->
            <?php if (!empty($monster['senses'])): ?>
            <div class="col-md-6 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Sens
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['senses']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Langues -->
        <?php if (!empty($monster['languages'])): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card stat-card">
                    <div class="card-header bg-brown text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-language me-2"></i>Langues
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo htmlspecialchars($monster['languages']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

        <!-- Équipement et Trésor -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-gem me-2"></i>Équipement et Trésor
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($magicalEquipment)): ?>
                            <div class="row">
                                <?php foreach ($magicalEquipment as $item): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 <?php echo $item['equipped'] ? 'border-success' : 'border-secondary'; ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <?php if ($item['equipped']): ?>
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </h6>
                                                <span class="badge bg-<?php echo $item['equipped'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $item['equipped'] ? 'Équipé' : 'Non équipé'; ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <?php if ($item['item_type']): ?>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <?php echo htmlspecialchars($item['item_type']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['item_description']): ?>
                                                    <p class="mb-2">
                                                        <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['item_source']): ?>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-book me-1"></i>
                                                        Source: <?php echo htmlspecialchars($item['item_source']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['quantity'] > 1): ?>
                                                    <p class="mb-2">
                                                        <i class="fas fa-layer-group me-1"></i>
                                                        Quantité: <?php echo $item['quantity']; ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['notes']): ?>
                                                    <div class="alert alert-info mb-2">
                                                        <i class="fas fa-sticky-note me-1"></i>
                                                        <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($item['notes'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Obtenu le <?php echo date('d/m/Y à H:i', strtotime($item['obtained_at'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($item['obtained_from']); ?>
                                                    </small>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-sm btn-outline-brown" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#transferModal" 
                                                            data-item-id="<?php echo $item['id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                            data-current-owner="monster_<?php echo $monster_npc_id; ?>"
                                                            data-current-owner-name="<?php echo htmlspecialchars($monster['name']); ?>"
                                                            title="Transférer cet objet">
                                                        <i class="fas fa-exchange-alt me-1"></i>Transférer à
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-gem fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun objet magique attribué à ce monstre</p>
                                <p class="text-muted">Les objets magiques peuvent être attribués depuis la page du lieu</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Poisons attribués par le MJ -->
                        <?php if (!empty($monsterPoisons)): ?>
                            <div class="mt-4">
                                <h6><i class="fas fa-skull-crossbones me-2"></i>Poisons</h6>
                                <div class="row">
                                    <?php foreach ($monsterPoisons as $poison): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 border-brown">
                                                <div class="card-header d-flex justify-content-between align-items-center bg-brown text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-skull-crossbones me-1"></i>
                                                        <?php echo htmlspecialchars($poison['poison_nom']); ?>
                                                    </h6>
                                                    <small class="text-light">
                                                        <?php echo htmlspecialchars($poison['poison_type']); ?>
                                                    </small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text small">
                                                        <?php echo htmlspecialchars($poison['poison_description']); ?>
                                                    </p>
                                                    <?php if ($poison['notes']): ?>
                                                        <div class="alert alert-info mb-2">
                                                            <i class="fas fa-sticky-note me-1"></i>
                                                            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($poison['notes'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            Obtenu le <?php echo date('d/m/Y à H:i', strtotime($poison['obtained_at'])); ?>
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($poison['obtained_from']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <button type="button" class="btn btn-sm btn-outline-brown" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#transferModal" 
                                                            data-item-id="<?php echo $poison['id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($poison['poison_nom']); ?>"
                                                            data-current-owner="monster_<?php echo $monster_npc_id; ?>"
                                                            data-current-owner-name="<?php echo htmlspecialchars($monster['name']); ?>"
                                                            title="Transférer ce poison">
                                                        <i class="fas fa-exchange-alt me-1"></i>Transférer à
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

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
                                <button type="button" class="btn btn-brown w-100" onclick="quickDamage(1)">
                                    <i class="fas fa-sword me-1"></i>-1 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-brown w-100" onclick="quickDamage(5)">
                                    <i class="fas fa-sword me-1"></i>-5 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-brown w-100" onclick="quickDamage(10)">
                                    <i class="fas fa-sword me-1"></i>-10 PV
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="button" class="btn btn-brown w-100" onclick="quickDamage(20)">
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
                        <button type="submit" class="btn btn-brown">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pour Transfert d'Objets -->
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Transférer un Objet Magique
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Objet :</strong> <span id="transferItemName"></span><br>
                        <strong>Propriétaire actuel :</strong> <span id="transferCurrentOwner"></span>
                    </div>
                    
                    <form id="transferForm" method="POST">
                        <input type="hidden" name="action" value="transfer_item">
                        <input type="hidden" name="item_id" id="transferItemId">
                        <input type="hidden" name="current_owner" id="transferCurrentOwnerType">
                        
                        <div class="mb-3">
                            <label for="transferTarget" class="form-label">Transférer vers :</label>
                            <select class="form-select" name="target" id="transferTarget" required>
                                <option value="">Sélectionner une cible...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transferNotes" class="form-label">Notes (optionnel) :</label>
                            <textarea class="form-control" name="notes" id="transferNotes" rows="3" placeholder="Raison du transfert, conditions, etc."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-brown" onclick="confirmTransfer()">
                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                    </button>
                </div>
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

        // Gestion du modal de transfert
        document.getElementById('transferModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            const currentOwner = button.getAttribute('data-current-owner');
            const currentOwnerName = button.getAttribute('data-current-owner-name');
            
            // Remplir les informations de base
            document.getElementById('transferItemName').textContent = itemName;
            document.getElementById('transferCurrentOwner').textContent = currentOwnerName;
            document.getElementById('transferItemId').value = itemId;
            document.getElementById('transferCurrentOwnerType').value = currentOwner;
            
            // Charger les cibles disponibles
            loadTransferTargets(currentOwner);
        });

        function loadTransferTargets(currentOwner) {
            const select = document.getElementById('transferTarget');
            select.innerHTML = '<option value="">Chargement...</option>';
            
            // Simuler le chargement des cibles (à remplacer par un appel AJAX)
            setTimeout(() => {
                select.innerHTML = '<option value="">Sélectionner une cible...</option>';
                
                // Ajouter les personnages joueurs
                select.innerHTML += '<optgroup label="Personnages Joueurs">';
                select.innerHTML += '<option value="character_1">Hyphrédicte (Robin)</option>';
                select.innerHTML += '<option value="character_2">Lieutenant Cameron (MJ)</option>';
                select.innerHTML += '</optgroup>';
                
                // Ajouter les PNJ
                select.innerHTML += '<optgroup label="PNJ">';
                select.innerHTML += '<option value="npc_1">PNJ Test</option>';
                select.innerHTML += '</optgroup>';
                
                // Ajouter les monstres
                select.innerHTML += '<optgroup label="Monstres">';
                select.innerHTML += '<option value="monster_10">Aboleth #1</option>';
                select.innerHTML += '<option value="monster_11">Aboleth #2</option>';
                select.innerHTML += '</optgroup>';
            }, 500);
        }

        function confirmTransfer() {
            const form = document.getElementById('transferForm');
            const target = document.getElementById('transferTarget').value;
            const itemName = document.getElementById('transferItemName').textContent;
            
            if (!target) {
                alert('Veuillez sélectionner une cible pour le transfert.');
                return;
            }
            
            const targetName = document.getElementById('transferTarget').selectedOptions[0].text;
            
            if (confirm(`Confirmer le transfert de "${itemName}" vers "${targetName}" ?`)) {
                form.submit();
            }
        }
    </script>
</body>
</html>
