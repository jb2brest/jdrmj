<?php
/**
 * Script pour enregistrer l'équipement de départ du Marin
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU MARIN ===\n\n";
    
    // Vérifier que la table est vide pour le marin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 9");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Marin: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Un cabillot d'amarrage
    $cabillotAmarrageId = autoInsertObject($pdo, 'outils', 'Cabillot d\'amarrage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 9, 'outils', $cabillotAmarrageId, 1, 'obligatoire', 1]);
    echo "  - Un cabillot d'amarrage (Object ID: $cabillotAmarrageId)\n";
    
    // Une corde en soie de 15m
    $cordeSoieId = autoInsertObject($pdo, 'outils', 'Corde en soie de 15m');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 9, 'outils', $cordeSoieId, 1, 'obligatoire', 1]);
    echo "  - Une corde en soie de 15m (Object ID: $cordeSoieId)\n";
    
    // Un porte bonheur
    $porteBonheurId = autoInsertObject($pdo, 'outils', 'Porte bonheur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 9, 'outils', $porteBonheurId, 1, 'obligatoire', 1]);
    echo "  - Un porte bonheur (Object ID: $porteBonheurId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 9, 'outils', $vetementsCommunsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 9");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 4 items\n";
    echo "  - Un cabillot d'amarrage\n";
    echo "  - Une corde en soie de 15m\n";
    echo "  - Un porte bonheur\n";
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
