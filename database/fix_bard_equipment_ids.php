<?php
/**
 * Script pour corriger les IDs incorrects dans l'équipement du Barde
 */

// Configuration de la base de données
$config = include 'config/database.test.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "=== CORRECTION DES IDs DE L'ÉQUIPEMENT DU BARDE ===\n\n";
    
    // Corrections à effectuer
    $corrections = [
        // ID 30: Rapière (ID incorrect: 1, correct: 31)
        ['id' => 30, 'type_id' => 31, 'description' => 'Rapière'],
        
        // ID 31: Épée longue (ID incorrect: 2, correct: 19)
        ['id' => 31, 'type_id' => 19, 'description' => 'Épée longue'],
        
        // ID 54: Armure de cuir (ID incorrect: 1, correct: 2)
        ['id' => 54, 'type_id' => 2, 'description' => 'Armure de cuir'],
        
        // ID 55: Dague (ID incorrect: 3, correct: 2)
        ['id' => 55, 'type_id' => 2, 'description' => 'Dague']
    ];
    
    echo "Correction des IDs...\n";
    
    foreach ($corrections as $correction) {
        $stmt = $pdo->prepare("UPDATE starting_equipment SET type_id = ? WHERE id = ?");
        $stmt->execute([$correction['type_id'], $correction['id']]);
        echo "  - ID {$correction['id']}: {$correction['description']} -> type_id {$correction['type_id']}\n";
    }
    
    echo "\n✅ Corrections terminées!\n\n";
    
    // Vérifier les résultats
    echo "Vérification des corrections...\n";
    $stmt = $pdo->query("
        SELECT se.id, se.type, se.type_id, 
               CASE 
                   WHEN se.type = 'weapon' AND se.type_id IS NOT NULL THEN w.name
                   WHEN se.type = 'armor' AND se.type_id IS NOT NULL THEN a.name
                   ELSE 'N/A'
               END as object_name
        FROM starting_equipment se
        LEFT JOIN weapons w ON se.type = 'weapon' AND se.type_id = w.id
        LEFT JOIN armor a ON se.type = 'armor' AND se.type_id = a.id
        WHERE se.src = 'class' AND se.src_id = 2 
        AND se.id IN (30, 31, 54, 55)
        ORDER BY se.id
    ");
    
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "État final des équipements corrigés:\n";
    foreach ($equipment as $item) {
        echo "  ✅ ID {$item['id']}: {$item['type']} - {$item['object_name']} (type_id: {$item['type_id']})\n";
    }
    
    echo "\n=== SCRIPT TERMINÉ ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
