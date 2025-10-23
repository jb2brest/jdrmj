<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

$page_title = "Création de PNJ - Étape 5";
$current_page = "create_npc";

requireLogin();

// Vérifier que l'utilisateur est MJ ou Admin
if (!User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

if (!$session_id) {
    header('Location: npc_create_step1.php');
    exit();
}

// Récupérer les données de la session
$sessionData = getNPCCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 5) {
    header('Location: npc_create_step1.php');
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
            
            if (saveNPCCreationStep($user_id, $session_id, 6, $dataToSave)) {
                header("Location: npc_create_step6.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde du choix.", "error");
            }
        } else {
            $message = displayMessage("Veuillez sélectionner une option.", "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: npc_create_step4.php?session_id=$session_id");
        exit();
    }
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
    <style>
        .step-progress-bar {
            width: 55.56%; /* 5/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .option-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .option-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .option-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .feature-list {
            font-size: 0.9em;
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
                    <h1><i class="fas fa-user-tie me-3"></i>Création de PNJ</h1>
                    <p class="mb-0">Étape 5 sur 12 - Spécialisation</p>
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
        
        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-3">
                <?php if ($selectedClass): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                <strong>Classe :</strong> <?php echo htmlspecialchars($selectedClass['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <?php if ($selectedRace): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i>
                                <strong>Race :</strong> <?php echo htmlspecialchars($selectedRace['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <?php if ($selectedBackground): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-book me-1"></i>
                                <strong>Historique :</strong> <?php echo htmlspecialchars($selectedBackground['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-dice-d20 me-1"></i>
                            <strong>Caractéristiques :</strong> Définies
                        </small>
                    </div>
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
                            <input type="hidden" name="option_id" id="selected_option_id" value="0">
                            
                            <?php if (!empty($classOptions)): ?>
                                <div class="row">
                                    <?php foreach ($classOptions as $option): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card option-card h-100" data-option-id="<?php echo $option['id']; ?>">
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
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                        <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 6
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    La classe <strong><?php echo htmlspecialchars($selectedClass['name']); ?></strong> n'a pas d'options spéciales à choisir.
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 6
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Boutons de navigation -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="go_back">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 4
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const optionCards = document.querySelectorAll('.option-card');
            const selectedOptionInput = document.getElementById('selected_option_id');
            const continueBtn = document.getElementById('continueBtn');
            
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Retirer la sélection précédente
                    optionCards.forEach(c => c.classList.remove('selected'));
                    
                    // Ajouter la sélection à la carte cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input caché
                    const optionId = this.getAttribute('data-option-id');
                    selectedOptionInput.value = optionId;
                    
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