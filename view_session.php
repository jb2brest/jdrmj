<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$session_id = (int)$_GET['id'];
$isModal = isset($_GET['modal']);

// Charger la session et sa campagne
$stmt = $pdo->prepare("SELECT gs.*, c.title AS campaign_title, c.id AS campaign_id, u.username AS dm_username FROM game_sessions gs JOIN users u ON gs.dm_id = u.id LEFT JOIN campaigns c ON gs.campaign_id = c.id WHERE gs.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: index.php');
    exit();
}

$dm_id = (int)$session['dm_id'];
$isOwnerDM = (isDM() && $_SESSION['user_id'] === $dm_id);

// Autoriser également les joueurs inscrits à voir la session
$canView = $isOwnerDM;
if (!$canView) {
    $stmt = $pdo->prepare("SELECT 1 FROM session_registrations WHERE session_id = ? AND player_id = ? LIMIT 1");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $canView = (bool)$stmt->fetch();
}

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Actions MJ sur les inscriptions et session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerDM) {
    if (isset($_POST['action']) && isset($_POST['registration_id'])) {
        $registration_id = (int)$_POST['registration_id'];
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE session_registrations SET status = 'approved' WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription approuvée.";
        } elseif ($_POST['action'] === 'decline') {
            $stmt = $pdo->prepare("UPDATE session_registrations SET status = 'declined' WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription refusée.";
        } elseif ($_POST['action'] === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM session_registrations WHERE id = ? AND session_id = ?");
            $stmt->execute([$registration_id, $session_id]);
            $success_message = "Inscription supprimée.";
        }
    }

    // Ajouter un candidat approuvé de la campagne à cette session
    if (isset($_POST['action']) && $_POST['action'] === 'add_applicant' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        if (!empty($session['campaign_id'])) {
            $stmt = $pdo->prepare("SELECT ca.player_id, ca.character_id FROM campaign_applications ca WHERE ca.id = ? AND ca.campaign_id = ? AND ca.status = 'approved' AND ca.character_id IS NOT NULL");
            $stmt->execute([$application_id, $session['campaign_id']]);
            $app = $stmt->fetch();
            if ($app) {
                $player_id = (int)$app['player_id'];
                $character_id = (int)$app['character_id'];
                $check = $pdo->prepare("SELECT id FROM session_registrations WHERE session_id = ? AND player_id = ? LIMIT 1");
                $check->execute([$session_id, $player_id]);
                if ($check->fetch()) {
                    $error_message = "Le joueur est déjà inscrit à cette session.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO session_registrations (session_id, player_id, character_id, status) VALUES (?, ?, ?, 'approved')");
                    $stmt->execute([$session_id, $player_id, $character_id]);
                    $success_message = "Candidat ajouté à la session.";
                }
            } else {
                $error_message = "Candidature invalide (doit être approuvée avec un personnage).";
            }
        } else {
            $error_message = "Cette session n'est pas rattachée à une campagne.";
        }
    }

    // Démarrer la session (changer le statut à in_progress)
    if (isset($_POST['action']) && $_POST['action'] === 'start_session') {
        if (in_array($session['status'], ['planning', 'recruiting'])) {
            $stmt = $pdo->prepare("UPDATE game_sessions SET status = 'in_progress' WHERE id = ? AND dm_id = ?");
            $stmt->execute([$session_id, $dm_id]);
            $success_message = "Session démarrée.";
            $stmt = $pdo->prepare("SELECT gs.*, c.title AS campaign_title, c.id AS campaign_id, u.username AS dm_username FROM game_sessions gs JOIN users u ON gs.dm_id = u.id LEFT JOIN campaigns c ON gs.campaign_id = c.id WHERE gs.id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
        } else {
            $error_message = "La session ne peut pas être démarrée depuis son statut actuel.";
        }
    }

    // Mettre à jour le contexte de début
    if (isset($_POST['action']) && $_POST['action'] === 'save_start_context') {
        $start_context = trim($_POST['start_context'] ?? '');
        $stmt = $pdo->prepare("UPDATE game_sessions SET start_context = ? WHERE id = ? AND dm_id = ?");
        $stmt->execute([$start_context, $session_id, $dm_id]);
        $success_message = "Contexte de début sauvegardé.";
        $session['start_context'] = $start_context;
    }

    // Créer une scène
    if (isset($_POST['action']) && $_POST['action'] === 'create_scene') {
        $scene_title = trim($_POST['scene_title'] ?? '');
        $map_url = trim($_POST['map_url'] ?? '');
        $npcs_raw = trim($_POST['npcs'] ?? '');
                    if ($scene_title === '') {
                $error_message = "Le titre de la scène est obligatoire.";
            } else {
                // Upload de plan si fourni
                if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['plan_file']['tmp_name'];
                    $size = (int)$_FILES['plan_file']['size'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                    if (!isset($allowed[$mime])) {
                        $error_message = "Format d'image non supporté.";
                    } elseif ($size > 2 * 1024 * 1024) {
                        $error_message = "Image trop volumineuse (max 2 Mo).";
                    } else {
                        $ext = $allowed[$mime];
                        $subdir = 'uploads/plans/' . date('Y/m');
                        $diskDir = __DIR__ . '/' . $subdir;
                        if (!is_dir($diskDir)) {
                            mkdir($diskDir, 0755, true);
                        }
                        $basename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $diskPath = $diskDir . '/' . $basename;
                        $webPath = $subdir . '/' . $basename;
                        if (move_uploaded_file($tmp, $diskPath)) {
                            $map_url = $webPath;
                        } else {
                            $error_message = "Échec de l'upload du plan.";
                        }
                    }
                }
            }
        if (!isset($error_message)) {
            // position = max(position)+1
            $posStmt = $pdo->prepare("SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM scenes WHERE session_id = ?");
            $posStmt->execute([$session_id]);
            $nextPos = (int)$posStmt->fetch()['next_pos'];
            $stmt = $pdo->prepare("INSERT INTO scenes (session_id, title, map_url, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$session_id, $scene_title, $map_url, $nextPos]);
            $scene_id = (int)$pdo->lastInsertId();
            // Ajouter par défaut tous les joueurs approuvés de la session
            $players = $pdo->prepare("SELECT player_id, character_id FROM session_registrations WHERE session_id = ? AND status = 'approved'");
            $players->execute([$session_id]);
            foreach ($players->fetchAll() as $p) {
                $ins = $pdo->prepare("INSERT IGNORE INTO scene_players (scene_id, player_id, character_id) VALUES (?, ?, ?)");
                $ins->execute([$scene_id, (int)$p['player_id'], (int)$p['character_id']]);
            }
            // Ajouter PNJ (séparés par lignes)
            if ($npcs_raw !== '') {
                $lines = preg_split("/\r?\n/", $npcs_raw);
                foreach ($lines as $line) {
                    $name = trim($line);
                    if ($name !== '') {
                        $ins = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name) VALUES (?, ?)");
                        $ins->execute([$scene_id, $name]);
                    }
                }
            }
            $success_message = "Scène créée.";
        }
    }

    // Mettre à jour une scène
    if (isset($_POST['action']) && $_POST['action'] === 'update_scene') {
        $scene_id = (int)($_POST['scene_id'] ?? 0);
        $scene_title = trim($_POST['scene_title'] ?? '');
        $map_url = trim($_POST['map_url'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $present_players = isset($_POST['present_players']) && is_array($_POST['present_players']) ? array_map('intval', $_POST['present_players']) : [];
        $npcs_raw = trim($_POST['npcs'] ?? '');

        // Vérifier que la scène appartient à cette session
        $chk = $pdo->prepare("SELECT id, map_url FROM scenes WHERE id = ? AND session_id = ?");
        $chk->execute([$scene_id, $session_id]);
        $sceneRow = $chk->fetch();
        if (!$sceneRow) {
            $error_message = "Scène introuvable.";
        } elseif ($scene_title === '') {
            $error_message = "Le titre de la scène est obligatoire.";
        } else {
            // Upload de plan si fourni
            $newMapUrl = $map_url;
            if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['plan_file']['tmp_name'];
                $size = (int)$_FILES['plan_file']['size'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmp);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $error_message = "Format d'image non supporté.";
                } elseif ($size > 2 * 1024 * 1024) {
                    $error_message = "Image trop volumineuse (max 2 Mo).";
                } else {
                    $ext = $allowed[$mime];
                    $subdir = 'uploads/plans/' . date('Y/m');
                    $diskDir = __DIR__ . '/' . $subdir;
                    if (!is_dir($diskDir)) {
                        mkdir($diskDir, 0755, true);
                    }
                    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
                    $diskPath = $diskDir . '/' . $basename;
                    $webPath = $subdir . '/' . $basename;
                    if (move_uploaded_file($tmp, $diskPath)) {
                        $newMapUrl = $webPath;
                    } else {
                        $error_message = "Échec de l'upload du plan.";
                    }
                }
            }
            if (!isset($error_message)) {
                // Si aucun nouveau lien fourni et pas d'upload, conserver l'ancien
                if ($newMapUrl === '') { $newMapUrl = $sceneRow['map_url']; }
                $upd = $pdo->prepare("UPDATE scenes SET title = ?, map_url = ?, notes = ? WHERE id = ? AND session_id = ?");
                $upd->execute([$scene_title, $newMapUrl, $notes, $scene_id, $session_id]);

                // Mettre à jour joueurs présents: garder uniquement sélection
                // Construire mapping player_id -> character_id depuis inscriptions approuvées
                $reg = $pdo->prepare("SELECT player_id, username, character_id, ch.name AS character_name FROM session_registrations sr JOIN users u ON sr.player_id = u.id LEFT JOIN characters ch ON sr.character_id = ch.id WHERE sr.session_id = ? AND sr.status = 'approved' ORDER BY u.username ASC");
                $reg->execute([$session_id]);
                $map = [];
                foreach ($reg->fetchAll() as $r) { $map[(int)$r['player_id']] = (int)$r['character_id']; }
                // Supprimer ceux non sélectionnés
                $inClause = !empty($present_players) ? (' AND player_id NOT IN (' . implode(',', array_fill(0, count($present_players), '?')) . ')') : '';
                $delArgs = array_merge([$scene_id], $present_players);
                $del = $pdo->prepare("DELETE FROM scene_players WHERE scene_id = ?" . $inClause);
                $del->execute($delArgs);
                // Ajouter ceux sélectionnés (UPSERT naive via INSERT IGNORE)
                foreach ($present_players as $pid) {
                    $cid = $map[$pid] ?? null;
                    $ins = $pdo->prepare("INSERT IGNORE INTO scene_players (scene_id, player_id, character_id) VALUES (?, ?, ?)");
                    $ins->execute([$scene_id, $pid, $cid]);
                }

                // Remplacer PNJ texte (préserver PNJ liés à un perso MJ)
                $pdo->prepare("DELETE FROM scene_npcs WHERE scene_id = ? AND npc_character_id IS NULL")->execute([$scene_id]);
                if ($npcs_raw !== '') {
                    $lines = preg_split("/\r?\n/", $npcs_raw);
                    foreach ($lines as $line) {
                        $name = trim($line);
                        if ($name !== '') {
                            $pdo->prepare("INSERT INTO scene_npcs (scene_id, name) VALUES (?, ?)")->execute([$scene_id, $name]);
                        }
                    }
                }

                $success_message = "Scène mise à jour.";
            }
        }
    }

    // Changer l'ordre d'une scène
    if (isset($_POST['action']) && $_POST['action'] === 'move_scene' && isset($_POST['scene_id']) && isset($_POST['direction'])) {
        $scene_id = (int)$_POST['scene_id'];
        $direction = $_POST['direction'] === 'up' ? 'up' : 'down';
        // Récupérer la scène
        $cur = $pdo->prepare("SELECT id, position FROM scenes WHERE id = ? AND session_id = ?");
        $cur->execute([$scene_id, $session_id]);
        $curScene = $cur->fetch();
        if ($curScene) {
            if ($direction === 'up' && $curScene['position'] > 0) {
                // Trouver la scène précédente
                $prev = $pdo->prepare("SELECT id, position FROM scenes WHERE session_id = ? AND position = ?");
                $prev->execute([$session_id, (int)$curScene['position'] - 1]);
                $prevScene = $prev->fetch();
                if ($prevScene) {
                    // Swap positions
                    $pdo->beginTransaction();
                    $u1 = $pdo->prepare("UPDATE scenes SET position = ? WHERE id = ?");
                    $u1->execute([(int)$curScene['position'] - 1, (int)$curScene['id']]);
                    $u2 = $pdo->prepare("UPDATE scenes SET position = ? WHERE id = ?");
                    $u2->execute([(int)$prevScene['position'] + 1, (int)$prevScene['id']]);
                    $pdo->commit();
                    $success_message = "Scène déplacée.";
                }
            } elseif ($direction === 'down') {
                // Trouver la scène suivante
                $next = $pdo->prepare("SELECT id, position FROM scenes WHERE session_id = ? AND position = ?");
                $next->execute([$session_id, (int)$curScene['position'] + 1]);
                $nextScene = $next->fetch();
                if ($nextScene) {
                    $pdo->beginTransaction();
                    $u1 = $pdo->prepare("UPDATE scenes SET position = ? WHERE id = ?");
                    $u1->execute([(int)$curScene['position'] + 1, (int)$curScene['id']]);
                    $u2 = $pdo->prepare("UPDATE scenes SET position = ? WHERE id = ?");
                    $u2->execute([(int)$nextScene['position'] - 1, (int)$nextScene['id']]);
                    $pdo->commit();
                    $success_message = "Scène déplacée.";
                }
            }
        }
        // Recharger scenes après mouvement
        $stmt = $pdo->prepare("SELECT * FROM scenes WHERE session_id = ? ORDER BY position ASC, created_at ASC");
        $stmt->execute([$session_id]);
        $scenes = $stmt->fetchAll();
    }

    // Ajouter un personnage du MJ comme PNJ à une scène
    if (isset($_POST['action']) && $_POST['action'] === 'add_dm_character_npc') {
        $scene_id = (int)($_POST['scene_id'] ?? 0);
        $character_id = (int)($_POST['dm_character_id'] ?? 0);
        if ($scene_id > 0 && $character_id > 0) {
            $chk = $pdo->prepare("SELECT 1 FROM scenes WHERE id = ? AND session_id = ?");
            $chk->execute([$scene_id, $session_id]);
            if ($chk->fetch()) {
                $chk2 = $pdo->prepare("SELECT name FROM characters WHERE id = ? AND user_id = ?");
                $chk2->execute([$character_id, $dm_id]);
                $char = $chk2->fetch();
                if ($char) {
                    $npc_name = trim($_POST['npc_name'] ?? '');
                    if ($npc_name === '') { $npc_name = $char['name']; }
                    $ins = $pdo->prepare("INSERT INTO scene_npcs (scene_id, name, npc_character_id) VALUES (?, ?, ?)");
                    $ins->execute([$scene_id, $npc_name, $character_id]);
                    $success_message = "PNJ (personnage du MJ) ajouté à la scène.";
                } else {
                    $error_message = "Personnage invalide (doit appartenir au MJ).";
                }
            } else {
                $error_message = "Scène introuvable.";
            }
        } else {
            $error_message = "Paramètres invalides pour l'ajout de PNJ.";
        }
    }

    // Supprimer une scène
    if (isset($_POST['action']) && $_POST['action'] === 'delete_scene' && isset($_POST['scene_id'])) {
        $scene_id = (int)$_POST['scene_id'];
        
        // Vérifier que la scène appartient à cette session
        $chk = $pdo->prepare("SELECT id, title FROM scenes WHERE id = ? AND session_id = ?");
        $chk->execute([$scene_id, $session_id]);
        $sceneToDelete = $chk->fetch();
        
        if (!$sceneToDelete) {
            $error_message = "Scène introuvable ou accès refusé.";
        } else {
            // Supprimer la scène et toutes ses données associées
            $pdo->beginTransaction();
            try {
                // Supprimer les joueurs de la scène
                $pdo->prepare("DELETE FROM scene_players WHERE scene_id = ?")->execute([$scene_id]);
                
                // Supprimer les PNJ de la scène
                $pdo->prepare("DELETE FROM scene_npcs WHERE scene_id = ?")->execute([$scene_id]);
                
                // Supprimer la scène elle-même
                $pdo->prepare("DELETE FROM scenes WHERE id = ? AND session_id = ?")->execute([$scene_id, $session_id]);
                
                $pdo->commit();
                $success_message = "Scène '" . htmlspecialchars($sceneToDelete['title']) . "' supprimée avec succès.";
                
                // Recharger les scènes
                $stmt = $pdo->prepare("SELECT * FROM scenes WHERE session_id = ? ORDER BY position ASC, created_at ASC");
                $stmt->execute([$session_id]);
                $scenes = $stmt->fetchAll();
                
                // Recharger les associations
                $scenePlayers = [];
                $sceneNpcs = [];
                if (!empty($scenes)) {
                    $sceneIds = array_column($scenes, 'id');
                    $in = implode(',', array_fill(0, count($sceneIds), '?'));
                    $sp = $pdo->prepare("SELECT sp.scene_id, sp.player_id, u.username, ch.name AS character_name FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id IN ($in)");
                    $sp->execute($sceneIds);
                    foreach ($sp->fetchAll() as $row) {
                        $scenePlayers[$row['scene_id']][] = $row;
                    }
                    $sn = $pdo->prepare("SELECT scene_id, name, description, npc_character_id FROM scene_npcs WHERE scene_id IN ($in)");
                    $sn->execute($sceneIds);
                    foreach ($sn->fetchAll() as $row) {
                        $sceneNpcs[$row['scene_id']][] = $row;
                    }
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de la suppression de la scène : " . $e->getMessage();
            }
        }
    }
}

// Récupérer les inscriptions
$stmt = $pdo->prepare("SELECT sr.id, sr.player_id, sr.character_id, sr.status, sr.registered_at, u.username, ch.name AS character_name FROM session_registrations sr JOIN users u ON sr.player_id = u.id LEFT JOIN characters ch ON sr.character_id = ch.id WHERE sr.session_id = ? ORDER BY sr.registered_at DESC");
$stmt->execute([$session_id]);
$registrations = $stmt->fetchAll();

// Récupérer la liste des candidatures approuvées de la campagne (non encore inscrits)
$approvedApplications = [];
if (!empty($session['campaign_id']) && $isOwnerDM) {
    $stmt = $pdo->prepare("SELECT ca.id, ca.player_id, ca.character_id, u.username, ch.name AS character_name FROM campaign_applications ca JOIN users u ON ca.player_id = u.id LEFT JOIN characters ch ON ca.character_id = ch.id WHERE ca.campaign_id = ? AND ca.status = 'approved' AND ca.character_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM session_registrations sr WHERE sr.session_id = ? AND sr.player_id = ca.player_id)");
    $stmt->execute([$session['campaign_id'], $session_id]);
    $approvedApplications = $stmt->fetchAll();
}

// Récupérer scènes et associations
$scenes = [];
$stmt = $pdo->prepare("SELECT * FROM scenes WHERE session_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$session_id]);
$scenes = $stmt->fetchAll();

$scenePlayers = [];
$sceneNpcs = [];
if (!empty($scenes)) {
    $sceneIds = array_column($scenes, 'id');
    $in = implode(',', array_fill(0, count($sceneIds), '?'));
    $sp = $pdo->prepare("SELECT sp.scene_id, sp.player_id, u.username, ch.name AS character_name FROM scene_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.scene_id IN ($in)");
    $sp->execute($sceneIds);
    foreach ($sp->fetchAll() as $row) {
        $scenePlayers[$row['scene_id']][] = $row;
    }
    $sn = $pdo->prepare("SELECT scene_id, name, description, npc_character_id FROM scene_npcs WHERE scene_id IN ($in)");
    $sn->execute($sceneIds);
    foreach ($sn->fetchAll() as $row) {
        $sceneNpcs[$row['scene_id']][] = $row;
    }
}

// Pour l'édition: liste des joueurs approuvés (pour cases à cocher)
$approvedRegs = [];
$regStmt = $pdo->prepare("SELECT player_id, username, character_id, ch.name AS character_name FROM session_registrations sr JOIN users u ON sr.player_id = u.id LEFT JOIN characters ch ON sr.character_id = ch.id WHERE sr.session_id = ? AND sr.status = 'approved' ORDER BY u.username ASC");
$regStmt->execute([$session_id]);
$approvedRegs = $regStmt->fetchAll();

// Récupérer la liste des personnages du MJ pour l'ajout en PNJ
$dmCharacters = [];
if ($isOwnerDM) {
    $stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$dm_id]);
    $dmCharacters = $stmt->fetchAll();
}
?>
<?php if (!$isModal): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session: <?php echo htmlspecialchars($session['title']); ?> - JDR 4 MJ</title>
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
                    <?php if ($session['campaign_id']): ?>
                        <li class="nav-item"><a class="nav-link" href="view_campaign.php?id=<?php echo (int)$session['campaign_id']; ?>">Retour Campagne</a></li>
                    <?php endif; ?>
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
            <h1><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($session['title']); ?></h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($session['status']); ?></span>
                <?php if ($isOwnerDM && in_array($session['status'], ['planning', 'recruiting'])): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="start_session">
                        <button class="btn btn-sm btn-primary"><i class="fas fa-play me-1"></i>Démarrer</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">Détails</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>MJ:</strong> <?php echo htmlspecialchars($session['dm_username']); ?></div>
                        <?php if ($session['campaign_id']): ?>
                            <div class="mb-2"><strong>Campagne:</strong> <a href="view_campaign.php?id=<?php echo (int)$session['campaign_id']; ?>"><?php echo htmlspecialchars($session['campaign_title']); ?></a></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong>Date:</strong> <?php echo !empty($session['session_date']) ? date('d/m/Y H:i', strtotime($session['session_date'])) : '—'; ?></div>
                        <div class="mb-2"><strong>Lieu:</strong> <?php echo $session['is_online'] ? 'En ligne' : htmlspecialchars($session['location'] ?: '—'); ?></div>
                        <?php if ($session['is_online'] && !empty($session['meeting_link'])): ?>
                            <div class="mb-2"><strong>Lien:</strong> <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" target="_blank" rel="noopener noreferrer">Rejoindre</a></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong>Places:</strong> <?php echo (int)$session['max_players']; ?></div>
                        <?php if (!empty($session['description'])): ?>
                            <div class="mt-3"><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($session['description'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($session['start_context'])): ?>
                            <div class="mt-3"><strong>Contexte de début:</strong><br><?php echo nl2br(htmlspecialchars($session['start_context'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Inscriptions</span>
                        <?php if ($isOwnerDM && !empty($approvedApplications)): ?>
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="action" value="add_applicant">
                                <select name="application_id" class="form-select form-select-sm" required>
                                    <option value="" disabled selected>Ajouter un candidat (approuvé)</option>
                                    <?php foreach ($approvedApplications as $a): ?>
                                        <option value="<?php echo (int)$a['id']; ?>">
                                            <?php echo htmlspecialchars($a['username']); ?> — <?php echo htmlspecialchars($a['character_name'] ?? 'Personnage'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-user-plus me-1"></i>Ajouter</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registrations)): ?>
                            <p class="text-muted">Aucune inscription pour le moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Personnage</th>
                                            <th>Statut</th>
                                            <?php if ($isOwnerDM): ?><th class="text-end">Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $r): ?>
                                            <tr>
                                                <td><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($r['username']); ?></td>
                                                <td>
                                                    <?php if ($r['character_id']): ?>
                                                        <a href="view_character.php?id=<?php echo (int)$r['character_id']; ?>&dm_campaign_id=<?php echo (int)$session['campaign_id']; ?>" class="text-decoration-none">
                                                            <span class="badge bg-secondary">#<?php echo (int)$r['character_id']; ?></span>
                                                            <?php echo htmlspecialchars($r['character_name'] ?? 'Personnage'); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $badge = 'secondary';
                                                        if ($r['status'] === 'pending') $badge = 'warning';
                                                        if ($r['status'] === 'approved') $badge = 'primary';
                                                        if ($r['status'] === 'declined') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo $r['status']; ?></span>
                                                </td>
                                                <?php if ($isOwnerDM): ?>
                                                <td class="text-end">
                                                    <?php if ($r['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i></button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Refuser cette inscription ?');">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="decline">
                                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette inscription ?');">
                                                            <input type="hidden" name="registration_id" value="<?php echo (int)$r['id']; ?>">
                                                            <input type="hidden" name="action" value="remove">
                                                            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($isOwnerDM): ?>
        <div class="row g-4 mt-1">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header">Contexte de début de session</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_start_context">
                            <textarea class="form-control" name="start_context" rows="5" placeholder="Décrivez la scène d'ouverture, l'ambiance, les éléments clés..."><?php echo htmlspecialchars($session['start_context'] ?? ''); ?></textarea>
                            <div class="mt-2">
                                <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Sauvegarder</button>
                                <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#createSceneModal">
                                    <i class="fas fa-plus me-1"></i>Nouvelle scène
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal création de scène -->
                <div class="modal fade" id="createSceneModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-photo-video me-2"></i>Créer une scène</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="create_scene">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Titre</label>
                                            <input type="text" class="form-control" name="scene_title" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Plan (URL)</label>
                                            <input type="url" class="form-control" name="map_url" placeholder="https://...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ou téléverser un plan (image)</label>
                                            <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">PNJ (un par ligne)</label>
                                            <textarea class="form-control" name="npcs" rows="4" placeholder="Barman de la Taverne
Garde de la Porte
Capitaine Eloria"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button class="btn btn-success"><i class="fas fa-plus me-1"></i>Créer la scène</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4 mt-1">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">Scènes de la session</div>
                    <div class="card-body">
                        <?php if (empty($scenes)): ?>
                            <p class="text-muted">Aucune scène pour le moment.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($scenes as $sc): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h5 class="card-title mb-2">
                                                        <a href="view_scene.php?id=<?php echo (int)$sc['id']; ?>" class="text-decoration-none view-scene-btn" data-scene-id="<?php echo (int)$sc['id']; ?>">
                                                            <i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($sc['title']); ?>
                                                        </a>
                                                    </h5>
                                                    <div>
                                                        <?php if ($isOwnerDM): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="move_scene">
                                                                <input type="hidden" name="scene_id" value="<?php echo (int)$sc['id']; ?>">
                                                                <input type="hidden" name="direction" value="up">
                                                                <button class="btn btn-sm btn-outline-secondary" title="Monter"><i class="fas fa-arrow-up"></i></button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="move_scene">
                                                                <input type="hidden" name="scene_id" value="<?php echo (int)$sc['id']; ?>">
                                                                <input type="hidden" name="direction" value="down">
                                                                <button class="btn btn-sm btn-outline-secondary" title="Descendre"><i class="fas fa-arrow-down"></i></button>
                                                            </form>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la scène \'<?php echo htmlspecialchars($sc['title']); ?>\' ? Cette action est irréversible et supprimera tous les joueurs et PNJ associés.');">
                                                                <input type="hidden" name="action" value="delete_scene">
                                                                <input type="hidden" name="scene_id" value="<?php echo (int)$sc['id']; ?>">
                                                                <button class="btn btn-sm btn-outline-danger" title="Supprimer la scène"><i class="fas fa-trash"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="collapse mt-2" id="sceneDetails<?php echo (int)$sc['id']; ?>">
                                                    <?php if (!empty($sc['map_url'])): ?>
                                                        <div class="mb-2"><a href="<?php echo htmlspecialchars($sc['map_url']); ?>" target="_blank" rel="noopener noreferrer">Ouvrir le plan</a></div>
                                                    <?php endif; ?>
                                                    <div class="mb-2">
                                                        <strong>Joueurs présents:</strong>
                                                        <?php if (empty($scenePlayers[$sc['id']] ?? [])): ?>
                                                            <span class="text-muted">—</span>
                                                        <?php else: ?>
                                                            <ul class="mb-0">
                                                                <?php foreach (($scenePlayers[$sc['id']] ?? []) as $sp): ?>
                                                                    <li><?php echo htmlspecialchars($sp['username']); ?><?php echo $sp['character_name'] ? ' — ' . htmlspecialchars($sp['character_name']) : ''; ?></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>PNJ:</strong>
                                                        <?php if (empty($sceneNpcs[$sc['id']] ?? [])): ?>
                                                            <span class="text-muted">—</span>
                                                        <?php else: ?>
                                                            <ul class="mb-0">
                                                                <?php foreach (($sceneNpcs[$sc['id']] ?? []) as $sn): ?>
                                                                    <li>
                                                                        <?php echo htmlspecialchars($sn['name']); ?>
                                                                        <?php if (!empty($sn['npc_character_id'])): ?>
                                                                            <span class="badge bg-info ms-1">perso MJ</span>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($isOwnerDM): ?>
                                                        <div class="mt-2">
                                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editScene<?php echo (int)$sc['id']; ?>">Modifier</button>
                                                        </div>
                                                        <div class="collapse mt-2" id="editScene<?php echo (int)$sc['id']; ?>">
                                                            <hr>
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="action" value="update_scene">
                                                                <input type="hidden" name="scene_id" value="<?php echo (int)$sc['id']; ?>">
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Titre</label>
                                                                        <input type="text" class="form-control" name="scene_title" value="<?php echo htmlspecialchars($sc['title']); ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Plan (URL)</label>
                                                                        <input type="url" class="form-control" name="map_url" value="<?php echo htmlspecialchars($sc['map_url']); ?>" placeholder="https://...">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Ou téléverser un plan (image)</label>
                                                                        <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Notes</label>
                                                                        <input type="text" class="form-control" name="notes" value="<?php echo htmlspecialchars($sc['notes'] ?? ''); ?>" placeholder="Notes internes (optionnel)">
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label class="form-label">Joueurs présents</label>
                                                                        <div class="row">
                                                                            <?php foreach ($approvedRegs as $ar): ?>
                                                                                <?php $checked = false; foreach (($scenePlayers[$sc['id']] ?? []) as $sp) { if ((int)$sp['player_id'] === (int)$ar['player_id']) { $checked = true; break; } } ?>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-check">
                                                                                        <input class="form-check-input" type="checkbox" name="present_players[]" value="<?php echo (int)$ar['player_id']; ?>" id="present_<?php echo (int)$sc['id']; ?>_<?php echo (int)$ar['player_id']; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                                                                        <label class="form-check-label" for="present_<?php echo (int)$sc['id']; ?>_<?php echo (int)$ar['player_id']; ?>">
                                                                                            <?php echo htmlspecialchars($ar['username']); ?><?php echo $ar['character_name'] ? ' — ' . htmlspecialchars($ar['character_name']) : ''; ?>
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <label class="form-label">PNJ (texte, un par ligne; les PNJ liés à un perso MJ sont conservés)</label>
                                                                        <?php
                                                                            $freeNpcs = array_filter(($sceneNpcs[$sc['id']] ?? []), function($n){ return empty($n['npc_character_id']); });
                                                                            $freeNpcsText = '';
                                                                            foreach ($freeNpcs as $fn) { $freeNpcsText .= $fn['name'] . "\n"; }
                                                                        ?>
                                                                        <textarea class="form-control" name="npcs" rows="3" placeholder="Ajouter/supprimer PNJ texte..."><?php echo htmlspecialchars(trim($freeNpcsText)); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-3">
                                                                    <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($isOwnerDM && !empty($dmCharacters)): ?>
                                                        <hr>
                                                        <form method="POST" class="row g-2 align-items-end">
                                                            <input type="hidden" name="action" value="add_dm_character_npc">
                                                            <input type="hidden" name="scene_id" value="<?php echo (int)$sc['id']; ?>">
                                                            <div class="col-12">
                                                                <label class="form-label">Ajouter un PNJ à partir d'un de vos personnages</label>
                                                            </div>
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
                                                                <button class="btn btn-outline-primary w-100"><i class="fas fa-user-plus"></i></button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scene Detail Modal -->
    <div class="modal fade" id="sceneDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail de la scène</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="sceneDetailContent">
                        <div class="text-center p-5 text-muted">Chargement...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if (!$isModal): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('sceneDetailModal');
        var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        document.querySelectorAll('.view-scene-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var sceneId = this.getAttribute('data-scene-id');
                if (!sceneId || !modal) return;
                var url = 'view_scene.php?id=' + encodeURIComponent(sceneId) + '&modal=1';
                var container = document.getElementById('sceneDetailContent');
                if (container) {
                    container.innerHTML = '<div class="text-center p-5 text-muted">Chargement...</div>';
                }
                fetch(url, { credentials: 'same-origin' })
                    .then(function(resp) { return resp.text(); })
                    .then(function(html) {
                        if (container) { container.innerHTML = html; }
                        modal.show();
                    })
                    .catch(function() {
                        if (container) { container.innerHTML = '<div class="text-danger p-3">Erreur de chargement.</div>'; }
                        modal.show();
                    });
            });
        });
    });
    </script>
</body>
</html>
<?php endif; ?>
