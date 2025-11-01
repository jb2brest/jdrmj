<?php
/**
 * Module Capacités - Peut être appelé directement ou via AJAX
 */

// Inclure les classes nécessaires (chemin depuis la racine du projet)
$rootPath = dirname(__DIR__);
if (!class_exists('Character') && !class_exists('NPC')) {
    require_once $rootPath . '/classes/init.php';
}
if (!function_exists('requireLogin')) {
    require_once $rootPath . '/includes/functions.php';
}

// Si appelé via AJAX, récupérer les données depuis $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = $_POST['target_id'] ?? null;
    $target_type = $_POST['target_type'] ?? null;
} else {
    // Si appelé directement, utiliser les variables globales
    $target_id = $target_id ?? null;
    $target_type = $target_type ?? null;
}

// Charger l'objet personnage selon le type
$pers = null;
if ($target_id && $target_type) {
    if ($target_type === 'PJ') {
        $pers = Character::findById($target_id);
    } elseif ($target_type === 'PNJ') {
        $pers = NPC::findById($target_id);
    }
}

// Si aucun personnage trouvé, afficher un message d'erreur
if (!$pers) {
    echo '<div class="alert alert-danger">Personnage non trouvé</div>';
    return;
}

// Récupérer les données nécessaires via les méthodes d'instance
$allCapabilities = $pers->getCapabilities();
?>

<!-- Onglet Capacités -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-star me-2"></i>Capacités</h4>
            <div class="row">
                <div class="col-md-12">
                    <h5>Toutes les capacités</h5>
                    <?php if (!empty($allCapabilities)): ?>
                        <div class="capabilities-list">
                            <?php foreach ($allCapabilities as $capability): ?>
                                <div class="capability-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="text-primary"><?php echo htmlspecialchars($capability['name'] ?? 'Capacité inconnue'); ?></strong>
                                        <?php if (!empty($capability['type_name'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($capability['type_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($capability['description'])): ?>
                                        <p class="mb-2 text-muted"><?php echo htmlspecialchars($capability['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if (!empty($capability['source'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i>Source: <?php echo htmlspecialchars($capability['source']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($capability['learned_at'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($capability['learned_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-star text-muted fa-3x mb-3"></i>
                            <p class="text-muted">Aucune capacité assignée à ce personnage.</p>
                            <small class="text-muted">Les capacités peuvent être ajoutées via la gestion des personnages.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
