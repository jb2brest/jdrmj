<?php
header('Content-Type: application/json');
require_once '../classes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $target = $_POST['target'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $source = $_POST['source'] ?? 'items';
    $npc_id = (int)($_POST['npc_id'] ?? 0);
    
    if (!$item_id || !$target || !$npc_id) {
        throw new Exception('Paramètres manquants');
    }
    
    // Récupérer l'instance NPC
    $npc = NPC::findById($npc_id);
    if (!$npc) {
        throw new Exception('NPC non trouvé');
    }
    
    // Récupérer les informations de l'objet à transférer selon la source
    $item = null;
    if ($source === 'npc_equipment') {
        // Récupérer depuis npc_equipment via la méthode d'instance
        $item = $npc->getMyNpcEquipmentWithDetails($item_id);
    } else {
        // Récupérer depuis items via la classe Item
        // Essayer d'abord avec owner_type='npc', puis avec 'player' (pour compatibilité avec les anciens items)
        $itemObj = Item::findByIdAndOwner($item_id, 'npc', $npc_id);
        if (!$itemObj) {
            $itemObj = Item::findByIdAndOwner($item_id, 'player', $npc_id);
        }
        $item = $itemObj ? $itemObj->toArray() : null;
    }
    
    if (!$item) {
        throw new Exception('Objet introuvable');
    }
    
    // Analyser la cible
    $target_parts = explode('_', $target);
    $target_type = $target_parts[0];
    $target_id = (int)$target_parts[1];
    
    $transfer_success = false;
    $target_name = '';
    
    switch ($target_type) {
        case 'character':
            // Transférer vers un autre personnage
            $target_npc = NPC::findById($target_id);
            $target_char = $target_npc ? ['name' => $target_npc->name] : null;
            
            if ($target_char) {
                // Insérer dans items du nouveau propriétaire via la classe Item
                $itemData = [
                    'place_id' => null,
                    'display_name' => $item['display_name'],
                    'object_type' => $item['object_type'],
                    'type_precis' => $item['type_precis'],
                    'description' => $item['description'],
                    'is_identified' => $item['is_identified'],
                    'is_visible' => false, // Les objets d'équipement ne sont pas visibles sur la carte
                    'is_equipped' => false, // Toujours non équipé lors du transfert
                    'position_x' => 0,
                    'position_y' => 0,
                    'is_on_map' => false,
                    'owner_type' => 'player',
                    'owner_id' => $target_id,
                    'poison_id' => $item['poison_id'] ?: null,
                    'weapon_id' => $item['weapon_id'] ?: null,
                    'armor_id' => $item['armor_id'] ?: null,
                    'gold_coins' => (int)($item['gold_coins'] ?: 0),
                    'silver_coins' => (int)($item['silver_coins'] ?: 0),
                    'copper_coins' => (int)($item['copper_coins'] ?: 0),
                    'letter_content' => $item['letter_content'],
                    'is_sealed' => $item['is_sealed'] ?: false,
                    'magical_item_id' => $item['magical_item_id'],
                    'item_source' => $item['item_source'],
                    'quantity' => (int)($item['quantity'] ?: 1),
                    'equipped_slot' => $item['equipped_slot'],
                    'notes' => $notes ?: $item['notes'],
                    'obtained_at' => $item['obtained_at'],
                    'obtained_from' => 'Transfert depuis ' . $npc->name
                ];
                
                Item::createExtended($itemData);
                
                // Supprimer de l'ancien propriétaire selon la source
                if ($source === 'npc_equipment') {
                    $npc->removeMyEquipmentFromNpc($item_id);
                } else {
                    Item::deleteById($item_id);
                }
                
                $transfer_success = true;
                $target_name = $target_char['name'];
            }
            break;
            
        case 'monster':
            // Transférer vers un monstre
            $target_monster = NPC::getNpcInfoInPlace($target_id);
            
            if ($target_monster) {
                // Insérer dans monster_equipment via la classe Monstre
                $equipmentData = [
                    'magical_item_id' => $item['magical_item_id'],
                    'item_name' => $item['display_name'],
                    'item_type' => $item['object_type'],
                    'item_description' => $item['description'],
                    'item_source' => $item['item_source'],
                    'quantity' => $item['quantity'],
                    'equipped' => false, // Toujours non équipé lors du transfert
                    'notes' => $notes ?: $item['notes'],
                    'obtained_from' => 'Transfert depuis ' . $npc->name
                ];
                
                Monstre::addMonsterEquipment($target_id, $target_monster['place_id'], $equipmentData);
                
                // Supprimer de l'ancien propriétaire selon la source
                if ($source === 'npc_equipment') {
                    $npc->removeMyEquipmentFromNpc($item_id);
                } else {
                    Item::deleteById($item_id);
                }
                
                $transfer_success = true;
                $target_name = $target_monster['name'];
            }
            break;
            
        case 'npc':
            // Transférer vers un PNJ
            $target_npc_info = NPC::getNpcInfoInPlace($target_id);
            
            if ($target_npc_info) {
                // Créer une instance NPC pour la cible
                $target_npc_instance = NPC::findById($target_id);
                
                if ($target_npc_instance) {
                    // Insérer dans npc_equipment via la méthode d'instance
                    $equipmentData = [
                        'magical_item_id' => $item['magical_item_id'],
                        'item_name' => $item['display_name'],
                        'item_type' => $item['object_type'],
                        'item_description' => $item['description'],
                        'item_source' => $item['item_source'],
                        'quantity' => $item['quantity'],
                        'equipped' => 0, // Toujours non équipé lors du transfert
                        'notes' => $notes ?: $item['notes'],
                        'obtained_from' => 'Transfert depuis ' . $npc->name
                    ];
                    
                    $target_npc_instance->addMyEquipmentToNpc($target_npc_info['place_id'], $equipmentData);
                    
                    // Supprimer de l'ancien propriétaire selon la source
                    if ($source === 'npc_equipment') {
                        $npc->removeMyEquipmentFromNpc($item_id);
                    } else {
                        Item::deleteById($item_id);
                    }
                    
                    $transfer_success = true;
                    $target_name = $target_npc_info['name'];
                }
            }
            break;
    }
    
    if ($transfer_success) {
        echo json_encode([
            'success' => true,
            'message' => "Objet '{$item['display_name']}' transféré vers {$target_name} avec succès."
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors du transfert de l\'objet.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
