<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireDM();

if (!isset($_GET['id'])) {
    header('Location: campaigns.php');
    exit();
}

$dm_id = $_SESSION['user_id'];
$campaign_id = (int)$_GET['id'];

// Charger la campagne
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND dm_id = ?");
$stmt->execute([$campaign_id, $dm_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

// Traitements POST: ajouter membre par invite, créer session rapide
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_member') {
        $username_or_email = sanitizeInput($_POST['username_or_email'] ?? '');
        if ($username_or_email !== '') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch();
            if ($user) {
                $stmt = $pdo->prepare("REPLACE INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'player')");
                $stmt->execute([$campaign_id, $user['id']]);
                $success_message = "Membre ajouté à la campagne.";
            } else {
                $error_message = "Utilisateur introuvable.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'create_session') {
        $title = sanitizeInput($_POST['title'] ?? 'Session');
        $session_date = sanitizeInput($_POST['session_date'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $is_online = isset($_POST['is_online']) ? 1 : 0;
        $meeting_link = sanitizeInput($_POST['meeting_link'] ?? '');
        $max_players = (int)($_POST['max_players'] ?? 6);

        $stmt = $pdo->prepare("INSERT INTO game_sessions (dm_id, title, session_date, location, is_online, meeting_link, max_players, campaign_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dm_id, $title, $session_date, $location, $is_online, $meeting_link, $max_players, $campaign_id]);
        $success_message = "Session créée.";
    }
}

// Récupérer membres
$stmt = $pdo->prepare("SELECT u.id, u.username, cm.role, cm.joined_at FROM campaign_members cm JOIN users u ON cm.user_id = u.id WHERE cm.campaign_id = ? ORDER BY cm.joined_at ASC");
$stmt->execute([$campaign_id]);
$members = $stmt->fetchAll();

// Récupérer sessions
$stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE campaign_id = ? ORDER BY session_date DESC, created_at DESC");
$stmt->execute([$campaign_id]);
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campaign['title']); ?> - Campagne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="campaigns.php">Mes Campagnes</a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($campaign['title']); ?></h1>
            <span class="badge bg-<?php echo $campaign['is_public'] ? 'success' : 'secondary'; ?>"><?php echo $campaign['is_public'] ? 'Publique' : 'Privée'; ?></span>
        </div>
        <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (!empty($campaign['description'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-users me-2"></i>Membres</div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <p class="text-muted">Aucun membre pour l'instant.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($members as $m): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($m['username']); ?>
                                            <span class="badge bg-<?php echo $m['role'] === 'dm' ? 'danger' : 'primary'; ?> ms-2"><?php echo $m['role'] === 'dm' ? 'MJ' : 'Joueur'; ?></span>
                                        </span>
                                        <small class="text-muted">Depuis <?php echo date('d/m/Y', strtotime($m['joined_at'])); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="add_member">
                            <div class="input-group">
                                <input type="text" class="form-control" name="username_or_email" placeholder="Nom d'utilisateur ou email">
                                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-user-plus me-2"></i>Ajouter</button>
                            </div>
                            <div class="form-text">Ou partagez le code d'invitation : <code><?php echo htmlspecialchars($campaign['invite_code']); ?></code></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>Sessions</div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="create_session">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Titre</label>
                                    <input type="text" class="form-control" name="title" placeholder="Session 1" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date/Heure</label>
                                    <input type="datetime-local" class="form-control" name="session_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Places</label>
                                    <input type="number" class="form-control" name="max_players" value="6" min="1" max="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lieu</label>
                                    <input type="text" class="form-control" name="location" placeholder="En ligne / Adresse">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_online" id="is_online">
                                        <label class="form-check-label" for="is_online">En ligne</label>
                                    </div>
                                    <input type="text" class="form-control mt-2" name="meeting_link" placeholder="Lien (si en ligne)">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary"><i class="fas fa-plus me-2"></i>Créer une session</button>
                                </div>
                            </div>
                        </form>

                        <?php if (empty($sessions)): ?>
                            <p class="text-muted">Aucune session planifiée.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($sessions as $s): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['title']); ?></strong>
                                            <div class="small text-muted">
                                                <?php if (!empty($s['session_date'])) echo date('d/m/Y H:i', strtotime($s['session_date'])) . ' · '; ?>
                                                <?php echo $s['is_online'] ? 'En ligne' : htmlspecialchars($s['location']); ?>
                                                · Places: <?php echo $s['max_players']; ?>
                                            </div>
                                        </div>
                                        <a href="view_session.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
