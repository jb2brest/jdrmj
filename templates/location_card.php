<?php
/**
 * Template de carte pour afficher un Lieu (Location)
 * Variables attendues : $location (objet Location or array)
 */

// Ensure $location is usable whether it's an object or array (depending on how it's fetched)
$locId = is_object($location) ? $location->getId() : $location['id'];
$locName = is_object($location) ? $location->getName() : $location['name'];
$locDesc = is_object($location) ? $location->getDescription() : $location['description'];
// Count rooms if available
$locRoomCount = isset($locRooms) ? count($locRooms) : (is_object($location) && method_exists($location, 'getRooms') ? count($location->getRooms()) : 0);
?>
<div class="col">
    <div class="card h-100 border-secondary mb-3 draggable-location droppable-area" 
         data-location-id="<?php echo $locId; ?>"
         ondrop="drop(event, <?php echo $locId; ?>)" 
         ondragover="allowDrop(event)"
         ondragleave="dragLeave(event)">
         
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 text-white">
                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($locName); ?>
            </h5>
            <span class="badge bg-light text-dark"><?php echo $locRoomCount; ?> pièce(s)</span>
        </div>
        
        <div class="card-body d-flex flex-column">
            <?php if (!empty($locDesc)): ?>
                <p class="card-text text-muted small flex-grow-1"><?php echo nl2br(htmlspecialchars(truncateText($locDesc, 100))); ?></p>
            <?php else: ?>
                <p class="card-text text-muted small flex-grow-1"><em>Aucune description.</em></p>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <a href="view_location.php?id=<?php echo $locId; ?>" class="btn btn-primary w-100">
                    <i class="fas fa-eye me-1"></i>Explorer le lieu
                </a>
            </div>
            
            <div class="mt-2 text-center text-muted small fst-italic">
                Glisser une pièce ici pour la déplacer
            </div>
        </div>
    </div>
</div>
