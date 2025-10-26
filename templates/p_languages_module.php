<!-- Onglet Langues -->
<div class="tab-pane fade" id="languages" role="tabpanel" aria-labelledby="languages-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-language me-2"></i>Langues</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Langues parlées</h5>
                    <?php if (!empty($characterLanguages)): ?>
                        <div class="languages-list">
                            <?php foreach ($characterLanguages as $language): ?>
                                <span class="badge bg-info me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune langue supplémentaire.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Langues de l'historique</h5>
                    <?php if (!empty($backgroundLanguages)): ?>
                        <div class="languages-list">
                            <?php foreach ($backgroundLanguages as $language): ?>
                                <span class="badge bg-warning me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune langue de l'historique.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
