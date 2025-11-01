<?php
/**
 * Module Équipement - Peut être appelé directement ou via AJAX
 */

// Inclure les classes nécessaires
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Si appelé via AJAX, récupérer les données depuis $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = $_POST['target_id'] ?? null;
    $target_type = $_POST['target_type'] ?? null;
} else {
    // Si appelé directement, utiliser les variables globales
    $target_id = $target_id ?? null;
    $target_type = $target_type ?? null;
}

// Charger l'objet personnage selon le type
$pers = null;
if ($target_id && $target_type) {
    if ($target_type === 'PJ') {
        $pers = Character::findById($target_id);
    } elseif ($target_type === 'PNJ') {
        $pers = NPC::findById($target_id);
    }
}

// Si aucun personnage trouvé, afficher un message d'erreur
if (!$pers) {
    echo '<div class="alert alert-danger">Personnage non trouvé</div>';
    return;
}

// Récupérer les données nécessaires via les méthodes d'instance
$equipment = $pers->getEquipment();

// Initialiser $canModifyHP si non défini (cas où le module est appelé directement)
if (!isset($canModifyHP)) {
    $canModifyHP = false; // Valeur par défaut
    
    // Vérifier les permissions seulement si la session est active et le personnage est chargé
    if ($pers && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
        // Vérifier les permissions selon le type de personnage
        if ($target_type === 'PJ' && isset($pers->user_id)) {
            $canModifyHP = ($pers->user_id == $_SESSION['user_id']);
        } elseif ($target_type === 'PNJ' && isset($pers->created_by)) {
            $canModifyHP = ($pers->created_by == $_SESSION['user_id']);
        }
        
        // Les MJ et admins peuvent modifier
        if (!$canModifyHP && class_exists('User') && User::isDMOrAdmin()) {
            $canModifyHP = true;
        }
    }
}
?>

<!-- Onglet Équipement -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-backpack me-2"></i>Équipement</h4>
            
            <!-- Filtres et contrôles -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un objet..." onkeyup="filterTable()">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="typeFilter" onchange="filterTable()">
                        <option value="">Tous les types</option>
                        <option value="weapon">Armes</option>
                        <option value="armor">Armures</option>
                        <option value="shield">Boucliers</option>
                        <option value="magical_item">Objets magiques</option>
                        <option value="poison">Poisons</option>
                        <option value="misc">Divers</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter" onchange="filterTable()">
                        <option value="">Tous</option>
                        <option value="equipped">Équipés</option>
                        <option value="unequipped">Non équipés</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i>Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Tableau d'équipement -->
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="equipmentTable">
                    <thead class="table-dark">
                        <tr>
                            <th onclick="sortTable(0)" style="cursor: pointer;">
                                Nom <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th onclick="sortTable(1)" style="cursor: pointer;">
                                Type <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th onclick="sortTable(2)" style="cursor: pointer;">
                                Description <i class="fas fa-sort ms-1"></i>
                            </th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $item): ?>
                        <tr data-type="<?php echo $item->type; ?>" data-equipped="<?php echo $item->equipped ? 'equipped' : 'unequipped'; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($item->name); ?></strong>
                                <?php if ($item->quantity > 1): ?>
                                    <span class="badge bg-info ms-1">x<?php echo $item->quantity; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($item->type) {
                                        'weapon' => 'danger',
                                        'armor' => 'primary', 
                                        'shield' => 'info',
                                        'magical_item' => 'success',
                                        'poison' => 'warning',
                                        'bag' => 'secondary',
                                        'tool' => 'info',
                                        'clothing' => 'light text-dark',
                                        'consumable' => 'warning',
                                        'misc' => 'secondary',
                                        default => 'light text-dark'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $item->type)); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($item->description); ?></small>
                            </td>
                            <td>
                                <?php if ($item->equipped): ?>
                                    <?php 
                                    $slotName = $item->equipped_slot ? SlotManager::getSlotDisplayName($item->equipped_slot) : 'Équipé';
                                    ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i><?php echo $slotName; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-times-circle me-1"></i>Non équipé
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 300px; white-space: nowrap; overflow: visible;">
                                <?php if ($item->type === 'weapon' || $item->type === 'armor' || $item->type === 'shield'): ?>
                                    <?php if ($item->equipped): ?>
                                        <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $item->id; ?>)"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $item->id; ?>)"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-rock me-1"></i>Équiper
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Non équipable</span>
                                <?php endif; ?>
                                
                                <?php if ($canModifyHP && !str_starts_with($item->id, 'base_')): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#transferModal" 
                                            data-item-id="<?php echo $item->id; ?>"
                                            data-item-name="<?php echo htmlspecialchars($item->name); ?>"
                                            data-item-type="<?php echo htmlspecialchars($item->type); ?>"
                                            data-source="character_equipment"
                                            style="white-space: nowrap; min-width: 80px;">
                                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                                    </button>
                                    <?php if (!$item->equipped): ?>
                                        <button type="button" class="btn btn-outline-warning btn-sm ms-1" 
                                                onclick="dropItem(<?php echo $item->id; ?>, '<?php echo addslashes($item->name); ?>')"
                                                title="Déposer l'objet dans le lieu actuel"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-holding me-1"></i>Déposer
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($equipment)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                Aucun objet trouvé
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
