<?php
/**
 * Écran de sélection d'historique - Étape 3
 * Troisième étape de la création de personnage
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

// Fonction utilitaire pour décoder les données JSON des historiques
function decodeBackgroundData($jsonData) {
    if (empty($jsonData)) return '';
    
    $decoded = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return implode(', ', $decoded);
    }
    
    return $jsonData;
}

requireLogin();

$page_title = "Sélection d'Historique";
$current_page = "character_creation";

// Récupérer les paramètres
$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';

if ($pt_id <= 0) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

// Vérifier les permissions
if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Récupérer le personnage temporaire
$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

// Récupérer la classe et la race sélectionnées
$classeManager = new Classe();
$class = $classeManager->findById($ptCharacter->class_id);

$raceManager = new Race();
$race = $raceManager->findById($ptCharacter->race_id);

// Récupérer tous les historiques
$backgroundManager = new Background();
$backgrounds = $backgroundManager->getAll();

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
                    <p class="mb-0">Étape 3 sur 9 - Choisissez l'historique</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-3" style="height: 8px;">
                            <div class="progress-bar bg-light" style="width: 33.33%"></div>
                        </div>
                        <small>Étape 3/9</small>
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
        
        <!-- Informations sur les sélections précédentes -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-shield-alt me-2"></i>Classe : <?php echo htmlspecialchars($class->name); ?></h4>
                    <small>Dé de vie : d<?php echo $class->hit_dice; ?></small>
                </div>
                <div class="col-md-6">
                    <h4><i class="fas fa-users me-2"></i>Race : <?php echo htmlspecialchars($race->name); ?></h4>
                    <small>Vitesse : <?php echo $race->speed; ?> pieds | Taille : <?php echo htmlspecialchars($race->size); ?></small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class="fas fa-scroll me-2"></i>Choisissez l'historique</h3>
                    <p class="mb-0">L'historique détermine les compétences, l'équipement de départ et les traits de personnalité.</p>
                </div>
                
                <form method="POST" id="backgroundForm">
                    <input type="hidden" name="action" value="select_background">
                    <input type="hidden" name="background_id" id="selected_background_id" value="">
                            
                    <div class="row">
                        <?php foreach ($backgrounds as $background): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card class-card position-relative" data-background-id="<?php echo $background->id; ?>">
                                    <div class="character-type-badge">
                                        <span class="badge bg-<?php echo $character_type === 'npc' ? 'warning' : 'primary'; ?>">
                                            <?php echo $character_type === 'npc' ? 'PNJ' : 'PJ'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-<?php echo getBackgroundIcon($background->name); ?> me-2"></i>
                                            <?php echo htmlspecialchars($background->name); ?>
                                        </h5>
                                        <div class="mt-auto">
                                            <small class="text-muted">
                                                <strong>Compétences :</strong> <?php echo htmlspecialchars(decodeBackgroundData($background->skill_proficiencies)); ?><br>
                                                <strong>Outils :</strong> <?php echo htmlspecialchars(decodeBackgroundData($background->tool_proficiencies)); ?><br>
                                                <strong>Langues :</strong> <?php echo htmlspecialchars(decodeBackgroundData($background->languages)); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="cc02_race_selection.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" 
                           class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn" disabled>
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 4
                        </button>
                        <p class="text-muted mt-2 small">Sélectionnez un historique pour continuer</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/jdrmj.js"></script>
</body>
</html>

<?php
// Fonction pour obtenir l'icône d'un historique
function getBackgroundIcon($backgroundName) {
    $icons = [
        'Acolyte' => 'cross',
        'Artisan' => 'hammer',
        'Charlatan' => 'theater-masks',
        'Criminel' => 'mask',
        'Ermite' => 'mountain',
        'Erudit' => 'book',
        'Folk Hero' => 'star',
        'Guild Artisan' => 'tools',
        'Hermit' => 'mountain',
        'Noble' => 'crown',
        'Outlander' => 'hiking',
        'Sage' => 'scroll',
        'Sailor' => 'ship',
        'Soldat' => 'shield-alt',
        'Soldier' => 'shield-alt',
        'Spy' => 'eye',
        'Urchin' => 'child'
    ];
    
    return $icons[$backgroundName] ?? 'scroll';
}
?>
