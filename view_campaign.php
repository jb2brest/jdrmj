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
    $stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id FROM campaigns c JOIN users u ON c.dm_id = u.id LEFT JOIN worlds w ON c.world_id = w.id WHERE c.id = ?");
    $stmt->execute([$campaign_id]);
} elseif (isDM()) {
    // Les DM peuvent voir leurs campagnes + les campagnes publiques
    $stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id FROM campaigns c JOIN users u ON c.dm_id = u.id LEFT JOIN worlds w ON c.world_id = w.id WHERE c.id = ? AND (c.dm_id = ? OR c.is_public = 1)");
    $stmt->execute([$campaign_id, $user_id]);
} else {
    // Les joueurs peuvent voir les campagnes publiques ET les campagnes où ils sont membres
    $stmt = $pdo->prepare("
        SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id FROM campaigns c 
        JOIN users u ON c.dm_id = u.id
        LEFT JOIN worlds w ON c.world_id = w.id
        WHERE c.id = ? AND (
            c.is_public = 1 
            OR EXISTS (
                SELECT 1 FROM campaign_members cm 
                WHERE cm.campaign_id = c.id AND cm.user_id = ?
            )
        )
    ");
    $stmt->execute([$campaign_id, $user_id]);
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
    // Gestion de la mise à jour du monde de la campagne (MJ/Admin uniquement)
    if (isset($_POST['action']) && $_POST['action'] === 'update_campaign_world' && isDMOrAdmin()) {
        $world_id = !empty($_POST['world_id']) ? (int)$_POST['world_id'] : null;
        
        try {
            $stmt = $pdo->prepare("UPDATE campaigns SET world_id = ? WHERE id = ? AND dm_id = ?");
            $stmt->execute([$world_id, $campaign_id, $dm_id]);
            $success_message = "Monde de la campagne mis à jour avec succès.";
            
            // Recharger les données de la campagne
            $stmt = $pdo->prepare("SELECT c.*, u.username AS dm_username, w.name AS world_name, w.id AS world_id FROM campaigns c JOIN users u ON c.dm_id = u.id LEFT JOIN worlds w ON c.world_id = w.id WHERE c.id = ?");
            $stmt->execute([$campaign_id]);
            $campaign = $stmt->fetch();
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la mise à jour du monde: " . $e->getMessage();
        }
    }
    
    // Gestion de l'association d'un lieu à la campagne (MJ/Admin uniquement)
    if (isset($_POST['action']) && $_POST['action'] === 'associate_place' && isDMOrAdmin()) {
        $place_id = (int)($_POST['place_id'] ?? 0);
        
        if ($place_id > 0) {
            try {
                // Vérifier que le lieu appartient au monde de la campagne et n'est pas déjà associé
                $stmt = $pdo->prepare("
                    SELECT p.id FROM places p
                    LEFT JOIN countries c ON p.country_id = c.id
                    WHERE p.id = ? AND c.world_id = ? AND p.id NOT IN (
                        SELECT place_id FROM place_campaigns WHERE campaign_id = ?
                    )
                ");
                $stmt->execute([$place_id, $campaign['world_id'], $campaign_id]);
                $place = $stmt->fetch();
                
                if ($place) {
                    // Associer le lieu à la campagne
                    if (associatePlaceToCampaign($place_id, $campaign_id)) {
                        $success_message = "Lieu associé à la campagne avec succès.";
                    } else {
                        $error_message = "Erreur lors de l'association du lieu à la campagne.";
                    }
                    
                    // Recharger les lieux de la campagne
                    $places = getPlacesWithGeography($campaign_id);
                } else {
                    $error_message = "Ce lieu ne peut pas être associé à cette campagne.";
                }
            } catch (PDOException $e) {
                $error_message = "Erreur lors de l'association du lieu: " . $e->getMessage();
            }
        } else {
            $error_message = "Lieu invalide sélectionné.";
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'apply_to_campaign') {
        $message = sanitizeInput($_POST['message'] ?? '');
        $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
        
        // Validation : le personnage est maintenant obligatoire
        if (!$character_id) {
            $error_message = "Vous devez sélectionner un personnage pour postuler à cette campagne.";
        } else {
            // Vérifier que le personnage appartient bien au joueur et est équipé
            $stmt = $pdo->prepare("SELECT id, is_equipped FROM characters WHERE id = ? AND user_id = ?");
            $stmt->execute([$character_id, $user_id]);
            $character = $stmt->fetch();
            
            if (!$character) {
                $error_message = "Le personnage sélectionné n'existe pas ou ne vous appartient pas.";
            } elseif (!$character['is_equipped']) {
                $error_message = "Le personnage sélectionné n'est pas encore équipé. Vous devez d'abord choisir son équipement de départ.";
            }
        }
        
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



    // Approuver une candidature
    if (isset($_POST['action']) && $_POST['action'] === 'approve_application' && isset($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        $place_id = !empty($_POST['place_id']) ? (int)$_POST['place_id'] : null;
        $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
        
        // Vérifier que la candidature correspond à cette campagne du MJ
        $stmt = $pdo->prepare("SELECT ca.player_id, ca.character_id FROM campaign_applications ca JOIN campaigns c ON ca.campaign_id = c.id WHERE ca.id = ? AND ca.campaign_id = ? AND c.dm_id = ?");
        $stmt->execute([$application_id, $campaign_id, $dm_id]);
        $app = $stmt->fetch();
        if ($app) {
            $player_id = (int)$app['player_id'];
            $app_character_id = (int)$app['character_id'];
            
            // Utiliser le personnage de la candidature si aucun n'est spécifié
            if (!$character_id && $app_character_id) {
                $character_id = $app_character_id;
            }
            
            $pdo->beginTransaction();
            try {
                // Mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE campaign_applications SET status = 'approved' WHERE id = ?");
                $stmt->execute([$application_id]);
                
                // Ajouter comme membre si pas déjà présent
                $stmt = $pdo->prepare("INSERT IGNORE INTO campaign_members (campaign_id, user_id, role) VALUES (?, ?, 'player')");
                $stmt->execute([$campaign_id, $player_id]);
                
                // Si un lieu est spécifié, assigner le joueur au lieu
                if ($place_id) {
                    // Vérifier que le lieu appartient à cette campagne
                    $stmt = $pdo->prepare("
                        SELECT p.id FROM places p
                        INNER JOIN place_campaigns pc ON p.id = pc.place_id
                        WHERE p.id = ? AND pc.campaign_id = ?
                    ");
                    $stmt->execute([$place_id, $campaign_id]);
                    if ($stmt->fetch()) {
                        // Retirer le joueur de tous les autres lieux de la campagne
                        $stmt = $pdo->prepare("
                            DELETE FROM place_players 
                            WHERE player_id = ? AND place_id IN (
                                SELECT p.id FROM places p
                                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                                WHERE pc.campaign_id = ?
                            )
                        ");
                        $stmt->execute([$player_id, $campaign_id]);
                        
                        // Ajouter le joueur au nouveau lieu
                        $stmt = $pdo->prepare("INSERT INTO place_players (place_id, player_id, character_id) VALUES (?, ?, ?)");
                        $stmt->execute([$place_id, $player_id, $character_id]);
                    }
                }
                
                // Notification au joueur
                $title = 'Candidature acceptée';
                $message = 'Votre candidature à la campagne "' . $campaign['title'] . '" a été acceptée.';
                if ($place_id) {
                    $stmt = $pdo->prepare("SELECT title FROM places WHERE id = ?");
                    $stmt->execute([$place_id]);
                    $place = $stmt->fetch();
                    if ($place) {
                        $message .= ' Vous avez été assigné au lieu "' . $place['title'] . '".';
                    }
                }
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'system', ?, ?, ?)");
                $stmt->execute([$player_id, $title, $message, $campaign_id]);
                
                $pdo->commit();
                $success_message = "Candidature approuvée et joueur ajouté à la campagne.";
                if ($place_id) {
                    $success_message .= " Le joueur a été assigné au lieu sélectionné.";
                }
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

    // Gestion du journal (DM propriétaire uniquement)
    if (isset($_POST['action']) && $_POST['action'] === 'create_journal_entry' && $isOwnerDM) {
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
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_journal_entry' && isset($_POST['entry_id']) && $isOwnerDM) {
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
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete_journal_entry' && isset($_POST['entry_id']) && $isOwnerDM) {
        $entry_id = (int)$_POST['entry_id'];
        $stmt = $pdo->prepare("DELETE FROM campaign_journal WHERE id = ? AND campaign_id = ?");
        $stmt->execute([$entry_id, $campaign_id]);
        $success_message = "Événement supprimé du journal.";
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_journal_visibility' && isset($_POST['entry_id']) && $isOwnerDM) {
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
                $country_id = isset($_POST['country_id']) && $_POST['country_id'] ? (int)$_POST['country_id'] : null;
                $region_id = isset($_POST['region_id']) && $_POST['region_id'] ? (int)$_POST['region_id'] : null;
                
                $stmt = $pdo->prepare("INSERT INTO places (title, map_url, notes, position, country_id, region_id) VALUES (?, ?, ?, 0, ?, ?)");
                $stmt->execute([$title, $map_url, $notes, $country_id, $region_id]);
                $place_id = $pdo->lastInsertId();
                
                // Associer le lieu à la campagne
                if (associatePlaceToCampaign($place_id, $campaign_id)) {
                    $success_message = "Lieu créé avec succès.";
                } else {
                    $error_message = "Lieu créé mais erreur lors de l'association à la campagne.";
                }
            }
        } else {
            $error_message = "Le titre du lieu est requis.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_scene' && isset($_POST['place_id'])) {
        $place_id = (int)$_POST['place_id'];
        // Vérifier que le lieu appartient à cette campagne
        $stmt = $pdo->prepare("
            SELECT p.id FROM places p
            INNER JOIN place_campaigns pc ON p.id = pc.place_id
            WHERE p.id = ? AND pc.campaign_id = ?
        ");
        $stmt->execute([$place_id, $campaign_id]);
        if ($stmt->fetch()) {
            // Dissocier le lieu de la campagne
            if (dissociatePlaceFromCampaign($place_id, $campaign_id)) {
                $success_message = "Lieu dissocié de la campagne avec succès.";
            } else {
                $error_message = "Erreur lors de la dissociation du lieu.";
            }
        } else {
            $error_message = "Ce lieu n'appartient pas à cette campagne.";
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'move_scene' && isset($_POST['place_id']) && isset($_POST['direction'])) {
        $place_id = (int)$_POST['place_id'];
        $direction = $_POST['direction'];
        
        // Récupérer la position actuelle
        $stmt = $pdo->prepare("
            SELECT p.position FROM places p
            INNER JOIN place_campaigns pc ON p.id = pc.place_id
            WHERE p.id = ? AND pc.campaign_id = ?
        ");
        $stmt->execute([$place_id, $campaign_id]);
        $scene = $stmt->fetch();
        
        if ($scene) {
            $new_position = $scene['position'] + ($direction === 'up' ? -1 : 1);
            $new_position = max(0, $new_position);
            
            // Échanger avec la lieu adjacente
            $stmt = $pdo->prepare("
                SELECT p.id FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE pc.campaign_id = ? AND p.position = ? AND p.id != ?
            ");
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
            $stmt = $pdo->prepare("
                SELECT p.id FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE p.id IN (?, ?) AND pc.campaign_id = ?
            ");
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

// Récupérer les mondes disponibles (pour le MJ/Admin)
$worlds = [];
if (isDMOrAdmin()) {
    $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $worlds = $stmt->fetchAll();
}

// Récupérer les lieux disponibles dans le monde de la campagne (pour l'association)
$available_places = [];
if (isDMOrAdmin() && !empty($campaign['world_id'])) {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.notes, p.map_url, 
               c.name as country_name, r.name as region_name
        FROM places p
        LEFT JOIN countries c ON p.country_id = c.id
        LEFT JOIN regions r ON p.region_id = r.id
        WHERE c.world_id = ? AND p.id NOT IN (
            SELECT place_id FROM place_campaigns WHERE campaign_id = ?
        )
        ORDER BY c.name, r.name, p.title
    ");
    $stmt->execute([$campaign['world_id'], $campaign_id]);
    $available_places = $stmt->fetchAll();
}

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

// Récupérer les personnages de l'utilisateur pour la candidature (seulement ceux qui sont équipés)
$stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? AND is_equipped = 1 ORDER BY name ASC");
$stmt->execute([$user_id]);
$user_characters = $stmt->fetchAll();

// Vérifier l'équipement de départ pour les personnages du joueur dans cette campagne
$characters_equipment_status = [];
if ($is_member && $user_role === 'player') {
    // D'abord, vérifier quels personnages ont été acceptés dans cette campagne
    $stmt = $pdo->prepare("
        SELECT character_id 
        FROM campaign_applications 
        WHERE campaign_id = ? AND player_id = ? AND status = 'approved'
    ");
    $stmt->execute([$campaign_id, $user_id]);
    $accepted_characters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($user_characters as $char) {
        // Vérifier si le personnage a été accepté dans cette campagne
        $is_accepted = in_array($char['id'], $accepted_characters);
        
        if ($is_accepted) {
            // Vérifier si l'équipement de départ a été choisi
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM place_objects 
                WHERE owner_type = 'player' AND owner_id = ? 
                AND (item_source = 'Équipement de départ' OR item_source = 'Classe')
            ");
            $stmt->execute([$char['id']]);
            $equipment_count = $stmt->fetch()['count'];
            
            $characters_equipment_status[$char['id']] = [
                'name' => $char['name'],
                'equipment_selected' => $equipment_count > 0
            ];
        }
    }
}

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


// Récupérer candidatures
$stmt = $pdo->prepare("SELECT ca.id, ca.player_id, ca.character_id, ca.message, ca.status, ca.created_at, u.username, ch.name AS character_name FROM campaign_applications ca JOIN users u ON ca.player_id = u.id LEFT JOIN characters ch ON ca.character_id = ch.id WHERE ca.campaign_id = ? ORDER BY ca.created_at DESC");
$stmt->execute([$campaign_id]);
$applications = $stmt->fetchAll();

// Récupérer lieux avec hiérarchie géographique
$places = getPlacesWithGeography($campaign_id);

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
    <style>
    /* Classes marron personnalisées */
    .btn-brown {
        background-color: #8B4513;
        border-color: #8B4513;
        color: white;
    }
    .btn-brown:hover {
        background-color: #A0522D;
        border-color: #A0522D;
        color: white;
    }
    .btn-outline-brown {
        color: #8B4513;
        border-color: #8B4513;
    }
    .btn-outline-brown:hover {
        background-color: #8B4513;
        border-color: #8B4513;
        color: white;
    }
    .bg-brown {
        background-color: #8B4513 !important;
    }
    .text-brown {
        color: #8B4513 !important;
    }
    .border-brown {
        border-color: #8B4513 !important;
    }
    .badge.bg-brown {
        background-color: #8B4513 !important;
    }
    .badge.bg-brown-light {
        background-color: #D2B48C !important;
        color: #8B4513 !important;
    }
    .badge.bg-brown-dark {
        background-color: #654321 !important;
        color: white !important;
    }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="me-3"><i class="fas fa-book me-2"></i><?php echo htmlspecialchars($campaign['title']); ?></h1>
                <p class="text-muted mb-0">Créée par <?php echo htmlspecialchars($campaign['dm_username']); ?></p>
            </div>
            <span class="badge bg-<?php echo $campaign['is_public'] ? 'brown' : 'secondary'; ?> fs-6"><?php echo $campaign['is_public'] ? 'Publique' : 'Privée'; ?></span>
        </div>
        <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
        <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>

        <?php if (!empty($campaign['description'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Description</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($campaign['description'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Membres</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <p class="text-muted">Aucun membre pour l'instant.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($members as $m): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($m['username']); ?>
                                            <span class="badge bg-<?php echo $m['role'] === 'dm' ? 'brown-dark' : 'brown'; ?> ms-2"><?php echo $m['role'] === 'dm' ? 'MJ' : 'Joueur'; ?></span>
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted">Depuis <?php echo date('d/m/Y', strtotime($m['joined_at'])); ?></small>
                                            <?php if ($m['role'] !== 'dm' && isDMOrAdmin()): ?>
                                                <form method="POST" onsubmit="return confirm('Exclure ce joueur de la campagne ?');">
                                                    <input type="hidden" name="action" value="remove_member">
                                                    <input type="hidden" name="member_user_id" value="<?php echo (int)$m['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-brown" title="Exclure">
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
                                <button class="btn btn-outline-brown" type="submit"><i class="fas fa-user-plus me-2"></i>Ajouter</button>
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
                        <div class="mt-4 p-3 border rounded bg-brown-light">
                            <h6 class="mb-3 text-brown"><i class="fas fa-check-circle me-2"></i>Vous êtes membre de cette campagne</h6>
                            <p class="mb-3">Vous pouvez maintenant rejoindre la partie et accéder à tous les contenus de la campagne.</p>
                            
                            <?php if (!empty($characters_equipment_status)): ?>
                                <?php 
                                $all_equipment_selected = true;
                                $characters_without_equipment = [];
                                foreach ($characters_equipment_status as $char_id => $status) {
                                    if (!$status['equipment_selected']) {
                                        $all_equipment_selected = false;
                                        $characters_without_equipment[] = $char_id;
                                    }
                                }
                                ?>
                                
                                <?php if ($all_equipment_selected): ?>
                                    <!-- Tous les personnages ont leur équipement -->
                                    <a href="view_scene_player.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-brown">
                                        <i class="fas fa-play me-2"></i>Rejoindre la partie
                                    </a>
                                <?php else: ?>
                                    <!-- Certains personnages n'ont pas choisi leur équipement -->
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Équipement de départ requis</strong><br>
                                        Vous devez d'abord choisir l'équipement de départ pour vos personnages :
                                        <ul class="mb-3 mt-2">
                                            <?php foreach ($characters_without_equipment as $char_id): ?>
                                                <li><?php echo htmlspecialchars($characters_equipment_status[$char_id]['name']); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#selectEquipmentModal">
                                            <i class="fas fa-shield-alt me-2"></i>Choisir l'équipement de départ
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Aucun personnage dans la campagne -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Vous n'avez pas encore de personnage dans cette campagne.
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php elseif (!$is_member && !$user_application): ?>
                        <!-- Formulaire de candidature pour les joueurs -->
                        <div class="mt-4 p-3 border rounded bg-light">
                            <h6 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Postuler à cette campagne</h6>
                            
                            <?php if (empty($user_characters)): ?>
                                <!-- Aucun personnage disponible -->
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Personnage requis</strong><br>
                                    Vous devez d'abord créer un personnage pour pouvoir postuler à cette campagne.
                                    <div class="mt-3">
                                        <a href="create_character.php" class="btn btn-warning">
                                            <i class="fas fa-user-plus me-2"></i>Créer un personnage
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="apply_to_campaign">
                                    <div class="mb-3">
                                        <label class="form-label">Personnage <span class="text-danger">*</span></label>
                                        <select name="character_id" class="form-select" required>
                                            <option value="">Sélectionnez un personnage</option>
                                            <?php foreach ($user_characters as $char): ?>
                                                <option value="<?php echo $char['id']; ?>"><?php echo htmlspecialchars($char['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Vous devez sélectionner un personnage pour postuler à cette campagne.</div>
                                    </div>
                                <div class="mb-3">
                                    <label class="form-label">Message de candidature</label>
                                    <textarea name="message" class="form-control" rows="3" placeholder="Présentez-vous et expliquez pourquoi vous souhaitez rejoindre cette campagne..."></textarea>
                                </div>
                                    <button type="submit" class="btn btn-brown">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer ma candidature
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($user_application): ?>
                        <!-- Statut de la candidature -->
                        <div class="mt-4 p-3 border rounded">
                            <h6 class="mb-2"><i class="fas fa-clock me-2"></i>Votre candidature</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php 
                                    echo $user_application['status'] === 'pending' ? 'warning' : 
                                        ($user_application['status'] === 'approved' ? 'brown' : 'brown-dark'); 
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

            <!-- Zone Monde -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-globe-americas me-2"></i>Monde</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isDMOrAdmin()): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_campaign_world">
                                <div class="mb-3">
                                    <label for="worldSelect" class="form-label">Monde de la campagne</label>
                                    <select class="form-select" id="worldSelect" name="world_id">
                                        <option value="">Aucun monde sélectionné</option>
                                        <?php foreach ($worlds as $world): ?>
                                            <option value="<?php echo (int)$world['id']; ?>" 
                                                    <?php echo ($campaign['world_id'] == $world['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($world['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        Sélectionnez le monde dans lequel se déroule cette campagne.
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-brown">
                                    <i class="fas fa-save me-2"></i>Mettre à jour
                                </button>
                            </form>
                        <?php else: ?>
                            <?php if (!empty($campaign['world_name'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-globe-americas me-2 text-brown"></i>
                                    <span class="fw-bold"><?php echo htmlspecialchars($campaign['world_name']); ?></span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Cette campagne se déroule dans le monde "<?php echo htmlspecialchars($campaign['world_name']); ?>".</p>
                            <?php else: ?>
                                <div class="text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun monde n'a été défini pour cette campagne.
                                </div>
                            <?php endif; ?>
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
                        <div class="btn-group" role="group">
                            <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#associatePlaceModal">
                                <i class="fas fa-link"></i> Associer un lieu
                            </button>
                            <a href="manage_place_campaigns.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-cogs"></i> Gérer les associations
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($places)): ?>
                            <p class="text-muted">Aucun lieu créé.</p>
                        <?php else: ?>
                            <!-- Filtres -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="filterCountry" class="form-label">Filtrer par pays</label>
                                    <select class="form-select" id="filterCountry">
                                        <option value="">Tous les pays</option>
                                        <?php
                                        $countries = getCountries();
                                        foreach ($countries as $country):
                                        ?>
                                            <option value="<?php echo htmlspecialchars($country['name']); ?>"><?php echo htmlspecialchars($country['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterRegion" class="form-label">Filtrer par région</label>
                                    <select class="form-select" id="filterRegion">
                                        <option value="">Toutes les régions</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterPlayers" class="form-label">Filtrer par présence</label>
                                    <select class="form-select" id="filterPlayers">
                                        <option value="">Tous les lieux</option>
                                        <option value="with-players">Avec joueurs</option>
                                        <option value="without-players">Sans joueurs</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="searchPlace" class="form-label">Rechercher</label>
                                    <input type="text" class="form-control" id="searchPlace" placeholder="Nom du lieu...">
                                </div>
                            </div>
                            
                            <!-- Tableau des lieux -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="placesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="world">
                                                <i class="fas fa-globe-americas me-1"></i>Monde
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" data-column="country">
                                                <i class="fas fa-flag me-1"></i>Pays
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" data-column="region">
                                                <i class="fas fa-map-marker-alt me-1"></i>Région
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" data-column="title">
                                                <i class="fas fa-photo-video me-1"></i>Lieu
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" data-column="players">
                                                <i class="fas fa-users me-1"></i>Joueurs
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($places as $place): ?>
                                            <?php $hasPlayers = hasPlayersInPlace($place['id']); ?>
                                            <tr data-world="<?php echo htmlspecialchars($place['world_name'] ?? ''); ?>"
                                                data-country="<?php echo htmlspecialchars($place['country_name'] ?? ''); ?>" 
                                                data-region="<?php echo htmlspecialchars($place['region_name'] ?? ''); ?>"
                                                data-title="<?php echo htmlspecialchars($place['title']); ?>"
                                                data-players="<?php echo $hasPlayers ? 'with-players' : 'without-players'; ?>">
                                                <td>
                                                    <?php if ($place['world_name']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($place['world_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($place['country_name']): ?>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($place['country_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($place['region_name']): ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($place['region_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_place.php?id=<?php echo (int)$place['id']; ?>" class="text-decoration-none fw-bold">
                                                        <?php echo htmlspecialchars($place['title']); ?>
                                                    </a>
                                                    <?php if (!empty($place['notes'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($place['notes'], 0, 50)) . (strlen($place['notes']) > 50 ? '...' : ''); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($hasPlayers): ?>
                                                        <span class="badge bg-success" title="Joueurs présents">
                                                            <i class="fas fa-users"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted" title="Aucun joueur">
                                                            <i class="fas fa-user-slash"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="view_place.php?id=<?php echo (int)$place['id']; ?>" class="btn btn-outline-primary" title="Voir le lieu">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="move_scene">
                                                                        <input type="hidden" name="place_id" value="<?php echo (int)$place['id']; ?>">
                                                                        <input type="hidden" name="direction" value="up">
                                                                        <button class="dropdown-item" type="submit"><i class="fas fa-arrow-up me-2"></i>Monter</button>
                                                                    </form>
                                                                </li>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="move_scene">
                                                                        <input type="hidden" name="place_id" value="<?php echo (int)$place['id']; ?>">
                                                                        <input type="hidden" name="direction" value="down">
                                                                        <button class="dropdown-item" type="submit"><i class="fas fa-arrow-down me-2"></i>Descendre</button>
                                                                    </form>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce lieu ?')">
                                                                        <input type="hidden" name="action" value="delete_scene">
                                                                        <input type="hidden" name="place_id" value="<?php echo (int)$place['id']; ?>">
                                                                        <button class="dropdown-item text-danger" type="submit"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                        <?php if ($isOwnerDM): ?>
                        <button class="btn btn-brown btn-sm" data-bs-toggle="modal" data-bs-target="#createJournalModal">
                            <i class="fas fa-plus"></i> Nouvel événement
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($journalEntries)): ?>
                            <p class="text-muted">Aucun événement dans le journal.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($journalEntries as $entry): ?>
                                    <div class="col-12">
                                        <div class="card border-<?php echo $entry['is_public'] ? 'brown' : 'brown'; ?>">
                                            <div class="card-header bg-<?php echo $entry['is_public'] ? 'brown' : 'brown'; ?> text-white d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-<?php echo $entry['is_public'] ? 'eye' : 'eye-slash'; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($entry['title']); ?>
                                                </h6>
                                                <?php if ($isOwnerDM): ?>
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
                                                <?php endif; ?>
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
                                                        if ($a['status'] === 'approved') $badge = 'brown';
                                                        if ($a['status'] === 'declined') $badge = 'brown-dark';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo $a['status']; ?></span>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?></small></td>
                                                <td class="text-end">
                                                    <?php if ($a['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-brown" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $a['id']; ?>">
                                                            <i class="fas fa-check me-1"></i>Accepter
                                                        </button>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Refuser cette candidature ?');">
                                                            <input type="hidden" name="action" value="decline_application">
                                                            <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-brown"><i class="fas fa-times me-1"></i>Refuser</button>
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
                                                            <button class="btn btn-sm btn-outline-brown"><i class="fas fa-undo me-1"></i>Annuler le refus</button>
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
        <?php endif; ?>
    </div>


    <!-- Modal Associer un lieu -->
    <div class="modal fade" id="associatePlaceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Associer un lieu à la campagne</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="associate_place">
                        
                        <?php if (empty($campaign['world_id'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Aucun monde n'est défini pour cette campagne. 
                                Veuillez d'abord sélectionner un monde dans la zone "Monde" pour pouvoir associer des lieux.
                            </div>
                        <?php elseif (empty($available_places)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Information :</strong> Aucun lieu disponible dans le monde "<?php echo htmlspecialchars($campaign['world_name']); ?>".
                                Tous les lieux de ce monde sont déjà associés à des campagnes ou il n'y a pas encore de lieux créés.
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Lieux disponibles dans le monde "<?php echo htmlspecialchars($campaign['world_name']); ?>"</label>
                                <div class="form-text mb-3">Sélectionnez un lieu à associer à cette campagne :</div>
                                
                                <div class="row g-3">
                                    <?php foreach ($available_places as $place): ?>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="place_id" 
                                                               id="place_<?php echo $place['id']; ?>" 
                                                               value="<?php echo $place['id']; ?>">
                                                        <label class="form-check-label w-100" for="place_<?php echo $place['id']; ?>">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($place['title']); ?></h6>
                                                                    <?php if (!empty($place['country_name'])): ?>
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-globe me-1"></i><?php echo htmlspecialchars($place['country_name']); ?>
                                                                            <?php if (!empty($place['region_name'])): ?>
                                                                                <i class="fas fa-map-marker-alt me-1 ms-2"></i><?php echo htmlspecialchars($place['region_name']); ?>
                                                                            <?php endif; ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($place['notes'])): ?>
                                                                        <p class="mb-0 mt-2 small text-muted">
                                                                            <?php echo htmlspecialchars(truncateText($place['notes'], 100)); ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($place['map_url'])): ?>
                                                                    <div class="ms-2">
                                                                        <img src="<?php echo htmlspecialchars($place['map_url']); ?>" 
                                                                             class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;"
                                                                             alt="Plan du lieu">
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <?php if (!empty($campaign['world_id']) && !empty($available_places)): ?>
                            <button type="submit" class="btn btn-brown">
                                <i class="fas fa-link me-2"></i>Associer le lieu
                            </button>
                        <?php endif; ?>
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
                        <button type="submit" class="btn btn-brown">Transférer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal Créer Événement Journal -->
    <?php if ($isOwnerDM): ?>
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
                        <button type="submit" class="btn btn-brown">Créer l'événement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Modifier Événement Journal -->
    <?php if ($isOwnerDM): ?>
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
                        <button type="submit" class="btn btn-brown">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
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
        
        // Gestion de la sélection pays/région
        document.getElementById('sceneCountry').addEventListener('change', function() {
            var countryId = this.value;
            var regionSelect = document.getElementById('sceneRegion');
            
            // Vider la liste des régions
            regionSelect.innerHTML = '<option value="">-- Sélectionner une région --</option>';
            
            if (countryId) {
                // Charger les régions du pays sélectionné via AJAX
                fetch('get_regions.php?country_id=' + countryId)
                    .then(response => response.json())
                    .then(regions => {
                        regions.forEach(function(region) {
                            var option = document.createElement('option');
                            option.value = region.id;
                            option.textContent = region.name;
                            regionSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des régions:', error);
                    });
            }
        });
        
        // Gestion du tableau des lieux - Tri et filtres
        let currentSort = { column: null, direction: 'asc' };
        
        // Fonction de tri
        function sortTable(column) {
            const table = document.getElementById('placesTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Déterminer la direction du tri
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.direction = 'asc';
            }
            currentSort.column = column;
            
            // Mettre à jour les icônes de tri
            document.querySelectorAll('.sortable i.fa-sort').forEach(icon => {
                icon.className = 'fas fa-sort ms-1';
            });
            const currentHeader = document.querySelector(`[data-column="${column}"] i.fa-sort`);
            currentHeader.className = currentSort.direction === 'asc' ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
            
            // Trier les lignes
            rows.sort((a, b) => {
                let aValue = a.getAttribute(`data-${column}`) || '';
                let bValue = b.getAttribute(`data-${column}`) || '';
                
                // Tri spécial pour les joueurs
                if (column === 'players') {
                    aValue = aValue === 'with-players' ? 1 : 0;
                    bValue = bValue === 'with-players' ? 1 : 0;
                }
                
                if (currentSort.direction === 'asc') {
                    return aValue > bValue ? 1 : -1;
                } else {
                    return aValue < bValue ? 1 : -1;
                }
            });
            
            // Réorganiser les lignes dans le DOM
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Ajouter les événements de clic sur les en-têtes
        document.querySelectorAll('.sortable').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(header.getAttribute('data-column'));
            });
        });
        
        // Fonction de filtrage
        function filterTable() {
            const countryFilter = document.getElementById('filterCountry').value.toLowerCase();
            const regionFilter = document.getElementById('filterRegion').value.toLowerCase();
            const playersFilter = document.getElementById('filterPlayers').value;
            const searchFilter = document.getElementById('searchPlace').value.toLowerCase();
            
            const rows = document.querySelectorAll('#placesTable tbody tr');
            
            rows.forEach(row => {
                const country = row.getAttribute('data-country').toLowerCase();
                const region = row.getAttribute('data-region').toLowerCase();
                const title = row.getAttribute('data-title').toLowerCase();
                const players = row.getAttribute('data-players');
                
                let show = true;
                
                if (countryFilter && !country.includes(countryFilter)) show = false;
                if (regionFilter && !region.includes(regionFilter)) show = false;
                if (playersFilter && players !== playersFilter) show = false;
                if (searchFilter && !title.includes(searchFilter)) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Ajouter les événements de filtrage
        document.getElementById('filterCountry').addEventListener('change', filterTable);
        document.getElementById('filterRegion').addEventListener('change', filterTable);
        document.getElementById('filterPlayers').addEventListener('change', filterTable);
        document.getElementById('searchPlace').addEventListener('input', filterTable);
        
        // Gestion de la sélection pays/région pour les filtres
        document.getElementById('filterCountry').addEventListener('change', function() {
            const countryId = this.value;
            const regionSelect = document.getElementById('filterRegion');
            
            // Vider la liste des régions
            regionSelect.innerHTML = '<option value="">Toutes les régions</option>';
            
            if (countryId) {
                // Trouver l'ID du pays sélectionné
                const countries = <?php echo json_encode($countries); ?>;
                const selectedCountry = countries.find(c => c.name === countryId);
                
                if (selectedCountry) {
                    // Charger les régions du pays sélectionné
                    fetch('get_regions.php?country_id=' + selectedCountry.id)
                        .then(response => response.json())
                        .then(regions => {
                            regions.forEach(function(region) {
                                var option = document.createElement('option');
                                option.value = region.name;
                                option.textContent = region.name;
                                regionSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des régions:', error);
                        });
                }
            }
        });
    });
    </script>

    <!-- Modal de sélection d'équipement -->
    <div class="modal fade" id="selectEquipmentModal" tabindex="-1" aria-labelledby="selectEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectEquipmentModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Choisir l'équipement de départ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Sélectionnez le personnage pour lequel vous voulez choisir l'équipement de départ :</p>
                    <div class="list-group">
                        <?php 
                        $characters_without_equipment_modal = [];
                        foreach ($characters_equipment_status as $char_id => $status) {
                            if (!$status['equipment_selected']) {
                                $characters_without_equipment_modal[] = $char_id;
                            }
                        }
                        ?>
                        <?php foreach ($characters_without_equipment_modal as $char_id): ?>
                            <a href="select_starting_equipment.php?campaign_id=<?php echo $campaign_id; ?>&character_id=<?php echo $char_id; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($characters_equipment_status[$char_id]['name']); ?></h6>
                                    <small class="text-muted">Cliquez pour choisir l'équipement</small>
                                </div>
                                <p class="mb-1">Équipement de départ non sélectionné</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals d'approbation des candidatures -->
    <?php foreach ($applications as $a): ?>
        <?php if ($a['status'] === 'pending'): ?>
            <div class="modal fade" id="approveModal<?php echo $a['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $a['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approveModalLabel<?php echo $a['id']; ?>">
                                <i class="fas fa-check me-2"></i>Accepter la candidature
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="approve_application">
                                <input type="hidden" name="application_id" value="<?php echo $a['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Joueur :</strong></label>
                                    <p class="mb-0"><?php echo htmlspecialchars($a['username']); ?></p>
                                </div>
                                
                                <?php if (!empty($a['character_id'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Personnage :</strong></label>
                                        <p class="mb-0">
                                            <span class="badge bg-secondary">#<?php echo (int)$a['character_id']; ?></span>
                                            <?php echo htmlspecialchars($a['character_name'] ?? 'Personnage'); ?>
                                        </p>
                                        <input type="hidden" name="character_id" value="<?php echo (int)$a['character_id']; ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="place_id_<?php echo $a['id']; ?>" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Assigner à un lieu <span class="text-muted">(optionnel)</span>
                                    </label>
                                    <select name="place_id" id="place_id_<?php echo $a['id']; ?>" class="form-select">
                                        <option value="">Aucun lieu spécifique</option>
                                        <?php foreach ($places as $place): ?>
                                            <option value="<?php echo (int)$place['id']; ?>">
                                                <?php echo htmlspecialchars($place['title']); ?>
                                                <?php if (!empty($place['country_name'])): ?>
                                                    (<?php echo htmlspecialchars($place['country_name']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        Si vous sélectionnez un lieu, le joueur sera automatiquement assigné à ce lieu.
                                        Sinon, il pourra être assigné plus tard via la gestion des lieux.
                                    </div>
                                </div>
                                
                                <?php if (!empty($a['message'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Message de candidature :</strong></label>
                                        <div class="border rounded p-2 bg-light">
                                            <small><?php echo nl2br(htmlspecialchars($a['message'])); ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-brown">
                                    <i class="fas fa-check me-1"></i>Accepter la candidature
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
