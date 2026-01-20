<?php
require_once 'classes/init.php';
// ===== FONCTIONS UTILITAIRES POUR LA CRÉATION DE MONSTRES =====

/**
 * Fonction pour créer un ou plusieurs monstres automatiquement
 * 
 * @param int $world_id ID du monde
 * @param int $country_id ID du pays
 * @param int $place_id ID de la pièce
 * @param int $count Nombre de monstres à créer
 * @param string|null $monster_type Type de monstre (optionnel)
 * @param int $is_visible Visibilité pour les joueurs
 * @param int $is_identified Identification pour les joueurs
 * @param string|null $challenge_rating Facteur de Puissance spécifique (optionnel, ex: "1/4", "5", etc.)
 * @return array|false Informations des monstres créés ou false en cas d'erreur
 */
function createAutomaticMonsters($world_id, $country_id, $place_id, $count, $monster_type = null, $is_visible = 1, $is_identified = 1, $challenge_rating = null) {
    $pdo = getPdo();
    
    error_log("=== DEBUG MONSTER CREATION ===");
    error_log("Variables reçues:");
    error_log("- world_id: " . $world_id);
    error_log("- country_id: " . $country_id);
    error_log("- place_id: " . $place_id);
    error_log("- count: " . $count);
    error_log("- monster_type: " . ($monster_type ?? 'NULL'));
    error_log("- challenge_rating: " . ($challenge_rating ?? 'NULL'));
    error_log("- is_visible: " . $is_visible);
    error_log("- is_identified: " . $is_identified);
    
    try {
        // Construire la clause WHERE pour filtrer par type et FP si nécessaire
        $where_clause = "1=1";
        $params = [];
        
        if ($monster_type) {
            $where_clause .= " AND type = ?";
            $params[] = $monster_type;
        }
        
        if ($challenge_rating) {
            $where_clause .= " AND challenge_rating = ?";
            $params[] = $challenge_rating;
        }
        
        error_log("WHERE clause: " . $where_clause);
        error_log("Params count: " . count($params));
        
        // Récupérer des monstres aléatoires depuis dnd_monsters
        $sql = "SELECT id, name, type, size, challenge_rating, hit_points FROM dnd_monsters WHERE $where_clause ORDER BY RAND() LIMIT ?";
        error_log("SQL query: " . $sql);
        $stmt = $pdo->prepare($sql);
        $params[] = $count;
        error_log("Final params: " . print_r($params, true));
        $stmt->execute($params);
        $monsters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Monsters found: " . count($monsters));
        
        if (empty($monsters)) {
            error_log("ERREUR: Aucun monstre trouvé avec les critères spécifiés");
            return false;
        }
        
        $created_monsters = [];
        $created_count = 0;
        
        foreach ($monsters as $monster) {
            // Générer un nom unique si plusieurs monstres
            $name = $monster['name'];
            if ($count > 1) {
                $name .= ' ' . ($created_count + 1);
            }
            
            // Générer la description
            $description = "Monstre généré automatiquement de type " . $monster['type'] . " (" . $monster['size'] . ").";
            
            // Utiliser les PV par défaut du monstre
            $hit_points = $monster['hit_points'] ?? 1;
            
            error_log("Création monstre: name=$name, description=$description, place_id=$place_id, monster_id=" . $monster['id'] . ", hit_points=$hit_points");
            
            try {
                // Créer l'entrée dans place_npcs
                $stmt = $pdo->prepare("
                    INSERT INTO place_npcs (
                        name, description, profile_photo, is_visible, is_identified, 
                        place_id, monster_id, quantity, current_hit_points
                    ) 
                    VALUES (?, ?, NULL, ?, ?, ?, ?, 1, ?)
                ");
                $result = $stmt->execute([
                    $name,
                    $description,
                    $is_visible,
                    $is_identified,
                    $place_id,
                    $monster['id'],
                    $hit_points
                ]);
                
                if ($result) {
                    $insert_id = $pdo->lastInsertId();
                    error_log("SUCCESS: Monstre créé avec ID place_npcs: " . $insert_id);
                    
                    $created_monsters[] = [
                        'id' => $insert_id,
                        'name' => $name,
                        'monster_type' => $monster['type'],
                        'monster_id' => $monster['id']
                    ];
                    
                    $created_count++;
                } else {
                    error_log("ERREUR: Échec de l'insertion pour le monstre: " . $monster['name']);
                }
            } catch (PDOException $e) {
                error_log("ERREUR SQL lors de la création du monstre: " . $e->getMessage());
                error_log("SQL State: " . $e->getCode());
            }
        }
        
        error_log("Total créé: " . $created_count . " monstre(s)");
        
        if ($created_count == 0) {
            error_log("ERREUR: Aucun monstre n'a pu être créé");
            return false;
        }
        
        return [
            'count' => $created_count,
            'monsters' => $created_monsters
        ];
        
    } catch (Exception $e) {
        error_log("EXCEPTION lors de la création automatique de monstres: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

