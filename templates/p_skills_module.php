<!-- Onglet Compétences -->
<div class="tab-pane fade" id="skills" role="tabpanel" aria-labelledby="skills-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-dice me-2"></i>Compétences</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Compétences maîtrisées</h5>
                    <?php if (!empty($allSkills)): ?>
                        <div class="skills-list">
                            <?php foreach ($allSkills as $skill): ?>
                                <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars($skill); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune compétence maîtrisée.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Outils et instruments</h5>
                    <?php if (!empty($allTools)): ?>
                        <div class="tools-list">
                            <?php foreach ($allTools as $tool): ?>
                                <span class="badge bg-secondary me-2 mb-2"><?php echo htmlspecialchars($tool); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun outil ou instrument maîtrisé.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
