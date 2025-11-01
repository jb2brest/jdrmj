<?php
/**
 * Script pour enregistrer l'équipement de départ du Noble
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU NOBLE ===\n\n";
    
    // Vérifier que la table est vide pour le noble
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 10");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Noble: $count\n\n";
    
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
    $stmt->execute(['background', 10, 'outils', $vetementsFinsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements fins (Object ID: $vetementsFinsId)\n";
    
    // Une chevalière
    $chevaliereId = autoInsertObject($pdo, 'outils', 'Chevalière');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 10, 'outils', $chevaliereId, 1, 'obligatoire', 1]);
    echo "  - Une chevalière (Object ID: $chevaliereId)\n";
    
    // Une lettre de noblesse
    $lettreNoblesseId = autoInsertObject($pdo, 'outils', 'Lettre de noblesse');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 10, 'outils', $lettreNoblesseId, 1, 'obligatoire', 1]);
    echo "  - Une lettre de noblesse (Object ID: $lettreNoblesseId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 10");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 3 items\n";
    echo "  - Des vêtements fins\n";
    echo "  - Une chevalière\n";
    echo "  - Une lettre de noblesse\n";
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
