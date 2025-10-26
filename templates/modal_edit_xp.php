<?php
/**
 * Template pour le modal de gestion des points d'expérience
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($npc) || !isset($npc->name) || !isset($npc->level) || !isset($target_id) || !isset($target_type)) {
    throw new Exception('Variables $npc, $npc->name, $npc->level, $target_id et $target_type sont requises pour ce template');
}
?>

<div class="modal fade" id="xpModal" tabindex="-1" aria-labelledby="xpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="xpModalLabel">
                    <i class="fas fa-star me-2"></i>
                    Gestion des Points d'Expérience - <?php echo htmlspecialchars($name); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <!-- Affichage des Points d'Expérience Actuels -->
                <div class="mb-4">
                    <h6>Points d'Expérience Actuels</h6>
                    <div class="alert alert-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong id="xp-display-text"><?php echo number_format($experience ?? 0); ?> XP</strong>
                                <br>
                                <small class="text-muted">Niveau <?php echo $level; ?></small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-star fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Rapides -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-minus text-danger me-2"></i>Retirer des Points d'Expérience</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-danger btn-sm" onclick="quickRemoveXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 100)">-100</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickRemoveXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 500)">-500</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="quickRemoveXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 1000)">-1000</button>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="number" id="remove_xp_amount" class="form-control form-control-sm" placeholder="Points à retirer" min="1" required>
                            <button type="button" class="btn btn-danger btn-sm" onclick="quickRemoveXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('remove_xp_amount').value)">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-plus text-success me-2"></i>Ajouter des Points d'Expérience</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" onclick="quickAddXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 100)">+100</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickAddXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 500)">+500</button>
                            <button class="btn btn-outline-success btn-sm" onclick="quickAddXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', 1000)">+1000</button>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="number" id="add_xp_amount" class="form-control form-control-sm" placeholder="Points à ajouter" min="1" required>
                            <button type="button" class="btn btn-success btn-sm" onclick="quickAddXp(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('add_xp_amount').value)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Avancée -->
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                        <div class="d-flex gap-2">
                            <input type="number" id="direct_xp_input" class="form-control" 
                                   value="<?php echo $npc->experience ?? 0; ?>" 
                                   min="0" required>
                            <button type="button" class="btn btn-warning" onclick="setXpDirect(<?php echo $target_id; ?>, '<?php echo $target_type ?? 'PNJ'; ?>', document.getElementById('direct_xp_input').value)">
                                <i class="fas fa-edit me-2"></i>
                                Définir
                            </button>
                        </div>
                        <small class="text-muted">Définir directement le nombre total de points d'expérience</small>
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
// Test de disponibilité des fonctions XP
document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal XP chargé');
    console.log('manageXp disponible:', typeof manageXp !== 'undefined');
    console.log('quickAddXp disponible:', typeof quickAddXp !== 'undefined');
    console.log('quickRemoveXp disponible:', typeof quickRemoveXp !== 'undefined');
    console.log('setXpDirect disponible:', typeof setXpDirect !== 'undefined');
});
</script>
