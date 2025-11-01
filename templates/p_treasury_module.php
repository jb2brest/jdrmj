<?php
/**
 * Module Bourse - Peut être appelé directement ou via AJAX
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
$gold = $pers->gold;
$silver = $pers->silver;
$copper = $pers->copper;
?>

<!-- Onglet Bourse -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-coins me-2"></i>Bourse</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-gold"><?php echo $gold ?? 0; ?></div>
                        <div class="stat-label">Pièces d'or</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-silver"><?php echo $silver ?? 0; ?></div>
                        <div class="stat-label">Pièces d'argent</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <div class="currency-display-copper"><?php echo $copper ?? 0; ?></div>
                        <div class="stat-label">Pièces de cuivre</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
