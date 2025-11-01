<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/character_compatibility.php';

$page_title = "Création de PNJ - Étape 7";
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
if (!$sessionData || $sessionData['step'] < 7) {
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
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'npc_profile_' . $user_id . '_' . time() . '.' . $file_extension;
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
    
    if (saveNPCCreationStep($user_id, $session_id, 8, $stepData)) {
        header("Location: npc_create_step8.php?session_id=$session_id");
        exit();
    } else {
        $message = displayMessage("Erreur lors de la sauvegarde des données.", "error");
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'go_back') {
    header("Location: npc_create_step6.php?session_id=$session_id");
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
    <title><?php echo $page_title; ?> - JDR 4 MJ</title>
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
        .alignment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 400px;
            margin: 0 auto;
        }
        .alignment-option {
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .alignment-option:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .alignment-option.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .alignment-option input[type="radio"] {
            display: none;
        }
        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Indicateur d'étape -->
    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-tie me-3"></i>Création de PNJ</h1>
                    <p class="mb-0">Étape 7 sur 12 - Alignement et Photo</p>
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
                            <i class="fas fa-brain me-1"></i>
                            <strong>Compétences :</strong> Définies
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-balance-scale me-2"></i>Alignement et Photo de Profil</h3>
                        <p class="mb-0 text-muted">Définissez l'alignement moral du PNJ et ajoutez une photo de profil (optionnel).</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="alignmentPhotoForm">
                            
                            <!-- Alignement -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-balance-scale me-2"></i>Alignement
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Axe Loi/Chaos</label>
                                        <div class="alignment-grid">
                                            <?php foreach ($lawAxis as $key => $value): ?>
                                                <div class="alignment-option" onclick="selectAlignment('law', '<?php echo $key; ?>')">
                                                    <input type="radio" name="law_alignment" value="<?php echo $key; ?>" id="law_<?php echo $key; ?>">
                                                    <label for="law_<?php echo $key; ?>" class="mb-0">
                                                        <strong><?php echo $value; ?></strong>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Axe Bien/Mal</label>
                                        <div class="alignment-grid">
                                            <?php foreach ($moralAxis as $key => $value): ?>
                                                <div class="alignment-option" onclick="selectAlignment('moral', '<?php echo $key; ?>')">
                                                    <input type="radio" name="moral_alignment" value="<?php echo $key; ?>" id="moral_<?php echo $key; ?>">
                                                    <label for="moral_<?php echo $key; ?>" class="mb-0">
                                                        <strong><?php echo $value; ?></strong>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Alignement sélectionné :</strong> <span id="selectedAlignment">Non défini</span>
                                    </div>
                                </div>
                                
                                <!-- Photo de profil -->
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-camera me-2"></i>Photo de Profil
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <div class="upload-area" onclick="document.getElementById('profile_photo').click()">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <p class="mb-2">Cliquez pour sélectionner une image</p>
                                            <small class="text-muted">ou glissez-déposez une image ici</small>
                                            <p class="mb-0"><small class="text-muted">Formats acceptés : JPG, PNG, GIF (max 5MB)</small></p>
                                        </div>
                                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                    </div>
                                    
                                    <div id="imagePreview" class="text-center" style="display: none;">
                                        <img id="previewImg" class="photo-preview" alt="Aperçu">
                                        <p class="mt-2 mb-0"><small class="text-muted">Aperçu de l'image</small></p>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Optionnel :</strong> Vous pouvez ajouter une photo plus tard dans les paramètres du PNJ.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continuer vers l'étape 8
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
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'étape 6
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectAlignment(axis, value) {
            // Retirer la sélection précédente
            document.querySelectorAll(`input[name="${axis}_alignment"]`).forEach(radio => {
                radio.closest('.alignment-option').classList.remove('selected');
            });
            
            // Ajouter la sélection
            const selectedOption = document.getElementById(`${axis}_${value}`).closest('.alignment-option');
            selectedOption.classList.add('selected');
            document.getElementById(`${axis}_${value}`).checked = true;
            
            // Mettre à jour l'affichage de l'alignement
            updateAlignmentDisplay();
        }
        
        function updateAlignmentDisplay() {
            const lawAlignment = document.querySelector('input[name="law_alignment"]:checked');
            const moralAlignment = document.querySelector('input[name="moral_alignment"]:checked');
            
            if (lawAlignment && moralAlignment) {
                const alignment = lawAlignment.value + ' ' + moralAlignment.value;
                document.getElementById('selectedAlignment').textContent = alignment;
            } else {
                document.getElementById('selectedAlignment').textContent = 'Non défini';
            }
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Gestion du drag & drop
        const uploadArea = document.querySelector('.upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('profile_photo').files = files;
                previewImage(document.getElementById('profile_photo'));
            }
        });
    </script>
</body>
</html>