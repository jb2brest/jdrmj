<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Équipement du PNJ";
$current_page = "view_npc_equipment";


requireLogin();

if (!isset($_GET['id']) || !isset($_GET['place_id'])) {
    header('Location: index.php');
    exit();
}

$npc_id = (int)$_GET['id'];
$place_id = (int)$_GET['place_id'];

// DEBUG: Logs pour déboguer l'accès
error_log("DEBUG view_npc_equipment.php - Accès avec NPC ID: $npc_id, Scene ID: $place_id");
error_log("DEBUG view_npc_equipment.php - User ID: " . ($_SESSION['user_id'] ?? 'NOT_SET'));

// Vérifier que le MJ a accès à cette lieu
$stmt = $pdo->prepare("SELECT s.*, gs.dm_id FROM places s JOIN game_sessions gs ON s.session_id = gs.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$scene = $stmt->fetch();

if (!$scene || $scene['dm_id'] != $_SESSION['user_id']) {
    error_log("DEBUG view_npc_equipment.php - Accès refusé - Scene: " . ($scene ? 'found' : 'not_found') . ", DM ID: " . ($scene['dm_id'] ?? 'N/A') . ", User ID: " . ($_SESSION['user_id'] ?? 'NOT_SET'));
    header('Location: index.php');
    exit();
}

error_log("DEBUG view_npc_equipment.php - Accès autorisé - DM ID: " . $scene['dm_id'] . ", User ID: " . $_SESSION['user_id']);

// Récupérer les informations du PNJ
$stmt = $pdo->prepare("SELECT * FROM place_npcs WHERE id = ? AND place_id = ? AND monster_id IS NULL");
$stmt->execute([$npc_id, $place_id]);
$npc = $stmt->fetch();

if (!$npc) {
    header('Location: index.php');
    exit();
}

// Récupérer tous les équipements du PNJ
$stmt = $pdo->prepare("SELECT * FROM npc_equipment WHERE npc_id = ? AND place_id = ? ORDER BY obtained_at DESC");
$stmt->execute([$npc_id, $place_id]);
$allEquipment = $stmt->fetchAll();

error_log("DEBUG view_npc_equipment.php - Équipements récupérés: " . count($allEquipment) . " pour PNJ ID: $npc_id");

// Séparer les objets magiques et les poisons
$magicalEquipment = [];
$npcPoisons = [];

foreach ($allEquipment as $item) {
    // Vérifier d'abord si c'est un objet magique
    $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
    $stmt->execute([$item['magical_item_id']]);
    $magical_info = $stmt->fetch();
    
    if ($magical_info) {
        // C'est un objet magique
        $item['magical_item_nom'] = $magical_info['nom'];
        $item['magical_item_type'] = $magical_info['type'];
        $item['magical_item_description'] = $magical_info['description'];
        $item['magical_item_source'] = $magical_info['source'];
        $magicalEquipment[] = $item;
    } else {
        // Vérifier si c'est un poison
        $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
        $stmt->execute([$item['magical_item_id']]);
        $poison_info = $stmt->fetch();
        
        if ($poison_info) {
            // C'est un poison
            $item['poison_nom'] = $poison_info['nom'];
            $item['poison_type'] = $poison_info['type'];
            $item['poison_description'] = $poison_info['description'];
            $item['poison_source'] = $poison_info['source'];
            $npcPoisons[] = $item;
        }
    }
}

error_log("DEBUG view_npc_equipment.php - Séparation terminée - Objets magiques: " . count($magicalEquipment) . ", Poisons: " . count($npcPoisons));

// Traitements POST pour gérer l'équipement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_equipped':
                $equipment_id = (int)$_POST['equipment_id'];
                $new_status = (int)$_POST['new_status'];
                
                $stmt = $pdo->prepare("UPDATE npc_equipment SET equipped = ? WHERE id = ? AND npc_id = ? AND place_id = ?");
                $stmt->execute([$new_status, $equipment_id, $npc_id, $place_id]);
                
                $success_message = "Statut d'équipement mis à jour.";
                
                // Recharger tous les équipements
                $stmt = $pdo->prepare("SELECT * FROM npc_equipment WHERE npc_id = ? AND place_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$npc_id, $place_id]);
                $allEquipment = $stmt->fetchAll();

                // Séparer les objets magiques et les poisons
                $magicalEquipment = [];
                $npcPoisons = [];

                foreach ($allEquipment as $item) {
                    // Vérifier d'abord si c'est un objet magique
                    $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
                    $stmt->execute([$item['magical_item_id']]);
                    $magical_info = $stmt->fetch();
                    
                    if ($magical_info) {
                        // C'est un objet magique
                        $item['magical_item_nom'] = $magical_info['nom'];
                        $item['magical_item_type'] = $magical_info['type'];
                        $item['magical_item_description'] = $magical_info['description'];
                        $item['magical_item_source'] = $magical_info['source'];
                        $magicalEquipment[] = $item;
                    } else {
                        // Vérifier si c'est un poison
                        $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
                        $stmt->execute([$item['magical_item_id']]);
                        $poison_info = $stmt->fetch();
                        
                        if ($poison_info) {
                            // C'est un poison
                            $item['poison_nom'] = $poison_info['nom'];
                            $item['poison_type'] = $poison_info['type'];
                            $item['poison_description'] = $poison_info['description'];
                            $item['poison_source'] = $poison_info['source'];
                            $npcPoisons[] = $item;
                        }
                    }
                }
                break;
                
            case 'remove_item':
                $equipment_id = (int)$_POST['equipment_id'];
                
                $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ? AND npc_id = ? AND place_id = ?");
                $stmt->execute([$equipment_id, $npc_id, $place_id]);
                
                $success_message = "Objet retiré de l'équipement.";
                
                // Recharger tous les équipements
                $stmt = $pdo->prepare("SELECT * FROM npc_equipment WHERE npc_id = ? AND place_id = ? ORDER BY obtained_at DESC");
                $stmt->execute([$npc_id, $place_id]);
                $allEquipment = $stmt->fetchAll();

                // Séparer les objets magiques et les poisons
                $magicalEquipment = [];
                $npcPoisons = [];

                foreach ($allEquipment as $item) {
                    // Vérifier d'abord si c'est un objet magique
                    $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
                    $stmt->execute([$item['magical_item_id']]);
                    $magical_info = $stmt->fetch();
                    
                    if ($magical_info) {
                        // C'est un objet magique
                        $item['magical_item_nom'] = $magical_info['nom'];
                        $item['magical_item_type'] = $magical_info['type'];
                        $item['magical_item_description'] = $magical_info['description'];
                        $item['magical_item_source'] = $magical_info['source'];
                        $magicalEquipment[] = $item;
                    } else {
                        // Vérifier si c'est un poison
                        $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
                        $stmt->execute([$item['magical_item_id']]);
                        $poison_info = $stmt->fetch();
                        
                        if ($poison_info) {
                            // C'est un poison
                            $item['poison_nom'] = $poison_info['nom'];
                            $item['poison_type'] = $poison_info['type'];
                            $item['poison_description'] = $poison_info['description'];
                            $item['poison_source'] = $poison_info['source'];
                            $npcPoisons[] = $item;
                        }
                    }
                }
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
    <title>Équipement: <?php echo htmlspecialchars($npc['name']); ?> - JDR 4 MJ</title>
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
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Informations du PNJ</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($npc['name']); ?></h6>
                        <?php if ($npc['description']): ?>
                            <p class="card-text">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($npc['description'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Objets Magiques -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-gem me-2"></i>Objets Magiques</h5>
                        <span class="badge bg-primary"><?php echo count($magicalEquipment); ?> objet(s)</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($magicalEquipment)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-gem fa-2x mb-3"></i>
                                <p>Aucun objet magique dans l'équipement.</p>
                                <p class="small">Les objets magiques attribués par le MJ apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($magicalEquipment as $item): ?>
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

                <!-- Poisons -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-skull-crossbones me-2"></i>Poisons</h5>
                        <span class="badge bg-danger"><?php echo count($npcPoisons); ?> poison(s)</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($npcPoisons)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-skull-crossbones fa-2x mb-3"></i>
                                <p>Aucun poison dans l'équipement.</p>
                                <p class="small">Les poisons attribués par le MJ apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($npcPoisons as $poison): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border-danger">
                                            <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-skull-crossbones me-1"></i>
                                                    <?php echo htmlspecialchars($poison['poison_nom']); ?>
                                                </h6>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-light" 
                                                            onclick="removeItem(<?php echo $poison['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <strong>Type:</strong> <?php echo htmlspecialchars($poison['poison_type']); ?><br>
                                                    <strong>Source:</strong> <?php echo htmlspecialchars($poison['poison_source']); ?><br>
                                                    <strong>Quantité:</strong> <?php echo (int)$poison['quantity']; ?><br>
                                                    <strong>Obtenu:</strong> <?php echo date('d/m/Y H:i', strtotime($poison['obtained_at'])); ?><br>
                                                    <strong>Provenance:</strong> <?php echo htmlspecialchars($poison['obtained_from']); ?>
                                                </p>
                                                <?php if (!empty($poison['poison_description'])): ?>
                                                    <p class="card-text">
                                                        <strong>Description:</strong><br>
                                                        <?php echo nl2br(htmlspecialchars($poison['poison_description'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($poison['notes'])): ?>
                                                    <p class="card-text">
                                                        <strong>Notes:</strong><br>
                                                        <em><?php echo nl2br(htmlspecialchars($poison['notes'])); ?></em>
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
