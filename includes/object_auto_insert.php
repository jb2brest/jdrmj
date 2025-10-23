<?php
/**
 * Fonctions pour l'auto-insertion des objets dans la table Object
 * lorsqu'ils sont rencontrés pour la première fois
 */

/**
 * Insère automatiquement un objet dans la table Object s'il n'existe pas
 * @param PDO $pdo Connexion à la base de données
 * @param string $type Type d'objet (sac, outils, nourriture, accessoire, instrument)
 * @param string $nom Nom de l'objet
 * @return int ID de l'objet (existant ou nouvellement créé)
 */
function autoInsertObject($pdo, $type, $nom) {
    // Vérifier si l'objet existe déjà
    $stmt = $pdo->prepare("SELECT id FROM Object WHERE type = ? AND nom = ?");
    $stmt->execute([$type, $nom]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        return $existing['id'];
    }
    
    // Insérer le nouvel objet
    $stmt = $pdo->prepare("INSERT INTO Object (type, nom) VALUES (?, ?)");
    $stmt->execute([$type, $nom]);
    
    return $pdo->lastInsertId();
}

/**
 * Met à jour les équipements de départ pour lier les objets aux entrées de la table Object
 * @param PDO $pdo Connexion à la base de données
 * @param array $objectMappings Mappings des objets à créer
 * @return array Résultats des insertions
 */
function updateStartingEquipmentObjects($pdo, $objectMappings) {
    $results = [];
    
    foreach ($objectMappings as $mapping) {
        $equipmentId = $mapping['equipment_id'];
        $type = $mapping['type'];
        $nom = $mapping['nom'];
        
        // Insérer l'objet s'il n'existe pas
        $objectId = autoInsertObject($pdo, $type, $nom);
        
        // Mettre à jour l'équipement de départ
        $stmt = $pdo->prepare("UPDATE starting_equipment SET type_id = ? WHERE id = ?");
        $stmt->execute([$objectId, $equipmentId]);
        
        $results[] = [
            'equipment_id' => $equipmentId,
            'object_id' => $objectId,
            'type' => $type,
            'nom' => $nom,
            'action' => 'updated'
        ];
    }
    
    return $results;
}

/**
 * Analyse les équipements de départ et suggère les objets à créer
 * @param PDO $pdo Connexion à la base de données
 * @return array Liste des objets suggérés
 */
function analyzeMissingObjects($pdo) {
    $stmt = $pdo->query("
        SELECT se.id, se.type, se.groupe_id, se.nb
        FROM starting_equipment se
        WHERE se.type IN ('sac', 'outils', 'nourriture', 'accessoire', 'instrument')
        AND se.type_id IS NULL
        ORDER BY se.type, se.groupe_id, se.id
    ");
    
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $suggestions = [];
    
    foreach ($equipment as $item) {
        $suggestion = [
            'equipment_id' => $item['id'],
            'type' => $item['type'],
            'nom' => '',
            'groupe_id' => $item['groupe_id'],
            'nb' => $item['nb']
        ];
        
        // Suggestions basées sur le contexte du Barbare
        switch ($item['type']) {
            case 'sac':
                if ($item['groupe_id'] == 3) { // Groupe obligatoire du Barbare
                    $suggestion['nom'] = 'Sac à dos';
                }
                break;
                
            case 'outils':
                if ($item['groupe_id'] == 3) { // Groupe obligatoire du Barbare
                    switch ($item['nb']) {
                        case 1:
                            // Premier outil - Sac de couchage
                            $suggestion['nom'] = 'Sac de couchage';
                            break;
                        case 1:
                            // Deuxième outil - Gamelle
                            $suggestion['nom'] = 'Gamelle';
                            break;
                        case 1:
                            // Troisième outil - Boite d'allume-feu
                            $suggestion['nom'] = 'Boite d\'allume-feu';
                            break;
                        case 10:
                            // Quatrième outil - Torches
                            $suggestion['nom'] = 'Torche';
                            break;
                        case 1:
                            // Cinquième outil - Corde
                            $suggestion['nom'] = 'Corde de chanvre (15m)';
                            break;
                    }
                }
                break;
                
            case 'nourriture':
                if ($item['groupe_id'] == 3) { // Groupe obligatoire du Barbare
                    switch ($item['nb']) {
                        case 10:
                            // Rations de voyage
                            $suggestion['nom'] = 'Rations de voyage';
                            break;
                        case 1:
                            // Gourde d'eau
                            $suggestion['nom'] = 'Gourde d\'eau';
                            break;
                    }
                }
                break;
                
            case 'instrument':
                // Instruments de musique - suggestions génériques
                $suggestion['nom'] = 'Instrument de musique';
                break;
        }
        
        if (!empty($suggestion['nom'])) {
            $suggestions[] = $suggestion;
        }
    }
    
    return $suggestions;
}

/**
 * Traite automatiquement tous les objets manquants
 * @param PDO $pdo Connexion à la base de données
 * @return array Résultats du traitement
 */
function processAllMissingObjects($pdo) {
    $suggestions = analyzeMissingObjects($pdo);
    return updateStartingEquipmentObjects($pdo, $suggestions);
}
?>
