<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Sauvegarde de Scène";
$current_page = "view_scene_backup";


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
$stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? ORDER BY u.username ASC");
$stmt->execute([$scene_id]);
$scenePlayers = $stmt->fetchAll();

// Récupérer les PNJ de cette scène
$stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? ORDER BY sn.name ASC");
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
            // Upload de photo de profil si fournie
            $profile_photo = null;
            if (isset($_FILES['npc_photo']) && $_FILES['npc_photo']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['npc_photo']['tmp_name'];
                $size = (int)$_FILES['npc_photo']['size'];
                $originalName = $_FILES['npc_photo']['name'];
                
                // Vérifier la taille (limite à 10M pour correspondre à la config PHP)
                if ($size > 10 * 1024 * 1024) {
                    $error_message = "Image trop volumineuse (max 10 Mo).";
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                    
                    if (!isset($allowed[$mime])) {
                        $error_message = "Format d'image non supporté. Formats acceptés: JPG, PNG, GIF, WebP.";
                    } else {
                        $ext = $allowed[$mime];
                        $subdir = 'uploads/profiles/' . date('Y/m');
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
                                $profile_photo = $webPath;
                            } else {
                                $error_message = "Échec de l'upload de la photo. Vérifiez les permissions du dossier.";
                            }
                        }
                    }
                }
            }
            
            if (!isset($error_message)) {
                $stmt = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, description, profile_photo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$scene_id, $npc_name, $npc_description, $profile_photo]);
                $success_message = "PNJ ajouté à la scène.";
                
                // Recharger les PNJ
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? ORDER BY sn.name ASC");
                $stmt->execute([$scene_id]);
                $sceneNpcs = $stmt->fetchAll();
            }
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
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? ORDER BY sn.name ASC");
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
        $stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? ORDER BY u.username ASC");
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
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? ORDER BY sn.name ASC");
        $stmt->execute([$scene_id]);
        $sceneNpcs = $stmt->fetchAll();
    }
    
    // Mettre à jour le nom de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'update_title') {
        $new_title = trim($_POST['scene_title'] ?? '');
        
        if ($new_title === '') {
            $error_message = "Le nom de la scène ne peut pas être vide.";
        } else {
            // Vérifier que la scène existe et appartient à la bonne session
            $check_stmt = $pdo->prepare("SELECT id, title FROM scenes WHERE id = ? AND session_id = ?");
            $check_stmt->execute([$scene_id, $scene['session_id']]);
            $current_scene = $check_stmt->fetch();
            
            if (!$current_scene) {
                $error_message = "Scène introuvable ou accès refusé.";
            } else {
                $stmt = $pdo->prepare("UPDATE scenes SET title = ? WHERE id = ? AND session_id = ?");
                $result = $stmt->execute([$new_title, $scene_id, $scene['session_id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $success_message = "Nom de la scène mis à jour avec succès.";
                    
                    // Recharger les données de la scène
                    $stmt = $pdo->prepare("SELECT s.*, gs.title AS session_title, gs.id AS session_id, gs.dm_id, u.username AS dm_username FROM scenes s JOIN game_sessions gs ON s.session_id = gs.id JOIN users u ON gs.dm_id = u.id WHERE s.id = ?");
                    $stmt->execute([$scene_id]);
                    $scene = $stmt->fetch();
                    
                    if (!$scene) {
                        $error_message = "Erreur lors du rechargement des données de la scène.";
                    }
                } else {
                    $error_message = "Erreur lors de la mise à jour du nom de la scène. Aucune ligne modifiée.";
                }
            }
        }
    }
    
    // Mettre à jour le plan de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'update_map') {
        $notes = trim($_POST['notes'] ?? '');
        
        // Upload de plan si fourni
        $newMapUrl = $scene['map_url']; // Conserver l'ancien plan par défaut
        if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['plan_file']['tmp_name'];
            $size = (int)$_FILES['plan_file']['size'];
            $originalName = $_FILES['plan_file']['name'];
            
            // Vérifier la taille (limite à 10M pour correspondre à la config PHP)
            if ($size > 10 * 1024 * 1024) {
                $error_message = "Image trop volumineuse (max 10 Mo).";
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

// Récupérer les positions des pions
$sceneTokens = [];
if ($isOwnerDM) {
    $stmt = $pdo->prepare("
        SELECT 
            st.id,
            st.token_type,
            st.entity_id,
            st.x_position,
            st.y_position,
            st.color,
            st.label,
            CASE 
                WHEN st.token_type = 'player' THEN u.username
                WHEN st.token_type = 'npc' THEN sn.name
            END AS display_name,
            CASE 
                WHEN st.token_type = 'player' THEN ch.name
                WHEN st.token_type = 'npc' THEN ch2.name
            END AS character_name
        FROM scene_tokens st
        LEFT JOIN scene_players sp ON st.token_type = 'player' AND st.entity_id = sp.player_id AND sp.scene_id = st.scene_id
        LEFT JOIN users u ON sp.player_id = u.id
        LEFT JOIN characters ch ON sp.character_id = ch.id
        LEFT JOIN scene_npcs sn ON st.token_type = 'npc' AND st.entity_id = sn.id AND sn.scene_id = st.scene_id
        LEFT JOIN characters ch2 ON sn.npc_character_id = ch2.id
        WHERE st.scene_id = ?
        ORDER BY st.token_type, st.entity_id
    ");
    $stmt->execute([$scene_id]);
    $sceneTokens = $stmt->fetchAll();
}

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
    <?php include 'includes/navbar.php'; ?>
<?php endif; ?>

<div class="container mt-4">
    <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
    <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <?php if ($isOwnerDM): ?>
                <div class="d-flex align-items-center">
                    <h1 class="me-3"><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($scene['title']); ?></h1>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editTitleForm">
                        <i class="fas fa-edit me-1"></i>Modifier le nom
                    </button>
                </div>
                <div class="collapse mt-2" id="editTitleForm">
                    <div class="card card-body">
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="action" value="update_title">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="scene_title" value="<?php echo htmlspecialchars($scene['title']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <h1><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($scene['title']); ?></h1>
            <?php endif; ?>
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
                                    <div class="col-12">
                                        <label class="form-label">Téléverser un plan (image)</label>
                                        <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
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
                            <?php if ($isOwnerDM): ?>
                                <!-- Zone des pions disponibles -->
                                <div class="mb-3">
                                    <h6>Pions disponibles</h6>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <!-- Pions des joueurs -->
                                        <?php foreach ($scenePlayers as $player): ?>
                                            <div class="token-source" 
                                                 data-token-type="player"
                                                 data-entity-id="<?php echo (int)$player['player_id']; ?>"
                                                 data-entity-name="<?php echo htmlspecialchars($player['username']); ?>"
                                                 data-character-name="<?php echo htmlspecialchars($player['character_name'] ?? ''); ?>"
                                                 style="cursor: grab; display: inline-block; margin: 5px;">
                                                <?php if (!empty($player['profile_photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" 
                                                         alt="Photo de <?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?>" 
                                                         class="rounded" 
                                                         style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #28a745;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; border: 2px solid #28a745;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="small text-center mt-1"><?php echo htmlspecialchars($player['username']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Pions des PNJ -->
                                        <?php foreach ($sceneNpcs as $npc): ?>
                                            <?php 
                                            $photo_to_show = !empty($npc['profile_photo']) ? $npc['profile_photo'] : (!empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : null);
                                            ?>
                                            <div class="token-source" 
                                                 data-token-type="npc"
                                                 data-entity-id="<?php echo (int)$npc['id']; ?>"
                                                 data-entity-name="<?php echo htmlspecialchars($npc['name']); ?>"
                                                 data-character-name="<?php echo htmlspecialchars($npc['character_name'] ?? ''); ?>"
                                                 style="cursor: grab; display: inline-block; margin: 5px;">
                                                <?php if (!empty($photo_to_show)): ?>
                                                    <img src="<?php echo htmlspecialchars($photo_to_show); ?>" 
                                                         alt="Photo de <?php echo htmlspecialchars($npc['name']); ?>" 
                                                         class="rounded" 
                                                         style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #dc3545;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; border: 2px solid #dc3545;">
                                                        <i class="fas fa-user-tie text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="small text-center mt-1"><?php echo htmlspecialchars($npc['name']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Boutons de contrôle -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="clearTokens" title="Supprimer tous les pions du plan">
                                        <i class="fas fa-trash"></i> Effacer tous les pions
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Zone du plan avec les pions placés -->
                            <div id="mapContainer" class="position-relative d-inline-block" style="max-width: 100%;">
                                <img id="sceneMap" src="<?php echo htmlspecialchars($scene['map_url']); ?>" class="img-fluid rounded" alt="Plan de la scène" style="max-height: 500px;">
                                
                                <?php if ($isOwnerDM): ?>
                                    <!-- Pions placés sur le plan -->
                                    <?php 
                                    // Debug: afficher le nombre de tokens
                                    echo "<!-- Debug: " . count($sceneTokens) . " tokens trouvés -->";
                                    ?>
                                    <?php foreach ($sceneTokens as $token): ?>
                                        <div class="token-placed" 
                                             data-token-id="<?php echo (int)$token['id']; ?>"
                                             data-token-type="<?php echo htmlspecialchars($token['token_type']); ?>"
                                             data-entity-id="<?php echo (int)$token['entity_id']; ?>"
                                             data-x="<?php echo (float)$token['x_position']; ?>"
                                             data-y="<?php echo (float)$token['y_position']; ?>"
                                             style="position: absolute; width: 40px; height: 40px; cursor: move; z-index: 10;"
                                             title="<?php echo htmlspecialchars($token['display_name'] . ($token['character_name'] ? ' (' . $token['character_name'] . ')' : '')); ?>">
                                            <?php 
                                            // Trouver la photo correspondante
                                            $token_photo = null;
                                            if ($token['token_type'] === 'player') {
                                                foreach ($scenePlayers as $player) {
                                                    if ($player['player_id'] == $token['entity_id']) {
                                                        $token_photo = $player['profile_photo'];
                                                        break;
                                                    }
                                                }
                                            } else {
                                                foreach ($sceneNpcs as $npc) {
                                                    if ($npc['id'] == $token['entity_id']) {
                                                        $token_photo = !empty($npc['profile_photo']) ? $npc['profile_photo'] : (!empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : null);
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <?php if (!empty($token_photo)): ?>
                                                <img src="<?php echo htmlspecialchars($token_photo); ?>" 
                                                     alt="Pion" 
                                                     class="rounded" 
                                                     style="width: 100%; height: 100%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 100%; height: 100%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                                    <i class="fas fa-<?php echo $token['token_type'] === 'player' ? 'user' : 'user-tie'; ?> text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
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
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start">
                                        <?php if (!empty($player['profile_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($player['profile_photo']); ?>" alt="Photo de <?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($player['username']); ?></div>
                                            <?php if ($player['character_name']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($player['character_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <?php if ($player['character_name'] && !empty($player['character_id'])): ?>
                                            <a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
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
                                <form method="POST" class="row g-2" enctype="multipart/form-data">
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
                                        <label class="form-label">Photo de profil (optionnel)</label>
                                        <input type="file" class="form-control" name="npc_photo" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
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
                                        <div class="d-flex align-items-start">
                                            <?php 
                                            // Utiliser la photo du PNJ si disponible, sinon la photo du personnage associé
                                            $photo_to_show = !empty($npc['profile_photo']) ? $npc['profile_photo'] : (!empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : null);
                                            ?>
                                            <?php if (!empty($photo_to_show)): ?>
                                                <img src="<?php echo htmlspecialchars($photo_to_show); ?>" alt="Photo de <?php echo htmlspecialchars($npc['name']); ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user-tie text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($npc['name']); ?>
                                                    <?php if (!empty($npc['npc_character_id'])): ?>
                                                        <span class="badge bg-info ms-1">perso MJ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($npc['description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <?php if (!empty($npc['npc_character_id'])): ?>
                                                <a href="view_character.php?id=<?php echo (int)$npc['npc_character_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
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
    
    <?php if ($isOwnerDM): ?>
    <script>
    console.log('=== SCRIPT DE TOKENS CHARGÉ ===');
    console.log('isOwnerDM = true');
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé');
        
        // Test simple - vérifier que les éléments existent
        const mapContainer = document.getElementById('mapContainer');
        const clearTokens = document.getElementById('clearTokens');
        
        console.log('mapContainer trouvé:', !!mapContainer);
        console.log('clearTokens trouvé:', !!clearTokens);
        
        if (!mapContainer) {
            console.error('mapContainer non trouvé - arrêt du script');
            return;
        }
        
        // Données des joueurs et PNJ
        const players = <?php echo json_encode($scenePlayers); ?>;
        const npcs = <?php echo json_encode($sceneNpcs); ?>;
        
        console.log('Joueurs:', players.length);
        console.log('PNJ:', npcs.length);
        
        // Test simple - juste afficher les tokens sources
        const tokenSources = document.querySelectorAll('.token-source');
        console.log('Tokens sources trouvés:', tokenSources.length);
        
        tokenSources.forEach((source, index) => {
            console.log(`Token source ${index}:`, source.dataset);
        });
        
        // Test de clic simple
        tokenSources.forEach(source => {
            source.addEventListener('click', function() {
                console.log('Clic sur token source:', this.dataset);
                alert('Clic détecté sur: ' + this.dataset.entityName);
            });
        });
        
        // Test du bouton clear
        if (clearTokens) {
            clearTokens.addEventListener('click', function() {
                console.log('Bouton clear cliqué');
                alert('Bouton clear cliqué');
            });
        }
    });
        
        // Rendre un pion déplaçable
        function makeTokenDraggable(token) {
            let isDragging = false;
            let startX, startY, startLeft, startTop;
            
            token.addEventListener('mousedown', function(e) {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startLeft = parseFloat(token.style.left);
                startTop = parseFloat(token.style.top);
                
                token.style.zIndex = '20';
                token.style.opacity = '0.8';
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                const containerRect = mapContainer.getBoundingClientRect();
                const newLeft = Math.max(0, Math.min(100, startLeft + (deltaX / containerRect.width) * 100));
                const newTop = Math.max(0, Math.min(100, startTop + (deltaY / containerRect.height) * 100));
                
                token.style.left = newLeft + '%';
                token.style.top = newTop + '%';
            });
            
            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    token.style.zIndex = '10';
                    token.style.opacity = '1';
                    
                    // Sauvegarder la nouvelle position
                    const x = parseFloat(token.style.left) / 100;
                    const y = parseFloat(token.style.top) / 100;
                    saveTokenPosition(token.dataset.tokenType, token.dataset.entityId, x, y);
                }
            });
        }
        
        // Sauvegarder la position d'un pion
        function saveTokenPosition(tokenType, entityId, x, y) {
            fetch('save_token_position.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    scene_id: <?php echo $scene_id; ?>,
                    token_type: tokenType,
                    entity_id: parseInt(entityId),
                    x_position: x,
                    y_position: y
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Position sauvegardée');
                } else {
                    console.error('Erreur:', data.error);
                    alert('Erreur lors de la sauvegarde: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la sauvegarde');
            });
        }
        
        // Gérer le glisser-déposer depuis les pions sources
        function setupTokenSources() {
            const tokenSources = document.querySelectorAll('.token-source');
            console.log('Configuration de', tokenSources.length, 'tokens sources');
            
            tokenSources.forEach((source, index) => {
                console.log(`Token source ${index}:`, source.dataset);
                
                source.addEventListener('mousedown', function(e) {
                    console.log('Début du drag pour:', this.dataset);
                    
                    const tokenType = this.dataset.tokenType;
                    const entityId = this.dataset.entityId;
                    const entityName = this.dataset.entityName;
                    
                    if (!tokenType || !entityId) {
                        console.error('Données manquantes pour le token source');
                        return;
                    }
                    
                    // Créer un clone pour le drag
                    const clone = this.cloneNode(true);
                    clone.style.position = 'fixed';
                    clone.style.zIndex = '1000';
                    clone.style.pointerEvents = 'none';
                    clone.style.opacity = '0.8';
                    document.body.appendChild(clone);
                    
                    let isDragging = true;
                    let startX = e.clientX;
                    let startY = e.clientY;
                    
                    function onMouseMove(e) {
                        if (!isDragging) return;
                        
                        clone.style.left = (e.clientX - clone.offsetWidth / 2) + 'px';
                        clone.style.top = (e.clientY - clone.offsetHeight / 2) + 'px';
                    }
                    
                    function onMouseUp(e) {
                        if (!isDragging) return;
                        isDragging = false;
                        
                        console.log('Fin du drag, position:', e.clientX, e.clientY);
                        
                        // Vérifier si on est sur le plan
                        const mapRect = mapContainer.getBoundingClientRect();
                        console.log('Zone du plan:', mapRect);
                        
                        if (e.clientX >= mapRect.left && e.clientX <= mapRect.right &&
                            e.clientY >= mapRect.top && e.clientY <= mapRect.bottom) {
                            
                            // Calculer la position relative sur le plan
                            const x = (e.clientX - mapRect.left) / mapRect.width;
                            const y = (e.clientY - mapRect.top) / mapRect.height;
                            
                            console.log('Position relative calculée:', x, y);
                            
                            // Créer le pion sur le plan
                            createTokenOnMap(tokenType, entityId, entityName, x, y);
                        } else {
                            console.log('Drop en dehors du plan');
                        }
                        
                        // Nettoyer
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        if (document.body.contains(clone)) {
                            document.body.removeChild(clone);
                        }
                    }
                    
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                    e.preventDefault();
                });
            });
        }
        
        // Créer un pion sur le plan
        function createTokenOnMap(tokenType, entityId, entityName, x, y) {
            // Vérifier si un pion existe déjà pour cette entité
            const existingToken = document.querySelector(`.token-placed[data-token-type="${tokenType}"][data-entity-id="${entityId}"]`);
            if (existingToken) {
                // Mettre à jour la position du pion existant
                existingToken.style.left = (x * 100) + '%';
                existingToken.style.top = (y * 100) + '%';
                saveTokenPosition(tokenType, entityId, x, y);
                return;
            }
            
            // Créer un nouveau pion
            const token = document.createElement('div');
            token.className = 'token-placed';
            token.dataset.tokenType = tokenType;
            token.dataset.entityId = entityId;
            token.dataset.x = x;
            token.dataset.y = y;
            token.style.position = 'absolute';
            token.style.left = (x * 100) + '%';
            token.style.top = (y * 100) + '%';
            token.style.width = '40px';
            token.style.height = '40px';
            token.style.cursor = 'move';
            token.style.zIndex = '10';
            token.title = entityName;
            
            // Trouver la photo correspondante
            let photoSrc = null;
            if (tokenType === 'player') {
                const player = players.find(p => p.player_id == entityId);
                if (player && player.profile_photo) {
                    photoSrc = player.profile_photo;
                }
            } else {
                const npc = npcs.find(n => n.id == entityId);
                if (npc) {
                    photoSrc = npc.profile_photo || npc.character_profile_photo;
                }
            }
            
            if (photoSrc) {
                const img = document.createElement('img');
                img.src = photoSrc;
                img.alt = 'Pion';
                img.className = 'rounded';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.border = '2px solid white';
                img.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
                token.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'bg-secondary rounded d-flex align-items-center justify-content-center';
                icon.style.width = '100%';
                icon.style.height = '100%';
                icon.style.border = '2px solid white';
                icon.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';
                icon.innerHTML = `<i class="fas fa-${tokenType === 'player' ? 'user' : 'user-tie'} text-white"></i>`;
                token.appendChild(icon);
            }
            
            mapContainer.appendChild(token);
            makeTokenDraggable(token);
            
            // Sauvegarder la position
            saveTokenPosition(tokenType, entityId, x, y);
        }
        
        // Effacer tous les pions
        clearTokens.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer tous les pions du plan ?')) {
                const tokens = document.querySelectorAll('.token-placed');
                tokens.forEach(token => token.remove());
                // TODO: Ajouter une API pour supprimer tous les pions
            }
        });
        
        // Initialiser
        initializeTokens();
        setupTokenSources();
    });
    </script>
    <?php endif; ?>
    
    <script>
    console.log('Script de debug - page chargée');
    console.log('isOwnerDM = <?php echo $isOwnerDM ? 'true' : 'false'; ?>');
    </script>
</body>
</html>
<?php endif; ?>
