<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$page_title = "Scène de Jeu";
$current_page = "view_place";


requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$place_id = (int)$_GET['id'];
$isModal = isset($_GET['modal']);

// Charger le lieu et sa campagne avec hiérarchie géographique
$lieu = Lieu::findById($place_id);
if ($lieu) {
    // Récupérer les campagnes associées à ce lieu
    $campaigns = $lieu->getCampaigns();
    if (!empty($campaigns)) {
        // Pour l'instant, on prend la première campagne (on pourrait améliorer cela plus tard)
        $campaign = $campaigns[0];
        $place = $lieu->toArray();
        $place['campaign_id'] = $campaign['id'];
        $place['campaign_title'] = $campaign['title'];
        $place['dm_id'] = $campaign['dm_id'];
        
        // Récupérer le nom d'utilisateur du DM
        $dm_user = User::findById(getPDO(), $campaign['dm_id']);
        $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
    } else {
        // Si aucun lieu n'est associé à une campagne, rediriger
        header('Location: index.php');
        exit();
    }
}

if (!$place) {
    header('Location: index.php');
    exit();
}

// Fonction utilitaire pour vérifier si campaign_id est défini
function hasCampaignId($place) {
    return isset($place['campaign_id']) && !empty($place['campaign_id']);
}

$dm_id = (int)$place['dm_id'];
$isOwnerDM = (isDMOrAdmin() && $_SESSION['user_id'] === $dm_id);

// DEBUG: Logs pour déboguer les permissions
error_log("DEBUG view_place.php - User ID: " . ($_SESSION['user_id'] ?? 'NOT_SET'));
error_log("DEBUG view_place.php - DM ID: " . $dm_id);
error_log("DEBUG view_place.php - isDM(): " . (isDM() ? 'true' : 'false'));
error_log("DEBUG view_place.php - isOwnerDM: " . ($isOwnerDM ? 'true' : 'false'));

// Autoriser les admins, les DM propriétaires et les membres de la campagne à voir le lieu
$canView = isAdmin() || $isOwnerDM;
if (!$canView && isset($place['campaign_id'])) {
    $campaign = Campaign::findById($place['campaign_id']);
    $canView = $campaign ? $campaign->isMember($_SESSION['user_id']) : false;
}

// Seuls les admins et les DM propriétaires peuvent éditer le lieu
$canEdit = isAdmin() || $isOwnerDM;

if (!$canView) {
    header('Location: index.php');
    exit();
}

// Récupérer les joueurs présents dans cette lieu
$placePlayers = $lieu ? $lieu->getAllPlayers() : [];

// Récupérer les PNJ de cette lieu
$placeNpcs = $lieu ? $lieu->getVisibleNpcs() : [];

// Récupérer les monstres de cette lieu
$placeMonsters = $lieu ? $lieu->getVisibleMonsters() : [];

// Récupérer les positions des pions
$tokenPositions = $lieu ? $lieu->getTokenPositions() : [];

// Récupérer les objets du lieu (seulement ceux non attribués pour l'affichage normal)
$placeObjects = $lieu ? $lieu->getVisibleObjects() : [];

// Récupérer les positions des objets depuis items (seulement les non attribués)
foreach ($placeObjects as $object) {
    $tokenKey = 'object_' . $object['id'];
    $tokenPositions[$tokenKey] = [
        'x' => (int)$object['position_x'],
        'y' => (int)$object['position_y'],
        'is_on_map' => (bool)$object['is_on_map']
    ];
}

// Récupérer TOUS les objets du lieu (y compris ceux attribués) pour le MJ
$allPlaceObjects = [];
if ($isOwnerDM && $lieu) {
    $allPlaceObjects = $lieu->getAllObjects();
}

// Récupérer les membres de la campagne pour le formulaire d'ajout de joueurs
$campaignMembers = [];
if (isset($place['campaign_id'])) {
    $campaign = Campaign::findById($place['campaign_id']);
    if ($campaign) {
        $campaignMembers = $campaign->getMembers();
    }
}

// Traitements POST pour ajouter des PNJ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerDM) {

    
    // Ajouter un personnage du MJ comme PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'add_dm_character_npc') {
        $character_id = (int)($_POST['dm_character_id'] ?? 0);
        if ($character_id > 0) {
            $character = Character::findById($character_id);
            if ($character && $character->user_id == $dm_id) {
                if (hasCampaignId($place)) {
                    $result = $lieu->addDmCharacterAsNpc($character_id, $place['campaign_id']);
                    if ($result['success']) {
                        $success_message = $result['message'];
                        // Recharger les PNJ
                        $placeNpcs = $lieu->getAllNpcsDetailed();
                    } else {
                        $error_message = $result['message'];
                    }
                } else {
                    $error_message = "Campagne non définie.";
                }
            } else {
                $error_message = "Personnage invalide (doit appartenir au MJ).";
            }
        } else {
            $error_message = "Veuillez sélectionner un personnage.";
        }
    }
    
    // Ajouter un joueur au lieu
    if (isset($_POST['action']) && $_POST['action'] === 'add_player' && isset($_POST['player_id'])) {
        $player_id = (int)$_POST['player_id'];
        $character_id = !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null;
        
        // Vérifier que le joueur est membre de la campagne
        if (hasCampaignId($place)) {
            $campaign = Campaign::findById($place['campaign_id']);
            if ($campaign && $campaign->isMember($player_id)) {
                $result = $lieu->addPlayer($player_id, $character_id, $place['campaign_id']);
                if ($result['success']) {
                    $success_message = $result['message'];
                    // Recharger les joueurs
                    $placePlayers = $lieu->getAllPlayersDetailed();
                } else {
                    $error_message = $result['message'];
                }
            } else {
                $error_message = "Ce joueur n'est pas membre de la campagne.";
            }
        } else {
            $error_message = "Campagne non définie.";
        }
    }
    
    // Exclure un joueur du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_player' && isset($_POST['player_id'])) {
        $player_id = (int)$_POST['player_id'];
        $result = $lieu->removePlayer($player_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les joueurs
            $placePlayers = $lieu->getAllPlayersDetailed();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Exclure un PNJ du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_npc' && isset($_POST['npc_name'])) {
        $npc_name = $_POST['npc_name'];
        $result = $lieu->removeNpc($npc_name);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les PNJ
            $placeNpcs = $lieu->getAllNpcsDetailed();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Ajouter un monstre du bestiaire
    if (isset($_POST['action']) && $_POST['action'] === 'add_monster') {
        $monster_id = (int)($_POST['monster_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($monster_id > 0 && $quantity > 0) {
            $result = $lieu->addMonster($monster_id, $quantity);
            if ($result['success']) {
                $success_message = $result['message'];
                // Recharger les monstres
                $placeMonsters = $lieu->getVisibleMonsters();
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Veuillez sélectionner un monstre et spécifier une quantité valide.";
        }
    }
    
    // Retirer un monstre du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'remove_monster' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $result = $lieu->removeMonster($npc_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les monstres
            $placeMonsters = $lieu->getVisibleMonsters();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Basculer la visibilité d'un PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_npc_visibility' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $result = $lieu->toggleNpcVisibility($npc_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les PNJ
            $placeNpcs = $lieu->getAllNpcsDetailed();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Basculer la visibilité d'un monstre
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_monster_visibility' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $result = $lieu->toggleMonsterVisibility($npc_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les monstres
            $placeMonsters = $lieu->getVisibleMonsters();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Basculer l'identification d'un PNJ
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_npc_identification' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $result = $lieu->toggleNpcIdentification($npc_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les PNJ
            $placeNpcs = $lieu->getAllNpcsDetailed();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Basculer l'identification d'un monstre
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_monster_identification' && isset($_POST['npc_id'])) {
        $npc_id = (int)$_POST['npc_id'];
        $result = $lieu->toggleMonsterIdentification($npc_id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les monstres
            $placeMonsters = $lieu->getVisibleMonsters();
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Mettre à jour le nom du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'update_title') {
        $new_title = trim($_POST['scene_title'] ?? '');
        
        if ($new_title === '') {
            $error_message = "Le nom du lieu ne peut pas être vide.";
        } else {
            // Vérifier que la lieu existe et appartient à la bonne campagne
            $check_stmt = $pdo->prepare("
                SELECT p.id, p.title 
                FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE p.id = ? AND pc.campaign_id = ?
            ");
            $check_stmt->execute([$place_id, $place['campaign_id']]);
            $current_scene = $check_stmt->fetch();
            
            if (!$current_scene) {
                $error_message = "Lieu introuvable ou accès refusé.";
            } else {
                $stmt = $pdo->prepare("UPDATE places SET title = ? WHERE id = ?");
                $result = $stmt->execute([$new_title, $place_id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $success_message = "Nom du lieu mis à jour avec succès.";
                    
                    // Recharger les données du lieu
                    $lieu = Lieu::findById($place_id);
                    if ($lieu) {
                        $place = $lieu->toArray();
                        $campaigns = $lieu->getCampaigns();
                        if (!empty($campaigns)) {
                            $campaign = $campaigns[0];
                            $place['campaign_id'] = $campaign['id'];
                            $place['campaign_title'] = $campaign['title'];
                            $place['dm_id'] = $campaign['dm_id'];
                            
                            $dm_user = User::findById(getPDO(), $campaign['dm_id']);
                            $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
                        }
                    }
                    
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
            $result = $lieu->updateMapUrl($newMapUrl, $notes);
            if ($result['success']) {
                $success_message = $result['message'];
                
                // Recharger les données du lieu
                $place = $lieu->toArray();
                $campaigns = $lieu->getCampaigns();
                if (!empty($campaigns)) {
                    $campaign = $campaigns[0];
                    $place['campaign_id'] = $campaign['id'];
                    $place['campaign_title'] = $campaign['title'];
                    $place['dm_id'] = $campaign['dm_id'];
                    
                    $dm_user = User::findById(getPDO(), $campaign['dm_id']);
                    $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
                }
            } else {
                $error_message = $result['message'];
            }
        }
    }
    
    // Éditer le lieu (titre, description, notes)
    if (isset($_POST['action']) && $_POST['action'] === 'edit_scene') {
        if (!$canEdit) {
            $error_message = "Vous n'avez pas les droits pour éditer ce lieu.";
        } else {
            $title = trim($_POST['scene_title'] ?? '');
            $notes = trim($_POST['scene_notes'] ?? '');
            
            if ($title === '') {
                $error_message = "Le titre du lieu est obligatoire.";
            } else {
                $country_id = isset($_POST['country_id']) && $_POST['country_id'] ? (int)$_POST['country_id'] : null;
                $region_id = isset($_POST['region_id']) && $_POST['region_id'] ? (int)$_POST['region_id'] : null;
                
                $result = $lieu->updatePlace($title, $notes, $country_id, $region_id);
                if ($result['success']) {
                    $success_message = $result['message'];
                    
                    // Recharger les données du lieu
                    $place = $lieu->toArray();
                    $campaigns = $lieu->getCampaigns();
                    if (!empty($campaigns)) {
                        $campaign = $campaigns[0];
                        $place['campaign_id'] = $campaign['id'];
                        $place['campaign_title'] = $campaign['title'];
                        $place['dm_id'] = $campaign['dm_id'];
                        
                        $dm_user = User::findById(getPDO(), $campaign['dm_id']);
                        $place['dm_username'] = $dm_user ? $dm_user->getUsername() : 'Inconnu';
                    }
                } else {
                    $error_message = $result['message'];
                }
            }
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
            $item_info = Lieu::getMagicalItemInfoByCsvId($item_id);
            
            if (!$item_info) {
                $error_message = "Objet magique introuvable.";
            } else {
                $target_name = '';
                $insert_success = false;
                
                switch ($target_type) {
                    case 'player':
                        // Récupérer les informations du personnage joueur
                        $target = $lieu->getPlayerInfo($target_id);
                        
                        if ($target && $target['character_id']) {
                            // Ajouter l'objet à l'équipement du personnage
                            $itemData = [
                                'place_id' => null,
                                'display_name' => $item_info['nom'],
                                'object_type' => $item_info['type'],
                                'type_precis' => $item_info['nom'],
                                'description' => $item_info['description'],
                                'is_identified' => true,
                                'is_visible' => false,
                                'is_equipped' => false,
                                'position_x' => 0,
                                'position_y' => 0,
                                'is_on_map' => false,
                                'owner_type' => 'player',
                                'owner_id' => $target['character_id'],
                                'poison_id' => null,
                                'weapon_id' => null,
                                'armor_id' => null,
                                'gold_coins' => 0,
                                'silver_coins' => 0,
                                'copper_coins' => 0,
                                'letter_content' => null,
                                'is_sealed' => false
                            ];
                            
                            $item = Item::create($itemData);
                            if ($item) {
                                $insert_success = true;
                                $target_name = $target['character_name'] ?: $target['username'];
                            }
                        } else {
                            $error_message = "Personnage joueur invalide ou sans personnage créé.";
                        }
                        break;
                        
                    case 'npc':
                        // Récupérer les informations du PNJ
                        $target = $lieu->getNpcInfo($target_id);
                        
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
                        $target = $lieu->getMonsterInfo($target_id);
                        
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
            $poison_info = Lieu::getPoisonInfoByCsvId($poison_id);
            
            if (!$poison_info) {
                $error_message = "Poison introuvable.";
            } else {
                $target_name = '';
                $insert_success = false;
                
                switch ($target_type) {
                    case 'player':
                        // Récupérer les informations du personnage joueur
                        $target = $lieu->getPlayerInfo($target_id);
                        
                        if ($target && $target['character_id']) {
                            // Ajouter le poison à l'équipement du personnage
                            $itemData = [
                                'place_id' => null,
                                'display_name' => $poison_info['nom'],
                                'object_type' => 'poison',
                                'type_precis' => $poison_info['nom'],
                                'description' => $poison_info['description'],
                                'is_identified' => true,
                                'is_visible' => false,
                                'is_equipped' => false,
                                'position_x' => 0,
                                'position_y' => 0,
                                'is_on_map' => false,
                                'owner_type' => 'player',
                                'owner_id' => $target['character_id'],
                                'poison_id' => $poison_id,
                                'weapon_id' => null,
                                'armor_id' => null,
                                'gold_coins' => 0,
                                'silver_coins' => 0,
                                'copper_coins' => 0,
                                'letter_content' => null,
                                'is_sealed' => false
                            ];
                            
                            $item = Item::create($itemData);
                            if ($item) {
                                $insert_success = true;
                                $target_name = $target['character_name'] ?: $target['username'];
                            }
                        } else {
                            $error_message = "Personnage joueur invalide ou sans personnage créé.";
                        }
                        break;
                        
                    case 'npc':
                        // Récupérer les informations du PNJ
                        $target = $lieu->getNpcInfo($target_id);
                        
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
                        $target = $lieu->getMonsterInfo($target_id);
                        
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
    
    // Gestion des objets du lieu
    if (isset($_POST['action']) && $_POST['action'] === 'add_object') {
        $object_name = sanitizeInput($_POST['object_name'] ?? '');
        $object_description = sanitizeInput($_POST['object_description'] ?? '');
        $object_type = $_POST['object_type'] ?? 'other';
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $selected_item = $_POST['selected_item'] ?? '';
        $letter_content = sanitizeInput($_POST['letter_content'] ?? '');
        $is_sealed = isset($_POST['is_sealed']) ? 1 : 0;
        $gold_coins = (int)($_POST['gold_coins'] ?? 0);
        $silver_coins = (int)($_POST['silver_coins'] ?? 0);
        $copper_coins = (int)($_POST['copper_coins'] ?? 0);
        $is_identified = isset($_POST['is_identified']) ? 1 : 0;
        
        // Variables pour les informations de l'objet sélectionné
        $item_id = null;
        $item_name = null;
        $item_description = null;
        
        if (empty($object_name)) {
            $error_message = "Le nom de l'objet est requis.";
        } else {
            try {
                // Gestion spéciale pour les pièces
                if ($object_type === 'coins') {
                    $coins_total = $gold_coins + $silver_coins + $copper_coins;
                    if ($coins_total > 0) {
                        $coins_parts = [];
                        if ($gold_coins > 0) $coins_parts[] = $gold_coins . ' po';
                        if ($silver_coins > 0) $coins_parts[] = $silver_coins . ' pa';
                        if ($copper_coins > 0) $coins_parts[] = $copper_coins . ' pc';
                        
                        if (empty($object_name)) {
                            $object_name = 'Bourse';
                        }
                        if (empty($object_description)) {
                            $object_description = 'Une bourse contenant ' . implode(', ', $coins_parts) . '.';
                        }
                    } else {
                        $error_message = "Veuillez spécifier au moins une quantité de pièces.";
                    }
                }
                
                // Récupérer les informations de l'objet sélectionné si applicable
                if (!empty($selected_item) && in_array($object_type, ['poison', 'magical_item', 'weapon', 'armor'])) {
                    switch ($object_type) {
                        case 'poison':
                            $item = Lieu::getPoisonInfo($selected_item);
                            if ($item) {
                                $item_id = $item['id'];
                                $item_name = $item['nom'];
                                $item_description = $item['description'];
                                // Utiliser le nom du poison si pas de nom personnalisé
                                if (empty($object_name)) {
                                    $object_name = $item_name;
                                }
                            }
                            break;
                            
                        case 'magical_item':
                            $item = Lieu::getMagicalItemInfo($selected_item);
                            if ($item) {
                                $item_id = $item['id'];
                                $item_name = $item['nom'];
                                $item_description = $item['description'];
                                if (empty($object_name)) {
                                    $object_name = $item_name;
                                }
                            }
                            break;
                            
                        case 'weapon':
                            $item = Lieu::getWeaponInfo($selected_item);
                            if ($item) {
                                $item_id = $item['id'];
                                $item_name = $item['nom'];
                                $item_description = $item['description'];
                                if (empty($object_name)) {
                                    $object_name = $item_name;
                                }
                            }
                            break;
                            
                        case 'armor':
                            $item = Lieu::getArmorInfo($selected_item);
                            if ($item) {
                                $item_id = $item['id'];
                                $item_name = $item['nom'];
                                $item_description = $item['description'];
                                if (empty($object_name)) {
                                    $object_name = $item_name;
                                }
                            }
                            break;
                    }
                }
                
                // Insérer l'objet dans la base de données avec la nouvelle structure
                $itemData = [
                    'place_id' => $place_id,
                    'display_name' => $object_name,
                    'object_type' => $object_type,
                    'type_precis' => $item_name,
                    'description' => $object_description,
                    'is_visible' => $is_visible,
                    'is_identified' => $is_identified,
                    'is_equipped' => false,
                    'position_x' => 0,
                    'position_y' => 0,
                    'is_on_map' => false,
                    'owner_type' => 'place',
                    'owner_id' => null,
                    'poison_id' => ($object_type === 'poison') ? $item_id : null,
                    'weapon_id' => ($object_type === 'weapon') ? $item_id : null,
                    'armor_id' => ($object_type === 'armor') ? $item_id : null,
                    'gold_coins' => $gold_coins,
                    'silver_coins' => $silver_coins,
                    'copper_coins' => $copper_coins,
                    'letter_content' => $letter_content,
                    'is_sealed' => $is_sealed
                ];
                
                $item = Item::create($itemData);
                
                $success_message = "Objet '$object_name' ajouté au lieu.";
                
                // Recharger les objets
                $stmt = $pdo->prepare("
                    SELECT id, name, description, object_type, is_visible, position_x, position_y, is_on_map, 
                           item_id, item_name, item_description, letter_content, is_sealed, gold_coins, silver_coins, copper_coins
                    FROM items 
                    WHERE place_id = ? 
                    ORDER BY name ASC
                ");
                $stmt->execute([$place_id]);
                $placeObjects = $stmt->fetchAll();
            } catch (PDOException $e) {
                $error_message = "Erreur lors de l'ajout de l'objet: " . $e->getMessage();
            }
        }
    }
    
    // Supprimer un objet
    if (isset($_POST['action']) && $_POST['action'] === 'remove_object') {
        $object_id = (int)($_POST['object_id'] ?? 0);
        
        if ($object_id > 0) {
            try {
                $item = Item::findById($object_id);
                if ($item && $item->getPlaceId() == $place_id) {
                    if ($item->delete()) {
                        $success_message = "Objet supprimé du lieu.";
                        
                        // Recharger les objets
                        $placeObjects = $lieu->reloadAllObjects();
                    } else {
                        $error_message = "Erreur lors de la suppression de l'objet.";
                    }
                } else {
                    $error_message = "Objet non trouvé.";
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors de la suppression: " . $e->getMessage();
            }
        }
    }
    
    // Modifier la visibilité d'un objet
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_object_visibility') {
        $object_id = (int)($_POST['object_id'] ?? 0);
        
        if ($object_id > 0) {
            try {
                $item = Item::findById($object_id);
                if ($item && $item->getPlaceId() == $place_id) {
                    $newVisibility = !$item->getIsVisible();
                    if ($item->setVisible($newVisibility)) {
                        $success_message = "Visibilité de l'objet modifiée.";
                        
                        // Recharger les objets
                        $placeObjects = $lieu->reloadAllObjects();
                    } else {
                        $error_message = "Erreur lors de la mise à jour de la visibilité.";
                    }
                } else {
                    $error_message = "Objet non trouvé.";
                }
            } catch (Exception $e) {
                $error_message = "Erreur lors de la modification: " . $e->getMessage();
            }
        }
    }
    
        // Modifier l'identification d'un objet
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_object_identification') {
            $object_id = (int)($_POST['object_id'] ?? 0);
            
            if ($object_id > 0) {
                try {
                    $item = Item::findById($object_id);
                    if ($item && $item->getPlaceId() == $place_id) {
                        $newIdentification = !$item->getIsIdentified();
                        if ($item->setIdentified($newIdentification)) {
                            $success_message = "Identification de l'objet modifiée.";
                            
                            // Recharger les objets
                            $placeObjects = $lieu->reloadAllObjects();
                        } else {
                            $error_message = "Erreur lors de la mise à jour de l'identification.";
                        }
                    } else {
                        $error_message = "Objet non trouvé.";
                    }
                } catch (Exception $e) {
                    $error_message = "Erreur lors de la modification: " . $e->getMessage();
                }
            }
        }
        
        // Attribuer un objet à un joueur ou PNJ
        if (isset($_POST['action']) && $_POST['action'] === 'assign_object') {
            $object_id = (int)($_POST['object_id'] ?? 0);
            $owner_type = $_POST['owner_type'] ?? 'none';
            $owner_id = (int)($_POST['owner_id'] ?? 0);
            
            if ($object_id > 0) {
                try {
                    // Valider le type de propriétaire
                    if (!in_array($owner_type, ['none', 'player', 'npc', 'monster'])) {
                        $error_message = "Type de propriétaire invalide.";
                    } else {
                        // Si owner_type est 'none', owner_id doit être NULL
                        if ($owner_type === 'none') {
                            $owner_id = null;
                        } elseif ($owner_id <= 0) {
                            $error_message = "ID du propriétaire invalide.";
                        } else {
                            // Vérifier que le propriétaire existe
                            if ($owner_type === 'player') {
                                if (!$lieu->isPlayerPresent($owner_id)) {
                                    $error_message = "Joueur non trouvé dans ce lieu.";
                                }
                            } elseif ($owner_type === 'npc') {
                                if (!$lieu->npcExists($owner_id)) {
                                    $error_message = "PNJ non trouvé dans ce lieu.";
                                }
                            } elseif ($owner_type === 'monster') {
                                if (!$lieu->monsterExists($owner_id)) {
                                    $error_message = "Monstre non trouvé dans ce lieu.";
                                }
                            }
                        }
                        
                        if (empty($error_message)) {
                            // Récupérer les informations de l'objet avant attribution
                            $object = $lieu->getObjectInfo($object_id);
                            
                            if ($object) {
                                // Ajouter l'objet à l'inventaire du propriétaire
                                if ($owner_type === 'player') {
                                    // Trouver le character_id du joueur
                                    $character_id = $lieu->getPlayerCharacterId($owner_id);
                                    $player_data = $character_id ? ['character_id' => $character_id] : null;
                                    
                                    if ($player_data && $player_data['character_id']) {
                                        // Ajouter à l'inventaire du personnage
                                        $stmt = $pdo->prepare("
                                            INSERT INTO character_equipment 
                                            (character_id, item_name, item_type, item_description, item_source, quantity, obtained_from) 
                                            VALUES (?, ?, ?, ?, ?, 1, ?)
                                        ");
                                        $stmt->execute([
                                            $player_data['character_id'],
                                            $object['display_name'],
                                            $object['object_type'],
                                            $object['description'],
                                            'Objet du lieu',
                                            'Attribution MJ - Lieu: ' . $place['title']
                                        ]);
                                    }
                                } elseif ($owner_type === 'npc') {
                                    // Ajouter à l'inventaire du PNJ
                                    $stmt = $pdo->prepare("
                                        INSERT INTO npc_equipment 
                                        (npc_id, scene_id, item_name, item_type, item_description, item_source, quantity, obtained_from) 
                                        VALUES (?, ?, ?, ?, ?, 1, ?)
                                    ");
                                    $stmt->execute([
                                        $owner_id,
                                        $place_id, // Utiliser place_id comme scene_id
                                        $object['display_name'],
                                        $object['object_type'],
                                        $object['description'],
                                        'Objet du lieu',
                                        'Attribution MJ - Lieu: ' . $place['title']
                                    ]);
                                } elseif ($owner_type === 'monster') {
                                    // Ajouter à l'inventaire du monstre
                                    $stmt = $pdo->prepare("
                                        INSERT INTO monster_equipment 
                                        (monster_id, scene_id, item_name, item_type, item_description, item_source, quantity, obtained_from) 
                                        VALUES (?, ?, ?, ?, ?, 1, ?)
                                    ");
                                    $stmt->execute([
                                        $owner_id,
                                        $place_id, // Utiliser place_id comme scene_id
                                        $object['display_name'],
                                        $object['object_type'],
                                        $object['description'],
                                        'Objet du lieu',
                                        'Attribution MJ - Lieu: ' . $place['title']
                                    ]);
                                }
                                
                                // Mettre à jour l'attribution dans items
                                $item = Item::findById($object_id);
                                if ($item && $item->getPlaceId() == $place_id) {
                                    $item->changeOwner($owner_type, $owner_id);
                                }
                                
                                if ($stmt->rowCount() > 0) {
                                    $success_message = "Objet attribué et ajouté à l'inventaire du propriétaire.";
                                    
                                    // Recharger les objets
                                    $placeObjects = $lieu->reloadVisibleObjects();
                                    
                                    // Recharger tous les objets pour le MJ
                                    if ($isOwnerDM) {
                                        $allPlaceObjects = $lieu->reloadAllObjects();
                                    }
                                } else {
                                    $error_message = "Erreur lors de l'attribution.";
                                }
                            } else {
                                $error_message = "Objet non trouvé.";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Erreur lors de l'attribution: " . $e->getMessage();
                }
            }
        }
}

// Récupérer la liste des personnages du MJ pour l'ajout en PNJ
$dmCharacters = [];
if ($isOwnerDM) {
    $dmCharacters = Character::findSimpleByUserId($dm_id);
}

// Récupérer les autres lieux de la campagne pour navigation
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.position 
    FROM places p
    INNER JOIN place_campaigns pc ON p.id = pc.place_id
    WHERE pc.campaign_id = ? 
    ORDER BY p.position ASC, p.created_at ASC
");
if (hasCampaignId($place)) {
    $stmt->execute([$place['campaign_id']]);
    $allScenes = $stmt->fetchAll();
} else {
    $allScenes = [];
}



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
    <link href="css/custom-theme.css" rel="stylesheet">
    
    <style>
    /* Styles personnalisés pour les dés */
    .dice-btn {
        transition: all 0.3s ease;
        min-width: 60px;
    }

    .dice-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .dice-btn.btn-primary, .dice-btn.btn-success {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    #dice-results {
        transition: all 0.3s ease;
    }

    #dice-results .badge {
        font-size: 1.1em;
        padding: 0.5em 0.75em;
        margin: 0.2em;
        animation: bounceIn 0.5s ease;
    }

    @keyframes bounceIn {
        0% { transform: scale(0.3); opacity: 0; }
        50% { transform: scale(1.05); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); opacity: 1; }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .dice-rolling {
        animation: spin 0.1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Amélioration de l'apparence des résultats */
    .alert {
        border-radius: 0.5rem;
        border: none;
        font-weight: 500;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }

        /* Styles pour les barres de points de vie */
        .progress {
            border-radius: 3px;
            background-color: rgba(0,0,0,0.1);
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        .progress-bar.bg-success {
            background-color: #28a745 !important;
        }

        .progress-bar.bg-warning {
            background-color: #ffc107 !important;
        }

        .progress-bar.bg-danger {
            background-color: #dc3545 !important;
        }

        /* Animation pour les barres de PV */
        .progress-bar {
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            0% {
                background-position-x: 1rem;
            }
        }
        
        /* Styles pour les objets et pions dorés */
        .object-token {
            position: absolute;
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border: 2px solid #FF8C00;
            border-radius: 4px;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #8B4513;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        
        .object-token:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
            z-index: 20;
        }
        
        .object-token.dragging {
            transform: scale(1.3);
            box-shadow: 0 6px 12px rgba(0,0,0,0.5);
            z-index: 30;
        }
        
        /* Styles pour les badges d'objets */
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        .badge.bg-info {
            background-color: #0dcaf0 !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .badge.bg-primary {
            background-color: #0d6efd !important;
        }
        
        .badge.bg-success {
            background-color: #198754 !important;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
        
        /* Animation pour les pions dorés */
        @keyframes goldenPulse {
            0% { 
                box-shadow: 0 2px 4px rgba(0,0,0,0.3), 0 0 0 0 rgba(255, 215, 0, 0.7);
            }
            70% { 
                box-shadow: 0 2px 4px rgba(0,0,0,0.3), 0 0 0 10px rgba(255, 215, 0, 0);
            }
            100% { 
                box-shadow: 0 2px 4px rgba(0,0,0,0.3), 0 0 0 0 rgba(255, 215, 0, 0);
            }
        }
        
        .object-token.visible {
            animation: goldenPulse 2s infinite;
        }
        
        /* Styles pour la liste des objets */
        .list-group-item {
            border-left: none;
            border-right: none;
        }
        
        .list-group-item:first-child {
            border-top: none;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .list-group-item:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        /* Styles pour les icônes d'objets */
        .fa-skull-crossbones {
            color: #dc3545;
        }
        
        .fa-flask {
            color: #0dcaf0;
        }
        
        .fa-coins {
            color: #ffc107;
        }
        
        .fa-envelope {
            color: #0d6efd;
        }
        
        .fa-shield-alt {
            color: #198754;
        }
        
        .fa-box {
            color: #6c757d;
        }
        
        .fa-sword {
            color: #dc3545;
        }
        
        .fa-magic {
            color: #0dcaf0;
        }
        
        .fa-lock {
            color: #000;
        }
        
        /* Styles pour les icônes des pions d'objets */
        .object-token .fa-question {
            color: #8B4513 !important;
            font-weight: bold;
        }
        
        .object-token .fa-flask {
            color: #dc3545 !important;
        }
        
        .object-token .fa-magic {
            color: #0dcaf0 !important;
        }
        
        .object-token .fa-sword {
            color: #dc3545 !important;
        }
        
        .object-token .fa-shield-alt {
            color: #198754 !important;
        }
        
        .object-token .fa-envelope {
            color: #0d6efd !important;
        }
        
        .object-token .fa-coins {
            color: #ffc107 !important;
        }
        
        .object-token .fa-box {
            color: #6c757d !important;
        }
        </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
<?php endif; ?>

<div class="container mt-4">
    <?php if (!empty($success_message)) echo displayMessage($success_message, 'success'); ?>
    <?php if (!empty($error_message)) echo displayMessage($error_message, 'error'); ?>
    
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
                    <div>
                        <h1 class="me-3"><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
                        <?php if ($place['country_name'] || $place['region_name']): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php 
                                $location = [];
                                if ($place['region_name']) $location[] = $place['region_name'];
                                if ($place['country_name']) $location[] = $place['country_name'];
                                echo htmlspecialchars(implode(', ', $location));
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editTitleForm">
                            <i class="fas fa-edit me-1"></i>Modifier le nom
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#editSceneModal">
                            <i class="fas fa-edit me-1"></i>Éditer le lieu
                        </button>
                    </div>
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
                <div>
                    <h1><i class="fas fa-photo-video me-2"></i><?php echo htmlspecialchars($place['title']); ?></h1>
                    <?php if ($place['country_name'] || $place['region_name']): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php 
                            $location = [];
                            if ($place['region_name']) $location[] = $place['region_name'];
                            if ($place['country_name']) $location[] = $place['country_name'];
                            echo htmlspecialchars(implode(', ', $location));
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <p class="text-muted mb-0">
                Campagne: <a href="view_campaign.php?id=<?php echo (int)$place['campaign_id']; ?>" class="text-decoration-none fw-bold" style="color: var(--bs-primary) !important;"><?php echo htmlspecialchars($place['campaign_title']); ?></a> • MJ: <?php echo htmlspecialchars($place['dm_username']); ?>
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
                <a href="view_place.php?id=<?php echo (int)$prevScene['id']; ?><?php echo $isModal ? '&modal=1' : ''; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-chevron-left me-1"></i>Précédente
                </a>
            <?php endif; ?>
            <?php if ($nextScene): ?>
                <a href="view_place.php?id=<?php echo (int)$nextScene['id']; ?><?php echo $isModal ? '&modal=1' : ''; ?>" class="btn btn-sm btn-outline-secondary">
                    Suivante<i class="fas fa-chevron-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Jets de dés -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-dice me-2"></i>Jets de dés</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Sélection des dés et résultats -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Choisir un dé :</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="4" title="Dé à 4 faces">
                                    <i class="fas fa-dice-d4"></i> D4
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="6" title="Dé à 6 faces">
                                    <i class="fas fa-dice"></i> D6
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="8" title="Dé à 8 faces">
                                    <i class="fas fa-dice-d8"></i> D8
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="10" title="Dé à 10 faces">
                                    <i class="fas fa-dice-d10"></i> D10
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="12" title="Dé à 12 faces">
                                    <i class="fas fa-dice-d12"></i> D12
                                </button>
                                <button type="button" class="btn btn-outline-primary dice-btn" data-sides="20" title="Dé à 20 faces">
                                    <i class="fas fa-dice-d20"></i> D20
                                </button>
                                <button type="button" class="btn btn-outline-success dice-btn" data-sides="100" title="Dé percentille">
                                    <i class="fas fa-percentage"></i> D100
                                </button>
                            </div>
                            
                            <!-- Options de lancer -->
                            <div class="mb-3">
                                <label for="dice-quantity" class="form-label">Nombre de dés :</label>
                                <select class="form-select" id="dice-quantity" style="max-width: 100px;">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="roll-dice-btn" disabled>
                                    <i class="fas fa-play me-2"></i>Lancer les dés
                                </button>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="hide-dice-roll" <?php echo $isOwnerDM ? '' : 'disabled'; ?>>
                                    <label class="form-check-label" for="hide-dice-roll">
                                        <small>Masquer ce jet</small>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Zone de résultats sous le bouton -->
                            <div class="mt-3">
                                <h6 class="mb-3">Résultats :</h6>
                                <div id="dice-results" class="border rounded p-3 bg-light" style="min-height: 120px;">
                                    <div class="text-muted text-center">
                                        <i class="fas fa-dice fa-2x mb-2"></i>
                                        <p class="mb-0">Sélectionnez un dé et lancez !</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Historique des jets -->
                        <div class="col-md-6">
                            <h6 class="mb-2">Historique des jets (50 derniers) :</h6>
                            <div id="dice-history" class="border rounded p-2 bg-white" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-history fa-lg mb-2"></i>
                                    <p class="mb-0 small">Chargement de l'historique...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                        <div class="form-text">Formats acceptés: JPG, PNG, GIF, WebP (max 10 Mo)</div>
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
                    
                    <?php if (!empty($place['map_url']) && file_exists($place['map_url'])): ?>
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
                                        // Priorité : characters.profile_photo, puis place_npcs.profile_photo, avec vérification d'existence
                                        $imageUrl = 'images/default_npc.png';
                                        if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                            $imageUrl = $npc['character_profile_photo'];
                                        } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                            $imageUrl = $npc['profile_photo'];
                                        }
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
                                        
                                        // Logique d'affichage selon la visibilité et l'identification
                                        if ($monster['is_visible'] && $monster['is_identified']) {
                                            // Monstre visible et identifié : utiliser l'image du bestiaire
                                            $monster_image_path = "images/monstres/{$monster['csv_id']}.jpg";
                                            if (file_exists($monster_image_path)) {
                                                $imageUrl = $monster_image_path;
                                            } else {
                                                $imageUrl = 'images/default_monster.png';
                                            }
                                        } else {
                                            // Monstre non visible ou non identifié : utiliser l'image par défaut
                                            $imageUrl = 'images/default_monster.png';
                                        }
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
                                    
                                    <!-- Pions des objets (générés par JavaScript) -->
                                    <!-- Les pions d'objets sont maintenant créés dynamiquement par JavaScript -->
                                </div>
                            </div>
                            
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if (file_exists($place['map_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($place['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>Ouvrir en plein écran
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Fichier de plan manquant
                                        </span>
                                    <?php endif; ?>
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
                            <?php if (!empty($place['map_url']) && !file_exists($place['map_url'])): ?>
                                <p>Plan référencé mais fichier manquant : <code><?php echo htmlspecialchars($place['map_url']); ?></code></p>
                                <?php if ($isOwnerDM): ?>
                                    <p class="small">Cliquez sur "Modifier le plan" pour téléverser un nouveau plan.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Aucun plan disponible pour ce lieu.</p>
                                <?php if ($isOwnerDM): ?>
                                    <p class="small">Cliquez sur "Modifier le plan" pour ajouter un plan.</p>
                                <?php endif; ?>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Joueurs présents</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addPlayerForm">
                            <i class="fas fa-user-plus me-1"></i>Ajouter
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($isOwnerDM): ?>
                        <div class="collapse mb-3" id="addPlayerForm">
                            <div class="card card-body">
                                <h6>Ajouter un joueur</h6>
                                <form method="POST" class="row g-2">
                                    <input type="hidden" name="action" value="add_player">
                                    <div class="col-12">
                                        <label class="form-label">Sélectionner un joueur</label>
                                        <select class="form-select" name="player_id" required>
                                            <option value="">Choisir un joueur...</option>
                                            <?php foreach ($campaignMembers as $member): ?>
                                                <?php
                                                // Vérifier si le joueur est déjà dans le lieu
                                                $alreadyPresent = false;
                                                foreach ($placePlayers as $player) {
                                                    if ($player['player_id'] == $member['id']) {
                                                        $alreadyPresent = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if (!$alreadyPresent): ?>
                                                    <option value="<?php echo (int)$member['id']; ?>" data-character-id="<?php echo (int)$member['character_id']; ?>">
                                                        <?php echo htmlspecialchars($member['username']); ?>
                                                        <?php if ($member['character_name']): ?>
                                                            (<?php echo htmlspecialchars($member['character_name']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-1"></i>Ajouter au lieu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?php echo htmlspecialchars($player['username']); ?></div>
                                            <?php if ($player['character_name']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($player['character_name']); ?></small>
                                            <?php endif; ?>
                                            
                                            <?php if ($player['character_name'] && $player['hit_points_max'] > 0): ?>
                                                <!-- Barre de points de vie -->
                                                <div class="mt-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-heart text-danger me-1"></i>PV
                                                        </small>
                                                        <small class="text-muted">
                                                            <?php echo (int)$player['hit_points_current']; ?> / <?php echo (int)$player['hit_points_max']; ?>
                                                        </small>
                                                    </div>
                                                    <?php 
                                                    $hp_percentage = ($player['hit_points_current'] / $player['hit_points_max']) * 100;
                                                    $hp_class = 'bg-success';
                                                    if ($hp_percentage <= 25) {
                                                        $hp_class = 'bg-danger';
                                                    } elseif ($hp_percentage <= 50) {
                                                        $hp_class = 'bg-warning';
                                                    }
                                                    ?>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar <?php echo $hp_class; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $hp_percentage; ?>%"
                                                             aria-valuenow="<?php echo $hp_percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100"
                                                             title="<?php echo (int)$player['hit_points_current']; ?> / <?php echo (int)$player['hit_points_max']; ?> PV">
                                                        </div>
                                                    </div>
                                                </div>
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
                                            // Utiliser characters.profile_photo en priorité, sinon place_npcs.profile_photo, avec vérification d'existence
                                            $photo_to_show = null;
                                            if (!empty($npc['character_profile_photo']) && file_exists($npc['character_profile_photo'])) {
                                                $photo_to_show = $npc['character_profile_photo'];
                                            } elseif (!empty($npc['profile_photo']) && file_exists($npc['profile_photo'])) {
                                                $photo_to_show = $npc['profile_photo'];
                                            }
                                            ?>
                                            <?php if (!empty($photo_to_show)): ?>
                                                <img src="<?php echo htmlspecialchars($photo_to_show); ?>" alt="Photo de <?php echo htmlspecialchars($npc['name']); ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user-tie text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($npc['name']); ?>
                                                    <?php if (!empty($npc['npc_character_id'])): ?>
                                                        <span class="badge bg-info ms-1">perso MJ</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($npc['description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($npc['description']); ?></small>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($npc['npc_character_id']) && $npc['hit_points_max'] > 0): ?>
                                                    <!-- Barre de points de vie pour les PNJ personnages -->
                                                    <div class="mt-2">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="text-muted">
                                                                <i class="fas fa-heart text-danger me-1"></i>PV
                                                            </small>
                                                            <small class="text-muted">
                                                                <?php echo (int)$npc['hit_points_current']; ?> / <?php echo (int)$npc['hit_points_max']; ?>
                                                            </small>
                                                        </div>
                                                        <?php 
                                                        $hp_percentage = ($npc['hit_points_current'] / $npc['hit_points_max']) * 100;
                                                        $hp_class = 'bg-success';
                                                        if ($hp_percentage <= 25) {
                                                            $hp_class = 'bg-danger';
                                                        } elseif ($hp_percentage <= 50) {
                                                            $hp_class = 'bg-warning';
                                                        }
                                                        ?>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar <?php echo $hp_class; ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?php echo $hp_percentage; ?>%"
                                                                 aria-valuenow="<?php echo $hp_percentage; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"
                                                                 title="<?php echo (int)$npc['hit_points_current']; ?> / <?php echo (int)$npc['hit_points_max']; ?> PV">
                                                            </div>
                                                        </div>
                                                    </div>
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
                                                        <i class="fas <?php echo $npc['is_identified'] ? 'fa-user-check' : 'fa-question'; ?>"></i>
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
                                            <?php 
                                            // Utiliser l'image du monstre du bestiaire
                                            $monster_image_path = "images/monstres/{$monster['csv_id']}.jpg";
                                            if (file_exists($monster_image_path)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($monster_image_path); ?>" 
                                                     alt="<?php echo htmlspecialchars($monster['name']); ?>" 
                                                     class="rounded me-2" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-danger rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-dragon text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($monster['type']); ?> • 
                                                    <?php echo htmlspecialchars($monster['size']); ?> • 
                                                    CR <?php echo htmlspecialchars($monster['challenge_rating']); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    CA <?php echo htmlspecialchars($monster['armor_class']); ?>
                                                </small>
                                                
                                                <!-- Barre de points de vie pour les monstres -->
                                                <div class="mt-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-heart text-danger me-1"></i>PV
                                                        </small>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $current_hp = $monster['current_hit_points'] ?? $monster['hit_points'];
                                                            $max_hp = $monster['hit_points'];
                                                            echo (int)$current_hp . ' / ' . (int)$max_hp;
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <?php 
                                                    $hp_percentage = ($current_hp / $max_hp) * 100;
                                                    $hp_class = 'bg-success';
                                                    if ($hp_percentage <= 25) {
                                                        $hp_class = 'bg-danger';
                                                    } elseif ($hp_percentage <= 50) {
                                                        $hp_class = 'bg-warning';
                                                    }
                                                    ?>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar <?php echo $hp_class; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $hp_percentage; ?>%"
                                                             aria-valuenow="<?php echo $hp_percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100"
                                                             title="<?php echo (int)$current_hp; ?> / <?php echo (int)$max_hp; ?> PV">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="view_monster_sheet.php?id=<?php echo (int)$monster['id']; ?>&campaign_id=<?php echo (int)$place['campaign_id']; ?>" class="btn btn-sm btn-outline-danger" title="Voir la feuille du monstre" target="_blank">
                                                <i class="fas fa-dragon"></i>
                                            </a>
                                            <?php if ($isOwnerDM): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($monster['is_identified'] ? 'Désidentifier' : 'Identifier'); ?> <?php echo htmlspecialchars($monster['name']); ?> pour les joueurs ?');">
                                                    <input type="hidden" name="action" value="toggle_monster_identification">
                                                    <input type="hidden" name="npc_id" value="<?php echo (int)$monster['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $monster['is_identified'] ? 'btn-outline-info' : 'btn-outline-secondary'; ?>" title="<?php echo $monster['is_identified'] ? 'Désidentifier pour les joueurs' : 'Identifier pour les joueurs'; ?>">
                                                        <i class="fas <?php echo $monster['is_identified'] ? 'fa-dragon' : 'fa-question'; ?>"></i>
                                                    </button>
                                                </form>
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
            
            <!-- Section Objets du lieu -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Objets du lieu</span>
                    <?php if ($isOwnerDM): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addObjectModal">
                            <i class="fas fa-plus me-1"></i>Ajouter objet
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($placeObjects)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucun objet dans ce lieu</p>
                        </div>
                    <?php else: ?>
                     <ul class="list-group list-group-flush">
                         <?php 
                         // Afficher seulement les objets non attribués (même pour le MJ)
                         foreach ($placeObjects as $object): 
                         ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php
                                            $icon_class = 'fa-box';
                                            $badge_class = 'bg-secondary';
                                            $type_label = ucfirst($object['object_type']);
                                            
                                            switch ($object['object_type']) {
                                                case 'poison':
                                                    $icon_class = 'fa-skull-crossbones';
                                                    $badge_class = 'bg-danger';
                                                    $type_label = 'Poison';
                                                    break;
                                                case 'bourse':
                                                    $icon_class = 'fa-coins';
                                                    $badge_class = 'bg-warning';
                                                    $type_label = 'Bourse';
                                                    break;
                                                case 'outil':
                                                    $icon_class = 'fa-tools';
                                                    $badge_class = 'bg-info';
                                                    $type_label = 'Outil';
                                                    break;
                                                case 'letter':
                                                    $icon_class = 'fa-envelope';
                                                    $badge_class = 'bg-primary';
                                                    $type_label = 'Lettre';
                                                    break;
                                                case 'weapon':
                                                    $icon_class = 'fa-sword';
                                                    $badge_class = 'bg-danger';
                                                    $type_label = 'Arme';
                                                    break;
                                                case 'armor':
                                                    $icon_class = 'fa-shield-alt';
                                                    $badge_class = 'bg-success';
                                                    $type_label = 'Armure';
                                                    break;
                                                case 'magical_item':
                                                    $icon_class = 'fa-magic';
                                                    $badge_class = 'bg-info';
                                                    $type_label = 'Objet magique';
                                                    break;
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon_class; ?> fa-lg text-muted"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <strong><?php echo htmlspecialchars($object['display_name']); ?></strong>
                                                <span class="badge <?php echo $badge_class; ?> ms-2"><?php echo $type_label; ?></span>
                                                <?php if ($object['is_visible']): ?>
                                                    <span class="badge bg-warning ms-1" title="Visible des joueurs">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary ms-1" title="Invisible des joueurs">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($object['object_type'] === 'letter' && $object['is_sealed']): ?>
                                                    <span class="badge bg-dark ms-1" title="Lettre cachetée">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                             <?php if ($object['is_identified']): ?>
                                                 <span class="badge bg-success ms-1" title="Identifié par les joueurs">
                                                     <i class="fas fa-check-circle"></i>
                                                 </span>
                                             <?php else: ?>
                                                 <span class="badge bg-warning ms-1" title="Non identifié par les joueurs">
                                                     <i class="fas fa-question-circle"></i>
                                                 </span>
                                             <?php endif; ?>
                                             <?php if ($object['owner_type'] !== 'none'): ?>
                                                 <span class="badge bg-info ms-1" title="Attribué à un <?php echo $object['owner_type'] === 'player' ? 'joueur' : 'PNJ'; ?>">
                                                     <i class="fas fa-user"></i>
                                                 </span>
                                             <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($object['is_identified']): ?>
                                                <!-- Affichage pour objet identifié -->
                                                <?php if (!empty($object['item_name']) && $object['item_name'] !== $object['display_name']): ?>
                                                    <small class="text-info">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Objet sélectionné: <?php echo htmlspecialchars($object['item_name']); ?>
                                                    </small><br>
                                                <?php endif; ?>
                                                
                                             <?php if (!empty($object['description'])): ?>
                                                 <small class="text-muted"><?php echo nl2br(htmlspecialchars($object['description'])); ?></small>
                                             <?php endif; ?>
                                             
                                             <?php if ($object['owner_type'] !== 'none'): ?>
                                                 <div class="mt-1">
                                                     <small class="text-info">
                                                         <i class="fas fa-user me-1"></i>
                                                         <?php 
                                                         if ($object['owner_type'] === 'player') {
                                                             // Trouver le nom du joueur
                                                             $owner_name = 'Joueur inconnu';
                                                             foreach ($placePlayers as $player) {
                                                                 if ($player['player_id'] == $object['owner_id']) {
                                                                     $owner_name = $player['character_name'] ?: $player['username'];
                                                                     break;
                                                                 }
                                                             }
                                                             echo 'Attribué à: ' . htmlspecialchars($owner_name);
                                                         } elseif ($object['owner_type'] === 'npc') {
                                                             // Trouver le nom du PNJ
                                                             $owner_name = 'PNJ inconnu';
                                                             foreach ($placeNpcs as $npc) {
                                                                 if ($npc['id'] == $object['owner_id']) {
                                                                     $owner_name = $npc['name'];
                                                                     break;
                                                                 }
                                                             }
                                                             echo 'Attribué à: ' . htmlspecialchars($owner_name);
                                                         } elseif ($object['owner_type'] === 'monster') {
                                                             // Trouver le nom du monstre
                                                             $owner_name = 'Monstre inconnu';
                                                             foreach ($placeMonsters as $monster) {
                                                                 if ($monster['id'] == $object['owner_id']) {
                                                                     $owner_name = $monster['name'];
                                                                     break;
                                                                 }
                                                             }
                                                             echo 'Attribué à: ' . htmlspecialchars($owner_name);
                                                         }
                                                         ?>
                                                     </small>
                                                 </div>
                                             <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Affichage pour objet non identifié -->
                                                <small class="text-muted">
                                                    <i class="fas fa-question-circle me-1"></i>
                                                    <?php 
                                                    // Description générale selon le type
                                                    switch ($object['object_type']) {
                                                        case 'poison':
                                                            echo "Une fiole contenant un liquide suspect. Sa nature exacte reste à déterminer.";
                                                            break;
                                                        case 'magical_item':
                                                            echo "Un objet qui dégage une aura magique. Ses propriétés sont inconnues.";
                                                            break;
                                                        case 'weapon':
                                                            echo "Une arme d'apparence ordinaire. Ses qualités particulières ne sont pas évidentes.";
                                                            break;
                                                        case 'armor':
                                                            echo "Une pièce d'armure standard. Ses propriétés spéciales ne sont pas apparentes.";
                                                            break;
                                                        case 'bourse':
                                                            $gold = (int)$object['gold_coins'];
                                                            $silver = (int)$object['silver_coins'];
                                                            $copper = (int)$object['copper_coins'];
                                                            
                                                            if ($gold > 0 || $silver > 0 || $copper > 0) {
                                                                echo "Une bourse contenant ";
                                                                $parts = [];
                                                                if ($gold > 0) $parts[] = "$gold pièce" . ($gold > 1 ? 's' : '') . " d'or";
                                                                if ($silver > 0) $parts[] = "$silver pièce" . ($silver > 1 ? 's' : '') . " d'argent";
                                                                if ($copper > 0) $parts[] = "$copper pièce" . ($copper > 1 ? 's' : '') . " de cuivre";
                                                                echo implode(', ', $parts) . ".";
                                                            } else {
                                                                echo "Une bourse vide.";
                                                            }
                                                            break;
                                                        case 'letter':
                                                            echo "Une lettre. Son contenu et son expéditeur sont inconnus.";
                                                            break;
                                                        default:
                                                            echo "Un objet mystérieux. Sa nature exacte reste à découvrir.";
                                                    }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($object['is_identified']): ?>
                                                <!-- Contenu détaillé pour objets identifiés -->
                                                <?php if ($object['object_type'] === 'letter' && !empty($object['letter_content'])): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <strong>Contenu de la lettre:</strong><br>
                                                            <?php echo nl2br(htmlspecialchars($object['letter_content'])); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($object['object_type'] === 'coins' && ($object['gold_coins'] > 0 || $object['silver_coins'] > 0 || $object['copper_coins'] > 0)): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <strong>Contenu du trésor:</strong><br>
                                                            <?php 
                                                            $coins_parts = [];
                                                            if ($object['gold_coins'] > 0) $coins_parts[] = '<span class="text-warning"><i class="fas fa-coins"></i> ' . $object['gold_coins'] . ' po</span>';
                                                            if ($object['silver_coins'] > 0) $coins_parts[] = '<span class="text-secondary"><i class="fas fa-coins"></i> ' . $object['silver_coins'] . ' pa</span>';
                                                            if ($object['copper_coins'] > 0) $coins_parts[] = '<span class="text-danger"><i class="fas fa-coins"></i> ' . $object['copper_coins'] . ' pc</span>';
                                                            echo implode(' | ', $coins_parts);
                                                            ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($isOwnerDM): ?>
                                        <div class="d-flex gap-1">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($object['is_visible'] ? 'Masquer' : 'Afficher'); ?> <?php echo htmlspecialchars($object['display_name']); ?> pour les joueurs ?');">
                                                <input type="hidden" name="action" value="toggle_object_visibility">
                                                <input type="hidden" name="object_id" value="<?php echo (int)$object['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $object['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $object['is_visible'] ? 'Masquer pour les joueurs' : 'Afficher pour les joueurs'; ?>">
                                                    <i class="fas <?php echo $object['is_visible'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($object['is_identified'] ? 'Désidentifier' : 'Identifier'); ?> <?php echo htmlspecialchars($object['display_name']); ?> pour les joueurs ?');">
                                                <input type="hidden" name="action" value="toggle_object_identification">
                                                <input type="hidden" name="object_id" value="<?php echo (int)$object['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $object['is_identified'] ? 'btn-outline-success' : 'btn-outline-warning'; ?>" title="<?php echo $object['is_identified'] ? 'Désidentifier pour les joueurs' : 'Identifier pour les joueurs'; ?>">
                                                    <i class="fas <?php echo $object['is_identified'] ? 'fa-check-circle' : 'fa-question-circle'; ?>"></i>
                                                </button>
                                            </form>
                                         <button type="button" class="btn btn-sm btn-outline-info" title="Attribuer l'objet" onclick="showAssignObjectModal(<?php echo $object['id']; ?>, '<?php echo htmlspecialchars($object['display_name']); ?>', '<?php echo $object['owner_type']; ?>', <?php echo $object['owner_id'] ?: 'null'; ?>)">
                                             <i class="fas fa-user-plus"></i>
                                         </button>
                                         <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer <?php echo htmlspecialchars($object['display_name']); ?> de ce lieu ?');">
                                             <input type="hidden" name="action" value="remove_object">
                                             <input type="hidden" name="object_id" value="<?php echo (int)$object['id']; ?>">
                                             <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer l'objet">
                                                 <i class="fas fa-trash"></i>
                                             </button>
                                         </form>
                                        </div>
                                    <?php endif; ?>
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

        if (poisonSearch && poisonResults) {
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

    });
    </script>

    <?php if ($isOwnerDM && !empty($place['map_url'])): ?>
    <script>
    // Variables globales pour le drag & drop
    let draggedToken = null;
    let isDragging = false;
    
    function initializeTokenSystem() {
        const mapImage = document.getElementById('mapImage');
        const tokens = document.querySelectorAll('.token');
        const resetBtn = document.getElementById('resetTokensBtn');
        
        if (!mapImage || tokens.length === 0) return;

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
            
            // Sauvegarder la position selon le type de pion
            if (draggedToken.classList.contains('object-token')) {
                saveObjectTokenPosition(draggedToken, clampedX, clampedY, true);
            } else {
                saveTokenPosition(draggedToken, clampedX, clampedY, true);
            }
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

            // Remettre dans la sidebar selon le type de pion
            if (draggedToken.classList.contains('object-token')) {
                positionObjectTokenInSidebar(draggedToken);
                saveObjectTokenPosition(draggedToken, 0, 0, false);
            } else {
                resetTokenToSidebar(draggedToken);
                saveTokenPosition(draggedToken, 0, 0, false);
            }
        });

        // Les pions d'objets sont maintenant gérés par initializeObjectTokensDragDrop()
        console.log('🔧 Les pions d\'objets seront initialisés par JavaScript');

        // Bouton de réinitialisation
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir remettre tous les pions sur le côté du plan ?')) {
                    resetAllTokens();
                }
            });
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

            console.log('=== SAVE TOKEN POSITION DEBUG ===');
            console.log('Données à envoyer:', data);
            console.log('Token:', token);
            console.log('Position:', {x, y, isOnMap});

            fetch('update_token_position.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Réponse reçue:', response.status, response.statusText);
                return response.json();
            })
            .then(result => {
                console.log('Résultat de la sauvegarde:', result);
                if (!result.success) {
                    console.error('Erreur lors de la sauvegarde:', result.error);
                } else {
                    console.log('✅ Position sauvegardée avec succès');
                }
            })
            .catch(error => {
                console.error('❌ Erreur lors de l\'appel:', error);
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
                    
                    // Les pions d'objets sont maintenant gérés par JavaScript
                    console.log('🔧 Les pions d\'objets seront réinitialisés par JavaScript');
                } else {
                    console.error('Erreur lors de la réinitialisation:', result.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
    }
    
    // Fonction pour positionner un pion d'objet dans la sidebar
    function positionObjectTokenInSidebar(token) {
        const sidebar = document.getElementById('tokenSidebar');
        if (!sidebar) {
            console.error('tokenSidebar non trouvé');
            return;
        }
        
        // Style pour la sidebar
        token.style.position = 'static';
        token.style.margin = '2px';
        token.style.display = 'inline-block';
        token.style.float = 'none';
        token.style.left = 'auto';
        token.style.top = 'auto';
        
        sidebar.appendChild(token);
        console.log('Pion d\'objet ajouté à la sidebar:', token.dataset.objectName);
    }
    
    // Fonction pour positionner un pion sur la carte
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
    
    // Fonction pour créer les pions d'objets
    function createObjectToken(objectId, objectName, objectType, isIdentified, x, y, isOnMap) {
        console.log('🔧 createObjectToken appelé:', objectName, objectType, isIdentified, x, y, isOnMap);
        
        const mapImage = document.getElementById('mapImage');
        if (!mapImage) {
            console.error('mapImage non trouvé dans createObjectToken');
            return;
        }
        
        const token = document.createElement('div');
        token.className = 'object-token';
        token.dataset.tokenType = 'object';
        token.dataset.entityId = objectId;
        token.dataset.objectId = objectId;
        token.dataset.objectName = objectName;
        token.dataset.objectType = objectType;
        token.dataset.isIdentified = isIdentified;
        token.dataset.positionX = x;
        token.dataset.positionY = y;
        token.dataset.isOnMap = isOnMap;
        
        // Style du pion carré doré
        token.style.cssText = `
            width: 24px;
            height: 24px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border: 2px solid #FF8C00;
            border-radius: 4px;
            cursor: move;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #8B4513;
            font-weight: bold;
        `;
        
        // Icône selon le type d'objet et l'identification
        const icon = document.createElement('i');
        
        if (!isIdentified) {
            // Objet non identifié : afficher un "?"
            icon.className = 'fas fa-question';
            icon.style.color = '#8B4513';
            icon.style.fontWeight = 'bold';
        } else {
            // Objet identifié : afficher l'icône selon le type
            switch (objectType) {
                case 'poison':
                    icon.className = 'fas fa-flask';
                    icon.style.color = '#dc3545';
                    break;
                case 'magical_item':
                    icon.className = 'fas fa-magic';
                    icon.style.color = '#0dcaf0';
                    break;
                case 'weapon':
                    icon.className = 'fas fa-sword';
                    icon.style.color = '#dc3545';
                    break;
                case 'armor':
                    icon.className = 'fas fa-shield-alt';
                    icon.style.color = '#198754';
                    break;
                case 'letter':
                    icon.className = 'fas fa-envelope';
                    icon.style.color = '#0d6efd';
                    break;
                case 'bourse':
                    icon.className = 'fas fa-coins';
                    icon.style.color = '#ffc107';
                    break;
                case 'outil':
                    icon.className = 'fas fa-tools';
                    icon.style.color = '#6c757d';
                    break;
                default:
                    icon.className = 'fas fa-box';
                    icon.style.color = '#6c757d';
            }
        }
        
        token.appendChild(icon);
        
        // Tooltip
        token.title = objectName;
        
        // Gestion du drag & drop
        token.draggable = true;
        console.log('🔧 Token rendu draggable:', objectName, 'draggable =', token.draggable);
        
        // Positionner le pion selon son état
        if (isOnMap) {
            // Si l'objet est sur la carte, le positionner sur la carte
            positionTokenOnMap(token, x, y);
        } else {
            // Sinon, le positionner dans la sidebar
            positionObjectTokenInSidebar(token);
        }
        
        console.log('✅ Pion d\'objet créé:', objectName, isOnMap ? 'sur la carte' : 'dans la sidebar');
        
        // Debug: vérifier les attributs du token créé
        console.log('🔍 Debug token créé:', {
            className: token.className,
            objectName: token.dataset.objectName,
            objectType: token.dataset.objectType,
            objectId: token.dataset.objectId,
            entityId: token.dataset.entityId,
            tokenType: token.dataset.tokenType,
            draggable: token.draggable
        });
    }
    
         // Initialiser le système de pions après que le DOM soit complètement chargé
         document.addEventListener('DOMContentLoaded', function() {
             initializeTokenSystem();
             initializeObjectTokens();
         });
         
         // Fonction pour afficher le modal d'attribution d'objet
         function showAssignObjectModal(objectId, objectName, currentOwnerType, currentOwnerId) {
             document.getElementById('assignObjectId').value = objectId;
             document.getElementById('assignObjectName').textContent = objectName;
             
             // Réinitialiser les sélections
             document.getElementById('ownerType').value = currentOwnerType || 'none';
             updateOwnerOptions();
             
             // Si l'objet a déjà un propriétaire, le sélectionner
             if (currentOwnerId && currentOwnerType !== 'none') {
                 setTimeout(() => {
                     document.getElementById('ownerId').value = currentOwnerId;
                 }, 100);
             }
             
             // Afficher le modal
             const modal = new bootstrap.Modal(document.getElementById('assignObjectModal'));
             modal.show();
         }
         
         // Fonction pour mettre à jour les options de propriétaire
         function updateOwnerOptions() {
             const ownerType = document.getElementById('ownerType').value;
             const ownerSelection = document.getElementById('ownerSelection');
             const ownerId = document.getElementById('ownerId');
             const ownerSelectionLabel = document.getElementById('ownerSelectionLabel');
             
             // Vider les options
             ownerId.innerHTML = '<option value="">Choisir...</option>';
             
             if (ownerType === 'none') {
                 ownerSelection.style.display = 'none';
             } else {
                 ownerSelection.style.display = 'block';
                 
                 if (ownerType === 'player') {
                     ownerSelectionLabel.textContent = 'Sélectionner un joueur';
                     
                     // Ajouter les joueurs
                     <?php foreach ($placePlayers as $player): ?>
                         ownerId.innerHTML += '<option value="<?php echo $player['player_id']; ?>"><?php echo htmlspecialchars($player['character_name'] ?: $player['username']); ?></option>';
                     <?php endforeach; ?>
                 } else if (ownerType === 'npc') {
                     ownerSelectionLabel.textContent = 'Sélectionner un PNJ';
                     
                     // Ajouter les PNJ
                     <?php foreach ($placeNpcs as $npc): ?>
                         ownerId.innerHTML += '<option value="<?php echo $npc['id']; ?>"><?php echo htmlspecialchars($npc['name']); ?></option>';
                     <?php endforeach; ?>
                 } else if (ownerType === 'monster') {
                     ownerSelectionLabel.textContent = 'Sélectionner un monstre';
                     
                     // Ajouter les monstres
                     <?php foreach ($placeMonsters as $monster): ?>
                         ownerId.innerHTML += '<option value="<?php echo $monster['id']; ?>"><?php echo htmlspecialchars($monster['name']); ?></option>';
                     <?php endforeach; ?>
                 }
             }
         }
    
    // Gestion des pions dorés pour les objets
    function initializeObjectTokens() {
        const mapImage = document.getElementById('mapImage');
        if (!mapImage) {
            console.error('mapImage non trouvé');
            return;
        }
        
        console.log('Initialisation des pions d\'objets...');
        
        // Créer les pions dorés pour les objets visibles
        <?php foreach ($placeObjects as $object): ?>
            <?php if ($object['is_visible']): ?>
                console.log('Création du pion pour: <?php echo htmlspecialchars($object['display_name']); ?> (<?php echo $object['object_type']; ?>, identifié: <?php echo $object['is_identified'] ? 'oui' : 'non'; ?>)');
                createObjectToken(<?php echo $object['id']; ?>, '<?php echo htmlspecialchars($object['display_name']); ?>', '<?php echo $object['object_type']; ?>', <?php echo $object['is_identified'] ? 'true' : 'false'; ?>, <?php echo $object['position_x']; ?>, <?php echo $object['position_y']; ?>, <?php echo $object['is_on_map'] ? 'true' : 'false'; ?>);
            <?php endif; ?>
        <?php endforeach; ?>
        
        console.log('Initialisation des pions terminée');
        
        // Initialiser le drag & drop pour les tokens d'objets créés
        initializeObjectTokensDragDrop();
    }
    
    // Fonction pour initialiser le drag & drop des tokens d'objets
    function initializeObjectTokensDragDrop() {
        const objectTokens = document.querySelectorAll('.object-token');
        console.log('🔧 Initialisation drag & drop pour', objectTokens.length, 'tokens d\'objets (après création)');
        
        // Debug: lister tous les tokens trouvés
        objectTokens.forEach((token, index) => {
            console.log(`🔧 Token ${index + 1}:`, {
                className: token.className,
                objectName: token.dataset.objectName,
                objectType: token.dataset.objectType,
                objectId: token.dataset.objectId,
                entityId: token.dataset.entityId,
                tokenType: token.dataset.tokenType
            });
        });
        
        objectTokens.forEach(token => {
            // Vérifier si le token n'a pas déjà été initialisé
            if (!token.dataset.dragInitialized) {
                token.draggable = true;
                token.dataset.dragInitialized = 'true';
                console.log('🔧 Token initialisé comme draggable:', token.dataset.objectName, 'draggable =', token.draggable);
                
                token.addEventListener('dragstart', function(e) {
                    console.log('🚀 Drag start:', this.dataset.objectName);
                    draggedToken = this;
                    isDragging = true;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.outerHTML);
                    this.style.opacity = '0.5';
                });

                token.addEventListener('dragend', function(e) {
                    console.log('🏁 Drag end:', this.dataset.objectName);
                    this.style.opacity = '1';
                    draggedToken = null;
                    isDragging = false;
                });
            }
        });
    }
    
    
    
    function saveObjectTokenPosition(token, x, y, isOnMap) {
        console.log('💾 saveObjectTokenPosition appelé:', token.dataset.objectName, 'x:', x, 'y:', y, 'isOnMap:', isOnMap);
        
        // Mettre à jour les données du token
        token.dataset.positionX = x;
        token.dataset.positionY = y;
        token.dataset.isOnMap = isOnMap;
        
        const data = {
            place_id: <?php echo $place_id; ?>,
            object_id: parseInt(token.dataset.objectId || token.dataset.entityId),
            position_x: x,
            position_y: y,
            is_on_map: isOnMap
        };
        
        console.log('💾 Sauvegarde position objet:', data);
        
        fetch('update_object_position.php', {
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
    </script>
    <?php endif; ?>

    <script>
    // Gestion de l'ajout de joueurs
    document.addEventListener('DOMContentLoaded', function() {
        const playerSelect = document.querySelector('select[name="player_id"]');
        if (playerSelect) {
            playerSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const characterId = selectedOption.getAttribute('data-character-id');
                
                // Créer un champ caché pour le character_id si nécessaire
                let characterIdInput = document.querySelector('input[name="character_id"]');
                if (!characterIdInput) {
                    characterIdInput = document.createElement('input');
                    characterIdInput.type = 'hidden';
                    characterIdInput.name = 'character_id';
                    this.parentNode.appendChild(characterIdInput);
                }
                
                characterIdInput.value = characterId || '';
            });
        }
        
        // Gestion des types d'objets dynamiques
        const objectTypeSelect = document.getElementById('objectType');
        const itemSelection = document.getElementById('itemSelection');
        const letterContent = document.getElementById('letterContent');
        const coinsContent = document.getElementById('coinsContent');
        const selectedItemSelect = document.getElementById('selectedItem');
        const itemSelectionLabel = document.getElementById('itemSelectionLabel');
        
        if (objectTypeSelect) {
            objectTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                
                // Masquer toutes les sections dynamiques
                itemSelection.style.display = 'none';
                letterContent.style.display = 'none';
                coinsContent.style.display = 'none';
                
                // Vider les sélections
                selectedItemSelect.innerHTML = '<option value="">Choisir...</option>';
                
                if (selectedType === 'poison') {
                    loadPoisons();
                    itemSelectionLabel.textContent = 'Sélectionner un poison';
                    itemSelection.style.display = 'block';
                } else if (selectedType === 'magical_item') {
                    loadMagicalItems();
                    itemSelectionLabel.textContent = 'Sélectionner un objet magique';
                    itemSelection.style.display = 'block';
                } else if (selectedType === 'weapon') {
                    loadWeapons();
                    itemSelectionLabel.textContent = 'Sélectionner une arme';
                    itemSelection.style.display = 'block';
                } else if (selectedType === 'armor') {
                    loadArmors();
                    itemSelectionLabel.textContent = 'Sélectionner une armure';
                    itemSelection.style.display = 'block';
                } else if (selectedType === 'letter') {
                    letterContent.style.display = 'block';
                } else if (selectedType === 'coins') {
                    coinsContent.style.display = 'block';
                }
            });
        }
        
        // Fonction pour charger les poisons
        function loadPoisons() {
            fetch('get_poisons.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedItemSelect.innerHTML = '<option value="">Choisir un poison...</option>';
                        data.poisons.forEach(poison => {
                            const option = document.createElement('option');
                            option.value = poison.id;
                            option.textContent = poison.nom;
                            option.setAttribute('data-description', poison.description || '');
                            selectedItemSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des poisons:', error));
        }
        
        // Fonction pour charger les objets magiques
        function loadMagicalItems() {
            fetch('get_magical_items.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedItemSelect.innerHTML = '<option value="">Choisir un objet magique...</option>';
                        data.items.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.nom;
                            option.setAttribute('data-description', item.description || '');
                            selectedItemSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des objets magiques:', error));
        }
        
        // Fonction pour charger les armes
        function loadWeapons() {
            fetch('get_weapons.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedItemSelect.innerHTML = '<option value="">Choisir une arme...</option>';
                        data.weapons.forEach(weapon => {
                            const option = document.createElement('option');
                            option.value = weapon.id;
                            option.textContent = weapon.nom;
                            option.setAttribute('data-description', weapon.description || '');
                            selectedItemSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des armes:', error));
        }
        
        // Fonction pour charger les armures
        function loadArmors() {
            fetch('get_armors.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedItemSelect.innerHTML = '<option value="">Choisir une armure...</option>';
                        data.armors.forEach(armor => {
                            const option = document.createElement('option');
                            option.value = armor.id;
                            option.textContent = armor.nom;
                            option.setAttribute('data-description', armor.description || '');
                            selectedItemSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des armures:', error));
        }
        
        // Mettre à jour la description quand un objet est sélectionné
        if (selectedItemSelect) {
            selectedItemSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const description = selectedOption.getAttribute('data-description');
                const objectDescription = document.getElementById('objectDescription');
                
                if (description && objectDescription) {
                    objectDescription.value = description;
                }
            });
        }
    });

    // ===== LOGIQUE DES DÉS =====

    let selectedDiceSides = null;
    let currentCampaignId = <?php echo (int)$place['campaign_id']; ?>;

    // Gestion de la sélection des dés
    document.addEventListener('DOMContentLoaded', function() {
        const diceButtons = document.querySelectorAll('.dice-btn');
        const rollButton = document.getElementById('roll-dice-btn');
        const resultsDiv = document.getElementById('dice-results');
        
        // Charger l'historique des jets au chargement de la page
        loadDiceHistory();
        
        // Mettre à jour l'historique des jets automatiquement toutes les 3 secondes
        diceHistoryInterval = setInterval(loadDiceHistory, 3000);
        
        // Ajouter les événements aux boutons de dés
        diceButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Retirer la sélection précédente
                diceButtons.forEach(btn => {
                    btn.classList.remove('btn-primary', 'btn-success');
                    btn.classList.add('btn-outline-primary', 'btn-outline-success');
                });
                
                // Sélectionner le dé actuel
                selectedDiceSides = parseInt(this.getAttribute('data-sides'));
                this.classList.remove('btn-outline-primary', 'btn-outline-success');
                
                if (selectedDiceSides === 100) {
                    this.classList.add('btn-success');
                } else {
                    this.classList.add('btn-primary');
                }
                
                // Activer le bouton de lancer
                rollButton.disabled = false;
                
                // Mettre à jour l'affichage
                updateDiceSelectionDisplay();
            });
        });
        
        // Gestion du lancer de dés
        rollButton.addEventListener('click', function() {
            if (selectedDiceSides) {
                rollDice();
            }
        });
    });

    // Fonction pour mettre à jour l'affichage de la sélection
    function updateDiceSelectionDisplay() {
        const resultsDiv = document.getElementById('dice-results');
        const quantity = document.getElementById('dice-quantity').value;
        
        if (selectedDiceSides) {
            resultsDiv.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-primary"></i>
                    <p class="mb-0"><strong>${quantity} dé${quantity > 1 ? 's' : ''} à ${selectedDiceSides} face${selectedDiceSides > 1 ? 's' : ''}</strong></p>
                    <small class="text-muted">Prêt à lancer !</small>
                </div>
            `;
        }
    }

    // Fonction pour obtenir l'icône du dé
    function getDiceIcon(sides) {
        switch(sides) {
            case 4: return 'd4';
            case 6: return '';
            case 8: return 'd8';
            case 10: return 'd10';
            case 12: return 'd12';
            case 20: return 'd20';
            case 100: return '';
            default: return '';
        }
    }

    // Fonction pour lancer les dés
    function rollDice() {
        const quantity = parseInt(document.getElementById('dice-quantity').value);
        const resultsDiv = document.getElementById('dice-results');
        const rollButton = document.getElementById('roll-dice-btn');
        
        // Désactiver le bouton pendant l'animation
        rollButton.disabled = true;
        rollButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Lancement...';
        
        // Animation de lancer
        let animationCount = 0;
        const animationInterval = setInterval(() => {
            animationCount++;
            const randomResults = [];
            for (let i = 0; i < quantity; i++) {
                randomResults.push(Math.floor(Math.random() * selectedDiceSides) + 1);
            }
            
            resultsDiv.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-warning"></i>
                    <div class="mb-2">
                        ${randomResults.map(result => `<span class="badge bg-warning text-dark me-1">${result}</span>`).join('')}
                    </div>
                    <small class="text-muted">Lancement...</small>
                </div>
            `;
            
            if (animationCount >= 10) {
                clearInterval(animationInterval);
                showFinalResults(randomResults);
            }
        }, 100);
    }

    // Fonction pour afficher les résultats finaux
    function showFinalResults(results) {
        const resultsDiv = document.getElementById('dice-results');
        const rollButton = document.getElementById('roll-dice-btn');
        const total = results.reduce((sum, result) => sum + result, 0);
        const maxResult = Math.max(...results);
        const minResult = Math.min(...results);
        
        // Réactiver le bouton
        rollButton.disabled = false;
        rollButton.innerHTML = '<i class="fas fa-play me-2"></i>Lancer les dés';
        
        // Afficher les résultats
        let resultsHtml = `
            <div class="text-center">
                <i class="fas fa-dice-${getDiceIcon(selectedDiceSides)} fa-2x mb-2 text-success"></i>
                <h5 class="text-success mb-3">Résultats du lancer</h5>
        `;
        
        // Afficher chaque résultat
        resultsHtml += '<div class="mb-3">';
        results.forEach((result, index) => {
            let badgeClass = 'bg-primary';
            if (result === selectedDiceSides) {
                badgeClass = 'bg-success'; // Critique
            } else if (result === 1 && selectedDiceSides === 20) {
                badgeClass = 'bg-danger'; // Échec critique (uniquement sur D20)
            }
            resultsHtml += `<span class="badge ${badgeClass} me-1 fs-6">${result}</span>`;
        });
        resultsHtml += '</div>';
        
        // Statistiques
        resultsHtml += `
            <div class="row text-center">
                <div class="col-4">
                    <small class="text-muted">Total</small><br>
                    <strong class="text-primary">${total}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Max</small><br>
                    <strong class="text-success">${maxResult}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Min</small><br>
                    <strong class="text-danger">${minResult}</strong>
                </div>
            </div>
        `;
        
        // Message spécial pour les critiques (uniquement sur D20)
        if (selectedDiceSides === 20) {
            if (results.includes(20) && results.includes(1)) {
                resultsHtml += '<div class="alert alert-warning mt-2 mb-0"><small><i class="fas fa-exclamation-triangle me-1"></i>Critique et échec critique !</small></div>';
            } else if (results.includes(20)) {
                resultsHtml += '<div class="alert alert-success mt-2 mb-0"><small><i class="fas fa-star me-1"></i>Critique !</small></div>';
            } else if (results.includes(1)) {
                resultsHtml += '<div class="alert alert-danger mt-2 mb-0"><small><i class="fas fa-times me-1"></i>Échec critique !</small></div>';
            }
        } else if (results.includes(selectedDiceSides)) {
            // Critique sur les autres dés (mais pas d'échec critique)
            resultsHtml += '<div class="alert alert-success mt-2 mb-0"><small><i class="fas fa-star me-1"></i>Critique !</small></div>';
        }
        
        resultsHtml += '</div>';
        resultsDiv.innerHTML = resultsHtml;
        
        // Ajouter un effet sonore visuel (optionnel)
        resultsDiv.style.animation = 'pulse 0.5s ease-in-out';
        setTimeout(() => {
            resultsDiv.style.animation = '';
        }, 500);
        
        // Sauvegarder le jet de dés
        saveDiceRoll(results, total, maxResult, minResult);
    }

    // Mettre à jour l'affichage quand la quantité change
    document.getElementById('dice-quantity').addEventListener('change', function() {
        if (selectedDiceSides) {
            updateDiceSelectionDisplay();
        }
    });

    // Fonction pour charger l'historique des jets de dés
    function loadDiceHistory() {
        // Le MJ voit tous les jets (y compris les masqués), les joueurs ne voient que les jets visibles
        const showHidden = <?php echo $isOwnerDM ? 'true' : 'false'; ?>;
        const url = `get_dice_rolls_history.php?campaign_id=${currentCampaignId}&show_hidden=${showHidden}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayDiceHistory(data.rolls);
                } else {
                    console.error('Erreur lors du chargement de l\'historique:', data.error);
                    document.getElementById('dice-history').innerHTML = `
                        <div class="text-muted text-center py-3">
                            <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                            <p class="mb-0 small">Erreur lors du chargement de l'historique</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement de l\'historique:', error);
                document.getElementById('dice-history').innerHTML = `
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-lg mb-2"></i>
                        <p class="mb-0 small">Erreur de connexion</p>
                    </div>
                `;
            });
    }

    // Fonction pour afficher l'historique des jets
    function displayDiceHistory(rolls) {
        const historyDiv = document.getElementById('dice-history');
        
        if (rolls.length === 0) {
            historyDiv.innerHTML = `
                <div class="text-muted text-center py-3">
                    <i class="fas fa-dice fa-lg mb-2"></i>
                    <p class="mb-0 small">Aucun jet de dés enregistré</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        rolls.forEach(roll => {
            const rollDate = new Date(roll.rolled_at);
            const timeStr = rollDate.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Déterminer les classes CSS pour les résultats
            let resultBadges = '';
            roll.results.forEach(result => {
                let badgeClass = 'bg-secondary';
                if (roll.has_crit && result === roll.dice_sides) {
                    badgeClass = 'bg-success';
                } else if (roll.has_fumble && result === 1 && roll.dice_sides === 20) {
                    badgeClass = 'bg-danger';
                } else if (result === roll.dice_sides) {
                    badgeClass = 'bg-primary';
                }
                resultBadges += `<span class="badge ${badgeClass} me-1">${result}</span>`;
            });
            
            // Vérifier si l'utilisateur est le MJ (seul le MJ peut supprimer et modifier la visibilité)
            const isDM = <?php echo $isOwnerDM ? 'true' : 'false'; ?>;
            const deleteButton = isDM ? `
                <button class="btn btn-sm btn-outline-danger ms-2" 
                        onclick="deleteDiceRoll(${roll.id})" 
                        title="Supprimer ce jet">
                    <i class="fas fa-trash"></i>
                </button>
            ` : '';
            
            // Bouton pour basculer la visibilité (visible uniquement pour le MJ)
            const toggleVisibilityButton = isDM ? `
                <button class="btn btn-sm ${roll.is_hidden ? 'btn-outline-warning' : 'btn-outline-info'} ms-2" 
                        onclick="toggleDiceRollVisibility(${roll.id})" 
                        title="${roll.is_hidden ? 'Rendre visible pour les joueurs' : 'Masquer pour les joueurs'}">
                    <i class="fas ${roll.is_hidden ? 'fa-eye' : 'fa-eye-slash'}"></i>
                </button>
            ` : '';
            
            // Indicateur pour les jets masqués (visible uniquement pour le MJ)
            const hiddenIndicator = (isDM && roll.is_hidden) ? `
                <span class="badge bg-warning text-dark ms-1" title="Jet masqué pour les joueurs">
                    <i class="fas fa-eye-slash"></i>
                </span>
            ` : '';
            
            html += `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <strong class="me-2">${roll.username}</strong>
                            <span class="badge bg-outline-primary me-2">${roll.dice_type}</span>
                            <small class="text-muted">${roll.quantity} ${roll.dice_type}${roll.quantity > 1 ? 's' : ''}</small>
                            ${hiddenIndicator}
                        </div>
                        <div class="mt-1">
                            ${resultBadges}
                            <span class="ms-2 text-primary"><strong>Total: ${roll.total}</strong></span>
                        </div>
                    </div>
                        <div class="d-flex align-items-center">
                            <div class="text-end me-2">
                                <small class="text-muted">${timeStr}</small>
                                ${roll.has_crit ? '<i class="fas fa-star text-success ms-1" title="Critique"></i>' : ''}
                                ${roll.has_fumble ? '<i class="fas fa-times text-danger ms-1" title="Échec critique"></i>' : ''}
                            </div>
                            ${toggleVisibilityButton}
                            ${deleteButton}
                        </div>
                </div>
            `;
        });
        
        historyDiv.innerHTML = html;
    }

    // Fonction pour sauvegarder un jet de dés
    function saveDiceRoll(results, total, maxResult, minResult) {
        const diceType = `D${selectedDiceSides}`;
        const quantity = parseInt(document.getElementById('dice-quantity').value);
        const isHidden = document.getElementById('hide-dice-roll').checked;
        
        const rollData = {
            campaign_id: currentCampaignId,
            dice_type: diceType,
            dice_sides: selectedDiceSides,
            quantity: quantity,
            results: results,
            total: total,
            max_result: maxResult,
            min_result: minResult,
            is_hidden: isHidden
        };
        
        fetch('save_dice_roll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(rollData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger l'historique après avoir sauvegardé
                loadDiceHistory();
            } else {
                console.error('Erreur lors de la sauvegarde du jet:', data.error);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la sauvegarde du jet:', error);
        });
    }

    // Fonction pour basculer la visibilité d'un jet de dés
    function toggleDiceRollVisibility(rollId) {
        fetch('toggle_dice_roll_hidden.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ roll_id: rollId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger l'historique après modification
                loadDiceHistory();
                
                // Afficher un message de succès
                const historyDiv = document.getElementById('dice-history');
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-info alert-dismissible fade show';
                successMessage.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                historyDiv.insertBefore(successMessage, historyDiv.firstChild);
                
                // Supprimer le message après 3 secondes
                setTimeout(() => {
                    if (successMessage.parentNode) {
                        successMessage.remove();
                    }
                }, 3000);
            } else {
                alert('Erreur lors de la modification : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur lors de la modification du jet:', error);
            alert('Erreur de connexion lors de la modification');
        });
    }

    // Fonction pour supprimer un jet de dés
    function deleteDiceRoll(rollId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce jet de dés ? Cette action est irréversible.')) {
            return;
        }
        
        fetch('delete_dice_roll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ roll_id: rollId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger l'historique après suppression
                loadDiceHistory();
                
                // Afficher un message de succès
                const historyDiv = document.getElementById('dice-history');
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-success alert-dismissible fade show';
                successMessage.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                historyDiv.insertBefore(successMessage, historyDiv.firstChild);
                
                // Supprimer le message après 3 secondes
                setTimeout(() => {
                    if (successMessage.parentNode) {
                        successMessage.remove();
                    }
                }, 3000);
            } else {
                alert('Erreur lors de la suppression : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du jet:', error);
            alert('Erreur de connexion lors de la suppression');
        });
    }

    // Arrêter la mise à jour automatique quand la page se ferme
    window.addEventListener('beforeunload', function() {
        if (diceHistoryInterval) {
            clearInterval(diceHistoryInterval);
        }
    });
    
    // Gestion de la sélection pays/région dans le formulaire d'édition
    const editSceneCountry = document.getElementById('editSceneCountry');
    if (editSceneCountry) {
        editSceneCountry.addEventListener('change', function() {
        var countryId = this.value;
        var regionSelect = document.getElementById('editSceneRegion');
        
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
    }
    </script>

<!-- Modal pour éditer le lieu -->
<?php if ($canEdit): ?>
<div class="modal fade" id="editSceneModal" tabindex="-1" aria-labelledby="editSceneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSceneModalLabel">
                    <i class="fas fa-edit me-2"></i>Éditer le lieu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_scene">
                    
                    <div class="mb-3">
                        <label for="editSceneTitle" class="form-label">Titre du lieu *</label>
                        <input type="text" class="form-control" id="editSceneTitle" name="scene_title" 
                               value="<?php echo htmlspecialchars($place['title']); ?>" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSceneCountry" class="form-label">Pays (optionnel)</label>
                        <select class="form-control" id="editSceneCountry" name="country_id">
                            <option value="">-- Sélectionner un pays --</option>
                            <?php
                            $countries = getCountries();
                            foreach ($countries as $country):
                            ?>
                                <option value="<?php echo $country['id']; ?>" <?php echo ($place['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSceneRegion" class="form-label">Région (optionnel)</label>
                        <select class="form-control" id="editSceneRegion" name="region_id">
                            <option value="">-- Sélectionner une région --</option>
                            <?php
                            if ($place['country_id']) {
                                $regions = getRegionsByCountry($place['country_id']);
                                foreach ($regions as $region):
                                ?>
                                    <option value="<?php echo $region['id']; ?>" <?php echo ($place['region_id'] == $region['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($region['name']); ?>
                                    </option>
                                <?php endforeach;
                            } ?>
                        </select>
                        <div class="form-text">Sélectionnez d'abord un pays pour voir ses régions</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSceneNotes" class="form-label">Notes du MJ</label>
                        <textarea class="form-control" id="editSceneNotes" name="scene_notes" 
                                  rows="6" placeholder="Notes privées du MJ..."><?php echo htmlspecialchars($place['notes'] ?? ''); ?></textarea>
                        <div class="form-text">Ces notes ne sont visibles que par le MJ et les administrateurs.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour ajouter un objet -->
<?php if ($isOwnerDM): ?>
<div class="modal fade" id="addObjectModal" tabindex="-1" aria-labelledby="addObjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addObjectModalLabel">
                    <i class="fas fa-box me-2"></i>Ajouter un objet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_object">
                    
                    <div class="mb-3">
                        <label for="objectName" class="form-label">Nom de l'objet *</label>
                        <input type="text" class="form-control" id="objectName" name="object_name" required placeholder="Ex: Fiole de poison, Lettre secrète, Pièces d'or...">
                    </div>
                    
                    <div class="mb-3">
                        <label for="objectType" class="form-label">Type d'objet</label>
                        <select class="form-select" id="objectType" name="object_type">
                            <option value="other">Autre</option>
                            <option value="poison">Poison</option>
                            <option value="coins">Pièces</option>
                            <option value="letter">Lettre</option>
                            <option value="weapon">Arme</option>
                            <option value="armor">Armure</option>
                            <option value="magical_item">Objet magique</option>
                        </select>
                    </div>
                    
                    <!-- Sélection dynamique selon le type -->
                    <div class="mb-3" id="itemSelection" style="display: none;">
                        <label for="selectedItem" class="form-label" id="itemSelectionLabel">Sélectionner un objet</label>
                        <select class="form-select" id="selectedItem" name="selected_item">
                            <option value="">Choisir...</option>
                        </select>
                    </div>
                    
                    <!-- Contenu de la lettre -->
                    <div class="mb-3" id="letterContent" style="display: none;">
                        <label for="letterContentText" class="form-label">Contenu de la lettre</label>
                        <textarea class="form-control" id="letterContentText" name="letter_content" rows="4" placeholder="Contenu de la lettre..."></textarea>
                        
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="isSealed" name="is_sealed">
                            <label class="form-check-label" for="isSealed">
                                Lettre cachetée
                            </label>
                        </div>
                    </div>
                    
                    <!-- Quantités de pièces -->
                    <div class="mb-3" id="coinsContent" style="display: none;">
                        <label class="form-label">Quantités de pièces</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="goldCoins" class="form-label">Pièces d'or</label>
                                <input type="number" class="form-control" id="goldCoins" name="gold_coins" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label for="silverCoins" class="form-label">Pièces d'argent</label>
                                <input type="number" class="form-control" id="silverCoins" name="silver_coins" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label for="copperCoins" class="form-label">Pièces de cuivre</label>
                                <input type="number" class="form-control" id="copperCoins" name="copper_coins" min="0" value="0">
                            </div>
                        </div>
                        <div class="form-text">Indiquez les quantités de chaque type de pièces.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="objectDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="objectDescription" name="object_description" rows="3" placeholder="Description détaillée de l'objet..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isVisible" name="is_visible" checked>
                            <label class="form-check-label" for="isVisible">
                                Visible des joueurs (pion doré sur la carte)
                            </label>
                        </div>
                        <div class="form-text">Si coché, l'objet apparaîtra comme un pion doré sur la carte pour les joueurs.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="isIdentified" name="is_identified">
                            <label class="form-check-label" for="isIdentified">
                                Identifié par les joueurs
                            </label>
                        </div>
                        <div class="form-text">Si coché, les joueurs connaîtront la vraie nature de l'objet. Sinon, ils ne verront qu'une description générale.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Ajouter l'objet
                    </button>
                </div>
            </form>
        </div>
    </div>
         </div>
         
         <!-- Note pour le MJ -->
         <?php if ($isOwnerDM): ?>
         <div class="alert alert-info mt-4">
             <i class="fas fa-info-circle me-2"></i>
             <strong>Note :</strong> Les objets attribués sont automatiquement ajoutés à l'inventaire du propriétaire et peuvent être consultés dans leur fiche respective.
         </div>
         <?php endif; ?>
         
         <!-- Modal pour attribuer un objet -->
<div class="modal fade" id="assignObjectModal" tabindex="-1" aria-labelledby="assignObjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignObjectModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Attribuer un objet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_object">
                    <input type="hidden" name="object_id" id="assignObjectId">

                    <div class="mb-3">
                        <label class="form-label">Objet à attribuer</label>
                        <div class="form-control-plaintext" id="assignObjectName"></div>
                    </div>

                    <div class="mb-3">
                        <label for="ownerType" class="form-label">Attribuer à</label>
                        <select class="form-select" id="ownerType" name="owner_type" onchange="updateOwnerOptions()">
                            <option value="none">Personne (objet libre)</option>
                            <option value="player">Joueur</option>
                            <option value="npc">PNJ</option>
                            <option value="monster">Monstre</option>
                        </select>
                    </div>

                    <div class="mb-3" id="ownerSelection" style="display: none;">
                        <label for="ownerId" class="form-label" id="ownerSelectionLabel">Sélectionner</label>
                        <select class="form-select" id="ownerId" name="owner_id">
                            <option value="">Choisir...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i>Attribuer l'objet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
<?php endif; ?>
