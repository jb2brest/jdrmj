<?php
/**
 * Nouvelles fonctions pour gérer l'équipement de départ avec la table starting_equipment
 */

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

/**
 * Récupère l'équipement de départ pour une source donnée (classe, background, race)
 */
function getStartingEquipmentBySource($src, $srcId) {
    $pdo = getPDO();
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM starting_equipment 
            WHERE src = ? AND src_id = ? 
            ORDER BY groupe_id, option_letter, id
        ");
        $stmt->execute([$src, $srcId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur getStartingEquipmentBySource: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère l'équipement de départ d'une classe
 */
function getClassStartingEquipmentNew($classId) {
    return getStartingEquipmentBySource('class', $classId);
}

/**
 * Récupère l'équipement de départ d'un background
 */
function getBackgroundStartingEquipment($backgroundId) {
    return getStartingEquipmentBySource('background', $backgroundId);
}

/**
 * Récupère l'équipement de départ d'une race
 */
function getRaceStartingEquipment($raceId) {
    return getStartingEquipmentBySource('race', $raceId);
}

/**
 * Structure l'équipement de départ par groupes pour l'affichage
 */
function structureStartingEquipmentByGroups($equipment) {
    $groups = [];
    
    foreach ($equipment as $item) {
        $groupId = $item['groupe_id'] ?? 0;
        
        if (!isset($groups[$groupId])) {
            $groups[$groupId] = [
                'id' => $groupId,
                'type_choix' => $item['type_choix'],
                'options' => []
            ];
        }
        
        $groups[$groupId]['options'][] = $item;
    }
    
    return $groups;
}

/**
 * Structure l'équipement de départ par no_choix pour fusionner les options a et b
 */
function structureStartingEquipmentByChoices($equipment) {
    $choices = [];
    
    foreach ($equipment as $item) {
        $choiceId = $item['no_choix'] ?? 0;
        
        if (!isset($choices[$choiceId])) {
            $choices[$choiceId] = [
                'id' => $choiceId,
                'type_choix' => $item['type_choix'] ?? 'obligatoire',
                'options' => []
            ];
        }
        
        $choices[$choiceId]['options'][] = $item;
    }
    
    // Fusionner les options a et b du même no_choix
    $mergedChoices = [];
    foreach ($choices as $choiceId => $choice) {
        if ($choiceId == 0) {
            // Équipement obligatoire sans no_choix
            $mergedChoices[$choiceId] = [
                'id' => $choiceId,
                'type' => 'fixed',
                'description' => 'Équipement de base',
                'options' => $choice['options']
            ];
        } else {
            // Grouper par option_letter pour fusionner a et b
            $groupedOptions = [];
            foreach ($choice['options'] as $option) {
                $letter = $option['option_letter'] ?? 'default';
                if (!isset($groupedOptions[$letter])) {
                    $groupedOptions[$letter] = [];
                }
                $groupedOptions[$letter][] = $option;
            }
            
            // Créer les options fusionnées
            $mergedOptions = [];
            foreach ($groupedOptions as $letter => $options) {
                if (count($options) > 1) {
                    // Fusionner plusieurs items de la même option
                    $mergedOption = $options[0]; // Prendre le premier comme base
                    $mergedOption['merged_items'] = $options; // Garder tous les items
                    $mergedOptions[$letter] = $mergedOption;
                } else {
                    $mergedOptions[$letter] = $options[0];
                }
            }
            
            // Générer une description basée sur le type de choix
            $description = '';
            if ($choice['type_choix'] === 'à_choisir') {
                $description = 'Choisissez une option d\'équipement';
            } else {
                $description = 'Équipement ' . $choice['type_choix'];
            }
            
            $mergedChoices[$choiceId] = [
                'id' => $choiceId,
                'type' => 'choice',
                'description' => $description,
                'options' => $mergedOptions
            ];
        }
    }
    
    return $mergedChoices;
}

/**
 * Récupère les détails d'un équipement selon son type
 */
function getEquipmentDetails($type, $typeId) {
    $pdo = getPDO();
    
    if (!$typeId) {
        return null;
    }
    
    try {
        switch ($type) {
            case 'weapon':
                $stmt = $pdo->prepare("SELECT * FROM weapons WHERE id = ?");
                break;
            case 'armor':
                $stmt = $pdo->prepare("SELECT * FROM armor WHERE id = ?");
                break;
            case 'bouclier':
                $stmt = $pdo->prepare("SELECT * FROM armor WHERE id = ? AND type = 'Bouclier'");
                break;
            case 'outils':
            case 'sac':
            case 'nourriture':
            case 'accessoire':
            case 'instrument':
                // Pour les objets génériques, chercher d'abord dans le type spécifié, puis dans tous les types
                $stmt = $pdo->prepare("SELECT * FROM Object WHERE id = ? AND type = ?");
                $stmt->execute([$typeId, $type]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si pas trouvé dans le type spécifié, chercher dans tous les types
                if (!$result) {
                    $stmt = $pdo->prepare("SELECT * FROM Object WHERE id = ?");
                    $stmt->execute([$typeId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                return $result;
            default:
                return null;
        }
        
        $stmt->execute([$typeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur getEquipmentDetails: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les détails d'un sac d'équipement
 */
function getEquipmentPackDetails($packId) {
    $packs = [
        1 => [ // Sac d'explorateur
            'name' => 'Sac d\'explorateur',
            'description' => 'Sac d\'explorateur contenant: un sac à dos, un sac de couchage, une gamelle, une boite d\'allume-feu, 10 torches, 10 jours de rations, corde de chanvre (15m), une gourde d\'eau',
            'contents' => [
                'un sac à dos',
                'un sac de couchage', 
                'une gamelle',
                'une boite d\'allume-feu',
                '10 torches',
                '10 jours de rations',
                'corde de chanvre (15m)',
                'une gourde d\'eau'
            ]
        ],
        2 => [ // Sac d'exploration souterraine
            'name' => 'Sac d\'exploration souterraine',
            'description' => 'Sac d\'exploration souterraine contenant: un sac à dos, un pied de biche, un marteau, 10 pitons, 10 torches, une boite d\'allume-feu, 10 jours de rations, corde de chanvre (15m), une gourde d\'eau',
            'contents' => [
                'un sac à dos',
                'un pied de biche',
                'un marteau',
                '10 pitons',
                '10 torches',
                'une boite d\'allume-feu',
                '10 jours de rations',
                'corde de chanvre (15m)',
                'une gourde d\'eau'
            ]
        ]
    ];
    
    return $packs[$packId] ?? null;
}

/**
 * Génère l'équipement final basé sur les choix du joueur (nouvelle version corrigée)
 */
function generateFinalEquipmentNew($classId, $backgroundId, $raceId, $equipmentChoices) {
    $pdo = getPDO();
    $finalEquipment = [];
    $backgroundGold = 0;
    
    // Récupérer l'argent de départ du background
    $stmt = $pdo->prepare("SELECT money_gold FROM backgrounds WHERE id = ?");
    $stmt->execute([$backgroundId]);
    $result = $stmt->fetch();
    if ($result) {
        $backgroundGold = $result['money_gold'];
    }
    
    // Récupérer l'équipement de classe depuis la table starting_equipment
    $classEquipment = getClassStartingEquipmentNew($classId);
    
    if (!empty($classEquipment)) {
        // Structurer l'équipement par choix
        $classChoices = structureStartingEquipmentByChoices($classEquipment);
        
        foreach ($classChoices as $choiceIndex => $choiceGroup) {
            if ($choiceGroup['type'] === 'fixed') {
                // Équipement obligatoire - ajouter tous les items
                foreach ($choiceGroup['options'] as $option) {
                    $itemId = $option['id'];
                    $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                }
            } elseif ($choiceGroup['type'] === 'choice') {
                // Choix d'équipement - traiter selon les choix du joueur
                if (isset($equipmentChoices['class'][$choiceIndex])) {
                    $selectedOptionIndex = $equipmentChoices['class'][$choiceIndex];
                    if (isset($choiceGroup['options'][$selectedOptionIndex])) {
                        $selectedOption = $choiceGroup['options'][$selectedOptionIndex];
                        $itemId = $selectedOption['id'];
                        $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                    }
                } else {
                    // Si aucun choix n'a été fait, prendre la première option par défaut
                    if (!empty($choiceGroup['options']) && isset($choiceGroup['options'][0])) {
                        $firstOption = $choiceGroup['options'][0];
                        if (isset($firstOption['id'])) {
                            $itemId = $firstOption['id'];
                            $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                        }
                    }
                }
            }
        }
    }
    
    // Récupérer l'équipement de l'historique depuis la table starting_equipment
    $backgroundEquipment = getBackgroundStartingEquipment($backgroundId);
    if (!empty($backgroundEquipment)) {
        $backgroundChoices = structureStartingEquipmentByChoices($backgroundEquipment);
        
        foreach ($backgroundChoices as $choiceIndex => $choiceGroup) {
            if ($choiceGroup['type'] === 'fixed') {
                // Équipement obligatoire - ajouter tous les items
                foreach ($choiceGroup['options'] as $option) {
                    $itemId = $option['id'];
                    $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                }
            } elseif ($choiceGroup['type'] === 'choice') {
                // Choix d'équipement - traiter selon les choix du joueur
                if (isset($equipmentChoices['background'][$choiceIndex])) {
                    $selectedOptionIndex = $equipmentChoices['background'][$choiceIndex];
                    if (isset($choiceGroup['options'][$selectedOptionIndex])) {
                        $selectedOption = $choiceGroup['options'][$selectedOptionIndex];
                        $itemId = $selectedOption['id'];
                        $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                    }
                } else {
                    // Si aucun choix n'a été fait, prendre la première option par défaut
                    if (!empty($choiceGroup['options']) && isset($choiceGroup['options'][0])) {
                        $firstOption = $choiceGroup['options'][0];
                        if (isset($firstOption['id'])) {
                            $itemId = $firstOption['id'];
                            $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                        }
                    }
                }
            }
        }
    }
    
    // Récupérer l'équipement de race (s'il existe)
    $raceEquipment = getRaceStartingEquipment($raceId);
    if (!empty($raceEquipment)) {
        $raceChoices = structureStartingEquipmentByChoices($raceEquipment);
        
        foreach ($raceChoices as $choiceIndex => $choiceGroup) {
            if ($choiceGroup['type'] === 'fixed') {
                // Équipement obligatoire - ajouter tous les items
                foreach ($choiceGroup['options'] as $option) {
                    $itemId = $option['id'];
                    $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                }
            } elseif ($choiceGroup['type'] === 'choice') {
                // Choix d'équipement - traiter selon les choix du joueur
                if (isset($equipmentChoices['race'][$choiceIndex])) {
                    $selectedOptionIndex = $equipmentChoices['race'][$choiceIndex];
                    if (isset($choiceGroup['options'][$selectedOptionIndex])) {
                        $selectedOption = $choiceGroup['options'][$selectedOptionIndex];
                        $itemId = $selectedOption['id'];
                        $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                    }
                } else {
                    // Si aucun choix n'a été fait, prendre la première option par défaut
                    if (!empty($choiceGroup['options']) && isset($choiceGroup['options'][0])) {
                        $firstOption = $choiceGroup['options'][0];
                        if (isset($firstOption['id'])) {
                            $itemId = $firstOption['id'];
                            $finalEquipment[] = $itemId; // Utiliser l'ID pour addStartingEquipmentToCharacterNew
                        }
                    }
                }
            }
        }
    }
    
    return [
        'equipment' => $finalEquipment,
        'gold' => $backgroundGold
    ];
}

/**
 * Détermine le type d'équipement à partir du nom de l'item
 */
function detectEquipmentType($itemName) {
    $pdo = getPDO();
    
    // Convertir en minuscules pour la comparaison
    $itemNameLower = strtolower($itemName);
    
    // Vérifier d'abord dans les tables spécifiques
    try {
        // Vérifier dans weapons
        $stmt = $pdo->prepare("SELECT 'weapon' as type FROM weapons WHERE LOWER(name) = ? LIMIT 1");
        $stmt->execute([$itemNameLower]);
        if ($stmt->fetch()) {
            return 'weapon';
        }
        
        // Vérifier dans armor (armures et boucliers)
        $stmt = $pdo->prepare("SELECT 'armor' as type FROM armor WHERE LOWER(name) = ? AND type != 'Bouclier' LIMIT 1");
        $stmt->execute([$itemNameLower]);
        if ($stmt->fetch()) {
            return 'armor';
        }
        
        // Vérifier dans armor pour les boucliers
        $stmt = $pdo->prepare("SELECT 'shield' as type FROM armor WHERE LOWER(name) = ? AND type = 'Bouclier' LIMIT 1");
        $stmt->execute([$itemNameLower]);
        if ($stmt->fetch()) {
            return 'shield';
        }
        
        // Vérifier dans Object (outils, sacs, nourriture)
        $stmt = $pdo->prepare("SELECT type FROM Object WHERE LOWER(nom) = ? LIMIT 1");
        $stmt->execute([$itemNameLower]);
        $result = $stmt->fetch();
        if ($result) {
            switch ($result['type']) {
                case 'outils':
                    return 'outil';  // Mapper vers l'ENUM autorisé
                case 'sac':
                    return 'bourse'; // Mapper vers l'ENUM autorisé
                case 'nourriture':
                    return 'outil';  // Mapper vers l'ENUM autorisé
                default:
                    return 'outil';  // Mapper vers l'ENUM autorisé
            }
        }
        
    } catch (PDOException $e) {
        error_log("Erreur detectEquipmentType: " . $e->getMessage());
    }
    
    // Détection par mots-clés si pas trouvé dans les tables
    if (strpos($itemNameLower, 'épée') !== false || 
        strpos($itemNameLower, 'hache') !== false || 
        strpos($itemNameLower, 'arc') !== false || 
        strpos($itemNameLower, 'dague') !== false ||
        strpos($itemNameLower, 'marteau') !== false ||
        strpos($itemNameLower, 'lance') !== false ||
        strpos($itemNameLower, 'javeline') !== false) {
        return 'weapon';
    }
    
    if (strpos($itemNameLower, 'armure') !== false || 
        strpos($itemNameLower, 'cuirasse') !== false || 
        strpos($itemNameLower, 'cotte') !== false ||
        strpos($itemNameLower, 'robe') !== false) {
        return 'armor';
    }
    
    if (strpos($itemNameLower, 'bouclier') !== false) {
        return 'shield';
    }
    
    if (strpos($itemNameLower, 'sac') !== false) {
        return 'bourse';  // Mapper vers l'ENUM autorisé
    }
    
    if (strpos($itemNameLower, 'ration') !== false || 
        strpos($itemNameLower, 'pain') !== false || 
        strpos($itemNameLower, 'fromage') !== false ||
        strpos($itemNameLower, 'viande') !== false) {
        return 'outil';  // Mapper vers l'ENUM autorisé
    }
    
    if (strpos($itemNameLower, 'outil') !== false || 
        strpos($itemNameLower, 'corde') !== false || 
        strpos($itemNameLower, 'torche') !== false ||
        strpos($itemNameLower, 'gamelle') !== false) {
        return 'outil';  // Mapper vers l'ENUM autorisé
    }
    
    // Par défaut
    return 'outil';  // Mapper vers l'ENUM autorisé
}

/**
 * Ajoute l'équipement de départ à un personnage (nouvelle version)
 */
function addStartingEquipmentToCharacterNew($characterId, $equipmentData) {
    $pdo = getPDO();
    
    try {
        $pdo->beginTransaction();
        
        // Parser l'équipement final
        $equipmentLines = explode("\n", $equipmentData['equipment']);
        
        foreach ($equipmentLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Si la ligne est un ID numérique, récupérer le vrai nom et le type depuis starting_equipment
            $displayName = $line;
            $itemType = 'other'; // Default type
            $weaponId = null;
            $armorId = null;
            $quantity = 1; // Default quantity

            if (is_numeric($line)) {
                $stmt = $pdo->prepare("SELECT * FROM starting_equipment WHERE id = ?");
                $stmt->execute([$line]);
                $starting_equipment = $stmt->fetch();
                
                if ($starting_equipment) {
                    // Récupérer la quantité spécifiée
                    $quantity = $starting_equipment['nb'] ?? 1;
                    
                    // Determine display name
                    switch ($starting_equipment['type']) {
                        case 'weapon':
                            if ($starting_equipment['type_id']) {
                                $stmt2 = $pdo->prepare("SELECT name FROM weapons WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: 'Arme';
                                $weaponId = $starting_equipment['type_id'];
                            } else {
                                $displayName = 'Arme';
                                $weaponId = null;
                            }
                            $itemType = 'weapon';
                            break;
                            
                        case 'armor':
                            if ($starting_equipment['type_id']) {
                                $stmt2 = $pdo->prepare("SELECT name FROM armor WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: 'Armure';
                                $armorId = $starting_equipment['type_id'];
                            } else {
                                $displayName = 'Armure';
                                $armorId = null;
                            }
                            $itemType = 'armor';
                            break;
                            
                        case 'sac':
                            if ($starting_equipment['type_id']) {
                                $stmt2 = $pdo->prepare("SELECT nom FROM Object WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: 'Sac';
                            } else {
                                $displayName = 'Sac';
                            }
                            $itemType = 'bourse'; // Use 'bourse' for bags as per ENUM
                            break;
                        case 'outils':
                            if ($starting_equipment['type_id']) {
                                $stmt2 = $pdo->prepare("SELECT nom FROM Object WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: 'Outils';
                            } else {
                                $displayName = 'Outils';
                            }
                            $itemType = 'outil';
                            break;
                        case 'nourriture':
                            if ($starting_equipment['type_id']) {
                                $stmt2 = $pdo->prepare("SELECT nom FROM Object WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: 'Nourriture';
                            } else {
                                $displayName = 'Nourriture';
                            }
                            $itemType = 'bourse'; // Use 'bourse' for food as per ENUM
                            break;
                        default:
                            $displayName = ucfirst($starting_equipment['type']);
                            $itemType = 'outil'; // Default to 'outil' for unknown types in ENUM
                            break;
                    }
                }
            } else {
                // Existing logic for non-numeric lines (direct names)
                // Déterminer le type d'équipement
                $itemType = detectEquipmentType($line);
            }
            
            // Insérer dans la table items
            $stmt = $pdo->prepare("
                INSERT INTO items 
                (place_id, display_name, object_type, type_precis, description, 
                 is_identified, is_visible, is_equipped, position_x, position_y, 
                 is_on_map, owner_type, owner_id, item_source, quantity, 
                 equipped_slot, notes, obtained_at, obtained_from, weapon_id, armor_id) 
                VALUES (NULL, ?, ?, ?, NULL, 
                        1, 0, 0, 0, 0, 
                        0, 'player', ?, 'Équipement de départ', ?, 
                        NULL, 'Équipement de départ', NOW(), 'Équipement de départ', ?, ?)
            ");
            $stmt->execute([$displayName, $itemType, $itemType, $characterId, $quantity, $weaponId, $armorId]);
        }
        
        // Mettre à jour l'argent du personnage
        if ($equipmentData['gold'] > 0) {
            $stmt = $pdo->prepare("UPDATE characters SET money_gold = money_gold + ? WHERE id = ?");
            $stmt->execute([$equipmentData['gold'], $characterId]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur addStartingEquipmentToCharacterNew: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un personnage a déjà son équipement de départ
 */
function hasStartingEquipment($characterId) {
    $pdo = getPDO();
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM items 
            WHERE owner_type = 'player' AND owner_id = ? AND obtained_from = 'Équipement de départ'
        ");
        $stmt->execute([$characterId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Erreur hasStartingEquipment: " . $e->getMessage());
        return false;
    }
}
?>
