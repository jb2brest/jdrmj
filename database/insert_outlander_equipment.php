<?php
/**
 * Script pour enregistrer l'équipement de départ du Sauvageon
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU SAUVAGEON ===\n\n";
    
    // Vérifier que la table est vide pour le sauvageon
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 12");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Sauvageon: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Un bâton
    $batonId = autoInsertObject($pdo, 'outils', 'Bâton');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 12, 'outils', $batonId, 1, 'obligatoire', 1]);
    echo "  - Un bâton (Object ID: $batonId)\n";
    
    // Un piège à mâchoires
    $piegeMachoiresId = autoInsertObject($pdo, 'outils', 'Piège à mâchoires');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 12, 'outils', $piegeMachoiresId, 1, 'obligatoire', 1]);
    echo "  - Un piège à mâchoires (Object ID: $piegeMachoiresId)\n";
    
    // Un trophée d'animal que vous avez tué
    $tropheeAnimalId = autoInsertObject($pdo, 'outils', 'Trophée d\'animal que vous avez tué');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 12, 'outils', $tropheeAnimalId, 1, 'obligatoire', 1]);
    echo "  - Un trophée d'animal que vous avez tué (Object ID: $tropheeAnimalId)\n";
    
    // Des vêtements de voyage
    $vetementsVoyageId = autoInsertObject($pdo, 'outils', 'Vêtements de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 12, 'outils', $vetementsVoyageId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements de voyage (Object ID: $vetementsVoyageId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 12");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 4 items\n";
    echo "  - Un bâton\n";
    echo "  - Un piège à mâchoires\n";
    echo "  - Un trophée d'animal que vous avez tué\n";
    echo "  - Des vêtements de voyage\n";
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
