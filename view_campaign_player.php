<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

// Récupérer l'ID de la campagne depuis l'URL
$campaign_id = (int)($_GET['id'] ?? 0);

if ($campaign_id <= 0) {
    header('Location: characters.php');
    exit();
}

// Vérifier que l'utilisateur est membre de cette campagne
$stmt = $pdo->prepare("
    SELECT c.*, u.username as dm_username 
    FROM campaigns c 
    JOIN users u ON c.dm_id = u.id 
    WHERE c.id = ? AND EXISTS (
        SELECT 1 FROM campaign_applications ca 
        WHERE ca.campaign_id = c.id 
        AND ca.player_id = ? 
        AND ca.status = 'approved'
    )
");
$stmt->execute([$campaign_id, $_SESSION['user_id']]);
$campaign = $stmt->fetch();

if (!$campaign) {
    header('Location: characters.php');
    exit();
}

// Récupérer le personnage du joueur dans cette campagne
$stmt = $pdo->prepare("
    SELECT ch.*, ca.character_id 
    FROM campaign_applications ca 
    JOIN characters ch ON ca.character_id = ch.id 
    WHERE ca.campaign_id = ? 
    AND ca.player_id = ? 
    AND ca.status = 'approved'
");
$stmt->execute([$campaign_id, $_SESSION['user_id']]);
$playerCharacter = $stmt->fetch();

// Récupérer les membres de la campagne
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar, ch.name as character_name, ch.profile_photo, ch.level, ch.race_id, ch.class_id, r.name as race_name, cl.name as class_name
    FROM campaign_applications ca 
    JOIN users u ON ca.player_id = u.id 
    LEFT JOIN characters ch ON ca.character_id = ch.id 
    LEFT JOIN races r ON ch.race_id = r.id 
    LEFT JOIN classes cl ON ch.class_id = cl.id 
    WHERE ca.campaign_id = ? 
    AND ca.status = 'approved'
    ORDER BY u.username ASC
");
$stmt->execute([$campaign_id]);
$members = $stmt->fetchAll();

// Récupérer les lieux de la campagne
$stmt = $pdo->prepare("SELECT * FROM places WHERE campaign_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$campaign_id]);
$places = $stmt->fetchAll();

// Déterminer dans quel lieu se trouve le personnage du joueur
$playerPlace = null;
$playerPlaceId = null;

if ($playerCharacter) {
    // Chercher dans quelle lieu se trouve le personnage
    $stmt = $pdo->prepare("
        SELECT p.*, pp.place_id 
        FROM place_players pp 
        JOIN places p ON pp.place_id = p.id 
        WHERE pp.character_id = ? AND p.campaign_id = ?
    ");
    $stmt->execute([$playerCharacter['id'], $campaign_id]);
    $playerPlace = $stmt->fetch();
    
    if ($playerPlace) {
        $playerPlaceId = $playerPlace['id'];
    }
}

// Si le personnage n'est dans aucun lieu, prendre le premier lieu disponible
if (!$playerPlace && !empty($places)) {
    $playerPlace = $places[0];
    $playerPlaceId = $playerPlace['id'];
}

// Récupérer les joueurs présents dans le lieu du joueur
$placePlayers = [];
$placeNpcs = [];
$placeMonsters = [];

if ($playerPlaceId) {
    // Récupérer les joueurs présents
    $stmt = $pdo->prepare("
        SELECT pp.player_id, pp.character_id, u.username, ch.name AS character_name, ch.profile_photo, ch.level, r.name as race_name, cl.name as class_name
        FROM place_players pp 
        JOIN users u ON pp.player_id = u.id 
        LEFT JOIN characters ch ON pp.character_id = ch.id 
        LEFT JOIN races r ON ch.race_id = r.id 
        LEFT JOIN classes cl ON ch.class_id = cl.id 
        WHERE pp.place_id = ? 
        ORDER BY u.username ASC
    ");
    $stmt->execute([$playerPlaceId]);
    $placePlayers = $stmt->fetchAll();
    
    // Récupérer les PNJ (seulement ceux visibles ET identifiés)
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, pn.description, pn.npc_character_id, pn.profile_photo, c.profile_photo AS character_profile_photo 
        FROM place_npcs pn 
        LEFT JOIN characters c ON pn.npc_character_id = c.id 
        WHERE pn.place_id = ? AND pn.monster_id IS NULL AND pn.is_visible = TRUE AND pn.is_identified = TRUE
        ORDER BY pn.name ASC
    ");
    $stmt->execute([$playerPlaceId]);
    $placeNpcs = $stmt->fetchAll();
    
    // Récupérer les monstres (seulement ceux visibles)
    $stmt = $pdo->prepare("
        SELECT pn.id, pn.name, pn.description, pn.monster_id, pn.quantity, pn.current_hit_points, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class 
        FROM place_npcs pn 
        JOIN dnd_monsters m ON pn.monster_id = m.id 
        WHERE pn.place_id = ? AND pn.monster_id IS NOT NULL AND pn.is_visible = TRUE
        ORDER BY pn.name ASC
    ");
    $stmt->execute([$playerPlaceId]);
    $placeMonsters = $stmt->fetchAll();
    
    // Récupérer les événements publics du journal
    $stmt = $pdo->prepare("SELECT * FROM campaign_journal WHERE campaign_id = ? AND is_public = TRUE ORDER BY created_at DESC");
    $stmt->execute([$campaign_id]);
    $journalEntries = $stmt->fetchAll();
    
    // Récupérer les positions des pions
    $stmt = $pdo->prepare("
        SELECT token_type, entity_id, position_x, position_y, is_on_map
        FROM place_tokens 
        WHERE place_id = ?
    ");
    $stmt->execute([$playerPlaceId]);
    $tokenPositions = [];
    while ($row = $stmt->fetch()) {
        $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
            'x' => (int)$row['position_x'],
            'y' => (int)$row['position_y'],
            'is_on_map' => (bool)$row['is_on_map']
        ];
    }
    
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campaign['title']); ?> - Campagne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .npc-card {
            border-left: 4px solid #17a2b8;
            transition: transform 0.2s ease;
        }
        .npc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .monster-card {
            border-left: 4px solid #dc3545;
            transition: transform 0.2s ease;
        }
        .monster-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .member-card {
            transition: transform 0.2s ease;
        }
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
    <style>
        .campaign-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .member-card {
            transition: transform 0.2s ease;
        }
        .member-card:hover {
            transform: translateY(-2px);
        }
        .place-info {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="characters.php">
                <i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="characters.php">
                    <i class="fas fa-users me-1"></i>Mes Personnages
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- En-tête de la campagne -->
    <div class="campaign-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-crown me-2"></i><?php echo htmlspecialchars($campaign['title']); ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user-tie me-1"></i>MJ: <?php echo htmlspecialchars($campaign['dm_username']); ?>
                        <span class="ms-3">
                            <i class="fas fa-dice me-1"></i><?php echo htmlspecialchars($campaign['game_system']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if ($playerCharacter): ?>
                        <div class="badge bg-light text-dark fs-6">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($playerCharacter['name']); ?>
                            <span class="ms-1">Niv. <?php echo $playerCharacter['level']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Colonne gauche : Participants -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Participants
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <p class="text-muted">Aucun participant.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($members as $member): ?>
                                    <div class="col-12">
                                        <div class="card member-card">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php if (!empty($member['profile_photo'])): ?>
                                                            <img src="<?php echo htmlspecialchars($member['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($member['character_name'] ?: $member['username']); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <?php echo htmlspecialchars($member['character_name'] ?: $member['username']); ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <?php if ($member['character_name']): ?>
                                                                <i class="fas fa-dragon me-1"></i><?php echo htmlspecialchars($member['race_name']); ?> 
                                                                <i class="fas fa-shield-alt me-1 ms-1"></i><?php echo htmlspecialchars($member['class_name']); ?>
                                                                <span class="ms-2">Niv. <?php echo $member['level']; ?></span>
                                                            <?php else: ?>
                                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($member['username']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section Personnages non joueurs -->
                <?php if (!empty($placeNpcs) || !empty($placeMonsters)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>Personnages non joueurs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($placeNpcs)): ?>
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-user-friends me-1"></i>PNJ
                            </h6>
                            <div class="row g-2">
                                <?php foreach ($placeNpcs as $npc): ?>
                                    <div class="col-12">
                                        <div class="card npc-card">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php 
                                                        $photo_to_show = !empty($npc['profile_photo']) ? $npc['profile_photo'] : (!empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : null);
                                                        ?>
                                                        <?php if (!empty($photo_to_show)): ?>
                                                            <img src="<?php echo htmlspecialchars($photo_to_show); ?>" alt="Photo de <?php echo htmlspecialchars($npc['name']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-info rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user-tie text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <?php echo htmlspecialchars($npc['name']); ?>
                                                            <?php if (!empty($npc['npc_character_id'])): ?>
                                                                <span class="badge bg-info ms-1">perso MJ</span>
                                                            <?php endif; ?>
                                                        </h6>
                                                        <?php if (!empty($npc['description'])): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($placeMonsters)): ?>
                            <?php if (!empty($placeNpcs)): ?>
                                <hr class="my-3">
                            <?php endif; ?>
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-dragon me-1"></i>Monstres
                            </h6>
                            <div class="row g-2">
                                <?php foreach ($placeMonsters as $monster): ?>
                                    <div class="col-12">
                                        <div class="card monster-card">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="bg-danger rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-dragon text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($monster['name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($monster['type']); ?> • 
                                                            <?php echo htmlspecialchars($monster['size']); ?> • 
                                                            CR <?php echo htmlspecialchars($monster['challenge_rating']); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            CA <?php echo htmlspecialchars($monster['armor_class']); ?> • 
                                                            PV <?php 
                                                                $current_hp = $monster['current_hit_points'] ?? $monster['hit_points'];
                                                                $max_hp = $monster['hit_points'];
                                                                $hp_percentage = ($current_hp / $max_hp) * 100;
                                                                $hp_color = $hp_percentage > 50 ? 'text-success' : ($hp_percentage > 25 ? 'text-warning' : 'text-danger');
                                                                echo "<span class='{$hp_color}'>{$current_hp}</span>/{$max_hp}";
                                                            ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Section Journal -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Journal de campagne</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($journalEntries)): ?>
                            <p class="text-muted">Aucun événement public dans le journal.</p>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($journalEntries as $entry): ?>
                                    <div class="col-12">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white p-2">
                                                <h6 class="mb-0 small">
                                                    <i class="fas fa-eye me-1"></i>
                                                    <?php echo htmlspecialchars($entry['title']); ?>
                                                </h6>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($entry['created_at'])); ?>
                                                </div>
                                                <div class="journal-content small">
                                                    <?php echo nl2br(htmlspecialchars($entry['content'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Colonne droite : Plan du lieu -->
            <div class="col-lg-8">
                <?php if ($playerPlace): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-map me-2"></i><?php echo htmlspecialchars($playerPlace['title']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($playerPlace['map_url'])): ?>
                                <div class="position-relative">
                                    <!-- Zone du plan avec pions -->
                                    <div id="mapContainer" class="position-relative" style="display: inline-block;">
                                        <img id="mapImage" src="<?php echo htmlspecialchars($playerPlace['map_url']); ?>" class="img-fluid rounded" alt="Plan du lieu" style="max-height: 500px; cursor: default;">
                                        
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
                                                     style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: default; border: 2px solid #007bff; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
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
                                                     style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: default; border: 2px solid #28a745; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                                     title="<?php echo htmlspecialchars($npc['name']); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <!-- Pions des monstres -->
                                            <?php foreach ($placeMonsters as $monster): ?>
                                                <?php 
                                                $tokenKey = 'monster_' . $monster['id'];
                                                $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                                $imageUrl = 'images/' . $monster['monster_id'] . '.jpg';
                                                ?>
                                                <div class="token" 
                                                     data-token-type="monster" 
                                                     data-entity-id="<?php echo $monster['id']; ?>"
                                                     data-position-x="<?php echo $position['x']; ?>"
                                                     data-position-y="<?php echo $position['y']; ?>"
                                                     data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                                     style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: default; border: 2px solid #dc3545; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                                     title="<?php echo htmlspecialchars($monster['name']); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-map fa-3x mb-3"></i>
                                    <p>Aucun plan disponible pour ce lieu.</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Informations sur le lieu -->
                            <?php if (!empty($playerPlace['notes'])): ?>
                                <div class="place-info p-3 mt-3 rounded">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informations du lieu</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($playerPlace['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun lieu disponible</h5>
                            <p class="text-muted">Votre personnage n'est actuellement dans aucun lieu de cette campagne.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($playerPlace['map_url'])): ?>
    <script>
    let tokenUpdateInterval;
    let lastUpdateTimestamp = 0;
    
    function initializeTokenSystem() {
        const mapImage = document.getElementById('mapImage');
        const tokens = document.querySelectorAll('.token');
        
        if (!mapImage || tokens.length === 0) return;

        // Initialiser les positions des pions (lecture seule)
        console.log('Initialisation du système de pions (lecture seule)...');
        console.log('Nombre de pions trouvés:', tokens.length);
        
        // Positionner les pions selon leurs données initiales
        tokens.forEach(token => {
            const isOnMap = token.dataset.isOnMap === 'true';
            const x = parseInt(token.dataset.positionX);
            const y = parseInt(token.dataset.positionY);
            
            // Marquer comme visible par défaut (sera mis à jour par l'API)
            token.dataset.isVisible = 'true';
            
            console.log(`Pion initial ${token.dataset.tokenType}_${token.dataset.entityId}:`);
            console.log(`  - isOnMap: ${isOnMap} (raw: "${token.dataset.isOnMap}")`);
            console.log(`  - positionX: ${x} (raw: "${token.dataset.positionX}")`);
            console.log(`  - positionY: ${y} (raw: "${token.dataset.positionY}")`);
            console.log(`  - current parent:`, token.parentElement?.id || 'none');
            
            if (isOnMap) {
                console.log(`  -> Positionnement sur la carte à ${x}%, ${y}%`);
                positionTokenOnMap(token, x, y);
            } else {
                console.log(`  -> Reste dans la sidebar`);
            }
        });
        
        // Vérifier la visibilité initiale
        updateTokenPositions();
        
        // Démarrer la mise à jour en temps réel
        startRealTimeUpdates();
    }

    function startRealTimeUpdates() {
        // Mettre à jour toutes les 2 secondes
        tokenUpdateInterval = setInterval(updateTokenPositions, 2000);
        console.log('Mise à jour en temps réel démarrée');
    }

    function stopRealTimeUpdates() {
        if (tokenUpdateInterval) {
            clearInterval(tokenUpdateInterval);
            tokenUpdateInterval = null;
            console.log('Mise à jour en temps réel arrêtée');
        }
    }

    function updateTokenPositions() {
        // Utiliser le lieu actuel du joueur depuis l'URL ou le lieu par défaut
        const urlParams = new URLSearchParams(window.location.search);
        const placeId = urlParams.get('place_id') || <?php echo $playerPlaceId; ?>;
        
        fetch(`get_token_positions.php?place_id=${placeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Positions mises à jour:', data.token_positions);
                    
                    // Vérifier si le joueur a changé de lieu
                    if (data.place_changed) {
                        console.log('Changement de lieu détecté:', data.player_current_place);
                        handlePlaceChange(data.player_current_place);
                    } else {
                        // Mise à jour normale des positions et visibilité
                        applyTokenPositions(data.token_positions);
                        handleVisibilityChanges(data.hidden_tokens);
                        updateNpcMonsterList(data.visible_npcs, data.visible_monsters);
                    }
                } else {
                    console.error('Erreur lors de la récupération des positions:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur de connexion:', error);
            });
    }

    function applyTokenPositions(tokenPositions) {
        const tokens = document.querySelectorAll('.token');
        
        tokens.forEach(token => {
            const tokenKey = `${token.dataset.tokenType}_${token.dataset.entityId}`;
            const newPosition = tokenPositions[tokenKey];
            
            if (newPosition) {
                const currentX = parseInt(token.dataset.positionX);
                const currentY = parseInt(token.dataset.positionY);
                const currentIsOnMap = token.dataset.isOnMap === 'true';
                const currentIsVisible = token.dataset.isVisible !== 'false';
                
                // Vérifier si la position ou la visibilité a changé
                if (currentX !== newPosition.x || currentY !== newPosition.y || currentIsOnMap !== newPosition.is_on_map || currentIsVisible !== newPosition.is_visible) {
                    console.log(`Mise à jour du pion ${tokenKey}: ${currentX},${currentY} -> ${newPosition.x},${newPosition.y} (on_map: ${newPosition.is_on_map}, visible: ${newPosition.is_visible})`);
                    
                    // Mettre à jour les données
                    token.dataset.positionX = newPosition.x;
                    token.dataset.positionY = newPosition.y;
                    token.dataset.isOnMap = newPosition.is_on_map ? 'true' : 'false';
                    token.dataset.isVisible = newPosition.is_visible ? 'true' : 'false';
                    
                    // Gérer la visibilité
                    if (newPosition.is_visible) {
                        token.style.display = 'inline-block';
                        
                        // Appliquer la nouvelle position si visible
                        if (newPosition.is_on_map) {
                            positionTokenOnMap(token, newPosition.x, newPosition.y);
                        } else {
                            resetTokenToSidebar(token);
                        }
                    } else {
                        // Masquer le pion
                        token.style.display = 'none';
                    }
                }
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
        
        // Positionner le pion (même logique que le MJ)
        token.style.position = 'absolute';
        token.style.left = x + '%';
        token.style.top = y + '%';
        token.style.transform = 'translate(-50%, -50%)';
        token.style.zIndex = '1000';
        token.style.margin = '0';
        token.style.pointerEvents = 'none'; // Lecture seule
        token.dataset.isOnMap = 'true';
        token.dataset.positionX = x;
        token.dataset.positionY = y;
        
        console.log(`Pion positionné avec succès à ${x}%, ${y}%`);
        console.log(`Styles appliqués: position=${token.style.position}, left=${token.style.left}, top=${token.style.top}, transform=${token.style.transform}`);
    }

    function resetTokenToSidebar(token) {
        console.log(`Remise du pion ${token.dataset.tokenType}_${token.dataset.entityId} dans la sidebar`);
        
        // Retirer le pion du conteneur du plan
        token.remove();
        
        // Remettre le pion dans la sidebar
        const sidebar = document.getElementById('tokenSidebar');
        if (sidebar) {
            sidebar.appendChild(token);
        }
        
        // Réinitialiser les styles
        token.style.position = 'static';
        token.style.left = 'auto';
        token.style.top = 'auto';
        token.style.transform = 'none';
        token.style.zIndex = 'auto';
        token.style.margin = '2px';
        token.style.pointerEvents = 'none';
        token.dataset.isOnMap = 'false';
        token.dataset.positionX = '0';
        token.dataset.positionY = '0';
    }

    function handleVisibilityChanges(hiddenTokens) {
        console.log('Gestion des changements de visibilité:', hiddenTokens);
        
        // Cacher les pions qui ne sont plus visibles
        Object.keys(hiddenTokens).forEach(tokenKey => {
            const token = document.querySelector(`[data-token-type="${tokenKey.split('_')[0]}"][data-entity-id="${tokenKey.split('_')[1]}"]`);
            if (token) {
                console.log(`Masquage du pion ${tokenKey}`);
                token.style.display = 'none';
                token.dataset.isVisible = 'false';
            }
        });
        
        // Afficher les pions qui sont maintenant visibles
        const allTokens = document.querySelectorAll('.token');
        allTokens.forEach(token => {
            const tokenKey = `${token.dataset.tokenType}_${token.dataset.entityId}`;
            const isCurrentlyHidden = hiddenTokens[tokenKey];
            
            if (!isCurrentlyHidden && token.dataset.isVisible === 'false') {
                console.log(`Affichage du pion ${tokenKey}`);
                token.style.display = 'inline-block';
                token.dataset.isVisible = 'true';
            }
        });
    }

    function updateNpcMonsterList(visibleNpcs, visibleMonsters) {
        console.log('Mise à jour de la liste des PNJ/monstres:', { visibleNpcs, visibleMonsters });
        
        // Trouver le conteneur de la section PNJ
        const npcSection = document.querySelector('.card .card-header h5');
        let npcSectionCard = null;
        
        // Chercher la carte qui contient le titre "Personnages non joueurs"
        document.querySelectorAll('.card').forEach(card => {
            const header = card.querySelector('.card-header h5');
            if (header && header.textContent.includes('Personnages non joueurs')) {
                npcSectionCard = card;
            }
        });
        
        if (!npcSectionCard) {
            console.log('Section PNJ non trouvée');
            return;
        }
        
        const cardBody = npcSectionCard.querySelector('.card-body');
        if (!cardBody) {
            console.log('Corps de la carte PNJ non trouvé');
            return;
        }
        
        // Vérifier s'il y a des PNJ ou monstres visibles
        const hasVisibleContent = (visibleNpcs && visibleNpcs.length > 0) || (visibleMonsters && visibleMonsters.length > 0);
        
        if (!hasVisibleContent) {
            // Masquer la section si aucun PNJ/monstre visible
            npcSectionCard.style.display = 'none';
            return;
        }
        
        // Afficher la section
        npcSectionCard.style.display = 'block';
        
        // Reconstruire le contenu
        let content = '';
        
        // Section PNJ
        if (visibleNpcs && visibleNpcs.length > 0) {
            content += `
                <h6 class="text-muted mb-3">
                    <i class="fas fa-user-friends me-1"></i>PNJ
                </h6>
                <div class="row g-2">
            `;
            
            visibleNpcs.forEach(npc => {
                const photoToShow = npc.profile_photo || npc.character_profile_photo;
                const photoHtml = photoToShow ? 
                    `<img src="${escapeHtml(photoToShow)}" alt="Photo de ${escapeHtml(npc.name)}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">` :
                    `<div class="bg-info rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-user-tie text-white"></i></div>`;
                
                const badgeHtml = npc.npc_character_id ? '<span class="badge bg-info ms-1">perso MJ</span>' : '';
                const descriptionHtml = npc.description ? `<small class="text-muted">${escapeHtml(npc.description)}</small>` : '';
                
                content += `
                    <div class="col-12">
                        <div class="card npc-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        ${photoHtml}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            ${escapeHtml(npc.name)}${badgeHtml}
                                        </h6>
                                        ${descriptionHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += '</div>';
        }
        
        // Section Monstres
        if (visibleMonsters && visibleMonsters.length > 0) {
            if (visibleNpcs && visibleNpcs.length > 0) {
                content += '<hr class="my-3">';
            }
            
            content += `
                <h6 class="text-muted mb-3">
                    <i class="fas fa-dragon me-1"></i>Monstres
                </h6>
                <div class="row g-2">
            `;
            
            visibleMonsters.forEach(monster => {
                const currentHp = monster.current_hit_points || monster.hit_points;
                const maxHp = monster.hit_points;
                const hpPercentage = (currentHp / maxHp) * 100;
                const hpColor = hpPercentage > 50 ? 'text-success' : (hpPercentage > 25 ? 'text-warning' : 'text-danger');
                
                content += `
                    <div class="col-12">
                        <div class="card monster-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-danger rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-dragon text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">${escapeHtml(monster.name)}</h6>
                                        <small class="text-muted">
                                            ${escapeHtml(monster.type)} • 
                                            ${escapeHtml(monster.size)} • 
                                            CR ${escapeHtml(monster.challenge_rating)}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            CA ${escapeHtml(monster.armor_class)} • 
                                            PV <span class="${hpColor}">${currentHp}</span>/${maxHp}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += '</div>';
        }
        
        // Mettre à jour le contenu
        cardBody.innerHTML = content;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function handlePlaceChange(newPlace) {
        console.log('Gestion du changement de lieu vers:', newPlace);
        
        if (!newPlace || !newPlace.place_id) {
            console.error('Informations de lieu invalides');
            return;
        }
        
        // Mettre à jour l'URL pour refléter le nouveau lieu
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('place_id', newPlace.place_id);
        window.history.replaceState({}, '', newUrl);
        
        // Afficher un message de changement de lieu
        showPlaceChangeNotification(newPlace.place_title);
        
        // Recharger la page pour afficher le nouveau lieu
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }

    function showPlaceChangeNotification(placeTitle) {
        // Créer une notification de changement de lieu
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-map-marker-alt me-2"></i>
            <strong>Changement de lieu !</strong><br>
            Vous êtes maintenant dans : <strong>${escapeHtml(placeTitle)}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer la notification après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // Initialiser le système de pions au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Attendre que l'image soit chargée avant d'initialiser les pions
        const mapImage = document.getElementById('mapImage');
        if (mapImage) {
            if (mapImage.complete) {
                // Image déjà chargée
                setTimeout(initializeTokenSystem, 100);
            } else {
                // Attendre le chargement de l'image
                mapImage.addEventListener('load', function() {
                    setTimeout(initializeTokenSystem, 100);
                });
            }
        } else {
            // Pas d'image, initialiser quand même
            setTimeout(initializeTokenSystem, 100);
        }
    });

    // Arrêter les mises à jour quand la page se ferme
    window.addEventListener('beforeunload', function() {
        stopRealTimeUpdates();
    });

    // Reprendre les mises à jour quand la page redevient visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopRealTimeUpdates();
        } else {
            startRealTimeUpdates();
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>
