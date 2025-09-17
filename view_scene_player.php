<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Lieu - Vue Joueur";
$current_page = "view_scene_player";

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: campaigns.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$place_id = (int)$_GET['id'];

// Charger le lieu
$stmt = $pdo->prepare("SELECT p.*, c.title as campaign_title, c.dm_id FROM places p JOIN campaigns c ON p.campaign_id = c.id WHERE p.id = ?");
$stmt->execute([$place_id]);
$place = $stmt->fetch();

if (!$place) {
    header('Location: campaigns.php');
    exit();
}

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

// Récupérer les PNJ présents dans le lieu (comme dans view_scene.php)
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo
    FROM place_npcs sn 
    LEFT JOIN characters c ON sn.npc_character_id = c.id
    WHERE sn.place_id = ? AND sn.monster_id IS NULL
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$placeNpcs = $stmt->fetchAll();

// Récupérer les monstres présents dans le lieu (comme dans view_scene.php)
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, 
           m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL
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
                                
                                <!-- Pions des PNJ -->
                                <?php foreach ($placeNpcs as $npc): ?>
                                    <?php 
                                    $tokenKey = 'npc_' . $npc['id'];
                                    $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                    $imageUrl = !empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : (!empty($npc['profile_photo']) ? $npc['profile_photo'] : 'images/default_npc.png');
                                    ?>
                                    <div class="token" 
                                         data-token-type="npc" 
                                         data-entity-id="<?php echo $npc['id']; ?>"
                                         data-position-x="<?php echo $position['x']; ?>"
                                         data-position-y="<?php echo $position['y']; ?>"
                                         data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                         style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #28a745; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                         title="<?php echo htmlspecialchars($npc['name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Pions des monstres -->
                                <?php foreach ($placeMonsters as $monster): ?>
                                    <?php 
                                    $tokenKey = 'monster_' . $monster['id'];
                                    $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                    $imageUrl = 'images/monstres/' . $monster['monster_id'] . '.jpg';
                                    ?>
                                    <div class="token" 
                                         data-token-type="monster" 
                                         data-entity-id="<?php echo $monster['id']; ?>"
                                         data-position-x="<?php echo $position['x']; ?>"
                                         data-position-y="<?php echo $position['y']; ?>"
                                         data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                         style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #dc3545; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                         title="<?php echo htmlspecialchars($monster['name']); ?>">
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

    <!-- PNJ présents -->
    <?php if (!empty($place_npcs)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>PNJ présents</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($place_npcs as $npc): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($npc['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($npc['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($npc['name']); ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-success rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user-tie text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($npc['name']); ?></h6>
                                                <?php if (!empty($npc['description'])): ?>
                                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($npc['description'], 0, 100)); ?><?php echo strlen($npc['description']) > 100 ? '...' : ''; ?></p>
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

    <!-- Monstres présents -->
    <?php if (!empty($place_monsters)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dragon me-2"></i>Monstres présents</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($place_monsters as $monster): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-dragon text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($monster['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($monster['type']); ?> - <?php echo htmlspecialchars($monster['size']); ?> - CR <?php echo $monster['challenge_rating']; ?>
                                                </small>
                                                <?php if (!empty($monster['description'])): ?>
                                                    <p class="mb-0 text-muted small mt-1"><?php echo htmlspecialchars(substr($monster['description'], 0, 100)); ?><?php echo strlen($monster['description']) > 100 ? '...' : ''; ?></p>
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

<?php if (!empty($place['map_url']) && file_exists($place['map_url'])): ?>
<script>
function initializeTokenSystem() {
    const mapImage = document.getElementById('mapImage');
    const tokens = document.querySelectorAll('.token');
    
    if (!mapImage || tokens.length === 0) return;

    // Initialiser les positions des pions (identique à view_scene.php)
    console.log('Initialisation du système de pions (vue joueur)...');
    console.log('Nombre de pions trouvés:', tokens.length);
    
    tokens.forEach(token => {
        const isOnMap = token.dataset.isOnMap === 'true';
        console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId}: isOnMap=${isOnMap}`);
        
        if (isOnMap) {
            const x = parseInt(token.dataset.positionX);
            const y = parseInt(token.dataset.positionY);
            console.log(`Initialisation pion: ${token.dataset.tokenType}_${token.dataset.entityId} à ${x}%, ${y}%`);
            positionTokenOnMap(token, x, y);
        } else {
            console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId} reste dans la sidebar`);
        }
    });
}

function positionTokenOnMap(token, x, y) {
    console.log(`Positionnement du pion ${token.dataset.tokenType}_${token.dataset.entityId} à ${x}%, ${y}%`);
    
    // Retirer le pion de son conteneur actuel
    token.remove();
    
    // Ajouter le pion au conteneur du plan
    const mapContainer = document.getElementById('mapContainer');
    if (!mapContainer) {
        console.error('Conteneur du plan non trouvé');
        return;
    }
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
    
    console.log(`Pion positionné avec succès à ${x}%, ${y}%`);
}

// Initialiser le système de pions après que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    initializeTokenSystem();
    
    // Démarrer la mise à jour automatique des positions
    startAutoUpdate();
});

// Système de mise à jour automatique des positions
let autoUpdateInterval;
let lastUpdateTime = null;

function startAutoUpdate() {
    console.log('🔄 Démarrage de la mise à jour automatique des positions...');
    
    // Mettre à jour toutes les 2 secondes
    autoUpdateInterval = setInterval(updateTokenPositions, 2000);
}

function stopAutoUpdate() {
    if (autoUpdateInterval) {
        clearInterval(autoUpdateInterval);
        autoUpdateInterval = null;
        console.log('⏹️ Mise à jour automatique arrêtée');
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
        if (result.success && result.positions) {
            console.log('🔄 Mise à jour des positions reçue:', result.positions);
            applyPositionUpdates(result.positions);
            lastUpdateTime = result.timestamp;
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la mise à jour des positions:', error);
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
                console.log(`🔄 Mise à jour pion ${tokenKey}: ${currentX},${currentY} -> ${newPosition.x},${newPosition.y} (on_map: ${newPosition.is_on_map})`);
                
                // Mettre à jour les attributs
                token.dataset.positionX = newPosition.x;
                token.dataset.positionY = newPosition.y;
                token.dataset.isOnMap = newPosition.is_on_map ? 'true' : 'false';
                
                // Appliquer la nouvelle position
                if (newPosition.is_on_map) {
                    positionTokenOnMap(token, newPosition.x, newPosition.y);
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
    if (!tokenSidebar) {
        console.error('Sidebar des pions non trouvée');
        return;
    }
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
    
    console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId} remis dans la sidebar`);
}

// Arrêter la mise à jour automatique quand la page se ferme
window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
