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
    $userId = $_SESSION['user_id'] ?? null;
    
    // Vérifier les permissions selon le type de propriétaire
    $hasPermission = false;
    
    // D'abord, vérifier si l'owner_id correspond à un NPC (même si owner_type = 'player')
    // Car certains items peuvent être mal enregistrés avec owner_type='player' alors qu'ils appartiennent à un NPC
    $npc = NPC::findById($ownerId);
    $character = null;
    
    // Si ce n'est pas un NPC, essayer de trouver un Character
    if (!$npc) {
        $character = Character::findById($ownerId);
    }

    if ($npc) {
        // C'est un NPC (même si owner_type dit 'player')
        $isOwner = ($npc->created_by == $userId);
        $hasPermission = $isOwner || User::isDMOrAdmin();
    } elseif ($character) {
        // C'est un Character
        $isOwner = $character->belongsToUser($userId);
        $hasPermission = $isOwner || User::isDMOrAdmin();
    } elseif ($ownerType === 'player') {
        // Essayer de trouver le Character avec l'ancienne méthode
        $character = Character::findById($ownerId);
        if ($character) {
            $isOwner = $character->belongsToUser($userId);
            $hasPermission = $isOwner || User::isDMOrAdmin();
        }
    } elseif ($ownerType === 'npc') {
        // Essayer de trouver le NPC avec l'ancienne méthode
        $npc = NPC::findById($ownerId);
        if ($npc) {
            $isOwner = ($npc->created_by == $userId);
            $hasPermission = $isOwner || User::isDMOrAdmin();
        }
    } elseif ($ownerType === 'monster') {
        // Pour les monstres, vérifier que l'utilisateur est MJ ou Admin
        $hasPermission = User::isDMOrAdmin();
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
        echo json_encode([
            'success' => false, 
            'message' => 'Type d\'objet non supporté pour l\'équipement',
            'debug' => [
                'object_type' => $item['object_type'],
                'display_name' => $item['display_name'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId
            ]
        ]);
        exit();
    }
    
    // Vérifier la compatibilité du slot avec le type d'objet
    if (!SlotManager::isSlotCompatible($slot, $item['object_type'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Slot non compatible avec ce type d\'objet',
            'debug' => [
                'slot' => $slot,
                'object_type' => $item['object_type'],
                'display_name' => $item['display_name']
            ]
        ]);
        exit();
    }
    
    // Vérifier et libérer les slots nécessaires
    $slotsToFree = [];
    
    // D'abord, vérifier s'il y a une arme à deux mains déjà équipée (peu importe le slot cible)
    $stmt = $pdo->prepare("
        SELECT id, display_name, object_type, equipped_slot 
        FROM items 
        WHERE owner_id = ? AND equipped_slot = 'deux_mains' AND is_equipped = 1
    ");
    $stmt->execute([$ownerId]);
    $twoHandedWeapon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Gestion spéciale pour les boucliers
    if ($item['object_type'] === 'shield') {
        // Un bouclier s'équipe toujours en main secondaire
        $slot = 'main_secondaire';
        $slotsToFree = ['main_secondaire'];
        // Si une arme à deux mains est équipée, la déséquiper aussi
        if ($twoHandedWeapon) {
            $slotsToFree[] = 'deux_mains';
        }
    } elseif ($slot === 'deux_mains') {
        // Pour les armes à deux mains, libérer les deux mains ET toute arme à deux mains existante
        $slotsToFree = ['main_principale', 'main_secondaire'];
        if ($twoHandedWeapon) {
            $slotsToFree[] = 'deux_mains';
        }
    } elseif ($slot === 'main_principale') {
        // Si une arme à deux mains est équipée, la déséquiper et aussi toute arme en main_secondaire
        if ($twoHandedWeapon) {
            $slotsToFree = ['deux_mains', 'main_secondaire'];
        } else {
            // Pour les armes en main principale, vérifier s'il y a déjà une arme à une main
            $stmt = $pdo->prepare("
                SELECT id, display_name, object_type, equipped_slot 
                FROM items 
                WHERE owner_id = ? AND equipped_slot = 'main_principale' AND is_equipped = 1
            ");
            $stmt->execute([$ownerId]);
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
        }
    } elseif ($slot === 'main_secondaire') {
        // Si une arme à deux mains est équipée, la déséquiper et aussi toute arme en main_principale
        if ($twoHandedWeapon) {
            $slotsToFree = ['deux_mains', 'main_principale'];
        } else {
            // Pour les armes en main secondaire, vérifier la compatibilité avec la main principale
            $stmt = $pdo->prepare("
                SELECT id, display_name, object_type, equipped_slot 
                FROM items 
                WHERE owner_id = ? AND equipped_slot = 'main_principale' AND is_equipped = 1
            ");
            $stmt->execute([$ownerId]);
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
        }
    } else {
        // Pour les autres objets, libérer seulement le slot cible
        $slotsToFree = [$slot];
    }
    
    // Libérer les objets dans les slots nécessaires
    $freedItems = [];
    foreach ($slotsToFree as $slotToFree) {
        // Utiliser le bon owner_type pour la requête (ou chercher avec owner_id seulement)
        $stmt = $pdo->prepare("
            SELECT id, display_name, object_type, equipped_slot 
            FROM items 
            WHERE owner_id = ? AND equipped_slot = ? AND is_equipped = 1
        ");
        $stmt->execute([$ownerId, $slotToFree]);
        $occupiedItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($occupiedItem) {
            // Déséquiper l'objet qui occupe ce slot
            if ($character && !$npc) {
                // Pour les personnages seulement (pas les PNJ), utiliser la méthode unequipItem si possible
                $character->unequipItem($occupiedItem['display_name']);
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
    $equipResult = false;
    
    if ($ownerType === 'player' || $npc) {
        // Pour les personnages ou PNJ identifiés, utiliser la méthode appropriée
        if ($npc) {
            // Pour les PNJ, utiliser SQL direct (même si owner_type dit 'player')
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 1, equipped_slot = ? 
                WHERE id = ?
            ");
            $equipResult = $stmt->execute([$slot, $itemId]);
        } elseif ($character) {
            // Pour les personnages, utiliser la méthode d'instance equipItem()
            $equipResult = $character->equipItem($item['display_name'], $item['object_type'], $slot);
        }
    } else {
        // Pour les monstres et autres cas, utiliser SQL direct
        $stmt = $pdo->prepare("
            UPDATE items 
            SET is_equipped = 1, equipped_slot = ? 
            WHERE id = ?
        ");
        $equipResult = $stmt->execute([$slot, $itemId]);
    }
    
    if ($equipResult) {
        $slotName = SlotManager::getSlotDisplayName($slot);
        $message = "Objet équipé avec succès dans le slot: $slotName";
        
        // Ajouter des informations sur les objets déséquipés
        if (!empty($freedItems)) {
            $freedNames = array_map(function($item) {
                return $item['display_name'];
            }, $freedItems);
            $message .= ". Objets déséquipés: " . implode(', ', $freedNames);
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de l\'équipement',
            'debug' => [
                'item_id' => $itemId,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'slot' => $slot,
                'object_type' => $item['object_type']
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
