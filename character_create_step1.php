<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';
$page_title = "Création de Personnage - Étape 1";
$current_page = "create_character";

requireLogin();

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

// Si pas de session_id, créer une nouvelle session
if (!$session_id) {
    $session_id = createCharacterCreationSession($user_id);
    if (!$session_id) {
        header('Location: characters.php');
        exit();
    }
    header("Location: character_create_step1.php?session_id=$session_id");
    exit();
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData) {
    header('Location: characters.php');
    exit();
}

$message = '';

// Récupérer toutes les classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_class') {
    $class_id = (int)$_POST['class_id'];
    
    // DEBUG: Logs pour déboguer la soumission
    error_log("DEBUG character_create_step1.php - POST action: " . ($_POST['action'] ?? 'NOT_SET'));
    error_log("DEBUG character_create_step1.php - POST class_id: " . ($class_id ?? 'NOT_SET'));
    
    if ($class_id > 0) {
        // Sauvegarder le choix de classe
        $saveResult = saveCharacterCreationStep($user_id, $session_id, 2, ['class_id' => $class_id]);
        error_log("DEBUG character_create_step1.php - Save result: " . ($saveResult ? 'SUCCESS' : 'FAILED'));
        
        if ($saveResult) {
            error_log("DEBUG character_create_step1.php - Redirecting to step2");
            header("Location: character_create_step2.php?session_id=$session_id");
            exit();
        } else {
            $message = displayMessage("Erreur lors de la sauvegarde du choix de classe.", "error");
        }
    } else {
        $message = displayMessage("Veuillez sélectionner une classe.", "error");
    }
}

// Récupérer la classe sélectionnée si elle existe
$selectedClassId = $sessionData['data']['class_id'] ?? null;

// DEBUG: Logs pour déboguer la sélection de classe
error_log("DEBUG character_create_step1.php - Session ID: " . $session_id);
error_log("DEBUG character_create_step1.php - User ID: " . $user_id);
error_log("DEBUG character_create_step1.php - Session Data: " . print_r($sessionData, true));
error_log("DEBUG character_create_step1.php - Selected Class ID: " . ($selectedClassId ?? 'NULL'));
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
        .class-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .class-card.selected {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .step-progress-bar {
            width: 11.11%; /* 1/9 * 100 */
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
                    <p class="mb-0">Étape 1 sur 9 - Choisissez votre classe</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 1/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php echo $message; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shield-alt me-2"></i>Choisissez votre classe</h3>
                        <p class="mb-0 text-muted">Votre classe détermine vos capacités principales et votre style de jeu.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="classForm" onsubmit="console.log('Formulaire soumis avec class_id:', document.getElementById('selected_class_id').value);">
                            <input type="hidden" name="action" value="select_class">
                            <input type="hidden" name="class_id" id="selected_class_id" value="<?php echo $selectedClassId; ?>">
                            
                            <div class="row">
                                <?php foreach ($classes as $class): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card class-card h-100 <?php echo $selectedClassId == $class['id'] ? 'selected' : ''; ?>" 
                                             data-class-id="<?php echo $class['id']; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-<?php echo getClassIcon($class['name']); ?> me-2"></i>
                                                    <?php echo htmlspecialchars($class['name']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars(substr($class['description'], 0, 150)); ?>
                                                    <?php if (strlen($class['description']) > 150): ?>...<?php endif; ?>
                                                </p>
                                                <div class="mt-auto">
                                                    <small class="text-muted">
                                                        <strong>Dé de vie :</strong> d<?php echo $class['hit_dice']; ?><br>
                                                        <strong>Bonus de maîtrise :</strong> +2
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 2
                                </button>
                                <p class="text-muted mt-2 small">Sélectionnez une classe pour continuer</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            const selectedClassIdInput = document.getElementById('selected_class_id');
            const continueBtn = document.getElementById('continueBtn');
            
            classCards.forEach(card => {
                card.addEventListener('click', function() {
                    console.log('Classe cliquée:', this.dataset.classId);
                    
                    // Désélectionner toutes les cartes
                    classCards.forEach(c => c.classList.remove('selected'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('selected');
                    
                    // Mettre à jour l'input caché
                    const classId = this.dataset.classId;
                    selectedClassIdInput.value = classId;
                    console.log('Class ID mis à jour:', classId);
                    console.log('Input value:', selectedClassIdInput.value);
                    
                    // Activer le bouton continuer
                    continueBtn.disabled = false;
                    console.log('Bouton activé:', !continueBtn.disabled);
                });
            });
        });
    </script>
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
