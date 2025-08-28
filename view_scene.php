<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$scene_id = (int)$_GET['id'];
$isModal = isset($_GET['modal']);

// Charger la scène et sa session
$stmt = $pdo->prepare("SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, u.username AS dm_username FROM scenes s JOIN game_sessions gs ON s.session_id = gs.id JOIN users u ON gs.dm_id = u.id WHERE s.id = ?");
$stmt->execute([$scene_id]);
$scene = $stmt->fetch();

if (!$scene) {
    header('Location: index.php');
    exit();
}

$dm_id = (int)$scene['dm_id'];
$isOwnerDM = (isDM() && $_SESSION['user_id'] === $dm_id);

// Autoriser également les joueurs inscrits à voir la scène
$canView = $isOwnerDM;
if (!$canView) {
    $stmt = $pdo->prepare("SELECT 1 FROM session_registrations WHERE session_id = ? AND player_id = ? LIMIT 1");
    $stmt->execute([$scene['session_id'], $_SESSION['user_id']]);
    $canView = (bool)$stmt->fetch();
}

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Récupérer les joueurs présents dans cette scène
$stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.name AS character_name FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? ORDER BY u.username ASC");
$stmt->execute([$scene_id]);
$scenePlayers = $stmt->fetchAll();

// Récupérer les PNJ de cette scène
$stmt = $pdo->prepare("SELECT name, description, npc_character_id FROM scene_npcs WHERE scene_id = ? ORDER BY name ASC");
$stmt->execute([$scene_id]);
$sceneNpcs = $stmt->fetchAll();

// Traitements POST pour ajouter des PNJ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerDM) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_npc') {
        $npc_name = trim($_POST['npc_name'] ?? '');
        $npc_description = trim($_POST['npc_description'] ?? '');
        
        if ($npc_name === '') {
            $error_message = "Le nom du PNJ est obligatoire.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$scene_id, $npc_name, $npc_description]);
            $success_message = "PNJ ajouté à la scène.";
            
            // Recharger les PNJ
            $stmt = $pdo->prepare("SELECT name, description, npc_character_id FROM scene_npcs WHERE scene_id = ? ORDER BY name ASC");
            $stmt->execute([$scene_id]);
            $sceneNpcs = $stmt->fetchAll();
        }
    }
    
    // Ajouter un personnage du MJ comme PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'add_dm_character_npc') {
        $character_id = (int)($_POST['dm_character_id'] ?? 0);
        if ($character_id > 0) {
            $chk = $pdo->prepare("SELECT name FROM characters WHERE id = ? AND user_id = ?");
            $chk->execute([$character_id, $dm_id]);
            $char = $chk->fetch();
            if ($char) {
                $npc_name = trim($_POST['npc_name'] ?? '');
                if ($npc_name === '') { $npc_name = $char['name']; }
                $ins = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, npc_character_id) VALUES (?, ?, ?)");
                $ins->execute([$scene_id, $npc_name, $character_id]);
                $success_message = "PNJ (personnage du MJ) ajouté à la scène.";
                
                // Recharger les PNJ
                $stmt = $pdo->prepare("SELECT name, description, npc_character_id FROM scene_npcs WHERE scene_id = ? ORDER BY name ASC");
                $stmt->execute([$scene_id]);
                $sceneNpcs = $stmt->fetchAll();
            } else {
                $error_message = "Personnage invalide (doit appartenir au MJ).";
            }
        } else {
            $error_message = "Veuillez sélectionner un personnage.";
        }
    }
    
    // Exclure un joueur de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'remove_player' && isset($_POST['player_id'])) {
        $player_id = (int)$_POST['player_id'];
        $stmt = $pdo->prepare("DELETE FROM scene_players WHERE scene_id = ? AND player_id = ?");
        $stmt->execute([$scene_id, $player_id]);
        $success_message = "Joueur retiré de la scène.";
        
        // Recharger les joueurs
        $stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.name AS character_name FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? ORDER BY u.username ASC");
        $stmt->execute([$scene_id]);
        $scenePlayers = $stmt->fetchAll();
    }
    
    // Exclure un PNJ de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'remove_npc' && isset($_POST['npc_name'])) {
        $npc_name = $_POST['npc_name'];
        $stmt = $pdo->prepare("DELETE FROM scene_npcs WHERE scene_id = ? AND name = ?");
        $stmt->execute([$scene_id, $npc_name]);
        $success_message = "PNJ retiré de la scène.";
        
        // Recharger les PNJ
        $stmt = $pdo->prepare("SELECT name, description, npc_character_id FROM scene_npcs WHERE scene_id = ? ORDER BY name ASC");
        $stmt->execute([$scene_id]);
        $sceneNpcs = $stmt->fetchAll();
    }
    
    // Mettre à jour le plan de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'update_map') {
        $map_url = trim($_POST['map_url'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Upload de plan si fourni
        $newMapUrl = $map_url;
        if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['plan_file']['tmp_name'];
            $size = (int)$_FILES['plan_file']['size'];
            $originalName = $_FILES['plan_file']['name'];
            
            // Vérifier la taille (limite à 2M pour correspondre à la config PHP)
            if ($size > 2 * 1024 * 1024) {
                $error_message = "Image trop volumineuse (max 2 Mo).";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmp);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                
                if (!isset($allowed[$mime])) {
                    $error_message = "Format d'image non supporté. Formats acceptés: JPG, PNG, GIF, WebP.";
                } else {
                    $ext = $allowed[$mime];
                    $subdir = 'uploads/plans/' . date('Y/m');
                    $diskDir = __DIR__ . '/' . $subdir;
                    
                    // Créer le dossier s'il n'existe pas
                    if (!is_dir($diskDir)) {
                        if (!mkdir($diskDir, 0755, true)) {
                            $error_message = "Impossible de créer le dossier d'upload.";
                        }
                    }
                    
                    if (!isset($error_message)) {
                        $basename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $diskPath = $diskDir . '/' . $basename;
                        $webPath = $subdir . '/' . $basename;
                        
                        if (move_uploaded_file($tmp, $diskPath)) {
                            $newMapUrl = $webPath;
                        } else {
                            $error_message = "Échec de l'upload du plan. Vérifiez les permissions du dossier.";
                        }
                    }
                }
            }
        } elseif (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Gérer les erreurs d'upload
            switch ($_FILES['plan_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = "Le fichier est trop volumineux (max 2 Mo).";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = "Le fichier n'a été que partiellement uploadé.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = "Dossier temporaire manquant.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = "Impossible d'écrire le fichier sur le disque.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = "Une extension PHP a arrêté l'upload.";
                    break;
                default:
                    $error_message = "Erreur lors de l'upload du fichier.";
                    break;
            }
        }
        
        if (!isset($error_message)) {
            // Si aucun nouveau lien fourni et pas d'upload, conserver l'ancien
            if ($newMapUrl === '') { 
                $newMapUrl = $scene['map_url']; 
            }
            
            $stmt = $pdo->prepare("UPDATE scenes SET map_url = ?, notes = ? WHERE id = ? AND session_id = ?");
            $stmt->execute([$newMapUrl, $notes, $scene_id, $scene['session_id']]);
            $success_message = "Plan de la scène mis à jour.";
            
            // Recharger les données de la scène
            $stmt = $pdo->prepare("SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, u.username AS dm_username FROM scenes s JOIN game_sessions gs ON s.session_id = gs.id JOIN users u ON gs.dm_id = u.id WHERE s.id = ?");
            $stmt->execute([$scene_id]);
            $scene = $stmt->fetch();
        }
    }
}

// Récupérer la liste des personnages du MJ pour l'ajout en PNJ
$dmCharacters = [];
if ($isOwnerDM) {
    $stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$dm_id]);
    $dmCharacters = $stmt->fetchAll();
}

// Récupérer les autres scènes de la session pour navigation
$stmt = $pdo->prepare("SELECT id, title, position FROM scenes WHERE session_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$scene['session_id']]);
$allScenes = $stmt->fetchAll();

$currentPosition = $scene['position'];
$prevScene = null;
$nextScene = null;

foreach ($allScenes as $s) {
    if ($s['position'] == $currentPosition - 1) {
        $prevScene = $s;
    }
    if ($s['position'] == $currentPosition + 1) {
        $nextScene = $s;
    }
}
?>

<?php if (!$isModal): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scène: <?php echo htmlspecialchars($scene['title']); ?> - JDR 4 MJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-dice-d20 me-2"></i>JDR 4 MJ</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="view_session.php?id=<?php echo (int)$scene['session_id']; ?>">Retour Session</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="container mt-4">
    <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
    <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($scene['title']); ?></h1>
            <p class="text-muted mb-0">Session: <?php echo htmlspecialchars($scene['session_title']); ?> • MJ: <?php echo htmlspecialchars($scene['dm_username']); ?></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($prevScene): ?>
                <a href="view_scene.php?id=<?php echo (int)$prevScene['id']; ?><?php echo $isModal ? '&modal=1' : ''; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-chevron-left me-1"></i>Précédente
                </a>
            <?php endif; ?>
            <?php if ($nextScene): ?>
                <a href="view_scene.php?id=<?php echo (int)$nextScene['id']; ?><?php echo $isModal ? '&modal=1' : ''; ?>" class="btn btn-sm btn-outline-secondary">
                    Suivante<i class="fas fa-chevron-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Plan de la scène</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editMapForm">
                            <i class="fas fa-edit me-1"></i>Modifier le plan
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($isOwnerDM): ?>
                        <div class="collapse mb-3" id="editMapForm">
                            <div class="card card-body">
                                <h6>Modifier le plan de la scène</h6>
                                <form method="POST" enctype="multipart/form-data" class="row g-3">
                                    <input type="hidden" name="action" value="update_map">
                                    <div class="col-md-6">
                                        <label class="form-label">URL du plan</label>
                                        <input type="url" class="form-control" name="map_url" value="<?php echo htmlspecialchars($scene['map_url'] ?? ''); ?>" placeholder="https://...">
                                    </div>
                                                                            <div class="col-md-6">
                                            <label class="form-label">Ou téléverser un plan (image)</label>
                                            <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 2 Mo)</div>
                                        </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes du MJ</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Notes internes sur cette scène..."><?php echo htmlspecialchars($scene['notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Enregistrer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($scene['map_url'])): ?>
                        <div class="text-center">
                            <img src="<?php echo htmlspecialchars($scene['map_url']); ?>" class="img-fluid rounded" alt="Plan de la scène" style="max-height: 500px;">
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($scene['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>Ouvrir en plein écran
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-map fa-3x mb-3"></i>
                            <p>Aucun plan disponible pour cette scène.</p>
                            <?php if ($isOwnerDM): ?>
                                <p class="small">Cliquez sur "Modifier le plan" pour ajouter un plan.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($scene['notes'])): ?>
                <div class="card mt-4">
                    <div class="card-header">Notes du MJ</div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($scene['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Joueurs présents</div>
                <div class="card-body">
                    <?php if (empty($scenePlayers)): ?>
                        <p class="text-muted">Aucun joueur présent dans cette scène.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($scenePlayers as $player): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($player['username']); ?>
                                        <?php if ($player['character_name']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($player['character_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isOwnerDM): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($player['username']); ?> de cette scène ?');">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="player_id" value="<?php echo (int)$player['player_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer de la scène">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Personnages non-joueurs</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addNpcForm">
                            <i class="fas fa-plus me-1"></i>Ajouter PNJ
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($isOwnerDM): ?>
                        <div class="collapse mb-3" id="addNpcForm">
                            <div class="card card-body">
                                <h6>Ajouter un PNJ</h6>
                                <form method="POST" class="row g-2">
                                    <input type="hidden" name="action" value="add_npc">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom du PNJ</label>
                                        <input type="text" class="form-control" name="npc_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Description (optionnel)</label>
                                        <input type="text" class="form-control" name="npc_description" placeholder="Brève description...">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Ajouter
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if (!empty($dmCharacters)): ?>
                                    <hr>
                                    <h6>Ou ajouter un de vos personnages</h6>
                                    <form method="POST" class="row g-2">
                                        <input type="hidden" name="action" value="add_dm_character_npc">
                                        <div class="col-md-6">
                                            <select name="dm_character_id" class="form-select" required>
                                                <option value="" disabled selected>Choisir un personnage</option>
                                                <?php foreach ($dmCharacters as $dc): ?>
                                                    <option value="<?php echo (int)$dc['id']; ?>"><?php echo htmlspecialchars($dc['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="npc_name" class="form-control" placeholder="Nom PNJ (optionnel)">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($sceneNpcs)): ?>
                        <p class="text-muted">Aucun PNJ dans cette scène.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($sceneNpcs as $npc): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($npc['name']); ?>
                                            <?php if (!empty($npc['npc_character_id'])): ?>
                                                <span class="badge bg-info ms-1">perso MJ</span>
                                            <?php endif; ?>
                                            <?php if (!empty($npc['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($isOwnerDM): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($npc['name']); ?> de cette scène ?');">
                                                <input type="hidden" name="action" value="remove_npc">
                                                <input type="hidden" name="npc_name" value="<?php echo htmlspecialchars($npc['name']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer de la scène">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isModal): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
