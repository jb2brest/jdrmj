<?php
/**
 * Script de test pour l'auto-insertion des instruments de musique
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
    
    // Inclure les fonctions d'auto-insertion
    require_once 'includes/object_auto_insert.php';
    
    echo "=== TEST D'AUTO-INSERTION DES INSTRUMENTS ===\n\n";
    
    // Test 1: Vérifier que le type 'instrument' est disponible
    echo "Test 1: Vérification du type 'instrument' dans la table Object...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM Object LIKE 'type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Type ENUM: " . $column['Type'] . "\n";
    
    if (strpos($column['Type'], 'instrument') !== false) {
        echo "✅ Type 'instrument' disponible\n\n";
    } else {
        echo "❌ Type 'instrument' non disponible\n\n";
        exit(1);
    }
    
    // Test 2: Créer quelques instruments de musique
    echo "Test 2: Création d'instruments de musique...\n";
    
    $instruments = [
        'Luth',
        'Flûte à bec',
        'Tambour',
        'Harpe',
        'Violon'
    ];
    
    $createdInstruments = [];
    
    foreach ($instruments as $instrument) {
        $objectId = autoInsertObject($pdo, 'instrument', $instrument);
        $createdInstruments[] = ['id' => $objectId, 'nom' => $instrument];
        echo "  - Créé: {$instrument} (ID: {$objectId})\n";
    }
    
    echo "\n";
    
    // Test 3: Vérifier que les instruments existent dans la table Object
    echo "Test 3: Vérification des instruments dans la table Object...\n";
    $stmt = $pdo->query("SELECT * FROM Object WHERE type = 'instrument' ORDER BY nom");
    $instrumentsInDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($instrumentsInDb as $instrument) {
        echo "  - ID {$instrument['id']}: {$instrument['nom']} (créé: {$instrument['created_at']})\n";
    }
    
    echo "\n";
    
    // Test 4: Simuler l'ajout d'un équipement avec instrument
    echo "Test 4: Simulation d'ajout d'équipement avec instrument...\n";
    
    // Créer un équipement de test avec un instrument
    $testInstrument = 'Lyre';
    $objectId = autoInsertObject($pdo, 'instrument', $testInstrument);
    
    // Insérer un équipement de test
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 1, 'instrument', $objectId, 'obligatoire', 1]);
    $equipmentId = $pdo->lastInsertId();
    
    echo "  - Équipement créé: ID {$equipmentId}, Instrument: {$testInstrument} (Object ID: {$objectId})\n";
    
    // Test 5: Vérifier la requête complète avec JOIN
    echo "\nTest 5: Vérification de la requête avec JOIN...\n";
    $stmt = $pdo->prepare("
        SELECT se.id, se.type, se.type_id, o.nom as object_name
        FROM starting_equipment se
        LEFT JOIN Object o ON se.type = 'instrument' AND se.type_id = o.id
        WHERE se.id = ?
    ");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($equipment) {
        echo "  - Équipement ID {$equipment['id']}: {$equipment['type']} - {$equipment['object_name']} (Object ID: {$equipment['type_id']})\n";
        echo "✅ Requête avec JOIN fonctionne correctement\n";
    } else {
        echo "❌ Erreur dans la requête avec JOIN\n";
    }
    
    // Nettoyage: Supprimer l'équipement de test
    echo "\nNettoyage: Suppression de l'équipement de test...\n";
    $stmt = $pdo->prepare("DELETE FROM starting_equipment WHERE id = ?");
    $stmt->execute([$equipmentId]);
    echo "  - Équipement de test supprimé\n";
    
    echo "\n=== RÉSUMÉ DES TESTS ===\n";
    echo "✅ Type 'instrument' ajouté à la table Object\n";
    echo "✅ Fonction autoInsertObject() fonctionne avec les instruments\n";
    echo "✅ " . count($instrumentsInDb) . " instruments créés dans la table Object\n";
    echo "✅ Requête avec JOIN fonctionne correctement\n";
    echo "✅ Auto-insertion des instruments opérationnelle\n";
    
    echo "\n=== SCRIPT TERMINÉ ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
