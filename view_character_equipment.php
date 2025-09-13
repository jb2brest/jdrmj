<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$character_id = (int)$_GET['id'];

// Récupérer les informations du personnage
$stmt = $pdo->prepare("SELECT c.*, u.username, r.name AS race_name, cl.name AS class_name FROM characters c JOIN users u ON c.user_id = u.id LEFT JOIN races r ON c.race_id = r.id LEFT JOIN classes cl ON c.class_id = cl.id WHERE c.id = ?");
$stmt->execute([$character_id]);
$character = $stmt->fetch();

// L'équipement final est déjà stocké dans le champ equipment du personnage
// Plus besoin de parser l'équipement de départ séparément

if (!$character) {
    header('Location: index.php');
    exit();
}

// Vérifier que l'utilisateur peut voir ce personnage
$canView = ($_SESSION['user_id'] === $character['user_id']);

// Si ce n'est pas le propriétaire, vérifier si c'est le MJ de la campagne
if (!$canView && isDM()) {
    // Récupérer l'ID de la campagne du personnage
    $stmt = $pdo->prepare("
        SELECT c.id as campaign_id, c.dm_id 
        FROM characters ch 
        JOIN users u ON ch.user_id = u.id
        JOIN campaign_members cm ON u.id = cm.user_id
        JOIN campaigns c ON cm.campaign_id = c.id
        WHERE ch.id = ?
        LIMIT 1
    ");
    $stmt->execute([$character_id]);
    $campaign_info = $stmt->fetch();
    
    if ($campaign_info && $campaign_info['dm_id'] == $_SESSION['user_id']) {
        $canView = true;
    }
}

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Récupérer l'équipement du personnage
$stmt = $pdo->prepare("SELECT * FROM character_equipment WHERE character_id = ? ORDER BY obtained_at DESC");
$stmt->execute([$character_id]);
$equipment = $stmt->fetchAll();

// Traitements POST pour gérer l'équipement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canView) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_equipped':
                $equipment_id = (int)$_POST['equipment_id'];
                $new_status = (int)$_POST['new_status'];
                
                $stmt = $pdo->prepare("UPDATE character_equipment SET equipped = ? WHERE id = ? AND character_id = ?");
                $stmt->execute([$new_status, $equipment_id, $character_id]);
                
                $success_message = "Statut d'équipement mis à jour.";
                
                // Recharger l'équipement
                $stmt = $pdo->prepare("SELECT * FROM character_equipment WHERE character_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$character_id]);
                $equipment = $stmt->fetchAll();
                break;
                
            case 'remove_item':
                $equipment_id = (int)$_POST['equipment_id'];
                
                $stmt = $pdo->prepare("DELETE FROM character_equipment WHERE id = ? AND character_id = ?");
                $stmt->execute([$equipment_id, $character_id]);
                
                $success_message = "Objet retiré de l'équipement.";
                
                // Recharger l'équipement
                $stmt = $pdo->prepare("SELECT * FROM character_equipment WHERE character_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$character_id]);
                $equipment = $stmt->fetchAll();
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Équipement: <?php echo htmlspecialchars($character['name']); ?> - JDR 4 MJ</title>
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
                    <li class="nav-item"><a class="nav-link" href="view_character.php?id=<?php echo (int)$character_id; ?>">Retour Personnage</a></li>
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
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informations du personnage</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($character['name']); ?></h6>
                        <p class="card-text">
                            <strong>Joueur:</strong> <?php echo htmlspecialchars($character['username']); ?><br>
                            <strong>Race:</strong> <?php echo htmlspecialchars($character['race_name'] ?? 'Non définie'); ?><br>
                            <strong>Classe:</strong> <?php echo htmlspecialchars($character['class_name'] ?? 'Non définie'); ?><br>
                            <strong>Niveau:</strong> <?php echo (int)$character['level']; ?><br>
                            <strong>Points de vie:</strong> <?php echo (int)$character['hit_points_current']; ?>/<?php echo (int)$character['hit_points_max']; ?>
                        </p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-coins me-2"></i>Trésor</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Or:</strong> <?php echo (int)$character['money_gold']; ?> po<br>
                            <strong>Argent:</strong> <?php echo (int)$character['money_silver']; ?> pa<br>
                            <strong>Cuivre:</strong> <?php echo (int)$character['money_copper']; ?> pc
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-gem me-2"></i>Équipement et Objets Magiques</h5>
                        <span class="badge bg-primary"><?php echo count($equipment); ?> objet(s)</span>
                    </div>
                    <div class="card-body">
                        <?php if ($character['equipment']): ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-backpack me-2"></i>Équipement</h6>
                                <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                    <p><?php echo nl2br(htmlspecialchars($character['equipment'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Objets magiques attribués par le MJ -->
                        <?php if (empty($equipment)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-gem fa-3x mb-3"></i>
                                <p>Aucun objet magique attribué.</p>
                                <p class="small">Les objets magiques attribués par le MJ apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h6><i class="fas fa-gem me-2"></i>Objets Magiques</h6>
                                <div class="row">
                                    <?php foreach ($equipment as $item): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 <?php echo $item['equipped'] ? 'border-success' : 'border-secondary'; ?>">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        <?php if ($item['equipped']): ?>
                                                            <i class="fas fa-check-circle text-success me-2"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </h6>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-<?php echo $item['equipped'] ? 'success' : 'secondary'; ?>" 
                                                                onclick="toggleEquipped(<?php echo $item['id']; ?>, <?php echo $item['equipped'] ? 0 : 1; ?>)">
                                                            <i class="fas fa-<?php echo $item['equipped'] ? 'check' : 'times'; ?>"></i>
                                                            <?php echo $item['equipped'] ? 'Équipé' : 'Non équipé'; ?>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="removeItem(<?php echo $item['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        <strong>Type:</strong> <?php echo htmlspecialchars($item['item_type']); ?><br>
                                                        <strong>Source:</strong> <?php echo htmlspecialchars($item['item_source']); ?><br>
                                                        <strong>Quantité:</strong> <?php echo (int)$item['quantity']; ?><br>
                                                        <strong>Obtenu:</strong> <?php echo date('d/m/Y H:i', strtotime($item['obtained_at'])); ?><br>
                                                        <strong>Provenance:</strong> <?php echo htmlspecialchars($item['obtained_from']); ?>
                                                </p>
                                                <?php if (!empty($item['item_description'])): ?>
                                                    <p class="card-text">
                                                        <strong>Description:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($item['notes'])): ?>
                                                    <p class="card-text">
                                                        <strong>Notes:</strong><br>
                                                        <em><?php echo nl2br(htmlspecialchars($item['notes'])); ?></em>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleEquipped(equipmentId, newStatus) {
            if (confirm('Modifier le statut d\'équipement de cet objet ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_equipped">
                    <input type="hidden" name="equipment_id" value="${equipmentId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function removeItem(equipmentId) {
            if (confirm('Êtes-vous sûr de vouloir retirer cet objet de l\'équipement ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="remove_item">
                    <input type="hidden" name="equipment_id" value="${equipmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

