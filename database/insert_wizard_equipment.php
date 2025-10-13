<?php
/**
 * Script pour enregistrer l'équipement de départ du Magicien
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU MAGICIEN ===\n\n";
    
    // Vérifier que la table est vide pour le magicien
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 7");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Magicien: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Arme
    echo "Insertion du Choix 1 (Arme)...\n";
    
    // (a) un bâton
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'weapon', 15, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Un bâton (ID: 15)\n";
    
    // (b) une dague
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'weapon', 2, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Une dague (ID: 2)\n";
    
    // CHOIX 2: Sacoche ou Focaliseur
    echo "\nInsertion du Choix 2 (Sacoche ou Focaliseur)...\n";
    
    // (a) une sacoche à composantes
    $sacocheId = autoInsertObject($pdo, 'sac', 'Sacoche à composantes');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'sac', $sacocheId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - (a) Une sacoche à composantes (Object ID: $sacocheId)\n";
    
    // (b) un focaliseur arcanique
    $focaliseurId = autoInsertObject($pdo, 'outils', 'Focaliseur arcanique');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $focaliseurId, 2, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) Un focaliseur arcanique (Object ID: $focaliseurId)\n";
    
    // CHOIX 3: Sac d'équipement
    echo "\nInsertion du Choix 3 (Sac d'équipement)...\n";
    
    // (a) groupe sac d'explorateur
    echo "  - (a) Sac d'explorateur:\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'sac', $sacId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $sacCouchageId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // Une gamelle
    $gamelleId = autoInsertObject($pdo, 'outils', 'Gamelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $gamelleId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une gamelle (Object ID: $gamelleId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $allumeFeuId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $torcheId, 3, 'a', 3, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'nourriture', $rationsId, 3, 'a', 3, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'nourriture', $gourdeId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $cordeId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // (b) groupe sac d'érudit
    echo "  - (b) Sac d'érudit:\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'sac', $sacId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un livre de connaissance
    $livreId = autoInsertObject($pdo, 'outils', 'Livre de connaissance');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $livreId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un livre de connaissance (Object ID: $livreId)\n";
    
    // Une bouteille d'encre
    $encreId = autoInsertObject($pdo, 'outils', 'Bouteille d\'encre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $encreId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Une bouteille d'encre (Object ID: $encreId)\n";
    
    // Une plume d'écriture
    $plumeId = autoInsertObject($pdo, 'outils', 'Plume d\'écriture');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $plumeId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Une plume d'écriture (Object ID: $plumeId)\n";
    
    // 10 feuilles de parchemin
    $parcheminId = autoInsertObject($pdo, 'outils', 'Feuilles de parchemin');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $parcheminId, 3, 'b', 4, 'à_choisir', 10]);
    echo "    - 10 feuilles de parchemin (Object ID: $parcheminId)\n";
    
    // Un petit sac de sable
    $sableId = autoInsertObject($pdo, 'outils', 'Petit sac de sable');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $sableId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un petit sac de sable (Object ID: $sableId)\n";
    
    // Un petit couteau
    $couteauId = autoInsertObject($pdo, 'outils', 'Petit couteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $couteauId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un petit couteau (Object ID: $couteauId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Un grimoire
    $grimoireId = autoInsertObject($pdo, 'outils', 'Grimoire');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 7, 'outils', $grimoireId, 5, 'obligatoire', 1]);
    echo "  - Un grimoire (Object ID: $grimoireId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 7");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 2 options d'armes (a) bâton, (b) dague\n";
    echo "Choix 2: 2 options (a) sacoche à composantes, (b) focaliseur arcanique\n";
    echo "Choix 3a: 8 items du sac d'explorateur\n";
    echo "Choix 3b: 7 items du sac d'érudit\n";
    echo "Obligatoire: 1 item (grimoire)\n";
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
