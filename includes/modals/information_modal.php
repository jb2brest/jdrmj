<?php
// Modal pour créer/modifier une information
$is_editing = isset($editing_information) && $editing_information;
$modal_id = $is_editing ? 'editInformationModal' : 'createInformationModal';
$modal_title = $is_editing ? 'Modifier l\'Information' : 'Créer une Nouvelle Information';

if ($is_editing) {
    $info_accesses = $editing_information->getAccesses();
    $info_player_accesses = [];
    $info_npc_accesses = [];
    $info_monster_accesses = [];
    $info_group_accesses = []; // Format: ['groupe_id' => [niveau1, niveau2, ...]]
    foreach ($info_accesses as $access) {
        if ($access['access_type'] === 'player') {
            $info_player_accesses[] = $access['player_id'];
        } elseif ($access['access_type'] === 'npc') {
            $info_npc_accesses[] = $access['npc_id'];
        } elseif ($access['access_type'] === 'monster') {
            $info_monster_accesses[] = $access['npc_id'];
        } elseif ($access['access_type'] === 'group' && isset($access['niveau'])) {
            $groupe_id = $access['groupe_id'];
            if (!isset($info_group_accesses[$groupe_id])) {
                $info_group_accesses[$groupe_id] = [];
            }
            $info_group_accesses[$groupe_id][] = (int)$access['niveau'];
        }
    }
}
?>
<div class="modal fade" id="<?php echo $modal_id; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php if (isset($_GET['from_thematique'])): ?>
                    <input type="hidden" name="from_thematique" value="<?php echo (int)$_GET['from_thematique']; ?>">
                <?php endif; ?>
                <div id="fromThematiqueContainer_<?php echo $modal_id; ?>"></div>
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars($modal_title); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?php echo $is_editing ? 'update_information' : 'create_information'; ?>">
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="information_id" value="<?php echo $editing_information->id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="information_titre" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="information_titre" name="titre" 
                               value="<?php echo $is_editing ? htmlspecialchars($editing_information->titre) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="information_description" class="form-label">Description</label>
                        <textarea class="form-control" id="information_description" name="description" rows="4"><?php echo $is_editing ? htmlspecialchars($editing_information->description) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="information_niveau_confidentialite" class="form-label">Niveau de confidentialité *</label>
                            <select class="form-select" id="information_niveau_confidentialite" name="niveau_confidentialite" required>
                                <?php foreach (Information::NIVEAUX as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo ($is_editing && $editing_information->niveau_confidentialite === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="information_statut" class="form-label">Statut *</label>
                            <select class="form-select" id="information_statut" name="statut" required>
                                <?php foreach (Information::STATUTS as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo ($is_editing && $editing_information->statut === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="information_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="information_image" name="image" accept="image/*">
                        <?php if ($is_editing && $editing_information->image_path): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editing_information->image_path); ?>" 
                                     alt="Image actuelle" style="max-width: 200px; max-height: 200px; border-radius: 5px;">
                                <p class="text-muted small mt-1">Image actuelle</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Accès</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Accès Joueurs</label>
                        <?php if (empty($characters)): ?>
                            <p class="text-muted">Aucun personnage joueur disponible</p>
                        <?php else: ?>
                            <div class="border p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($characters as $character): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="info_player_<?php echo $character->id; ?>" 
                                               name="player_access[]" 
                                               value="<?php echo $character->id; ?>"
                                               <?php echo ($is_editing && in_array($character->id, $info_player_accesses ?? [])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="info_player_<?php echo $character->id; ?>">
                                            <?php echo htmlspecialchars($character->name); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Accès PNJ</label>
                        <?php if (empty($npcs)): ?>
                            <p class="text-muted">Aucun PNJ disponible</p>
                        <?php else: ?>
                            <div class="border p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($npcs as $npc): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="info_npc_<?php echo $npc['id']; ?>" 
                                               name="npc_access[]" 
                                               value="<?php echo $npc['id']; ?>"
                                               <?php echo ($is_editing && in_array($npc['id'], $info_npc_accesses ?? [])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="info_npc_<?php echo $npc['id']; ?>">
                                            <?php echo htmlspecialchars($npc['name']); ?> 
                                            <small class="text-muted">(<?php echo htmlspecialchars($npc['place_name']); ?>)</small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Accès Monstres</label>
                        <?php if (empty($monsters)): ?>
                            <p class="text-muted">Aucun monstre disponible</p>
                        <?php else: ?>
                            <div class="border p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($monsters as $monster): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="info_monster_<?php echo $monster['id']; ?>" 
                                               name="monster_access[]" 
                                               value="<?php echo $monster['id']; ?>"
                                               <?php echo ($is_editing && in_array($monster['id'], $info_monster_accesses ?? [])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="info_monster_<?php echo $monster['id']; ?>">
                                            <?php echo htmlspecialchars($monster['name']); ?> 
                                            <small class="text-muted">(<?php echo htmlspecialchars($monster['place_name']); ?>)</small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Accès Groupes</label>
                        <?php if (empty($groupes)): ?>
                            <p class="text-muted">Aucun groupe disponible</p>
                        <?php else: ?>
                            <div class="border p-2" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($groupes as $groupe): 
                                    $selected_niveaux = ($is_editing && isset($info_group_accesses[$groupe['id']])) ? $info_group_accesses[$groupe['id']] : [];
                                    $has_access = !empty($selected_niveaux);
                                ?>
                                    <div class="mb-3 border-bottom pb-2">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input group-checkbox" type="checkbox" 
                                                   id="info_group_<?php echo $groupe['id']; ?>" 
                                                   data-group-id="<?php echo $groupe['id']; ?>"
                                                   <?php echo $has_access ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="info_group_<?php echo $groupe['id']; ?>">
                                                <?php echo htmlspecialchars($groupe['name']); ?>
                                            </label>
                                        </div>
                                        <div class="ms-4" id="niveau_group_<?php echo $groupe['id']; ?>" style="display: <?php echo $has_access ? 'block' : 'none'; ?>;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label small mb-0">Niveaux hiérarchiques (1 = dirigeant)</label>
                                                <button type="button" class="btn btn-sm btn-outline-secondary toggle-all-levels" 
                                                        data-group-id="<?php echo $groupe['id']; ?>"
                                                        title="Cocher/Décocher tous les niveaux">
                                                    Tous
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php 
                                                $max_levels = isset($groupe['max_hierarchy_levels']) ? (int)$groupe['max_hierarchy_levels'] : 5;
                                                for ($niveau = 1; $niveau <= $max_levels; $niveau++): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input group-level-checkbox" 
                                                               type="checkbox" 
                                                               id="info_group_<?php echo $groupe['id']; ?>_niveau_<?php echo $niveau; ?>" 
                                                               name="group_access[]" 
                                                               value="<?php echo $groupe['id']; ?>_<?php echo $niveau; ?>"
                                                               data-group-id="<?php echo $groupe['id']; ?>"
                                                               <?php echo in_array($niveau, $selected_niveaux) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small" for="info_group_<?php echo $groupe['id']; ?>_niveau_<?php echo $niveau; ?>">
                                                            Niveau <?php echo $niveau; ?>
                                                        </label>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php 
                    $from_thematique = isset($_GET['from_thematique']) ? (int)$_GET['from_thematique'] : null;
                    if ($is_editing): ?>
                        <a href="<?php echo $from_thematique ? 'view_thematique.php?id=' . $from_thematique : 'thematiques.php'; ?>" class="btn btn-secondary">Annuler</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $is_editing ? 'Enregistrer' : 'Créer'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gérer l'affichage des cases à cocher de niveaux pour les groupes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.group-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var niveauDiv = document.getElementById('niveau_group_' + this.dataset.groupId);
            if (niveauDiv) {
                niveauDiv.style.display = this.checked ? 'block' : 'none';
                // Si on décoche le groupe, décocher aussi tous les niveaux
                if (!this.checked) {
                    var levelCheckboxes = niveauDiv.querySelectorAll('.group-level-checkbox');
                    levelCheckboxes.forEach(function(levelCb) {
                        levelCb.checked = false;
                    });
                }
            }
        });
    });
    
    // Gérer la case principale groupe : se cocher si au moins un niveau est coché
    document.querySelectorAll('.group-level-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var groupId = this.dataset.groupId;
            var groupCheckbox = document.getElementById('info_group_' + groupId);
            var levelCheckboxes = document.querySelectorAll('.group-level-checkbox[data-group-id="' + groupId + '"]');
            var hasChecked = Array.from(levelCheckboxes).some(function(cb) { return cb.checked; });
            
            if (groupCheckbox) {
                groupCheckbox.checked = hasChecked;
                var niveauDiv = document.getElementById('niveau_group_' + groupId);
                if (niveauDiv) {
                    niveauDiv.style.display = hasChecked ? 'block' : 'none';
                }
            }
        });
    });
    
    // Gérer le bouton "Tous" pour cocher/décocher tous les niveaux d'un groupe
    document.querySelectorAll('.toggle-all-levels').forEach(function(button) {
        button.addEventListener('click', function() {
            var groupId = this.dataset.groupId;
            var levelCheckboxes = document.querySelectorAll('.group-level-checkbox[data-group-id="' + groupId + '"]');
            var allChecked = Array.from(levelCheckboxes).every(function(cb) { return cb.checked; });
            
            // Si tous sont cochés, décocher tous. Sinon, cocher tous.
            levelCheckboxes.forEach(function(cb) {
                cb.checked = !allChecked;
                // Déclencher l'événement change pour mettre à jour la case principale
                cb.dispatchEvent(new Event('change'));
            });
        });
    });
});
</script>


