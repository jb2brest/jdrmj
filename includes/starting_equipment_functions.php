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
            ORDER BY groupe_id, option_indice, id
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
 * Récupère les détails d'un équipement selon son type
 */
function getEquipmentDetails($type, $typeId) {
    global $pdo;
    
    if (!$typeId) {
        return null;
    }
    
    try {
        switch ($type) {
            case 'Arme':
                $stmt = $pdo->prepare("SELECT * FROM weapons WHERE id = ?");
                break;
            case 'Armure':
                $stmt = $pdo->prepare("SELECT * FROM armor WHERE id = ?");
                break;
            case 'Bouclier':
                $stmt = $pdo->prepare("SELECT * FROM armor WHERE id = ? AND type = 'Bouclier'");
                break;
            case 'Outils':
                $stmt = $pdo->prepare("SELECT * FROM tools WHERE id = ?");
                break;
            case 'Accessoire':
                // Pour les accessoires, on peut avoir une table générique ou utiliser des données codées
                return ['name' => 'Accessoire générique', 'description' => 'Accessoire de départ'];
            case 'Sac':
                return getEquipmentPackDetails($typeId);
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
    $classGroups = structureStartingEquipmentByGroups($classEquipment);
    
    // Récupérer l'équipement de background
    $backgroundEquipment = getBackgroundStartingEquipment($backgroundId);
    $backgroundGroups = structureStartingEquipmentByGroups($backgroundEquipment);
    
    // Récupérer l'équipement de race
    $raceEquipment = getRaceStartingEquipment($raceId);
    $raceGroups = structureStartingEquipmentByGroups($raceEquipment);
    
    // Traiter l'équipement de classe
    foreach ($classGroups as $groupId => $group) {
        if ($group['type_choix'] === 'obligatoire') {
            // Équipement obligatoire - prendre tous les items du groupe
            foreach ($group['options'] as $item) {
                $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
                if ($equipmentDetails) {
                    if ($item['type'] === 'Sac' && isset($equipmentDetails['contents'])) {
                        $finalEquipment[] = $equipmentDetails['name'];
                        $finalEquipment = array_merge($finalEquipment, $equipmentDetails['contents']);
                    } else {
                        $finalEquipment[] = $equipmentDetails['name'] ?? $item['type'];
                    }
                }
            }
        } else {
            // Équipement à choisir
            if (isset($equipmentChoices['class'][$groupId])) {
                $selectedOption = $equipmentChoices['class'][$groupId];
                $selectedItem = null;
                
                foreach ($group['options'] as $item) {
                    if ($item['option_indice'] === $selectedOption) {
                        $selectedItem = $item;
                        break;
                    }
                }
                
                if ($selectedItem) {
                    $equipmentDetails = getEquipmentDetails($selectedItem['type'], $selectedItem['type_id']);
                    if ($equipmentDetails) {
                        if ($selectedItem['type'] === 'Sac' && isset($equipmentDetails['contents'])) {
                            $finalEquipment[] = $equipmentDetails['name'];
                            $finalEquipment = array_merge($finalEquipment, $equipmentDetails['contents']);
                        } else {
                            $finalEquipment[] = $equipmentDetails['name'] ?? $selectedItem['type'];
                        }
                    }
                }
            }
        }
    }
    
    // Traiter l'équipement de background
    foreach ($backgroundGroups as $groupId => $group) {
        foreach ($group['options'] as $item) {
            $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
            if ($equipmentDetails) {
                // Vérifier si c'est de l'argent
                if (strpos($equipmentDetails['name'], 'po') !== false || 
                    strpos($equipmentDetails['description'], 'po') !== false) {
                    preg_match('/(\d+)\s*po/i', $equipmentDetails['name'] . ' ' . $equipmentDetails['description'], $matches);
                    if ($matches) {
                        $backgroundGold += (int)$matches[1];
                    }
                } else {
                    $finalEquipment[] = $equipmentDetails['name'] ?? $item['type'];
                }
            }
        }
    }
    
    // Traiter l'équipement de race
    foreach ($raceGroups as $groupId => $group) {
        foreach ($group['options'] as $item) {
            $equipmentDetails = getEquipmentDetails($item['type'], $item['type_id']);
            if ($equipmentDetails) {
                $finalEquipment[] = $equipmentDetails['name'] ?? $item['type'];
            }
        }
    }
    
    return [
        'equipment' => implode("\n", $finalEquipment),
        'gold' => $backgroundGold
    ];
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
            
            // Insérer dans character_equipment
            $stmt = $pdo->prepare("
                INSERT INTO character_equipment 
                (character_id, item_name, item_type, item_source, obtained_from, quantity, equipped) 
                VALUES (?, ?, 'Équipement de départ', 'Équipement de départ', 'Équipement de départ', 1, 0)
            ");
            $stmt->execute([$characterId, $line]);
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
