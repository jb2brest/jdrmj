<?php
/**
 * Template de pièce pour l'impression
 */
$p = $data['place'];
?>

<div class="stat-block" style="background-color: white; border: none; box-shadow: none;">
    <h1><?php echo htmlspecialchars($p['title']); ?></h1>
    
    <div class="tapered-rule"></div>
    
    <?php if (!empty($p['map_url'])): ?>
        <div style="text-align: center; margin: 5px 0;">
            <img src="<?php echo htmlspecialchars($p['map_url']); ?>" style="width: 100%; max-height: 22cm; object-fit: contain; border: 2px solid #555;">
        </div>
    <?php endif; ?>

    <div class="property-line">
        <h4>Description:</h4>
        <p><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
    </div>

    <!-- Si on voulait lister les objets visibles -->
    <?php if (!empty($data['visible_objects'])): ?>
    <div class="tapered-rule"></div>
    <h3>Objets Visibles</h3>
    <ul style="column-count: 2;">
        <?php foreach ($data['visible_objects'] as $obj): ?>
            <li><?php echo htmlspecialchars($obj['name']); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
