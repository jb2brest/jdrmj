<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Équipement de la Scène";
$current_page = "view_scene_equipment";


requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$place_id = (int)$_GET['id'];

// Charger la lieu et vérifier les permissions
$stmt = $pdo->prepare("SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, gs.campaign_id, u.username AS dm_username FROM places s JOIN game_sessions gs ON s.session_id = gs.id JOIN users u ON gs.dm_id = u.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

if (!$scene) {
    header('Location: index.php');
    exit();
}

$dm_id = (int)$scene['dm_id'];
$isOwnerDM = (isDM() && $_SESSION['user_id'] === $dm_id);

if (!$isOwnerDM) {
    header('Location: index.php');
    exit();
}

// Récupérer les joueurs présents dans cette lieu avec leur équipement
$stmt = $pdo->prepare("
    SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo,
           COUNT(ce.id) as equipment_count
    FROM place_players sp 
    JOIN users u ON sp.player_id = u.id 
    LEFT JOIN characters ch ON sp.character_id = ch.id 
    LEFT JOIN character_equipment ce ON ch.id = ce.character_id
    WHERE sp.place_id = ? 
    GROUP BY sp.player_id, u.username, ch.id, ch.name, ch.profile_photo
    ORDER BY u.username ASC
");
$stmt->execute([$place_id]);
$scenePlayers = $stmt->fetchAll();

// Récupérer les PNJ de cette lieu avec leur équipement
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo,
           COUNT(ne.id) as equipment_count
    FROM place_npcs sn 
    LEFT JOIN characters c ON sn.npc_character_id = c.id 
    LEFT JOIN npc_equipment ne ON sn.id = ne.npc_id AND sn.place_id = ne.scene_id
    WHERE sn.place_id = ? AND sn.monster_id IS NULL 
    GROUP BY sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$sceneNpcs = $stmt->fetchAll();

// Récupérer les monstres de cette lieu avec leur équipement
$stmt = $pdo->prepare("
    SELECT sn.id, sn.name, sn.description, sn.monster_id, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class,
           COUNT(me.id) as equipment_count
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    LEFT JOIN monster_equipment me ON sn.id = me.monster_id AND sn.place_id = me.place_id
    WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL 
    GROUP BY sn.id, sn.name, sn.description, sn.monster_id, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class
    ORDER BY sn.name ASC
");
$stmt->execute([$place_id]);
$sceneMonsters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Équipement de la Lieu: <?php echo htmlspecialchars($scene['title']); ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-gem me-2"></i>Équipement de la Lieu</h1>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($scene['title']); ?> - <?php echo htmlspecialchars($scene['session_title']); ?></p>
                    </div>
                    <div class="text-end">
                        <a href="view_scene.php?id=<?php echo (int)$place_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la Lieu
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Joueurs -->
        <?php if (!empty($scenePlayers)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Joueurs</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($scenePlayers as $player): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($player['username']); ?>
                                            </h6>
                                            <span class="badge bg-primary"><?php echo (int)$player['equipment_count']; ?> objet(s)</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($player['character_name']): ?>
                                                <p class="card-text">
                                                    <strong>Personnage:</strong> <?php echo htmlspecialchars($player['character_name']); ?>
                                                </p>
                                                <div class="d-grid gap-2">
                                                    <a href="view_character_equipment.php?id=<?php echo (int)$player['character_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-gem me-1"></i>Voir l'Équipement
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <p class="card-text text-muted">Aucun personnage créé</p>
                                            <?php endif; ?>
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

        <!-- PNJ -->
        <?php if (!empty($sceneNpcs)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>PNJ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($sceneNpcs as $npc): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($npc['name']); ?>
                                            </h6>
                                            <span class="badge bg-info"><?php echo (int)$npc['equipment_count']; ?> objet(s)</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($npc['description']): ?>
                                                <p class="card-text">
                                                    <strong>Description:</strong> <?php echo htmlspecialchars($npc['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-grid gap-2">
                                                <a href="view_npc_equipment.php?id=<?php echo (int)$npc['id']; ?>&place_id=<?php echo (int)$place_id; ?>" 
                                                   class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-gem me-1"></i>Voir l'Équipement
                                                </a>
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

        <!-- Monstres -->
        <?php if (!empty($sceneMonsters)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dragon me-2"></i>Monstres</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($sceneMonsters as $monster): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-dragon me-2"></i><?php echo htmlspecialchars($monster['name']); ?>
                                            </h6>
                                            <span class="badge bg-danger"><?php echo (int)$monster['equipment_count']; ?> objet(s)</span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Type:</strong> <?php echo htmlspecialchars($monster['type']); ?><br>
                                                <strong>Taille:</strong> <?php echo htmlspecialchars($monster['size']); ?><br>
                                                <strong>CR:</strong> <?php echo htmlspecialchars($monster['challenge_rating']); ?><br>
                                                <strong>CA:</strong> <?php echo htmlspecialchars($monster['armor_class']); ?> | 
                                                <strong>PV:</strong> <?php echo htmlspecialchars($monster['hit_points']); ?>
                                            </p>
                                            <div class="d-grid gap-2">
                                                <a href="view_monster_equipment.php?id=<?php echo (int)$monster['id']; ?>&place_id=<?php echo (int)$place_id; ?>" 
                                                   class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-gem me-1"></i>Voir l'Équipement
                                                </a>
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

        <!-- Message si aucun participant -->
        <?php if (empty($scenePlayers) && empty($sceneNpcs) && empty($sceneMonsters)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-gem fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun participant dans cette lieu</h5>
                        <p class="text-muted">Ajoutez des joueurs, PNJ ou monstres à la lieu pour voir leur équipement.</p>
                        <a href="view_scene.php?id=<?php echo (int)$place_id; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la Lieu
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


