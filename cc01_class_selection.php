<?php
/**
 * Écran de sélection de classe commun pour PJ et PNJ
 * Première étape de la création de personnage
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Sélection de Classe";
$current_page = "character_creation";

// Récupérer le type de personnage (player ou npc)
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) {
    $character_type = 'player';
}

// Vérifier les permissions pour les PNJ
if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Récupérer toutes les classes
$classeManager = new Classe();
$classes = $classeManager->getAll();

// Gérer les messages passés en paramètre URL
$error_message = '';
$success_message = '';

if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success']) && !empty($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 1 sur 9 - Choisissez la classe</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-3" style="height: 8px;">
                            <div class="progress-bar bg-light" style="width: 11.11%"></div>
                        </div>
                        <small>Étape 1/9</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($success_message)): ?>
            <?php echo displayMessage($success_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <?php echo displayMessage($error_message, 'error'); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class="fas fa-shield-alt me-2"></i>Choisissez la classe</h3>
                    <p class="mb-0">La classe détermine les capacités principales et le style de jeu du personnage.</p>
                </div>
                
                <form method="POST" id="classForm">
                    <input type="hidden" name="action" value="select_class">
                    <input type="hidden" name="class_id" id="selected_class_id" value="">
                            
                            <div class="row">
                                <?php foreach ($classes as $class): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card class-card position-relative" data-class-id="<?php echo $class->id; ?>">
                                            <div class="character-type-badge">
                                                <span class="badge bg-<?php echo $character_type === 'npc' ? 'warning' : 'primary'; ?>">
                                                    <?php echo $character_type === 'npc' ? 'PNJ' : 'PJ'; ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-<?php echo getClassIcon($class->name); ?> me-2"></i>
                                                    <?php echo htmlspecialchars($class->name); ?>
                                                </h5>
                                                <div class="mt-auto">
                                                    <small class="text-muted">
                                                        <strong>Dé de vie :</strong> d<?php echo $class->hit_dice; ?><br>
                                                        <strong>Description :</strong> <?php echo htmlspecialchars($class->description); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                    <div class="text-center mt-4">
                        <a href="<?php echo $character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'; ?>" 
                           class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn" disabled>
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 2
                        </button>
                        <p class="text-muted mt-2 small">Sélectionnez une classe pour continuer</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/jdrmj.js"></script>
</body>
</html>

<?php
// Fonction pour obtenir l'icône d'une classe
function getClassIcon($className) {
    $icons = [
        'Barbare' => 'fist-raised',
        'Barde' => 'music',
        'Clerc' => 'cross',
        'Druide' => 'leaf',
        'Ensorceleur' => 'magic',
        'Guerrier' => 'sword',
        'Magicien' => 'hat-wizard',
        'Moine' => 'hand-rock',
        'Occultiste' => 'skull',
        'Paladin' => 'shield-alt',
        'Rôdeur' => 'bow-arrow',
        'Roublard' => 'mask'
    ];
    
    return $icons[$className] ?? 'user';
}
?>
