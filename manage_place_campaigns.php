<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les paramètres
$place_id = isset($_GET['place_id']) ? (int)$_GET['place_id'] : 0;
$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'associate_place':
                $place_id = (int)$_POST['place_id'];
                $campaign_id = (int)$_POST['campaign_id'];
                
                if (associatePlaceToCampaign($place_id, $campaign_id)) {
                    $success_message = "Lieu associé à la campagne avec succès.";
                } else {
                    $error_message = "Erreur lors de l'association du lieu à la campagne.";
                }
                break;
                
            case 'dissociate_place':
                $place_id = (int)$_POST['place_id'];
                $campaign_id = (int)$_POST['campaign_id'];
                
                if (dissociatePlaceFromCampaign($place_id, $campaign_id)) {
                    $success_message = "Lieu dissocié de la campagne avec succès.";
                } else {
                    $error_message = "Erreur lors de la dissociation du lieu de la campagne.";
                }
                break;
                
            case 'update_place_campaigns':
                $place_id = (int)$_POST['place_id'];
                $campaign_ids = isset($_POST['campaign_ids']) ? array_map('intval', $_POST['campaign_ids']) : [];
                
                if (updatePlaceCampaignAssociations($place_id, $campaign_ids)) {
                    $success_message = "Associations du lieu mises à jour avec succès.";
                } else {
                    $error_message = "Erreur lors de la mise à jour des associations.";
                }
                break;
                
            case 'update_campaign_places':
                $campaign_id = (int)$_POST['campaign_id'];
                $place_ids = isset($_POST['place_ids']) ? array_map('intval', $_POST['place_ids']) : [];
                
                if (updateCampaignPlaceAssociations($campaign_id, $place_ids)) {
                    $success_message = "Associations de la campagne mises à jour avec succès.";
                } else {
                    $error_message = "Erreur lors de la mise à jour des associations.";
                }
                break;
        }
    }
}

// Récupérer les données selon le contexte
if ($place_id > 0) {
    // Mode gestion d'un lieu spécifique
    $stmt = $pdo->prepare("SELECT * FROM places WHERE id = ?");
    $stmt->execute([$place_id]);
    $place = $stmt->fetch();
    
    if (!$place) {
        header('Location: campaigns.php');
        exit();
    }
    
    $associated_campaigns = getCampaignsForPlace($place_id);
    $available_campaigns = getAvailableCampaignsForPlace($place_id);
    
} elseif ($campaign_id > 0) {
    // Mode gestion d'une campagne spécifique
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND dm_id = ?");
    $stmt->execute([$campaign_id, $user_id]);
    $campaign = $stmt->fetch();
    
    if (!$campaign) {
        header('Location: campaigns.php');
        exit();
    }
    
    $associated_places = getPlacesForCampaign($campaign_id);
    $available_places = getAvailablePlacesForCampaign($campaign_id);
    
} else {
    // Mode vue d'ensemble
    $stmt = $pdo->prepare("
        SELECT p.*, 
               GROUP_CONCAT(c.title ORDER BY c.title SEPARATOR ', ') as campaign_titles
        FROM places p
        LEFT JOIN place_campaigns pc ON p.id = pc.place_id
        LEFT JOIN campaigns c ON pc.campaign_id = c.id
        GROUP BY p.id
        ORDER BY p.title
    ");
    $stmt->execute();
    $all_places = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(pc.place_id) as place_count
        FROM campaigns c
        LEFT JOIN place_campaigns pc ON c.id = pc.campaign_id
        WHERE c.dm_id = ?
        GROUP BY c.id
        ORDER BY c.title
    ");
    $stmt->execute([$user_id]);
    $all_campaigns = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des associations Lieu-Campagne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .association-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .association-card.associated {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .campaign-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="campaign-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-link me-3"></i>Gestion des associations Lieu-Campagne</h1>
                    <p class="mb-0">Associez des lieux à plusieurs campagnes</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="campaigns.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Retour aux campagnes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($place_id > 0): ?>
            <!-- Mode gestion d'un lieu spécifique -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Lieu : <?php echo htmlspecialchars($place['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_place_campaigns">
                                <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
                                
                                <h6>Campagnes associées :</h6>
                                <?php if (!empty($associated_campaigns)): ?>
                                    <?php foreach ($associated_campaigns as $campaign): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="campaign_ids[]" 
                                                   value="<?php echo $campaign['id']; ?>" id="campaign_<?php echo $campaign['id']; ?>" checked>
                                            <label class="form-check-label" for="campaign_<?php echo $campaign['id']; ?>">
                                                <?php echo htmlspecialchars($campaign['title']); ?>
                                                <small class="text-muted">(associé le <?php echo date('d/m/Y', strtotime($campaign['associated_at'])); ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aucune campagne associée</p>
                                <?php endif; ?>
                                
                                <h6 class="mt-3">Campagnes disponibles :</h6>
                                <?php if (!empty($available_campaigns)): ?>
                                    <?php foreach ($available_campaigns as $campaign): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="campaign_ids[]" 
                                                   value="<?php echo $campaign['id']; ?>" id="campaign_<?php echo $campaign['id']; ?>">
                                            <label class="form-check-label" for="campaign_<?php echo $campaign['id']; ?>">
                                                <?php echo htmlspecialchars($campaign['title']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Toutes les campagnes sont déjà associées</p>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Mettre à jour les associations
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations du lieu</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Titre :</strong> <?php echo htmlspecialchars($place['title']); ?></p>
                            <?php if ($place['notes']): ?>
                                <p><strong>Notes :</strong> <?php echo nl2br(htmlspecialchars($place['notes'])); ?></p>
                            <?php endif; ?>
                            <?php if ($place['map_url']): ?>
                                <p><strong>Carte :</strong> <a href="<?php echo htmlspecialchars($place['map_url']); ?>" target="_blank">Voir la carte</a></p>
                            <?php endif; ?>
                            <p><strong>Créé le :</strong> <?php echo date('d/m/Y H:i', strtotime($place['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($campaign_id > 0): ?>
            <!-- Mode gestion d'une campagne spécifique -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-dice-d20 me-2"></i>Campagne : <?php echo htmlspecialchars($campaign['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_campaign_places">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                                
                                <h6>Lieux associés :</h6>
                                <?php if (!empty($associated_places)): ?>
                                    <?php foreach ($associated_places as $place): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="place_ids[]" 
                                                   value="<?php echo $place['id']; ?>" id="place_<?php echo $place['id']; ?>" checked>
                                            <label class="form-check-label" for="place_<?php echo $place['id']; ?>">
                                                <?php echo htmlspecialchars($place['title']); ?>
                                                <small class="text-muted">(associé le <?php echo date('d/m/Y', strtotime($place['associated_at'])); ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aucun lieu associé</p>
                                <?php endif; ?>
                                
                                <h6 class="mt-3">Lieux disponibles :</h6>
                                <?php if (!empty($available_places)): ?>
                                    <?php foreach ($available_places as $place): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="place_ids[]" 
                                                   value="<?php echo $place['id']; ?>" id="place_<?php echo $place['id']; ?>">
                                            <label class="form-check-label" for="place_<?php echo $place['id']; ?>">
                                                <?php echo htmlspecialchars($place['title']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Tous les lieux sont déjà associés</p>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Mettre à jour les associations
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations de la campagne</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Titre :</strong> <?php echo htmlspecialchars($campaign['title']); ?></p>
                            <?php if ($campaign['description']): ?>
                                <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                            <?php endif; ?>
                            <p><strong>Système de jeu :</strong> <?php echo htmlspecialchars($campaign['game_system']); ?></p>
                            <p><strong>Créée le :</strong> <?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Mode vue d'ensemble -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Lieux</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($all_places)): ?>
                                <?php foreach ($all_places as $place): ?>
                                    <div class="association-card <?php echo $place['campaign_titles'] ? 'associated' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($place['title']); ?></h6>
                                                <?php if ($place['campaign_titles']): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-link me-1"></i>Associé à : <?php echo htmlspecialchars($place['campaign_titles']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-unlink me-1"></i>Aucune campagne associée
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="?place_id=<?php echo $place['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Aucun lieu trouvé</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-dice-d20 me-2"></i>Campagnes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($all_campaigns)): ?>
                                <?php foreach ($all_campaigns as $campaign): ?>
                                    <div class="association-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($campaign['title']); ?></h6>
                                                <small class="text-info">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo $campaign['place_count']; ?> lieu(x) associé(s)
                                                </small>
                                            </div>
                                            <a href="?campaign_id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Aucune campagne trouvée</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
