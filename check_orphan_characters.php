<?php
/**
 * Script pour vérifier les personnages acceptés dans une campagne mais non présents dans un lieu
 */

require_once 'includes/functions.php';
require_once 'classes/init.php';

requireLogin();

if (!User::isDMOrAdmin()) {
    header('Location: index.php');
    exit();
}

$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : null;
$fix = isset($_GET['fix']) && $_GET['fix'] === '1';

$pdo = getPDO();

// Récupérer toutes les campagnes
$campaigns = [];
$stmt = $pdo->query("SELECT id, title FROM campaigns ORDER BY title");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orphan_characters = [];
$campaign_title = '';

if ($campaign_id) {
    // Récupérer le titre de la campagne
    $stmt = $pdo->prepare("SELECT title FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    $campaign_title = $campaign ? $campaign['title'] : 'Inconnue';
    
    // Trouver les personnages acceptés dans la campagne mais non présents dans un lieu
    $stmt = $pdo->prepare("
        SELECT 
            ca.character_id,
            ca.player_id,
            c.name as character_name,
            u.username as player_username,
            c.id as character_id
        FROM campaign_applications ca
        INNER JOIN characters c ON ca.character_id = c.id
        INNER JOIN users u ON ca.player_id = u.id
        WHERE ca.campaign_id = ? 
        AND ca.status = 'approved'
        AND ca.character_id IS NOT NULL
        AND ca.character_id NOT IN (
            SELECT pp.character_id 
            FROM place_players pp
            INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id
            WHERE pc.campaign_id = ? AND pp.character_id IS NOT NULL
        )
        ORDER BY u.username, c.name
    ");
    $stmt->execute([$campaign_id, $campaign_id]);
    $orphan_characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si on demande de corriger, ajouter ces personnages au premier lieu de la campagne
    if ($fix && !empty($orphan_characters)) {
        // Récupérer le premier lieu de la campagne
        $stmt = $pdo->prepare("
            SELECT p.id, p.title
            FROM places p
            INNER JOIN place_campaigns pc ON p.id = pc.place_id
            WHERE pc.campaign_id = ?
            ORDER BY p.id ASC
            LIMIT 1
        ");
        $stmt->execute([$campaign_id]);
        $first_place = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($first_place) {
            $added_count = 0;
            $errors = [];
            
            foreach ($orphan_characters as $char) {
                try {
                    // Vérifier si le joueur n'est pas déjà dans un lieu de la campagne
                    $stmt = $pdo->prepare("
                        SELECT 1 FROM place_players pp
                        INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id
                        WHERE pp.player_id = ? AND pc.campaign_id = ?
                    ");
                    $stmt->execute([$char['player_id'], $campaign_id]);
                    
                    if (!$stmt->fetch()) {
                        // Ajouter le joueur au premier lieu
                        $stmt = $pdo->prepare("
                            INSERT INTO place_players (place_id, player_id, character_id)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$first_place['id'], $char['player_id'], $char['character_id']]);
                        $added_count++;
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erreur pour {$char['character_name']}: " . $e->getMessage();
                    error_log("Erreur lors de l'ajout de {$char['character_name']}: " . $e->getMessage());
                }
            }
            
            $success_message = "$added_count personnage(s) ajouté(s) au lieu '{$first_place['title']}'.";
            if (!empty($errors)) {
                $error_message = "Erreurs: " . implode(', ', $errors);
            }
            
            // Recharger la liste
            header('Location: check_orphan_characters.php?campaign_id=' . $campaign_id);
            exit();
        } else {
            $error_message = "Aucun lieu trouvé dans cette campagne. Veuillez d'abord créer un lieu.";
        }
    }
}

$page_title = "Vérification des personnages orphelins";
$current_page = "admin";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h1><i class="fas fa-search me-2"></i>Vérification des personnages orphelins</h1>
        <p class="text-muted">Personnages acceptés dans une campagne mais non présents dans un lieu</p>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="campaign_id" class="form-label">Sélectionner une campagne</label>
                            <select class="form-select" id="campaign_id" name="campaign_id" required>
                                <option value="">-- Sélectionner une campagne --</option>
                                <?php foreach ($campaigns as $camp): ?>
                                    <option value="<?php echo $camp['id']; ?>" <?php echo ($campaign_id == $camp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($camp['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Vérifier
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if ($campaign_id && !empty($orphan_characters)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong><?php echo count($orphan_characters); ?> personnage(s)</strong> accepté(s) dans la campagne 
                        <strong>"<?php echo htmlspecialchars($campaign_title); ?>"</strong> mais non présent(s) dans un lieu.
                    </div>
                    
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Joueur</th>
                                <th>Personnage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orphan_characters as $char): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($char['player_username']); ?></td>
                                    <td><?php echo htmlspecialchars($char['character_name']); ?></td>
                                    <td>
                                        <a href="view_character.php?id=<?php echo $char['character_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-eye me-1"></i>Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="mt-3">
                        <a href="?campaign_id=<?php echo $campaign_id; ?>&fix=1" 
                           class="btn btn-warning"
                           onclick="return confirm('Êtes-vous sûr de vouloir ajouter tous ces personnages au premier lieu de la campagne ?');">
                            <i class="fas fa-tools me-1"></i>Corriger automatiquement
                        </a>
                    </div>
                <?php elseif ($campaign_id && empty($orphan_characters)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Aucun personnage orphelin trouvé dans la campagne "<?php echo htmlspecialchars($campaign_title); ?>".
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

