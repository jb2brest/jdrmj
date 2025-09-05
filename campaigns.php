<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireDM();

$dm_id = $_SESSION['user_id'];

// Création d'un code d'invitation simple
function generateInviteCode($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

// Traitements POST: créer, supprimer, basculer visibilité
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $game_system = sanitizeInput($_POST['game_system'] ?? 'D&D 5e');
        $invite_code = generateInviteCode(12);

        if (strlen($title) < 3) {
            $error_message = "Le titre doit contenir au moins 3 caractères.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO campaigns (dm_id, title, description, game_system, is_public, invite_code) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dm_id, $title, $description, $game_system, $is_public, $invite_code]);
            $success_message = "Campagne créée avec succès.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND dm_id = ?");
        $stmt->execute([$campaign_id, $dm_id]);
        $success_message = "Campagne supprimée.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle_visibility' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        $stmt = $pdo->prepare("UPDATE campaigns SET is_public = NOT is_public WHERE id = ? AND dm_id = ?");
        $stmt->execute([$campaign_id, $dm_id]);
        $success_message = "Visibilité mise à jour.";
    }
}

// Récupérer les campagnes du MJ
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE dm_id = ? ORDER BY created_at DESC");
$stmt->execute([$dm_id]);
$campaigns = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Campagnes - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="characters.php">Mes Personnages</a></li>
                    <li class="nav-item"><a class="nav-link active" href="campaigns.php">Mes Campagnes</a></li>
                    <li class="nav-item"><a class="nav-link" href="create_character.php">Créer un Personnage</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i>Mes Campagnes</h1>
        </div>

        <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>

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
                                <p class="text-muted mb-2">Système : <?php echo htmlspecialchars($c['game_system']); ?></p>
                                <?php if (!empty($c['description'])): ?>
                                    <p class="small mb-3"><?php echo nl2br(htmlspecialchars($c['description'])); ?></p>
                                <?php endif; ?>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Créée le <?php echo date('d/m/Y', strtotime($c['created_at'])); ?></small>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary" href="view_campaign.php?id=<?php echo $c['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Code d'invitation :</small>
                                    <code><?php echo htmlspecialchars($c['invite_code']); ?></code>
                                </div>
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









