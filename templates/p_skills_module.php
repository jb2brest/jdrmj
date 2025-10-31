<?php
/**
 * Module Compétences - Peut être appelé directement ou via AJAX
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
$characterSkills = $pers->skills ? json_decode($pers->skills, true) : [];
$allSkills = $characterSkills;
?>

<!-- Onglet Compétences -->
<div class="p-4">
    <div class="info-section">
        <h4><i class="fas fa-dice me-2"></i>Compétences</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Compétences maîtrisées</h5>
                    <?php if (!empty($allSkills)): ?>
                        <div class="skills-list">
                            <?php foreach ($allSkills as $skill): ?>
                                <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars($skill); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune compétence maîtrisée.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Outils et instruments</h5>
                    <?php if (!empty($allTools)): ?>
                        <div class="tools-list">
                            <?php foreach ($allTools as $tool): ?>
                                <span class="badge bg-secondary me-2 mb-2"><?php echo htmlspecialchars($tool); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun outil ou instrument maîtrisé.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
