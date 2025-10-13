<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
$page_title = "Création de Personnage - Étape 3";
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
if (!$sessionData || $sessionData['step'] < 3) {
    header('Location: character_create_step1.php');
    exit();
}

$message = '';

// Récupérer tous les historiques
$backgrounds = $pdo->query("SELECT * FROM backgrounds ORDER BY name")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'select_background') {
        $background_id = (int)$_POST['background_id'];
        
        if ($background_id > 0) {
            // Sauvegarder le choix d'historique
            if (saveCharacterCreationStep($user_id, $session_id, 4, ['background_id' => $background_id])) {
                header("Location: character_create_step4.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde du choix d'historique.", "error");
            }
        } else {
            $message = displayMessage("Veuillez sélectionner un historique.", "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: character_create_step2.php?session_id=$session_id");
        exit();
    }
}

// Récupérer les données sélectionnées
$selectedBackgroundId = $sessionData['data']['background_id'] ?? null;
$selectedClassId = $sessionData['data']['class_id'] ?? null;
$selectedRaceId = $sessionData['data']['race_id'] ?? null;

// Récupérer les informations des choix précédents
$selectedClass = null;
$selectedRace = null;

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
        .background-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .background-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .background-card.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .step-progress-bar {
            width: 33.33%; /* 3/9 * 100 */
        }
        .background-features {
            font-size: 0.9em;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
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
                    <p class="mb-0">Étape 3 sur 9 - Choisissez votre historique</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 3/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-6">
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
            <div class="col-md-6">
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
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-scroll me-2"></i>Choisissez votre historique</h3>
                        <p class="mb-0 text-muted">Votre historique détermine vos compétences, votre équipement de départ et votre personnalité.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="backgroundForm">
                            <input type="hidden" name="action" value="select_background">
                            <input type="hidden" name="background_id" id="selected_background_id" value="<?php echo $selectedBackgroundId; ?>">
                            
                            <div class="row">
                                <?php foreach ($backgrounds as $background): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card background-card h-100 <?php echo $selectedBackgroundId == $background['id'] ? 'selected' : ''; ?>" 
                                             data-background-id="<?php echo $background['id']; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-<?php echo getBackgroundIcon($background['name']); ?> me-2"></i>
                                                    <?php echo htmlspecialchars($background['name']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars(substr($background['description'], 0, 120)); ?>
                                                    <?php if (strlen($background['description']) > 120): ?>...<?php endif; ?>
                                                </p>
                                                <div class="background-features">
                                                    <strong>Caractéristiques :</strong><br>
                                                    <?php if ($background['skill_proficiencies']): ?>
                                                        <small><i class="fas fa-check me-1"></i>Compétences : <?php echo htmlspecialchars($background['skill_proficiencies']); ?></small><br>
                                                    <?php endif; ?>
                                                    <?php if ($background['languages']): ?>
                                                        <small><i class="fas fa-language me-1"></i>Langues : <?php echo htmlspecialchars($background['languages']); ?></small><br>
                                                    <?php endif; ?>
                                                    <?php if ($background['money_gold']): ?>
                                                        <small><i class="fas fa-coins me-1"></i><?php echo $background['money_gold']; ?> po</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" <?php echo !$selectedBackgroundId ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 4
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
            const backgroundCards = document.querySelectorAll('.background-card');
            const selectedBackgroundIdInput = document.getElementById('selected_background_id');
            const continueBtn = document.getElementById('continueBtn');
            
            backgroundCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Désélectionner toutes les cartes
                    backgroundCards.forEach(c => c.classList.remove('selected'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input caché
                    const backgroundId = this.dataset.backgroundId;
                    selectedBackgroundIdInput.value = backgroundId;
                    
                    // Activer le bouton continuer
                    continueBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>

<?php
// Fonction pour obtenir l'icône d'un historique
function getBackgroundIcon($backgroundName) {
    $icons = [
        'Acolyte' => 'cross',
        'Artisan' => 'hammer',
        'Charlatan' => 'mask',
        'Criminel' => 'user-secret',
        'Ermite' => 'mountain',
        'Folk Hero' => 'star',
        'Guild Artisan' => 'tools',
        'Hermit' => 'tree',
        'Noble' => 'crown',
        'Outlander' => 'hiking',
        'Sage' => 'book',
        'Sailor' => 'ship',
        'Soldier' => 'shield-alt',
        'Spy' => 'eye',
        'Urchin' => 'home'
    ];
    
    return $icons[$backgroundName] ?? 'scroll';
}
?>
