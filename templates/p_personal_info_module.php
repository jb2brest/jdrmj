<?php
/**
 * Module Informations Personnelles - Peut être appelé directement ou via AJAX
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
$name = $pers->name;
$level = $pers->level;
$alignment = $pers->alignment;
$personality_traits = $pers->personality_traits;
$ideals = $pers->ideals;
$bonds = $pers->bonds;
$flaws = $pers->flaws;
$profile_photo = $pers->profile_photo;
?>

<!-- Onglet Informations Personnelles -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-user-edit me-2"></i>Informations Personnelles</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-heart me-2"></i>Traits de personnalité</h5>
                    <div class="form-text">
                        Comment ce PNJ se comporte-t-il ? Qu'est-ce qui le caractérise ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($personality_traits): ?>
                            <?php echo nl2br(htmlspecialchars($personality_traits)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun trait de personnalité défini</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-star me-2"></i>Idéaux</h5>
                    <div class="form-text">
                        Quelles valeurs sont importantes pour ce PNJ ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($ideals): ?>
                            <?php echo nl2br(htmlspecialchars($ideals)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun idéal défini</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-link me-2"></i>Liens</h5>
                    <div class="form-text">
                        À quoi ou à qui ce PNJ est-il attaché ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($bonds): ?>
                            <?php echo nl2br(htmlspecialchars($bonds)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun lien défini</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Défauts</h5>
                    <div class="form-text">
                        Quelles sont les faiblesses ou les vices de ce PNJ ?
                    </div>
                    <div class="form-control-plaintext bg-light p-3 rounded">
                        <?php if ($flaws): ?>
                            <?php echo nl2br(htmlspecialchars($flaws)); ?>
                        <?php else: ?>
                            <em class="text-muted">Aucun lien défini</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
