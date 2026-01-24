<?php
/**
 * Template de fiche Monstre pour l'impression
 * Style "Stat Block" 5e
 */
$m = $data['monster']; 
$def = $data['monstreDef'];
?>

<div class="stat-block" style="background-color: #fdf1dc; width: 100%;">
    <h1><?php echo htmlspecialchars($m['name']); ?></h1>
    <p style="font-style: italic; margin-bottom: 10px;">
        <?php echo htmlspecialchars($m['size']); ?> <?php echo htmlspecialchars($m['type']); ?>, <?php echo htmlspecialchars($def->getAlignment()); ?>
    </p>

    <div class="tapered-rule"></div>

    <div class="property-line">
        <h4>Classe d'Armure</h4>
        <p><?php echo $m['armor_class']; ?></p>
    </div>
    <div class="property-line">
        <h4>Points de Vie</h4>
        <p><?php echo $m['hit_points_max']; ?> (Actuels: <?php echo $m['current_hit_points']; ?>)</p>
    </div>
    <div class="property-line">
        <h4>Vitesse</h4>
        <p><?php echo $def->getSpeed(); ?></p>
    </div>

    <div class="tapered-rule"></div>

    <div class="abilities">
        <?php
        $stats = [
            'FOR' => $m['strength'],
            'DEX' => $m['dexterity'],
            'CON' => $m['constitution'],
            'INT' => $m['intelligence'],
            'SAG' => $m['wisdom'],
            'CHA' => $m['charisma']
        ];
        foreach ($stats as $label => $val):
            $mod = floor(($val - 10) / 2);
            $modStr = ($mod >= 0 ? '+' : '') . $mod;
        ?>
        <div class="ability-box">
            <div style="font-weight: bold;"><?php echo $label; ?></div>
            <div><?php echo $val; ?> (<?php echo $modStr; ?>)</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="tapered-rule"></div>

    <?php if ($m['challenge_rating']): ?>
    <div class="property-line">
        <h4>Dangerosité</h4>
        <p><?php echo $m['challenge_rating']; ?> (XP équivalent)</p>
    </div>
    <?php endif; ?>

    <div class="tapered-rule"></div>

    <!-- Actions -->
    <?php if (!empty($data['actions'])): ?>
    <h3>Actions</h3>
    <?php foreach ($data['actions'] as $action): ?>
        <div style="margin-bottom: 10px; break-inside: avoid;">
            <span style="font-weight: bold; font-style: italic; color: #58180D;"><?php echo htmlspecialchars($action['name']); ?>.</span>
            <span><?php echo htmlspecialchars($action['description']); ?></span>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Actions Légendaires -->
    <?php if (!empty($data['legendary_actions'])): ?>
    <h3>Actions Légendaires</h3>
    <p>Le monstre peut effectuer 3 actions légendaires, choisies parmi les options suivantes. Une seule action légendaire peut être utilisée à la fois et seulement à la fin du tour d'une autre créature. Le monstre récupère les actions légendaires dépensées au début de son tour.</p>
    <?php foreach ($data['legendary_actions'] as $action): ?>
        <div style="margin-bottom: 10px; break-inside: avoid;">
            <span style="font-weight: bold; font-style: italic; color: #58180D;"><?php echo htmlspecialchars($action['name']); ?>.</span>
            <span><?php echo htmlspecialchars($action['description']); ?></span>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Attaques Spéciales / Sorts -->
    <?php if (!empty($data['special_attacks'])): ?>
    <h3>Capacités Spéciales</h3>
    <?php foreach ($data['special_attacks'] as $attack): ?>
        <div style="margin-bottom: 10px; break-inside: avoid;">
            <span style="font-weight: bold; font-style: italic; color: #58180D;"><?php echo htmlspecialchars($attack['name']); ?>.</span>
            <span><?php echo htmlspecialchars($attack['description']); ?></span>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
