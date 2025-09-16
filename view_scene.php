<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$place_id = (int)$_GET['id'];
$isModal = isset($_GET['modal']);

// Charger la lieu et sa campagne
$stmt = $pdo->prepare("SELECT s.*, c.title AS campaign_title, c.id AS campaign_id, c.dm_id, u.username AS dm_username FROM places s JOIN campaigns c ON s.campaign_id = c.id JOIN users u ON c.dm_id = u.id WHERE s.id = ?");
$stmt->execute([$place_id]);
$place = $stmt->fetch();

if (!$place) {
    header('Location: index.php');
    exit();
}

$dm_id = (int)$place['dm_id'];
$isOwnerDM = (isDM() && $_SESSION['user_id'] === $dm_id);

// DEBUG: Logs pour déboguer les permissions
error_log("DEBUG view_scene.php - User ID: " . ($_SESSION['user_id'] ?? 'NOT_SET'));
error_log("DEBUG view_scene.php - DM ID: " . $dm_id);
error_log("DEBUG view_scene.php - isDM(): " . (isDM() ? 'true' : 'false'));
error_log("DEBUG view_scene.php - isOwnerDM: " . ($isOwnerDM ? 'true' : 'false'));

// Autoriser également les membres de la campagne à voir la lieu
$canView = $isOwnerDM;
if (!$canView) {
    $stmt = $pdo->prepare("SELECT 1 FROM campaign_members WHERE campaign_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$place['campaign_id'], $_SESSION['user_id']]);
    $canView = (bool)$stmt->fetch();
}

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Récupérer les joueurs présents dans cette lieu
$stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo, ch.class_id FROM place_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.place_id = ? ORDER BY u.username ASC");
$stmt->execute([$place_id]);
$placePlayers = $stmt->fetchAll();

// Récupérer les PNJ de cette lieu
$stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo FROM place_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.place_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
$stmt->execute([$place_id]);
$placeNpcs = $stmt->fetchAll();

// Récupérer les monstres de cette lieu
$stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM place_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
$stmt->execute([$place_id]);
$placeMonsters = $stmt->fetchAll();

// Récupérer les positions des pions
$stmt = $pdo->prepare("
    SELECT token_type, entity_id, position_x, position_y, is_on_map
    FROM place_tokens 
    WHERE place_id = ?
");
$stmt->execute([$place_id]);
$tokenPositions = [];
while ($row = $stmt->fetch()) {
    $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
        'x' => (int)$row['position_x'],
        'y' => (int)$row['position_y'],
        'is_on_map' => (bool)$row['is_on_map']
    ];
}

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
                $ins = $pdo->prepare("INSERT INTO place_npcs (place_id, name, npc_character_id) VALUES (?, ?, ?)");
                $ins->execute([$place_id, $char['name'], $character_id]);
                $success_message = "PNJ (personnage du MJ) ajouté à la lieu.";
                
                // Recharger les PNJ
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM place_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.place_id = ? ORDER BY sn.name ASC");
                $stmt->execute([$place_id]);
                $placeNpcs = $stmt->fetchAll();
            } else {
                $error_message = "Personnage invalide (doit appartenir au MJ).";
            }
        } else {
            $error_message = "Veuillez sélectionner un personnage.";
        }
    }
    
    // Exclure un joueur du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_player' && isset($_POST['player_id'])) {
        $player_id = (int)$_POST['player_id'];
        $stmt = $pdo->prepare("DELETE FROM place_players WHERE place_id = ? AND player_id = ?");
        $stmt->execute([$place_id, $player_id]);
        $success_message = "Joueur retiré du lieu.";
        
        // Recharger les joueurs
        $stmt = $pdo->prepare("SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo FROM place_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.place_id = ? ORDER BY u.username ASC");
        $stmt->execute([$place_id]);
        $placePlayers = $stmt->fetchAll();
    }
    
    // Exclure un PNJ du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_npc' && isset($_POST['npc_name'])) {
        $npc_name = $_POST['npc_name'];
        $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE place_id = ? AND name = ?");
        $stmt->execute([$place_id, $npc_name]);
        $success_message = "PNJ retiré du lieu.";
        
        // Recharger les PNJ
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, c.profile_photo AS character_profile_photo FROM place_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.place_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
        $stmt->execute([$place_id]);
        $placeNpcs = $stmt->fetchAll();
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
                $monster_name = $monster['name'];
                if ($quantity > 1) {
                    $monster_name .= " (x{$quantity})";
                }
                
                $stmt = $pdo->prepare("INSERT INTO place_npcs (place_id, name, monster_id, quantity, current_hit_points) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$place_id, $monster_name, $monster_id, $quantity, $monster['hit_points']]);
                $success_message = "Monstre ajouté à la lieu.";
                
                // Recharger les monstres
                $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM place_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
                $stmt->execute([$place_id]);
                $placeMonsters = $stmt->fetchAll();
            } else {
                $error_message = "Monstre introuvable.";
            }
        } else {
            $error_message = "Veuillez sélectionner un monstre et spécifier une quantité valide.";
        }
    }
    
    // Retirer un monstre du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_monster' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
        $stmt->execute([$place_id, $npc_id]);
        $success_message = "Monstre retiré du lieu.";
        
        // Recharger les monstres
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM place_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
        $stmt->execute([$place_id]);
        $placeMonsters = $stmt->fetchAll();
    }
    
    // Basculer la visibilité d'un PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_npc_visibility' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $stmt = $pdo->prepare("UPDATE place_npcs SET is_visible = NOT is_visible WHERE place_id = ? AND id = ? AND monster_id IS NULL");
        $stmt->execute([$place_id, $npc_id]);
        $success_message = "Visibilité du PNJ mise à jour.";
        
        // Recharger les PNJ
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, c.profile_photo AS character_profile_photo FROM place_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.place_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
        $stmt->execute([$place_id]);
        $placeNpcs = $stmt->fetchAll();
    }
    
    // Basculer la visibilité d'un monstre
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_monster_visibility' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $stmt = $pdo->prepare("UPDATE place_npcs SET is_visible = NOT is_visible WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
        $stmt->execute([$place_id, $npc_id]);
        $success_message = "Visibilité du monstre mise à jour.";
        
        // Recharger les monstres
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class FROM place_npcs sn JOIN dnd_monsters m ON sn.monster_id = m.id WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL ORDER BY sn.name ASC");
        $stmt->execute([$place_id]);
        $placeMonsters = $stmt->fetchAll();
    }
    
    // Basculer l'identification d'un PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_npc_identification' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $stmt = $pdo->prepare("UPDATE place_npcs SET is_identified = NOT is_identified WHERE place_id = ? AND id = ? AND monster_id IS NULL");
        $stmt->execute([$place_id, $npc_id]);
        $success_message = "Identification du PNJ mise à jour.";
        
        // Recharger les PNJ
        $stmt = $pdo->prepare("SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo FROM place_npcs sn LEFT JOIN characters c ON sn.npc_character_id = c.id WHERE sn.place_id = ? AND sn.monster_id IS NULL ORDER BY sn.name ASC");
        $stmt->execute([$place_id]);
        $placeNpcs = $stmt->fetchAll();
    }
    
    // Mettre à jour le nom du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'update_title') {
        $new_title = trim($_POST['scene_title'] ?? '');
        
        if ($new_title === '') {
            $error_message = "Le nom du lieu ne peut pas être vide.";
        } else {
            // Vérifier que la lieu existe et appartient à la bonne campagne
            $check_stmt = $pdo->prepare("SELECT id, title FROM places WHERE id = ? AND campaign_id = ?");
            $check_stmt->execute([$place_id, $place['campaign_id']]);
            $current_scene = $check_stmt->fetch();
            
            if (!$current_scene) {
                $error_message = "Lieu introuvable ou accès refusé.";
            } else {
                $stmt = $pdo->prepare("UPDATE places SET title = ? WHERE id = ? AND campaign_id = ?");
                $result = $stmt->execute([$new_title, $place_id, $place['campaign_id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $success_message = "Nom du lieu mis à jour avec succès.";
                    
                    // Recharger les données du lieu
                    $stmt = $pdo->prepare("SELECT s.*, c.title AS campaign_title, c.id AS campaign_id, c.dm_id, u.username AS dm_username FROM places s JOIN campaigns c ON s.campaign_id = c.id JOIN users u ON c.dm_id = u.id WHERE s.id = ?");
                    $stmt->execute([$place_id]);
                    $place = $stmt->fetch();
                    
                    if (!$place) {
                        $error_message = "Erreur lors du rechargement des données du lieu.";
                    }
                } else {
                    $error_message = "Erreur lors de la mise à jour du nom du lieu. Aucune ligne modifiée.";
                }
            }
        }
    }
    
    // Mettre à jour le plan du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'update_map') {
        $notes = trim($_POST['notes'] ?? '');
        
        // Upload de plan si fourni
        $newMapUrl = $place['map_url']; // Conserver l'ancien plan par défaut
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
            $stmt = $pdo->prepare("UPDATE places SET map_url = ?, notes = ? WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$newMapUrl, $notes, $place_id, $place['campaign_id']]);
            $success_message = "Plan du lieu mis à jour.";
            
            // Recharger les données du lieu
            $stmt = $pdo->prepare("SELECT s.*, c.title AS campaign_title, c.id AS campaign_id, c.dm_id, u.username AS dm_username FROM places s JOIN campaigns c ON s.campaign_id = c.id JOIN users u ON c.dm_id = u.id WHERE s.id = ?");
            $stmt->execute([$place_id]);
            $place = $stmt->fetch();
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
                        $stmt = $pdo->prepare("SELECT u.username, ch.id AS character_id, ch.name AS character_name FROM place_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.place_id = ? AND sp.player_id = ?");
                        $stmt->execute([$place_id, $target_id]);
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
                                'Attribution MJ - Lieu ' . $place['title']
                            ]);
                            $insert_success = true;
                            $target_name = $target['character_name'] ?: $target['username'];
                        } else {
                            $error_message = "Personnage joueur invalide ou sans personnage créé.";
                        }
                        break;
                        
                    case 'npc':
                        // Récupérer les informations du PNJ
                        $stmt = $pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ?");
                        $stmt->execute([$target_id, $place_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter l'objet à l'équipement du PNJ
                            $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $place_id,
                                $item_id,
                                $item_info['nom'],
                                $item_info['type'],
                                $item_info['description'],
                                $item_info['source'],
                                $assign_notes,
                                'Attribution MJ - Lieu ' . $place['title']
                            ]);
                            $insert_success = true;
                            $target_name = $target['name'];
                        } else {
                            $error_message = "PNJ introuvable.";
                        }
                        break;
                        
                    case 'monster':
                        // Récupérer les informations du monstre
                        $stmt = $pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ?");
                        $stmt->execute([$target_id, $place_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter l'objet à l'équipement du monstre
                            $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $place_id,
                                $item_id,
                                $item_info['nom'],
                                $item_info['type'],
                                $item_info['description'],
                                $item_info['source'],
                                $assign_notes,
                                'Attribution MJ - Lieu ' . $target['name']
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
    
    // Attribuer un poison à un PNJ ou personnage joueur
    if (isset($_POST['action']) && $_POST['action'] === 'assign_poison') {
        $poison_id = $_POST['poison_id'];
        $poison_name = $_POST['poison_name'];
        $assign_target = $_POST['assign_target'];
        $assign_notes = $_POST['assign_notes'] ?? '';
        
        if (!empty($assign_target)) {
            // Décomposer la cible (player_123, npc_456, monster_789)
            $target_parts = explode('_', $assign_target);
            $target_type = $target_parts[0];
            $target_id = (int)$target_parts[1];
            
            // Récupérer les informations du poison depuis la base de données
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
            $stmt->execute([$poison_id]);
            $poison_info = $stmt->fetch();
            
            if (!$poison_info) {
                $error_message = "Poison introuvable.";
            } else {
                $target_name = '';
                $insert_success = false;
                
                switch ($target_type) {
                    case 'player':
                        // Récupérer les informations du personnage joueur
                        $stmt = $pdo->prepare("SELECT u.username, ch.id AS character_id, ch.name AS character_name FROM place_players sp JOIN users u ON sp.player_id = u.id LEFT JOIN characters ch ON sp.character_id = ch.id WHERE sp.place_id = ? AND sp.player_id = ?");
                        $stmt->execute([$place_id, $target_id]);
                        $target = $stmt->fetch();
                        
                        if ($target && $target['character_id']) {
                            // Ajouter le poison à l'équipement du personnage
                            $stmt = $pdo->prepare("INSERT INTO character_equipment (character_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target['character_id'],
                                $poison_id,
                                $poison_info['nom'],
                                $poison_info['type'],
                                $poison_info['description'],
                                $poison_info['source'],
                                $assign_notes,
                                'Attribution MJ - Lieu ' . $place['title']
                            ]);
                            $insert_success = true;
                            $target_name = $target['character_name'] ?: $target['username'];
                        } else {
                            $error_message = "Personnage joueur invalide ou sans personnage créé.";
                        }
                        break;
                        
                    case 'npc':
                        // Récupérer les informations du PNJ
                        $stmt = $pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ?");
                        $stmt->execute([$target_id, $place_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter le poison à l'équipement du PNJ
                            $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $place_id,
                                $poison_id,
                                $poison_info['nom'],
                                $poison_info['type'],
                                $poison_info['description'],
                                $poison_info['source'],
                                $assign_notes,
                                'Attribution MJ - Lieu ' . $target['name']
                            ]);
                            $insert_success = true;
                            $target_name = $target['name'];
                        } else {
                            $error_message = "PNJ introuvable.";
                        }
                        break;
                        
                    case 'monster':
                        // Récupérer les informations du monstre
                        $stmt = $pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ?");
                        $stmt->execute([$target_id, $place_id]);
                        $target = $stmt->fetch();
                        
                        if ($target) {
                            // Ajouter le poison à l'équipement du monstre
                            $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $target_id,
                                $place_id,
                                $poison_id,
                                $poison_info['nom'],
                                $poison_info['type'],
                                $poison_info['description'],
                                $poison_info['source'],
                                $assign_notes,
                                'Attribution MJ - Lieu ' . $target['name']
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
                    $success_message = "Le poison \"{$poison_name}\" a été attribué à {$target_name} et ajouté à son équipement.";
                    if (!empty($assign_notes)) {
                        $success_message .= " Notes: {$assign_notes}";
                    }
                } elseif (!$insert_success && !isset($error_message)) {
                    $error_message = "Erreur lors de l'ajout du poison à l'équipement.";
                }
            }
        } else {
            $error_message = "Veuillez sélectionner un destinataire pour le poison.";
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

// Récupérer les autres lieux de la campagne pour navigation
$stmt = $pdo->prepare("SELECT id, title, position FROM places WHERE campaign_id = ? ORDER BY position ASC, created_at ASC");
$stmt->execute([$place['campaign_id']]);
$allScenes = $stmt->fetchAll();



$currentPosition = $place['position'];
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
    <title>Lieu: <?php echo htmlspecialchars($place['title']); ?> - JDR 4 MJ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="JDR 4 MJ" height="30" class="me-2">
                JDR 4 MJ
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="view_campaign.php?id=<?php echo (int)$place['campaign_id']; ?>">Retour Campagne</a></li>
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
    
    <?php
    // Vérifier si le joueur connecté est présent dans ce lieu
    $currentPlayer = null;
    foreach ($placePlayers as $player) {
        if ($player['player_id'] == $_SESSION['user_id']) {
            $currentPlayer = $player;
            break;
        }
    }
    ?>
    
    <?php if ($currentPlayer && $currentPlayer['character_id']): ?>
        <!-- Section spéciale pour le joueur connecté -->
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle me-2"></i>
                <div>
                    <strong>Vous êtes présent dans ce lieu</strong>
                    <br>
                    <small>Personnage: <?php echo htmlspecialchars($currentPlayer['character_name']); ?></small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="view_character.php?id=<?php echo (int)$currentPlayer['character_id']; ?>&dm_campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-alt me-1"></i>Ma feuille de personnage
                </a>
                <?php if ($currentPlayer['class_id'] && canCastSpells($currentPlayer['class_id'])): ?>
                    <a href="grimoire.php?id=<?php echo (int)$currentPlayer['character_id']; ?>" class="btn btn-info" target="_blank">
                        <i class="fas fa-book-open me-1"></i>Mon Grimoire
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <?php if ($isOwnerDM): ?>
                <div class="d-flex align-items-center">
                    <h1 class="me-3"><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editTitleForm">
                        <i class="fas fa-edit me-1"></i>Modifier le nom
                    </button>
                </div>
                <div class="collapse mt-2" id="editTitleForm">
                    <div class="card card-body">
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="action" value="update_title">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="scene_title" value="<?php echo htmlspecialchars($place['title']); ?>" required>
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
                <h1><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
            <?php endif; ?>
            <p class="text-muted mb-0">
                Campagne: <?php echo htmlspecialchars($place['campaign_title']); ?> • MJ: <?php echo htmlspecialchars($place['dm_username']); ?>
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
                    <span>Plan du lieu</span>
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
                                <h6>Modifier le plan du lieu</h6>
                                <form method="POST" enctype="multipart/form-data" class="row g-3">
                                    <input type="hidden" name="action" value="update_map">
                                    <div class="col-12">
                                        <label class="form-label">Téléverser un plan (image)</label>
                                        <input type="file" class="form-control" name="plan_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 2 Mo)</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes du MJ</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Notes internes sur cette lieu..."><?php echo htmlspecialchars($place['notes'] ?? ''); ?></textarea>
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
                    
                    <?php if (!empty($place['map_url'])): ?>
                        <div class="position-relative">
                            <!-- Zone du plan avec pions -->
                            <div id="mapContainer" class="position-relative" style="display: inline-block;">
                                <img id="mapImage" src="<?php echo htmlspecialchars($place['map_url']); ?>" class="img-fluid rounded" alt="Plan du lieu" style="max-height: 500px; cursor: crosshair;">
                                
                                <!-- Zone des pions sur le côté -->
                                <div id="tokenSidebar" class="position-absolute" style="right: -120px; top: 0; width: 100px; height: 500px; border: 2px dashed #ccc; border-radius: 8px; background: rgba(248, 249, 250, 0.8); padding: 10px; overflow-y: auto;">
                                    <div class="text-center mb-2">
                                        <small class="text-muted">Pions</small>
                                    </div>
                                    
                                    <!-- Pions des joueurs -->
                                    <?php foreach ($placePlayers as $player): ?>
                                        <?php 
                                        $tokenKey = 'player_' . $player['player_id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $displayName = $player['character_name'] ?: $player['username'];
                                        $imageUrl = !empty($player['profile_photo']) ? $player['profile_photo'] : 'images/default_character.png';
                                        ?>
                                        <div class="token" 
                                             data-token-type="player" 
                                             data-entity-id="<?php echo $player['player_id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #007bff; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($displayName); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des PNJ -->
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <?php 
                                        $tokenKey = 'npc_' . $npc['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $imageUrl = !empty($npc['character_profile_photo']) ? $npc['character_profile_photo'] : (!empty($npc['profile_photo']) ? $npc['profile_photo'] : 'images/default_npc.png');
                                        ?>
                                        <div class="token" 
                                             data-token-type="npc" 
                                             data-entity-id="<?php echo $npc['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #28a745; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($npc['name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pions des monstres -->
                                    <?php foreach ($placeMonsters as $monster): ?>
                                        <?php 
                                        $tokenKey = 'monster_' . $monster['id'];
                                        $position = $tokenPositions[$tokenKey] ?? ['x' => 0, 'y' => 0, 'is_on_map' => false];
                                        $imageUrl = 'images/monstres/' . $monster['monster_id'] . '.jpg';
                                        ?>
                                        <div class="token" 
                                             data-token-type="monster" 
                                             data-entity-id="<?php echo $monster['id']; ?>"
                                             data-position-x="<?php echo $position['x']; ?>"
                                             data-position-y="<?php echo $position['y']; ?>"
                                             data-is-on-map="<?php echo $position['is_on_map'] ? 'true' : 'false'; ?>"
                                             style="width: 30px; height: 30px; margin: 2px; display: inline-block; cursor: move; border: 2px solid #dc3545; border-radius: 50%; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;"
                                             title="<?php echo htmlspecialchars($monster['name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="<?php echo htmlspecialchars($place['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir en plein écran
                                    </a>
                                </div>
                                <?php if ($isOwnerDM): ?>
                                    <div>
                                        <button id="resetTokensBtn" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-undo me-1"></i>Réinitialiser les pions
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-map fa-3x mb-3"></i>
                            <p>Aucun plan disponible pour cette lieu.</p>
                            <?php if ($isOwnerDM): ?>
                                <p class="small">Cliquez sur "Modifier le plan" pour ajouter un plan.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($place['notes'])): ?>
                <div class="card mt-4">
                    <div class="card-header">Notes du MJ</div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($place['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Joueurs présents</div>
                <div class="card-body">
                    <?php if (empty($placePlayers)): ?>
                        <p class="text-muted">Aucun joueur présent dans cette lieu.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($placePlayers as $player): ?>
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
                                            <?php if ($player['player_id'] == $_SESSION['user_id']): ?>
                                                <!-- Bouton spécial pour le joueur connecté -->
                                                <a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>&dm_campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-sm btn-primary" title="Ma feuille de personnage" target="_blank">
                                                    <i class="fas fa-user-circle me-1"></i>Ma fiche
                                                </a>
                                            <?php else: ?>
                                                <!-- Bouton pour les autres joueurs -->
                                                <a href="view_character.php?id=<?php echo (int)$player['character_id']; ?>&dm_campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
                                                    <i class="fas fa-file-alt me-1"></i>Fiche
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isOwnerDM): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($player['username']); ?> de cette lieu ?');">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="player_id" value="<?php echo (int)$player['player_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer du lieu">
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
                    
                    <?php if (empty($placeNpcs)): ?>
                        <p class="text-muted">Aucun PNJ dans cette lieu.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($placeNpcs as $npc): ?>
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
                                                <a href="view_character.php?id=<?php echo (int)$npc['npc_character_id']; ?>&dm_campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche du personnage" target="_blank">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($isOwnerDM): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($npc['is_identified'] ? 'Désidentifier' : 'Identifier'); ?> <?php echo htmlspecialchars($npc['name']); ?> pour les joueurs ?');">
                                                    <input type="hidden" name="action" value="toggle_npc_identification">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$npc['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $npc['is_identified'] ? 'btn-outline-info' : 'btn-outline-secondary'; ?>" title="<?php echo $npc['is_identified'] ? 'Désidentifier pour les joueurs' : 'Identifier pour les joueurs'; ?>">
                                                        <i class="fas <?php echo $npc['is_identified'] ? 'fa-user-check' : 'fa-user-question'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($npc['is_visible'] ? 'Masquer' : 'Afficher'); ?> <?php echo htmlspecialchars($npc['name']); ?> pour les joueurs ?');">
                                                    <input type="hidden" name="action" value="toggle_npc_visibility">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$npc['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $npc['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $npc['is_visible'] ? 'Masquer pour les joueurs' : 'Afficher pour les joueurs'; ?>">
                                                        <i class="fas <?php echo $npc['is_visible'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($npc['name']); ?> de cette lieu ?');">
                                                    <input type="hidden" name="action" value="remove_npc">
                                                    <input type="hidden" name="npc_name" value="<?php echo htmlspecialchars($npc['name']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer du lieu">
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
                    <?php if (empty($placeMonsters)): ?>
                        <p class="text-muted">Aucun monstre dans cette lieu.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($placeMonsters as $monster): ?>
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
                                                    PV <?php 
                                                        $current_hp = $monster['current_hit_points'] ?? $monster['hit_points'];
                                                        $max_hp = $monster['hit_points'];
                                                        $hp_percentage = ($current_hp / $max_hp) * 100;
                                                        $hp_color = $hp_percentage > 50 ? 'text-success' : ($hp_percentage > 25 ? 'text-warning' : 'text-danger');
                                                        echo "<span class='{$hp_color}'>{$current_hp}</span>/{$max_hp}";
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="view_monster_sheet.php?id=<?php echo (int)$monster['id']; ?>&campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-sm btn-outline-danger" title="Voir la feuille du monstre" target="_blank">
                                                <i class="fas fa-dragon"></i>
                                            </a>
                                            <a href="bestiary.php?search=<?php echo urlencode($monster['name']); ?>" class="btn btn-sm btn-outline-primary" title="Voir dans le bestiaire" target="_blank">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <?php if ($isOwnerDM): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($monster['is_visible'] ? 'Masquer' : 'Afficher'); ?> <?php echo htmlspecialchars($monster['name']); ?> pour les joueurs ?');">
                                                    <input type="hidden" name="action" value="toggle_monster_visibility">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$monster['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $monster['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $monster['is_visible'] ? 'Masquer pour les joueurs' : 'Afficher pour les joueurs'; ?>">
                                                        <i class="fas <?php echo $monster['is_visible'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Retirer <?php echo htmlspecialchars($monster['name']); ?> de cette lieu ?');">
                                                    <input type="hidden" name="action" value="remove_monster">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$monster['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirer du lieu">
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="poisonSearchModalLabel">
                    <i class="fas fa-skull-crossbones me-2"></i>Recherche de poisons
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="poisonSearch" class="form-label">Rechercher un poison :</label>
                    <input type="text" class="form-control" id="poisonSearch" placeholder="Tapez le nom du poison...">
                </div>
                <div id="poisonResults" class="row">
                    <div class="text-muted text-center p-3">Tapez au moins 2 caractères pour rechercher...</div>
                </div>
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
                    <strong>Astuce :</strong> Cliquez sur le bouton "Attribuer" à côté d'un objet pour l'assigner à un PNJ ou un personnage joueur de cette lieu.
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
                            <?php if (!empty($placePlayers)): ?>
                                <optgroup label="Personnages joueurs">
                                    <?php foreach ($placePlayers as $player): ?>
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
                            <?php if (!empty($placeNpcs)): ?>
                                <optgroup label="PNJ">
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <option value="npc_<?php echo (int)$npc['id']; ?>">
                                            <?php echo htmlspecialchars($npc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <!-- Monstres -->
                            <?php if (!empty($placeMonsters)): ?>
                                <optgroup label="Monstres">
                                    <?php foreach ($placeMonsters as $monster): ?>
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

<!-- Modal pour attribuer un poison -->
<div class="modal fade" id="assignPoisonModal" tabindex="-1" aria-labelledby="assignPoisonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignPoisonModalLabel">
                    <i class="fas fa-skull-crossbones me-2"></i>Attribuer un poison
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignPoisonForm">
                    <input type="hidden" name="action" value="assign_poison">
                    <input type="hidden" name="poison_id" id="selectedPoisonId">
                    <input type="hidden" name="poison_name" id="selectedPoisonName">
                    
                    <div class="mb-3">
                        <label for="assignPoisonTarget" class="form-label">Attribuer à :</label>
                        <select class="form-select" name="assign_target" id="assignPoisonTarget" required>
                            <option value="">Sélectionner un destinataire...</option>
                            
                            <!-- Personnages joueurs -->
                            <?php if (!empty($placePlayers)): ?>
                                <optgroup label="Personnages Joueurs">
                                    <?php foreach ($placePlayers as $player): ?>
                                        <?php if ($player['character_name']): ?>
                                            <option value="player_<?php echo (int)$player['player_id']; ?>">
                                                <?php echo htmlspecialchars($player['character_name']); ?> (<?php echo htmlspecialchars($player['username']); ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <!-- PNJ -->
                            <?php if (!empty($placeNpcs)): ?>
                                <optgroup label="PNJ">
                                    <?php foreach ($placeNpcs as $npc): ?>
                                        <option value="npc_<?php echo (int)$npc['id']; ?>">
                                            <?php echo htmlspecialchars($npc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            
                            <!-- Monstres -->
                            <?php if (!empty($placeMonsters)): ?>
                                <optgroup label="Monstres">
                                    <?php foreach ($placeMonsters as $monster): ?>
                                        <option value="monster_<?php echo (int)$monster['id']; ?>">
                                            <?php echo htmlspecialchars($monster['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignPoisonNotes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" name="assign_notes" id="assignPoisonNotes" rows="3" placeholder="Comment le poison a-t-il été obtenu ?..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="assignPoisonForm" class="btn btn-danger">
                    <i class="fas fa-skull-crossbones me-1"></i>Attribuer le poison
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
                const poisonElement = document.createElement('div');
                poisonElement.className = 'col-md-6 col-lg-4 mb-3';
                poisonElement.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title">${poison.nom}</h6>
                            <p class="card-text small text-muted">${poison.type || 'Type non spécifié'}</p>
                            <p class="card-text small">${poison.description ? poison.description.substring(0, 100) + '...' : 'Aucune description'}</p>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-sm btn-danger w-100" data-poison-id="${poison.csv_id}" data-poison-name="${poison.nom}">
                                <i class="fas fa-skull-crossbones me-1"></i>Attribuer ce poison
                            </button>
                        </div>
                    </div>
                `;
                
                // Ajouter l'événement de clic
                poisonElement.querySelector('button').addEventListener('click', function() {
                    const poisonId = this.getAttribute('data-poison-id');
                    const poisonName = this.getAttribute('data-poison-name');
                    
                    // Remplir les champs cachés
                    document.getElementById('selectedPoisonId').value = poisonId;
                    document.getElementById('selectedPoisonName').value = poisonName;
                    
                    // Fermer la modale de recherche et ouvrir la modale d'attribution
                    const searchModal = bootstrap.Modal.getInstance(document.getElementById('poisonSearchModal'));
                    searchModal.hide();
                    
                    const assignModal = new bootstrap.Modal(document.getElementById('assignPoisonModal'));
                    assignModal.show();
                });
                
                poisonResults.appendChild(poisonElement);
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

        // Système de glisser-déposer pour les pions
        <?php if ($isOwnerDM && !empty($place['map_url'])): ?>
        initializeTokenSystem();
        <?php endif; ?>

    });
    </script>

    <?php if ($isOwnerDM && !empty($place['map_url'])): ?>
    <script>
    function initializeTokenSystem() {
        const mapImage = document.getElementById('mapImage');
        const tokens = document.querySelectorAll('.token');
        const resetBtn = document.getElementById('resetTokensBtn');
        
        if (!mapImage || tokens.length === 0) return;

        let draggedToken = null;
        let isDragging = false;

        // Initialiser les positions des pions
        console.log('Initialisation du système de pions...');
        console.log('Nombre de pions trouvés:', tokens.length);
        
        tokens.forEach(token => {
            const isOnMap = token.dataset.isOnMap === 'true';
            console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId}: isOnMap=${isOnMap}`);
            
            if (isOnMap) {
                const x = parseInt(token.dataset.positionX);
                const y = parseInt(token.dataset.positionY);
                console.log(`Initialisation pion: ${token.dataset.tokenType}_${token.dataset.entityId} à ${x}%, ${y}%`);
                positionTokenOnMap(token, x, y);
            } else {
                console.log(`Pion ${token.dataset.tokenType}_${token.dataset.entityId} reste dans la sidebar`);
            }
        });

        // Gestion du glisser-déposer
        tokens.forEach(token => {
            token.draggable = true;
            
            token.addEventListener('dragstart', function(e) {
                draggedToken = this;
                isDragging = true;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);
                this.style.opacity = '0.5';
            });

            token.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
                draggedToken = null;
                isDragging = false;
            });
        });

        // Gestion du dépôt sur le plan
        mapImage.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        mapImage.addEventListener('drop', function(e) {
            e.preventDefault();
            
            if (!draggedToken) return;

            const rect = mapImage.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Convertir en pourcentages pour la responsivité
            const xPercent = Math.round((x / rect.width) * 100);
            const yPercent = Math.round((y / rect.height) * 100);
            
            // S'assurer que les pourcentages sont dans les limites
            const clampedX = Math.max(0, Math.min(100, xPercent));
            const clampedY = Math.max(0, Math.min(100, yPercent));
            
            positionTokenOnMap(draggedToken, clampedX, clampedY);
            saveTokenPosition(draggedToken, clampedX, clampedY, true);
        });

        // Gestion du dépôt sur la sidebar (retour au côté)
        const sidebar = document.getElementById('tokenSidebar');
        sidebar.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        sidebar.addEventListener('drop', function(e) {
            e.preventDefault();
            
            if (!draggedToken) return;

            resetTokenToSidebar(draggedToken);
            saveTokenPosition(draggedToken, 0, 0, false);
        });

        // Bouton de réinitialisation
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir remettre tous les pions sur le côté du plan ?')) {
                    resetAllTokens();
                }
            });
        }

        function positionTokenOnMap(token, x, y) {
            console.log(`Positionnement du pion ${token.dataset.tokenType}_${token.dataset.entityId} à ${x}%, ${y}%`);
            
            // Retirer le pion de son conteneur actuel
            token.remove();
            
            // Ajouter le pion au conteneur du plan
            const mapContainer = document.getElementById('mapContainer');
            if (!mapContainer) {
                console.error('Conteneur du plan non trouvé');
                return;
            }
            mapContainer.appendChild(token);
            
            // Positionner le pion
            token.style.position = 'absolute';
            token.style.left = x + '%';
            token.style.top = y + '%';
            token.style.transform = 'translate(-50%, -50%)';
            token.style.zIndex = '1000';
            token.style.margin = '0';
            token.style.pointerEvents = 'auto';
            token.dataset.isOnMap = 'true';
            token.dataset.positionX = x;
            token.dataset.positionY = y;
            
            console.log(`Pion positionné avec succès à ${x}%, ${y}%`);
        }

        function resetTokenToSidebar(token) {
            // Retirer le pion du conteneur du plan
            token.remove();
            
            // Remettre le pion dans la sidebar
            const sidebar = document.getElementById('tokenSidebar');
            sidebar.appendChild(token);
            
            // Réinitialiser les styles
            token.style.position = 'static';
            token.style.left = 'auto';
            token.style.top = 'auto';
            token.style.transform = 'none';
            token.style.zIndex = 'auto';
            token.style.margin = '2px';
            token.style.pointerEvents = 'auto';
            token.dataset.isOnMap = 'false';
            token.dataset.positionX = '0';
            token.dataset.positionY = '0';
            
            console.log('Pion remis dans la sidebar');
        }

        function saveTokenPosition(token, x, y, isOnMap) {
            const data = {
                place_id: <?php echo $place_id; ?>,
                token_type: token.dataset.tokenType,
                entity_id: parseInt(token.dataset.entityId),
                position_x: x,
                position_y: y,
                is_on_map: isOnMap
            };

            fetch('update_token_position.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    console.error('Erreur lors de la sauvegarde:', result.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }

        function resetAllTokens() {
            const data = {
                place_id: <?php echo $place_id; ?>
            };

            fetch('reset_token_positions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    tokens.forEach(token => {
                        resetTokenToSidebar(token);
                    });
                } else {
                    console.error('Erreur lors de la réinitialisation:', result.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
    }
    </script>
    <?php endif; ?>


</body>
</html>
<?php endif; ?>
