<?php
require_once 'classes/init.php';
require_once 'includes/functions.php';
$page_title = "Lieu - Vue Joueur";
$current_page = "view_scene_player";

requireLogin();

$user_id = $_SESSION['user_id'];
$requested_campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;

// Localiser le personnage/joueur dans le monde
$localization = Monde::localizeCharacter($user_id, $requested_campaign_id);

// Traiter les différents statuts de localisation
switch ($localization['status']) {
    case 'found':
        $place = $localization['place'];
        break;
        
    case 'member_no_place':
        // Le joueur est membre mais pas assigné à un lieu dans cette campagne
        $page_title = "Aucun lieu assigné dans cette campagne";
        include_once 'includes/layout.php';
        ?>
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h4 class="card-title">Aucun lieu assigné</h4>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($localization['message']); ?>
                                Le maître du jeu doit vous ajouter à un lieu pour que vous puissiez y accéder.
                            </p>
                            <a href="view_campaign.php?id=<?php echo $localization['campaign_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        exit();
        
    case 'not_member':
        // Le joueur n'est pas membre de cette campagne
        header('Location: campaigns.php');
        exit();
        
    case 'member_no_place_any':
        // Le joueur est membre d'une campagne mais pas assigné à un lieu
        $page_title = "Aucun lieu assigné";
        include_once 'includes/layout.php';
        ?>
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h4 class="card-title">Aucun lieu assigné</h4>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($localization['message']); ?>
                                Le maître du jeu doit vous ajouter à un lieu pour que vous puissiez y accéder.
                            </p>
                            <a href="campaigns.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>Voir mes campagnes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        exit();
        
    case 'no_campaigns':
        // Le joueur n'est membre d'aucune campagne
        $page_title = "Aucune campagne";
        include_once 'includes/layout.php';
        ?>
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4 class="card-title">Aucune campagne</h4>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($localization['message']); ?>
                                Rejoignez une campagne pour commencer à jouer.
                            </p>
                            <a href="campaigns.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>Voir les campagnes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        exit();
        
    case 'error':
    default:
        // Erreur lors de la localisation
        $page_title = "Erreur de localisation";
        include_once 'includes/layout.php';
        ?>
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h4 class="card-title">Erreur</h4>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($localization['message']); ?>
                            </p>
                            <a href="campaigns.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux campagnes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        exit();
}

$place_id = (int)$place['id'];

// Vérifier que l'utilisateur est membre de la campagne
$membership = User::isMemberOfCampaign($user_id, $place['campaign_id']);

if (!$membership) {
    header('Location: campaigns.php');
    exit();
}

// Récupérer les personnages du joueur présents dans ce lieu
$lieu = Lieu::findById($place_id);
$player_characters = $lieu ? $lieu->getPlayerCharacters($user_id) : [];

// Récupérer les positions de tous les pions (comme dans view_scene.php)
$tokenPositions = $lieu ? $lieu->getTokenPositions() : [];

// Récupérer TOUS les joueurs présents dans le lieu (comme dans view_scene.php)
$placePlayers = $lieu ? $lieu->getAllPlayers() : [];

// Récupérer les autres joueurs (pour l'affichage séparé)
$other_players = array_filter($placePlayers, function($player) use ($user_id) {
    return $player['player_id'] != $user_id;
});

// Récupérer les PNJ présents dans le lieu (seulement ceux visibles)
$placeNpcs = $lieu ? $lieu->getVisibleNpcs() : [];

// Récupérer les monstres présents dans le lieu (seulement ceux visibles)
$placeMonsters = $lieu ? $lieu->getVisibleMonsters() : [];

// Récupérer les objets présents dans le lieu (seulement ceux visibles et non attribués)
$placeObjects = $lieu ? $lieu->getVisibleObjects() : [];

// Les positions des objets sont maintenant gérées par place_tokens
// Elles sont déjà incluses dans $tokenPositions via $lieu->getTokenPositions()
// Ne pas écraser les positions de place_tokens par celles de items
// Les objets sans position dans place_tokens resteront dans la sidebar

include_once 'includes/layout.php';
include_once 'templates/view_scene_player_template.php';
