<?php
/**
 * Script de correction pour mettre à jour le flag is_equipped des personnages
 * qui ont des items d'équipement de départ mais is_equipped = 0
 */

require_once 'classes/init.php';

$pdo = getPDO();

try {
    // Trouver tous les personnages qui ont des items d'équipement de départ mais is_equipped = 0
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name, c.user_id, 
               COUNT(i.id) as equipment_count
        FROM characters c
        INNER JOIN items i ON (i.owner_type = 'player' AND i.owner_id = c.id AND i.obtained_from = 'Équipement de départ')
        WHERE c.is_equipped = 0
        GROUP BY c.id
        HAVING equipment_count > 0
        ORDER BY c.id
    ");
    $stmt->execute();
    $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($characters)) {
        echo "Aucun personnage à corriger.\n";
        exit(0);
    }
    
    echo "Personnages à corriger: " . count($characters) . "\n\n";
    
    $updated = 0;
    foreach ($characters as $char) {
        echo "Personnage ID: {$char['id']}, Nom: {$char['name']}, Items d'équipement: {$char['equipment_count']}\n";
        
        $updateStmt = $pdo->prepare("UPDATE characters SET is_equipped = 1 WHERE id = ?");
        if ($updateStmt->execute([$char['id']])) {
            $updated++;
            echo "  -> Mis à jour: is_equipped = 1\n";
        } else {
            echo "  -> ERREUR lors de la mise à jour\n";
        }
    }
    
    echo "\nTotal mis à jour: {$updated} personnage(s)\n";
    
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

