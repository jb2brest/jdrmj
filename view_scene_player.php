<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Lieu - Vue Joueur";
$current_page = "view_scene_player";

requireLogin();

$user_id = $_SESSION['user_id'];

// Trouver le lieu où se trouve le joueur
$stmt = $pdo->prepare("
    SELECT p.*, c.title as campaign_title, c.dm_id, c.id as campaign_id
    FROM places p 
    JOIN campaigns c ON p.campaign_id = c.id 
    JOIN place_players pp ON p.id = pp.place_id 
    WHERE pp.player_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$place = $stmt->fetch();

if (!$place) {
    // Le joueur n'est dans aucun lieu, afficher un message informatif
    $page_title = "Aucun lieu assigné";
    include 'includes/layout.php';
    ?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h4 class="card-title">Aucun lieu assigné</h4>
                        <p class="card-text text-muted">
                            Vous n'êtes actuellement assigné à aucun lieu. 
                            Le maître du jeu doit vous ajouter à un lieu pour que vous puissiez y accéder.
                        </p>
                        <a href="campaigns.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux campagnes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit();
}

$place_id = (int)$place['id'];

// Vérifier que l'utilisateur est membre de la campagne
$stmt = $pdo->prepare("SELECT cm.role FROM campaign_members cm WHERE cm.campaign_id = ? AND cm.user_id = ?");
$stmt->execute([$place['campaign_id'], $user_id]);
$membership = $stmt->fetch();

if (!$membership) {
    header('Location: campaigns.php');
    exit();
}

// Récupérer les personnages du joueur présents dans ce lieu
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.level, c.profile_photo, c.class_id, cl.name as class_name
    FROM characters c
    LEFT JOIN classes cl ON c.class_id = cl.id
    WHERE c.user_id = ? AND c.id IN (
        SELECT sp.character_id FROM place_players sp WHERE sp.place_id = ? AND sp.character_id IS NOT NULL
    )
    ORDER BY c.name ASC
");
$stmt->execute([$user_id, $place_id]);
$player_characters = $stmt->fetchAll();

// Récupérer les positions de tous les pions (comme dans view_scene.php)
$stmt = $pdo->prepare("
    SELECT token_type, entity_id, position_x, position_y, is_on_map
    FROM place_tokens 
    WHERE place_id = ?
");
$stmt->execute([$place_id]);
$tokenPositions = [];
while ($row = $stmt->fetch()) {
    $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
        'x' => (int)$row['position_x'],
        'y' => (int)$row['position_y'],
        'is_on_map' => (bool)$row['is_on_map']
    ];
}

// Récupérer TOUS les joueurs présents dans le lieu (comme dans view_scene.php)
$stmt = $pdo->prepare("
    SELECT sp.player_id, u.username, c.id as character_id, c.name as character_name, c.profile_photo, c.level, cl.name as class_name
    FROM place_players sp 
    JOIN users u ON sp.player_id = u.id 
    LEFT JOIN characters c ON sp.character_id = c.id
    LEFT JOIN classes cl ON c.class_id = cl.id
    WHERE sp.place_id = ?
    ORDER BY u.username ASC
");
$stmt->execute([$place_id]);
$placePlayers = $stmt->fetchAll();

// Récupérer les autres joueurs (pour l'affichage séparé)
$other_players = array_filter($placePlayers, function($player) use ($user_id) {
    return $player['player_id'] != $user_id;
});

// Récupérer les PNJ présents dans le lieu (seulement ceux visibles)
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo
    FROM place_npcs sn 
    LEFT JOIN characters c ON sn.npc_character_id = c.id
    WHERE sn.place_id = ? AND sn.monster_id IS NULL AND sn.is_visible = 1
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$placeNpcs = $stmt->fetchAll();

// Récupérer les monstres présents dans le lieu (seulement ceux visibles)
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, sn.is_identified,
           m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class, m.csv_id
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL AND sn.is_visible = 1
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$placeMonsters = $stmt->fetchAll();

// Récupérer les objets présents dans le lieu (seulement ceux visibles)
$stmt = $pdo->prepare("
    SELECT id, name, description, object_type, is_visible, is_identified, position_x, position_y, is_on_map,
           item_id, item_name, item_description, letter_content, is_sealed, gold_coins, silver_coins, copper_coins
    FROM place_objects 
    WHERE place_id = ? AND is_visible = 1
    ORDER BY name ASC
");
$stmt->execute([$place_id]);
$placeObjects = $stmt->fetchAll();

// Récupérer les positions des objets depuis place_objects
foreach ($placeObjects as $object) {
    $tokenKey = 'object_' . $object['id'];
    $tokenPositions[$tokenKey] = [
        'x' => (int)$object['position_x'],
        'y' => (int)$object['position_y'],
        'is_on_map' => (bool)$object['is_on_map']
    ];
}

include 'includes/layout.php';
?>

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

.dice-btn.btn-primary, .dice-btn.btn-success {
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

/* Styles pour les pions d'objets */
.object-token {
    position: absolute;
    width: 24px;
    height: 24px;
    background: linear-gradient(45deg, #FFD700, #FFA500);
    border: 2px solid #FF8C00;
    border-radius: 4px;
    cursor: pointer;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #8B4513;
    font-weight: bold;
    transition: all 0.2s ease;
}

.object-token:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.4);
}

/* Styles pour les icônes des pions d'objets */
.object-token .fa-question {
    color: #8B4513 !important;
    font-weight: bold;
}
.object-token .fa-flask {
    color: #dc3545 !important;
}
.object-token .fa-magic {
    color: #0dcaf0 !important;
}
.object-token .fa-sword {
    color: #dc3545 !important;
}
.object-token .fa-shield-alt {
    color: #198754 !important;
}
.object-token .fa-envelope {
    color: #0d6efd !important;
}
.object-token .fa-coins {
    color: #ffc107 !important;
}
.object-token .fa-box {
    color: #6c757d !important;
}
</style>

<div class="container mt-4">
    <!-- En-tête du lieu -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
                    <p class="lead mb-2"><?php echo htmlspecialchars($place['campaign_title']); ?></p>
                    <span class="badge bg-info">Vue Joueur</span>
                </div>
                <div>
                    <a href="view_campaign.php?id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Jets de dés -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dice me-2"></i>Jets de dés</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Sélection des dés et résultats -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Choisir un dé :</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="4" title="Dé à 4 faces">
                                    <i class="fas fa-dice-d4"></i> D4
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="6" title="Dé à 6 faces">
                                    <i class="fas fa-dice"></i> D6
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="8" title="Dé à 8 faces">
                                    <i class="fas fa-dice-d8"></i> D8
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="10" title="Dé à 10 faces">
                                    <i class="fas fa-dice-d10"></i> D10
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="12" title="Dé à 12 faces">
                                    <i class="fas fa-dice-d12"></i> D12
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="20" title="Dé à 20 faces">
                                    <i class="fas fa-dice-d20"></i> D20
                                </button>
                                <button type="button" class="btn btn-outline-success dice-btn" data-sides="100" title="Dé percentille">
                                    <i class="fas fa-percentage"></i> D100
                                </button>
                            </div>
                            
                            <!-- Options de lancer -->
                            <div class="mb-3">
                                <label for="dice-quantity" class="form-label">Nombre de dés :</label>
                                <select class="form-select" id="dice-quantity" style="max-width: 100px;">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                            
                            <button type="button" class="btn btn-primary" id="roll-dice-btn" disabled>
                                <i class="fas fa-play me-2"></i>Lancer les dés
                            </button>
                            
                            <!-- Zone de résultats sous le bouton -->
                            <div class="mt-3">
                                <h6 class="mb-3">Résultats :</h6>
                                <div id="dice-results" class="border rounded p-3 bg-light" style="min-height: 120px;">
                                    <div class="text-muted text-center">
                                        <i class="fas fa-dice fa-2x mb-2"></i>
                                        <p class="mb-0">Sélectionnez un dé et lancez !</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Historique des jets -->
                        <div class="col-md-6">
                            <h6 class="mb-2">Historique des jets (50 derniers) :</h6>
                            <div id="dice-history" class="border rounded p-2 bg-white" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-history fa-lg mb-2"></i>
                                    <p class="mb-0 small">Chargement de l'historique...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan du lieu et Participants -->
    <div class="row mb-4">
        <!-- Plan du lieu -->
        <div class="col-lg-8">
            <?php if (!empty($place['map_url']) && file_exists($place['map_url'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map me-2"></i>Plan du lieu</h5>
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <!-- Zone du plan avec pions -->
                            <div id="mapContainer" class="position-relative" style="display: inline-block;">
                                <img id="mapImage" src="<?php echo htmlspecialchars($place['map_url']); ?>" class="img-fluid rounded" alt="Plan du lieu" style="max-height: 500px; cursor: crosshair;">
                                
                                <!-- Zone des pions sur le côté -->
                                <div id="tokenSidebar" class="position-absolute" style="right: -120px; top: 0; width: 100px; height: 500px; border: 2px dashed #ccc; border-radius: 8px; background: rgba(248, 249, 250, 0.8); padding: 10px; overflow-y: auto;">
                                    <div class="text-center mb-2">
                                        <small class="text-muted">Pions</small>
                                    </div>
                                    
                                    <!-- Pions des joueurs -->
                                    <?php foreach ($placePlayers as $player): ?>
                                        <?php 
                                        $tokenKey = 'player_' . $player['player_id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $displayName = $player['character_name'] ?: $player['username'];
                                        $imageUrl = !empty($player['profile_photo']) ? $player['profile_photo'] : 'images/default_character.png';
                                        ?>
                                        <div class="token" 
                                             data-token-type="player" 
                                             data-entity-id="<?php echo $player['player_id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #007bff; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($displayName); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des PNJ (seulement visibles) -->
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <?php 
                                        $tokenKey = 'npc_' . $npc['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        
                                        // Logique d'affichage selon l'identification
                                        $imageUrl = 'images/default_npc.png';
                                        $displayName = 'PNJ inconnu';
                                        
                                        if ($npc['is_identified']) {
                                            // PNJ identifié : afficher nom et photo
                                            $displayName = $npc['name'];
                                            if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                                $imageUrl = $npc['character_profile_photo'];
                                            } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                                $imageUrl = $npc['profile_photo'];
                                            }
                                        } else {
                                            // PNJ non identifié : afficher silhouette générique
                                            $imageUrl = 'images/default_npc.png';
                                        }
                                        ?>
                                        <div class="token" 
                                             data-token-type="npc" 
                                             data-entity-id="<?php echo $npc['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #28a745; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($displayName); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des monstres (seulement visibles) -->
                                    <?php foreach ($placeMonsters as $monster): ?>
                                        <?php 
                                        $tokenKey = 'monster_' . $monster['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        
                                        // Logique d'affichage selon l'identification
                                        $imageUrl = 'images/default_monster.png';
                                        $displayName = 'Monstre inconnu';
                                        
                                        if ($monster['is_identified']) {
                                            // Monstre identifié : afficher nom et photo
                                            $displayName = $monster['name'];
                                            $monster_image_path = "images/monstres/{$monster['csv_id']}.jpg";
                                            if (file_exists($monster_image_path)) {
                                                $imageUrl = $monster_image_path;
                                            } else {
                                                $imageUrl = 'images/default_monster.png';
                                            }
                                        } else {
                                            // Monstre non identifié : afficher silhouette générique
                                            $imageUrl = 'images/default_monster.png';
                                        }
                                        ?>
                                        <div class="token" 
                                             data-token-type="monster" 
                                             data-entity-id="<?php echo $monster['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #dc3545; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($displayName); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des objets (seulement visibles) -->
                                    <?php foreach ($placeObjects as $object): ?>
                                        <?php 
                                        $tokenKey = 'object_' . $object['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        
                                        // Icône selon le type et l'identification
                                        $icon_class = 'fa-box';
                                        $icon_color = '#6c757d';
                                        
                                        if (!$object['is_identified']) {
                                            $icon_class = 'fa-question';
                                            $icon_color = '#8B4513';
                                        } else {
                                            switch ($object['object_type']) {
                                                case 'poison':
                                                    $icon_class = 'fa-flask';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'magical_item':
                                                    $icon_class = 'fa-magic';
                                                    $icon_color = '#0dcaf0';
                                                    break;
                                                case 'weapon':
                                                    $icon_class = 'fa-sword';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'armor':
                                                    $icon_class = 'fa-shield-alt';
                                                    $icon_color = '#198754';
                                                    break;
                                                case 'letter':
                                                    $icon_class = 'fa-envelope';
                                                    $icon_color = '#0d6efd';
                                                    break;
                                                case 'coins':
                                                    $icon_class = 'fa-coins';
                                                    $icon_color = '#ffc107';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <div class="token object-token"
                                             data-token-type="object"
                                             data-entity-id="<?php echo $object['id']; ?>"
                                             data-object-id="<?php echo $object['id']; ?>"
                                             data-object-name="<?php echo htmlspecialchars($object['name']); ?>"
                                             data-object-type="<?php echo $object['object_type']; ?>"
                                             data-is-identified="<?php echo $object['is_identified'] ? 'true' : 'false'; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 24px; height: 24px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #FF8C00; border-radius: 4px; background: linear-gradient(45deg, #FFD700, #FFA500); box-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 12px; color: #8B4513; font-weight: bold;"
                                             title="<?php echo htmlspecialchars($object['name']); ?>">
                                            <i class="fas <?php echo $icon_class; ?>" style="color: <?php echo $icon_color; ?>;"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-map fa-3x text-muted mb-3"></i>
                        <h5>Aucun plan disponible</h5>
                        <p class="text-muted">Le MJ n'a pas encore ajouté de plan pour ce lieu.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Participants -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Participants</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <!-- Personnages Joueurs -->
                    <?php if (!empty($placePlayers)): ?>
                        <div class="mb-4">
                            <h6 class="text-primary mb-3"><i class="fas fa-user-circle me-2"></i>Personnages Joueurs</h6>
                            <div class="list-group list-group-flush">
                                <?php foreach ($placePlayers as $player): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($player['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?></h6>
                                                <small class="text-muted">
                                                    <?php if ($player['character_name']): ?>
                                                        <?php echo htmlspecialchars($player['class_name'] ?? 'Classe inconnue'); ?> - Niveau <?php echo (int)$player['level']; ?>
                                                    <?php else: ?>
                                                        Joueur
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <?php if ($player['character_name'] && !empty($player['character_id']) && $player['player_id'] == $user_id): ?>
                                                <a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ma feuille de personnage">
                                                    <i class="fas fa-user-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Message si aucun personnage joueur -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3"><i class="fas fa-user-circle me-2"></i>Personnages Joueurs</h6>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-user-slash fa-2x mb-2"></i>
                                <p class="mb-0">Aucun personnage joueur dans ce lieu.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- PNJ -->
                    <?php if (!empty($placeNpcs)): ?>
                        <div class="mb-4 npcs-section">
                            <h6 class="text-success mb-3"><i class="fas fa-user-tie me-2"></i>PNJ</h6>
                            <div class="list-group list-group-flush">
                                <?php foreach ($placeNpcs as $npc): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Logique d'affichage selon l'identification
                                            $imageUrl = 'images/default_npc.png';
                                            $displayName = 'Créature inconnue';
                                            
                                            if ($npc['is_identified']) {
                                                // PNJ identifié : afficher nom et photo
                                                $displayName = $npc['name'];
                                                if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                                    $imageUrl = $npc['character_profile_photo'];
                                                } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                                    $imageUrl = $npc['profile_photo'];
                                                }
                                            } else {
                                                // PNJ non identifié : afficher silhouette générique
                                                $imageUrl = 'images/default_npc.png';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($displayName); ?></h6>
                                                <small class="text-muted">PNJ</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Monstres -->
                    <?php if (!empty($placeMonsters)): ?>
                        <div class="mb-4 monsters-section">
                            <h6 class="text-danger mb-3"><i class="fas fa-dragon me-2"></i>Monstres</h6>
                            <div class="list-group list-group-flush">
                                <?php foreach ($placeMonsters as $monster): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Logique d'affichage selon l'identification
                                            $imageUrl = 'images/default_monster.png';
                                            $displayName = 'Créature inconnue';
                                            
                                            if ($monster['is_identified']) {
                                                // Monstre identifié : afficher nom et photo
                                                $displayName = $monster['name'];
                                                $monster_image_path = "images/monstres/{$monster['csv_id']}.jpg";
                                                if (file_exists($monster_image_path)) {
                                                    $imageUrl = $monster_image_path;
                                                } else {
                                                    $imageUrl = 'images/default_monster.png';
                                                }
                                            } else {
                                                // Monstre non identifié : afficher silhouette générique
                                                $imageUrl = 'images/default_monster.png';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($displayName); ?></h6>
                                                <small class="text-muted">
                                                    <?php if ($monster['is_identified']): ?>
                                                        <?php echo htmlspecialchars($monster['type'] ?? 'Type inconnu'); ?> - 
                                                        <?php if ($monster['quantity'] > 1): ?>
                                                            <?php echo (int)$monster['quantity']; ?> créatures
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($monster['size'] ?? 'Taille inconnue'); ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        Créature non identifiée
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Objets -->
                    <?php if (!empty($placeObjects)): ?>
                        <div class="mb-4 objects-section">
                            <h6 class="text-warning mb-3"><i class="fas fa-box me-2"></i>Objets</h6>
                            <div class="list-group list-group-flush">
                                <?php foreach ($placeObjects as $object): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Icône selon le type et l'identification
                                            $icon_class = 'fa-box';
                                            $icon_color = '#6c757d';
                                            
                                            if (!$object['is_identified']) {
                                                $icon_class = 'fa-question';
                                                $icon_color = '#8B4513';
                                            } else {
                                                switch ($object['object_type']) {
                                                    case 'poison':
                                                        $icon_class = 'fa-flask';
                                                        $icon_color = '#dc3545';
                                                        break;
                                                    case 'magical_item':
                                                        $icon_class = 'fa-magic';
                                                        $icon_color = '#0dcaf0';
                                                        break;
                                                    case 'weapon':
                                                        $icon_class = 'fa-sword';
                                                        $icon_color = '#dc3545';
                                                        break;
                                                    case 'armor':
                                                        $icon_class = 'fa-shield-alt';
                                                        $icon_color = '#198754';
                                                        break;
                                                    case 'letter':
                                                        $icon_class = 'fa-envelope';
                                                        $icon_color = '#0d6efd';
                                                        break;
                                                    case 'coins':
                                                        $icon_class = 'fa-coins';
                                                        $icon_color = '#ffc107';
                                                        break;
                                                }
                                            }
                                            ?>
                                            <div class="bg-warning rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas <?php echo $icon_class; ?> text-white" style="color: <?php echo $icon_color; ?> !important;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($object['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php if ($object['is_identified']): ?>
                                                        <?php 
                                                        $type_label = ucfirst($object['object_type']);
                                                        switch ($object['object_type']) {
                                                            case 'poison':
                                                                $type_label = 'Poison';
                                                                break;
                                                            case 'coins':
                                                                $type_label = 'Pièces';
                                                                break;
                                                            case 'letter':
                                                                $type_label = 'Lettre';
                                                                break;
                                                            case 'weapon':
                                                                $type_label = 'Arme';
                                                                break;
                                                            case 'armor':
                                                                $type_label = 'Armure';
                                                                break;
                                                            case 'magical_item':
                                                                $type_label = 'Objet magique';
                                                                break;
                                                        }
                                                        echo $type_label;
                                                        ?>
                                                    <?php else: ?>
                                                        Objet mystérieux
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Message si aucun participant -->
                    <?php if (empty($placePlayers) && empty($placeNpcs) && empty($placeMonsters) && empty($placeObjects)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-users fa-2x mb-3"></i>
                            <p>Aucun participant dans ce lieu.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Notes du lieu -->
    <?php if (!empty($place['notes'])): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes du lieu</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($place['notes'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function initializeTokenSystem() {
    const mapImage = document.getElementById('mapImage');
    const tokens = document.querySelectorAll('.token');
    
    if (tokens.length === 0) return;
    
    tokens.forEach(token => {
        const isOnMap = token.dataset.isOnMap === 'true';
        
        if (isOnMap && mapImage) {
            const x = parseInt(token.dataset.positionX);
            const y = parseInt(token.dataset.positionY);
            positionTokenOnMap(token, x, y);
        }
    });
}

function positionTokenOnMap(token, x, y) {
    // Vérifier que la carte existe
    const mapContainer = document.getElementById('mapContainer');
    if (!mapContainer) return;
    
    // Retirer le pion de son conteneur actuel
    token.remove();
    
    // Ajouter le pion au conteneur du plan
    mapContainer.appendChild(token);
    
    // Positionner le pion (identique à view_scene.php)
    token.style.position = 'absolute';
    token.style.left = x + '%';
    token.style.top = y + '%';
    token.style.transform = 'translate(-50%, -50%)';
    token.style.zIndex = '1000';
    token.style.margin = '0';
    token.style.pointerEvents = 'auto';
    token.dataset.isOnMap = 'true';
    token.dataset.positionX = x;
    token.dataset.positionY = y;
}

// Initialiser le système de pions après que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    initializeTokenSystem();
    startAutoUpdate();
});

// Système de mise à jour automatique des positions
let autoUpdateInterval;
let lastUpdateTime = null;
let lastNpcsData = {};
let lastMonstersData = {};
let lastObjectsData = {};

function startAutoUpdate() {
    // Mettre à jour toutes les 2 secondes
    autoUpdateInterval = setInterval(updateTokenPositions, 2000);
}

function stopAutoUpdate() {
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
        autoUpdateInterval = null;
    }
}

function updateTokenPositions() {
    const data = {
        place_id: <?php echo $place_id; ?>,
        last_update: lastUpdateTime
    };

    fetch('get_token_positions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (result.positions) {
                applyPositionUpdates(result.positions);
            }
            
            if (result.npcs) {
                // Comparer avec les données précédentes pour détecter les changements
                if (JSON.stringify(result.npcs) !== JSON.stringify(lastNpcsData)) {
                    updateNpcsDisplay(result.npcs);
                    lastNpcsData = result.npcs;
                }
            }
            
            if (result.monsters) {
                // Comparer avec les données précédentes pour détecter les changements
                if (JSON.stringify(result.monsters) !== JSON.stringify(lastMonstersData)) {
                    updateMonstersDisplay(result.monsters);
                    lastMonstersData = result.monsters;
                }
            }
            
            if (result.objects) {
                // Comparer avec les données précédentes pour détecter les changements
                if (JSON.stringify(result.objects) !== JSON.stringify(lastObjectsData)) {
                    updateObjectsDisplay(result.objects);
                    lastObjectsData = result.objects;
                }
            }
            
            lastUpdateTime = result.timestamp;
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la mise à jour:', error);
    });
}

function applyPositionUpdates(positions) {
    const tokens = document.querySelectorAll('.token');
    
    tokens.forEach(token => {
        const tokenType = token.dataset.tokenType;
        const entityId = token.dataset.entityId;
        const tokenKey = `${tokenType}_${entityId}`;
        
        if (positions[tokenKey]) {
            const newPosition = positions[tokenKey];
            const currentX = parseInt(token.dataset.positionX);
            const currentY = parseInt(token.dataset.positionY);
            const currentIsOnMap = token.dataset.isOnMap === 'true';
            
            // Vérifier si la position a changé
            if (newPosition.x !== currentX || newPosition.y !== currentY || newPosition.is_on_map !== currentIsOnMap) {
                // Mettre à jour les attributs
                token.dataset.positionX = newPosition.x;
                token.dataset.positionY = newPosition.y;
                token.dataset.isOnMap = newPosition.is_on_map ? 'true' : 'false';
                
                // Appliquer la nouvelle position
                if (newPosition.is_on_map) {
                    // Vérifier que la carte existe avant de positionner sur la carte
                    const mapContainer = document.getElementById('mapContainer');
                    if (mapContainer) {
                        positionTokenOnMap(token, newPosition.x, newPosition.y);
                    } else {
                        resetTokenToSidebar(token);
                    }
                } else {
                    resetTokenToSidebar(token);
                }
            }
        }
    });
}

function resetTokenToSidebar(token) {
    // Retirer le pion du conteneur du plan
    token.remove();
    
    // Ajouter le pion à la sidebar
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (!tokenSidebar) return;
    
    tokenSidebar.appendChild(token);
    
    // Réinitialiser les styles
    token.style.position = 'static';
    token.style.left = 'auto';
    token.style.top = 'auto';
    token.style.transform = 'none';
    token.style.zIndex = 'auto';
    token.style.margin = '2px';
    token.style.pointerEvents = 'auto';
    token.dataset.isOnMap = 'false';
}

function updateNpcsDisplay(npcs) {
    // Mettre à jour les pions des PNJ
    const npcTokens = document.querySelectorAll('.token[data-token-type="npc"]');
    npcTokens.forEach(token => {
        const entityId = token.dataset.entityId;
        const npcKey = `npc_${entityId}`;
        const npcData = npcs[npcKey];
        
        if (npcData) {
            if (npcData.is_visible) {
                // PNJ visible : afficher le pion
                token.style.display = 'inline-block';
                
                // Mettre à jour l'affichage selon l'identification
                if (npcData.is_identified) {
                    // PNJ identifié : nom réel et photo
                    token.title = npcData.name;
                    if (npcData.character_profile_photo) {
                        token.style.backgroundImage = `url('${npcData.character_profile_photo}')`;
                    } else if (npcData.profile_photo) {
                        token.style.backgroundImage = `url('${npcData.profile_photo}')`;
                    }
                } else {
                    // PNJ non identifié : silhouette générique
                    token.title = 'PNJ inconnu';
                    token.style.backgroundImage = 'url("images/default_npc.png")';
                }
            } else {
                // PNJ non visible : masquer le pion
                token.style.display = 'none';
            }
        }
    });
    
    // Vérifier s'il y a de nouveaux PNJ à ajouter ou des PNJ à supprimer
    updateNpcsTokens(npcs);
    
    // Mettre à jour la liste des PNJ
    updateNpcsList(npcs);
}

function updateNpcsTokens(npcs) {
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (!tokenSidebar) return;
    
    // Créer un ensemble des IDs de PNJ existants
    const existingNpcIds = new Set();
    document.querySelectorAll('.token[data-token-type="npc"]').forEach(token => {
        existingNpcIds.add(token.dataset.entityId);
    });
    
    // Ajouter les nouveaux PNJ visibles
    Object.keys(npcs).forEach(npcKey => {
        const npcData = npcs[npcKey];
        const entityId = npcKey.replace('npc_', '');
        
        if (npcData.is_visible && !existingNpcIds.has(entityId)) {
            // Créer un nouveau pion pour ce PNJ
            const newToken = createNpcToken(entityId, npcData);
            tokenSidebar.appendChild(newToken);
        }
    });
    
    // Supprimer les PNJ qui ne sont plus visibles
    document.querySelectorAll('.token[data-token-type="npc"]').forEach(token => {
        const entityId = token.dataset.entityId;
        const npcKey = `npc_${entityId}`;
        const npcData = npcs[npcKey];
        
        if (!npcData || !npcData.is_visible) {
            token.remove();
        }
    });
}

function createNpcToken(entityId, npcData) {
    const token = document.createElement('div');
    token.className = 'token';
    token.dataset.tokenType = 'npc';
    token.dataset.entityId = entityId;
    token.dataset.positionX = '0';
    token.dataset.positionY = '0';
    token.dataset.isOnMap = 'false';
    
    // Logique d'affichage selon l'identification
    let imageUrl = 'images/default_npc.png';
    let displayName = 'PNJ inconnu';
    
    if (npcData.is_identified) {
        displayName = npcData.name;
        if (npcData.character_profile_photo) {
            imageUrl = npcData.character_profile_photo;
        } else if (npcData.profile_photo) {
            imageUrl = npcData.profile_photo;
        }
    }
    
    token.style.cssText = 'width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #28a745; border-radius: 50%; background-image: url("' + imageUrl + '"); background-size: cover; background-position: center;';
    token.title = displayName;
    
    return token;
}

function updateMonstersDisplay(monsters) {
    // Mettre à jour les pions des monstres
    const monsterTokens = document.querySelectorAll('.token[data-token-type="monster"]');
    monsterTokens.forEach(token => {
        const entityId = token.dataset.entityId;
        const monsterKey = `monster_${entityId}`;
        const monsterData = monsters[monsterKey];
        
        if (monsterData) {
            if (monsterData.is_visible) {
                // Monstre visible : afficher le pion
                token.style.display = 'inline-block';
                
                // Mettre à jour l'affichage selon l'identification
                if (monsterData.is_identified) {
                    // Monstre identifié : nom réel et photo
                    token.title = monsterData.name;
                    token.style.backgroundImage = `url('images/monstres/${monsterData.monster_id}.jpg')`;
                } else {
                    // Monstre non identifié : silhouette générique
                    token.title = 'Monstre inconnu';
                    token.style.backgroundImage = 'url("images/default_monster.png")';
                }
            } else {
                // Monstre non visible : masquer le pion
                token.style.display = 'none';
            }
        }
    });
    
    // Vérifier s'il y a de nouveaux monstres à ajouter ou des monstres à supprimer
    updateMonstersTokens(monsters);
    
    // Mettre à jour la liste des monstres
    updateMonstersList(monsters);
}

function updateMonstersTokens(monsters) {
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (!tokenSidebar) return;
    
    // Créer un ensemble des IDs de monstres existants
    const existingMonsterIds = new Set();
    document.querySelectorAll('.token[data-token-type="monster"]').forEach(token => {
        existingMonsterIds.add(token.dataset.entityId);
    });
    
    // Ajouter les nouveaux monstres visibles
    Object.keys(monsters).forEach(monsterKey => {
        const monsterData = monsters[monsterKey];
        const entityId = monsterKey.replace('monster_', '');
        
        if (monsterData.is_visible && !existingMonsterIds.has(entityId)) {
            // Créer un nouveau pion pour ce monstre
            const newToken = createMonsterToken(entityId, monsterData);
            tokenSidebar.appendChild(newToken);
        }
    });
    
    // Supprimer les monstres qui ne sont plus visibles
    document.querySelectorAll('.token[data-token-type="monster"]').forEach(token => {
        const entityId = token.dataset.entityId;
        const monsterKey = `monster_${entityId}`;
        const monsterData = monsters[monsterKey];
        
        if (!monsterData || !monsterData.is_visible) {
            token.remove();
        }
    });
}

function createMonsterToken(entityId, monsterData) {
    const token = document.createElement('div');
    token.className = 'token';
    token.dataset.tokenType = 'monster';
    token.dataset.entityId = entityId;
    token.dataset.positionX = '0';
    token.dataset.positionY = '0';
    token.dataset.isOnMap = 'false';
    
    // Logique d'affichage selon l'identification
    let imageUrl = 'images/default_monster.png';
    let displayName = 'Monstre inconnu';
    
    if (monsterData.is_identified) {
        displayName = monsterData.name;
        imageUrl = `images/monstres/${monsterData.monster_id}.jpg`;
    }
    
    token.style.cssText = 'width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #dc3545; border-radius: 50%; background-image: url("' + imageUrl + '"); background-size: cover; background-position: center;';
    token.title = displayName;
    
    return token;
}

function updateNpcsList(npcs) {
    // Cette fonction pourrait être étendue pour mettre à jour dynamiquement la liste des PNJ
    // Pour l'instant, on se contente de la mise à jour des pions
}

function updateMonstersList(monsters) {
    // Cette fonction pourrait être étendue pour mettre à jour dynamiquement la liste des monstres
    // Pour l'instant, on se contente de la mise à jour des pions
}

function updateObjectsDisplay(objects) {
    // Mettre à jour les pions des objets
    const objectTokens = document.querySelectorAll('.token[data-token-type="object"]');
    objectTokens.forEach(token => {
        const entityId = token.dataset.entityId;
        const objectKey = `object_${entityId}`;
        const objectData = objects[objectKey];
        
        if (objectData) {
            if (objectData.is_visible) {
                // Objet visible : afficher le pion
                token.style.display = 'inline-block';
                
                // Mettre à jour l'affichage selon l'identification
                if (objectData.is_identified) {
                    // Objet identifié : nom réel et icône spécifique
                    token.title = objectData.name;
                    updateObjectTokenIcon(token, objectData.object_type, true);
                } else {
                    // Objet non identifié : nom générique et icône "?"
                    token.title = objectData.name;
                    updateObjectTokenIcon(token, objectData.object_type, false);
                }
            } else {
                // Objet non visible : masquer le pion
                token.style.display = 'none';
            }
        }
    });
    
    // Vérifier s'il y a de nouveaux objets à ajouter ou des objets à supprimer
    updateObjectsTokens(objects);
    
    // Mettre à jour la liste des objets
    updateObjectsList(objects);
}

function updateObjectsTokens(objects) {
    const tokenSidebar = document.getElementById('tokenSidebar');
    if (!tokenSidebar) return;
    
    // Créer un ensemble des IDs d'objets existants
    const existingObjectIds = new Set();
    document.querySelectorAll('.token[data-token-type="object"]').forEach(token => {
        existingObjectIds.add(token.dataset.entityId);
    });
    
    // Ajouter les nouveaux objets visibles
    Object.keys(objects).forEach(objectKey => {
        const objectData = objects[objectKey];
        const entityId = objectKey.replace('object_', '');
        
        if (objectData.is_visible && !existingObjectIds.has(entityId)) {
            // Créer un nouveau pion pour cet objet
            const newToken = createObjectToken(entityId, objectData);
            tokenSidebar.appendChild(newToken);
        }
    });
    
    // Supprimer les objets qui ne sont plus visibles
    document.querySelectorAll('.token[data-token-type="object"]').forEach(token => {
        const entityId = token.dataset.entityId;
        const objectKey = `object_${entityId}`;
        const objectData = objects[objectKey];
        
        if (!objectData || !objectData.is_visible) {
            token.remove();
        }
    });
}

function createObjectToken(entityId, objectData) {
    const token = document.createElement('div');
    token.className = 'token object-token';
    token.dataset.tokenType = 'object';
    token.dataset.entityId = entityId;
    token.dataset.objectId = entityId;
    token.dataset.objectName = objectData.name;
    token.dataset.objectType = objectData.object_type;
    token.dataset.isIdentified = objectData.is_identified ? 'true' : 'false';
    token.dataset.positionX = '0';
    token.dataset.positionY = '0';
    token.dataset.isOnMap = 'false';
    
    // Style du pion
    token.style.cssText = 'width: 24px; height: 24px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #FF8C00; border-radius: 4px; background: linear-gradient(45deg, #FFD700, #FFA500); box-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 12px; color: #8B4513; font-weight: bold;';
    token.title = objectData.name;
    
    // Ajouter l'icône
    updateObjectTokenIcon(token, objectData.object_type, objectData.is_identified);
    
    return token;
}

function updateObjectTokenIcon(token, objectType, isIdentified) {
    // Supprimer l'icône existante
    const existingIcon = token.querySelector('i');
    if (existingIcon) {
        existingIcon.remove();
    }
    
    // Créer la nouvelle icône
    const icon = document.createElement('i');
    
    if (!isIdentified) {
        // Objet non identifié : afficher un "?"
        icon.className = 'fas fa-question';
        icon.style.color = '#8B4513';
        icon.style.fontWeight = 'bold';
    } else {
        // Objet identifié : afficher l'icône selon le type
        switch (objectType) {
            case 'poison':
                icon.className = 'fas fa-flask';
                icon.style.color = '#dc3545';
                break;
            case 'magical_item':
                icon.className = 'fas fa-magic';
                icon.style.color = '#0dcaf0';
                break;
            case 'weapon':
                icon.className = 'fas fa-sword';
                icon.style.color = '#dc3545';
                break;
            case 'armor':
                icon.className = 'fas fa-shield-alt';
                icon.style.color = '#198754';
                break;
            case 'letter':
                icon.className = 'fas fa-envelope';
                icon.style.color = '#0d6efd';
                break;
            case 'coins':
                icon.className = 'fas fa-coins';
                icon.style.color = '#ffc107';
                break;
            default:
                icon.className = 'fas fa-box';
                icon.style.color = '#6c757d';
        }
    }
    
    token.appendChild(icon);
}

function updateObjectsList(objects) {
    // Cette fonction pourrait être étendue pour mettre à jour dynamiquement la liste des objets
    // Pour l'instant, on se contente de la mise à jour des pions
}

// Variable pour stocker l'ID du lieu actuel
let currentPlaceId = <?php echo (int)$place_id; ?>;

// Fonction pour vérifier si le joueur a changé de lieu
function checkPlayerLocationChange() {
    fetch('check_player_location.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (!data.has_location) {
                    // Le joueur n'est plus dans aucun lieu
                    console.log('Joueur retiré de tous les lieux, redirection...');
                    window.location.href = 'campaigns.php';
                } else if (data.place_id !== currentPlaceId) {
                    // Le joueur a changé de lieu
                    console.log('Joueur déplacé vers un nouveau lieu, rechargement de la page...');
                    window.location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification de la localisation:', error);
        });
}

// Fonction pour mettre à jour la liste des participants
function updateParticipantsList() {
    fetch('get_participants_list.php?place_id=<?php echo (int)$place_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNpcsSection(data.npcs);
                updateMonstersSection(data.monsters);
                updateNpcTokens(data.npcs);
                updateMonsterTokens(data.monsters);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour des participants:', error);
        });
}

// Fonction pour mettre à jour les pions des monstres sur la carte
function updateMonsterTokens(monsters) {
    // Trouver tous les pions de monstres existants
    const existingTokens = document.querySelectorAll('.token[data-token-type="monster"]');
    
    existingTokens.forEach(token => {
        const entityId = token.getAttribute('data-entity-id');
        const monster = monsters.find(m => m.id == entityId);
        
        if (monster) {
            // Mettre à jour l'image du pion
            let imageUrl = 'images/default_monster.png';
            if (monster.is_identified) {
                imageUrl = `images/monstres/${monster.csv_id}.jpg`;
            }
            
            // Mettre à jour le background-image
            token.style.backgroundImage = `url('${imageUrl}')`;
            
            // Mettre à jour le titre
            const displayName = monster.is_identified ? monster.name : 'Monstre inconnu';
            token.setAttribute('title', displayName);
        }
    });
}

// Fonction pour mettre à jour les pions des PNJ sur la carte
function updateNpcTokens(npcs) {
    // Trouver tous les pions de PNJ existants
    const existingTokens = document.querySelectorAll('.token[data-token-type="npc"]');
    
    existingTokens.forEach(token => {
        const entityId = token.getAttribute('data-entity-id');
        const npc = npcs.find(n => n.id == entityId);
        
        if (npc) {
            // Mettre à jour l'image du pion
            let imageUrl = 'images/default_npc.png';
            if (npc.is_identified) {
                if (npc.character_profile_photo) {
                    imageUrl = npc.character_profile_photo;
                } else if (npc.profile_photo) {
                    imageUrl = npc.profile_photo;
                }
            }
            
            // Mettre à jour le background-image
            token.style.backgroundImage = `url('${imageUrl}')`;
            
            // Mettre à jour le titre
            const displayName = npc.is_identified ? npc.name : 'PNJ inconnu';
            token.setAttribute('title', displayName);
        }
    });
}

// Fonction pour mettre à jour la section PNJ
function updateNpcsSection(npcs) {
    const npcsSection = document.querySelector('.npcs-section');
    if (!npcsSection) return;
    
    const npcsContainer = npcsSection.querySelector('.list-group');
    if (!npcsContainer) return;
    
    // Vider la liste actuelle
    npcsContainer.innerHTML = '';
    
    if (npcs.length === 0) {
        // Masquer la section PNJ si aucun PNJ visible
        npcsSection.style.display = 'none';
    } else {
        // Afficher la section PNJ
        npcsSection.style.display = 'block';
        
        // Ajouter les PNJ
        npcs.forEach(npc => {
            const npcItem = createNpcItem(npc);
            npcsContainer.appendChild(npcItem);
        });
    }
}

// Fonction pour mettre à jour la section Monstres
function updateMonstersSection(monsters) {
    const monstersSection = document.querySelector('.monsters-section');
    if (!monstersSection) return;
    
    const monstersContainer = monstersSection.querySelector('.list-group');
    if (!monstersContainer) return;
    
    // Vider la liste actuelle
    monstersContainer.innerHTML = '';
    
    if (monsters.length === 0) {
        // Masquer la section Monstres si aucun monstre visible
        monstersSection.style.display = 'none';
    } else {
        // Afficher la section Monstres
        monstersSection.style.display = 'block';
        
        // Ajouter les monstres
        monsters.forEach(monster => {
            const monsterItem = createMonsterItem(monster);
            monstersContainer.appendChild(monsterItem);
        });
    }
}

// Fonction pour créer un élément PNJ
function createNpcItem(npc) {
    const div = document.createElement('div');
    div.className = 'list-group-item px-0 py-2';
    
    const displayName = npc.is_identified ? npc.name : 'Créature inconnue';
    let imageUrl = 'images/default_npc.png';
    
    if (npc.is_identified) {
        if (npc.character_profile_photo) {
            imageUrl = npc.character_profile_photo;
        } else if (npc.profile_photo) {
            imageUrl = npc.profile_photo;
        }
    }
    
    div.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${imageUrl}" alt="${displayName}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='images/default_npc.png'">
            <div class="flex-grow-1">
                <h6 class="mb-1">${displayName}</h6>
                <small class="text-muted">PNJ</small>
            </div>
        </div>
    `;
    
    return div;
}

// Fonction pour créer un élément Monstre
function createMonsterItem(monster) {
    const div = document.createElement('div');
    div.className = 'list-group-item px-0 py-2';
    
    const displayName = monster.is_identified ? monster.name : 'Créature inconnue';
    const imageUrl = monster.is_identified ? `images/monstres/${monster.csv_id}.jpg` : 'images/default_monster.png';
    
    let details = '';
    if (monster.is_identified) {
        details = `${monster.type || 'Type inconnu'} - `;
        if (monster.quantity > 1) {
            details += `${monster.quantity} créatures`;
        } else {
            details += monster.size || 'Taille inconnue';
        }
    } else {
        details = 'Créature non identifiée';
    }
    
    div.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${imageUrl}" alt="${displayName}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='images/default_monster.png'">
            <div class="flex-grow-1">
                <h6 class="mb-1">${displayName}</h6>
                <small class="text-muted">${details}</small>
            </div>
        </div>
    `;
    
    return div;
}

// Vérifier le changement de lieu toutes les 3 secondes
let locationCheckInterval = setInterval(checkPlayerLocationChange, 3000);

// Mettre à jour la liste des participants toutes les 2 secondes
let participantsUpdateInterval = setInterval(updateParticipantsList, 2000);

// Arrêter la mise à jour automatique quand la page se ferme
window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
    if (locationCheckInterval) {
        clearInterval(locationCheckInterval);
    }
    if (participantsUpdateInterval) {
        clearInterval(participantsUpdateInterval);
    }
    if (diceHistoryInterval) {
        clearInterval(diceHistoryInterval);
    }
});

// ===== LOGIQUE DES DÉS =====

let selectedDiceSides = null;
let currentCampaignId = <?php echo (int)$place['campaign_id']; ?>;

// Gestion de la sélection des dés
document.addEventListener('DOMContentLoaded', function() {
    const diceButtons = document.querySelectorAll('.dice-btn');
    const rollButton = document.getElementById('roll-dice-btn');
    const resultsDiv = document.getElementById('dice-results');
    
        // Charger l'historique des jets au chargement de la page
        loadDiceHistory();
        
        // Mettre à jour l'historique des jets automatiquement toutes les 3 secondes
        diceHistoryInterval = setInterval(loadDiceHistory, 3000);
    
    // Ajouter les événements aux boutons de dés
    diceButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la sélection précédente
            diceButtons.forEach(btn => {
                btn.classList.remove('btn-primary', 'btn-success');
                btn.classList.add('btn-outline-primary', 'btn-outline-success');
            });
            
            // Sélectionner le dé actuel
            selectedDiceSides = parseInt(this.getAttribute('data-sides'));
            this.classList.remove('btn-outline-primary', 'btn-outline-success');
            
            if (selectedDiceSides === 100) {
                this.classList.add('btn-success');
            } else {
                this.classList.add('btn-primary');
            }
            
            // Activer le bouton de lancer
            rollButton.disabled = false;
            
            // Mettre à jour l'affichage
            updateDiceSelectionDisplay();
        });
    });
    
    // Gestion du lancer de dés
    rollButton.addEventListener('click', function() {
        if (selectedDiceSides) {
            rollDice();
        }
    });
});

// Fonction pour mettre à jour l'affichage de la sélection
function updateDiceSelectionDisplay() {
    const resultsDiv = document.getElementById('dice-results');
    const quantity = document.getElementById('dice-quantity').value;
    
    if (selectedDiceSides) {
        resultsDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-primary"></i>
                <p class="mb-0"><strong>${quantity} dé${quantity > 1 ? 's' : ''} à ${selectedDiceSides} face${selectedDiceSides > 1 ? 's' : ''}</strong></p>
                <small class="text-muted">Prêt à lancer !</small>
            </div>
        `;
    }
}

// Fonction pour obtenir l'icône du dé
function getDiceIcon(sides) {
    switch(sides) {
        case 4: return 'd4';
        case 6: return '';
        case 8: return 'd8';
        case 10: return 'd10';
        case 12: return 'd12';
        case 20: return 'd20';
        case 100: return '';
        default: return '';
    }
}

// Fonction pour lancer les dés
function rollDice() {
    const quantity = parseInt(document.getElementById('dice-quantity').value);
    const resultsDiv = document.getElementById('dice-results');
    const rollButton = document.getElementById('roll-dice-btn');
    
    // Désactiver le bouton pendant l'animation
    rollButton.disabled = true;
    rollButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Lancement...';
    
    // Animation de lancer
    let animationCount = 0;
    const animationInterval = setInterval(() => {
        animationCount++;
        const randomResults = [];
        for (let i = 0; i < quantity; i++) {
            randomResults.push(Math.floor(Math.random() * selectedDiceSides) + 1);
        }
        
        resultsDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-warning"></i>
                <div class="mb-2">
                    ${randomResults.map(result => `<span class="badge bg-warning text-dark me-1">${result}</span>`).join('')}
                </div>
                <small class="text-muted">Lancement...</small>
            </div>
        `;
        
        if (animationCount >= 10) {
            clearInterval(animationInterval);
            showFinalResults(randomResults);
        }
    }, 100);
}

// Fonction pour afficher les résultats finaux
function showFinalResults(results) {
    const resultsDiv = document.getElementById('dice-results');
    const rollButton = document.getElementById('roll-dice-btn');
    const total = results.reduce((sum, result) => sum + result, 0);
    const maxResult = Math.max(...results);
    const minResult = Math.min(...results);
    
    // Réactiver le bouton
    rollButton.disabled = false;
    rollButton.innerHTML = '<i class="fas fa-play me-2"></i>Lancer les dés';
    
    // Afficher les résultats
    let resultsHtml = `
        <div class="text-center">
            <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-success"></i>
            <h5 class="text-success mb-3">Résultats du lancer</h5>
    `;
    
    // Afficher chaque résultat
    resultsHtml += '<div class="mb-3">';
    results.forEach((result, index) => {
        let badgeClass = 'bg-primary';
        if (result === selectedDiceSides) {
            badgeClass = 'bg-success'; // Critique
        } else if (result === 1 && selectedDiceSides === 20) {
            badgeClass = 'bg-danger'; // Échec critique (uniquement sur D20)
        }
        resultsHtml += `<span class="badge ${badgeClass} me-1 fs-6">${result}</span>`;
    });
    resultsHtml += '</div>';
    
    // Statistiques
    resultsHtml += `
        <div class="row text-center">
            <div class="col-4">
                <small class="text-muted">Total</small><br>
                <strong class="text-primary">${total}</strong>
            </div>
            <div class="col-4">
                <small class="text-muted">Max</small><br>
                <strong class="text-success">${maxResult}</strong>
            </div>
            <div class="col-4">
                <small class="text-muted">Min</small><br>
                <strong class="text-danger">${minResult}</strong>
            </div>
        </div>
    `;
    
    // Message spécial pour les critiques (uniquement sur D20)
    if (selectedDiceSides === 20) {
        if (results.includes(20) && results.includes(1)) {
            resultsHtml += '<div class="alert alert-warning mt-2 mb-0"><small><i class="fas fa-exclamation-triangle me-1"></i>Critique et échec critique !</small></div>';
        } else if (results.includes(20)) {
            resultsHtml += '<div class="alert alert-success mt-2 mb-0"><small><i class="fas fa-star me-1"></i>Critique !</small></div>';
        } else if (results.includes(1)) {
            resultsHtml += '<div class="alert alert-danger mt-2 mb-0"><small><i class="fas fa-times me-1"></i>Échec critique !</small></div>';
        }
    } else if (results.includes(selectedDiceSides)) {
        // Critique sur les autres dés (mais pas d'échec critique)
        resultsHtml += '<div class="alert alert-success mt-2 mb-0"><small><i class="fas fa-star me-1"></i>Critique !</small></div>';
    }
    
    resultsHtml += '</div>';
    resultsDiv.innerHTML = resultsHtml;
    
    // Ajouter un effet sonore visuel (optionnel)
    resultsDiv.style.animation = 'pulse 0.5s ease-in-out';
    setTimeout(() => {
        resultsDiv.style.animation = '';
    }, 500);
    
    // Sauvegarder le jet de dés
    saveDiceRoll(results, total, maxResult, minResult);
}

// Mettre à jour l'affichage quand la quantité change
document.getElementById('dice-quantity').addEventListener('change', function() {
    if (selectedDiceSides) {
        updateDiceSelectionDisplay();
    }
});

// Fonction pour charger l'historique des jets de dés
function loadDiceHistory() {
    // Les joueurs ne voient que les jets visibles (show_hidden=false par défaut)
    fetch(`get_dice_rolls_history.php?campaign_id=${currentCampaignId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDiceHistory(data.rolls);
            } else {
                console.error('Erreur lors du chargement de l\'historique:', data.error);
                document.getElementById('dice-history').innerHTML = `
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                        <p class="mb-0 small">Erreur lors du chargement de l'historique</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement de l\'historique:', error);
            document.getElementById('dice-history').innerHTML = `
                <div class="text-muted text-center py-3">
                    <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                    <p class="mb-0 small">Erreur de connexion</p>
                </div>
            `;
        });
}

// Fonction pour afficher l'historique des jets
function displayDiceHistory(rolls) {
    const historyDiv = document.getElementById('dice-history');
    
    if (rolls.length === 0) {
        historyDiv.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-dice fa-lg mb-2"></i>
                <p class="mb-0 small">Aucun jet de dés enregistré</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    rolls.forEach(roll => {
        const rollDate = new Date(roll.rolled_at);
        const timeStr = rollDate.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        // Déterminer les classes CSS pour les résultats
        let resultBadges = '';
        roll.results.forEach(result => {
            let badgeClass = 'bg-secondary';
            if (roll.has_crit && result === roll.dice_sides) {
                badgeClass = 'bg-success';
            } else if (roll.has_fumble && result === 1 && roll.dice_sides === 20) {
                badgeClass = 'bg-danger';
            } else if (result === roll.dice_sides) {
                badgeClass = 'bg-primary';
            }
            resultBadges += `<span class="badge ${badgeClass} me-1">${result}</span>`;
        });
        
        html += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center">
                        <strong class="me-2">${roll.username}</strong>
                        <span class="badge bg-outline-primary me-2">${roll.dice_type}</span>
                        <small class="text-muted">${roll.quantity} dé${roll.quantity > 1 ? 's' : ''}</small>
                    </div>
                    <div class="mt-1">
                        ${resultBadges}
                        <span class="ms-2 text-primary"><strong>Total: ${roll.total}</strong></span>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted">${timeStr}</small>
                    ${roll.has_crit ? '<i class="fas fa-star text-success ms-1" title="Critique"></i>' : ''}
                    ${roll.has_fumble ? '<i class="fas fa-times text-danger ms-1" title="Échec critique"></i>' : ''}
                </div>
            </div>
        `;
    });
    
    historyDiv.innerHTML = html;
}

// Fonction pour sauvegarder un jet de dés
function saveDiceRoll(results, total, maxResult, minResult) {
    const diceType = `D${selectedDiceSides}`;
    const quantity = parseInt(document.getElementById('dice-quantity').value);
    
    const rollData = {
        campaign_id: currentCampaignId,
        dice_type: diceType,
        dice_sides: selectedDiceSides,
        quantity: quantity,
        results: results,
        total: total,
        max_result: maxResult,
        min_result: minResult
    };
    
    fetch('save_dice_roll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(rollData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger l'historique après avoir sauvegardé
            loadDiceHistory();
        } else {
            console.error('Erreur lors de la sauvegarde du jet:', data.error);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la sauvegarde du jet:', error);
    });
}
</script>

</body>
</html>
