<?php
/**
 * Template de fiche PNJ pour l'impression
 */
$npc = $data['npc'];
$r = $data['race'];
$cl = $data['class'];
?>

<div class="stat-block">
    <h1><?php echo htmlspecialchars($npc->name); ?></h1>
    <p style="font-style: italic; margin-bottom: 10px;">
        <?php echo htmlspecialchars($r->name); ?> 
        <?php if ($cl) echo " " . htmlspecialchars($cl->name) . " niveau " . $npc->level; ?>
        - <?php echo htmlspecialchars($npc->alignment); ?>
    </p>
    
    <div class="tapered-rule"></div>
    
    <div class="property-line">
        <h4>Classe d'Armure</h4>
        <p><?php echo $npc->getCA(); ?></p>
    </div>
    <div class="property-line">
        <h4>Points de Vie</h4>
        <p><?php echo $npc->hit_points_max; ?> (Actuels: <?php echo $npc->hit_points_current; ?>)</p>
    </div>
    <div class="property-line">
        <h4>Vitesse</h4>
        <p><?php echo $npc->speed; ?> m</p>
    </div>
    
    <div class="tapered-rule"></div>
    
    <div class="abilities">
        <?php
        $abilities = ['FOR' => 'strength', 'DEX' => 'dexterity', 'CON' => 'constitution', 'INT' => 'intelligence', 'SAG' => 'wisdom', 'CHA' => 'charisma'];
        foreach ($abilities as $label => $key):
            $val = $npc->$key;
            $mod = $npc->getAbilityModifier($key);
            $modStr = ($mod >= 0 ? '+' : '') . $mod;
        ?>
        <div class="ability-box">
            <div style="font-weight: bold;"><?php echo $label; ?></div>
            <div><?php echo $val; ?> (<?php echo $modStr; ?>)</div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="tapered-rule"></div>

    <div class="property-line">
        <h4>Compétences</h4>
        <p><?php echo implode(', ', $npc->getNpcSkills()); ?></p>
    </div>
    <div class="property-line">
        <h4>Langues</h4>
        <p><?php echo implode(', ', $npc->getNpcLanguages()); ?></p>
    </div>
    <div class="property-line">
        <h4>Puissance</h4>
        <p><?php echo $npc->level; ?> (XP <?php echo $npc->experience; ?>)</p>
    </div>
    
    <div class="tapered-rule"></div>
    
    <h3>Actions</h3>
    <?php if (!empty($data['attacks'])): ?>
        <?php foreach ($data['attacks'] as $atk): ?>
        <div class="property-line">
            <span style="font-weight: bold; font-style: italic;"><?php echo htmlspecialchars($atk['name']); ?>.</span>
            <em>Attaque au corps à corps ou à distance avec une arme :</em> 
            <?php echo $atk['attack_bonus'] >= 0 ? '+' . $atk['attack_bonus'] : $atk['attack_bonus']; ?> pour toucher, 
            dégâts <?php echo htmlspecialchars($atk['damage']); ?> <?php echo htmlspecialchars($atk['damage_type']); ?>.
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3>Capacités</h3>
    <?php if (!empty($data['capabilities'])): ?>
        <?php foreach ($data['capabilities'] as $cap): ?>
        <div class="property-line">
            <span style="font-weight: bold; font-style: italic;"><?php echo htmlspecialchars($cap['name']); ?>.</span>
            <span><?php echo htmlspecialchars($cap['description']); ?></span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3>Équipement</h3>
    <div class="item-list">
        <?php foreach ($data['equipment'] as $item): ?>
            <div class="item-entry">
                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                <?php if (!empty($item['item_description'])): ?>
                    <br><em style="font-size: 0.9em;"><?php echo htmlspecialchars($item['item_description']); ?></em>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="section-header">Description & Roleplay</div>
        
        <div class="property-line">
            <h4>Traits de Personnalité:</h4>
            <p><?php echo nl2br(htmlspecialchars($npc->personality_traits)); ?></p>
        </div>
        <br>
        <div class="property-line">
            <h4>Idéaux:</h4>
            <p><?php echo nl2br(htmlspecialchars($npc->ideals)); ?></p>
        </div>
        <br>
        <div class="property-line">
            <h4>Liens:</h4>
            <p><?php echo nl2br(htmlspecialchars($npc->bonds)); ?></p>
        </div>
        <br>
        <div class="property-line">
            <h4>Défauts:</h4>
            <p><?php echo nl2br(htmlspecialchars($npc->flaws)); ?></p>
        </div>
    </div>
</div>

<?php if ($npc->profile_photo): ?>
<div style="margin-top: 20px; text-align: center;">
    <img src="<?php echo htmlspecialchars($npc->profile_photo); ?>" style="max-width: 300px; border: 5px solid #58180D; border-radius: 10px;">
</div>
<?php endif; ?>
