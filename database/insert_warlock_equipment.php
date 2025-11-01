<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Occultiste
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'OCCULTISTE ===\n\n";
    
    // Vérifier que la table est vide pour l'occultiste
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 9");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Occultiste: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Arme
    echo "Insertion du Choix 1 (Arme)...\n";
    
    // (a) une arbalète légère
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 11, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Une arbalète légère (ID: 11)\n";
    
    // (b) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 'Armes courantes à distance', 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (distance)\n";
    
    // (c) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 'Armes courantes de corps à corps', 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme courante (corps à corps)\n";
    
    // CHOIX 2: Sacoche ou Focaliseur
    echo "\nInsertion du Choix 2 (Sacoche ou Focaliseur)...\n";
    
    // (a) une sacoche à composantes
    $sacocheId = autoInsertObject($pdo, 'sac', 'Sacoche à composantes');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'sac', $sacocheId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - (a) Une sacoche à composantes (Object ID: $sacocheId)\n";
    
    // (b) un focaliseur arcanique
    $focaliseurId = autoInsertObject($pdo, 'outils', 'Focaliseur arcanique');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $focaliseurId, 2, 'b', 2, 'à_choisir', 1]);
    echo "  - (b) Un focaliseur arcanique (Object ID: $focaliseurId)\n";
    
    // CHOIX 3: Sac d'équipement
    echo "\nInsertion du Choix 3 (Sac d'équipement)...\n";
    
    // (a) groupe sac d'érudit
    echo "  - (a) Sac d'érudit:\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'sac', $sacId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un livre de connaissance
    $livreId = autoInsertObject($pdo, 'outils', 'Livre de connaissance');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $livreId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un livre de connaissance (Object ID: $livreId)\n";
    
    // Une bouteille d'encre
    $encreId = autoInsertObject($pdo, 'outils', 'Bouteille d\'encre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $encreId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une bouteille d'encre (Object ID: $encreId)\n";
    
    // Une plume d'écriture
    $plumeId = autoInsertObject($pdo, 'outils', 'Plume d\'écriture');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $plumeId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Une plume d'écriture (Object ID: $plumeId)\n";
    
    // 10 feuilles de parchemin
    $parcheminId = autoInsertObject($pdo, 'outils', 'Feuilles de parchemin');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $parcheminId, 3, 'a', 3, 'à_choisir', 10]);
    echo "    - 10 feuilles de parchemin (Object ID: $parcheminId)\n";
    
    // Un petit sac de sable
    $sableId = autoInsertObject($pdo, 'outils', 'Petit sac de sable');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $sableId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un petit sac de sable (Object ID: $sableId)\n";
    
    // Un petit couteau
    $couteauId = autoInsertObject($pdo, 'outils', 'Petit couteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $couteauId, 3, 'a', 3, 'à_choisir', 1]);
    echo "    - Un petit couteau (Object ID: $couteauId)\n";
    
    // (b) groupe sac d'exploration souterraine
    echo "  - (b) Sac d'exploration souterraine:\n";
    
    // Un sac à dos
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'sac', $sacId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un sac à dos (Object ID: $sacId)\n";
    
    // Un pied de biche
    $piedBicheId = autoInsertObject($pdo, 'outils', 'Pied de biche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $piedBicheId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un pied de biche (Object ID: $piedBicheId)\n";
    
    // Un marteau
    $marteauId = autoInsertObject($pdo, 'outils', 'Marteau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $marteauId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Un marteau (Object ID: $marteauId)\n";
    
    // 10 pitons
    $pitonId = autoInsertObject($pdo, 'outils', 'Piton');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $pitonId, 3, 'b', 4, 'à_choisir', 10]);
    echo "    - 10 pitons (Object ID: $pitonId)\n";
    
    // 10 torches
    $torcheId = autoInsertObject($pdo, 'outils', 'Torche');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $torcheId, 3, 'b', 4, 'à_choisir', 10]);
    echo "    - 10 torches (Object ID: $torcheId)\n";
    
    // Une boite d'allume-feu
    $allumeFeuId = autoInsertObject($pdo, 'outils', 'Boite d\'allume-feu');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $allumeFeuId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Une boite d'allume-feu (Object ID: $allumeFeuId)\n";
    
    // 10 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'nourriture', $rationsId, 3, 'b', 4, 'à_choisir', 10]);
    echo "    - 10 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'nourriture', $gourdeId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Une corde de 15m
    $cordeId = autoInsertObject($pdo, 'outils', 'Corde de chanvre (15m)');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'outils', $cordeId, 3, 'b', 4, 'à_choisir', 1]);
    echo "    - Une corde de 15m (Object ID: $cordeId)\n";
    
    // CHOIX 4: Arme secondaire
    echo "\nInsertion du Choix 4 (Arme secondaire)...\n";
    
    // (a) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 'Armes courantes à distance', 4, 'a', 5, 'à_choisir', 1]);
    echo "  - (a) N'importe quelle arme courante (distance)\n";
    
    // (b) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 'Armes courantes de corps à corps', 4, 'b', 5, 'à_choisir', 1]);
    echo "  - (b) N'importe quelle arme courante (corps à corps)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // 2 dagues
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'weapon', 2, 6, 'obligatoire', 2]);
    echo "  - 2 dagues (ID: 2)\n";
    
    // Une armure de cuir
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 9, 'armor', 2, 6, 'obligatoire', 1]);
    echo "  - Une armure de cuir (ID: 2)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 9");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 3 options d'armes (a) arbalète légère, (b) arme courante distance, (c) arme courante corps à corps\n";
    echo "Choix 2: 2 options (a) sacoche à composantes, (b) focaliseur arcanique\n";
    echo "Choix 3a: 7 items du sac d'érudit\n";
    echo "Choix 3b: 9 items du sac d'exploration souterraine\n";
    echo "Choix 4: 2 options d'armes secondaires\n";
    echo "Obligatoire: 2 items (2 dagues + armure de cuir)\n";
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
