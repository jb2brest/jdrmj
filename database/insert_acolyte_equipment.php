<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Acolyte
 * avec la nouvelle structure de table starting_equipment
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'ACOLYTE ===\n\n";
    
    // Vérifier que la table est vide pour l'acolyte
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 1");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Acolyte: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Un symbole sacré de sacerdoce
    $symboleSacerdoceId = autoInsertObject($pdo, 'outils', 'Symbole sacré de sacerdoce');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 1, 'outils', $symboleSacerdoceId, 1, 'obligatoire', 1]);
    echo "  - Un symbole sacré de sacerdoce (Object ID: $symboleSacerdoceId)\n";
    
    // Un livre de prières
    $livrePrieresId = autoInsertObject($pdo, 'outils', 'Livre de prières');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 1, 'outils', $livrePrieresId, 1, 'obligatoire', 1]);
    echo "  - Un livre de prières (Object ID: $livrePrieresId)\n";
    
    // 5 bâtons d'encens
    $batonsEncensId = autoInsertObject($pdo, 'outils', 'Bâtons d\'encens');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 1, 'outils', $batonsEncensId, 1, 'obligatoire', 5]);
    echo "  - 5 bâtons d'encens (Object ID: $batonsEncensId)\n";
    
    // Des habits de cérémonie
    $habitsCeremonieId = autoInsertObject($pdo, 'outils', 'Habits de cérémonie');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 1, 'outils', $habitsCeremonieId, 1, 'obligatoire', 1]);
    echo "  - Des habits de cérémonie (Object ID: $habitsCeremonieId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 1, 'outils', $vetementsCommunsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 1");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 5 items\n";
    echo "  - Un symbole sacré de sacerdoce\n";
    echo "  - Un livre de prières\n";
    echo "  - 5 bâtons d'encens\n";
    echo "  - Des habits de cérémonie\n";
    echo "  - Des vêtements communs\n";
    echo "Total: " . ($count_after - $count) . " enregistrements ajoutés\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERREUR lors de l'insertion: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SCRIPT TERMINÉ ===\n";
?>
