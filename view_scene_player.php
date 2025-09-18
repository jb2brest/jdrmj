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
           m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL AND sn.is_visible = 1
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$placeMonsters = $stmt->fetchAll();

include 'includes/layout.php';
?>

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

    <!-- Plan du lieu -->
    <?php if (!empty($place['map_url']) && file_exists($place['map_url'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i>Plan du lieu</h5>
                </div>
                <div class="card-body">
                    <div class="position-relative">
                        <!-- Zone du plan avec pions (identique à view_scene.php) -->
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
                                        $imageUrl = 'images/monstres/' . $monster['monster_id'] . '.jpg';
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Mes personnages présents -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Mes personnages présents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($player_characters)): ?>
                        <p class="text-muted">Aucun de vos personnages n'est présent dans ce lieu.</p>
                        <a href="view_campaign.php?id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                        </a>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($player_characters as $character): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($character['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($character['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($character['name']); ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($character['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($character['class_name'] ?? 'Classe inconnue'); ?> - Niveau <?php echo (int)$character['level']; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <a href="view_character.php?id=<?php echo (int)$character['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Voir la fiche
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Autres joueurs présents -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Autres joueurs présents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($other_players)): ?>
                        <p class="text-muted">Aucun autre joueur présent dans ce lieu.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($other_players as $player): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($player['profile_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?></h6>
                                            <small class="text-muted">
                                                <?php if ($player['character_name']): ?>
                                                    <?php echo htmlspecialchars($player['class_name'] ?? 'Classe inconnue'); ?> - Niveau <?php echo (int)$player['level']; ?>
                                                <?php else: ?>
                                                    Joueur
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- PNJ présents (seulement visibles) -->
    <?php if (!empty($placeNpcs)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>PNJ présents</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($placeNpcs as $npc): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Logique d'affichage selon l'identification
                                            $photo_to_show = null;
                                            $displayName = 'PNJ inconnu';
                                            
                                            if ($npc['is_identified']) {
                                                // PNJ identifié : afficher nom et photo
                                                $displayName = $npc['name'];
                                                if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                                    $photo_to_show = $npc['character_profile_photo'];
                                                } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                                    $photo_to_show = $npc['profile_photo'];
                                                }
                                            }
                                            ?>
                                            <?php if (!empty($photo_to_show)): ?>
                                                <img src="<?php echo htmlspecialchars($photo_to_show); ?>" alt="Photo de <?php echo htmlspecialchars($displayName); ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-success rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user-tie text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($displayName); ?></h6>
                                                <?php if ($npc['is_identified'] && !empty($npc['description'])): ?>
                                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($npc['description'], 0, 100)); ?><?php echo strlen($npc['description']) > 100 ? '...' : ''; ?></p>
                                                <?php else: ?>
                                                    <p class="mb-0 text-muted small">PNJ non identifié</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Monstres présents (seulement visibles) -->
    <?php if (!empty($placeMonsters)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dragon me-2"></i>Monstres présents</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($placeMonsters as $monster): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Logique d'affichage selon l'identification
                                            $displayName = 'Monstre inconnu';
                                            $showDetails = false;
                                            
                                            if ($monster['is_identified']) {
                                                // Monstre identifié : afficher nom et détails
                                                $displayName = $monster['name'];
                                                $showDetails = true;
                                            }
                                            ?>
                                            <div class="bg-danger rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-dragon text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($displayName); ?></h6>
                                                <?php if ($showDetails): ?>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($monster['type']); ?> - <?php echo htmlspecialchars($monster['size']); ?> - CR <?php echo $monster['challenge_rating']; ?>
                                                    </small>
                                                    <?php if (!empty($monster['description'])): ?>
                                                        <p class="mb-0 text-muted small mt-1"><?php echo htmlspecialchars(substr($monster['description'], 0, 100)); ?><?php echo strlen($monster['description']) > 100 ? '...' : ''; ?></p>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <small class="text-muted">Monstre non identifié</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

// Vérifier le changement de lieu toutes les 3 secondes
let locationCheckInterval = setInterval(checkPlayerLocationChange, 3000);

// Arrêter la mise à jour automatique quand la page se ferme
window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
    if (locationCheckInterval) {
        clearInterval(locationCheckInterval);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
