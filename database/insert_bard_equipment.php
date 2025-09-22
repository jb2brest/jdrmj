<?php
/**
 * Script pour enregistrer l'équipement de départ du Barde
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DU BARDE ===\n\n";
    
    // Vérifier que la table est vide pour le barde
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 2");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour le Barde: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Arme principale
    echo "Insertion du Choix 1 (Arme principale)...\n";
    
    // (a) rapière
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'weapon', 1, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Rapière (ID: 1)\n";
    
    // (b) épée longue
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'weapon', 2, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Épée longue (ID: 2)\n";
    
    // (c) n'importe quelle arme courante (distance)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'weapon', 'Armes courantes à distance', 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) N'importe quelle arme courante (distance)\n";
    
    // (d) n'importe quelle arme courante (corps à corps)
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'weapon', 'Armes courantes de corps à corps', 1, 'd', 1, 'à_choisir', 1]);
    echo "  - (d) N'importe quelle arme courante (corps à corps)\n";
    
    // CHOIX 2: Sac de diplomate (a)
    echo "\nInsertion du Choix 2a (Sac de diplomate)...\n";
    
    // Un coffre
    $coffreId = autoInsertObject($pdo, 'sac', 'Coffre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'sac', $coffreId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Un coffre (Object ID: $coffreId)\n";
    
    // 2 étuis à cartes ou parchemins
    $etuisId = autoInsertObject($pdo, 'outils', 'Étuis à cartes ou parchemins');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $etuisId, 2, 'a', 2, 'à_choisir', 2]);
    echo "  - 2 étuis à cartes ou parchemins (Object ID: $etuisId)\n";
    
    // Des vêtements fins
    $vetementsId = autoInsertObject($pdo, 'outils', 'Vêtements fins');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $vetementsId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Des vêtements fins (Object ID: $vetementsId)\n";
    
    // Une bouteille d'encre
    $encreId = autoInsertObject($pdo, 'outils', 'Bouteille d\'encre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $encreId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Une bouteille d'encre (Object ID: $encreId)\n";
    
    // Une plume d'écriture
    $plumeId = autoInsertObject($pdo, 'outils', 'Plume d\'écriture');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $plumeId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Une plume d'écriture (Object ID: $plumeId)\n";
    
    // Une lampe
    $lampeId = autoInsertObject($pdo, 'outils', 'Lampe');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $lampeId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Une lampe (Object ID: $lampeId)\n";
    
    // Deux flasques d'huile
    $huileId = autoInsertObject($pdo, 'outils', 'Flasque d\'huile');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $huileId, 2, 'a', 2, 'à_choisir', 2]);
    echo "  - Deux flasques d'huile (Object ID: $huileId)\n";
    
    // 5 feuilles de papier
    $papierId = autoInsertObject($pdo, 'outils', 'Feuilles de papier');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $papierId, 2, 'a', 2, 'à_choisir', 5]);
    echo "  - 5 feuilles de papier (Object ID: $papierId)\n";
    
    // Un flacon de parfum
    $parfumId = autoInsertObject($pdo, 'outils', 'Flacon de parfum');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $parfumId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Un flacon de parfum (Object ID: $parfumId)\n";
    
    // De la cire à cacheter
    $cireId = autoInsertObject($pdo, 'outils', 'Cire à cacheter');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $cireId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - De la cire à cacheter (Object ID: $cireId)\n";
    
    // Du savon
    $savonId = autoInsertObject($pdo, 'outils', 'Savon');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $savonId, 2, 'a', 2, 'à_choisir', 1]);
    echo "  - Du savon (Object ID: $savonId)\n";
    
    // CHOIX 2: Sac d'artiste (b)
    echo "\nInsertion du Choix 2b (Sac d'artiste)...\n";
    
    // Un sac à dos
    $sacId = autoInsertObject($pdo, 'sac', 'Sac à dos');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'sac', $sacId, 2, 'b', 3, 'à_choisir', 1]);
    echo "  - Un sac à dos (Object ID: $sacId)\n";
    
    // Un sac de couchage
    $sacCouchageId = autoInsertObject($pdo, 'outils', 'Sac de couchage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $sacCouchageId, 2, 'b', 3, 'à_choisir', 1]);
    echo "  - Un sac de couchage (Object ID: $sacCouchageId)\n";
    
    // 2 costumes
    $costumesId = autoInsertObject($pdo, 'outils', 'Costumes');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $costumesId, 2, 'b', 3, 'à_choisir', 2]);
    echo "  - 2 costumes (Object ID: $costumesId)\n";
    
    // 5 bougies
    $bougiesId = autoInsertObject($pdo, 'outils', 'Bougies');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $bougiesId, 2, 'b', 3, 'à_choisir', 5]);
    echo "  - 5 bougies (Object ID: $bougiesId)\n";
    
    // 5 jours de rations
    $rationsId = autoInsertObject($pdo, 'nourriture', 'Rations de voyage');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'nourriture', $rationsId, 2, 'b', 3, 'à_choisir', 5]);
    echo "  - 5 jours de rations (Object ID: $rationsId)\n";
    
    // Une gourde d'eau
    $gourdeId = autoInsertObject($pdo, 'nourriture', 'Gourde d\'eau');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'nourriture', $gourdeId, 2, 'b', 3, 'à_choisir', 1]);
    echo "  - Une gourde d'eau (Object ID: $gourdeId)\n";
    
    // Un kit de déguisement
    $deguisementId = autoInsertObject($pdo, 'outils', 'Kit de déguisement');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'outils', $deguisementId, 2, 'b', 3, 'à_choisir', 1]);
    echo "  - Un kit de déguisement (Object ID: $deguisementId)\n";
    
    // CHOIX 3: Instrument de musique
    echo "\nInsertion du Choix 3 (Instrument de musique)...\n";
    
    // (a) luth
    $luthId = autoInsertObject($pdo, 'instrument', 'Luth');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'instrument', $luthId, 3, 'a', 4, 'à_choisir', 1]);
    echo "  - (a) Luth (Object ID: $luthId)\n";
    
    // (b) n'importe quel autre instrument
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_filter, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'instrument', 'instrument', 3, 'b', 4, 'à_choisir', 1]);
    echo "  - (b) N'importe quel autre instrument\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Une armure de cuir
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'armor', 1, 5, 'obligatoire', 1]);
    echo "  - Une armure de cuir (ID: 1)\n";
    
    // Une dague
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['class', 2, 'weapon', 3, 5, 'obligatoire', 1]);
    echo "  - Une dague (ID: 3)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'class' AND src_id = 2");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 4 options d'armes (a) rapière, (b) épée longue, (c) arme courante distance, (d) arme courante corps à corps\n";
    echo "Choix 2a: 12 items du sac de diplomate\n";
    echo "Choix 2b: 7 items du sac d'artiste\n";
    echo "Choix 3: 2 options d'instruments (a) luth, (b) autre instrument\n";
    echo "Obligatoire: 2 items (armure de cuir + dague)\n";
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
