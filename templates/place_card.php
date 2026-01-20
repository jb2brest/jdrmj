<?php
/**
 * Template de carte pour afficher une pièce (Room)
 * Variables attendues : $place (tableau associatif)
 */
?>
<div class="col draggable-place" draggable="true" data-place-id="<?php echo (int)$place['id']; ?>">
    <div class="card h-100">
        <?php if (!empty($place['map_url'])): ?>
            <img src="<?php echo htmlspecialchars($place['map_url']); ?>" 
                    alt="Carte de <?php echo htmlspecialchars($place['title']); ?>" 
                    class="card-img-top cursor-pointer" 
                    style="height: 200px; object-fit: cover;"
                    draggable="false"
                    onclick="openMapFullscreen('<?php echo htmlspecialchars($place['map_url']); ?>', '<?php echo htmlspecialchars($place['title']); ?>')"
                    title="Cliquer pour voir en plein écran">
        <?php else: ?>
            <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background-color: #f8f9fa;">
                <i class="fas fa-map-pin fa-3x text-muted"></i>
            </div>
        <?php endif; ?>
        
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo htmlspecialchars($place['title']); ?></h5>
            
            <?php if (!empty($place['notes'])): ?>
                <p class="card-text text-muted small flex-grow-1"><?php echo nl2br(htmlspecialchars(truncateText($place['notes'], 100))); ?></p>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mt-2">
                <a href="view_place.php?id=<?php echo (int)$place['id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>Voir
                </a>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPlaceModal"
                            onclick="editPlace(<?php echo htmlspecialchars(json_encode($place)); ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la pièce <?php echo htmlspecialchars($place['title']); ?> ?');">
                        <input type="hidden" name="action" value="delete_place">
                        <input type="hidden" name="place_id" value="<?php echo (int)$place['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
