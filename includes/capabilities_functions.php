<?php
/**
 * Fonctions pour le système homogène de gestion des capacités
 * Toutes les capacités sont maintenant stockées en base de données
 */

/**
 * Récupère toutes les capacités d'un personnage
 * @param int $character_id ID du personnage
 * @return array Liste des capacités avec leurs détails
 */
function getCharacterCapabilities($character_id) {
    global $pdo;
    
    // Debug temporaire
    error_log("Debug getCharacterCapabilities - Character ID: " . $character_id);
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color,
            c.source_type,
            c.level_requirement,
            cc.is_active,
            cc.notes,
            cc.obtained_at
        FROM character_capabilities cc
        JOIN capabilities c ON cc.capability_id = c.id
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE cc.character_id = ? AND cc.is_active = 1
        ORDER BY c.source_type, c.level_requirement, c.name
    ");
    
    $stmt->execute([$character_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug temporaire
    error_log("Debug getCharacterCapabilities - Result count: " . count($result));
    if (empty($result)) {
        // Vérifier si le personnage existe dans character_capabilities
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_capabilities WHERE character_id = ?");
        $checkStmt->execute([$character_id]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Debug getCharacterCapabilities - Character capabilities count in DB: " . $checkResult['count']);
    }
    
    return $result;
}

/**
 * Récupère les capacités disponibles pour une classe et un niveau donnés
 * @param int $class_id ID de la classe
 * @param int $level Niveau du personnage
 * @return array Liste des capacités de classe
 */
function getClassCapabilities($class_id, $level) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color,
            c.level_requirement
        FROM capabilities c
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE c.source_type = 'class' 
        AND c.source_id = ? 
        AND c.level_requirement <= ?
        AND c.is_active = 1
        ORDER BY c.level_requirement, c.name
    ");
    
    $stmt->execute([$class_id, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les capacités raciales pour une race donnée
 * @param int $race_id ID de la race
 * @return array Liste des capacités raciales
 */
function getRaceCapabilities($race_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color
        FROM capabilities c
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE c.source_type = 'race' 
        AND c.source_id = ?
        AND c.is_active = 1
        ORDER BY c.name
    ");
    
    $stmt->execute([$race_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les capacités d'historique pour un historique donné
 * @param int $background_id ID de l'historique
 * @return array Liste des capacités d'historique
 */
function getBackgroundCapabilities($background_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color
        FROM capabilities c
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE c.source_type = 'background' 
        AND c.source_id = ?
        AND c.is_active = 1
        ORDER BY c.name
    ");
    
    $stmt->execute([$background_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ajoute une capacité à un personnage
 * @param int $character_id ID du personnage
 * @param int $capability_id ID de la capacité
 * @param string $notes Notes optionnelles
 * @return bool Succès de l'opération
 */
function addCharacterCapability($character_id, $capability_id, $notes = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_capabilities (character_id, capability_id, notes)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            is_active = 1,
            notes = VALUES(notes)
        ");
        
        return $stmt->execute([$character_id, $capability_id, $notes]);
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout de capacité: " . $e->getMessage());
        return false;
    }
}

/**
 * Retire une capacité d'un personnage
 * @param int $character_id ID du personnage
 * @param int $capability_id ID de la capacité
 * @return bool Succès de l'opération
 */
function removeCharacterCapability($character_id, $capability_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE character_capabilities 
            SET is_active = 0 
            WHERE character_id = ? AND capability_id = ?
        ");
        
        return $stmt->execute([$character_id, $capability_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de capacité: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour automatiquement les capacités d'un personnage selon sa classe, race et niveau
 * @param int $character_id ID du personnage
 * @return bool Succès de l'opération
 */
function updateCharacterCapabilities($character_id) {
    global $pdo;
    
    try {
        // Récupérer les informations du personnage
        $stmt = $pdo->prepare("
            SELECT c.class_id, c.race_id, c.background_id, c.level
            FROM characters c
            WHERE c.id = ?
        ");
        $stmt->execute([$character_id]);
        $character = $stmt->fetch();
        
        if (!$character) {
            return false;
        }
        
        // Désactiver toutes les capacités actuelles
        $stmt = $pdo->prepare("
            UPDATE character_capabilities 
            SET is_active = 0 
            WHERE character_id = ?
        ");
        $stmt->execute([$character_id]);
        
        // Ajouter les capacités de classe
        $classCapabilities = getClassCapabilities($character['class_id'], $character['level']);
        foreach ($classCapabilities as $capability) {
            addCharacterCapability($character_id, $capability['id']);
        }
        
        // Ajouter les capacités raciales
        $raceCapabilities = getRaceCapabilities($character['race_id']);
        foreach ($raceCapabilities as $capability) {
            addCharacterCapability($character_id, $capability['id']);
        }
        
        // Ajouter les capacités d'historique
        if ($character['background_id']) {
            $backgroundCapabilities = getBackgroundCapabilities($character['background_id']);
            foreach ($backgroundCapabilities as $capability) {
                addCharacterCapability($character_id, $capability['id']);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour des capacités: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère toutes les capacités disponibles par type
 * @return array Liste des capacités groupées par type
 */
function getAllCapabilitiesByType() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color,
            c.source_type,
            c.source_id,
            c.level_requirement
        FROM capabilities c
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE c.is_active = 1
        ORDER BY ct.name, c.source_type, c.level_requirement, c.name
    ");
    
    $stmt->execute();
    $capabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Grouper par type
    $grouped = [];
    foreach ($capabilities as $capability) {
        $grouped[$capability['type_name']][] = $capability;
    }
    
    return $grouped;
}

/**
 * Recherche des capacités par nom ou description
 * @param string $search Terme de recherche
 * @return array Liste des capacités correspondantes
 */
function searchCapabilities($search) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.name,
            c.description,
            ct.name as type_name,
            ct.icon,
            ct.color,
            c.source_type,
            c.level_requirement
        FROM capabilities c
        JOIN capability_types ct ON c.type_id = ct.id
        WHERE c.is_active = 1
        AND (c.name LIKE ? OR c.description LIKE ?)
        ORDER BY c.name
    ");
    
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Les fonctions de compatibilité sont définies dans includes/functions.php
// pour éviter les conflits de redéclaration
?>
