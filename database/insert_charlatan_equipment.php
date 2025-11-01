<?php
/**
 * Script pour enregistrer l'équipement de départ du Charlatan
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU CHARLATAN ===\n\n";
    
    // Vérifier que la table est vide pour le charlatan
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 4");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Charlatan: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Des vêtements fins
    $vetementsFinsId = autoInsertObject($pdo, 'outils', 'Vêtements fins');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 4, 'outils', $vetementsFinsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements fins (Object ID: $vetementsFinsId)\n";
    
    // Un kit de déguisement
    $kitDeguisementId = autoInsertObject($pdo, 'outils', 'Kit de déguisement');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 4, 'outils', $kitDeguisementId, 1, 'obligatoire', 1]);
    echo "  - Un kit de déguisement (Object ID: $kitDeguisementId)\n";
    
    // Des outils d'escroquerie
    $outilsEscroquerieId = autoInsertObject($pdo, 'outils', 'Outils d\'escroquerie');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 4, 'outils', $outilsEscroquerieId, 1, 'obligatoire', 1]);
    echo "  - Des outils d'escroquerie (Object ID: $outilsEscroquerieId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 4");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 3 items\n";
    echo "  - Des vêtements fins\n";
    echo "  - Un kit de déguisement\n";
    echo "  - Des outils d'escroquerie\n";
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
