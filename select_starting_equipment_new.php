<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/starting_equipment_functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : 0;
$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;

if (!$character_id) {
    header('Location: characters.php');
    exit;
}

// Récupérer les informations du personnage
$stmt = $pdo->prepare("
    SELECT c.*, cl.name as class_name, r.name as race_name, b.name as background_name
    FROM characters c
    LEFT JOIN classes cl ON c.class_id = cl.id
    LEFT JOIN races r ON c.race_id = r.id
    LEFT JOIN backgrounds b ON c.background_id = b.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$character_id, $_SESSION['user_id']]);
$character = $stmt->fetch();

if (!$character) {
    header('Location: characters.php');
    exit;
}

// Vérifier si le personnage a déjà son équipement de départ
if (hasStartingEquipment($character_id)) {
    $_SESSION['message'] = "Ce personnage a déjà son équipement de départ configuré.";
    $_SESSION['message_type'] = 'info';
    header('Location: view_character.php?id=' . $character_id);
    exit;
}

// Récupérer l'équipement de départ
$classEquipment = getClassStartingEquipmentNew($character['class_id']);
$backgroundEquipment = getBackgroundStartingEquipment($character['background_id']);
$raceEquipment = getRaceStartingEquipment($character['race_id']);

// Structurer par groupes
$classGroups = structureStartingEquipmentByGroups($classEquipment);
$backgroundGroups = structureStartingEquipmentByGroups($backgroundEquipment);
$raceGroups = structureStartingEquipmentByGroups($raceEquipment);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_equipment') {
    $equipmentChoices = isset($_POST['equipment_choices']) ? $_POST['equipment_choices'] : [];
    
    // Générer l'équipement final
    $equipmentData = generateFinalEquipmentNew(
        $character['class_id'], 
        $character['background_id'], 
        $character['race_id'], 
        $equipmentChoices
    );
    
    // Ajouter l'équipement au personnage
    if (addStartingEquipmentToCharacterNew($character_id, $equipmentData)) {
        $_SESSION['message'] = "Équipement de départ configuré avec succès!";
        $_SESSION['message_type'] = 'success';
        
        if ($campaign_id) {
            header('Location: view_campaign.php?id=' . $campaign_id);
        } else {
            header('Location: view_character.php?id=' . $character_id);
        }
        exit;
    } else {
        $error = "Erreur lors de la configuration de l'équipement de départ.";
    }
}

$pageTitle = "Équipement de départ - " . $character['name'];
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-shopping-bag"></i> Équipement de départ
                        <small class="text-muted">- <?php echo htmlspecialchars($character['name']); ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Classe:</strong> <?php echo htmlspecialchars($character['class_name']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Race:</strong> <?php echo htmlspecialchars($character['race_name']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Historique:</strong> <?php echo htmlspecialchars($character['background_name']); ?>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="select_equipment">
                        
                        <!-- Équipement de classe -->
                        <?php if (!empty($classGroups)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-shield-alt"></i> Équipement de classe</h5>
                                <?php foreach ($classGroups as $groupId => $group): ?>
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                Groupe <?php echo $groupId; ?>
                                                <span class="badge badge-<?php echo $group['type_choix'] === 'obligatoire' ? 'success' : 'warning'; ?>">
                                                    <?php echo $group['type_choix'] === 'obligatoire' ? 'Obligatoire' : 'À choisir'; ?>
                                                </span>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($group['type_choix'] === 'obligatoire'): ?>
                                                <div class="alert alert-info">
                                                    <strong>Équipement obligatoire:</strong>
                                                    <ul class="mb-0">
                                                        <?php foreach ($group['options'] as $item): ?>
                                                            <li><?php echo htmlspecialchars($item['type']); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-group">
                                                    <label>Choisissez une option:</label>
                                                    <?php foreach ($group['options'] as $item): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="equipment_choices[class][<?php echo $groupId; ?>]" 
                                                                   value="<?php echo $item['option_indice']; ?>" 
                                                                   id="class_<?php echo $groupId; ?>_<?php echo $item['option_indice']; ?>">
                                                            <label class="form-check-label" for="class_<?php echo $groupId; ?>_<?php echo $item['option_indice']; ?>">
                                                                <strong><?php echo $item['option_indice']; ?>)</strong> 
                                                                <?php echo htmlspecialchars($item['type']); ?>
                                                                <?php if ($item['type_id']): ?>
                                                                    <?php 
                                                                    $details = getEquipmentDetails($item['type'], $item['type_id']);
                                                                    if ($details): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($details['name']); ?></small>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Équipement de background -->
                        <?php if (!empty($backgroundGroups)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-user-tie"></i> Équipement d'historique</h5>
                                <div class="alert alert-info">
                                    <strong>Équipement automatique:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($backgroundGroups as $groupId => $group): ?>
                                            <?php foreach ($group['options'] as $item): ?>
                                                <li><?php echo htmlspecialchars($item['type']); ?></li>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Équipement de race -->
                        <?php if (!empty($raceGroups)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-dragon"></i> Équipement de race</h5>
                                <div class="alert alert-info">
                                    <strong>Équipement automatique:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($raceGroups as $groupId => $group): ?>
                                            <?php foreach ($group['options'] as $item): ?>
                                                <li><?php echo htmlspecialchars($item['type']); ?></li>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Confirmer l'équipement de départ
                            </button>
                            <a href="<?php echo $campaign_id ? 'view_campaign.php?id=' . $campaign_id : 'view_character.php?id=' . $character_id; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
