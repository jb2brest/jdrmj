<?php
/**
 * Template pour le modal de gestion des points de vie
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($npc) || !isset($npc->name) || !isset($npc->hit_points_current) || !isset($npc->hit_points_max)) {
    throw new Exception('Variables $npc, $npc->name, $npc->hit_points_current et $npc->hit_points_max sont requises pour ce template');
}
?>

<div class="modal fade" id="hpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-heart me-2"></i>
                    Gestion des Points de Vie - <?php echo htmlspecialchars($npc->name); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Barre de Points de Vie -->
                <div class="mb-4">
                    <h6>Points de Vie Actuels</h6>
                    <?php
                    $current_hp = $npc->hit_points_current;
                    $max_hp = $npc->hit_points_max;
                    $hp_percentage = $max_hp > 0 ? ($current_hp / $max_hp) * 100 : 100;
                    $hp_class = $hp_percentage > 50 ? 'bg-success' : ($hp_percentage > 25 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <div class="progress mb-2 progress-bar-custom">
                        <div class="progress-bar <?php echo $hp_class; ?>" role="progressbar" style="width: <?php echo $hp_percentage; ?>%">
                            <?php echo $current_hp; ?>/<?php echo $max_hp; ?>
                        </div>
                    </div>
                    <small class="text-muted"><?php echo round($hp_percentage, 1); ?>% des points de vie restants</small>
                </div>

                <!-- Actions Rapides -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-sword text-danger me-2"></i>Infliger des Dégâts</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="1" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-1</button>
                            <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="5" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-5</button>
                            <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="10" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-10</button>
                            <button class="btn btn-outline-danger btn-sm" data-action="damage" data-amount="20" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">-20</button>
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="hp_action" value="damage">
                            <input type="number" name="damage" class="form-control form-control-sm" placeholder="Dégâts" min="1" required>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-minus"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-heart text-success me-2"></i>Appliquer des Soins</h6>
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="1" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+1</button>
                            <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="5" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+5</button>
                            <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="10" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+10</button>
                            <button class="btn btn-outline-success btn-sm" data-action="heal" data-amount="20" data-npc-name="<?php echo htmlspecialchars($npc->name); ?>">+20</button>
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="hp_action" value="heal">
                            <input type="number" name="healing" class="form-control form-control-sm" placeholder="Soins" min="1" required>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Actions Avancées -->
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-edit text-warning me-2"></i>Modifier Directement</h6>
                        <form method="POST">
                            <input type="hidden" name="hp_action" value="update_hp">
                            <input type="hidden" name="max_hp" value="<?php echo $npc->hit_points_max; ?>">
                            <div class="d-flex gap-2">
                                <input type="number" name="current_hp" class="form-control form-control-sm" 
                                       value="<?php echo $npc->hit_points_current; ?>" 
                                       min="0" max="<?php echo $npc->hit_points_max; ?>" required>
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <small class="text-muted">Maximum : <?php echo $npc->hit_points_max; ?> PV</small>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-redo text-info me-2"></i>Réinitialiser</h6>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="hp_action" value="reset_hp">
                            <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Réinitialiser les points de vie au maximum ?')">
                                <i class="fas fa-redo me-2"></i>
                                Remettre au Maximum
                            </button>
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
