<?php
/**
 * Script pour enregistrer l'équipement de départ du Soldat
 * avec la nouvelle structure de table starting_equipment
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
    
    // Inclure les fonctions d'auto-insertion
    require_once 'includes/object_auto_insert.php';
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU SOLDAT ===\n\n";
    
    // Vérifier que la table est vide pour le soldat
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 13");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Soldat: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1
    echo "Insertion du choix 1...\n";
    
    // (a) Un jeu de dés en os
    $jeuDesOsId = autoInsertObject($pdo, 'outils', 'Jeu de dés en os');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 13, 'outils', $jeuDesOsId, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Un jeu de dés en os (Object ID: $jeuDesOsId)\n";
    
    // (b) Un jeu de cartes
    $jeuCartesId = autoInsertObject($pdo, 'outils', 'Jeu de cartes');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 13, 'outils', $jeuCartesId, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Un jeu de cartes (Object ID: $jeuCartesId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Un insigne de grade
    $insigneGradeId = autoInsertObject($pdo, 'outils', 'Insigne de grade');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 13, 'outils', $insigneGradeId, 2, 'obligatoire', 1]);
    echo "  - Un insigne de grade (Object ID: $insigneGradeId)\n";
    
    // Un trophée pris sur un ennemi mort
    $tropheeEnnemiId = autoInsertObject($pdo, 'outils', 'Trophée pris sur un ennemi mort');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 13, 'outils', $tropheeEnnemiId, 2, 'obligatoire', 1]);
    echo "  - Un trophée pris sur un ennemi mort (Object ID: $tropheeEnnemiId)\n";
    
    // Des vêtements communs
    $vetementsCommunsId = autoInsertObject($pdo, 'outils', 'Vêtements communs');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 13, 'outils', $vetementsCommunsId, 2, 'obligatoire', 1]);
    echo "  - Des vêtements communs (Object ID: $vetementsCommunsId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 13");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 2 options (a) jeu de dés en os ou (b) jeu de cartes\n";
    echo "Équipement obligatoire: 3 items\n";
    echo "  - Un insigne de grade\n";
    echo "  - Un trophée pris sur un ennemi mort\n";
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
