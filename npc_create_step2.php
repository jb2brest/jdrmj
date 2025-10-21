<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
$page_title = "Création de PNJ - Étape 2";
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
if (!$sessionData || $sessionData['step'] < 2) {
    header('Location: npc_create_step1.php');
    exit();
}

$message = '';

// Récupérer toutes les races
$races = $pdo->query("SELECT * FROM races ORDER BY name")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'select_race') {
        $race_id = (int)$_POST['race_id'];
        
        if ($race_id > 0) {
            // Sauvegarder le choix de race
            if (saveNPCCreationStep($user_id, $session_id, 3, ['race_id' => $race_id])) {
                header("Location: npc_create_step3.php?session_id=$session_id");
                exit();
            } else {
                $message = displayMessage("Erreur lors de la sauvegarde du choix de race.", "error");
            }
        } else {
            $message = displayMessage("Veuillez sélectionner une race.", "error");
        }
    } elseif ($_POST['action'] === 'go_back') {
        header("Location: npc_create_step1.php?session_id=$session_id");
        exit();
    }
}

// Récupérer la race sélectionnée si elle existe
$selectedRaceId = $sessionData['data']['race_id'] ?? null;
$selectedClassId = $sessionData['data']['class_id'] ?? null;

// Récupérer les informations de la classe sélectionnée
$selectedClass = null;
if ($selectedClassId) {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$selectedClassId]);
    $selectedClass = $stmt->fetch();
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
        .race-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .race-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .race-card.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .step-progress-bar {
            width: 22.22%; /* 2/9 * 100 */
        }
        .race-bonuses {
            font-size: 0.9em;
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
                    <p class="mb-0">Étape 2 sur 12 - Choisissez la race</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 2/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <!-- Récapitulatif de l'étape précédente -->
        <?php if ($selectedClass): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Classe sélectionnée :</strong> <?php echo htmlspecialchars($selectedClass['name']); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users me-2"></i>Choisissez la race du PNJ</h3>
                        <p class="mb-0 text-muted">La race détermine les traits raciaux et les bonus de caractéristiques du PNJ.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="raceForm">
                            <input type="hidden" name="action" value="select_race">
                            <input type="hidden" name="race_id" id="selected_race_id" value="<?php echo $selectedRaceId; ?>">
                            
                            <div class="row">
                                <?php foreach ($races as $race): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card race-card h-100 <?php echo $selectedRaceId == $race['id'] ? 'selected' : ''; ?>" 
                                             data-race-id="<?php echo $race['id']; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-<?php echo getRaceIcon($race['name']); ?> me-2"></i>
                                                    <?php echo htmlspecialchars($race['name']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars(substr($race['description'], 0, 150)); ?>
                                                    <?php if (strlen($race['description']) > 150): ?>...<?php endif; ?>
                                                </p>
                                                <div class="mt-auto">
                                                    <small class="text-muted race-bonuses">
                                                        <strong>Bonus raciaux :</strong><br>
                                                        <?php 
                                                        $bonuses = [];
                                                        if ($race['strength_bonus'] > 0) $bonuses[] = "Force +" . $race['strength_bonus'];
                                                        if ($race['dexterity_bonus'] > 0) $bonuses[] = "Dextérité +" . $race['dexterity_bonus'];
                                                        if ($race['constitution_bonus'] > 0) $bonuses[] = "Constitution +" . $race['constitution_bonus'];
                                                        if ($race['intelligence_bonus'] > 0) $bonuses[] = "Intelligence +" . $race['intelligence_bonus'];
                                                        if ($race['wisdom_bonus'] > 0) $bonuses[] = "Sagesse +" . $race['wisdom_bonus'];
                                                        if ($race['charisma_bonus'] > 0) $bonuses[] = "Charisme +" . $race['charisma_bonus'];
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
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 3
                                </button>
                                <p class="text-muted mt-2 small">Sélectionnez une race pour continuer</p>
                            </div>
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
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 1
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const raceCards = document.querySelectorAll('.race-card');
            const selectedRaceIdInput = document.getElementById('selected_race_id');
            const continueBtn = document.getElementById('continueBtn');
            
            raceCards.forEach(card => {
                card.addEventListener('click', function() {
                    console.log('Race cliquée:', this.dataset.raceId);
                    
                    // Désélectionner toutes les cartes
                    raceCards.forEach(c => c.classList.remove('selected'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input caché
                    const raceId = this.dataset.raceId;
                    selectedRaceIdInput.value = raceId;
                    console.log('Race ID mis à jour:', raceId);
                    
                    // Activer le bouton continuer
                    continueBtn.disabled = false;
                });
            });
            
            // Si une race est déjà sélectionnée, activer le bouton
            if (selectedRaceIdInput.value) {
                continueBtn.disabled = false;
            }
        });
    </script>
</body>
</html>