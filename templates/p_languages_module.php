<?php
/**
 * Module Langues - Peut être appelé directement ou via AJAX
 */

// Inclure les classes nécessaires
require_once '../classes/init.php';
require_once '../includes/functions.php';

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
$characterLanguages = $pers->languages ? json_decode($pers->languages, true) : [];
?>

<!-- Onglet Langues -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-language me-2"></i>Langues</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Langues parlées</h5>
                    <?php if (!empty($characterLanguages)): ?>
                        <div class="languages-list">
                            <?php foreach ($characterLanguages as $language): ?>
                                <span class="badge bg-info me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune langue supplémentaire.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Langues de l'historique</h5>
                    <?php if (!empty($backgroundLanguages)): ?>
                        <div class="languages-list">
                            <?php foreach ($backgroundLanguages as $language): ?>
                                <span class="badge bg-warning me-2 mb-2"><?php echo htmlspecialchars($language); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune langue de l'historique.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
