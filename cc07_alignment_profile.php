<?php
/**
 * Étape 7 - Choix de l'alignement et de la photo de profil
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = "Alignement et Photo";
$current_page = "character_creation";

// Paramètres
$pt_id = (int)($_GET['pt_id'] ?? 0);
$character_type = $_GET['type'] ?? 'player';
if (!in_array($character_type, ['player', 'npc'])) {
    $character_type = 'player';
}

if ($character_type === 'npc' && !User::isDMOrAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Charger PTCharacter
$ptCharacter = PTCharacter::findById($pt_id);
if (!$ptCharacter || $ptCharacter->user_id != $_SESSION['user_id']) {
    header('Location: ' . ($character_type === 'npc' ? 'manage_npcs.php' : 'characters.php'));
    exit();
}

// Charger classe, race, historique pour le bandeau
$classeManager = new Classe();
$selectedClass = $ptCharacter->class_id ? $classeManager->findById($ptCharacter->class_id) : null;
$raceManager = new Race();
$selectedRace = $ptCharacter->race_id ? $raceManager->findById($ptCharacter->race_id) : null;
$backgroundManager = new Background();
$selectedBackground = $ptCharacter->background_id ? $backgroundManager->findById($ptCharacter->background_id) : null;

$alignments = [
    "Loyal Bon", "Neutre Bon", "Chaotique Bon",
    "Loyal Neutre", "Neutre", "Chaotique Neutre",
    "Loyal Mauvais", "Neutre Mauvais", "Chaotique Mauvais"
];

// Pré-sélection à partir de l'alignement existant
$selectedAxisOrder = '';
$selectedAxisMoral = '';
if (!empty($ptCharacter->alignment)) {
    if ($ptCharacter->alignment === 'Neutre') {
        $selectedAxisOrder = 'Neutre';
        $selectedAxisMoral = 'Neutre';
    } else {
        $parts = explode(' ', $ptCharacter->alignment);
        if (count($parts) === 2) {
            $selectedAxisOrder = $parts[0];
            $selectedAxisMoral = $parts[1];
        }
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_alignment_photo') {
        // Alignement à partir de deux axes
        $axisOrder = trim($_POST['axis_order'] ?? ''); // Chaotique | Neutre | Loyal
        $axisMoral = trim($_POST['axis_moral'] ?? ''); // Mauvais | Neutre | Bon

        // Construire l'alignement combiné
        if ($axisOrder === 'Neutre' && $axisMoral === 'Neutre') {
            $alignment = 'Neutre';
        } elseif ($axisOrder && $axisMoral && $axisOrder !== '' && $axisMoral !== '') {
            $alignment = $axisOrder . ' ' . $axisMoral;
        } else {
            $alignment = '';
        }
        if (!in_array($alignment, $alignments, true)) {
            $message = displayMessage("Veuillez choisir un alignement valide (deux axes).", 'error');
        }

        // Upload photo
        $photoPath = $ptCharacter->profile_photo; // conserver existant si non changé
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['profile_photo']['tmp_name'];
            $origName = basename($_FILES['profile_photo']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed, true)) {
                $targetDir = __DIR__ . '/uploads/profile_photos';
                
                // Vérifier/créer le dossier parent uploads d'abord
                $parentDir = dirname($targetDir);
                if (!is_dir($parentDir)) {
                    @mkdir($parentDir, 0777, true);
                }
                @chmod($parentDir, 0777);
                
                // Créer le dossier avec permissions permettant l'écriture par Apache
                if (!is_dir($targetDir)) {
                    if (!@mkdir($targetDir, 0777, true)) {
                        $message = displayMessage("Impossible de créer le dossier d'upload. Vérifiez les permissions.", 'error');
                    }
                }
                
                // S'assurer que les permissions sont correctes (777 pour permettre l'écriture par www-data)
                if (empty($message)) {
                    @chmod($targetDir, 0777);
                    
                    // Vérifier que le dossier est accessible en écriture
                    if (!is_writable($targetDir)) {
                        $currentPerms = substr(sprintf('%o', fileperms($targetDir)), -4);
                        $message = displayMessage("Le dossier d'upload n'est pas accessible en écriture (permissions: $currentPerms). Contactez l'administrateur ou exécutez: sudo chmod 777 $targetDir", 'error');
                        error_log("Upload error: Cannot write to $targetDir. Permissions: $currentPerms. Owner: " . fileowner($targetDir));
                    }
                }
                
                if (empty($message)) {
                    $safeName = 'pt_' . $ptCharacter->id . '_' . time() . '.' . $ext;
                    $absPath = $targetDir . '/' . $safeName;
                    if (move_uploaded_file($tmpName, $absPath)) {
                        // Définir les permissions du fichier créé
                        @chmod($absPath, 0664);
                        // Chemin relatif depuis la racine web
                        $photoPath = 'uploads/profile_photos/' . $safeName;
                    } else {
                        $errorMsg = "Échec de l'upload de la photo.";
                        $uploadError = $_FILES['profile_photo']['error'];
                        if ($uploadError !== UPLOAD_ERR_OK) {
                            $errorMsg .= " Erreur PHP: " . $uploadError;
                        }
                        $message = displayMessage($errorMsg, 'error');
                        error_log("Upload error: Failed to move uploaded file from $tmpName to $absPath. Error code: $uploadError");
                    }
                }
            } else {
                $message = displayMessage("Format d'image non supporté.", 'error');
            }
        }

        if (empty($message)) {
            // Sauvegarder
            $ptCharacter->alignment = $alignment;
            $ptCharacter->profile_photo = $photoPath;
            if ((int)$ptCharacter->step < 8) { $ptCharacter->step = 8; }

            if ($ptCharacter->update()) {
                header('Location: cc08_identity_story.php?pt_id=' . $pt_id . '&type=' . $character_type);
                exit();
            } else {
                $message = displayMessage("Erreur lors de l'enregistrement.", 'error');
            }
        }
    } elseif ($action === 'go_back') {
        header('Location: cc06_skills_languages.php?pt_id=' . $pt_id . '&type=' . $character_type);
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
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>

    <div class="step-indicator">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-user-plus me-3"></i>Création de <?php echo $character_type === 'npc' ? 'PNJ' : 'Personnage'; ?></h1>
                    <p class="mb-0">Étape 7 sur 9 - Choisissez votre alignement et photo</p>
                </div>
                <div class="col-md-4">
                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                        <div class="progress-bar bg-light" style="width: 77.78%"></div>
                    </div>
                    <small>Étape 7/9</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)) echo $message; ?>

        <!-- Bandeau info-card -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <?php if ($selectedClass): ?>
                        <h4><i class="fas fa-shield-alt me-2"></i>Classe : <?php echo htmlspecialchars($selectedClass->name); ?></h4>
                        <small>Dé de vie : d<?php echo $selectedClass->hit_dice; ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedRace): ?>
                        <h4><i class="fas fa-users me-2"></i>Race : <?php echo htmlspecialchars($selectedRace->name); ?></h4>
                        <small>Vitesse : <?php echo $selectedRace->speed; ?> pieds | Taille : <?php echo htmlspecialchars($selectedRace->size); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedBackground): ?>
                        <h4><i class="fas fa-scroll me-2"></i>Historique : <?php echo htmlspecialchars($selectedBackground->name); ?></h4>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-balance-scale me-2"></i>Alignement</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="alignmentPhotoForm">
                            <input type="hidden" name="action" value="save_alignment_photo">

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <h5 class="mb-2"><i class="fas fa-exchange-alt me-2"></i>Axe de l'ordre</h5>
                                    <div class="d-flex gap-3">
                                        <?php $orders = ['Chaotique','Neutre','Loyal']; foreach ($orders as $o): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="axis_order" id="order_<?php echo strtolower($o); ?>" value="<?php echo $o; ?>" <?php echo ($selectedAxisOrder === $o) ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="order_<?php echo strtolower($o); ?>"><?php echo $o; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5 class="mb-2"><i class="fas fa-heart me-2"></i>Axe moral</h5>
                                    <div class="d-flex gap-3">
                                        <?php $morals = ['Mauvais','Neutre','Bon']; foreach ($morals as $m): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="axis_moral" id="moral_<?php echo strtolower($m); ?>" value="<?php echo $m; ?>" <?php echo ($selectedAxisMoral === $m) ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="moral_<?php echo strtolower($m); ?>"><?php echo $m; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <h3 class="mt-3"><i class="fas fa-image me-2"></i>Photo de profil</h3>
                            <div class="row align-items-center">
                                <div class="col-md-3 mb-3">
                                    <?php if (!empty($ptCharacter->profile_photo)): ?>
                                        <img id="preview" src="<?php echo htmlspecialchars($ptCharacter->profile_photo); ?>" alt="Photo" class="img-fluid rounded">
                                    <?php else: ?>
                                        <img id="preview" src="images/default_profile.png" alt="Photo" class="img-fluid rounded">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-9">
                                    <label for="profile_photo" class="form-label">Télécharger une image (jpg, png, gif, webp)</label>
                                    <input class="form-control" type="file" id="profile_photo" name="profile_photo" accept="image/*">
                                    <small class="text-muted">Optionnel. L'image sera redimensionnée au besoin par votre navigateur.</small>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="cc06_skills_languages.php?pt_id=<?php echo $pt_id; ?>&type=<?php echo $character_type; ?>" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                                <button type="submit" class="btn btn-continue btn-lg">
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
    document.getElementById('profile_photo')?.addEventListener('change', function(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = document.getElementById('preview');
            if (img) img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


