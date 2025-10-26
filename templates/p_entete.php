<?php
/**
 * Template pour l'en-tête du personnage
 * Utilisé dans view_npc.php et potentiellement d'autres pages
 */

// Vérification que les variables nécessaires sont définies
if (!isset($target_id)) {
    throw new Exception('Variable $target_id est requise pour ce template');
}
if (!isset($name)) {
    throw new Exception('Variable $name est requise pour ce template');
}
if (!isset($level)) {
    throw new Exception('Variable $level est requise pour ce template');
}
if (!isset($hit_points_current)) {
    throw new Exception('Variable $hit_points_current est requise pour ce template');
}
if (!isset($hit_points_max)) {
    throw new Exception('Variable $hit_points_max est requise pour ce template');
}
if (!isset($experience)) {
    throw new Exception('Variable $experience est requise pour ce template');
}

if (!isset($raceObject)) {
    throw new Exception('Variable $raceObject est requise pour ce template');
}
if (!isset($classObject)) {
    throw new Exception('Variable $classObject est requise pour ce template');
}
if (!isset($armorClass)) {
    throw new Exception('Variable $armorClass est requise pour ce template');
}

// $backgroundObject peut être null, on le gère avec une valeur par défaut
if (!isset($backgroundObject)) {
    $backgroundObject = null;
}
// Debug: backgroundObject géré

if (!isset($canModifyHP)) {
    $canModifyHP = false; // Valeur par défaut
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="d-flex align-items-start">
            <div class="me-3 position-relative">
                <?php if (!empty($profile_photo )): ?>
                    <img id="profile-photo" src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Photo de <?php echo htmlspecialchars($name); ?>" class="profile-photo">
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
                    <strong>Niveau :</strong> <?php echo $level; ?>
                </p>
                <p>
                    <i class="fas fa-book me-1"></i>
                    <strong>Historique:</strong> <?php echo $backgroundObject ? htmlspecialchars($backgroundObject->name) : 'Non défini'; ?>
                </p>
                <p>
                    <i class="fas fa-balance-scale me-1"></i>
                    <strong>Alignement:</strong> <?php echo htmlspecialchars($alignment); ?>
                </p>                            
                <?php if (isset($characterArchetype) && $characterArchetype): ?>
                    <p>
                        <i class="fas fa-magic me-1"></i>
                        <strong><?php echo htmlspecialchars($characterArchetype['archetype_type']); ?>:</strong> <?php echo htmlspecialchars($characterArchetype['name']); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <?php if ($canModifyHP): ?>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#longRestModal">
                        <i class="fas fa-moon me-1"></i>Long repos
                    </button>
                <?php else: ?>
                    <button class="btn btn-warning" disabled title="Permissions insuffisantes">
                        <i class="fas fa-moon me-1"></i>Long repos
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="col-4">
                <div class="stat-box">
                    <?php if ($canModifyHP): ?>
                        <div class="hp-display clickable-hp h5 mb-1" data-bs-toggle="modal" data-bs-target="#hpModal" title="Cliquer pour modifier les points de vie"><?php echo $hit_points_current; ?>/<?php echo $hit_points_max; ?></div>
                    <?php else: ?>
                        <div class="hp-display h5 mb-1"><?php echo $hit_points_current; ?>/<?php echo $hit_points_max; ?></div>
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
                        <div class="xp-display clickable-xp  h5 mb-1" data-bs-toggle="modal" data-bs-target="#xpModal" title="Gérer les points d'expérience"><?php echo number_format($experience ?? 0); ?></div>
                    <?php else: ?>
                        <div class="xp-display  h5 mb-1"><?php echo number_format($experience ?? 0); ?></div>
                    <?php endif; ?>
                    <div class="stat-label -50 small">Exp.</div>
                </div>
            </div>
        </div>
    </div>
</div>
