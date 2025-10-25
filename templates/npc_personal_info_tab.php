<!-- Onglet Info perso. -->
<div class="tab-pane fade" id="personal-info" role="tabpanel" aria-labelledby="personal-info-tab">
    <div class="p-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book me-2"></i>Histoire du Personnage</h3>
                <p class="mb-0 text-muted">L'histoire, la personnalité et les traits de ce PNJ</p>
            </div>
            <div class="card-body">
                <?php if ($npc->personality_traits || $npc->ideals || $npc->bonds || $npc->flaws): ?>
                    <!-- Traits de personnalité -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-heart me-2"></i>Traits de personnalité</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Les traits de personnalité de ce PNJ qui l'aident à prendre des décisions 
                                et à interagir avec les autres.
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Traits de personnalité</label>
                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                            <?php if ($npc->personality_traits): ?>
                                                <?php echo nl2br(htmlspecialchars($npc->personality_traits)); ?>
                                            <?php else: ?>
                                                <em class="text-muted">Aucun trait de personnalité défini</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Comment ce PNJ se comporte-t-il ? Qu'est-ce qui le caractérise ?
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Idéaux</label>
                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                            <?php if ($npc->ideals): ?>
                                                <?php echo nl2br(htmlspecialchars($npc->ideals)); ?>
                                            <?php else: ?>
                                                <em class="text-muted">Aucun idéal défini</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-star me-1"></i>
                                            Quelles valeurs sont importantes pour ce PNJ ?
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Liens</label>
                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                            <?php if ($npc->bonds): ?>
                                                <?php echo nl2br(htmlspecialchars($npc->bonds)); ?>
                                            <?php else: ?>
                                                <em class="text-muted">Aucun lien défini</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-link me-1"></i>
                                            À quoi ou à qui ce PNJ est-il attaché ?
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Défauts</label>
                                        <div class="form-control-plaintext bg-light p-3 rounded">
                                            <?php if ($npc->flaws): ?>
                                                <?php echo nl2br(htmlspecialchars($npc->flaws)); ?>
                                            <?php else: ?>
                                                <em class="text-muted">Aucun défaut défini</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Quelles sont les faiblesses ou les vices de ce PNJ ?
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-user-edit text-muted fa-3x mb-3"></i>
                        <p class="text-muted">Aucune information personnelle définie pour ce PNJ.</p>
                        <small class="text-muted">Les traits de personnalité, idéaux, liens et défauts peuvent être ajoutés lors de la création ou modification du PNJ.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
