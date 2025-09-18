<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Les joueurs peuvent voir les campagnes publiques, les DM/Admin peuvent voir toutes les campagnes
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Création d'un code d'invitation simple
function generateInviteCode($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

// Traitements POST: créer, supprimer, basculer visibilité (DM et Admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isDMOrAdmin()) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $game_system = sanitizeInput($_POST['game_system'] ?? 'D&D 5e');
        $invite_code = generateInviteCode(12);

        if (strlen($title) < 3) {
            $error_message = "Le titre doit contenir au moins 3 caractères.";
        } else {
            $pdo->beginTransaction();
            try {
                // Créer la campagne
                $stmt = $pdo->prepare("INSERT INTO campaigns (dm_id, title, description, game_system, is_public, invite_code) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $description, $game_system, $is_public, $invite_code]);
                $campaign_id = $pdo->lastInsertId();
                
                // Ajouter le DM comme membre de sa propre campagne
                $stmt = $pdo->prepare("INSERT INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'dm')");
                $stmt->execute([$campaign_id, $user_id]);
                
                $pdo->commit();
                $success_message = "Campagne créée avec succès.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de la création de la campagne.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        // Les admins peuvent supprimer toutes les campagnes, les MJ seulement les leurs
        if (isAdmin()) {
            $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND dm_id = ?");
            $stmt->execute([$campaign_id, $user_id]);
        }
        $success_message = "Campagne supprimée.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle_visibility' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        // Les admins peuvent modifier la visibilité de toutes les campagnes, les MJ seulement les leurs
        if (isAdmin()) {
            $stmt = $pdo->prepare("UPDATE campaigns SET is_public = NOT is_public WHERE id = ?");
            $stmt->execute([$campaign_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE campaigns SET is_public = NOT is_public WHERE id = ? AND dm_id = ?");
            $stmt->execute([$campaign_id, $user_id]);
        }
        $success_message = "Visibilité mise à jour.";
    }
}

// Récupérer les campagnes selon le rôle
if (isAdmin()) {
    // Les admins voient toutes les campagnes
    $stmt = $pdo->prepare("SELECT c.*, u.username as dm_name FROM campaigns c LEFT JOIN users u ON c.dm_id = u.id ORDER BY c.created_at DESC");
    $stmt->execute();
    $page_title = 'Toutes les Campagnes';
} elseif (isDM()) {
    // Les DM voient leurs campagnes + les campagnes publiques
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as dm_name 
        FROM campaigns c 
        LEFT JOIN users u ON c.dm_id = u.id 
        WHERE c.dm_id = ? OR c.is_public = 1 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $page_title = 'Mes Campagnes';
} else {
    // Les joueurs voient les campagnes publiques ET les campagnes où ils sont membres
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as dm_name 
        FROM campaigns c 
        LEFT JOIN users u ON c.dm_id = u.id 
        WHERE c.is_public = 1 
        OR EXISTS (
            SELECT 1 FROM campaign_members cm 
            WHERE cm.campaign_id = c.id AND cm.user_id = ?
        )
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $page_title = 'Mes Campagnes';
}
$campaigns = $stmt->fetchAll();
$current_page = "campaigns";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i><?php echo isAdmin() ? 'Toutes les Campagnes' : 'Mes Campagnes'; ?></h1>
        </div>

        <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (isDMOrAdmin()): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>Créer une nouvelle campagne
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Système de jeu</label>
                            <select name="game_system" class="form-select">
                                <option>D&D 5e</option>
                                <option>Pathfinder</option>
                                <option>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" id="is_public" checked>
                                <label class="form-check-label" for="is_public">Publique</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Synopsis, ton, thèmes..."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Créer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($campaigns)): ?>
            <p class="text-muted">Aucune campagne pour le moment.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($campaigns as $c): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($c['title']); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $c['is_public'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $c['is_public'] ? 'Publique' : 'Privée'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-2">
                                    Système : <?php echo htmlspecialchars($c['game_system']); ?>
                                    <?php if (isAdmin() && !empty($c['dm_name'])): ?>
                                        <br>MJ : <?php echo htmlspecialchars($c['dm_name']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($c['description'])): ?>
                                    <p class="small mb-3"><?php echo nl2br(htmlspecialchars($c['description'])); ?></p>
                                <?php endif; ?>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Créée le <?php echo date('d/m/Y', strtotime($c['created_at'])); ?></small>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary" href="view_campaign.php?id=<?php echo $c['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isDMOrAdmin()): ?>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette campagne ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="toggle_visibility">
                                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                <button class="btn btn-sm btn-outline-secondary" title="Basculer visibilité">
                                                    <i class="fas fa-eye-slash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isDMOrAdmin()): ?>
                                    <div class="mt-3">
                                        <small class="text-muted">Code d'invitation :</small>
                                        <code><?php echo htmlspecialchars($c['invite_code']); ?></code>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



















