<?php
/**
 * Script pour enregistrer l'équipement de départ du Paladin
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU PALADIN ===\n\n";
    
    // Vérifier que la table est vide pour le paladin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 10");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Paladin: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Armes principales
    echo "Insertion du Choix 1 (Armes principales)...\n";
    
    // (a) groupe - arme de guerre à distance + bouclier
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre à distance', 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) N'importe quelle arme de guerre à distance\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'armor', 13, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Un bouclier (ID: 13)\n";
    
    // (b) groupe - 2 armes de guerre de corps à corps
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre de corps à corps', 1, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme de guerre de corps à corps (1ère)\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre de corps à corps', 1, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme de guerre de corps à corps (2ème)\n";
    
    // (c) groupe - 2 armes de guerre à distance
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre à distance', 1, 'c', 3, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme de guerre à distance (1ère)\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre à distance', 1, 'c', 3, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme de guerre à distance (2ème)\n";
    
    // (d) groupe - arme de guerre de corps à corps + arme de guerre à distance
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre de corps à corps', 1, 'd', 4, 'à_choisir', 1]);
    echo "  - (d) N'importe quelle arme de guerre de corps à corps\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre à distance', 1, 'd', 4, 'à_choisir', 1]);
    echo "  - (d) N'importe quelle arme de guerre à distance\n";
    
    // (e) groupe - arme de guerre de corps à corps + bouclier
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes de guerre de corps à corps', 1, 'e', 5, 'à_choisir', 1]);
    echo "  - (e) N'importe quelle arme de guerre de corps à corps\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'armor', 13, 1, 'e', 5, 'à_choisir', 1]);
    echo "  - (e) Un bouclier (ID: 13)\n";
    
    // CHOIX 2: Armes secondaires
    echo "\nInsertion du Choix 2 (Armes secondaires)...\n";
    
    // (a) cinq javelines
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 17, 2, 'a', 6, 'à_choisir', 5]);
    echo "  - (a) Cinq javelines (ID: 17)\n";
    
    // (b) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes courantes à distance', 2, 'b', 6, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (distance)\n";
    
    // (c) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'weapon', 'Armes courantes de corps à corps', 2, 'c', 6, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme courante (corps à corps)\n";
    
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
    $stmt->execute(['class', 10, 'sac', $sacId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $sacCouchageId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // Une gamelle
    $gamelleId = autoInsertObject($pdo, 'outils', 'Gamelle');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $gamelleId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Une gamelle (Object ID: $gamelleId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $allumeFeuId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $torcheId, 3, 'a', 7, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'nourriture', $rationsId, 3, 'a', 7, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'nourriture', $gourdeId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $cordeId, 3, 'a', 7, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // (b) groupe sac d'ecclésiastique
    echo "  - (b) Sac d'ecclésiastique:\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'sac', $sacId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Une couverture
    $couvertureId = autoInsertObject($pdo, 'outils', 'Couverture');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $couvertureId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Une couverture (Object ID: $couvertureId)\n";
    
    // 10 bougies
    $bougiesId = autoInsertObject($pdo, 'outils', 'Bougies');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $bougiesId, 3, 'b', 8, 'à_choisir', 10]);
    echo "    - 10 bougies (Object ID: $bougiesId)\n";
    
    // 5 bougies supplémentaires
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $bougiesId, 3, 'b', 8, 'à_choisir', 5]);
    echo "    - 5 bougies supplémentaires (Object ID: $bougiesId)\n";
    
    // Une boite d'allume-feu
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $allumeFeuId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // Une boîte pour l'aumône
    $aumoneId = autoInsertObject($pdo, 'outils', 'Boîte pour l\'aumône');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $aumoneId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Une boîte pour l'aumône (Object ID: $aumoneId)\n";
    
    // Un encensoir
    $encensoirId = autoInsertObject($pdo, 'outils', 'Encensoir');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $encensoirId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Un encensoir (Object ID: $encensoirId)\n";
    
    // 2 bâtonnets d'encens
    $encensId = autoInsertObject($pdo, 'outils', 'Bâtonnets d\'encens');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $encensId, 3, 'b', 8, 'à_choisir', 2]);
    echo "    - 2 bâtonnets d'encens (Object ID: $encensId)\n";
    
    // Des habits de cérémonie
    $habitsId = autoInsertObject($pdo, 'outils', 'Habits de cérémonie');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $habitsId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Des habits de cérémonie (Object ID: $habitsId)\n";
    
    // 2 jours de rations
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'nourriture', $rationsId, 3, 'b', 8, 'à_choisir', 2]);
    echo "    - 2 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'nourriture', $gourdeId, 3, 'b', 8, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Une cotte de mailles
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'armor', 10, 9, 'obligatoire', 1]);
    echo "  - Une cotte de mailles (ID: 10)\n";
    
    // Un symbole sacré
    $symboleId = autoInsertObject($pdo, 'outils', 'Symbole sacré');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 10, 'outils', $symboleId, 9, 'obligatoire', 1]);
    echo "  - Un symbole sacré (Object ID: $symboleId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 10");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 5 options d'armes principales (a-e) avec différentes combinaisons\n";
    echo "Choix 2: 3 options d'armes secondaires (a) 5 javelines, (b) arme courante distance, (c) arme courante corps à corps\n";
    echo "Choix 3a: 8 items du sac d'explorateur\n";
    echo "Choix 3b: 12 items du sac d'ecclésiastique\n";
    echo "Obligatoire: 2 items (cotte de mailles + symbole sacré)\n";
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
