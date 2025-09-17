<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Détails de Campagne";
$current_page = "view_campaign";


// Les joueurs peuvent voir les campagnes publiques, les DM/Admin peuvent voir toutes les campagnes
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: campaigns.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$campaign_id = (int)$_GET['id'];

// Charger la campagne selon le rôle
if (isAdmin()) {
    // Les admins peuvent voir toutes les campagnes
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
} elseif (isDM()) {
    // Les DM peuvent voir leurs campagnes + les campagnes publiques
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND (dm_id = ? OR is_public = 1)");
    $stmt->execute([$campaign_id, $user_id]);
} else {
    // Les joueurs peuvent voir seulement les campagnes publiques
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND is_public = 1");
    $stmt->execute([$campaign_id]);
}
$campaign = $stmt->fetch();

if (!$campaign) {
    header('Location: campaigns.php');
    exit();
}

// Définir si l'utilisateur est le MJ propriétaire
$dm_id = (int)$campaign['dm_id'];
$isOwnerDM = ($user_id == $dm_id);

// Traitements POST: candidatures (tous les utilisateurs connectés)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'apply_to_campaign') {
        $message = sanitizeInput($_POST['message'] ?? '');
        $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
        
        // Vérifier si l'utilisateur n'est pas déjà membre
        $stmt = $pdo->prepare("SELECT user_id FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
        $stmt->execute([$campaign_id, $user_id]);
        $is_member = $stmt->fetch();
        
        if ($is_member) {
            $error_message = "Vous êtes déjà membre de cette campagne.";
        } else {
            // Vérifier si l'utilisateur n'a pas déjà postulé
            $stmt = $pdo->prepare("SELECT id FROM campaign_applications WHERE campaign_id = ? AND player_id = ? AND status = 'pending'");
            $stmt->execute([$campaign_id, $user_id]);
            $existing_application = $stmt->fetch();
            
            if ($existing_application) {
                $error_message = "Vous avez déjà postulé à cette campagne.";
            } else {
                // Créer la candidature
                $stmt = $pdo->prepare("INSERT INTO campaign_applications (campaign_id, player_id, character_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$campaign_id, $user_id, $character_id, $message]);
                $success_message = "Votre candidature a été envoyée avec succès !";
            }
        }
    }
}

// Traitements POST: ajouter membre par invite, créer session rapide (DM et Admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isDMOrAdmin()) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_member') {
        $username_or_email = sanitizeInput($_POST['username_or_email'] ?? '');
        if ($username_or_email !== '') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch();
            if ($user) {
                $stmt = $pdo->prepare("REPLACE INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'player')");
                $stmt->execute([$campaign_id, $user['id']]);
                $success_message = "Membre ajouté à la campagne.";
            } else {
                $error_message = "Utilisateur introuvable.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'create_session') {
        $title = sanitizeInput($_POST['title'] ?? 'Session');
        $session_date = sanitizeInput($_POST['session_date'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $is_online = isset($_POST['is_online']) ? 1 : 0;
        $meeting_link = sanitizeInput($_POST['meeting_link'] ?? '');
        $max_players = (int)($_POST['max_players'] ?? 6);

        $stmt = $pdo->prepare("INSERT INTO game_sessions (dm_id, title, session_date, location, is_online, meeting_link, max_players, campaign_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dm_id, $title, $session_date, $location, $is_online, $meeting_link, $max_players, $campaign_id]);
        $success_message = "Session créée.";
    }

    // Approuver une candidature
    if (isset($_POST['action']) && $_POST['action'] === 'approve_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Vérifier que la candidature correspond à cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ?");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $pdo->beginTransaction();
            try {
                // Mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'approved' WHERE id = ?");
                $stmt->execute([$application_id]);
                // Ajouter comme membre si pas déjà présent
                $stmt = $pdo->prepare("INSERT IGNORE INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'player')");
                $stmt->execute([$campaign_id, $player_id]);
                // Notification au joueur
                $title = 'Candidature acceptée';
                $message = 'Votre candidature à la campagne "' . $campaign['title'] . '" a été acceptée.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$player_id, $title, $message, $campaign_id]);
                $pdo->commit();
                $success_message = "Candidature approuvée et joueur ajouté à la campagne.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de l'approbation.";
            }
        } else {
            $error_message = "Candidature introuvable.";
        }
    }

    // Refuser une candidature
    if (isset($_POST['action']) && $_POST['action'] === 'decline_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Récupérer le player_id et vérifier droits MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ?");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'declined' WHERE id = ?");
            $stmt->execute([$application_id]);
            // Notification au joueur
            $title = 'Candidature refusée';
            $message = 'Votre candidature à la campagne "' . $campaign['title'] . '" a été refusée.';
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
            $stmt->execute([$player_id, $title, $message, $campaign_id]);
            $success_message = "Candidature refusée.";
        } else {
            $error_message = "Candidature introuvable.";
        }
    }

    // Annuler l'acceptation (revenir à 'pending' et retirer le joueur des membres)
    if (isset($_POST['action']) && $_POST['action'] === 'revoke_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Récupérer player_id et vérifier que la candidature est approuvée pour cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ? AND ca.status = 'approved'");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $pdo->beginTransaction();
            try {
                // Revenir à pending
                $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'pending' WHERE id = ?");
                $stmt->execute([$application_id]);
                // Retirer le membre de la campagne s'il y est
                $stmt = $pdo->prepare("DELETE FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
                $stmt->execute([$campaign_id, $player_id]);
                // Notifier le joueur
                $title = 'Acceptation annulée';
                $message = 'Votre acceptation dans la campagne "' . $campaign['title'] . '" a été annulée par le MJ. Votre candidature est de nouveau en attente.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$player_id, $title, $message, $campaign_id]);
                $pdo->commit();
                $success_message = "Acceptation annulée. Candidature remise en attente et joueur retiré.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Erreur lors de l'annulation de l'acceptation.";
            }
        } else {
            $error_message = "Candidature approuvée introuvable.";
        }
    }

    // Annuler le refus (revenir à 'pending')
    if (isset($_POST['action']) && $_POST['action'] === 'unrevoke_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        // Vérifier que la candidature est refusée pour cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ? AND ca.status = 'declined'");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            // Remettre la candidature en attente
            $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'pending' WHERE id = ?");
            $stmt->execute([$application_id]);
            // Notifier le joueur
            $title = 'Refus annulé';
            $message = 'Votre refus dans la campagne "' . $campaign['title'] . '" a été annulé par le MJ. Votre candidature est de nouveau en attente.';
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
            $stmt->execute([$player_id, $title, $message, $campaign_id]);
            $success_message = "Refus annulé. La candidature est remise en attente.";
        } else {
            $error_message = "Candidature refusée introuvable.";
        }
    }

    // Exclure un membre (joueur) de la campagne
    if (isset($_POST['action']) && $_POST['action'] === 'remove_member' && isset($_POST['member_user_id'])) {
        $member_user_id = (int)$_POST['member_user_id'];
        // Ne pas autoriser la suppression du MJ propriétaire
        if ($member_user_id === $dm_id) {
            $error_message = "Impossible d'exclure le MJ de sa propre campagne.";
        } else {
            // Vérifier que l'utilisateur est bien membre de cette campagne
            $stmt = $pdo->prepare("SELECT role FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
            $stmt->execute([$campaign_id, $member_user_id]);
            $member = $stmt->fetch();
            if ($member) {
                // Supprimer le membre
                $stmt = $pdo->prepare("DELETE FROM campaign_members WHERE campaign_id = ? AND user_id = ?");
                $stmt->execute([$campaign_id, $member_user_id]);
                // Notifier le joueur
                $title = 'Exclusion de la campagne';
                $message = 'Vous avez été exclu de la campagne "' . $campaign['title'] . '" par le MJ.';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$member_user_id, $title, $message, $campaign_id]);
                $success_message = "Joueur exclu de la campagne.";
            } else {
                $error_message = "Ce joueur n'est pas membre de la campagne.";
            }
        }
    }

    // Gestion du journal
    if (isset($_POST['action']) && $_POST['action'] === 'create_journal_entry') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if ($title !== '' && $content !== '') {
            $stmt = $pdo->prepare("INSERT INTO campaign_journal (campaign_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$campaign_id, $title, $content]);
            $success_message = "Événement ajouté au journal.";
        } else {
            $error_message = "Le titre et le contenu sont obligatoires.";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_journal_entry' && isset($_POST['entry_id'])) {
        $entry_id = (int)$_POST['entry_id'];
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if ($title !== '' && $content !== '') {
            $stmt = $pdo->prepare("UPDATE campaign_journal SET title = ?, content = ? WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$title, $content, $entry_id, $campaign_id]);
            $success_message = "Événement mis à jour.";
        } else {
            $error_message = "Le titre et le contenu sont obligatoires.";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete_journal_entry' && isset($_POST['entry_id'])) {
        $entry_id = (int)$_POST['entry_id'];
        $stmt = $pdo->prepare("DELETE FROM campaign_journal WHERE id = ? AND campaign_id = ?");
        $stmt->execute([$entry_id, $campaign_id]);
        $success_message = "Événement supprimé du journal.";
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_journal_visibility' && isset($_POST['entry_id'])) {
        $entry_id = (int)$_POST['entry_id'];
        $stmt = $pdo->prepare("UPDATE campaign_journal SET is_public = NOT is_public WHERE id = ? AND campaign_id = ?");
        $stmt->execute([$entry_id, $campaign_id]);
        $success_message = "Visibilité de l'événement mise à jour.";
    }

    // Gestion des lieux
    if (isset($_POST['action']) && $_POST['action'] === 'create_scene') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        if ($title !== '') {
            $map_url = '';
            
            // Upload de plan si fourni
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
                        $filename = 'plan_' . time() . '_' . uniqid() . '.' . $ext;
                        $uploadPath = 'uploads/' . $filename;
                        
                        if (move_uploaded_file($tmp, $uploadPath)) {
                            $map_url = $uploadPath;
                        } else {
                            $error_message = "Erreur lors du téléversement de l'image.";
                        }
                    }
                }
            }
            
            if (!isset($error_message)) {
                $stmt = $pdo->prepare("INSERT INTO places (campaign_id, title, map_url, notes, position) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$campaign_id, $title, $map_url, $notes]);
                $success_message = "Lieu créée avec succès.";
            }
        } else {
            $error_message = "Le titre du lieu est requis.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_scene' && isset($_POST['place_id'])) {
        $place_id = (int)$_POST['place_id'];
        $stmt = $pdo->prepare("DELETE FROM places WHERE id = ? AND campaign_id = ?");
        $stmt->execute([$place_id, $campaign_id]);
        $success_message = "Lieu supprimée avec succès.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'move_scene' && isset($_POST['place_id']) && isset($_POST['direction'])) {
        $place_id = (int)$_POST['place_id'];
        $direction = $_POST['direction'];
        
        // Récupérer la position actuelle
        $stmt = $pdo->prepare("SELECT position FROM places WHERE id = ? AND campaign_id = ?");
        $stmt->execute([$place_id, $campaign_id]);
        $scene = $stmt->fetch();
        
        if ($scene) {
            $new_position = $scene['position'] + ($direction === 'up' ? -1 : 1);
            $new_position = max(0, $new_position);
            
            // Échanger avec la lieu adjacente
            $stmt = $pdo->prepare("SELECT id FROM places WHERE campaign_id = ? AND position = ? AND id != ?");
            $stmt->execute([$campaign_id, $new_position, $place_id]);
            $adjacent_scene = $stmt->fetch();
            
            if ($adjacent_scene) {
                // Échanger les positions
                $stmt = $pdo->prepare("UPDATE places SET position = ? WHERE id = ?");
                $stmt->execute([$scene['position'], $adjacent_scene['id']]);
                $stmt->execute([$new_position, $place_id]);
            }
        }
    }

    // Transfert d'entités entre lieux
    if (isset($_POST['action']) && $_POST['action'] === 'transfer_entity' && $isOwnerDM) {
        $entity_type = $_POST['entity_type'] ?? '';
        $entity_id = (int)($_POST['entity_id'] ?? 0);
        $from_place_id = (int)($_POST['from_place_id'] ?? 0);
        $to_place_id = (int)($_POST['to_place_id'] ?? 0);
        
        if ($entity_type && $entity_id && $from_place_id && $to_place_id && $from_place_id !== $to_place_id) {
            // Vérifier que les lieux appartiennent à la campagne
            $stmt = $pdo->prepare("SELECT id FROM places WHERE id IN (?, ?) AND campaign_id = ?");
            $stmt->execute([$from_place_id, $to_place_id, $campaign_id]);
            $valid_places = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($valid_places) === 2) {
                $pdo->beginTransaction();
                try {
                    if ($entity_type === 'player') {
                        // Transférer un joueur
                        $stmt = $pdo->prepare("UPDATE place_players SET place_id = ? WHERE place_id = ? AND player_id = ?");
                        $stmt->execute([$to_place_id, $from_place_id, $entity_id]);
                        $success_message = "Joueur transféré avec succès.";
                    } elseif ($entity_type === 'npc') {
                        // Transférer un PNJ
                        $stmt = $pdo->prepare("UPDATE place_npcs SET place_id = ? WHERE place_id = ? AND id = ? AND monster_id IS NULL");
                        $stmt->execute([$to_place_id, $from_place_id, $entity_id]);
                        $success_message = "PNJ transféré avec succès.";
                    } elseif ($entity_type === 'monster') {
                        // Transférer un monstre
                        $stmt = $pdo->prepare("UPDATE place_npcs SET place_id = ? WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
                        $stmt->execute([$to_place_id, $from_place_id, $entity_id]);
                        $success_message = "Monstre transféré avec succès.";
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_message = "Erreur lors du transfert : " . $e->getMessage();
                }
            } else {
                $error_message = "Lieux invalides.";
            }
        } else {
            $error_message = "Paramètres de transfert invalides.";
        }
    }
}

// Récupérer membres
$stmt = $pdo->prepare("SELECT u.id, u.username, cm.role, cm.joined_at FROM campaign_members cm JOIN users u ON cm.user_id = u.id WHERE cm.campaign_id = ? ORDER BY cm.joined_at ASC");
$stmt->execute([$campaign_id]);
$members = $stmt->fetchAll();

// Vérifier si l'utilisateur actuel est membre de la campagne
$is_member = false;
$user_role = null;
foreach ($members as $member) {
    if ($member['id'] == $user_id) {
        $is_member = true;
        $user_role = $member['role'];
        break;
    }
}

// Récupérer les personnages de l'utilisateur pour la candidature
$stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$user_characters = $stmt->fetchAll();

// Vérifier si l'utilisateur a déjà postulé
$stmt = $pdo->prepare("SELECT id, status, created_at FROM campaign_applications WHERE campaign_id = ? AND player_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$campaign_id, $user_id]);
$user_application = $stmt->fetch();

// Vérifier si l'utilisateur est déjà membre
$is_member = false;
foreach ($members as $member) {
    if ($member['id'] == $user_id) {
        $is_member = true;
        break;
    }
}

// Récupérer sessions
$stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE campaign_id = ? ORDER BY session_date DESC, created_at DESC");
$stmt->execute([$campaign_id]);
$sessions = $stmt->fetchAll();

// Récupérer candidatures
$stmt = $pdo->prepare("SELECT ca.id, ca.player_id, ca.character_id, ca.message, ca.status, ca.created_at, u.username, ch.name AS character_name FROM campaign_applications ca JOIN users u ON ca.player_id = u.id LEFT JOIN characters ch ON ca.character_id = ch.id WHERE ca.campaign_id = ? ORDER BY ca.created_at DESC");
$stmt->execute([$campaign_id]);
$applications = $stmt->fetchAll();

// Récupérer lieux
$stmt = $pdo->prepare("SELECT * FROM places WHERE campaign_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$campaign_id]);
$places = $stmt->fetchAll();

// Récupérer les événements du journal
$stmt = $pdo->prepare("SELECT * FROM campaign_journal WHERE campaign_id = ? ORDER BY created_at DESC");
$stmt->execute([$campaign_id]);
$journalEntries = $stmt->fetchAll();

// Récupérer les joueurs, PNJ et monstres pour chaque lieu
$placePlayers = [];
$placeNpcs = [];
$placeMonsters = [];

if (!empty($places)) {
    $placeIds = array_column($places, 'id');
    $in = implode(',', array_fill(0, count($placeIds), '?'));
    
    // Récupérer les joueurs
    $stmt = $pdo->prepare("SELECT pp.place_id, pp.player_id, u.username, ch.id AS character_id, ch.name AS character_name FROM place_players pp JOIN users u ON pp.player_id = u.id LEFT JOIN characters ch ON pp.character_id = ch.id WHERE pp.place_id IN ($in) ORDER BY u.username ASC");
    $stmt->execute($placeIds);
    foreach ($stmt->fetchAll() as $row) {
        $placePlayers[$row['place_id']][] = $row;
    }
    
    // Récupérer les PNJ (non-monstres)
    $stmt = $pdo->prepare("SELECT pn.place_id, pn.id, pn.name, pn.description, pn.npc_character_id FROM place_npcs pn WHERE pn.place_id IN ($in) AND pn.monster_id IS NULL ORDER BY pn.name ASC");
    $stmt->execute($placeIds);
    foreach ($stmt->fetchAll() as $row) {
        $placeNpcs[$row['place_id']][] = $row;
    }
    
    // Récupérer les monstres
    $stmt = $pdo->prepare("SELECT pn.place_id, pn.id, pn.name, pn.description, pn.monster_id, pn.quantity, pn.current_hit_points, m.type, m.size, m.challenge_rating FROM place_npcs pn JOIN dnd_monsters m ON pn.monster_id = m.id WHERE pn.place_id IN ($in) AND pn.monster_id IS NOT NULL ORDER BY pn.name ASC");
    $stmt->execute($placeIds);
    foreach ($stmt->fetchAll() as $row) {
        $placeMonsters[$row['place_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campaign['title']); ?> - Campagne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($campaign['title']); ?></h1>
            <span class="badge bg-<?php echo $campaign['is_public'] ? 'success' : 'secondary'; ?>"><?php echo $campaign['is_public'] ? 'Publique' : 'Privée'; ?></span>
        </div>
        <?php if (isset($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (isset($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (!empty($campaign['description'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-users me-2"></i>Membres</div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <p class="text-muted">Aucun membre pour l'instant.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($members as $m): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($m['username']); ?>
                                            <span class="badge bg-<?php echo $m['role'] === 'dm' ? 'danger' : 'primary'; ?> ms-2"><?php echo $m['role'] === 'dm' ? 'MJ' : 'Joueur'; ?></span>
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted">Depuis <?php echo date('d/m/Y', strtotime($m['joined_at'])); ?></small>
                                            <?php if ($m['role'] !== 'dm' && isDMOrAdmin()): ?>
                                                <form method="POST" onsubmit="return confirm('Exclure ce joueur de la campagne ?');">
                                                    <input type="hidden" name="action" value="remove_member">
                                                    <input type="hidden" name="member_user_id" value="<?php echo (int)$m['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" title="Exclure">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (isDMOrAdmin()): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="add_member">
                            <div class="input-group">
                                <input type="text" class="form-control" name="username_or_email" placeholder="Nom d'utilisateur ou email">
                                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-user-plus me-2"></i>Ajouter</button>
                            </div>
                            <div class="form-text">Ou partagez le code d'invitation : <code><?php echo htmlspecialchars($campaign['invite_code']); ?></code></div>
                        </form>
                        <?php else: ?>
                        <div class="mt-3">
                            <div class="form-text">Code d'invitation : <code><?php echo htmlspecialchars($campaign['invite_code']); ?></code></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($is_member && $user_role === 'player'): ?>
                        <!-- Bouton Rejoindre pour les joueurs membres -->
                        <div class="mt-4 p-3 border rounded bg-success bg-opacity-10">
                            <h6 class="mb-3 text-success"><i class="fas fa-check-circle me-2"></i>Vous êtes membre de cette campagne</h6>
                            <p class="mb-3">Vous pouvez maintenant rejoindre la partie et accéder à tous les contenus de la campagne.</p>
                            <?php if (!empty($places)): ?>
                                <a href="view_scene_player.php?id=<?php echo (int)$places[0]['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-play me-2"></i>Rejoindre la partie
                                </a>
                            <?php else: ?>
                                <p class="text-muted mb-0">Aucun lieu créé pour l'instant. Attendez que le MJ crée un lieu.</p>
                            <?php endif; ?>
                        </div>
                        <?php elseif (!$is_member && !$user_application): ?>
                        <!-- Formulaire de candidature pour les joueurs -->
                        <div class="mt-4 p-3 border rounded bg-light">
                            <h6 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Postuler à cette campagne</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="apply_to_campaign">
                                <div class="mb-3">
                                    <label class="form-label">Personnage (optionnel)</label>
                                    <select name="character_id" class="form-select">
                                        <option value="">Aucun personnage spécifique</option>
                                        <?php foreach ($user_characters as $char): ?>
                                            <option value="<?php echo $char['id']; ?>"><?php echo htmlspecialchars($char['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message de candidature</label>
                                    <textarea name="message" class="form-control" rows="3" placeholder="Présentez-vous et expliquez pourquoi vous souhaitez rejoindre cette campagne..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer ma candidature
                                </button>
                            </form>
                        </div>
                        <?php elseif ($user_application): ?>
                        <!-- Statut de la candidature -->
                        <div class="mt-4 p-3 border rounded">
                            <h6 class="mb-2"><i class="fas fa-clock me-2"></i>Votre candidature</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php 
                                    echo $user_application['status'] === 'pending' ? 'warning' : 
                                        ($user_application['status'] === 'approved' ? 'success' : 'danger'); 
                                ?> me-2">
                                    <?php 
                                    echo $user_application['status'] === 'pending' ? 'En attente' : 
                                        ($user_application['status'] === 'approved' ? 'Acceptée' : 'Refusée'); 
                                    ?>
                                </span>
                                <small class="text-muted">Envoyée le <?php echo date('d/m/Y H:i', strtotime($user_application['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>Sessions</div>
                    <div class="card-body">
                        <?php if (isDMOrAdmin()): ?>
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="create_session">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Titre</label>
                                    <input type="text" class="form-control" name="title" placeholder="Session 1" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date/Heure</label>
                                    <input type="datetime-local" class="form-control" name="session_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Places</label>
                                    <input type="number" class="form-control" name="max_players" value="6" min="1" max="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Lieu</label>
                                    <input type="text" class="form-control" name="location" placeholder="En ligne / Adresse">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_online" id="is_online">
                                        <label class="form-check-label" for="is_online">En ligne</label>
                                    </div>
                                    <input type="text" class="form-control mt-2" name="meeting_link" placeholder="Lien (si en ligne)">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary"><i class="fas fa-plus me-2"></i>Créer une session</button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>

                        <?php if (empty($sessions)): ?>
                            <p class="text-muted">Aucune session planifiée.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($sessions as $s): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['title']); ?></strong>
                                            <div class="small text-muted">
                                                <?php if (!empty($s['session_date'])) echo date('d/m/Y H:i', strtotime($s['session_date'])) . ' · '; ?>
                                                <?php echo $s['is_online'] ? 'En ligne' : htmlspecialchars($s['location']); ?>
                                                · Places: <?php echo $s['max_players']; ?>
                                            </div>
                                        </div>
                                        <a href="view_session.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary view-session-btn" data-session-id="<?php echo $s['id']; ?>"><i class="fas fa-eye"></i></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Lieux - Visible uniquement pour les DM et Admin -->
        <?php if (isDMOrAdmin()): ?>
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-photo-video me-2"></i>Lieux de la campagne</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSceneModal">
                            <i class="fas fa-plus"></i> Nouveau lieu
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($places)): ?>
                            <p class="text-muted">Aucun lieu créé.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($places as $scene): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="card-title mb-2">
                                                        <a href="view_scene.php?id=<?php echo (int)$scene['id']; ?>" class="text-decoration-none">
                                                            <i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($scene['title']); ?>
                                                        </a>
                                                    </h6>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="view_scene.php?id=<?php echo (int)$scene['id']; ?>"><i class="fas fa-eye me-2"></i>Voir</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="action" value="move_scene">
                                                                    <input type="hidden" name="place_id" value="<?php echo (int)$scene['id']; ?>">
                                                                    <input type="hidden" name="direction" value="up">
                                                                    <button class="dropdown-item" type="submit"><i class="fas fa-arrow-up me-2"></i>Monter</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="action" value="move_scene">
                                                                    <input type="hidden" name="place_id" value="<?php echo (int)$scene['id']; ?>">
                                                                    <input type="hidden" name="direction" value="down">
                                                                    <button class="dropdown-item" type="submit"><i class="fas fa-arrow-down me-2"></i>Descendre</button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette lieu ?')">
                                                                    <input type="hidden" name="action" value="delete_scene">
                                                                    <input type="hidden" name="place_id" value="<?php echo (int)$scene['id']; ?>">
                                                                    <button class="dropdown-item text-danger" type="submit"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($scene['map_url'])): ?>
                                                    <div class="mb-2">
                                                        <a href="<?php echo htmlspecialchars($scene['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                            <i class="fas fa-map me-1"></i>Plan du lieu
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($scene['notes'])): ?>
                                                    <div class="small text-muted mb-2">
                                                        <?php echo nl2br(htmlspecialchars($scene['notes'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Joueurs présents -->
                                                <?php if (!empty($placePlayers[$scene['id']])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted"><i class="fas fa-users me-1"></i>Joueurs :</small>
                                                        <div class="mt-1">
                                                            <?php foreach ($placePlayers[$scene['id']] as $player): ?>
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small">
                                                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($player['username']); ?>
                                                                        <?php if ($player['character_name']): ?>
                                                                            <span class="text-muted">(<?php echo htmlspecialchars($player['character_name']); ?>)</span>
                                                                        <?php endif; ?>
                                                                    </span>
                                                                    <?php if ($isOwnerDM && count($places) > 1): ?>
                                                                        <button class="btn btn-sm btn-outline-primary" onclick="showTransferModal('player', <?php echo $player['player_id']; ?>, <?php echo $scene['id']; ?>, '<?php echo htmlspecialchars($player['username']); ?>')">
                                                                            <i class="fas fa-exchange-alt"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- PNJ présents -->
                                                <?php if (!empty($placeNpcs[$scene['id']])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted"><i class="fas fa-user-tie me-1"></i>PNJ :</small>
                                                        <div class="mt-1">
                                                            <?php foreach ($placeNpcs[$scene['id']] as $npc): ?>
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small">
                                                                        <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($npc['name']); ?>
                                                                    </span>
                                                                    <?php if ($isOwnerDM && count($places) > 1): ?>
                                                                        <button class="btn btn-sm btn-outline-primary" onclick="showTransferModal('npc', <?php echo $npc['id']; ?>, <?php echo $scene['id']; ?>, '<?php echo htmlspecialchars($npc['name']); ?>')">
                                                                            <i class="fas fa-exchange-alt"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Monstres présents -->
                                                <?php if (!empty($placeMonsters[$scene['id']])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted"><i class="fas fa-dragon me-1"></i>Monstres :</small>
                                                        <div class="mt-1">
                                                            <?php foreach ($placeMonsters[$scene['id']] as $monster): ?>
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="small">
                                                                        <i class="fas fa-dragon me-1"></i><?php echo htmlspecialchars($monster['name']); ?>
                                                                        <span class="text-muted">(<?php echo htmlspecialchars($monster['type']); ?>)</span>
                                                                    </span>
                                                                    <?php if ($isOwnerDM && count($places) > 1): ?>
                                                                        <button class="btn btn-sm btn-outline-primary" onclick="showTransferModal('monster', <?php echo $monster['id']; ?>, <?php echo $scene['id']; ?>, '<?php echo htmlspecialchars($monster['name']); ?>')">
                                                                            <i class="fas fa-exchange-alt"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="small text-muted mt-2">
                                                    Créée le <?php echo date('d/m/Y H:i', strtotime($scene['created_at'])); ?>
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
        <?php endif; ?>

        <!-- Section Journal -->
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Journal de campagne</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createJournalModal">
                            <i class="fas fa-plus"></i> Nouvel événement
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($journalEntries)): ?>
                            <p class="text-muted">Aucun événement dans le journal.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($journalEntries as $entry): ?>
                                    <div class="col-12">
                                        <div class="card border-<?php echo $entry['is_public'] ? 'success' : 'danger'; ?>">
                                            <div class="card-header bg-<?php echo $entry['is_public'] ? 'success' : 'danger'; ?> text-white d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-<?php echo $entry['is_public'] ? 'eye' : 'eye-slash'; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($entry['title']); ?>
                                                </h6>
                                                <div class="d-flex gap-1">
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($entry['is_public'] ? 'Rendre privé' : 'Rendre public'); ?> cet événement ?');">
                                                        <input type="hidden" name="action" value="toggle_journal_visibility">
                                                        <input type="hidden" name="entry_id" value="<?php echo (int)$entry['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-light" title="<?php echo $entry['is_public'] ? 'Rendre privé' : 'Rendre public'; ?>">
                                                            <i class="fas fa-<?php echo $entry['is_public'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#editJournalModal" onclick="editJournalEntry(<?php echo (int)$entry['id']; ?>, '<?php echo htmlspecialchars($entry['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($entry['content'], ENT_QUOTES); ?>')" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet événement ?');">
                                                        <input type="hidden" name="action" value="delete_journal_entry">
                                                        <input type="hidden" name="entry_id" value="<?php echo (int)$entry['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-light" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-muted small mb-2">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Créé le <?php echo date('d/m/Y à H:i', strtotime($entry['created_at'])); ?>
                                                    <?php if ($entry['updated_at'] !== $entry['created_at']): ?>
                                                        • Modifié le <?php echo date('d/m/Y à H:i', strtotime($entry['updated_at'])); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="journal-content">
                                                    <?php echo nl2br(htmlspecialchars($entry['content'])); ?>
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

        <?php if (isDMOrAdmin() && $isOwnerDM): ?>
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><i class="fas fa-inbox me-2"></i>Candidatures</div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <p class="text-muted">Aucune candidature pour le moment.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Personnage</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $a): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($a['username']); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($a['character_id'])): ?>
                                                        <a href="view_character.php?id=<?php echo (int)$a['character_id']; ?>&dm_campaign_id=<?php echo (int)$campaign_id; ?>" class="text-decoration-none">
                                                            <span class="badge bg-secondary">#<?php echo (int)$a['character_id']; ?></span>
                                                            <?php echo htmlspecialchars($a['character_name'] ?? 'Personnage'); ?>
                                                            <i class="fas fa-external-link-alt ms-1 small"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small" style="max-width: 400px;">
                                                    <?php echo nl2br(htmlspecialchars($a['message'] ?: '—')); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $badge = 'secondary';
                                                        if ($a['status'] === 'pending') $badge = 'warning';
                                                        if ($a['status'] === 'approved') $badge = 'primary';
                                                        if ($a['status'] === 'declined') $badge = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo $a['status']; ?></span>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></small></td>
                                                <td class="text-end">
                                                    <?php if ($a['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="approve_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Accepter</button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Refuser cette candidature ?');">
                                                            <input type="hidden" name="action" value="decline_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1"></i>Refuser</button>
                                                        </form>
                                                    <?php elseif ($a['status'] === 'approved'): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Annuler l\'acceptation de cette candidature ? Le joueur sera retiré de la campagne.');">
                                                            <input type="hidden" name="action" value="revoke_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-warning"><i class="fas fa-undo me-1"></i>Annuler l'acceptation</button>
                                                        </form>
                                                    <?php elseif ($a['status'] === 'declined'): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Annuler le refus de cette candidature ? Elle sera remise en attente.');">
                                                            <input type="hidden" name="action" value="unrevoke_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-success"><i class="fas fa-undo me-1"></i>Annuler le refus</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
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
        <?php endif; ?>
    </div>

    <!-- Modal Création Lieu -->
    <div class="modal fade" id="createSceneModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau lieu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_scene">
                        
                        <div class="mb-3">
                            <label for="sceneTitle" class="form-label">Titre du lieu *</label>
                            <input type="text" class="form-control" id="sceneTitle" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="scenePlanFile" class="form-label">Plan du lieu (optionnel)</label>
                            <input type="file" class="form-control" id="scenePlanFile" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sceneNotes" class="form-label">Notes (optionnel)</label>
                            <textarea class="form-control" id="sceneNotes" name="notes" rows="3" placeholder="Description du lieu, contexte, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer le lieu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Transfert d'Entité -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transférer <span id="transferEntityName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" id="transferForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="transfer_entity">
                        <input type="hidden" name="entity_type" id="transferEntityType">
                        <input type="hidden" name="entity_id" id="transferEntityId">
                        <input type="hidden" name="from_place_id" id="transferFromPlaceId">
                        
                        <div class="mb-3">
                            <label for="transferToPlace" class="form-label">Transférer vers :</label>
                            <select class="form-select" name="to_place_id" id="transferToPlace" required>
                                <option value="">Sélectionner un lieu</option>
                                <?php foreach ($places as $place): ?>
                                    <option value="<?php echo (int)$place['id']; ?>"><?php echo htmlspecialchars($place['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="transferInfo"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Transférer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Session Detail Modal -->
    <div class="modal fade" id="sessionDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail de la session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="sessionDetailContent">
                        <div class="text-center p-5 text-muted">Chargement...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer Événement Journal -->
    <div class="modal fade" id="createJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel événement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_journal_entry">
                        
                        <div class="mb-3">
                            <label for="journalTitle" class="form-label">Titre de l'événement</label>
                            <input type="text" class="form-control" id="journalTitle" name="title" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="journalContent" class="form-label">Contenu</label>
                            <textarea class="form-control" id="journalContent" name="content" rows="8" required placeholder="Décrivez l'événement..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            L'événement sera créé en mode privé (rouge). Vous pourrez le rendre public (vert) après sa création.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer l'événement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modifier Événement Journal -->
    <div class="modal fade" id="editJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'événement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_journal_entry">
                        <input type="hidden" name="entry_id" id="editEntryId">
                        
                        <div class="mb-3">
                            <label for="editJournalTitle" class="form-label">Titre de l'événement</label>
                            <input type="text" class="form-control" id="editJournalTitle" name="title" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="editJournalContent" class="form-label">Contenu</label>
                            <textarea class="form-control" id="editJournalContent" name="content" rows="8" required placeholder="Décrivez l'événement..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('sessionDetailModal');
        var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        document.querySelectorAll('.view-session-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var sessionId = this.getAttribute('data-session-id');
                if (!sessionId || !modal) return;
                var url = 'view_session.php?id=' + encodeURIComponent(sessionId) + '&modal=1';
                var container = document.getElementById('sessionDetailContent');
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
        
        // Fonction pour afficher le modal de transfert
        window.showTransferModal = function(entityType, entityId, fromPlaceId, entityName) {
            document.getElementById('transferEntityType').value = entityType;
            document.getElementById('transferEntityId').value = entityId;
            document.getElementById('transferFromPlaceId').value = fromPlaceId;
            document.getElementById('transferEntityName').textContent = entityName;
            
            // Réinitialiser le formulaire
            document.getElementById('transferToPlace').value = '';
            
            // Mettre à jour les informations
            var typeText = '';
            var infoText = '';
            switch(entityType) {
                case 'player':
                    typeText = 'Joueur';
                    infoText = 'Ce joueur sera transféré vers le lieu sélectionné.';
                    break;
                case 'npc':
                    typeText = 'PNJ';
                    infoText = 'Ce PNJ sera transféré vers le lieu sélectionné.';
                    break;
                case 'monster':
                    typeText = 'Monstre';
                    infoText = 'Ce monstre sera transféré vers le lieu sélectionné.';
                    break;
            }
            
            document.getElementById('transferInfo').textContent = infoText;
            
            // Exclure le lieu d'origine de la liste
            var select = document.getElementById('transferToPlace');
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value == fromPlaceId) {
                    select.options[i].style.display = 'none';
                } else {
                    select.options[i].style.display = 'block';
                }
            }
            
            // Afficher le modal
            var modal = new bootstrap.Modal(document.getElementById('transferModal'));
            modal.show();
        };
        
        // Fonction pour éditer un événement du journal
        window.editJournalEntry = function(entryId, title, content) {
            document.getElementById('editEntryId').value = entryId;
            document.getElementById('editJournalTitle').value = title;
            document.getElementById('editJournalContent').value = content;
        };
    });
    </script>
</body>
</html>
