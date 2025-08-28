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

    // Approuver une candidature
    if (isset($_POST['action']) && $_POST['action'] === 'approve_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Vérifier que la candidature correspond à cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ?");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $pdo->beginTransaction();
            try {
                // Mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'approved' WHERE id = ?");
                $stmt->execute([$application_id]);
                // Ajouter comme membre si pas déjà présent
                $stmt = $pdo->prepare("INSERT IGNORE INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'player')");
                $stmt->execute([$campaign_id, $player_id]);
                // Notification au joueur
                $title = 'Candidature acceptée';
                $message = 'Votre candidature à la campagne "' . $campaign['title'] . '" a été acceptée.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$player_id, $title, $message, $campaign_id]);
                $pdo->commit();
                $success_message = "Candidature approuvée et joueur ajouté à la campagne.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de l'approbation.";
            }
        } else {
            $error_message = "Candidature introuvable.";
        }
    }

    // Refuser une candidature
    if (isset($_POST['action']) && $_POST['action'] === 'decline_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Récupérer le player_id et vérifier droits MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ?");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'declined' WHERE id = ?");
            $stmt->execute([$application_id]);
            // Notification au joueur
            $title = 'Candidature refusée';
            $message = 'Votre candidature à la campagne "' . $campaign['title'] . '" a été refusée.';
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
            $stmt->execute([$player_id, $title, $message, $campaign_id]);
            $success_message = "Candidature refusée.";
        } else {
            $error_message = "Candidature introuvable.";
        }
    }

    // Annuler l'acceptation (revenir à 'pending' et retirer le joueur des membres)
    if (isset($_POST['action']) && $_POST['action'] === 'revoke_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Récupérer player_id et vérifier que la candidature est approuvée pour cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ? AND ca.status = 'approved'");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $pdo->beginTransaction();
            try {
                // Revenir à pending
                $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'pending' WHERE id = ?");
                $stmt->execute([$application_id]);
                // Retirer le membre de la campagne s'il y est
                $stmt = $pdo->prepare("DELETE FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
                $stmt->execute([$campaign_id, $player_id]);
                // Notifier le joueur
                $title = 'Acceptation annulée';
                $message = 'Votre acceptation dans la campagne "' . $campaign['title'] . '" a été annulée par le MJ. Votre candidature est de nouveau en attente.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$player_id, $title, $message, $campaign_id]);
                $pdo->commit();
                $success_message = "Acceptation annulée. Candidature remise en attente et joueur retiré.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de l'annulation de l'acceptation.";
            }
        } else {
            $error_message = "Candidature approuvée introuvable.";
        }
    }

    // Exclure un membre (joueur) de la campagne
    if (isset($_POST['action']) && $_POST['action'] === 'remove_member' && isset($_POST['member_user_id'])) {
        $member_user_id = (int)$_POST['member_user_id'];
        // Ne pas autoriser la suppression du MJ propriétaire
        if ($member_user_id === $dm_id) {
            $error_message = "Impossible d'exclure le MJ de sa propre campagne.";
        } else {
            // Vérifier que l'utilisateur est bien membre de cette campagne
            $stmt = $pdo->prepare("SELECT role FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
            $stmt->execute([$campaign_id, $member_user_id]);
            $member = $stmt->fetch();
            if ($member) {
                // Supprimer le membre
                $stmt = $pdo->prepare("DELETE FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
                $stmt->execute([$campaign_id, $member_user_id]);
                // Notifier le joueur
                $title = 'Exclusion de la campagne';
                $message = 'Vous avez été exclu de la campagne "' . $campaign['title'] . '" par le MJ.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$member_user_id, $title, $message, $campaign_id]);
                $success_message = "Joueur exclu de la campagne.";
            } else {
                $error_message = "Ce joueur n'est pas membre de la campagne.";
            }
        }
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

// Récupérer candidatures
$stmt = $pdo->prepare("SELECT ca.id, ca.player_id, ca.character_id, ca.message, ca.status, ca.created_at, u.username, ch.name AS character_name FROM campaign_applications ca JOIN users u ON ca.player_id = u.id LEFT JOIN characters ch ON ca.character_id = ch.id WHERE ca.campaign_id = ? ORDER BY ca.created_at DESC");
$stmt->execute([$campaign_id]);
$applications = $stmt->fetchAll();
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
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted">Depuis <?php echo date('d/m/Y', strtotime($m['joined_at'])); ?></small>
                                            <?php if ($m['role'] !== 'dm'): ?>
                                                <form method="POST" onsubmit="return confirm('Exclure ce joueur de la campagne ?');">
                                                    <input type="hidden" name="action" value="remove_member">
                                                    <input type="hidden" name="member_user_id" value="<?php echo (int)$m['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" title="Exclure">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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

        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><i class="fas fa-inbox me-2"></i>Candidatures</div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <p class="text-muted">Aucune candidature pour le moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Personnage</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $a): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($a['username']); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($a['character_id'])): ?>
                                                        <span class="badge bg-secondary">#<?php echo (int)$a['character_id']; ?></span>
                                                        <?php echo htmlspecialchars($a['character_name'] ?? 'Personnage'); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small" style="max-width: 400px;">
                                                    <?php echo nl2br(htmlspecialchars($a['message'] ?: '—')); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $badge = 'secondary';
                                                        if ($a['status'] === 'pending') $badge = 'warning';
                                                        if ($a['status'] === 'approved') $badge = 'primary';
                                                        if ($a['status'] === 'declined') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo $a['status']; ?></span>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></small></td>
                                                <td class="text-end">
                                                    <?php if ($a['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="approve_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Accepter</button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Refuser cette candidature ?');">
                                                            <input type="hidden" name="action" value="decline_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1"></i>Refuser</button>
                                                        </form>
                                                    <?php elseif ($a['status'] === 'approved'): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Annuler l\'acceptation de cette candidature ? Le joueur sera retiré de la campagne.');">
                                                            <input type="hidden" name="action" value="revoke_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-warning"><i class="fas fa-undo me-1"></i>Annuler l'acceptation</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
