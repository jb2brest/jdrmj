<!-- Onglet Info perso. -->
<div class="tab-pane fade" id="personal-info" role="tabpanel" aria-labelledby="personal-info-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-book me-2"></i>Histoire du Personnage</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-heart me-2"></i>Traits de personnalité</h5>
                    <div class="form-text">
                        Comment ce PNJ se comporte-t-il ? Qu'est-ce qui le caractérise ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($personality_traits): ?>
                            <?php echo nl2br(htmlspecialchars($personality_traits)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun trait de personnalité défini</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-star me-2"></i>Idéaux</h5>
                    <div class="form-text">
                        Quelles valeurs sont importantes pour ce PNJ ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($ideals): ?>
                            <?php echo nl2br(htmlspecialchars($ideals)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun idéal défini</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-link me-2"></i>Liens</h5>
                    <div class="form-text">
                        À quoi ou à qui ce PNJ est-il attaché ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($bonds): ?>
                            <?php echo nl2br(htmlspecialchars($bonds)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun lien défini</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Défauts</h5>
                    <div class="form-text">
                        Quelles sont les faiblesses ou les vices de ce PNJ ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($flaws): ?>
                            <?php echo nl2br(htmlspecialchars($flaws)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun lien défini</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
