<?php
/**
 * Écran de sélection de race - Étape 2
 * Deuxième étape de la création de personnage
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Sélection de Race";
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

// Récupérer la classe sélectionnée
$classeManager = new Classe();
$class = $classeManager->findById($ptCharacter->class_id);

// Récupérer toutes les races
$raceManager = new Race();
$races = $raceManager->getAll();

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
                    <p class="mb-0">Étape 2 sur 9 - Choisissez la race</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-3" style="height: 8px;">
                            <div class="progress-bar bg-light" style="width: 22.22%"></div>
                        </div>
                        <small>Étape 2/9</small>
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
        
        <!-- Informations sur la classe sélectionnée -->
        <div class="info-card">
            <h4><i class="fas fa-shield-alt me-2"></i>Classe sélectionnée : <?php echo htmlspecialchars($class->name); ?></h4>
            <p class="mb-0"><?php echo htmlspecialchars($class->description); ?></p>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class="fas fa-users me-2"></i>Choisissez la race</h3>
                    <p class="mb-0">La race détermine les traits raciaux et les bonus aux caractéristiques.</p>
                </div>
                
                <form method="POST" id="raceForm">
                    <input type="hidden" name="action" value="select_race">
                    <input type="hidden" name="race_id" id="selected_race_id" value="">
                            
                    <div class="row">
                        <?php foreach ($races as $race): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card class-card position-relative" data-race-id="<?php echo $race->id; ?>">
                                    <div class="character-type-badge">
                                        <span class="badge bg-<?php echo $character_type === 'npc' ? 'warning' : 'primary'; ?>">
                                            <?php echo $character_type === 'npc' ? 'PNJ' : 'PJ'; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-<?php echo getRaceIcon($race->name); ?> me-2"></i>
                                            <?php echo htmlspecialchars($race->name); ?>
                                        </h5>
                                        <div class="mt-auto">
                                            <small class="text-muted">
                                                <strong>Vitesse :</strong> <?php echo $race->speed; ?> pieds<br>
                                                <strong>Taille :</strong> <?php echo htmlspecialchars($race->size); ?><br>
                                                <strong>Vision :</strong> <?php echo htmlspecialchars($race->vision); ?><br>
                                                <strong>Bonus :</strong> 
                                                <?php 
                                                $bonuses = [];
                                                if ($race->strength_bonus > 0) $bonuses[] = "For +{$race->strength_bonus}";
                                                if ($race->dexterity_bonus > 0) $bonuses[] = "Dex +{$race->dexterity_bonus}";
                                                if ($race->constitution_bonus > 0) $bonuses[] = "Con +{$race->constitution_bonus}";
                                                if ($race->intelligence_bonus > 0) $bonuses[] = "Int +{$race->intelligence_bonus}";
                                                if ($race->wisdom_bonus > 0) $bonuses[] = "Sag +{$race->wisdom_bonus}";
                                                if ($race->charisma_bonus > 0) $bonuses[] = "Cha +{$race->charisma_bonus}";
                                                echo implode(', ', $bonuses);
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="cc01_class_selection.php?type=<?php echo $character_type; ?>" 
                           class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn" disabled>
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 3
                        </button>
                        <p class="text-muted mt-2 small">Sélectionnez une race pour continuer</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/jdrmj.js"></script>
</body>
</html>

<?php
// Fonction pour obtenir l'icône d'une race
function getRaceIcon($raceName) {
    $icons = [
        'Humain' => 'user',
        'Elfe' => 'magic',
        'Nain' => 'hammer',
        'Halfelin' => 'child',
        'Demi-elfe' => 'user-friends',
        'Demi-orc' => 'fist-raised',
        'Dragonborn' => 'dragon',
        'Tieffelin' => 'skull'
    ];
    
    return $icons[$raceName] ?? 'user';
}
?>
