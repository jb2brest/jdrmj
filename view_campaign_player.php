<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Campagne - Vue Joueur";
$current_page = "view_campaign_player";

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: campaigns.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$campaign_id = (int)$_GET['id'];

// Vérifier que l'utilisateur est membre de la campagne
$stmt = $pdo->prepare("SELECT cm.role FROM campaign_members cm WHERE cm.campaign_id = ? AND cm.user_id = ?");
$stmt->execute([$campaign_id, $user_id]);
$membership = $stmt->fetch();

if (!$membership) {
    header('Location: campaigns.php');
    exit();
}

// Charger la campagne
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

// Récupérer les lieux de la campagne
$stmt = $pdo->prepare("SELECT * FROM places WHERE campaign_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$campaign_id]);
$places = $stmt->fetchAll();

// Récupérer les sessions de la campagne
$stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE campaign_id = ? ORDER BY session_date DESC");
$stmt->execute([$campaign_id]);
$sessions = $stmt->fetchAll();

// Récupérer les membres de la campagne
$stmt = $pdo->prepare("SELECT u.id, u.username, cm.role, cm.joined_at FROM campaign_members cm JOIN users u ON cm.user_id = u.id WHERE cm.campaign_id = ? ORDER BY cm.joined_at ASC");
$stmt->execute([$campaign_id]);
$members = $stmt->fetchAll();

// Récupérer les personnages du joueur
$stmt = $pdo->prepare("SELECT id, name, class_id, level FROM characters WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$player_characters = $stmt->fetchAll();

include 'includes/layout.php';
?>

<div class="container mt-4">
    <!-- En-tête de la campagne -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($campaign['title']); ?></h1>
                    <span class="badge bg-<?php echo $campaign['is_public'] ? 'success' : 'secondary'; ?>"><?php echo $campaign['is_public'] ? 'Publique' : 'Privée'; ?></span>
                    <span class="badge bg-info ms-2">Vue Joueur</span>
                </div>
                <div>
                    <a href="view_campaign.php?id=<?php echo (int)$campaign_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>Vue complète
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Description de la campagne -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Description</h5>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Mes personnages -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Mes personnages</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($player_characters)): ?>
                        <p class="text-muted">Aucun personnage créé pour cette campagne.</p>
                        <a href="create_character.php?campaign_id=<?php echo (int)$campaign_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer un personnage
                        </a>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($player_characters as $character): ?>
                                <a href="view_character.php?id=<?php echo (int)$character['id']; ?>&dm_campaign_id=<?php echo (int)$campaign_id; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($character['name']); ?></h6>
                                            <small class="text-muted">Niveau <?php echo (int)$character['level']; ?></small>
                                        </div>
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="create_character.php?campaign_id=<?php echo (int)$campaign_id; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Créer un autre personnage
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Membres de la campagne -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Membres</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($members)): ?>
                        <p class="text-muted">Aucun membre pour l'instant.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($members as $member): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($member['username']); ?>
                                        <span class="badge bg-<?php echo $member['role'] === 'dm' ? 'danger' : 'primary'; ?> ms-2"><?php echo $member['role'] === 'dm' ? 'MJ' : 'Joueur'; ?></span>
                                    </div>
                                    <small class="text-muted">Depuis <?php echo date('d/m/Y', strtotime($member['joined_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lieux de la campagne -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-photo-video me-2"></i>Lieux de la campagne</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($places)): ?>
                        <p class="text-muted">Aucun lieu créé pour l'instant.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($places as $place): ?>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="view_scene.php?id=<?php echo (int)$place['id']; ?>" class="text-decoration-none">
                                                    <i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?>
                                                </a>
                                            </h6>
                                            <?php if (!empty($place['description'])): ?>
                                                <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($place['description'], 0, 100)); ?><?php echo strlen($place['description']) > 100 ? '...' : ''; ?></p>
                                            <?php endif; ?>
                                            <a href="view_scene.php?id=<?php echo (int)$place['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>Explorer
                                            </a>
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

    <!-- Sessions de jeu -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Sessions de jeu</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sessions)): ?>
                        <p class="text-muted">Aucune session programmée.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($sessions as $session): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($session['title']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($session['description']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y à H:i', strtotime($session['session_date'])); ?>
                                                <?php if (!empty($session['location'])): ?>
                                                    <i class="fas fa-map-marker-alt ms-3 me-1"></i><?php echo htmlspecialchars($session['location']); ?>
                                                <?php endif; ?>
                                                <?php if ($session['is_online']): ?>
                                                    <span class="badge bg-info ms-2">En ligne</span>
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
</div>
