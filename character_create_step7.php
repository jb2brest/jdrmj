<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? null;

// Vérifier que la session existe
if (!$session_id) {
    header('Location: characters.php');
    exit;
}

// Récupérer les données de la session
$sessionData = getCharacterCreationData($user_id, $session_id);
if (!$sessionData || $sessionData['step'] < 6) {
    header('Location: characters.php');
    exit;
}

$data = $sessionData['data'];

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
    $law_alignment = $_POST['law_alignment'] ?? '';
    $moral_alignment = $_POST['moral_alignment'] ?? '';
    $alignment = $law_alignment . ' ' . $moral_alignment;
    
    // Gestion de l'upload de photo
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $file_path)) {
                $profile_photo = $file_path;
            }
        }
    }
    
    // Sauvegarder les données
    $stepData = [
        'alignment' => $alignment,
        'law_alignment' => $law_alignment,
        'moral_alignment' => $moral_alignment,
        'profile_photo' => $profile_photo
    ];
    
    if (saveCharacterCreationStep($user_id, $session_id, 7, $stepData)) {
        header("Location: character_create_step8.php?session_id=$session_id");
        exit;
    } else {
        $error_message = "Erreur lors de la sauvegarde des données.";
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'go_back') {
    header("Location: character_create_step6.php?session_id=$session_id");
    exit();
}


// Axes d'alignement
$lawAxis = [
    'Chaotique' => 'Chaotique',
    'Neutre' => 'Neutre',
    'Loyal' => 'Loyal'
];

$moralAxis = [
    'Mauvais' => 'Mauvais',
    'Neutre' => 'Neutre',
    'Bon' => 'Bon'
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de Personnage - Étape 7</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom-theme.css" rel="stylesheet">
    <style>
        .step-progress-bar {
            width: 77.78%; /* 7/9 * 100 */
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
                    <p class="mb-0">Étape 7 sur 9 - Alignement et Personnalité</p>
                </div>
                <div class="col-md-4">
                    <div class="step-progress">
                        <div class="step-progress-bar"></div>
                    </div>
                    <small class="mt-2 d-block">Étape 7/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Récapitulatif des étapes précédentes -->
        <div class="row mb-4">
            <div class="col-md-2">
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
            <div class="col-md-2">
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
            <div class="col-md-2">
                <?php if ($selectedBackground): ?>
                    <div class="card summary-card">
                        <div class="card-body py-2">
                            <small class="text-muted">
                                <i class="fas fa-scroll me-1"></i>
                                <strong>Historique :</strong> <?php echo htmlspecialchars($selectedBackground['name']); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-dice-d20 me-1"></i>
                            <strong>Caractéristiques :</strong> Définies
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-star me-1"></i>
                            <strong>Spécialisation :</strong> Choisie
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card summary-card">
                    <div class="card-body py-2">
                        <small class="text-muted">
                            <i class="fas fa-brain me-1"></i>
                            <strong>Compétences :</strong> Sélectionnées
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-balance-scale me-2"></i>Alignement et Personnalité</h3>
                        <p class="mb-0 text-muted">Définissez l'alignement et les traits de personnalité de votre personnage</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="step7Form">
                <div class="row">
                    <!-- Alignement -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-balance-scale me-2"></i>Alignement</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">L'alignement décrit les tendances morales et éthiques de votre personnage sur deux axes.</p>
                                
                                <!-- Axe Loi/Chaos -->
                                <div class="mb-4">
                                    <h6><i class="fas fa-balance-scale me-2"></i>Axe Loi/Chaos</h6>
                                    <p class="text-muted small mb-3">Comment votre personnage se comporte-t-il face à l'ordre et à l'autorité ?</p>
                                    <div class="row">
                                        <?php foreach ($lawAxis as $value => $label): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="law_alignment" value="<?php echo htmlspecialchars($value); ?>" 
                                                           id="law_<?php echo str_replace(' ', '_', $value); ?>"
                                                           <?php echo (($data['law_alignment'] ?? '') === $value) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="law_<?php echo str_replace(' ', '_', $value); ?>">
                                                        <strong><?php echo htmlspecialchars($label); ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Axe Bien/Mal -->
                                <div class="mb-3">
                                    <h6><i class="fas fa-heart me-2"></i>Axe Bien/Mal</h6>
                                    <p class="text-muted small mb-3">Quelles sont les motivations morales de votre personnage ?</p>
                                    <div class="row">
                                        <?php foreach ($moralAxis as $value => $label): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="moral_alignment" value="<?php echo htmlspecialchars($value); ?>" 
                                                           id="moral_<?php echo str_replace(' ', '_', $value); ?>"
                                                           <?php echo (($data['moral_alignment'] ?? '') === $value) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="moral_<?php echo str_replace(' ', '_', $value); ?>">
                                                        <strong><?php echo htmlspecialchars($label); ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Affichage de l'alignement combiné -->
                                <div id="alignmentDisplay" class="alert alert-info" style="display: none;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Alignement sélectionné :</strong> <span id="alignmentText"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photo de profil -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-camera me-2"></i>Photo de profil</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Ajoutez une photo pour représenter votre personnage (optionnel).</p>
                                
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Sélectionner une image</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                                           accept="image/*" onchange="previewImage(this)">
                                </div>
                                
                                <div id="imagePreview" class="text-center" style="display: none;">
                                    <img id="previewImg" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                    <p class="text-muted mt-2">Aperçu de l'image</p>
                                </div>
                                
                                <?php if (isset($data['profile_photo']) && $data['profile_photo']): ?>
                                    <div class="text-center">
                                        <img src="<?php echo htmlspecialchars($data['profile_photo']); ?>" alt="Photo actuelle" 
                                             class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        <p class="text-muted mt-2">Photo actuelle</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Boutons de navigation -->
                <div class="d-flex justify-content-between">
                            <div class="text-center mt-4">
                                <button type="submit" name="action" value="go_back" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 8
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
    
    // Gestion de l'affichage de l'alignement combiné
    const lawAlignments = document.querySelectorAll('input[name="law_alignment"]');
    const moralAlignments = document.querySelectorAll('input[name="moral_alignment"]');
    const alignmentDisplay = document.getElementById('alignmentDisplay');
    const alignmentText = document.getElementById('alignmentText');
    
    function updateAlignmentDisplay() {
        const lawSelected = document.querySelector('input[name="law_alignment"]:checked');
        const moralSelected = document.querySelector('input[name="moral_alignment"]:checked');
        
        if (lawSelected && moralSelected) {
            const lawValue = lawSelected.value;
            const moralValue = moralSelected.value;
            let combinedAlignment = '';
            
            // Construire l'alignement combiné
            if (lawValue === 'Neutre' && moralValue === 'Neutre') {
                combinedAlignment = 'Neutre (N)';
            } else if (lawValue === 'Neutre') {
                combinedAlignment = `Neutre ${moralValue} (N${moralValue.charAt(0)})`;
            } else if (moralValue === 'Neutre') {
                combinedAlignment = `${lawValue} Neutre (${lawValue.charAt(0)}N)`;
            } else {
                combinedAlignment = `${lawValue} ${moralValue} (${lawValue.charAt(0)}${moralValue.charAt(0)})`;
            }
            
            alignmentText.textContent = combinedAlignment;
            alignmentDisplay.style.display = 'block';
        } else {
            alignmentDisplay.style.display = 'none';
        }
    }
    
    // Ajouter les event listeners
    lawAlignments.forEach(radio => {
        radio.addEventListener('change', updateAlignmentDisplay);
    });
    
    moralAlignments.forEach(radio => {
        radio.addEventListener('change', updateAlignmentDisplay);
    });
    
    // Mise à jour initiale
    updateAlignmentDisplay();
    
    // Validation du formulaire
    const form = document.getElementById('step7Form');
    const continueBtn = document.getElementById('continueBtn');
    
    form.addEventListener('submit', function(e) {
        const lawAlignment = document.querySelector('input[name="law_alignment"]:checked');
        const moralAlignment = document.querySelector('input[name="moral_alignment"]:checked');
        
        if (!lawAlignment || !moralAlignment) {
            e.preventDefault();
            alert('Veuillez sélectionner un alignement sur les deux axes.');
            return;
        }
    });
});

// Fonction de prévisualisation d'image
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>

<style>
        .step-progress-bar {
            width: 77.78%; /* 7/9 * 100 */
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.6s ease;
}

.img-thumbnail {
    border: 2px solid #dee2e6;
    border-radius: 8px;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.small {
    font-size: 0.875rem;
}
</style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
