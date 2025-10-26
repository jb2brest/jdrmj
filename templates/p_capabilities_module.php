<!-- Onglet Capacités -->
<div class="tab-pane fade" id="capabilities" role="tabpanel" aria-labelledby="capabilities-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-star me-2"></i>Capacités</h4>
            <div class="row">
                <div class="col-md-12">
                    <h5>Toutes les capacités</h5>
                    <?php if (!empty($allCapabilities)): ?>
                        <div class="capabilities-list">
                            <?php foreach ($allCapabilities as $capability): ?>
                                <div class="capability-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="text-primary"><?php echo htmlspecialchars($capability['name'] ?? 'Capacité inconnue'); ?></strong>
                                        <?php if (!empty($capability['type_name'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($capability['type_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($capability['description'])): ?>
                                        <p class="mb-2 text-muted"><?php echo htmlspecialchars($capability['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if (!empty($capability['source'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i>Source: <?php echo htmlspecialchars($capability['source']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($capability['learned_at'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($capability['learned_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-star text-muted fa-3x mb-3"></i>
                            <p class="text-muted">Aucune capacité assignée à ce personnage.</p>
                            <small class="text-muted">Les capacités peuvent être ajoutées via la gestion des personnages.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
