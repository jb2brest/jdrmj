<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Campagnes Publiques";
$current_page = "public_campaigns";


requireLogin();

// Seuls les joueurs candidatent, mais un MJ connecté comme joueur peut aussi voir
$player_id = $_SESSION['user_id'];

// Récupérer les personnages du joueur pour la sélection dans la candidature
$userCharactersStmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY created_at DESC");
$userCharactersStmt->execute([$player_id]);
$userCharacters = $userCharactersStmt->fetchAll();

// Postuler / Annuler une candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Annuler une candidature en attente
    if (isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'cancelled' WHERE campaign_id = ? AND player_id = ? AND status = 'pending'");
        $stmt->execute([$campaign_id, $player_id]);
        if ($stmt->rowCount() > 0) {
            $success_message = "Candidature annulée.";
        } else {
            $error_message = "Aucune candidature en attente à annuler.";
        }
    }

    // Nouvelle candidature / mise à jour
    if (!isset($_POST['action']) || $_POST['action'] !== 'cancel') {
        if (isset($_POST['campaign_id'])) {
            $campaign_id = (int)$_POST['campaign_id'];
            $message = sanitizeInput($_POST['message'] ?? '');
            $character_id = isset($_POST['character_id']) ? (int)$_POST['character_id'] : 0;

            // Vérifier campagne publique
            $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND is_public = 1");
            $stmt->execute([$campaign_id]);
            if ($stmt->fetch()) {
                // Vérifier que le personnage appartient bien au joueur
                $charCheck = $pdo->prepare("SELECT id FROM characters WHERE id = ? AND user_id = ?");
                $charCheck->execute([$character_id, $player_id]);
                if (!$charCheck->fetch()) {
                    $error_message = "Veuillez sélectionner un personnage valide.";
                } else {
                    // Empêcher candidatures multiples, mettre à jour message et personnage si déjà existant, et repasser en pending
                    $stmt = $pdo->prepare("INSERT INTO campaign_applications (campaign_id, player_id, character_id, message) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE message = VALUES(message), character_id = VALUES(character_id), status = 'pending'");
                    $stmt->execute([$campaign_id, $player_id, $character_id, $message]);
                    $success_message = "Candidature envoyée !";
                }
            } else {
                $error_message = "Campagne introuvable ou non publique.";
            }
        }
    }
}

// Rechercher
$search = sanitizeInput($_GET['q'] ?? '');
$params = [];
$where = "WHERE c.is_public = 1";
if ($search !== '') {
    $where .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.game_system LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

// Récupérer campagnes publiques avec stats simples
$sql = "SELECT c.*, (SELECT COUNT(*) FROM campaign_members cm WHERE cm.campaign_id = c.id) as member_count FROM campaigns c $where ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

// Candidatures existantes de l'utilisateur
$stmt = $pdo->prepare("SELECT campaign_id, status FROM campaign_applications WHERE player_id = ?");
$stmt->execute([$player_id]);
$applications = $stmt->fetchAll();
$applicationByCampaign = [];
foreach ($applications as $a) {
    $applicationByCampaign[$a['campaign_id']] = $a['status'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-book-open me-2"></i>Campagnes Publiques</h1>
            <form class="d-flex" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-light" type="submit">Rechercher</button>
            </form>
        </div>

        <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (empty($campaigns)): ?>
            <p class="text-muted">Aucune campagne publique trouvée.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($campaigns as $c): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($c['title']); ?>
                                    </h5>
                                    <span class="badge bg-success">Publique</span>
                                </div>
                                <p class="text-muted mb-2">Système : <?php echo htmlspecialchars($c['game_system']); ?></p>
                                <?php if (!empty($c['description'])): ?>
                                    <p class="small mb-3"><?php echo nl2br(htmlspecialchars(substr($c['description'], 0, 140))); ?><?php echo strlen($c['description']) > 140 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?php echo $c['member_count']; ?> membre(s)</small>
                                        <?php 
                                            $status = $applicationByCampaign[$c['id']] ?? null;
                                        ?>
                                        <?php if ($status === 'pending'): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Annuler votre candidature ?');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                <button class="btn btn-sm btn-outline-warning"><i class="fas fa-ban me-1"></i>Annuler</button>
                                            </form>
                                        <?php elseif ($status === 'approved'): ?>
                                            <span class="badge bg-primary">Accepté</span>
                                        <?php elseif ($status === 'declined'): ?>
                                            <span class="badge bg-danger">Refusé</span>
                                        <?php elseif ($status === 'cancelled'): ?>
                                            <span class="badge bg-secondary">Annulée</span>
                                            <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#applyModal<?php echo $c['id']; ?>">
                                                <i class="fas fa-paper-plane me-2"></i>Postuler à nouveau
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#applyModal<?php echo $c['id']; ?>">
                                                <i class="fas fa-paper-plane me-2"></i>Postuler
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de candidature -->
                        <div class="modal fade" id="applyModal<?php echo $c['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Postuler à la campagne</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Personnage</label>
                                                <select class="form-select" name="character_id" required <?php echo empty($userCharacters) ? 'disabled' : ''; ?>>
                                                    <option value="" disabled selected>Choisir un personnage</option>
                                                    <?php foreach ($userCharacters as $uc): ?>
                                                        <option value="<?php echo $uc['id']; ?>"><?php echo htmlspecialchars($uc['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if (empty($userCharacters)): ?>
                                                    <div class="form-text text-danger">Vous n'avez pas encore de personnage. <a href="character_create_step1.php">Créez-en un</a> pour postuler.</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Message (optionnel)</label>
                                                <textarea class="form-control" name="message" rows="4" placeholder="Parlez de votre expérience, disponibilités..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary" <?php echo empty($userCharacters) ? 'disabled' : ''; ?>>Envoyer</button>
                                        </div>
                                    </form>
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
