<?php
/**
 * Template pour le modal de gestion des points d'expérience
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($npc) || !isset($npc->name) || !isset($npc->level)) {
    throw new Exception('Variables $npc, $npc->name et $npc->level sont requises pour ce template');
}
?>

<div class="modal fade" id="xpModal" tabindex="-1" aria-labelledby="xpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="xpModalLabel">
                    <i class="fas fa-star me-2"></i>
                    Gestion des Points d'Expérience - <?php echo htmlspecialchars($npc->name); ?>
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
                                <strong><?php echo number_format($npc->experience ?? 0); ?> XP</strong>
                                <br>
                                <small class="text-muted">Niveau <?php echo $npc->level; ?></small>
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
                            <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-100" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-100</button>
                            <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-500" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-500</button>
                            <button class="btn btn-outline-danger btn-sm" data-action="xp" data-amount="-1000" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-1000</button>
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="xp_action" value="remove">
                            <input type="number" name="xp_amount" class="form-control form-control-sm" placeholder="Points à retirer" min="1" required>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-minus"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-plus text-success me-2"></i>Ajouter des Points d'Expérience</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="100" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+100</button>
                            <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="500" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+500</button>
                            <button class="btn btn-outline-success btn-sm" data-action="xp" data-amount="1000" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+1000</button>
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="xp_action" value="add">
                            <input type="number" name="xp_amount" class="form-control form-control-sm" placeholder="Points à ajouter" min="1" required>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Action Avancée -->
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                        <form method="POST">
                            <input type="hidden" name="xp_action" value="set">
                            <div class="d-flex gap-2">
                                <input type="number" name="xp_amount" class="form-control" 
                                       value="<?php echo $npc->experience ?? 0; ?>" 
                                       min="0" required>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>
                                    Définir
                                </button>
                            </div>
                            <small class="text-muted">Définir directement le nombre total de points d'expérience</small>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
