<?php
/**
 * Script pour corriger les IDs incorrects dans l'équipement du Clerc
 */

// Configuration de la base de données
$config = include_once 'config/database.test.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "=== CORRECTION DES IDs DE L'ÉQUIPEMENT DU CLERC ===\n\n";
    
    // Corrections à effectuer
    $corrections = [
        // ID 56: Masse d'armes (ID incorrect: 4, correct: 8)
        ['id' => 56, 'type_id' => 8, 'description' => 'Masse d\'armes'],
        
        // ID 57: Marteau de guerre (ID incorrect: 5, correct: 27)
        ['id' => 57, 'type_id' => 27, 'description' => 'Marteau de guerre'],
        
        // ID 58: Armure d'écailles (ID incorrect: 3, correct: 6)
        ['id' => 58, 'type_id' => 6, 'description' => 'Armure d\'écailles'],
        
        // ID 60: Cotte de mailles (ID incorrect: 4, correct: 10)
        ['id' => 60, 'type_id' => 10, 'description' => 'Cotte de mailles'],
        
        // ID 61: Arbalète légère (ID incorrect: 6, correct: 11)
        ['id' => 61, 'type_id' => 11, 'description' => 'Arbalète légère'],
        
        // ID 62: Carreaux (ID incorrect: 7, correct: NULL - à créer)
        ['id' => 62, 'type_id' => null, 'description' => 'Carreaux (à créer)'],
        
        // ID 85: Bouclier (ID incorrect: 1, correct: 13)
        ['id' => 85, 'type_id' => 13, 'description' => 'Bouclier']
    ];
    
    echo "Correction des IDs...\n";
    
    foreach ($corrections as $correction) {
        if ($correction['type_id'] !== null) {
            $stmt = $pdo->prepare("UPDATE starting_equipment SET type_id = ? WHERE id = ?");
            $stmt->execute([$correction['type_id'], $correction['id']]);
            echo "  - ID {$correction['id']}: {$correction['description']} -> type_id {$correction['type_id']}\n";
        } else {
            echo "  - ID {$correction['id']}: {$correction['description']} -> À traiter séparément\n";
        }
    }
    
    // Pour les carreaux, on va créer un objet dans la table Object
    echo "\nCréation de l'objet 'Carreaux' dans la table Object...\n";
    $stmt = $pdo->prepare("INSERT INTO Object (type, nom) VALUES (?, ?)");
    $stmt->execute(['outils', 'Carreaux']);
    $carreauxObjectId = $pdo->lastInsertId();
    
    // Mettre à jour l'équipement des carreaux
    $stmt = $pdo->prepare("UPDATE starting_equipment SET type = ?, type_id = ? WHERE id = ?");
    $stmt->execute(['outils', $carreauxObjectId, 62]);
    echo "  - Carreaux créés comme outil (Object ID: $carreauxObjectId)\n";
    
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
        WHERE se.src = 'class' AND se.src_id = 3 
        AND se.id IN (56, 57, 58, 60, 61, 62, 85)
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
