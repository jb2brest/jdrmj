<!-- Onglet Combat -->
<div class="tab-pane fade show active" id="combat" role="tabpanel" aria-labelledby="combat-tab">
    <div class="p-4">
        <!-- Informations de combat -->
        <div class="info-section mb-4">
            <h4><i class="fas fa-sword me-2"></i>Informations de Combat</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box text-center">
                        <div class="stat-value"><?php echo ($initiative >= 0 ? '+' : '') . $initiative; ?></div>
                        <div class="stat-label">Initiative</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box text-center">
                        <div class="stat-value"><?php echo $speed; ?></div>
                        <div class="stat-label">Vitesse (pieds)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box text-center">
                        <div class="stat-value">+<?php echo ceil($level / 4) + 1; ?></div>
                        <div class="stat-label">Bonus de maîtrise</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box text-center">
                        <div class="stat-value"><?php echo $armorClass; ?></div>
                        <div class="stat-label">Classe d'Armure</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classe d'armure détaillée -->
        <div class="info-section mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                        <h5><i class="fas fa-shield-alt me-2"></i>Classe d'armure</h5>
                            <small class="text-muted">
                                <?php if ($equippedArmor): ?>
                                    <strong>Armure:</strong> <?php echo $equippedArmor['name']; ?> (<?php echo $equippedArmor['ac_formula']; ?>)<br>
                                <?php else: ?>
                                    <?php if ($isBarbarian): ?>
                                        <strong>Armure:</strong> Aucune (10 + modificateur de Dextérité + modificateur de Constitution)<br>
                                    <?php else: ?>
                                        <strong>Armure:</strong> Aucune (10 + modificateur de Dextérité)<br>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($equippedShield): ?>
                                    <strong>Bouclier:</strong> <?php echo $equippedShield['name']; ?> (+<?php echo $equippedShield['ac_formula'] ?? '2'; ?>)<br>
                                <?php else: ?>
                                    <strong>Bouclier:</strong> Aucun<br>
                                <?php endif; ?>
                                
                                <strong>Modificateur de Dextérité:</strong> <?php echo ($dexterityModifier >= 0 ? '+' : '') . $dexterityModifier; ?>
                                <?php if ($isBarbarian && !$equippedArmor): ?>
                                    <br><strong>Modificateur de Constitution:</strong> <?php echo ($constitutionModifier >= 0 ? '+' : '') . $constitutionModifier; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-sword me-2"></i>Attaques</h5>
                            <!-- Rages (pour les barbares) -->
                            <?php if ($isBarbarian && $rageData): ?>
                                <h6><i class="fas fa-fire me-2"></i>Gestion des Rages</h6>
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="rage-container">
                                            <div class="rage-symbols">
                                                <?php for ($i = 1; $i <= $rageData['max']; $i++): ?>
                                                    <div class="rage-symbol <?php echo $i <= $rageData['used'] ? 'used' : 'available'; ?>" 
                                                        data-rage="<?php echo $i; ?>" data-npc-id="<?php echo $npc_id; ?>" data-action="toggle"
                                                        title="<?php echo $i <= $rageData['used'] ? 'Rage utilisée' : 'Rage disponible'; ?>">
                                                        <i class="fas fa-fire"></i>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="rage-info mt-2">
                                                <span class="badge bg-info"><?php echo $rageData['available']; ?>/<?php echo $rageData['max']; ?> rages disponibles</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                        <!-- Bouton Grimoire pour les classes de sorts -->
                                <?php 
                                // Classes qui peuvent lancer des sorts
                                $spellcastingClasses = [2, 3, 4, 5, 7, 9, 10, 11]; // Barde, Clerc, Druide, Ensorceleur, Magicien, Occultiste, Paladin, Rôdeur
                                $canCastSpells = in_array($npc->class_id, $spellcastingClasses);
                                ?>
                                <?php if ($canCastSpells): ?>
                                <div class="info-section">
                                    <div class="d-flex justify-content-center">
                                        <a href="grimoire_npc.php?id=<?php echo $npc_id; ?>" class="btn btn-primary btn-lg">
                                            <i class="fas fa-book-open me-2"></i>Grimoire
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <div class="card">
                                <div class="card-body">
                                    <?php if (!empty($characterAttacks)): ?>
                                        <?php foreach ($characterAttacks as $attack): ?>
                                            <div class="row mb-2">
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($attack['name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($attack['damage']); ?>
                                                                <?php if (isset($attack['properties']) && !empty($attack['properties'])): ?>
                                                                    <br><em><?php echo htmlspecialchars($attack['properties']); ?></em>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-<?php echo $attack['type'] === 'À distance' ? 'info' : 'success'; ?> fs-6">
                                                                <?php echo (($attack['bonus'] ?? 0) >= 0 ? '+' : '') . ($attack['bonus'] ?? 0); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($attack !== end($characterAttacks)): ?>
                                                <hr class="my-2">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-hand-paper fa-2x mb-2"></i>
                                            <p>Aucune arme équipée</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
 
        

    </div>
</div>
