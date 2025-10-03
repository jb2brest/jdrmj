<?php
require_once 'config/database.php';
require_once 'classes/init.php';
require_once 'includes/functions.php';

// Les joueurs peuvent voir les campagnes publiques, les DM/Admin peuvent voir toutes les campagnes
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


// La fonction generateInviteCode est maintenant gérée par la classe Campaign

// Traitements POST: créer, supprimer, basculer visibilité (DM et Admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && User::isDMOrAdmin()) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $game_system = sanitizeInput($_POST['game_system'] ?? 'D&D 5e');
        // Le code d'invitation est généré automatiquement par la classe Campaign

        if (strlen($title) < 3) {
            $error_message = "Le titre doit contenir au moins 3 caractères.";
        } else {
            try {
                // Utilisation de la classe Campaign pour la création
                $campaignData = [
                    'dm_id' => $user_id,
                    'title' => $title,
                    'description' => $description,
                    'game_system' => $game_system,
                    'is_public' => $is_public
                ];
                
                $newCampaign = Campaign::create($campaignData);
                
                if ($newCampaign) {
                    $success_message = "Campagne créée avec succès.";
                } else {
                    $error_message = "Erreur lors de la création de la campagne.";
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors de la création de la campagne : " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Vérifier que l'utilisateur a le droit de supprimer cette campagne
            if (User::isAdmin()) {
                $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ?");
                $stmt->execute([$campaign_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND dm_id = ?");
                $stmt->execute([$campaign_id, $user_id]);
            }
            
            if (!$stmt->fetch()) {
                throw new Exception("Campagne non trouvée ou vous n'avez pas les droits.");
            }
            
            // 1. Supprimer les notifications liées à la campagne (si la colonne existe)
            try {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE campaign_id = ?");
                $stmt->execute([$campaign_id]);
            } catch (PDOException $e) {
                // La table notifications n'a peut-être pas de colonne campaign_id
                // Ce n'est pas critique, on continue
            }
            
            // 2. Supprimer les applications de campagne
            $stmt = $pdo->prepare("DELETE FROM campaign_applications WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            // 3. Supprimer les entrées du journal de campagne
            $stmt = $pdo->prepare("DELETE FROM campaign_journal WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            // 4. Dissocier les lieux de la campagne (ne pas les supprimer)
            $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            // 5. Retirer les joueurs des lieux de cette campagne
            $stmt = $pdo->prepare("
                DELETE pp FROM place_players pp
                INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id
                WHERE pc.campaign_id = ?
            ");
            $stmt->execute([$campaign_id]);
            
            // 6. Retirer les PNJ des lieux de cette campagne
            $stmt = $pdo->prepare("
                DELETE pn FROM place_npcs pn
                INNER JOIN place_campaigns pc ON pn.place_id = pc.place_id
                WHERE pc.campaign_id = ?
            ");
            $stmt->execute([$campaign_id]);
            
            // 7. Retirer les monstres des lieux de cette campagne
            $stmt = $pdo->prepare("
                DELETE pm FROM place_monsters pm
                INNER JOIN place_campaigns pc ON pm.place_id = pc.place_id
                WHERE pc.campaign_id = ?
            ");
            $stmt->execute([$campaign_id]);
            
            // 8. Supprimer les membres de la campagne
            $stmt = $pdo->prepare("DELETE FROM campaign_members WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            
            // 9. Supprimer la campagne elle-même
            $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            
            $pdo->commit();
            $success_message = "Campagne supprimée avec succès. Toutes les inscriptions des joueurs et personnages participants ont été supprimées.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors de la suppression de la campagne: " . $e->getMessage();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle_visibility' && isset($_POST['campaign_id'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        // Les admins peuvent modifier la visibilité de toutes les campagnes, les MJ seulement les leurs
        if (User::isAdmin()) {
            $stmt = $pdo->prepare("UPDATE campaigns SET is_public = NOT is_public WHERE id = ?");
            $stmt->execute([$campaign_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE campaigns SET is_public = NOT is_public WHERE id = ? AND dm_id = ?");
            $stmt->execute([$campaign_id, $user_id]);
        }
        $success_message = "Visibilité mise à jour.";
    }
}

// Récupérer les campagnes selon le rôle en utilisant la classe Campaign
$userRole = getUserRole();
$campaigns = Campaign::getAccessibleCampaigns($user_id, $userRole);

// Définir le titre de la page selon le rôle
if ($userRole === 'admin') {
    $page_title = 'Toutes les Campagnes';
} elseif ($userRole === 'dm') {
    $page_title = 'Mes Campagnes';
} else {
    $page_title = 'Campagnes Disponibles';
}
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
            <h1><i class="fas fa-book me-2"></i><?php echo User::isAdmin() ? 'Toutes les Campagnes' : 'Mes Campagnes'; ?></h1>
        </div>

        <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (User::isDMOrAdmin()): ?>
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
                                    <?php if (User::isAdmin() && !empty($c['dm_name'])): ?>
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
                                        <?php if (User::isAdmin() || (User::isDM() && $c['dm_id'] == $user_id)): ?>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette campagne ? Cette action supprimera également toutes les inscriptions des joueurs et personnages participants.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="<?php echo User::isAdmin() ? 'Supprimer la campagne (Admin)' : 'Supprimer ma campagne'; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="toggle_visibility">
                                                <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                <button class="btn btn-sm btn-outline-secondary" title="Basculer visibilité">
                                                    <i class="fas fa-eye-slash"></i>
                                                </button>
                                            </form>
                                    </div>
                                </div>
                                <?php if (User::isDMOrAdmin()): ?>
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



















