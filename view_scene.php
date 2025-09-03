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
$stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? ORDER BY u.username ASC");
$stmt->execute([$scene_id]);
$scenePlayers = $stmt->fetchAll();

// Récupérer les PNJ de cette scène
$stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
$stmt->execute([$scene_id]);
$sceneNpcs = $stmt->fetchAll();

                // Récupérer les monstres de cette scène
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM scene_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.scene_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
                $stmt->execute([$scene_id]);
                $sceneMonsters = $stmt->fetchAll();

// Traitements POST pour ajouter des PNJ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerDM) {

    
    // Ajouter un personnage du MJ comme PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'add_dm_character_npc') {
        $character_id = (int)($_POST['dm_character_id'] ?? 0);
        if ($character_id > 0) {
            $chk = $pdo->prepare("SELECT name FROM characters WHERE id = ? AND user_id = ?");
            $chk->execute([$character_id, $dm_id]);
            $char = $chk->fetch();
            if ($char) {
                $ins = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, npc_character_id) VALUES (?, ?, ?)");
                $ins->execute([$scene_id, $char['name'], $character_id]);
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
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM scene_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.scene_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
        $stmt->execute([$scene_id]);
        $sceneNpcs = $stmt->fetchAll();
    }
    
    // Ajouter un monstre du bestiaire
    if (isset($_POST['action']) && $_POST['action'] === 'add_monster') {
        $monster_id = (int)($_POST['monster_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($monster_id > 0 && $quantity > 0) {
            // Récupérer les informations du monstre
            $stmt = $pdo->prepare("SELECT name FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$monster_id]);
            $monster = $stmt->fetch();
            
            if ($monster) {
                // Créer une ligne individuelle pour chaque monstre
                for ($i = 0; $i < $quantity; $i++) {
                    $monster_name = $monster['name'];
                    if ($quantity > 1) {
                        $monster_name .= " #" . ($i + 1);
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$scene_id, $monster_name, $monster_id]);
                }
                
                $success_message = $quantity . " monstre(s) ajouté(s) à la scène.";
                
                // Recharger les monstres
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM scene_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.scene_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
                $stmt->execute([$scene_id]);
                $sceneMonsters = $stmt->fetchAll();
            } else {
                $error_message = "Monstre introuvable.";
            }
        } else {
            $error_message = "Veuillez sélectionner un monstre et spécifier une quantité valide.";
        }
    }
    
    // Retirer un monstre de la scène
    if (isset($_POST['action']) && $_POST['action'] === 'remove_monster' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $stmt = $pdo->prepare("DELETE FROM scene_npcs WHERE scene_id = ? AND id = ? AND monster_id IS NOT NULL");
        $stmt->execute([$scene_id, $npc_id]);
        $success_message = "Monstre retiré de la scène.";
        
        // Recharger les monstres
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM scene_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.scene_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
        $stmt->execute([$scene_id]);
        $sceneMonsters = $stmt->fetchAll();
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
    
    // Attribuer un objet magique à un PNJ ou personnage joueur
    if (isset($_POST['action']) && $_POST['action'] === 'assign_magical_item') {
        $item_id = $_POST['item_id'];
        $item_name = $_POST['item_name'];
        $assign_target = $_POST['assign_target'];
        $assign_notes = $_POST['assign_notes'] ?? '';
        
        if (!empty($assign_target)) {
            // Décomposer la cible (player_123, npc_456, monster_789)
            $target_parts = explode('_', $assign_target);
            $target_type = $target_parts[0];
            $target_id = (int)$target_parts[1];
            
            // Récupérer les informations de l'objet magique depuis la base de données
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
            $stmt->execute([$item_id]);
            $item_info = $stmt->fetch();
            
            if (!$item_info) {
                $error_message = "Objet magique introuvable.";
            } else {
                $target_name = '';
                $insert_success = false;
                
                switch ($target_type) {
                    case 'player':
                        // Récupérer les informations du personnage joueur
                        $stmt = $pdo->prepare("SELECT u.username, ch.id AS character_id, ch.name AS character_name FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id = ? AND sp.player_id = ?");
                        $stmt->execute([$scene_id, $target_id]);
                        $target = $stmt->fetch();
                        
                        if ($target && $target['character_id']) {
                            // Ajouter l'objet à l'équipement du personnage
                            $stmt = $pdo->prepare("INSERT INTO character_equipment (character_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target['character_id'],
                                $item_id,
                                $item_info['nom'],
                                $item_info['type'],
                                $item_info['description'],
                                $item_info['source'],
                                $assign_notes,
                                'Attribution MJ - Scène ' . $scene['title']
                            ]);
                            $insert_success = true;
                            $target_name = $target['character_name'] ?: $target['username'];
                        } else {
                            $error_message = "Personnage joueur invalide ou sans personnage créé.";
                        }
                        break;
                        
                    case 'npc':
                        // Récupérer les informations du PNJ
                        $stmt = $pdo->prepare("SELECT name FROM scene_npcs WHERE id = ? AND scene_id = ?");
                        $stmt->execute([$target_id, $scene_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter l'objet à l'équipement du PNJ
                            $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, scene_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $scene_id,
                                $item_id,
                                $item_info['nom'],
                                $item_info['type'],
                                $item_info['description'],
                                $item_info['source'],
                                $assign_notes,
                                'Attribution MJ - Scène ' . $scene['title']
                            ]);
                            $insert_success = true;
                            $target_name = $target['name'];
                        } else {
                            $error_message = "PNJ introuvable.";
                        }
                        break;
                        
                    case 'monster':
                        // Récupérer les informations du monstre
                        $stmt = $pdo->prepare("SELECT name FROM scene_npcs WHERE id = ? AND scene_id = ?");
                        $stmt->execute([$target_id, $scene_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter l'objet à l'équipement du monstre
                            $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, scene_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $scene_id,
                                $item_id,
                                $item_info['nom'],
                                $item_info['type'],
                                $item_info['description'],
                                $item_info['source'],
                                $assign_notes,
                                'Attribution MJ - Scène ' . $target['name']
                            ]);
                            $insert_success = true;
                            $target_name = $target['name'];
                        } else {
                            $error_message = "Monstre introuvable.";
                        }
                        break;
                        
                    default:
                        $error_message = "Type de cible invalide.";
                        break;
                }
                
                if ($insert_success && $target_name) {
                    $success_message = "L'objet magique \"{$item_name}\" a été attribué à {$target_name} et ajouté à son équipement.";
                    if (!empty($assign_notes)) {
                        $success_message .= " Notes: {$assign_notes}";
                    }
                } elseif (!$insert_success && !isset($error_message)) {
                    $error_message = "Erreur lors de l'ajout de l'objet à l'équipement.";
                }
            }
        } else {
            $error_message = "Veuillez sélectionner un destinataire pour l'objet.";
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
            <p class="text-muted mb-0">
                Session: <?php echo htmlspecialchars($scene['session_title']); ?> • MJ: <?php echo htmlspecialchars($scene['dm_username']); ?>
                <button class="btn btn-sm btn-outline-danger ms-2" type="button" data-bs-toggle="modal" data-bs-target="#poisonSearchModal">
                    <i class="fas fa-skull-crossbones me-1"></i>Poison
                </button>
                <button class="btn btn-sm btn-outline-primary ms-2" type="button" data-bs-toggle="modal" data-bs-target="#magicalItemSearchModal">
                    <i class="fas fa-gem me-1"></i>Objet Magique
                </button>
            </p>
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
                                <?php if (!empty($dmCharacters)): ?>
                                    <form method="POST" action="" class="row g-2">
                                        <input type="hidden" name="action" value="add_dm_character_npc">
                                        <div class="col-md-8">
                                            <label class="form-label">Choisir un de vos personnages</label>
                                            <select name="dm_character_id" class="form-select" required>
                                                <option value="" disabled selected>Sélectionner un personnage</option>
                                                <?php foreach ($dmCharacters as $dc): ?>
                                                    <option value="<?php echo (int)$dc['id']; ?>"><?php echo htmlspecialchars($dc['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-user-plus"></i> Ajouter
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Vous devez d'abord créer des personnages dans votre profil pour pouvoir les utiliser comme PNJ.
                                        <br>
                                        <a href="characters.php" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-plus me-1"></i>Créer un personnage
                                        </a>
                                    </div>
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
            
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Monstres</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addMonsterModal">
                            <i class="fas fa-plus me-1"></i>Ajouter monstre
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($sceneMonsters)): ?>
                        <p class="text-muted">Aucun monstre dans cette scène.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($sceneMonsters as $monster): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-danger rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-dragon text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($monster['type']); ?> • 
                                                    <?php echo htmlspecialchars($monster['size']); ?> • 
                                                    CR <?php echo htmlspecialchars($monster['challenge_rating']); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    CA <?php echo htmlspecialchars($monster['armor_class']); ?> • 
                                                    PV <?php echo htmlspecialchars($monster['hit_points']); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="bestiary.php?search=<?php echo urlencode($monster['name']); ?>" class="btn btn-sm btn-outline-primary" title="Voir dans le bestiaire" target="_blank">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <?php if ($isOwnerDM): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($monster['name']); ?> de cette scène ?');">
                                                    <input type="hidden" name="action" value="remove_monster">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$monster['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer de la scène">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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

<!-- Modal pour ajouter un monstre -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="addMonsterModal" tabindex="-1" aria-labelledby="addMonsterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMonsterModalLabel">
                    <i class="fas fa-dragon me-2"></i>Ajouter un monstre
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addMonsterForm">
                    <input type="hidden" name="action" value="add_monster">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="monsterSearch" class="form-label">Rechercher un monstre</label>
                            <input type="text" class="form-control" id="monsterSearch" placeholder="Nom du monstre...">
                        </div>
                        <div class="col-md-4">
                            <label for="monsterQuantity" class="form-label">Quantité</label>
                            <input type="number" class="form-control" id="monsterQuantity" name="quantity" value="1" min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div id="monsterResults" class="list-group" style="max-height: 300px; overflow-y: auto;">
                            <!-- Les résultats de recherche seront affichés ici -->
                        </div>
                    </div>
                    
                    <input type="hidden" name="monster_id" id="selectedMonsterId" required>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="addMonsterForm" class="btn btn-primary" id="addMonsterBtn" disabled>
                    <i class="fas fa-plus me-1"></i>Ajouter le monstre
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour rechercher des poisons -->
<div class="modal fade" id="poisonSearchModal" tabindex="-1" aria-labelledby="poisonSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="poisonSearchModalLabel">
                    <i class="fas fa-skull-crossbones me-2"></i>Recherche de poisons
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="poisonSearch" class="form-label">Rechercher un poison</label>
                        <input type="text" class="form-control" id="poisonSearch" placeholder="Nom, type ou description du poison...">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div id="poisonResults" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour rechercher des objets magiques -->
<div class="modal fade" id="magicalItemSearchModal" tabindex="-1" aria-labelledby="magicalItemSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="magicalItemSearchModalLabel">
                    <i class="fas fa-gem me-2"></i>Recherche d'objets magiques
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="magicalItemSearch" class="form-label">Rechercher un objet magique</label>
                        <input type="text" class="form-control" id="magicalItemSearch" placeholder="Nom, type ou description de l'objet magique...">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div id="magicalItemResults" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Astuce :</strong> Cliquez sur le bouton "Attribuer" à côté d'un objet pour l'assigner à un PNJ ou un personnage joueur de cette scène.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour attribuer un objet magique -->
<div class="modal fade" id="assignItemModal" tabindex="-1" aria-labelledby="assignItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignItemModalLabel">
                    <i class="fas fa-gift me-2"></i>Attribuer un objet magique
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignItemForm">
                    <input type="hidden" name="action" value="assign_magical_item">
                    <input type="hidden" name="item_id" id="selectedItemId">
                    <input type="hidden" name="item_name" id="selectedItemName">
                    
                    <div class="mb-3">
                        <label class="form-label">Objet sélectionné</label>
                        <div class="form-control-plaintext" id="selectedItemDisplay"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignTarget" class="form-label">Attribuer à</label>
                        <select class="form-select" name="assign_target" id="assignTarget" required>
                            <option value="">Sélectionner un destinataire...</option>
                            
                            <!-- Personnages joueurs -->
                            <?php if (!empty($scenePlayers)): ?>
                                <optgroup label="Personnages joueurs">
                                    <?php foreach ($scenePlayers as $player): ?>
                                        <option value="player_<?php echo (int)$player['player_id']; ?>">
                                            <?php echo htmlspecialchars($player['username']); ?>
                                            <?php if (!empty($player['character_name'])): ?>
                                                (<?php echo htmlspecialchars($player['character_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <!-- PNJ -->
                            <?php if (!empty($sceneNpcs)): ?>
                                <optgroup label="PNJ">
                                    <?php foreach ($sceneNpcs as $npc): ?>
                                        <option value="npc_<?php echo (int)$npc['id']; ?>">
                                            <?php echo htmlspecialchars($npc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <!-- Monstres -->
                            <?php if (!empty($sceneMonsters)): ?>
                                <optgroup label="Monstres">
                                    <?php foreach ($sceneMonsters as $monster): ?>
                                        <option value="monster_<?php echo (int)$monster['id']; ?>">
                                            <?php echo htmlspecialchars($monster['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignNotes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" name="assign_notes" id="assignNotes" rows="3" placeholder="Comment l'objet a-t-il été obtenu ?..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="assignItemForm" class="btn btn-primary">
                    <i class="fas fa-gift me-1"></i>Attribuer l'objet
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (!$isModal): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Gestion de la recherche de monstres
    document.addEventListener('DOMContentLoaded', function() {
        const monsterSearch = document.getElementById('monsterSearch');
        const monsterResults = document.getElementById('monsterResults');
        const selectedMonsterId = document.getElementById('selectedMonsterId');
        const addMonsterBtn = document.getElementById('addMonsterBtn');
        let searchTimeout;

        if (monsterSearch) {
            monsterSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    monsterResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
                    return;
                }

                searchTimeout = setTimeout(function() {
                    searchMonsters(query);
                }, 300);
            });
        }

        function searchMonsters(query) {
            monsterResults.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</div>';
            
            fetch('search_monsters.php?q=' + encodeURIComponent(query), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        monsterResults.innerHTML = '<div class="text-muted text-center p-3">Aucun monstre trouvé.</div>';
                    } else {
                        displayMonsterResults(data);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    monsterResults.innerHTML = '<div class="text-danger text-center p-3">Erreur lors de la recherche.</div>';
                });
        }

        function displayMonsterResults(monsters) {
            monsterResults.innerHTML = '';
            
            monsters.forEach(monster => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold">${monster.name}</div>
                            <small class="text-muted">
                                ${monster.type} • ${monster.size} • CR ${monster.challenge_rating}
                            </small>
                            <br>
                            <small class="text-muted">
                                CA ${monster.armor_class} • PV ${monster.hit_points}
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary select-monster" 
                                data-monster-id="${monster.id}" data-monster-name="${monster.name}">
                            <i class="fas fa-plus me-1"></i>Sélectionner
                        </button>
                    </div>
                `;
                
                item.querySelector('.select-monster').addEventListener('click', function() {
                    const monsterId = this.getAttribute('data-monster-id');
                    const monsterName = this.getAttribute('data-monster-name');
                    
                    selectedMonsterId.value = monsterId;
                    monsterSearch.value = monsterName;
                    addMonsterBtn.disabled = false;
                    
                    // Mettre en surbrillance la sélection
                    monsterResults.querySelectorAll('.list-group-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    item.classList.add('active');
                });
                
                monsterResults.appendChild(item);
            });
        }

        // Réinitialiser la sélection quand la modale s'ouvre
        const addMonsterModal = document.getElementById('addMonsterModal');
        if (addMonsterModal) {
            addMonsterModal.addEventListener('show.bs.modal', function() {
                monsterSearch.value = '';
                monsterResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
                selectedMonsterId.value = '';
                addMonsterBtn.disabled = true;
            });
        }

        // Gestion de la recherche de poisons
        const poisonSearch = document.getElementById('poisonSearch');
        const poisonResults = document.getElementById('poisonResults');
        let poisonSearchTimeout;

        if (poisonSearch) {
            poisonSearch.addEventListener('input', function() {
                clearTimeout(poisonSearchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    poisonResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
                    return;
                }

                poisonSearchTimeout = setTimeout(function() {
                    searchPoisons(query);
                }, 300);
            });
        }

        function searchPoisons(query) {
            poisonResults.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</div>';
            
            fetch('search_poisons.php?q=' + encodeURIComponent(query), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        poisonResults.innerHTML = '<div class="text-muted text-center p-3">Aucun poison trouvé.</div>';
                    } else {
                        displayPoisonResults(data);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    poisonResults.innerHTML = '<div class="text-danger text-center p-3">Erreur lors de la recherche.</div>';
                });
        }

        function displayPoisonResults(poisons) {
            poisonResults.innerHTML = '';
            
            poisons.forEach(poison => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="fw-bold text-danger">${poison.nom}</div>
                                <small class="text-muted">${poison.type}</small>
                            </div>
                            <div class="text-muted small mb-2">Source: ${poison.source}</div>
                            <div class="mb-2">${poison.description}</div>
                            <div class="text-muted small">
                                <strong>Clé:</strong> ${poison.cle}
                            </div>
                        </div>
                    </div>
                `;
                
                poisonResults.appendChild(item);
            });
        }

        // Réinitialiser la recherche quand la modale des poisons s'ouvre
        const poisonSearchModal = document.getElementById('poisonSearchModal');
        if (poisonSearchModal) {
            poisonSearchModal.addEventListener('show.bs.modal', function() {
                poisonSearch.value = '';
                poisonResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
            });
        }

        // Gestion de la recherche d'objets magiques
        const magicalItemSearch = document.getElementById('magicalItemSearch');
        const magicalItemResults = document.getElementById('magicalItemResults');
        let magicalItemSearchTimeout;

        if (magicalItemSearch) {
            magicalItemSearch.addEventListener('input', function() {
                clearTimeout(magicalItemSearchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    magicalItemResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
                    return;
                }

                magicalItemSearchTimeout = setTimeout(function() {
                    searchMagicalItems(query);
                }, 300);
            });
        }

        function searchMagicalItems(query) {
            magicalItemResults.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</div>';
            
            fetch('search_magical_items.php?q=' + encodeURIComponent(query), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        magicalItemResults.innerHTML = '<div class="text-muted text-center p-3">Aucun objet magique trouvé.</div>';
                    } else {
                        displayMagicalItemResults(data);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    magicalItemResults.innerHTML = '<div class="text-danger text-center p-3">Erreur lors de la recherche.</div>';
                });
        }

        function displayMagicalItemResults(items) {
            magicalItemResults.innerHTML = '';
            
            items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'list-group-item';
                itemElement.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="fw-bold text-primary">${item.nom}</div>
                                <small class="text-muted">${item.type}</small>
                            </div>
                            <div class="text-muted small mb-2">Source: ${item.source}</div>
                            <div class="mb-2">${item.description}</div>
                            <div class="text-muted small">
                                <strong>Clé:</strong> ${item.cle}
                            </div>
                        </div>
                        <div class="ms-3">
                            <button type="button" class="btn btn-sm btn-outline-success assign-item-btn" 
                                    data-item-id="${item.id}" 
                                    data-item-name="${item.nom}"
                                    data-item-type="${item.type}">
                                <i class="fas fa-gift me-1"></i>Attribuer
                            </button>
                        </div>
                    </div>
                `;
                
                // Ajouter l'événement click pour le bouton d'attribution
                const assignBtn = itemElement.querySelector('.assign-item-btn');
                assignBtn.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const itemName = this.getAttribute('data-item-name');
                    const itemType = this.getAttribute('data-item-type');
                    
                    // Remplir la modale d'attribution
                    document.getElementById('selectedItemId').value = itemId;
                    document.getElementById('selectedItemName').value = itemName;
                    document.getElementById('selectedItemDisplay').innerHTML = `
                        <strong>${itemName}</strong><br>
                        <small class="text-muted">${itemType}</small>
                    `;
                    
                    // Fermer la modale de recherche et ouvrir la modale d'attribution
                    const searchModal = bootstrap.Modal.getInstance(document.getElementById('magicalItemSearchModal'));
                    searchModal.hide();
                    
                    const assignModal = new bootstrap.Modal(document.getElementById('assignItemModal'));
                    assignModal.show();
                });
                
                magicalItemResults.appendChild(itemElement);
            });
        }

        // Réinitialiser la recherche quand la modale des objets magiques s'ouvre
        const magicalItemSearchModal = document.getElementById('magicalItemSearchModal');
        if (magicalItemSearchModal) {
            magicalItemSearchModal.addEventListener('show.bs.modal', function() {
                magicalItemSearch.value = '';
                magicalItemResults.innerHTML = '<div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>';
            });
        }
    });
    </script>

</body>
</html>
<?php endif; ?>
