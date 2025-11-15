<?php
require_once 'classes/init.php';
require_once 'classes/CampaignEvent.php';
require_once 'includes/functions.php';
$page_title = "Journal des Événements";
$current_page = "view_campaign_journal_player";

requireLogin();

$user_id = $_SESSION['user_id'];

// Vérifier que l'ID de campagne est fourni
if (!isset($_GET['campaign_id']) || !is_numeric($_GET['campaign_id'])) {
    header('Location: campaigns.php');
    exit();
}

$campaign_id = (int)$_GET['campaign_id'];

// Vérifier que l'utilisateur est membre de la campagne
$membership = User::isMemberOfCampaign($user_id, $campaign_id);

if (!$membership) {
    header('Location: campaigns.php');
    exit();
}

// Récupérer la campagne
$campaign = Campaign::findById($campaign_id);
if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

// Récupérer les événements publics de la campagne
$events = CampaignEvent::getPublicByCampaignId($campaign_id);

include_once 'includes/layout.php';
?>

<style>
.event-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #0dcaf0;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.event-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.event-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>

<div class="container mt-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><i class="fas fa-book me-2"></i>Journal des Événements</h1>
                    <p class="lead mb-2"><?php echo htmlspecialchars($campaign->getTitle()); ?></p>
                    <span class="badge bg-info">Vue Joueur</span>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.close();">
                        <i class="fas fa-times me-2"></i>Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des événements -->
    <?php if (empty($events)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h4 class="card-title">Aucun événement disponible</h4>
                        <p class="card-text text-muted">
                            Le maître du jeu n'a pas encore publié d'événements dans le journal de campagne.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <?php foreach ($events as $event): ?>
                    <div class="card event-card mb-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2 text-info"></i>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </h5>
                                <span class="event-date">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('d/m/Y à H:i', strtotime($event['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="event-content">
                                <?php echo nl2br(htmlspecialchars($event['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

