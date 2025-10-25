<?php
/**
 * Template pour le modal de changement de photo de profil
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($npc_id)) {
    throw new Exception('Variable $npc_id est requise pour ce template');
}
?>

<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>
                    Changer la Photo de Profil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="photoForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_photo">
                    
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Sélectionner une nouvelle photo :</label>
                        <input type="file" class="form-control" name="profile_photo" id="profile_photo" accept="image/*" required>
                        <div class="form-text">
                            Formats acceptés : JPG, PNG, GIF (max 10MB)
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Conseil :</strong> Pour un meilleur rendu, utilisez une image carrée ou rectangulaire.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" data-action="upload-photo" data-npc-id="<?php echo $npc_id; ?>" data-entity-type="PNJ">
                    <i class="fas fa-upload me-1"></i>Uploader
                </button>
            </div>
        </div>
    </div>
</div>
