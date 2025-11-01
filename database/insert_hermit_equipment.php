<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Ermite
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'ERMITE ===\n\n";
    
    // Vérifier que la table est vide pour l'ermite
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 7");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Ermite: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "Insertion de l'équipement obligatoire...\n";
    
    // Un étui à parchemin remplis de notes sur vos études ou vos prières
    $etuiParcheminId = autoInsertObject($pdo, 'outils', 'Étui à parchemin remplis de notes sur vos études ou vos prières');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 7, 'outils', $etuiParcheminId, 1, 'obligatoire', 1]);
    echo "  - Un étui à parchemin remplis de notes sur vos études ou vos prières (Object ID: $etuiParcheminId)\n";
    
    // Une couverture pour l'hiver
    $couvertureHiverId = autoInsertObject($pdo, 'outils', 'Couverture pour l\'hiver');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 7, 'outils', $couvertureHiverId, 1, 'obligatoire', 1]);
    echo "  - Une couverture pour l'hiver (Object ID: $couvertureHiverId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 7, 'outils', $vetementsCommunsId, 1, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Un kit d'herboriste
    $kitHerboristeId = autoInsertObject($pdo, 'outils', 'Kit d\'herboriste');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 7, 'outils', $kitHerboristeId, 1, 'obligatoire', 1]);
    echo "  - Un kit d'herboriste (Object ID: $kitHerboristeId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 7");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Équipement obligatoire: 4 items\n";
    echo "  - Un étui à parchemin remplis de notes sur vos études ou vos prières\n";
    echo "  - Une couverture pour l'hiver\n";
    echo "  - Des vêtements communs\n";
    echo "  - Un kit d'herboriste\n";
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
