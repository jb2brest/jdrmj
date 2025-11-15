<?php
/**
 * Module Infos - Affiche les informations accessibles par thématique
 * Peut être appelé directement ou via AJAX
 */

// Inclure les classes nécessaires (chemin depuis la racine du projet)
$rootPath = dirname(__DIR__);
if (!class_exists('Character') && !class_exists('NPC') && !class_exists('Monster')) {
    require_once $rootPath . '/classes/init.php';
}
if (!function_exists('requireLogin')) {
    require_once $rootPath . '/includes/functions.php';
}
if (!function_exists('isDMOrAdmin')) {
    require_once $rootPath . '/includes/user_compatibility.php';
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

// Déterminer le type correct pour la fonction
$function_target_type = '';
if ($target_type === 'PJ') {
    $function_target_type = 'PJ';
} elseif ($target_type === 'PNJ') {
    $function_target_type = 'PNJ';
} elseif ($target_type === 'Monster' || $target_type === 'monster') {
    $function_target_type = 'Monster';
}

// Récupérer les informations accessibles
$accessible_informations = [];
if ($target_id && $function_target_type) {
    $accessible_informations = Information::getAccessibleInformations($target_id, $function_target_type);
}

// Récupérer les groupes (uniquement pour MJ/admin)
$group_memberships = [];
$isDMOrAdmin = isDMOrAdmin();
if ($isDMOrAdmin && $target_id && $function_target_type) {
    $group_memberships = Groupe::getGroupMemberships($target_id, $function_target_type);
}

// Récupérer les niveaux de confidentialité
$niveaux_confidentialite = Information::NIVEAUX;
$statuts = Information::STATUTS;
?>

<!-- Onglet Infos -->
<div class="p-4">
    <div class="info-section">
        <?php if ($isDMOrAdmin && !empty($group_memberships)): ?>
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Groupes d'appartenance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($group_memberships as $membership): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users-cog me-2 text-primary"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($membership['groupe_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Niveau hiérarchique: 
                                            <span class="badge bg-primary">
                                                <?php echo (int)$membership['hierarchy_level']; ?>
                                                <?php if ($membership['hierarchy_level'] == 1): ?>
                                                    (Dirigeant)
                                                <?php endif; ?>
                                            </span>
                                        </small>
                                        <?php if (!empty($membership['groupe_description'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($membership['groupe_description']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <h4><i class="fas fa-search me-2"></i>Informations connues</h4>
        
        <?php if (empty($accessible_informations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucune information accessible pour ce personnage.
            </div>
        <?php else: ?>
            <?php foreach ($accessible_informations as $thematique_id => $thematique_data): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($thematique_data['nom']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($thematique_data['informations'])): ?>
                            <p class="text-muted mb-0">Aucune information dans cette thématique.</p>
                        <?php else: ?>
                            <?php foreach ($thematique_data['informations'] as $info): ?>
                                <div class="mb-4 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">
                                            <i class="fas fa-file-alt me-2"></i>
                                            <?php echo htmlspecialchars($info['titre']); ?>
                                        </h6>
                                        <?php if ($isDMOrAdmin): ?>
                                            <div>
                                                <?php if (isset($niveaux_confidentialite[$info['niveau_confidentialite']])): ?>
                                                    <span class="badge bg-secondary me-1">
                                                        <?php echo htmlspecialchars($niveaux_confidentialite[$info['niveau_confidentialite']]); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($statuts[$info['statut']])): ?>
                                                    <?php
                                                    $statut_class = 'bg-info';
                                                    if ($info['statut'] === 'vraie') {
                                                        $statut_class = 'bg-success';
                                                    } elseif ($info['statut'] === 'fausse') {
                                                        $statut_class = 'bg-danger';
                                                    } elseif ($info['statut'] === 'a_verifier') {
                                                        $statut_class = 'bg-warning';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statut_class; ?>">
                                                        <?php echo htmlspecialchars($statuts[$info['statut']]); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($info['image_path'])): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo htmlspecialchars($info['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($info['titre']); ?>" 
                                                 class="img-fluid rounded" 
                                                 style="max-height: 300px; object-fit: contain;">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($info['description'])): ?>
                                        <div class="text-muted">
                                            <?php echo nl2br(htmlspecialchars($info['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($info['created_at'])): ?>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-calendar me-1"></i>
                                            Créée le <?php echo date('d/m/Y à H:i', strtotime($info['created_at'])); ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Fonction récursive pour afficher les sous-informations
                                    $renderSubInformations = function($sub_infos, $level = 0) use ($isDMOrAdmin, $niveaux_confidentialite, $statuts) {
                                        if (empty($sub_infos)) {
                                            return;
                                        }
                                        $margin_class = $level > 0 ? 'ms-4' : 'ms-4';
                                        $font_size = $level > 0 ? '0.65rem' : '0.7rem';
                                    ?>
                                        <div class="mt-3 <?php echo $margin_class; ?> border-start border-3 border-secondary ps-3">
                                            <?php if ($level === 0): ?>
                                                <h6 class="small mb-2">
                                                    <i class="fas fa-list-ul me-1"></i>Sous-informations
                                                </h6>
                                            <?php endif; ?>
                                            <?php foreach ($sub_infos as $sub_info): ?>
                                                <div class="mb-3 pb-2 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                        <strong class="small" style="font-size: <?php echo $font_size; ?>;">
                                                            <i class="fas fa-file-alt me-1"></i>
                                                            <?php echo htmlspecialchars($sub_info['titre']); ?>
                                                        </strong>
                                                        <?php if ($isDMOrAdmin): ?>
                                                            <div>
                                                                <?php if (isset($niveaux_confidentialite[$sub_info['niveau_confidentialite']])): ?>
                                                                    <span class="badge bg-secondary me-1" style="font-size: <?php echo $font_size; ?>;">
                                                                        <?php echo htmlspecialchars($niveaux_confidentialite[$sub_info['niveau_confidentialite']]); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if (isset($statuts[$sub_info['statut']])): ?>
                                                                    <?php
                                                                    $statut_class = 'bg-info';
                                                                    if ($sub_info['statut'] === 'vraie') {
                                                                        $statut_class = 'bg-success';
                                                                    } elseif ($sub_info['statut'] === 'fausse') {
                                                                        $statut_class = 'bg-danger';
                                                                    } elseif ($sub_info['statut'] === 'a_verifier') {
                                                                        $statut_class = 'bg-warning';
                                                                    }
                                                                    ?>
                                                                    <span class="badge <?php echo $statut_class; ?>" style="font-size: <?php echo $font_size; ?>;">
                                                                        <?php echo htmlspecialchars($statuts[$sub_info['statut']]); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if (!empty($sub_info['image_path'])): ?>
                                                        <div class="mb-2">
                                                            <img src="<?php echo htmlspecialchars($sub_info['image_path']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($sub_info['titre']); ?>" 
                                                                 class="img-fluid rounded" 
                                                                 style="max-height: 200px; object-fit: contain;">
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($sub_info['description'])): ?>
                                                        <div class="text-muted small" style="font-size: <?php echo $font_size; ?>;">
                                                            <?php echo nl2br(htmlspecialchars($sub_info['description'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    // Récursion : afficher les sous-informations de cette sous-information
                                                    if (!empty($sub_info['sous_informations'])) {
                                                        $renderSubInformations($sub_info['sous_informations'], $level + 1);
                                                    }
                                                    ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php 
                                    };
                                    
                                    if (!empty($info['sous_informations'])) {
                                        $renderSubInformations($info['sous_informations']);
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

