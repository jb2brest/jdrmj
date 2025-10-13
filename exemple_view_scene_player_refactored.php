<?php
/**
 * Exemple de refactorisation de view_scene_player.php avec le singleton Database
 * 
 * Ce fichier montre comment view_scene_player.php pourrait être refactorisé
 * pour utiliser le singleton Database au lieu de la variable $pdo globale
 */

// Option 1: Utiliser le singleton Database directement
require_once 'classes/init.php'; // Inclut Database et User
require_once 'includes/functions.php';

$page_title = "Lieu - Vue Joueur";
$current_page = "view_scene_player";

requireLogin();

$user_id = $_SESSION['user_id'];
$requested_campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;

// Trouver le lieu où se trouve le joueur
if ($requested_campaign_id) {
    // Si un campaign_id est spécifié, chercher dans cette campagne
    // AVANT (avec $pdo global) :
    // $stmt = $pdo->prepare("SELECT ...");
    // $stmt->execute([$user_id, $requested_campaign_id]);
    // $place = $stmt->fetch();
    
    // APRÈS (avec singleton Database) :
    $place = Database::fetch("
        SELECT p.*, c.title as campaign_title, c.dm_id, c.id as campaign_id
        FROM places p 
        INNER JOIN place_campaigns pc ON p.id = pc.place_id
        JOIN campaigns c ON pc.campaign_id = c.id 
        JOIN place_players pp ON p.id = pp.place_id 
        WHERE pp.player_id = ? AND c.id = ?
        LIMIT 1
    ", [$user_id, $requested_campaign_id]);
    
    // Si aucun lieu trouvé dans cette campagne, vérifier si le joueur est membre de la campagne
    if (!$place) {
        // AVANT (avec $pdo global) :
        // $stmt = $pdo->prepare("SELECT cm.role FROM campaign_members cm WHERE cm.campaign_id = ? AND cm.user_id = ?");
        // $stmt->execute([$requested_campaign_id, $user_id]);
        // $membership = $stmt->fetch();
        
        // APRÈS (avec singleton Database) :
        $membership = Database::fetch(
            "SELECT cm.role FROM campaign_members cm WHERE cm.campaign_id = ? AND cm.user_id = ?",
            [$requested_campaign_id, $user_id]
        );
        
        if ($membership) {
            // Le joueur est membre mais pas assigné à un lieu, afficher un message spécifique
            $page_title = "Aucun lieu assigné dans cette campagne";
            include 'includes/layout.php';
            ?>
            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h4 class="card-title">Aucun lieu assigné</h4>
                                <p class="card-text text-muted">
                                    Vous êtes membre de cette campagne mais n'êtes pas encore assigné à un lieu spécifique. 
                                    Le maître du jeu doit vous ajouter à un lieu pour que vous puissiez y accéder.
                                </p>
                                <a href="view_campaign.php?id=<?php echo $requested_campaign_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour à la campagne
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            exit();
        } else {
            // Le joueur n'est pas membre de cette campagne
            header("Location: campaigns.php?error=not_member");
            exit();
        }
    }
} else {
    // Comportement original : chercher n'importe quel lieu où se trouve le joueur
    // AVANT (avec $pdo global) :
    // $stmt = $pdo->prepare("SELECT ...");
    // $stmt->execute([$user_id]);
    // $place = $stmt->fetch();
    
    // APRÈS (avec singleton Database) :
    $place = Database::fetch("
        SELECT p.*, c.title as campaign_title, c.dm_id, c.id as campaign_id
        FROM places p
        INNER JOIN place_campaigns pc ON p.id = pc.place_id
        JOIN campaigns c ON pc.campaign_id = c.id 
        JOIN place_players pp ON p.id = pp.place_id 
        WHERE pp.player_id = ?
        LIMIT 1
    ", [$user_id]);
    
    if (!$place) {
        // Vérifier si le joueur est membre d'au moins une campagne
        // AVANT (avec $pdo global) :
        // $stmt = $pdo->prepare("SELECT cm.role FROM campaign_members cm WHERE cm.user_id = ?");
        // $stmt->execute([$user_id]);
        // $membership = $stmt->fetch();
        
        // APRÈS (avec singleton Database) :
        $membership = Database::fetch(
            "SELECT cm.role FROM campaign_members cm WHERE cm.user_id = ?",
            [$user_id]
        );
        
        if ($membership) {
            // Le joueur est membre mais pas assigné à un lieu
            $page_title = "Aucun lieu assigné";
            include 'includes/layout.php';
            ?>
            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h4 class="card-title">Aucun lieu assigné</h4>
                                <p class="card-text text-muted">
                                    Vous êtes membre d'une campagne mais n'êtes pas encore assigné à un lieu spécifique. 
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
        } else {
            // Le joueur n'est membre d'aucune campagne
            header("Location: campaigns.php?error=no_campaigns");
            exit();
        }
    }
}

// Si on arrive ici, le joueur a un lieu assigné
// Récupérer les informations du lieu et de la campagne
$place_id = $place['id'];
$campaign_id = $place['campaign_id'];

// Récupérer les autres joueurs présents dans ce lieu
// AVANT (avec $pdo global) :
// $stmt = $pdo->prepare("SELECT ...");
// $stmt->execute([$place_id]);
// $players = $stmt->fetchAll();

// APRÈS (avec singleton Database) :
$players = Database::fetchAll("
    SELECT u.username, u.role, pp.arrival_time
    FROM place_players pp
    JOIN users u ON pp.player_id = u.id
    WHERE pp.place_id = ?
    ORDER BY pp.arrival_time ASC
", [$place_id]);

// Récupérer les objets présents dans ce lieu
// AVANT (avec $pdo global) :
// $stmt = $pdo->prepare("SELECT ...");
// $stmt->execute([$place_id]);
// $objects = $stmt->fetchAll();

// APRÈS (avec singleton Database) :
$objects = Database::fetchAll("
    SELECT o.*, ot.name as object_type_name
    FROM objects o
    JOIN object_types ot ON o.object_type_id = ot.id
    WHERE o.place_id = ?
    ORDER BY o.name ASC
", [$place_id]);

// Récupérer les PNJ présents dans ce lieu
// AVANT (avec $pdo global) :
// $stmt = $pdo->prepare("SELECT ...");
// $stmt->execute([$place_id]);
// $npcs = $stmt->fetchAll();

// APRÈS (avec singleton Database) :
$npcs = Database::fetchAll("
    SELECT n.*, nt.name as npc_type_name
    FROM npcs n
    JOIN npc_types nt ON n.npc_type_id = nt.id
    WHERE n.place_id = ?
    ORDER BY n.name ASC
", [$place_id]);

// Afficher la page
include 'includes/layout.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo htmlspecialchars($place['name']); ?></h4>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($place['campaign_title']); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($place['description'])): ?>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($place['description'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($place['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($place['image_url']); ?>" 
                             class="img-fluid rounded mb-3" 
                             alt="<?php echo htmlspecialchars($place['name']); ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Joueurs présents -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Joueurs présents</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($players)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($players as $player): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($player['username']); ?></span>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($player['arrival_time'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Aucun joueur présent</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Objets présents -->
            <?php if (!empty($objects)): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Objets présents</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($objects as $object): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($object['name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($object['object_type_name']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- PNJ présents -->
            <?php if (!empty($npcs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">PNJ présents</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($npcs as $npc): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($npc['name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($npc['npc_type_name']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Option 2: Utiliser les fonctions de compatibilité
// Au lieu de Database::fetch(), on pourrait utiliser :
// $place = fetchQuery("SELECT ...", [$user_id, $requested_campaign_id]);

// Option 3: Utiliser la variable $pdo globale (compatibilité)
// La variable $pdo globale est toujours disponible pour la compatibilité
// $stmt = $pdo->prepare("SELECT ...");
// $stmt->execute([$user_id, $requested_campaign_id]);
// $place = $stmt->fetch();
?>
