<?php
/**
 * Fonctions simplifiées pour la création d'équipement de personnage
 * Évite la complexité de starting_equipment et traite directement les choix du joueur
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Crée l'équipement de départ d'un personnage de manière simplifiée
 * @param int $characterId ID du personnage
 * @param array $equipmentChoices Choix d'équipement du joueur
 * @param int $backgroundId ID de l'historique
 * @return bool Succès de l'opération
 */
function createSimpleStartingEquipment($characterId, $equipmentChoices, $backgroundId) {
    $pdo = getPDO();
    
    error_log("DEBUG createSimpleStartingEquipment: Character ID $characterId, Background ID $backgroundId");
    error_log("DEBUG Equipment choices: " . json_encode($equipmentChoices));
    
    try {
        $pdo->beginTransaction();
        
        $allEquipment = []; // Pour éviter les doublons
        
        // 1. Équipement de classe (traité directement)
        if (isset($equipmentChoices['class'])) {
            error_log("DEBUG Processing class equipment: " . json_encode($equipmentChoices['class']));
            foreach ($equipmentChoices['class'] as $choiceIndex => $selectedOption) {
                $equipment = getClassEquipmentByChoice($choiceIndex, $selectedOption);
                error_log("DEBUG Choice $choiceIndex, Option $selectedOption: " . count($equipment) . " items");
                foreach ($equipment as $item) {
                    $allEquipment[] = $item;
                }
            }
        }
        
        // 2. Armes sélectionnées par le joueur (éviter les doublons)
        if (isset($equipmentChoices['selected_weapons'])) {
            error_log("DEBUG Processing selected weapons: " . json_encode($equipmentChoices['selected_weapons']));
            foreach ($equipmentChoices['selected_weapons'] as $weaponName) {
                if (!empty($weaponName)) {
                    // Vérifier si cette arme n'est pas déjà dans l'équipement de classe
                    $alreadyExists = false;
                    foreach ($allEquipment as $existingItem) {
                        if (isset($existingItem['weapon_id']) && $existingItem['name'] === $weaponName) {
                            $alreadyExists = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyExists) {
                        $weaponId = getWeaponIdByName($weaponName);
                        if ($weaponId) {
                            $allEquipment[] = [
                                'name' => $weaponName,
                                'type' => 'weapon',
                                'weapon_id' => $weaponId,
                                'quantity' => 1
                            ];
                        }
                    } else {
                        error_log("DEBUG Weapon '$weaponName' already exists in class equipment, skipping");
                    }
                }
            }
        }
        
        // 3. Équipement d'historique (éviter les doublons)
        $backgroundEquipment = getBackgroundEquipment($backgroundId);
        error_log("DEBUG Background equipment: " . count($backgroundEquipment) . " items");
        foreach ($backgroundEquipment as $item) {
            // Vérifier si cet objet n'est pas déjà dans l'équipement
            $alreadyExists = false;
            foreach ($allEquipment as $existingItem) {
                if ($existingItem['name'] === $item['name'] && $existingItem['type'] === $item['type']) {
                    $alreadyExists = true;
                    break;
                }
            }
            
            if (!$alreadyExists) {
                $allEquipment[] = $item;
            } else {
                error_log("DEBUG Background item '{$item['name']}' already exists, skipping");
            }
        }
        
        // 4. Insérer tous les objets dans la base de données
        foreach ($allEquipment as $item) {
            insertEquipmentItem($characterId, $item);
        }
        
        $pdo->commit();
        error_log("DEBUG createSimpleStartingEquipment: SUCCESS - " . count($allEquipment) . " unique items inserted");
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur createSimpleStartingEquipment: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'équipement de classe selon le choix du joueur
 */
function getClassEquipmentByChoice($choiceIndex, $selectedOption) {
    $equipment = [];
    
    error_log("DEBUG getClassEquipmentByChoice: Index=$choiceIndex, Option=$selectedOption");
    
    // Définir l'équipement de classe Guerrier selon les VRAIS choix de la base de données
    if ($choiceIndex == 0) { // Premier choix d'équipement
        if ($selectedOption == 'a') {
            $equipment[] = [
                'name' => 'Cotte de mailles',
                'type' => 'armor',
                'armor_id' => 10,
                'quantity' => 1
            ];
        } elseif ($selectedOption == 'b') {
            $equipment[] = [
                'name' => 'Armure de cuir',
                'type' => 'armor',
                'armor_id' => 2,
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Arc long',
                'type' => 'weapon',
                'weapon_id' => 35,
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Fléchette',
                'type' => 'outil',
                'quantity' => 20
            ];
        }
    } elseif ($choiceIndex == 1) { // Deuxième choix d'équipement
        if ($selectedOption == 'a') {
            $equipment[] = [
                'name' => 'Bouclier',
                'type' => 'shield',
                'shield_id' => 13,
                'quantity' => 1
            ];
        } elseif ($selectedOption == 'e') {
            // Option e = Arme de guerre de corps à corps + Bouclier
            // L'arme sera ajoutée via selected_weapons, ici on ajoute juste le bouclier
            $equipment[] = [
                'name' => 'Bouclier',
                'type' => 'shield',
                'shield_id' => 13,
                'quantity' => 1
            ];
        }
    } elseif ($choiceIndex == 2) { // Troisième choix d'équipement
        if ($selectedOption == 'a') {
            $equipment[] = [
                'name' => 'Arbalète légère',
                'type' => 'weapon',
                'weapon_id' => 11,
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Carreaux',
                'type' => 'outil',
                'quantity' => 20
            ];
        } elseif ($selectedOption == 'b') {
            $equipment[] = [
                'name' => 'Hachette',
                'type' => 'weapon',
                'weapon_id' => 4,
                'quantity' => 2
            ];
        }
    } elseif ($choiceIndex == 3) { // Quatrième choix d'équipement
        if ($selectedOption == 'a') {
            $equipment[] = [
                'name' => 'Sac à dos',
                'type' => 'outil',
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Sac de couchage',
                'type' => 'outil',
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Gamelle',
                'type' => 'outil',
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Boite d\'allume-feu',
                'type' => 'outil',
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Torche',
                'type' => 'outil',
                'quantity' => 10
            ];
            $equipment[] = [
                'name' => 'Rations de voyage',
                'type' => 'outil',
                'quantity' => 10
            ];
            $equipment[] = [
                'name' => 'Gourde d\'eau',
                'type' => 'outil',
                'quantity' => 1
            ];
            $equipment[] = [
                'name' => 'Corde de chanvre',
                'type' => 'outil',
                'quantity' => 1
            ];
        }
    }
    
    error_log("DEBUG getClassEquipmentByChoice: Generated " . count($equipment) . " items");
    return $equipment;
}

/**
 * Récupère l'équipement d'historique
 */
function getBackgroundEquipment($backgroundId) {
    $equipment = [];
    
    error_log("DEBUG getBackgroundEquipment: Background ID $backgroundId");
    
    // Équipement d'historique Acolyte (ID 1)
    if ($backgroundId == 1) {
        $equipment[] = [
            'name' => 'Symbole sacré de sacerdoce',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Livre de prières',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Bâtons d\'encens',
            'type' => 'outil',
            'quantity' => 5
        ];
        $equipment[] = [
            'name' => 'Habits de cérémonie',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Vêtements communs',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Insigne de grade',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Trophée pris sur un ennemi mort',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Jeu de dés en os',
            'type' => 'outil',
            'quantity' => 1
        ];
    }
    // Équipement d'historique Soldat (ID 13)
    elseif ($backgroundId == 13) {
        $equipment[] = [
            'name' => 'Insigne de grade',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Trophée pris sur un ennemi mort',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Jeu de dés en os',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Vêtements communs',
            'type' => 'outil',
            'quantity' => 1
        ];
        // Note: Sac à dos, Sac de couchage, Gamelle, Rations de voyage 
        // sont déjà dans l'équipement de classe (choix 3), donc pas de doublons
    }
    // Historique par défaut (si ID non reconnu)
    else {
        $equipment[] = [
            'name' => 'Vêtements communs',
            'type' => 'outil',
            'quantity' => 1
        ];
        $equipment[] = [
            'name' => 'Jeu de dés en os',
            'type' => 'outil',
            'quantity' => 1
        ];
    }
    
    error_log("DEBUG getBackgroundEquipment: Generated " . count($equipment) . " items");
    return $equipment;
}

/**
 * Récupère l'ID d'une arme par son nom
 */
function getWeaponIdByName($weaponName) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
    $stmt->execute([$weaponName]);
    return $stmt->fetchColumn();
}

/**
 * Insère un objet d'équipement dans la table items
 */
function insertEquipmentItem($characterId, $item) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("
        INSERT INTO items 
        (place_id, display_name, object_type, type_precis, description, 
         is_identified, is_visible, is_equipped, position_x, position_y, 
         is_on_map, owner_type, owner_id, item_source, quantity, 
         equipped_slot, notes, obtained_at, obtained_from, weapon_id, armor_id, poison_id, shield_id) 
        VALUES (NULL, ?, ?, ?, NULL, 
                1, 0, 0, 0, 0, 
                0, 'player', ?, 'Équipement de départ', ?, 
                NULL, 'Équipement de départ', NOW(), 'Équipement de départ', ?, ?, NULL, ?)
    ");
    
    $weaponId = $item['weapon_id'] ?? null;
    $armorId = $item['armor_id'] ?? null;
    $shieldId = $item['shield_id'] ?? null;
    
    $stmt->execute([
        $item['name'],
        $item['type'],
        $item['type'],
        $characterId,
        $item['quantity'] ?? 1,
        $weaponId,
        $armorId,
        $shieldId
    ]);
}
?>
