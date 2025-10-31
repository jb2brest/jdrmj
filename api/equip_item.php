<?php
/**
 * API endpoint universelle pour équiper un objet
 * Supporte les personnages, PNJ et monstres
 */

header('Content-Type: application/json');
require_once '../classes/init.php';
require_once '../includes/functions.php';

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérifier l'authentification
requireLogin();

// Récupérer les données
$input = json_decode(file_get_contents('php://input'), true);
$itemId = (int)($input['item_id'] ?? 0);

// Validation
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID d\'objet invalide']);
    exit();
}

try {
    // Récupérer les informations de l'item
    $pdo = \Database::getInstance()->getPdo();
    $stmt = $pdo->prepare("
        SELECT owner_type, owner_id, display_name, object_type 
        FROM items 
        WHERE id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objet non trouvé']);
        exit();
    }
    
    $ownerType = $item['owner_type'];
    $ownerId = $item['owner_id'];
    
    // Vérifier les permissions selon le type de propriétaire
    $hasPermission = false;
    
    if ($ownerType === 'player') {
        // Pour les personnages
        $character = Character::findById($ownerId);
        if ($character) {
            $isOwner = $character->belongsToUser($_SESSION['user_id']);
            $isDM = isDM();
            $isAdmin = User::isAdmin();
            $hasPermission = $isOwner || $isDM || $isAdmin;
        }
    } elseif ($ownerType === 'npc') {
        // Pour les PNJ
        $npc = NPC::findById($ownerId);
        if ($npc) {
            $isOwner = ($npc->created_by == $_SESSION['user_id']);
            $isDM = isDM();
            $isAdmin = User::isAdmin();
            $hasPermission = $isOwner || $isDM || $isAdmin;
        }
    } elseif ($ownerType === 'monster') {
        // Pour les monstres
        $isDM = isDM();
        $isAdmin = User::isAdmin();
        $hasPermission = $isDM || $isAdmin;
    }
    
    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
        exit();
    }
    
    // Déterminer le slot automatiquement selon le type d'objet
    require_once '../classes/SlotManager.php';
    $slot = SlotManager::getSlotForObjectType($item['object_type'], $item['display_name']);
    
    if (!$slot) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type d\'objet non supporté pour l\'équipement']);
        exit();
    }
    
    // Vérifier la compatibilité du slot avec le type d'objet
    if (!SlotManager::isSlotCompatible($slot, $item['object_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Slot non compatible avec ce type d\'objet']);
        exit();
    }
    
    // Vérifier et libérer les slots nécessaires
    $slotsToFree = [];
    
    // Gestion spéciale pour les boucliers
    if ($item['object_type'] === 'shield') {
        // Un bouclier s'équipe toujours en main secondaire
        $slot = 'main_secondaire';
        $slotsToFree = ['main_secondaire'];
    } elseif ($slot === 'deux_mains') {
        // Pour les armes à deux mains, libérer les deux mains
        $slotsToFree = ['main_principale', 'main_secondaire'];
    } elseif ($slot === 'main_principale') {
        // Pour les armes en main principale, vérifier s'il y a déjà une arme à une main
        $stmt = $pdo->prepare("
            SELECT id, display_name, object_type, equipped_slot 
            FROM items 
            WHERE owner_type = ? AND owner_id = ? AND equipped_slot = 'main_principale' AND is_equipped = 1
        ");
        $stmt->execute([$ownerType, $ownerId]);
        $existingMainHand = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingMainHand) {
            // Vérifier si l'arme existante est à une main
            require_once '../classes/SlotManager.php';
            $existingSlot = SlotManager::getSlotForObjectType($existingMainHand['object_type'], $existingMainHand['display_name']);
            
            if ($existingSlot === 'main_principale') {
                // L'arme existante est à une main, déplacer la nouvelle arme en main secondaire
                $slot = 'main_secondaire';
                $slotsToFree = ['main_secondaire'];
            } else {
                // L'arme existante est à deux mains, la déséquiper
                $slotsToFree = ['main_principale'];
            }
        } else {
            // Pas d'arme en main principale, libérer seulement la main principale
            $slotsToFree = ['main_principale'];
        }
    } elseif ($slot === 'main_secondaire') {
        // Pour les armes en main secondaire, vérifier la compatibilité avec la main principale
        $stmt = $pdo->prepare("
            SELECT id, display_name, object_type, equipped_slot 
            FROM items 
            WHERE owner_type = ? AND owner_id = ? AND equipped_slot = 'main_principale' AND is_equipped = 1
        ");
        $stmt->execute([$ownerType, $ownerId]);
        $mainHandWeapon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mainHandWeapon) {
            // Vérifier si l'arme en main principale est compatible avec une arme en main secondaire
            require_once '../classes/SlotManager.php';
            $mainHandSlot = SlotManager::getSlotForObjectType($mainHandWeapon['object_type'], $mainHandWeapon['display_name']);
            
            if ($mainHandSlot === 'deux_mains') {
                // L'arme en main principale est à deux mains, la déséquiper
                $slotsToFree = ['main_principale'];
            } else {
                // L'arme en main principale est compatible, libérer seulement la main secondaire
                $slotsToFree = ['main_secondaire'];
            }
        } else {
            // Pas d'arme en main principale, libérer seulement la main secondaire
            $slotsToFree = ['main_secondaire'];
        }
    } else {
        // Pour les autres objets, libérer seulement le slot cible
        $slotsToFree = [$slot];
    }
    
    // Libérer les objets dans les slots nécessaires
    $freedItems = [];
    foreach ($slotsToFree as $slotToFree) {
        $stmt = $pdo->prepare("
            SELECT id, display_name, object_type, equipped_slot 
            FROM items 
            WHERE owner_type = ? AND owner_id = ? AND equipped_slot = ? AND is_equipped = 1
        ");
        $stmt->execute([$ownerType, $ownerId, $slotToFree]);
        $occupiedItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($occupiedItem) {
            // Déséquiper l'objet qui occupe ce slot
            if ($ownerType === 'player') {
                // Pour les personnages, utiliser la méthode unequipItem si possible
                $character = Character::findById($ownerId);
                if ($character) {
                    $character->unequipItem($occupiedItem['display_name']);
                }
            } else {
                // Pour les PNJ et monstres, utiliser SQL direct
                $stmt = $pdo->prepare("
                    UPDATE items 
                    SET is_equipped = 0, equipped_slot = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$occupiedItem['id']]);
            }
            $freedItems[] = $occupiedItem;
        }
    }
    
    // Équiper le nouvel objet
    if ($ownerType === 'player') {
        // Pour les personnages, utiliser la méthode d'instance equipItem()
        $character = Character::findById($ownerId);
        if ($character) {
            $result = $character->equipItem($item['display_name'], $item['object_type'], $slot);
        } else {
            $result = false;
        }
    } else {
        // Pour les PNJ et monstres, utiliser SQL direct
        $stmt = $pdo->prepare("
            UPDATE items 
            SET is_equipped = 1, equipped_slot = ? 
            WHERE id = ?
        ");
        $result = $stmt->execute([$slot, $itemId]);
    }
    
    if ($result) {
        $slotName = SlotManager::getSlotDisplayName($slot);
        $message = "Objet équipé avec succès dans le slot: $slotName";
        
        // Ajouter des informations sur les objets déséquipés
        if (!empty($freedItems)) {
            $freedNames = array_map(function($item) {
                return $item['display_name'];
            }, $freedItems);
            $message .= ". Objets déséquipés: " . implode(', ', $freedNames);
        }
        
        $result = ['success' => true, 'message' => $message];
    } else {
        $result = ['success' => false, 'message' => 'Erreur lors de l\'équipement'];
    }
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
