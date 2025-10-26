<?php
/**
 * Template pour le modal de transfert d'objets
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */
?>

<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Transférer un Objet Magique
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Objet :</strong> <span id="transferItemName"></span><br>
                    <strong>Propriétaire actuel :</strong> <span id="transferCurrentOwner"></span>
                </div>
                
                <form id="transferForm" method="POST">
                    <input type="hidden" name="action" value="transfer_item">
                    <input type="hidden" name="item_id" id="transferItemId">
                    <input type="hidden" name="current_owner" id="transferCurrentOwnerType">
                    <input type="hidden" name="source" id="transferSource">
                    
                    <div class="mb-3">
                        <label for="transferTarget" class="form-label">Transférer vers :</label>
                        <select class="form-select" name="target" id="transferTarget" required>
                            <option value="">Sélectionner une cible...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transferNotes" class="form-label">Notes (optionnel) :</label>
                        <textarea class="form-control" name="notes" id="transferNotes" rows="3" placeholder="Raison du transfert, conditions, etc."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" data-action="confirm-transfer">
                    <i class="fas fa-exchange-alt me-1"></i>Transférer
                </button>
            </div>
        </div>
    </div>
</div>
