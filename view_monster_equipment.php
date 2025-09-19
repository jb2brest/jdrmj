<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Équipement du Monstre";
$current_page = "view_monster_equipment";


requireLogin();

if (!isset($_GET['id']) || !isset($_GET['place_id'])) {
    header('Location: index.php');
    exit();
}

$monster_id = (int)$_GET['id'];
$place_id = (int)$_GET['place_id'];

// Vérifier que le MJ a accès à cette lieu
$stmt = $pdo->prepare("SELECT s.*, gs.dm_id FROM places s JOIN game_sessions gs ON s.session_id = gs.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

if (!$scene || $scene['dm_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

// Récupérer les informations du monstre
$stmt = $pdo->prepare("
    SELECT sn.*, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class 
    FROM place_npcs sn 
    JOIN dnd_monsters m ON sn.monster_id = m.id 
    WHERE sn.id = ? AND sn.place_id = ? AND sn.monster_id IS NOT NULL
");
$stmt->execute([$monster_id, $place_id]);
$monster = $stmt->fetch();

if (!$monster) {
    header('Location: index.php');
    exit();
}

// Récupérer l'équipement du monstre
$stmt = $pdo->prepare("SELECT * FROM monster_equipment WHERE monster_id = ? AND place_id = ? ORDER BY obtained_at DESC");
$stmt->execute([$monster_id, $place_id]);
$equipment = $stmt->fetchAll();

// Traitements POST pour gérer l'équipement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_equipped':
                $equipment_id = (int)$_POST['equipment_id'];
                $new_status = (int)$_POST['new_status'];
                
                $stmt = $pdo->prepare("UPDATE monster_equipment SET equipped = ? WHERE id = ? AND monster_id = ? AND place_id = ?");
                $stmt->execute([$new_status, $equipment_id, $monster_id, $place_id]);
                
                $success_message = "Statut d'équipement mis à jour.";
                
                // Recharger l'équipement
                $stmt = $pdo->prepare("SELECT * FROM monster_equipment WHERE monster_id = ? AND place_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$monster_id, $place_id]);
                $equipment = $stmt->fetchAll();
                break;
                
            case 'remove_item':
                $equipment_id = (int)$_POST['equipment_id'];
                
                $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE id = ? AND monster_id = ? AND place_id = ?");
                $stmt->execute([$equipment_id, $monster_id, $place_id]);
                
                $success_message = "Objet retiré de l'équipement.";
                
                // Recharger l'équipement
                $stmt = $pdo->prepare("SELECT * FROM monster_equipment WHERE monster_id = ? AND place_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$monster_id, $place_id]);
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
    <title>Équipement: <?php echo htmlspecialchars($monster['name']); ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dragon me-2"></i>Informations du Monstre</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($monster['name']); ?></h6>
                        <p class="card-text">
                            <strong>Type:</strong> <?php echo htmlspecialchars($monster['type']); ?><br>
                            <strong>Taille:</strong> <?php echo htmlspecialchars($monster['size']); ?><br>
                            <strong>Défi:</strong> <?php echo htmlspecialchars($monster['challenge_rating']); ?><br>
                            <strong>Classe d'Armure:</strong> <?php echo htmlspecialchars($monster['armor_class']); ?><br>
                            <strong>Points de Vie:</strong> <?php echo htmlspecialchars($monster['hit_points']); ?>
                        </p>
                        <?php if ($monster['description']): ?>
                            <p class="card-text">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($monster['description'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-gem me-2"></i>Équipement et Objets Magiques</h5>
                        <span class="badge bg-danger"><?php echo count($equipment); ?> objet(s)</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($equipment)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-gem fa-3x mb-3"></i>
                                <p>Aucun objet dans l'équipement.</p>
                                <p class="small">Les objets magiques attribués par le MJ apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
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










