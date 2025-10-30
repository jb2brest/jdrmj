<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
$page_title = "Création de Personnage - Étape 5";
$current_page = "create_character";

requireLogin();

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

if (!$session_id) {
    header('Location: character_create_step1.php');
    exit();
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 5) {
    header('Location: character_create_step1.php');
    exit();
}

$message = '';

// Récupérer les données sélectionnées
$selectedClassId = $sessionData['data']['class_id'] ?? null;
$selectedRaceId = $sessionData['data']['race_id'] ?? null;
$selectedBackgroundId = $sessionData['data']['background_id'] ?? null;

// Récupérer les informations des choix précédents
$selectedClass = null;
$selectedRace = null;
$selectedBackground = null;

if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $selectedClass = $stmt->fetch();
}

if ($selectedRaceId) {
    $stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
    $stmt->execute([$selectedRaceId]);
    $selectedRace = $stmt->fetch();
}

if ($selectedBackgroundId) {
    $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
    $stmt->execute([$selectedBackgroundId]);
    $selectedBackground = $stmt->fetch();
}

// Utilitaire local pour décoder des JSON d'historique comme dans C04
function decodeBackgroundData($jsonData) {
    if (empty($jsonData)) return '';
    $decoded = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return implode(', ', $decoded);
    }
    return $jsonData;
}

// Récupérer les options disponibles selon la classe
$classOptions = [];
$optionType = '';

if ($selectedClass) {
    // Définir le type d'option selon la classe
    $optionTypes = [
        'Barbare' => 'Voie',
        'Barde' => 'Collège',
        'Clerc' => 'Domaine',
        'Druide' => 'Cercle',
        'Guerrier' => 'Archétype',
        'Magicien' => 'Tradition',
        'Moine' => 'Tradition',
        'Paladin' => 'Serment',
        'Rôdeur' => 'Archétype',
        'Roublard' => 'Archétype',
        'Ensorceleur' => 'Origine',
        'Occultiste' => 'Pacte'
    ];
    
    $optionType = $optionTypes[$selectedClass['name']] ?? '';
    
    // Récupérer les archetypes pour cette classe depuis la table unifiée
    if ($optionType) {
        $stmt = $pdo->prepare("SELECT * FROM class_archetypes WHERE class_id = ? ORDER BY name");
        $stmt->execute([$selectedClassId]);
        $classOptions = $stmt->fetchAll();
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'select_option') {
        $option_id = (int)$_POST['option_id'];
        
        if ($option_id > 0 || empty($classOptions)) {
            // Sauvegarder le choix (ou passer si pas d'options)
            $dataToSave = [];
            if (!empty($classOptions)) {
                $dataToSave['class_option_id'] = $option_id;
                $dataToSave['class_option_type'] = $optionType;
            }
            
            if (saveCharacterCreationStep($user_id, $session_id, 6, $dataToSave)) {
                header("Location: character_create_step6.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde du choix.", "error");
            }
        } else {
            $message = displayMessage("Veuillez sélectionner une option.", "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: character_create_step4.php?session_id=$session_id");
        exit();
    }
}

// Récupérer l'option sélectionnée si elle existe
$selectedOptionId = $sessionData['data']['class_option_id'] ?? null;
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
    <style>
        .option-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .option-card.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .step-progress-bar {
            width: 55.56%; /* 5/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .feature-list {
            font-size: 0.9em;
        }
        .no-options {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
        }
        .feature-active {
            color: #28a745;
        }
        .feature-future {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de Personnage</h1>
                    <p class="mb-0">Étape 5 sur 9 - Choisissez votre <?php echo $optionType ?: 'spécialisation'; ?></p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 5/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif des étapes précédentes (même bandeau que C04) -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <?php if ($selectedClass): ?>
                        <h4><i class="fas fa-shield-alt me-2"></i>Classe : <?php echo htmlspecialchars($selectedClass['name']); ?></h4>
                        <small>Dé de vie : d<?php echo $selectedClass['hit_dice']; ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedRace): ?>
                        <h4><i class="fas fa-users me-2"></i>Race : <?php echo htmlspecialchars($selectedRace['name']); ?></h4>
                        <small>Vitesse : <?php echo $selectedRace['speed']; ?> pieds | Taille : <?php echo htmlspecialchars($selectedRace['size']); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedBackground): ?>
                        <h4><i class="fas fa-scroll me-2"></i>Historique : <?php echo htmlspecialchars($selectedBackground['name']); ?></h4>
                        <small>Compétences : <?php echo htmlspecialchars(decodeBackgroundData($selectedBackground['skill_proficiencies'] ?? '')); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-<?php echo getClassOptionIcon($selectedClass['name'] ?? ''); ?> me-2"></i>
                            <?php if ($optionType): ?>
                                Choisissez votre <?php echo $optionType; ?>
                            <?php else: ?>
                                Spécialisation de classe
                            <?php endif; ?>
                        </h3>
                        <p class="mb-0 text-muted">
                            <?php if ($optionType): ?>
                                Votre <?php echo strtolower($optionType); ?> détermine vos capacités spéciales et votre style de jeu. 
                                <strong>Ce choix est définitif</strong> - certaines capacités ne seront actives qu'aux niveaux supérieurs.
                            <?php else: ?>
                                Cette classe n'a pas d'options spéciales à choisir.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="optionForm">
                            <input type="hidden" name="action" value="select_option">
                            <input type="hidden" name="option_id" id="selected_option_id" value="<?php echo $selectedOptionId; ?>">
                            
                            <?php if (!empty($classOptions)): ?>
                                <div class="row">
                                    <?php foreach ($classOptions as $option): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card option-card h-100 <?php echo $selectedOptionId == $option['id'] ? 'selected' : ''; ?>" 
                                                 data-option-id="<?php echo $option['id']; ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="fas fa-<?php echo getClassOptionIcon($selectedClass['name']); ?> me-2"></i>
                                                        <?php echo htmlspecialchars($option['name']); ?>
                                                    </h5>
                                                    <p class="card-text">
                                                        <?php echo htmlspecialchars(substr($option['description'], 0, 150)); ?>
                                                        <?php if (strlen($option['description']) > 150): ?>...<?php endif; ?>
                                                    </p>
                                                    
                                                    <?php if (isset($option['level_1_feature']) && $option['level_1_feature']): ?>
                                                        <div class="feature-list feature-active">
                                                            <strong><i class="fas fa-check-circle text-success me-1"></i>Niveau 1 :</strong><br>
                                                            <small><?php echo htmlspecialchars(substr($option['level_1_feature'], 0, 100)); ?>
                                                            <?php if (strlen($option['level_1_feature']) > 100): ?>...<?php endif; ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($option['level_2_feature']) && $option['level_2_feature']): ?>
                                                        <div class="feature-list mt-2 feature-future">
                                                            <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 2 :</strong><br>
                                                            <small><?php echo htmlspecialchars(substr($option['level_2_feature'], 0, 100)); ?>
                                                            <?php if (strlen($option['level_2_feature']) > 100): ?>...<?php endif; ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($option['level_3_feature']) && $option['level_3_feature']): ?>
                                                        <div class="feature-list mt-2 feature-future">
                                                            <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 3 :</strong><br>
                                                            <small><?php echo htmlspecialchars(substr($option['level_3_feature'], 0, 100)); ?>
                                                            <?php if (strlen($option['level_3_feature']) > 100): ?>...<?php endif; ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($option['level_6_feature']) && $option['level_6_feature']): ?>
                                                        <div class="feature-list mt-2 feature-future">
                                                            <strong><i class="fas fa-clock text-warning me-1"></i>Niveau 6 :</strong><br>
                                                            <small><?php echo htmlspecialchars(substr($option['level_6_feature'], 0, 100)); ?>
                                                            <?php if (strlen($option['level_6_feature']) > 100): ?>...<?php endif; ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-options">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h4>Pas d'options spéciales</h4>
                                    <p class="text-muted">
                                        La classe <strong><?php echo htmlspecialchars($selectedClass['name']); ?></strong> 
                                        n'a pas d'options spéciales à choisir.
                                    </p>
                                    <p class="text-muted">
                                        Cette classe se développe de manière unique sans sous-classes.
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" <?php echo !empty($classOptions) && !$selectedOptionId ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 6
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const optionCards = document.querySelectorAll('.option-card');
            const selectedOptionIdInput = document.getElementById('selected_option_id');
            const continueBtn = document.getElementById('continueBtn');
            
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Désélectionner toutes les cartes
                    optionCards.forEach(c => c.classList.remove('selected'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input caché
                    const optionId = this.dataset.optionId;
                    selectedOptionIdInput.value = optionId;
                    
                    // Activer le bouton continuer
                    if (continueBtn) {
                        continueBtn.disabled = false;
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Fonction pour obtenir l'icône selon le type d'option de classe
function getClassOptionIcon($className) {
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
    
    return $icons[$className] ?? 'star';
}
?>
