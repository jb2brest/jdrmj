<?php
// Module d'affichage des groupes

if (!isset($_POST['target_id']) || !isset($_POST['target_type'])) {
    echo '<div class="alert alert-danger">Paramètres manquants.</div>';
    exit;
}

$target_id = (int)$_POST['target_id'];
$target_type = $_POST['target_type'];

require_once '../classes/init.php';
require_once '../classes/Groupe.php';
require_once '../includes/functions.php';

// Récupérer les groupes du personnage
$groups = Groupe::getGroupMemberships($target_id, $target_type);

// Récupérer tous les groupes pour le sélecteur (pour l'ajout)
$allGroups = Groupe::getAll();

$canEdit = true; // TODO: Affiner les permissions si nécessaire (MJ ou propriétaire)
?>

<div class="row">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-users me-2"></i>Groupes et Organisations</h4>
        
        <?php if ($canEdit): ?>
            <div class="mb-4">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addGroupCollapse">
                    <i class="fas fa-plus me-1"></i>Ajouter au groupe
                </button>
                
                <div class="collapse mt-3" id="addGroupCollapse">
                    <div class="card card-body bg-light">
                        <form id="addToGroupForm" class="row g-3 align-items-end">
                            <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
                            <input type="hidden" name="target_type" value="<?php echo $target_type; ?>">
                            
                            <div class="col-md-5">
                                <label for="group_id" class="form-label">Choisir un groupe</label>
                                <select class="form-select" id="group_id" name="group_id" required>
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($allGroups as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="hierarchy_level" class="form-label">Rang</label>
                                <select class="form-select" id="hierarchy_level" name="hierarchy_level">
                                    <option value="1">Dirigeant</option>
                                    <option value="2" selected>Membre</option>
                                    <option value="3">Recrue</option>
                                    <option value="4">Associé</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($groups)): ?>
            <div class="alert alert-light border text-center text-muted">
                Ce personnage n'appartient à aucun groupe.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($groups as $membership): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-primary">
                                <?php echo htmlspecialchars($membership['groupe_name']); ?>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-medal me-1"></i>
                                <?php 
                                if (!empty($membership['hierarchy_level_title'])) {
                                    echo htmlspecialchars($membership['hierarchy_level_title']);
                                } else {
                                    $ranks = [1 => 'Dirigeant', 2 => 'Membre', 3 => 'Recrue', 4 => 'Associé'];
                                    echo $ranks[$membership['hierarchy_level']] ?? 'Niveau ' . $membership['hierarchy_level'];
                                }
                                ?>
                            </small>
                            <?php if (!empty($membership['comment'])): ?>
                                <small class="d-block text-muted fst-italic mt-1">
                                    "<?php echo htmlspecialchars($membership['comment']); ?>"
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($canEdit): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary btn-edit-rank" 
                                        data-group-id="<?php echo $membership['groupe_id']; ?>" 
                                        data-target-id="<?php echo $target_id; ?>" 
                                        data-target-type="<?php echo htmlspecialchars($target_type, ENT_QUOTES); ?>" 
                                        data-current-rank="<?php echo $membership['hierarchy_level']; ?>" 
                                        title="Modifier le rang">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-remove-group" 
                                        data-group-id="<?php echo $membership['groupe_id']; ?>" 
                                        data-target-id="<?php echo $target_id; ?>" 
                                        data-target-type="<?php echo htmlspecialchars($target_type, ENT_QUOTES); ?>" 
                                        title="Retirer du groupe">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


