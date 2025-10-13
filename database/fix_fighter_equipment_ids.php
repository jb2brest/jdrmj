<?php
/**
 * Script pour corriger les IDs incorrects dans l'équipement du Guerrier
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
    
    echo "=== CORRECTION DES IDs DE L'ÉQUIPEMENT DU GUERRIER ===\n\n";
    
    // Corrections à effectuer
    $corrections = [
        // ID 127: Arc long (ID incorrect: 12, correct: 35)
        ['id' => 127, 'type_id' => 35, 'description' => 'Arc long'],
        
        // ID 128: Flèches (ID incorrect: 13, correct: 13 - déjà correct)
        ['id' => 128, 'type_id' => 13, 'description' => 'Flèches'],
        
        // ID 141: Hachettes (ID incorrect: 14, correct: 4)
        ['id' => 141, 'type_id' => 4, 'description' => 'Hachettes']
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
                   WHEN se.type = 'bouclier' AND se.type_id IS NOT NULL THEN a.name
                   WHEN se.type = 'outils' AND se.type_id IS NOT NULL THEN o.nom
                   ELSE 'N/A'
               END as object_name
        FROM starting_equipment se
        LEFT JOIN weapons w ON se.type = 'weapon' AND se.type_id = w.id
        LEFT JOIN armor a ON (se.type = 'armor' OR se.type = 'bouclier') AND se.type_id = a.id
        LEFT JOIN Object o ON se.type = 'outils' AND se.type_id = o.id
        WHERE se.src = 'class' AND se.src_id = 6 
        AND se.id IN (127, 128, 141)
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
