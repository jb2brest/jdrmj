<?php
/**
 * Template pour le modal de gestion des points de vie
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($name)) {
    throw new Exception('Variable $name est requise pour ce template');
}
if (!isset($hit_points_current)) {
    throw new Exception('Variable $hit_points_current est requise pour ce template');
}
if (!isset($hit_points_max)) {
    throw new Exception('Variable $hit_points_max est requise pour ce template');
}
if (!isset($target_id)) {
    throw new Exception('Variable $target_id est requise pour ce template');
}
if (!isset($target_type)) {
    throw new Exception('Variable $target_type est requise pour ce template');
}
?>

<div class="modal fade" id="hpModal" tabindex="-1" aria-labelledby="hpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hpModalLabel">
                    <i class="fas fa-heart me-2"></i>
                    Gestion des Points de Vie - <?php echo htmlspecialchars($name); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <!-- Barre de Points de Vie -->
                <div class="mb-4">
                    <h6>Points de Vie Actuels</h6>
                    <?php
                    $hp_percentage = $hit_points_max > 0 ? ($hit_points_current / $hit_points_max) * 100 : 100;
                    $hp_class = $hp_percentage >= 90 ? 'hp-full' : ($hp_percentage >= 75 ? 'hp-high' : ($hp_percentage >= 50 ? 'hp-medium' : ($hp_percentage >= 25 ? 'hp-low' : 'hp-critical')));
                    ?>
                    <div class="progress mb-2 progress-bar-custom">
                        <div id="hp-progress-bar" class="progress-bar <?php echo $hp_class; ?>" role="progressbar" style="width: <?php echo $hp_percentage; ?>%">
                            <span id="hp-display-text"><?php echo $hit_points_current; ?>/<?php echo $hit_points_max; ?></span>
                        </div>
                    </div>
                    <small class="text-muted"><span id="hp-percentage"><?php echo round($hp_percentage, 1); ?></span>% des points de vie restants</small>
                </div>

                <!-- Actions Rapides -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-sword text-danger me-2"></i>Infliger des Dégâts</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 1)">-1</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 5)">-5</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 10)">-10</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickDamage(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 20)">-20</button>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="number" id="damage_amount" class="form-control form-control-sm" placeholder="Dégâts" min="1" required>
                            <button type="button" class="btn btn-danger btn-sm" onclick="quickDamage(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('damage_amount').value)">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-heart text-success me-2"></i>Appliquer des Soins</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 1)">+1</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 5)">+5</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 10)">+10</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickHeal(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 20)">+20</button>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="number" id="heal_amount" class="form-control form-control-sm" placeholder="Soins" min="1" required>
                            <button type="button" class="btn btn-success btn-sm" onclick="quickHeal(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('heal_amount').value)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Actions Avancées -->
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                        <div class="d-flex gap-2">
                            <input type="number" id="direct_hp_input" class="form-control form-control-sm" 
                                   value="<?php echo $hit_points_current; ?>" 
                                   min="0" max="<?php echo $hit_points_max; ?>" required>
                            <button type="button" class="btn btn-warning btn-sm" onclick="updateHpDirect(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('direct_hp_input').value)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <small class="text-muted">Maximum : <?php echo $hit_points_max; ?> PV</small>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-redo text-info me-2"></i>Réinitialiser</h6>
                        <button type="button" class="btn btn-info btn-sm" onclick="resetHp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>')">
                            <i class="fas fa-redo me-2"></i>
                            Remettre au Maximum
                        </button>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Test de disponibilité des fonctions HP
document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal HP chargé');
    console.log('manageHp disponible:', typeof manageHp !== 'undefined');
    console.log('updateHpModalDisplay disponible:', typeof updateHpModalDisplay !== 'undefined');
    console.log('quickDamage disponible:', typeof quickDamage !== 'undefined');
});
</script>
