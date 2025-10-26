<?php
/**
 * Template pour le modal de gestion des longs repos
 * Utilisé dans view_npc.php, view_character.php et autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($target_id)) {
    throw new Exception('Variable $target_id est requise pour ce template');
}
if (!isset($target_type)) {
    throw new Exception('Variable $target_type est requise pour ce template');
}
if (!isset($name)) {
    throw new Exception('Variable $name est requise pour ce template');
}
?>

<div class="modal fade" id="longRestModal" tabindex="-1" aria-labelledby="longRestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="longRestModalLabel">
                    <i class="fas fa-moon me-2"></i>
                    Long Repos - <?php echo htmlspecialchars($name); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <!-- Description du Long Repos -->
                <div class="mb-4">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Qu'est-ce qu'un Long Repos ?</h6>
                        <p class="mb-0">Un long repos dure 8 heures et permet de restaurer complètement les capacités d'un <?php echo $target_type === 'PNJ' ? 'PNJ' : 'personnage'; ?>.</p>
                    </div>
                </div>

                <!-- Actions restaurées -->
                <div class="mb-4">
                    <h6><i class="fas fa-check-circle text-success me-2"></i>Actions effectuées lors du Long Repos :</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-heart text-danger me-2"></i>
                            <span>Points de vie restaurés au maximum</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-magic text-primary me-2"></i>
                            <span>Emplacements de sorts restaurés</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-fire text-warning me-2"></i>
                            <span>Rages restaurées (si applicable)</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-star text-info me-2"></i>
                            <span>Capacités spéciales restaurées</span>
                        </li>
                    </ul>
                </div>

                <!-- Avertissement -->
                <div class="mb-4">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Attention</h6>
                        <p class="mb-0">Cette action est irréversible. Toutes les capacités utilisées seront restaurées.</p>
                    </div>
                </div>

                <!-- Bouton d'action -->
                <div class="text-center">
                    <button type="button" class="btn btn-primary btn-lg" 
                            onclick="performLongRest(<?php echo $target_id; ?>, '<?php echo $target_type; ?>')">
                        <i class="fas fa-moon me-2"></i>
                        Effectuer le Long Repos
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>

<script>
// Test de disponibilité des fonctions Long Repos
document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal Long Repos chargé');
    console.log('performLongRest disponible:', typeof performLongRest !== 'undefined');
    console.log('performNpcLongRest disponible:', typeof performNpcLongRest !== 'undefined');
    console.log('performCharacterLongRest disponible:', typeof performCharacterLongRest !== 'undefined');
});
</script>
