<?php
/**
 * Template de Pays pour l'impression
 */
$country = $data['country'];
$regions = $data['regions'];
?>

<div class="stat-block" style="background-color: white; border: none; box-shadow: none;">
    <h1 style="text-align: center; font-size: 32pt;"><?php echo htmlspecialchars($country->getName()); ?></h1>
    <h3 style="text-align: center; border: none;">Monde : <?php echo htmlspecialchars($country->getWorldName()); ?></h3>
    
    <div class="tapered-rule"></div>
    
    <?php if ($country->getMapUrl()): ?>
        <div style="text-align: center; margin: 20px 0;">
            <img src="<?php echo htmlspecialchars($country->getMapUrl()); ?>" style="width: 100%; max-height: 25cm; border: 2px solid #58180D;">
        </div>
    <?php endif; ?>

    <div class="property-line">
        <h4>Description:</h4>
        <p><?php echo nl2br(htmlspecialchars($country->getDescription())); ?></p>
    </div>

    <?php if (!empty($regions)): ?>
        <div class="page-break" style="page-break-before: always;"></div>
        <h2>Régions</h2>
        <div class="row">
            <?php foreach ($regions as $region): ?>
                <div class="col-6" style="margin-bottom: 20px; break-inside: avoid;">
                    <div class="stat-block">
                        <h3><?php echo htmlspecialchars($region['name']); ?></h3>
                        <?php if (!empty($region['map_url'])): ?>
                            <img src="<?php echo htmlspecialchars($region['map_url']); ?>" style="width: 100%; margin-bottom: 10px; border: 1px solid #ccc;">
                        <?php endif; ?>
                        <p style="font-size: 9pt;"><?php echo nl2br(htmlspecialchars($region['description'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
