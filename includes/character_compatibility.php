<?php

/**
 * Couche de compatibilité pour les fonctions liées aux personnages
 * 
 * Ce fichier fournit des fonctions de compatibilité qui encapsulent
 * les méthodes de la classe Character pour maintenir la compatibilité
 * avec le code existant.
 */

// S'assurer que les classes nécessaires sont chargées
if (!class_exists('Character')) {
    require_once __DIR__ . '/../classes/init.php';
}

/**
 * Créer un personnage (compatibilité)
 */
if (!function_exists('createCharacter')) {
    function createCharacter($data) {
        return Character::create($data);
    }
}

/**
 * Trouver un personnage par ID (compatibilité)
 */
if (!function_exists('getCharacterById')) {
    function getCharacterById($id) {
        return Character::findById($id);
    }
}

/**
 * Trouver tous les personnages d'un utilisateur (compatibilité)
 */
if (!function_exists('getCharactersByUserId')) {
    function getCharactersByUserId($userId) {
        return Character::findByUserId($userId);
    }
}

/**
 * Mettre à jour le niveau d'un personnage basé sur l'expérience (compatibilité)
 */
if (!function_exists('updateCharacterLevelFromExperience')) {
    function updateCharacterLevelFromExperience($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            $character->updateLevelFromExperience();
            return true;
        }
        return false;
    }
}

/**
 * Obtenir les sorts d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterSpells')) {
    function getCharacterSpells($characterId) {
        return Character::getCharacterSpells($characterId);
    }
}

/**
 * Ajouter un sort à un personnage (compatibilité)
 */
if (!function_exists('addSpellToCharacter')) {
    function addSpellToCharacter($characterId, $spellId, $prepared = false) {
        return Character::addSpellToCharacter($characterId, $spellId, $prepared);
    }
}

/**
 * Retirer un sort d'un personnage (compatibilité)
 */
if (!function_exists('removeSpellFromCharacter')) {
    function removeSpellFromCharacter($characterId, $spellId) {
        return Character::removeSpellFromCharacter($characterId, $spellId);
    }
}

/**
 * Mettre à jour l'état préparé d'un sort (compatibilité)
 */
if (!function_exists('updateSpellPrepared')) {
    function updateSpellPrepared($characterId, $spellId, $prepared) {
        return Character::updateSpellPrepared($characterId, $spellId, $prepared);
    }
}

/**
 * Obtenir l'utilisation des emplacements de sorts (compatibilité)
 */
if (!function_exists('getSpellSlotsUsage')) {
    function getSpellSlotsUsage($characterId) {
        return Character::getSpellSlotsUsageStatic($characterId);
    }
}

/**
 * Ajouter l'équipement de départ à un personnage (compatibilité)
 */
if (!function_exists('addStartingEquipmentToCharacter')) {
    function addStartingEquipmentToCharacter($characterId, $equipmentData) {
        return Character::addStartingEquipmentToCharacter($characterId, $equipmentData);
    }
}

/**
 * Calculer la classe d'armure étendue (compatibilité)
 */
if (!function_exists('calculateArmorClassExtended')) {
    function calculateArmorClassExtended($character, $equippedArmor = null, $equippedShield = null) {
        if (is_array($character)) {
            // Convertir le tableau en objet Character
            $characterObj = new Character(null, $character);
            return $characterObj->calculateArmorClass();
        } elseif ($character instanceof Character) {
            return $character->calculateArmorClass();
        }
        return 10;
    }
}

/**
 * Calculer les attaques d'un personnage (compatibilité)
 */
if (!function_exists('calculateCharacterAttacks')) {
    function calculateCharacterAttacks($characterId, $character) {
        // Cette fonction nécessite une logique complexe
        // Pour l'instant, on retourne un tableau vide pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return [];
    }
}

/**
 * Obtenir l'équipement équipé d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterEquippedItems')) {
    function getCharacterEquippedItems($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->getEquippedItems();
        }
        return [];
    }
}

/**
 * Équiper un objet (compatibilité)
 */
if (!function_exists('equipItem')) {
    function equipItem($characterId, $itemName, $itemType, $slot) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->equipItem($itemName, $itemType, $slot);
        }
        return false;
    }
}

/**
 * Déséquiper un objet (compatibilité)
 */
if (!function_exists('unequipItem')) {
    function unequipItem($characterId, $itemName) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->unequipItem($itemName);
        }
        return false;
    }
}

/**
 * Synchroniser l'équipement de base avec l'équipement du personnage (compatibilité)
 */
if (!function_exists('syncBaseEquipmentToCharacterEquipment')) {
    function syncBaseEquipmentToCharacterEquipment($characterId) {
        // Cette fonction nécessite une logique complexe
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Utiliser un emplacement de sort (compatibilité)
 */
if (!function_exists('useSpellSlot')) {
    function useSpellSlot($characterId, $level) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->useSpellSlot($level);
        }
        return false;
    }
}

/**
 * Libérer un emplacement de sort (compatibilité)
 */
if (!function_exists('freeSpellSlot')) {
    function freeSpellSlot($characterId, $level) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->freeSpellSlot($level);
        }
        return false;
    }
}

/**
 * Réinitialiser l'utilisation des emplacements de sorts (compatibilité)
 */
if (!function_exists('resetSpellSlotsUsage')) {
    function resetSpellSlotsUsage($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->resetSpellSlotsUsage();
        }
        return false;
    }
}

/**
 * Obtenir l'utilisation de la rage (compatibilité)
 */
if (!function_exists('getRageUsage')) {
    function getRageUsage($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->getRageUsage();
        }
        return ['used' => 0, 'max_uses' => 0];
    }
}

/**
 * Utiliser la rage (compatibilité)
 */
if (!function_exists('useRage')) {
    function useRage($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->useRage();
        }
        return false;
    }
}

/**
 * Libérer la rage (compatibilité)
 */
if (!function_exists('freeRage')) {
    function freeRage($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->freeRage();
        }
        return false;
    }
}

/**
 * Réinitialiser l'utilisation de la rage (compatibilité)
 */
if (!function_exists('resetRageUsage')) {
    function resetRageUsage($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->resetRageUsage();
        }
        return false;
    }
}

/**
 * Obtenir le chemin barbare d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterBarbarianPath')) {
    function getCharacterBarbarianPath($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir le serment paladin d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterPaladinOath')) {
    function getCharacterPaladinOath($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir l'archétype ranger d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterRangerArchetype')) {
    function getCharacterRangerArchetype($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir l'archétype rogue d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterRogueArchetype')) {
    function getCharacterRogueArchetype($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir le collège bard d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterBardCollege')) {
    function getCharacterBardCollege($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir le domaine cleric d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterClericDomain')) {
    function getCharacterClericDomain($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir le cercle druid d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterDruidCircle')) {
    function getCharacterDruidCircle($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir l'origine sorcerer d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterSorcererOrigin')) {
    function getCharacterSorcererOrigin($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir l'archétype fighter d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterFighterArchetype')) {
    function getCharacterFighterArchetype($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir la tradition wizard d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterWizardTradition')) {
    function getCharacterWizardTradition($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir la tradition monk d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterMonkTradition')) {
    function getCharacterMonkTradition($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Obtenir le pacte warlock d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterWarlockPact')) {
    function getCharacterWarlockPact($characterId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne null pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return null;
    }
}

/**
 * Sauvegarder le chemin barbare (compatibilité)
 */
if (!function_exists('saveBarbarianPath')) {
    function saveBarbarianPath($characterId, $pathId, $level3Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder le serment paladin (compatibilité)
 */
if (!function_exists('savePaladinOath')) {
    function savePaladinOath($characterId, $oathId, $level3Choice = null, $level7Choice = null, $level15Choice = null, $level20Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder l'archétype ranger (compatibilité)
 */
if (!function_exists('saveRangerArchetype')) {
    function saveRangerArchetype($characterId, $archetypeId, $level3Choice = null, $level7Choice = null, $level11Choice = null, $level15Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder l'archétype rogue (compatibilité)
 */
if (!function_exists('saveRogueArchetype')) {
    function saveRogueArchetype($characterId, $archetypeId, $level3Choice = null, $level9Choice = null, $level13Choice = null, $level17Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder le collège bard (compatibilité)
 */
if (!function_exists('saveBardCollege')) {
    function saveBardCollege($characterId, $collegeId, $level3Choice = null, $level6Choice = null, $level14Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder le domaine cleric (compatibilité)
 */
if (!function_exists('saveClericDomain')) {
    function saveClericDomain($characterId, $domainId, $level1Choice = null, $level2Choice = null, $level6Choice = null, $level8Choice = null, $level17Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder le cercle druid (compatibilité)
 */
if (!function_exists('saveDruidCircle')) {
    function saveDruidCircle($characterId, $circleId, $level2Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder l'origine sorcerer (compatibilité)
 */
if (!function_exists('saveSorcererOrigin')) {
    function saveSorcererOrigin($characterId, $originId, $level1Choice = null, $level6Choice = null, $level14Choice = null, $level18Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder l'archétype fighter (compatibilité)
 */
if (!function_exists('saveFighterArchetype')) {
    function saveFighterArchetype($characterId, $archetypeId, $level3Choice = null, $level7Choice = null, $level10Choice = null, $level15Choice = null, $level18Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder la tradition wizard (compatibilité)
 */
if (!function_exists('saveWizardTradition')) {
    function saveWizardTradition($characterId, $traditionId, $level2Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder la tradition monk (compatibilité)
 */
if (!function_exists('saveMonkTradition')) {
    function saveMonkTradition($characterId, $traditionId, $level3Choice = null, $level6Choice = null, $level11Choice = null, $level17Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Sauvegarder le pacte warlock (compatibilité)
 */
if (!function_exists('saveWarlockPact')) {
    function saveWarlockPact($characterId, $pactId, $level3Choice = null, $level7Choice = null, $level15Choice = null, $level20Choice = null) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}

/**
 * Obtenir les améliorations de caractéristiques d'un personnage (compatibilité)
 */
if (!function_exists('getCharacterAbilityImprovements')) {
    function getCharacterAbilityImprovements($characterId) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->getAbilityImprovements();
        }
        return [];
    }
}

/**
 * Sauvegarder les améliorations de caractéristiques d'un personnage (compatibilité)
 */
if (!function_exists('saveCharacterAbilityImprovements')) {
    function saveCharacterAbilityImprovements($characterId, $improvements) {
        $character = Character::findById($characterId);
        if ($character) {
            return $character->saveAbilityImprovements($improvements);
        }
        return false;
    }
}

/**
 * Calculer les caractéristiques finales (compatibilité)
 */
if (!function_exists('calculateFinalAbilities')) {
    function calculateFinalAbilities($character, $abilityImprovements) {
        if (is_array($character)) {
            // Convertir le tableau en objet Character
            $characterObj = new Character(null, $character);
            return $characterObj->calculateFinalAbilities($abilityImprovements);
        } elseif ($character instanceof Character) {
            return $character->calculateFinalAbilities($abilityImprovements);
        }
        return [];
    }
}

/**
 * Créer une session de création de personnage (compatibilité)
 */
if (!function_exists('createCharacterCreationSession')) {
    function createCharacterCreationSession($userId) {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne un ID de session généré pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return uniqid('char_creation_', true);
    }
}

/**
 * Obtenir les données de création de personnage (compatibilité)
 */
if (!function_exists('getCharacterCreationData')) {
    function getCharacterCreationData($userId, $sessionId) {
        // Récupérer les données depuis les sessions PHP
        if (isset($_SESSION['character_creation_data'][$sessionId])) {
            $data = $_SESSION['character_creation_data'][$sessionId];
            
            // Vérifier que les données appartiennent à l'utilisateur
            if ($data['user_id'] == $userId) {
                return $data;
            }
        }
        
        // Si aucune donnée trouvée, retourner une structure de base
        return [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'step' => 1,
            'data' => []
        ];
    }
}

/**
 * Sauvegarder une étape de création de personnage (compatibilité)
 */
if (!function_exists('saveCharacterCreationStep')) {
    function saveCharacterCreationStep($userId, $sessionId, $step, $data) {
        // Utiliser les sessions PHP pour stocker temporairement les données
        if (!isset($_SESSION['character_creation_data'])) {
            $_SESSION['character_creation_data'] = [];
        }
        
        if (!isset($_SESSION['character_creation_data'][$sessionId])) {
            $_SESSION['character_creation_data'][$sessionId] = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'step' => $step,
                'data' => []
            ];
        }
        
        // Mettre à jour les données
        $_SESSION['character_creation_data'][$sessionId]['data'] = array_merge(
            $_SESSION['character_creation_data'][$sessionId]['data'],
            $data
        );
        $_SESSION['character_creation_data'][$sessionId]['step'] = $step;
        
        return true;
    }
}

/**
 * Sauvegarder les données de création de personnage (compatibilité)
 */
if (!function_exists('saveCharacterCreationData')) {
    function saveCharacterCreationData($userId, $sessionId, $data) {
        // Pour l'instant, on retourne true pour permettre la création
        // TODO: Implémenter la logique complète avec stockage en base
        return true;
    }
}

/**
 * Créer une session de création de PNJ (compatibilité)
 */
if (!function_exists('createNPCCreationSession')) {
    function createNPCCreationSession($userId) {
        return uniqid('npc_creation_', true);
    }
}

/**
 * Obtenir les données de création de PNJ (compatibilité)
 */
if (!function_exists('getNPCCreationData')) {
    function getNPCCreationData($userId, $sessionId) {
        if (isset($_SESSION['npc_creation_data'][$sessionId])) {
            $data = $_SESSION['npc_creation_data'][$sessionId];
            
            if ($data['user_id'] == $userId) {
                return $data;
            }
        }
        
        return [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'step' => 1,
            'data' => []
        ];
    }
}

/**
 * Sauvegarder une étape de création de PNJ (compatibilité)
 */
if (!function_exists('saveNPCCreationStep')) {
    function saveNPCCreationStep($userId, $sessionId, $step, $data) {
        if (!isset($_SESSION['npc_creation_data'])) {
            $_SESSION['npc_creation_data'] = [];
        }
        
        if (!isset($_SESSION['npc_creation_data'][$sessionId])) {
            $_SESSION['npc_creation_data'][$sessionId] = [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'step' => $step,
                'data' => []
            ];
        }
        
        $_SESSION['npc_creation_data'][$sessionId]['data'] = array_merge(
            $_SESSION['npc_creation_data'][$sessionId]['data'],
            $data
        );
        $_SESSION['npc_creation_data'][$sessionId]['step'] = $step;
        
        return true;
    }
}

/**
 * Nettoyer les données de création de PNJ (compatibilité)
 */
if (!function_exists('clearNPCCreationData')) {
    function clearNPCCreationData($userId, $sessionId) {
        if (isset($_SESSION['npc_creation_data'][$sessionId])) {
            unset($_SESSION['npc_creation_data'][$sessionId]);
        }
        return true;
    }
}

/**
 * Finaliser la création d'un PNJ (compatibilité)
 */
if (!function_exists('finalizeNPCCreation')) {
    function finalizeNPCCreation($userId, $sessionId) {
        $pdo = getPDO();
        
        try {
            $sessionData = getNPCCreationData($userId, $sessionId);
            if (!$sessionData || empty($sessionData['data'])) {
                error_log("Erreur finalizeNPCCreation: Aucune donnée de session trouvée");
                return false;
            }
            
            $data = $sessionData['data'];
            
            if (empty($data['name']) || empty($data['class_id']) || empty($data['race_id']) || empty($data['place_id'])) {
                error_log("Erreur finalizeNPCCreation: Données essentielles manquantes");
                return false;
            }
            
            // Créer d'abord le personnage dans la table characters
            $stmt = $pdo->prepare("
                INSERT INTO npcs (
                    name, race_id, class_id, level, experience, background_id, alignment,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    hit_points_current, hit_points_max, armor_class, speed, starting_equipment, personality_traits, ideals, bonds, flaws, 
                    world_id, location_id, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['name'], $data['race_id'], $data['class_id'], $data['level'] ?? 1, 
                $data['experience_points'] ?? 0, $data['background_id'] ?? null, $data['alignment'] ?? '',
                $data['strength'] ?? 10, $data['dexterity'] ?? 10, $data['constitution'] ?? 10,
                $data['intelligence'] ?? 10, $data['wisdom'] ?? 10, $data['charisma'] ?? 10,
                $data['hit_points_max'] ?? 8, $data['hit_points_max'] ?? 8, $data['armor_class'] ?? 10, $data['speed'] ?? 30,
                $data['equipment'] ?? '', $data['personality_traits'] ?? '', $data['ideals'] ?? '',
                $data['bonds'] ?? '', $data['flaws'] ?? '',
                $data['world_id'] ?? null, $data['location_id'] ?? null, $userId
            ]);
            
            $npc_id = $pdo->lastInsertId();
            
            // Créer ensuite l'entrée dans place_npcs
            $description = "PNJ de niveau " . ($data['level'] ?? 1) . " - " . ($data['race_name'] ?? '') . " " . ($data['class_name'] ?? '') . ". " . ($data['personality_traits'] ?? '');
            
            $stmt = $pdo->prepare("
                INSERT INTO place_npcs (name, description, profile_photo, is_visible, is_identified, place_id, monster_id, npc_character_id) 
                VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
            ");
            $stmt->execute([
                $data['name'], $description, $data['profile_photo'] ?? null, 
                $data['is_visible'] ?? 1, $data['is_identified'] ?? 1, 
                $data['place_id'], $npc_id
            ]);
            
            // Nettoyer la session
            unset($_SESSION['npc_creation_data'][$sessionId]);
            
            return [
                'character_id' => $character_id,
                'npc_id' => $pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            error_log("Erreur finalizeNPCCreation: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Fonction pour obtenir l'icône d'une classe
 */
if (!function_exists('getClassIcon')) {
    function getClassIcon($className) {
        $icons = [
            'Barbare' => 'fist-raised',
            'Barde' => 'music',
            'Clerc' => 'cross',
            'Druide' => 'leaf',
            'Ensorceleur' => 'magic',
            'Guerrier' => 'sword',
            'Magicien' => 'hat-wizard',
            'Moine' => 'hand-rock',
            'Occultiste' => 'skull',
            'Paladin' => 'shield-alt',
            'Rôdeur' => 'bow-arrow',
            'Roublard' => 'mask'
        ];
        
        return $icons[$className] ?? 'user';
    }
}

/**
 * Fonction pour obtenir l'icône d'une race
 */
if (!function_exists('getRaceIcon')) {
    function getRaceIcon($raceName) {
        $icons = [
            'Humain' => 'user',
            'Elfe' => 'leaf',
            'Nain' => 'hammer',
            'Halfelin' => 'seedling',
            'Demi-elfe' => 'star',
            'Demi-orc' => 'fist-raised',
            'Dragonide' => 'dragon',
            'Gnome' => 'magic',
            'Tieffelin' => 'fire'
        ];
        
        return $icons[$raceName] ?? 'user';
    }
}

/**
 * Fonction pour obtenir l'icône d'un historique
 */
if (!function_exists('getBackgroundIcon')) {
    function getBackgroundIcon($backgroundName) {
        $icons = [
            'Acolyte' => 'cross',
            'Artisan' => 'hammer',
            'Charlatan' => 'mask',
            'Criminel' => 'user-secret',
            'Ermite' => 'mountain',
            'Folk Hero' => 'star',
            'Guild Artisan' => 'tools',
            'Hermit' => 'tree',
            'Noble' => 'crown',
            'Outlander' => 'hiking',
            'Sage' => 'book',
            'Sailor' => 'ship',
            'Soldier' => 'shield-alt',
            'Spy' => 'eye',
            'Urchin' => 'home'
        ];
        
        return $icons[$backgroundName] ?? 'scroll';
    }
}

/**
 * Fonction pour obtenir l'icône d'une option de classe
 */
if (!function_exists('getClassOptionIcon')) {
    function getClassOptionIcon($className) {
        $icons = [
            'Barbare' => 'axe-battle',
            'Barde' => 'music',
            'Clerc' => 'cross',
            'Druide' => 'leaf',
            'Guerrier' => 'sword',
            'Magicien' => 'hat-wizard',
            'Moine' => 'fist-raised',
            'Paladin' => 'shield-alt',
            'Rôdeur' => 'bow-arrow',
            'Roublard' => 'mask',
            'Ensorceleur' => 'magic',
            'Occultiste' => 'hand-holding-magic'
        ];
        
        return $icons[$className] ?? 'star';
    }
}

/**
 * Fonction pour vérifier si une classe peut lancer des sorts
 */
if (!function_exists('canCastSpells')) {
    function canCastSpells($className) {
        $spellcasters = [
            'Barde',
            'Clerc', 
            'Druide',
            'Ensorceleur',
            'Magicien',
            'Occultiste',
            'Paladin',
            'Rôdeur'
        ];
        
        return in_array($className, $spellcasters);
    }
}

/**
 * Fonction pour obtenir les préconisations D&D pour une classe
 */
if (!function_exists('getDnDRecommendations')) {
    function getDnDRecommendations($className) {
        $recommendations = [
            'Barbare' => [
                'primary' => ['Force', 'Constitution'],
                'secondary' => ['Dextérité', 'Sagesse'],
                'description' => 'La Force pour les attaques, la Constitution pour la rage et la survie.'
            ],
            'Barde' => [
                'primary' => ['Charisme', 'Dextérité'],
                'secondary' => ['Constitution', 'Intelligence'],
                'description' => 'Le Charisme alimente les sorts et les performances, la Dextérité améliore l\'armure.'
            ],
            'Clerc' => [
                'primary' => ['Sagesse', 'Constitution'],
                'secondary' => ['Force', 'Dextérité'],
                'description' => 'La Sagesse alimente les sorts divins, la Constitution assure la survie au combat.'
            ],
            'Druide' => [
                'primary' => ['Sagesse', 'Constitution'],
                'secondary' => ['Dextérité', 'Intelligence'],
                'description' => 'La Sagesse alimente les sorts, la Constitution assure la survie en forme animale.'
            ],
            'Guerrier' => [
                'primary' => ['Force', 'Constitution'],
                'secondary' => ['Dextérité', 'Intelligence'],
                'description' => 'Les guerriers comptent sur la Force pour les attaques et la Constitution pour survivre au combat.'
            ],
            'Moine' => [
                'primary' => ['Dextérité', 'Sagesse'],
                'secondary' => ['Force', 'Constitution'],
                'description' => 'La Dextérité améliore l\'armure et les attaques, la Sagesse alimente les capacités ki.'
            ],
            'Paladin' => [
                'primary' => ['Force', 'Charisme'],
                'secondary' => ['Dextérité', 'Constitution'],
                'description' => 'La Force pour les attaques, le Charisme alimente les sorts et les capacités divines.'
            ],
            'Magicien' => [
                'primary' => ['Intelligence', 'Constitution'],
                'secondary' => ['Dextérité', 'Sagesse'],
                'description' => 'L\'Intelligence détermine la puissance des sorts, la Constitution aide à maintenir la concentration.'
            ],
            'Ensorceleur' => [
                'primary' => ['Charisme', 'Constitution'],
                'secondary' => ['Dextérité', 'Intelligence'],
                'description' => 'Le Charisme alimente les sorts, la Constitution aide à maintenir la concentration.'
            ],
            'Occultiste' => [
                'primary' => ['Charisme', 'Constitution'],
                'secondary' => ['Dextérité', 'Intelligence'],
                'description' => 'Le Charisme alimente les sorts, la Constitution aide à maintenir la concentration.'
            ],
            'Roublard' => [
                'primary' => ['Dextérité', 'Intelligence'],
                'secondary' => ['Constitution', 'Charisme'],
                'description' => 'La Dextérité améliore les attaques furtives, l\'Intelligence aide aux compétences.'
            ],
            'Rôdeur' => [
                'primary' => ['Dextérité', 'Sagesse'],
                'secondary' => ['Constitution', 'Intelligence'],
                'description' => 'La Dextérité pour les attaques à distance, la Sagesse pour les sorts et la perception.'
            ]
        ];
        
        return $recommendations[$className] ?? $recommendations['Guerrier'];
    }
}

/**
 * Finaliser la création d'un personnage (compatibilité)
 */
if (!function_exists('finalizeCharacterCreation')) {
    function finalizeCharacterCreation($userId, $sessionId) {
        $pdo = getPDO();
        
        try {
            // Récupérer les données de la session
            $sessionData = getCharacterCreationData($userId, $sessionId);
            if (!$sessionData || empty($sessionData['data'])) {
                error_log("Erreur finalizeCharacterCreation: Aucune donnée de session trouvée");
                return false;
            }
            
            $data = $sessionData['data'];
            
            // Vérifier que les données essentielles sont présentes
            if (empty($data['name']) || empty($data['class_id']) || empty($data['race_id'])) {
                error_log("Erreur finalizeCharacterCreation: Données essentielles manquantes");
                return false;
            }
            
            // Calculer les points de vie initiaux (classe de base + constitution)
            $constitution = $data['constitution'] ?? 10;
            $classId = $data['class_id'];
            
            // Récupérer les dés de vie de la classe
            $stmt = $pdo->prepare("SELECT hit_dice FROM classes WHERE id = ?");
            $stmt->execute([$classId]);
            $classData = $stmt->fetch(PDO::FETCH_ASSOC);
            $hitDice = $classData ? $classData['hit_dice'] : '1d8';
            
            // Calculer les PV max (dé de vie max + modificateur de constitution)
            $constitutionModifier = floor(($constitution - 10) / 2);
            $maxHitPoints = 0;
            
            // Parser le dé de vie (ex: "1d8" -> 8)
            if (preg_match('/(\d+)d(\d+)/', $hitDice, $matches)) {
                $diceCount = (int)$matches[1];
                $diceSize = (int)$matches[2];
                $maxHitPoints = ($diceCount * $diceSize) + $constitutionModifier;
            } else {
                $maxHitPoints = 8 + $constitutionModifier; // Valeur par défaut
            }
            
            // S'assurer que les PV ne sont pas négatifs
            $maxHitPoints = max(1, $maxHitPoints);
            
            // Insérer le personnage dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, class_archetype_id, level, experience_points,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, initiative, speed, hit_points_max, hit_points_current,
                    proficiency_bonus, money_gold, background, alignment,
                    personality_traits, ideals, bonds, flaws,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 1, 0,
                    ?, ?, ?, ?, ?, ?,
                    10, 0, 30, ?, ?,
                    2, 0, ?, ?,
                    ?, ?, ?, ?,
                    NOW(), NOW()
                )
            ");
            
            $result = $stmt->execute([
                $userId,
                $data['name'],
                $data['race_id'],
                $data['class_id'],
                $data['class_option_id'] ?? null, // Archetype choisi
                $data['strength'] ?? 10,
                $data['dexterity'] ?? 10,
                $data['constitution'] ?? 10,
                $data['intelligence'] ?? 10,
                $data['wisdom'] ?? 10,
                $data['charisma'] ?? 10,
                $maxHitPoints,
                $maxHitPoints, // PV actuels = PV max au début
                $data['background'] ?? null,
                $data['alignment'] ?? null,
                $data['personality_traits'] ?? null,
                $data['ideals'] ?? null,
                $data['bonds'] ?? null,
                $data['flaws'] ?? null
            ]);
            
            if ($result) {
                $characterId = $pdo->lastInsertId();
                error_log("Personnage créé avec succès - ID: $characterId");
                return $characterId;
            } else {
                error_log("Erreur lors de l'insertion du personnage");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur finalizeCharacterCreation: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Nettoyer une session de création de personnage (compatibilité)
 */
if (!function_exists('cleanupCharacterCreationSession')) {
    function cleanupCharacterCreationSession($userId, $sessionId) {
        // Pour l'instant, on retourne true pour permettre la création
        // TODO: Implémenter la logique complète avec nettoyage en base
        return true;
    }
}

/**
 * Nettoyer les sessions de création expirées (compatibilité)
 */
if (!function_exists('cleanupExpiredCharacterSessions')) {
    function cleanupExpiredCharacterSessions() {
        // Cette fonction nécessite une logique spécifique
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }
}
