<?php
/**
 * Écran de sélection des caractéristiques - Étape 4
 * Quatrième étape de la création de personnage
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

$page_title = "Sélection des Caractéristiques";
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

// Récupérer la classe, la race et l'historique sélectionnés
$classeManager = new Classe();
$class = $classeManager->findById($ptCharacter->class_id);

$raceManager = new Race();
$race = $raceManager->findById($ptCharacter->race_id);

$backgroundManager = new Background();
$background = $backgroundManager->findById($ptCharacter->background_id);

// Valeurs par défaut des caractéristiques (valeurs recommandées de la classe)
$default_stats = [
    'strength' => $class->recommended_strength ?? 10,
    'dexterity' => $class->recommended_dexterity ?? 10,
    'constitution' => $class->recommended_constitution ?? 10,
    'intelligence' => $class->recommended_intelligence ?? 10,
    'wisdom' => $class->recommended_wisdom ?? 10,
    'charisma' => $class->recommended_charisma ?? 10
];

// Valeurs actuelles du personnage temporaire (si déjà définies)
$current_stats = [
    'strength' => $ptCharacter->strength ?? $default_stats['strength'],
    'dexterity' => $ptCharacter->dexterity ?? $default_stats['dexterity'],
    'constitution' => $ptCharacter->constitution ?? $default_stats['constitution'],
    'intelligence' => $ptCharacter->intelligence ?? $default_stats['intelligence'],
    'wisdom' => $ptCharacter->wisdom ?? $default_stats['wisdom'],
    'charisma' => $ptCharacter->charisma ?? $default_stats['charisma']
];

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
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 4 sur 9 - Définissez les caractéristiques</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-3" style="height: 8px;">
                            <div class="progress-bar bg-light" style="width: 44.44%"></div>
                        </div>
                        <small>Étape 4/9</small>
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
        
        <!-- Badge du type de personnage -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert-character-type d-flex align-items-center">
                    <i class="fas fa-<?php echo $character_type === 'npc' ? 'robot' : 'user'; ?> me-2"></i>
                    <strong>Type de personnage :</strong>
                    <span class="badge bg-<?php echo $character_type === 'npc' ? 'warning' : 'primary'; ?> ms-2">
                        <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage Joueur'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Informations sur les sélections précédentes -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h4><i class="fas fa-shield-alt me-2"></i>Classe : <?php echo htmlspecialchars($class->name); ?></h4>
                    <small>Dé de vie : d<?php echo $class->hit_dice; ?></small>
                </div>
                <div class="col-md-4">
                    <h4><i class="fas fa-users me-2"></i>Race : <?php echo htmlspecialchars($race->name); ?></h4>
                    <small>Vitesse : <?php echo $race->speed; ?> pieds | Taille : <?php echo htmlspecialchars($race->size); ?></small>
                </div>
                <div class="col-md-4">
                    <h4><i class="fas fa-scroll me-2"></i>Historique : <?php echo htmlspecialchars($background->name); ?></h4>
                    <small>Compétences : <?php echo htmlspecialchars(decodeBackgroundData($background->skill_proficiencies)); ?></small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class="fas fa-dice-d20 me-2"></i>Définissez les caractéristiques</h3>
                    <p class="mb-0">Ajustez les valeurs des caractéristiques selon vos préférences. Les valeurs recommandées pour la classe sont préchargées.</p>
                </div>
                
                <form method="POST" id="characteristicsForm">
                    <input type="hidden" name="action" value="update_characteristics">
                    
                    <div class="row">
                        <!-- Force -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-fist-raised me-2"></i>Force
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="strength" name="strength" 
                                               value="<?php echo $current_stats['strength']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['strength']; ?>">
                                        <div class="stat-modifier" id="strength_modifier">
                                            <?php echo getModifier($current_stats['strength']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['strength']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dextérité -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-running me-2"></i>Dextérité
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="dexterity" name="dexterity" 
                                               value="<?php echo $current_stats['dexterity']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['dexterity']; ?>">
                                        <div class="stat-modifier" id="dexterity_modifier">
                                            <?php echo getModifier($current_stats['dexterity']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['dexterity']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Constitution -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-heart me-2"></i>Constitution
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="constitution" name="constitution" 
                                               value="<?php echo $current_stats['constitution']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['constitution']; ?>">
                                        <div class="stat-modifier" id="constitution_modifier">
                                            <?php echo getModifier($current_stats['constitution']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['constitution']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Intelligence -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-brain me-2"></i>Intelligence
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="intelligence" name="intelligence" 
                                               value="<?php echo $current_stats['intelligence']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['intelligence']; ?>">
                                        <div class="stat-modifier" id="intelligence_modifier">
                                            <?php echo getModifier($current_stats['intelligence']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['intelligence']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sagesse -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-eye me-2"></i>Sagesse
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="wisdom" name="wisdom" 
                                               value="<?php echo $current_stats['wisdom']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['wisdom']; ?>">
                                        <div class="stat-modifier" id="wisdom_modifier">
                                            <?php echo getModifier($current_stats['wisdom']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['wisdom']; ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charisme -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-comments me-2"></i>Charisme
                                    </h5>
                                    <div class="stat-input-group">
                                        <input type="number" class="form-control stat-input" id="charisma" name="charisma" 
                                               value="<?php echo $current_stats['charisma']; ?>" min="1" max="20" 
                                               data-recommended="<?php echo $default_stats['charisma']; ?>">
                                        <div class="stat-modifier" id="charisma_modifier">
                                            <?php echo getModifier($current_stats['charisma']); ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Recommandé : <?php echo $default_stats['charisma']; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-primary me-3" id="applyRecommendedBtn">
                            <i class="fas fa-magic me-2"></i>Appliquer les préconisations D&D
                        </button>
                        <a href="cc03_background_selection.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" 
                           class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-continue btn-lg" id="continueBtn">
                            <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 5
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/jdrmj.js"></script>
</body>
</html>

<?php
// Fonction pour calculer le modificateur
function getModifier($value) {
    $modifier = floor(($value - 10) / 2);
    return $modifier >= 0 ? "+$modifier" : "$modifier";
}
?>
