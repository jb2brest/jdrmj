<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$session_id = (int)$_GET['id'];

// Charger la session et sa campagne
$stmt = $pdo->prepare("SELECT gs.*, c.title AS campaign_title, c.id AS campaign_id, u.username AS dm_username FROM game_sessions gs JOIN users u ON gs.dm_id = u.id LEFT JOIN campaigns c ON gs.campaign_id = c.id WHERE gs.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: index.php');
    exit();
}

$dm_id = (int)$session['dm_id'];
$isOwnerDM = (isDM() && $_SESSION['user_id'] === $dm_id);

// Autoriser également les joueurs inscrits à voir la session
$canView = $isOwnerDM;
if (!$canView) {
    $stmt = $pdo->prepare("SELECT 1 FROM session_registrations WHERE session_id = ? AND player_id = ? LIMIT 1");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $canView = (bool)$stmt->fetch();
}

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Actions MJ sur les inscriptions et session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerDM) {
    if (isset($_POST['action']) && isset($_POST['registration_id'])) {
        $registration_id = (int)$_POST['registration_id'];
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE session_registrations SET status = 'approved' WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription approuvée.";
        } elseif ($_POST['action'] === 'decline') {
            $stmt = $pdo->prepare("UPDATE session_registrations SET status = 'declined' WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription refusée.";
        } elseif ($_POST['action'] === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM session_registrations WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription supprimée.";
        }
    }

    // Ajouter un candidat approuvé de la campagne à cette session
    if (isset($_POST['action']) && $_POST['action'] === 'add_applicant' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        if (!empty($session['campaign_id'])) {
            $stmt = $pdo->prepare("SELECT ca.player_id, ca.character_id FROM campaign_applications ca WHERE ca.id = ? AND ca.campaign_id = ? AND ca.status = 'approved' AND ca.character_id IS NOT NULL");
            $stmt->execute([$application_id, $session['campaign_id']]);
            $app = $stmt->fetch();
            if ($app) {
                $player_id = (int)$app['player_id'];
                $character_id = (int)$app['character_id'];
                $check = $pdo->prepare("SELECT id FROM session_registrations WHERE session_id = ? AND player_id = ? LIMIT 1");
                $check->execute([$session_id, $player_id]);
                if ($check->fetch()) {
                    $error_message = "Le joueur est déjà inscrit à cette session.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO session_registrations (session_id, player_id, character_id, status) VALUES (?, ?, ?, 'approved')");
                    $stmt->execute([$session_id, $player_id, $character_id]);
                    $success_message = "Candidat ajouté à la session.";
                }
            } else {
                $error_message = "Candidature invalide (doit être approuvée avec un personnage).";
            }
        } else {
            $error_message = "Cette session n'est pas rattachée à une campagne.";
        }
    }

    // Démarrer la session (changer le statut à in_progress)
    if (isset($_POST['action']) && $_POST['action'] === 'start_session') {
        if (in_array($session['status'], ['planning', 'recruiting'])) {
            $stmt = $pdo->prepare("UPDATE game_sessions SET status = 'in_progress' WHERE id = ? AND dm_id = ?");
            $stmt->execute([$session_id, $dm_id]);
            $success_message = "Session démarrée.";
            $stmt = $pdo->prepare("SELECT gs.*, c.title AS campaign_title, c.id AS campaign_id, u.username AS dm_username FROM game_sessions gs JOIN users u ON gs.dm_id = u.id LEFT JOIN campaigns c ON gs.campaign_id = c.id WHERE gs.id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
        } else {
            $error_message = "La session ne peut pas être démarrée depuis son statut actuel.";
        }
    }

    // Mettre à jour le contexte de début
    if (isset($_POST['action']) && $_POST['action'] === 'save_start_context') {
        $start_context = trim($_POST['start_context'] ?? '');
        $stmt = $pdo->prepare("UPDATE game_sessions SET start_context = ? WHERE id = ? AND dm_id = ?");
        $stmt->execute([$start_context, $session_id, $dm_id]);
        $success_message = "Contexte de début sauvegardé.";
        $session['start_context'] = $start_context;
    }
}

// Récupérer les inscriptions
$stmt = $pdo->prepare("SELECT sr.id, sr.player_id, sr.character_id, sr.status, sr.registered_at, u.username, ch.name AS character_name FROM session_registrations sr JOIN users u ON sr.player_id = u.id LEFT JOIN characters ch ON sr.character_id = ch.id WHERE sr.session_id = ? ORDER BY sr.registered_at DESC");
$stmt->execute([$session_id]);
$registrations = $stmt->fetchAll();

// Récupérer la liste des candidatures approuvées de la campagne (non encore inscrits)
$approvedApplications = [];
if (!empty($session['campaign_id']) && $isOwnerDM) {
    $stmt = $pdo->prepare("SELECT ca.id, ca.player_id, ca.character_id, u.username, ch.name AS character_name FROM campaign_applications ca JOIN users u ON ca.player_id = u.id LEFT JOIN characters ch ON ca.character_id = ch.id WHERE ca.campaign_id = ? AND ca.status = 'approved' AND ca.character_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM session_registrations sr WHERE sr.session_id = ? AND sr.player_id = ca.player_id)");
    $stmt->execute([$session['campaign_id'], $session_id]);
    $approvedApplications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session: <?php echo htmlspecialchars($session['title']); ?> - JDR 4 MJ</title>
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
                    <?php if ($session['campaign_id']): ?>
                        <li class="nav-item"><a class="nav-link" href="view_campaign.php?id=<?php echo (int)$session['campaign_id']; ?>">Retour Campagne</a></li>
                    <?php endif; ?>
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
        <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($session['title']); ?></h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($session['status']); ?></span>
                <?php if ($isOwnerDM && in_array($session['status'], ['planning', 'recruiting'])): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="start_session">
                        <button class="btn btn-sm btn-primary"><i class="fas fa-play me-1"></i>Démarrer</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">Détails</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>MJ:</strong> <?php echo htmlspecialchars($session['dm_username']); ?></div>
                        <?php if ($session['campaign_id']): ?>
                            <div class="mb-2"><strong>Campagne:</strong> <a href="view_campaign.php?id=<?php echo (int)$session['campaign_id']; ?>"><?php echo htmlspecialchars($session['campaign_title']); ?></a></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong>Date:</strong> <?php echo !empty($session['session_date']) ? date('d/m/Y H:i', strtotime($session['session_date'])) : '—'; ?></div>
                        <div class="mb-2"><strong>Lieu:</strong> <?php echo $session['is_online'] ? 'En ligne' : htmlspecialchars($session['location'] ?: '—'); ?></div>
                        <?php if ($session['is_online'] && !empty($session['meeting_link'])): ?>
                            <div class="mb-2"><strong>Lien:</strong> <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" target="_blank" rel="noopener noreferrer">Rejoindre</a></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong>Places:</strong> <?php echo (int)$session['max_players']; ?></div>
                        <?php if (!empty($session['description'])): ?>
                            <div class="mt-3"><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($session['description'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($session['start_context'])): ?>
                            <div class="mt-3"><strong>Contexte de début:</strong><br><?php echo nl2br(htmlspecialchars($session['start_context'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Inscriptions</span>
                        <?php if ($isOwnerDM && !empty($approvedApplications)): ?>
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="action" value="add_applicant">
                                <select name="application_id" class="form-select form-select-sm" required>
                                    <option value="" disabled selected>Ajouter un candidat (approuvé)</option>
                                    <?php foreach ($approvedApplications as $a): ?>
                                        <option value="<?php echo (int)$a['id']; ?>">
                                            <?php echo htmlspecialchars($a['username']); ?> — <?php echo htmlspecialchars($a['character_name'] ?? 'Personnage'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-user-plus me-1"></i>Ajouter</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registrations)): ?>
                            <p class="text-muted">Aucune inscription pour le moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Personnage</th>
                                            <th>Statut</th>
                                            <?php if ($isOwnerDM): ?><th class="text-end">Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $r): ?>
                                            <tr>
                                                <td><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($r['username']); ?></td>
                                                <td>
                                                    <?php if ($r['character_id']): ?>
                                                        <a href="view_character.php?id=<?php echo (int)$r['character_id']; ?>&dm_campaign_id=<?php echo (int)$session['campaign_id']; ?>" class="text-decoration-none">
                                                            <span class="badge bg-secondary">#<?php echo (int)$r['character_id']; ?></span>
                                                            <?php echo htmlspecialchars($r['character_name'] ?? 'Personnage'); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $badge = 'secondary';
                                                        if ($r['status'] === 'pending') $badge = 'warning';
                                                        if ($r['status'] === 'approved') $badge = 'primary';
                                                        if ($r['status'] === 'declined') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo $r['status']; ?></span>
                                                </td>
                                                <?php if ($isOwnerDM): ?>
                                                <td class="text-end">
                                                    <?php if ($r['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i></button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Refuser cette inscription ?');">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="decline">
                                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette inscription ?');">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="remove">
                                                            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                                <?php endif; ?>
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

        <?php if ($isOwnerDM): ?>
        <div class="row g-4 mt-1">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">Contexte de début de session</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_start_context">
                            <textarea class="form-control" name="start_context" rows="5" placeholder="Décrivez la scène d'ouverture, l'ambiance, les éléments clés..."><?php echo htmlspecialchars($session['start_context'] ?? ''); ?></textarea>
                            <div class="mt-2">
                                <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Sauvegarder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
