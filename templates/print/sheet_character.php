<?php
/**
 * Template de fiche de personnage pour l'impression
 * Variables disponibles : $data['character'], $data['race'], $data['class'], etc.
 */
$c = $data['character'];
$r = $data['race'];
$cl = $data['class'];
$bg = $data['background'];
?>

<div class="header-section">
    <?php if ($c->profile_photo): ?>
        <img src="<?php echo htmlspecialchars($c->profile_photo); ?>" class="profile-photo-print">
    <?php endif; ?>
    <h1><?php echo htmlspecialchars($c->name); ?></h1>
    <div class="details">
        <strong>Classe/Niveau :</strong> <?php echo htmlspecialchars($cl->name); ?> <?php echo $c->level; ?> | 
        <strong>Race :</strong> <?php echo htmlspecialchars($r->name); ?> | 
        <strong>Historique :</strong> <?php echo $bg ? htmlspecialchars($bg->name) : 'Aucun'; ?>
    </div>
    <div class="details">
        <strong>Alignement :</strong> <?php echo htmlspecialchars($c->alignment); ?> | 
        <strong>XP :</strong> <?php echo number_format($c->experience_points); ?>
    </div>
</div>

<div class="tapered-rule"></div>

<!-- Caractéristiques -->
<div class="abilities">
    <?php
    $abilities = ['Force' => 'strength', 'Dextérité' => 'dexterity', 'Constitution' => 'constitution', 'Intelligence' => 'intelligence', 'Sagesse' => 'wisdom', 'Charisme' => 'charisma'];
    foreach ($abilities as $label => $key):
        $val = $c->$key;
        $mod = $c->getAbilityModifier($key);
        $modStr = ($mod >= 0 ? '+' : '') . $mod;
    ?>
    <div class="ability-box">
        <div style="font-size: 10px; text-transform: uppercase;"><?php echo $label; ?></div>
        <div style="font-size: 18pt; font-weight: bold;"><?php echo $val; ?></div>
        <div style="font-size: 12pt;"><?php echo $modStr; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <!-- Colonne Gauche -->
    <div class="col-4">
        <div class="stat-block">
            <div class="property-line">
                <h4>Classe d'Armure</h4>
                <p><?php echo $c->getCA(); ?></p>
            </div>
            <div class="property-line">
                <h4>Initiative</h4>
                <p><?php echo ($c->getDexterityModifier() >= 0 ? '+' : '') . $c->getDexterityModifier(); ?></p>
            </div>
            <div class="property-line">
                <h4>Vitesse</h4>
                <p><?php echo $c->speed; ?> m</p>
            </div>
            <div class="property-line">
                <h4>PV Max</h4>
                <p><?php echo $c->hit_points_max; ?></p>
            </div>
        </div>

        <div class="section-header">Compétences</div>
        <ul style="font-size: 9pt; padding-left: 20px; list-style-type: circle;">
            <?php 
            $skills = json_decode($c->skills, true) ?: [];
            sort($skills);
            foreach ($skills as $skill): ?>
                <li><?php echo htmlspecialchars($skill); ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="section-header">Langues</div>
        <p style="font-size: 9pt;"><?php 
            $langs = json_decode($c->languages, true) ?: []; 
            echo implode(', ', $langs);
        ?></p>
    </div>

    <!-- Colonne Droite -->
    <div class="col-8" style="width: 66.666%;">
        
        <!-- Attaques -->
        <div class="section-header">Attaques & Incantations</div>
        <?php if (!empty($data['attacks'])): ?>
            <div class="stat-block" style="padding: 5px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                    <tr style="border-bottom: 1px solid #58180D;">
                        <th style="padding: 2px;">Nom</th>
                        <th style="padding: 2px;">Bonus</th>
                        <th style="padding: 2px;">Dégâts/Type</th>
                    </tr>
                    <?php foreach ($data['attacks'] as $atk): ?>
                    <tr>
                        <td style="padding: 2px; font-weight: bold;"><?php echo htmlspecialchars($atk['name']); ?></td>
                        <td style="padding: 2px;"><?php echo $atk['attack_bonus'] >= 0 ? '+' . $atk['attack_bonus'] : $atk['attack_bonus']; ?></td>
                        <td style="padding: 2px;"><?php echo htmlspecialchars($atk['damage']); ?> <?php echo htmlspecialchars($atk['damage_type']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <!-- Rage (si barbare) -->
        <?php if (isset($data['rage'])): ?>
            <div class="section-header">Rage</div>
            <p><strong>Utilisations max :</strong> <?php echo $data['rage']['max']; ?></p>
        <?php endif; ?>
        
        <!-- Capacités -->
        <div class="section-header">Traits & Capacités</div>
        
        <!-- Capacités raciales, de classe et d'historique -->
        <?php 
        $groupedCapabilities = [];
        if (!empty($data['capabilities'])) {
            foreach ($data['capabilities'] as $cap) {
                $groupedCapabilities[$cap['source_type']][] = $cap;
            }
        }
        ?>

        <?php if (isset($groupedCapabilities['race'])): ?>
            <div style="margin-bottom: 10px;">
                <strong>Traits Raciaux:</strong>
                <ul style="margin: 5px 0 10px 20px; font-size: 9pt;">
                <?php foreach ($groupedCapabilities['race'] as $cap): ?>
                    <li><strong><?php echo htmlspecialchars($cap['name']); ?>:</strong> <?php echo htmlspecialchars($cap['description']); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($groupedCapabilities['class'])): ?>
            <div style="margin-bottom: 10px;">
                <strong>Capacités de Classe:</strong>
                <ul style="margin: 5px 0 10px 20px; font-size: 9pt;">
                <?php foreach ($groupedCapabilities['class'] as $cap): ?>
                    <li><strong><?php echo htmlspecialchars($cap['name']); ?>:</strong> <?php echo htmlspecialchars($cap['description']); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Equipement -->
        <div class="section-header">Equipement & Trésor</div>
        <div style="margin-bottom: 10px; font-weight: bold;">
            OR : <?php echo $c->gold; ?> | AR : <?php echo $c->silver; ?> | CU : <?php echo $c->copper; ?>
        </div>
        
        <div class="item-list">
            <?php foreach ($data['equipment'] as $item): ?>
                <div class="item-entry">
                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong> 
                    <?php if ($item['quantity'] > 1) echo " (x{$item['quantity']})"; ?>
                    <?php if ($item['is_equipped']) echo " [Équipé]"; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($data['spells'])): ?>
            <div class="section-header">Grimoire</div>
            <div class="spell-list">
            <?php 
                // Group by level
                $spellsByLevel = [];
                foreach ($data['spells'] as $spell) {
                    $spellsByLevel[$spell['level']][] = $spell;
                }
                ksort($spellsByLevel);
                
                foreach ($spellsByLevel as $lvl => $spells): 
            ?>
                <div style="break-inside: avoid;">
                    <strong>Niveau <?php echo $lvl; ?></strong>: 
                    <?php 
                        $spellNames = array_map(function($s) { return $s['name']; }, $spells);
                        echo implode(', ', $spellNames);
                    ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="section-header">Description</div>
        <div class="row" style="font-size: 9pt;">
            <div class="col-6">
                <strong>Traits:</strong> <?php echo nl2br(htmlspecialchars($c->personality_traits)); ?><br><br>
                <strong>Idéaux:</strong> <?php echo nl2br(htmlspecialchars($c->ideals)); ?>
            </div>
            <div class="col-6">
                 <strong>Liens:</strong> <?php echo nl2br(htmlspecialchars($c->bonds)); ?><br><br>
                 <strong>Défauts:</strong> <?php echo nl2br(htmlspecialchars($c->flaws)); ?>
            </div>
        </div>

    </div>
</div>
