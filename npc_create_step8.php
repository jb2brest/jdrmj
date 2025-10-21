<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

$page_title = "Création de PNJ - Étape 8";
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
if (!$sessionData || $sessionData['step'] < 8) {
    header('Location: npc_create_step1.php');
    exit();
}

$data = $sessionData['data'];
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $height = trim($_POST['height'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $eyes = trim($_POST['eyes'] ?? '');
    $skin = trim($_POST['skin'] ?? '');
    $hair = trim($_POST['hair'] ?? '');
    $backstory = trim($_POST['backstory'] ?? '');
    $personality_traits = trim($_POST['personality_traits'] ?? '');
    $ideals = trim($_POST['ideals'] ?? '');
    $bonds = trim($_POST['bonds'] ?? '');
    $flaws = trim($_POST['flaws'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Le nom du PNJ est obligatoire.";
    }
    
    if (empty($backstory)) {
        $errors[] = "L'histoire du PNJ est obligatoire.";
    }
    
    if (empty($errors)) {
        // Sauvegarder les données
        $stepData = [
            'name' => $name,
            'age' => $age,
            'height' => $height,
            'weight' => $weight,
            'eyes' => $eyes,
            'skin' => $skin,
            'hair' => $hair,
            'backstory' => $backstory,
            'personality_traits' => $personality_traits,
            'ideals' => $ideals,
            'bonds' => $bonds,
            'flaws' => $flaws
        ];
        
        if (saveNPCCreationStep($user_id, $session_id, 9, $stepData)) {
            header("Location: npc_create_step9.php?session_id=$session_id");
            exit();
        } else {
            $message = displayMessage("Erreur lors de la sauvegarde des données.", "error");
        }
    } else {
        $message = displayMessage(implode('<br>', $errors), "error");
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'go_back') {
    header("Location: npc_create_step7.php?session_id=$session_id");
    exit();
}

// Récupérer les informations de la race et classe pour les suggestions
$raceInfo = null;
$classInfo = null;
$backgroundInfo = null;

// Données par défaut pour les suggestions de race
$raceSuggestions = [
    'Humain' => [
        'age_range' => '18-80 ans',
        'height_range' => '1,50m - 1,90m',
        'weight_range' => '50kg - 100kg'
    ],
    'Elfe' => [
        'age_range' => '100-750 ans',
        'height_range' => '1,50m - 1,80m',
        'weight_range' => '40kg - 80kg'
    ],
    'Nain' => [
        'age_range' => '50-350 ans',
        'height_range' => '1,20m - 1,50m',
        'weight_range' => '60kg - 120kg'
    ],
    'Halfelin' => [
        'age_range' => '20-150 ans',
        'height_range' => '0,90m - 1,20m',
        'weight_range' => '20kg - 40kg'
    ],
    'Demi-elfe' => [
        'age_range' => '20-180 ans',
        'height_range' => '1,50m - 1,80m',
        'weight_range' => '45kg - 90kg'
    ],
    'Demi-orc' => [
        'age_range' => '14-75 ans',
        'height_range' => '1,60m - 2,00m',
        'weight_range' => '70kg - 120kg'
    ],
    'Drakéide' => [
        'age_range' => '15-80 ans',
        'height_range' => '1,70m - 2,20m',
        'weight_range' => '80kg - 150kg'
    ],
    'Gnome' => [
        'age_range' => '40-500 ans',
        'height_range' => '0,90m - 1,20m',
        'weight_range' => '20kg - 40kg'
    ],
    'Tieffelin' => [
        'age_range' => '18-110 ans',
        'height_range' => '1,50m - 1,90m',
        'weight_range' => '50kg - 100kg'
    ]
];

// Récupérer les suggestions pour la race sélectionnée
$currentSuggestions = null;
if ($selectedRace && isset($raceSuggestions[$selectedRace['name']])) {
    $currentSuggestions = $raceSuggestions[$selectedRace['name']];
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
            width: 88.89%; /* 8/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .suggestion-box {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .suggestion-box h6 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .suggestion-box small {
            color: #424242;
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-section h5 {
            color: #007bff;
            margin-bottom: 15px;
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
                    <p class="mb-0">Étape 8 sur 12 - Histoire du Personnage</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 8/9</small>
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
                            <i class="fas fa-balance-scale me-1"></i>
                            <strong>Alignement :</strong> Défini
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suggestions basées sur la race -->
        <?php if ($currentSuggestions): ?>
            <div class="suggestion-box">
                <h6><i class="fas fa-lightbulb me-2"></i>Suggestions pour <?php echo htmlspecialchars($selectedRace['name']); ?></h6>
                <div class="row">
                    <div class="col-md-4">
                        <small><strong>Âge :</strong> <?php echo $currentSuggestions['age_range']; ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Taille :</strong> <?php echo $currentSuggestions['height_range']; ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Poids :</strong> <?php echo $currentSuggestions['weight_range']; ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-scroll me-2"></i>Histoire du Personnage</h3>
                        <p class="mb-0 text-muted">Définissez l'identité, l'apparence et l'histoire de votre PNJ.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="characterStoryForm">
                            
                            <!-- Informations de base -->
                            <div class="form-section">
                                <h5><i class="fas fa-user me-2"></i>Informations de Base</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nom du PNJ <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" required 
                                                   value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>"
                                                   placeholder="Ex: Aelindra Vaelen">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="age" class="form-label">Âge</label>
                                            <input type="text" class="form-control" id="age" name="age" 
                                                   value="<?php echo htmlspecialchars($data['age'] ?? ''); ?>"
                                                   placeholder="Ex: 25 ans">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Apparence physique -->
                            <div class="form-section">
                                <h5><i class="fas fa-eye me-2"></i>Apparence Physique</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="height" class="form-label">Taille</label>
                                            <input type="text" class="form-control" id="height" name="height" 
                                                   value="<?php echo htmlspecialchars($data['height'] ?? ''); ?>"
                                                   placeholder="Ex: 1,75m">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="weight" class="form-label">Poids</label>
                                            <input type="text" class="form-control" id="weight" name="weight" 
                                                   value="<?php echo htmlspecialchars($data['weight'] ?? ''); ?>"
                                                   placeholder="Ex: 70kg">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="eyes" class="form-label">Yeux</label>
                                            <input type="text" class="form-control" id="eyes" name="eyes" 
                                                   value="<?php echo htmlspecialchars($data['eyes'] ?? ''); ?>"
                                                   placeholder="Ex: Verts">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="skin" class="form-label">Peau</label>
                                            <input type="text" class="form-control" id="skin" name="skin" 
                                                   value="<?php echo htmlspecialchars($data['skin'] ?? ''); ?>"
                                                   placeholder="Ex: Bronzée">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hair" class="form-label">Cheveux</label>
                                            <input type="text" class="form-control" id="hair" name="hair" 
                                                   value="<?php echo htmlspecialchars($data['hair'] ?? ''); ?>"
                                                   placeholder="Ex: Bruns, longs">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Histoire et personnalité -->
                            <div class="form-section">
                                <h5><i class="fas fa-book-open me-2"></i>Histoire et Personnalité</h5>
                                <div class="mb-3">
                                    <label for="backstory" class="form-label">Histoire du personnage <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="backstory" name="backstory" rows="4" required 
                                              placeholder="Racontez l'histoire de votre PNJ, ses origines, ses expériences passées..."><?php echo htmlspecialchars($data['backstory'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="personality_traits" class="form-label">Traits de personnalité</label>
                                            <textarea class="form-control" id="personality_traits" name="personality_traits" rows="3" 
                                                      placeholder="Comment votre PNJ se comporte-t-il ? Quels sont ses traits distinctifs ?"><?php echo htmlspecialchars($data['personality_traits'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ideals" class="form-label">Idéaux</label>
                                            <textarea class="form-control" id="ideals" name="ideals" rows="3" 
                                                      placeholder="Quelles sont les valeurs et croyances de votre PNJ ?"><?php echo htmlspecialchars($data['ideals'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bonds" class="form-label">Liens</label>
                                            <textarea class="form-control" id="bonds" name="bonds" rows="3" 
                                                      placeholder="Qui ou quoi est important pour votre PNJ ?"><?php echo htmlspecialchars($data['bonds'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="flaws" class="form-label">Défauts</label>
                                            <textarea class="form-control" id="flaws" name="flaws" rows="3" 
                                                      placeholder="Quels sont les défauts et faiblesses de votre PNJ ?"><?php echo htmlspecialchars($data['flaws'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 9
                                </button>
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
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 7
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-remplissage des suggestions
            const raceSuggestions = <?php echo json_encode($raceSuggestions); ?>;
            const selectedRace = '<?php echo $selectedRace ? addslashes($selectedRace['name']) : ''; ?>';
            
            if (selectedRace && raceSuggestions[selectedRace]) {
                const suggestions = raceSuggestions[selectedRace];
                
                // Ajouter des placeholders basés sur les suggestions
                const ageInput = document.getElementById('age');
                const heightInput = document.getElementById('height');
                const weightInput = document.getElementById('weight');
                
                if (ageInput && !ageInput.value) {
                    ageInput.placeholder = 'Ex: ' + suggestions.age_range.split('-')[0].trim();
                }
                if (heightInput && !heightInput.value) {
                    heightInput.placeholder = 'Ex: ' + suggestions.height_range.split('-')[0].trim();
                }
                if (weightInput && !weightInput.value) {
                    weightInput.placeholder = 'Ex: ' + suggestions.weight_range.split('-')[0].trim();
                }
            }
        });
    </script>
</body>
</html>