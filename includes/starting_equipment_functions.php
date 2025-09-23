<?php
/**
 * Nouvelles fonctions pour gérer l'équipement de départ avec la table starting_equipment
 */

/**
 * Récupère l'équipement de départ pour une source donnée (classe, background, race)
 */
function getStartingEquipmentBySource($src, $srcId) {
    global $pdo;
    
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
                'type_choix' => $item['type_choix'],
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
            $mergedChoices[$choiceId] = $choice;
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
                    $mergedOptions[] = $mergedOption;
                } else {
                    $mergedOptions[] = $options[0];
                }
            }
            
            $mergedChoices[$choiceId] = [
                'id' => $choiceId,
                'type_choix' => $choice['type_choix'],
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
    global $pdo;
    
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
 * Génère l'équipement final basé sur les choix du joueur (nouvelle version)
 */
function generateFinalEquipmentNew($classId, $backgroundId, $raceId, $equipmentChoices) {
    $finalEquipment = [];
    $backgroundGold = 0;
    
    // Récupérer l'équipement de classe
    $classEquipment = getClassStartingEquipmentNew($classId);
    $classGroups = structureStartingEquipmentByChoices($classEquipment);
    
    // Récupérer l'équipement de background
    $backgroundEquipment = getBackgroundStartingEquipment($backgroundId);
    $backgroundGroups = structureStartingEquipmentByChoices($backgroundEquipment);
    
    // Récupérer l'équipement de race
    $raceEquipment = getRaceStartingEquipment($raceId);
    $raceGroups = structureStartingEquipmentByChoices($raceEquipment);
    
    // Traiter l'équipement de classe
    foreach ($classGroups as $groupId => $group) {
        if ($group['type_choix'] === 'obligatoire') {
            // Équipement obligatoire - prendre tous les items du groupe
            foreach ($group['options'] as $item) {
                if (isset($item['merged_items'])) {
                    // Équipement fusionné - traiter tous les items fusionnés
                    foreach ($item['merged_items'] as $mergedItem) {
                        $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
                        if ($equipmentDetails) {
                            $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type'];
                            $finalEquipment[] = $itemName;
                        }
                    }
                } else {
                    // Équipement simple
                    $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
                    if ($equipmentDetails) {
                        $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $item['type'];
                        $finalEquipment[] = $itemName;
                    }
                }
            }
        } else {
            // Équipement à choisir
            if (isset($equipmentChoices['class'][$groupId])) {
                $selectedOption = $equipmentChoices['class'][$groupId];
                $selectedItem = null;
                
                foreach ($group['options'] as $item) {
                    if ($item['option_letter'] === $selectedOption) {
                        $selectedItem = $item;
                        break;
                    }
                }
                
                if ($selectedItem) {
                    if (isset($selectedItem['merged_items'])) {
                        // Équipement fusionné - traiter tous les items fusionnés
                        foreach ($selectedItem['merged_items'] as $mergedItem) {
                            $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
                            if ($equipmentDetails) {
                                $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type'];
                                $finalEquipment[] = $itemName;
                            }
                        }
                    } else {
                        // Équipement simple
                        $equipmentDetails = getEquipmentDetails($selectedItem['type'], $selectedItem['type_id']);
                        if ($equipmentDetails) {
                            $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $selectedItem['type'];
                            $finalEquipment[] = $itemName;
                        }
                    }
                }
            }
        }
    }
    
    // Traiter les armes sélectionnées
    if (isset($equipmentChoices['selected_weapons'])) {
        foreach ($equipmentChoices['selected_weapons'] as $weapon) {
            if (!empty($weapon)) {
                $finalEquipment[] = $weapon;
            }
        }
    }
    
    // Traiter l'équipement de background
    foreach ($backgroundGroups as $groupId => $group) {
        foreach ($group['options'] as $item) {
            if (isset($item['merged_items'])) {
                // Équipement fusionné - traiter tous les items fusionnés
                foreach ($item['merged_items'] as $mergedItem) {
                    $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
                    if ($equipmentDetails) {
                        $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type'];
                        // Vérifier si c'est de l'argent
                        if (strpos($itemName, 'po') !== false) {
                            preg_match('/(\d+)\s*po/i', $itemName, $matches);
                            if ($matches) {
                                $backgroundGold += (int)$matches[1];
                            }
                        } else {
                            $finalEquipment[] = $itemName;
                        }
                    }
                }
            } else {
                // Équipement simple
                $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
                if ($equipmentDetails) {
                    $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $item['type'];
                    // Vérifier si c'est de l'argent
                    if (strpos($itemName, 'po') !== false) {
                        preg_match('/(\d+)\s*po/i', $itemName, $matches);
                        if ($matches) {
                            $backgroundGold += (int)$matches[1];
                        }
                    } else {
                        $finalEquipment[] = $itemName;
                    }
                }
            }
        }
    }
    
    
    // Traiter l'équipement de race
    foreach ($raceGroups as $groupId => $group) {
        foreach ($group['options'] as $item) {
            if (isset($item['merged_items'])) {
                // Équipement fusionné - traiter tous les items fusionnés
                foreach ($item['merged_items'] as $mergedItem) {
                    $equipmentDetails = getEquipmentDetails($mergedItem['type'], $mergedItem['type_id']);
                    if ($equipmentDetails) {
                        $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $mergedItem['type'];
                        $finalEquipment[] = $itemName;
                    }
                }
            } else {
                // Équipement simple
                $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
                if ($equipmentDetails) {
                    $itemName = $equipmentDetails['name'] ?? $equipmentDetails['nom'] ?? $item['type'];
                    $finalEquipment[] = $itemName;
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
    global $pdo;
    
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
                    return 'tool';
                case 'sac':
                    return 'bag';
                case 'nourriture':
                    return 'consumable';
                default:
                    return 'misc';
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
        return 'bag';
    }
    
    if (strpos($itemNameLower, 'ration') !== false || 
        strpos($itemNameLower, 'pain') !== false || 
        strpos($itemNameLower, 'fromage') !== false ||
        strpos($itemNameLower, 'viande') !== false) {
        return 'consumable';
    }
    
    if (strpos($itemNameLower, 'outil') !== false || 
        strpos($itemNameLower, 'corde') !== false || 
        strpos($itemNameLower, 'torche') !== false ||
        strpos($itemNameLower, 'gamelle') !== false) {
        return 'tool';
    }
    
    // Par défaut
    return 'misc';
}

/**
 * Ajoute l'équipement de départ à un personnage (nouvelle version)
 */
function addStartingEquipmentToCharacterNew($characterId, $equipmentData) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Parser l'équipement final
        $equipmentLines = explode("\n", $equipmentData['equipment']);
        
        foreach ($equipmentLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Déterminer le type d'équipement
            $equipmentType = detectEquipmentType($line);
            
            // Insérer dans character_equipment
            $stmt = $pdo->prepare("
                INSERT INTO character_equipment 
                (character_id, item_name, item_type, item_source, obtained_from, quantity, equipped) 
                VALUES (?, ?, ?, 'Équipement de départ', 'Équipement de départ', 1, 0)
            ");
            $stmt->execute([$characterId, $line, $equipmentType]);
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
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM character_equipment 
            WHERE character_id = ? AND item_source = 'Équipement de départ'
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
