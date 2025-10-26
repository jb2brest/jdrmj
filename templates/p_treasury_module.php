<!-- Onglet Bourse -->
<div class="tab-pane fade" id="treasury" role="tabpanel" aria-labelledby="treasury-tab">
    <div class="p-4">
        <div class="info-section">
            <h4><i class="fas fa-coins me-2"></i>Bourse</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-gold"><?php echo $gold ?? 0; ?></div>
                        <div class="stat-label">Pièces d'or</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-silver"><?php echo $silver ?? 0; ?></div>
                        <div class="stat-label">Pièces d'argent</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-copper"><?php echo $copper ?? 0; ?></div>
                        <div class="stat-label">Pièces de cuivre</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
