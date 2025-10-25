<?php
/**
 * Template pour l'en-tête du personnage
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($npc) || !isset($npc->name) || !isset($npc->level) || !isset($npc->hit_points_current) || !isset($npc->hit_points_max) || !isset($npc->experience)) {
    throw new Exception('Variables $npc et ses propriétés sont requises pour ce template');
}

if (!isset($raceObject) || !isset($classObject) || !isset($backgroundObject) || !isset($armorClass)) {
    throw new Exception('Variables $raceObject, $classObject, $backgroundObject et $armorClass sont requises pour ce template');
}

if (!isset($canModifyHP)) {
    $canModifyHP = false; // Valeur par défaut
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="d-flex align-items-start">
            <div class="me-3 position-relative">
                <?php if (!empty($npc->profile_photo)): ?>
                    <img id="npc-profile-photo" src="<?php echo htmlspecialchars($npc->profile_photo); ?>" alt="Photo de <?php echo htmlspecialchars($npc->name); ?>" class="profile-photo">
                <?php else: ?>
                    <div class="profile-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <?php if ($canModifyHP): ?>
                    <button type="button" class="btn btn-sm btn-light photo-edit-button" data-bs-toggle="modal" data-bs-target="#photoModal" title="Changer la photo">
                        <i class="fas fa-camera text-primary"></i>
                    </button>
                <?php endif; ?>
            </div>
            <div>
                <p>
                    <i class="fas fa-tag me-1"></i>
                    <strong>Race :</strong> <?php echo htmlspecialchars($raceObject->name); ?>
                </p>
                <p>
                    <i class="fas fa-shield-alt me-1"></i>
                    <strong>Classe :</strong> <?php echo htmlspecialchars($classObject->name); ?>
                </p>
                <p>
                    <i class="fas fa-star me-1"></i>
                    <strong>Niveau :</strong> <?php echo $npc->level; ?>
                </p>
                <p>
                    <i class="fas fa-book me-1"></i>
                    <strong>Historique:</strong> <?php echo htmlspecialchars($backgroundObject->name); ?>
                </p>
                <p>
                    <i class="fas fa-balance-scale me-1"></i>
                    <strong>Alignement:</strong> <?php echo htmlspecialchars($npc->alignment); ?>
                </p>                            
                <?php if (isset($characterArchetype) && $characterArchetype): ?>
                    <p>
                        <i class="fas fa-magic me-1"></i>
                        <strong><?php echo htmlspecialchars($characterArchetype['archetype_type']); ?>:</strong> <?php echo htmlspecialchars($characterArchetype['name']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="col-4">
                <div class="stat-box">
                    <?php if ($canModifyHP): ?>
                        <div class="hp-display clickable-hp h5 mb-1" data-bs-toggle="modal" data-bs-target="#hpModal" title="Cliquer pour modifier les points de vie"><?php echo $npc->hit_points_current; ?>/<?php echo $npc->hit_points_max; ?></div>
                    <?php else: ?>
                        <div class="hp-display h5 mb-1"><?php echo $npc->hit_points_current; ?>/<?php echo $npc->hit_points_max; ?></div>
                    <?php endif; ?>
                    <div class="stat-label small">PV</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-box">
                    <div class="ac-display  h5 mb-1"><?php echo $armorClass; ?></div>
                    <div class="stat-label -50 small">CA</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-box">
                    <?php if ($canModifyHP): ?>
                        <div class="xp-display clickable-xp  h5 mb-1" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience"><?php echo number_format($npc->experience ?? 0); ?></div>
                    <?php else: ?>
                        <div class="xp-display  h5 mb-1"><?php echo number_format($npc->experience ?? 0); ?></div>
                    <?php endif; ?>
                    <div class="stat-label -50 small">Exp.</div>
                </div>
            </div>
        </div>
    </div>
</div>
