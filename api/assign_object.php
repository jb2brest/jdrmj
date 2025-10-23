<?php
/**
 * API pour attribuer un objet à un PJ, PNJ ou monstre
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/classes/User.php';
require_once dirname(__DIR__) . '/classes/Item.php';

header('Content-Type: application/json');

try {
    // Vérifier les permissions
    if (!isLoggedIn()) {
        throw new Exception('Non authentifié');
    }
    
    if (!User::isDMOrAdmin()) {
        throw new Exception('Permissions insuffisantes');
    }
    
    $objectId = (int)($_POST['object_id'] ?? 0);
    $targetType = sanitizeInput($_POST['target_type'] ?? '');
    $targetId = (int)($_POST['target_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (!$objectId || !$targetType || !$targetId || $quantity < 1) {
        throw new Exception('Données manquantes ou invalides');
    }
    
    // Vérifier que l'objet existe et appartient au lieu (non attribué)
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT * FROM items 
        WHERE id = ? AND (owner_type = 'place' OR owner_type IS NULL)
    ");
    $stmt->execute([$objectId]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$object) {
        throw new Exception('Objet non trouvé ou déjà attribué');
    }
    
    $placeId = $object['place_id'];
    
    // Vérifier que la cible existe
    $targetExists = false;
    $targetName = '';
    
    switch ($targetType) {
        case 'player':
            $stmt = $pdo->prepare("SELECT name FROM characters WHERE id = ?");
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($target) {
                $targetExists = true;
                $targetName = $target['name'];
            }
            break;
            
        case 'npc':
            $stmt = $pdo->prepare("
                SELECT pn.name 
                FROM place_npcs pn 
                WHERE pn.id = ? AND pn.place_id = ?
            ");
            $stmt->execute([$targetId, $placeId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($target) {
                $targetExists = true;
                $targetName = $target['name'];
            }
            break;
            
        case 'monster':
            $stmt = $pdo->prepare("
                SELECT m.name 
                FROM place_monsters pm 
                JOIN monsters m ON pm.monster_id = m.id 
                WHERE pm.id = ? AND pm.place_id = ?
            ");
            $stmt->execute([$targetId, $placeId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($target) {
                $targetExists = true;
                $targetName = $target['name'];
            }
            break;
            
        default:
            throw new Exception('Type de cible invalide');
    }
    
    if (!$targetExists) {
        throw new Exception('Cible non trouvée');
    }
    
    // Gérer la quantité et l'attribution
    if ($quantity > 1) {
        // Si quantité > 1, créer plusieurs copies dans l'inventaire de la cible
        for ($i = 0; $i < $quantity; $i++) {
            $newObjectData = $object;
            unset($newObjectData['id']);
            unset($newObjectData['created_at']);
            unset($newObjectData['updated_at']);
            
            // L'objet est maintenant dans l'inventaire de la cible
            $newObjectData['owner_type'] = $targetType;
            $newObjectData['owner_id'] = $targetId;
            // L'objet n'est plus dans un lieu spécifique
            $newObjectData['place_id'] = null;
            // L'objet n'est plus visible sur la carte du lieu
            $newObjectData['is_visible'] = 0;
            $newObjectData['is_on_map'] = 0;
            $newObjectData['position_x'] = 0;
            $newObjectData['position_y'] = 0;
            // L'objet n'est pas équipé par défaut
            $newObjectData['is_equipped'] = 0;
            $newObjectData['equipped_slot'] = null;
            
            $stmt = $pdo->prepare("
                INSERT INTO items (place_id, display_name, object_type, type_precis, description, 
                                 is_identified, is_visible, is_equipped, position_x, position_y, 
                                 is_on_map, owner_type, owner_id, poison_id, weapon_id, armor_id, 
                                 gold_coins, silver_coins, copper_coins, letter_content, is_sealed, 
                                 magical_item_id, item_source, quantity, equipped_slot, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $newObjectData['place_id'],
                $newObjectData['display_name'],
                $newObjectData['object_type'],
                $newObjectData['type_precis'],
                $newObjectData['description'],
                $newObjectData['is_identified'],
                $newObjectData['is_visible'],
                $newObjectData['is_equipped'],
                $newObjectData['position_x'],
                $newObjectData['position_y'],
                $newObjectData['is_on_map'],
                $newObjectData['owner_type'],
                $newObjectData['owner_id'],
                $newObjectData['poison_id'],
                $newObjectData['weapon_id'],
                $newObjectData['armor_id'],
                $newObjectData['gold_coins'],
                $newObjectData['silver_coins'],
                $newObjectData['copper_coins'],
                $newObjectData['letter_content'],
                $newObjectData['is_sealed'],
                $newObjectData['magical_item_id'],
                $newObjectData['item_source'],
                $newObjectData['quantity'],
                $newObjectData['equipped_slot'],
                $newObjectData['notes']
            ]);
        }
        
        // Supprimer l'objet original du lieu
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$objectId]);
        
    } else {
        // Si quantité = 1, transférer l'objet vers l'inventaire de la cible
        $stmt = $pdo->prepare("
            UPDATE items 
            SET owner_type = ?, 
                owner_id = ?, 
                place_id = NULL,
                is_visible = 0, 
                is_on_map = 0, 
                position_x = 0, 
                position_y = 0,
                is_equipped = 0,
                equipped_slot = NULL
            WHERE id = ?
        ");
        $stmt->execute([$targetType, $targetId, $objectId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Objet attribué à $targetName avec succès"
    ]);
    
} catch (Exception $e) {
    error_log("Erreur assign_object.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
