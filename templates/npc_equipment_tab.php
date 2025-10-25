<!-- Onglet Equipement -->
<div class="tab-pane fade" id="equipment" role="tabpanel" aria-labelledby="equipment-tab">
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
                        <?php 
                        // Combiner tous les objets du personnage
                        $allCharacterItems = array_merge($allMagicalEquipment, $allPoisons);
                        
                        // Fonction pour vérifier si un objet existe déjà
                        function itemExists($items, $name, $type) {
                            foreach ($items as $item) {
                                if (($item['item_name'] ?? '') === $name && ($item['item_type'] ?? '') === $type) {
                                    return true;
                                }
                            }
                            return false;
                        }
                        
                        foreach ($allCharacterItems as $item): 
                            // Utiliser les champs standardisés
                            $itemName = $item['item_name'] ?? $item['display_name'] ?? 'Objet inconnu';
                            $itemType = $item['item_type'] ?? $item['object_type'] ?? 'unknown';
                            $displayName = htmlspecialchars($itemName);
                            $typeLabel = ucfirst(str_replace('_', ' ', $itemType));
                        ?>
                        <tr data-type="<?php echo $itemType; ?>" data-equipped="<?php echo ($item['is_equipped'] ?? $item['equipped'] ?? false) ? 'equipped' : 'unequipped'; ?>">
                            <td>
                                <strong><?php echo $displayName; ?></strong>
                                <?php if (($item['quantity'] ?? 1) > 1): ?>
                                    <span class="badge bg-info ms-1">x<?php echo $item['quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($itemType) {
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
                                    <?php echo $typeLabel; ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($item['item_description'] ?? $item['description'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php if ($item['is_equipped'] ?? $item['equipped'] ?? false): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Équipé
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-times-circle me-1"></i>Non équipé
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 300px; white-space: nowrap; overflow: visible;">
                                <?php if ($itemType === 'weapon' || $itemType === 'armor' || $itemType === 'shield'): ?>
                                    <?php if ($item['is_equipped'] ?? $item['equipped'] ?? false): ?>
                                        <button class="btn btn-warning btn-sm" onclick="unequipItem(<?php echo $npc->id; ?>, '<?php echo addslashes($itemName); ?>')"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-paper me-1"></i>Déséquiper
                                        </button>
                                    <?php else: ?>
                                        <?php 
                                        $slot = match($itemType) {
                                            'weapon' => 'main_hand',
                                            'armor' => 'armor',
                                            'shield' => 'off_hand',
                                            default => 'main_hand'
                                        };
                                        ?>
                                        <button class="btn btn-success btn-sm" onclick="equipItem(<?php echo $npc->id; ?>, '<?php echo addslashes($itemName); ?>', '<?php echo $itemType; ?>', '<?php echo $slot; ?>')"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-rock me-1"></i>Équiper
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Non équipable</span>
                                <?php endif; ?>
                                
                                <?php if ($canModifyHP && !str_starts_with($item['id'] ?? '', 'base_')): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#transferModal" 
                                            data-item-id="<?php echo $item['id'] ?? ''; ?>"
                                            data-item-name="<?php echo htmlspecialchars($itemName); ?>"
                                            data-item-type="<?php echo htmlspecialchars($itemType); ?>"
                                            data-source="character_equipment"
                                            style="white-space: nowrap; min-width: 80px;">
                                        <i class="fas fa-exchange-alt me-1"></i>Transférer
                                    </button>
                                    <?php if (!($item['is_equipped'] ?? $item['equipped'] ?? false)): ?>
                                        <button type="button" class="btn btn-outline-warning btn-sm ms-1" 
                                                onclick="dropItem(<?php echo $item['id'] ?? 0; ?>, '<?php echo addslashes($item['item_name'] ?? ''); ?>')"
                                                title="Déposer l'objet dans le lieu actuel"
                                                style="white-space: nowrap; min-width: 80px;">
                                            <i class="fas fa-hand-holding me-1"></i>Déposer
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($allCharacterItems)): ?>
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
