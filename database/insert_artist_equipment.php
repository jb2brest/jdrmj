<?php
/**
 * Script pour enregistrer l'équipement de départ de l'Artiste
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
    
    echo "=== ENREGISTREMENT DE L'ÉQUIPEMENT DE L'ARTISTE ===\n\n";
    
    // Vérifier que la table est vide pour l'artiste
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 3");
    $count = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements existants pour l'Artiste: $count\n\n";
    
    // Commencer la transaction
    $pdo->beginTransaction();
    
    // CHOIX 1: Instrument de musique
    echo "Insertion du Choix 1 (Instrument de musique)...\n";
    
    // (a) Chalemie
    $chalemieId = autoInsertObject($pdo, 'instrument', 'Chalemie');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $chalemieId, 1, 'a', 1, 'à_choisir', 1]);
    echo "  - (a) Chalemie (Object ID: $chalemieId)\n";
    
    // (b) Cor
    $corId = autoInsertObject($pdo, 'instrument', 'Cor');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $corId, 1, 'b', 1, 'à_choisir', 1]);
    echo "  - (b) Cor (Object ID: $corId)\n";
    
    // (c) Cornemuse
    $cornemuseId = autoInsertObject($pdo, 'instrument', 'Cornemuse');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $cornemuseId, 1, 'c', 1, 'à_choisir', 1]);
    echo "  - (c) Cornemuse (Object ID: $cornemuseId)\n";
    
    // (d) Flûte
    $fluteId = autoInsertObject($pdo, 'instrument', 'Flûte');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $fluteId, 1, 'd', 1, 'à_choisir', 1]);
    echo "  - (d) Flûte (Object ID: $fluteId)\n";
    
    // (e) Flûte de pan
    $flutePanId = autoInsertObject($pdo, 'instrument', 'Flûte de pan');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $flutePanId, 1, 'e', 1, 'à_choisir', 1]);
    echo "  - (e) Flûte de pan (Object ID: $flutePanId)\n";
    
    // (f) Luth
    $luthId = autoInsertObject($pdo, 'instrument', 'Luth');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $luthId, 1, 'f', 1, 'à_choisir', 1]);
    echo "  - (f) Luth (Object ID: $luthId)\n";
    
    // (g) Lyre
    $lyreId = autoInsertObject($pdo, 'instrument', 'Lyre');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $lyreId, 1, 'g', 1, 'à_choisir', 1]);
    echo "  - (g) Lyre (Object ID: $lyreId)\n";
    
    // (h) Tambour
    $tambourId = autoInsertObject($pdo, 'instrument', 'Tambour');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $tambourId, 1, 'h', 1, 'à_choisir', 1]);
    echo "  - (h) Tambour (Object ID: $tambourId)\n";
    
    // (i) Tympanon
    $tympanonId = autoInsertObject($pdo, 'instrument', 'Tympanon');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $tympanonId, 1, 'i', 1, 'à_choisir', 1]);
    echo "  - (i) Tympanon (Object ID: $tympanonId)\n";
    
    // (j) Viole
    $violeId = autoInsertObject($pdo, 'instrument', 'Viole');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, no_choix, option_letter, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'instrument', $violeId, 1, 'j', 1, 'à_choisir', 1]);
    echo "  - (j) Viole (Object ID: $violeId)\n";
    
    // ÉQUIPEMENT OBLIGATOIRE
    echo "\nInsertion de l'équipement obligatoire...\n";
    
    // Un cadeau d'un admirateur
    $cadeauAdmirateurId = autoInsertObject($pdo, 'outils', 'Cadeau d\'un admirateur');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'outils', $cadeauAdmirateurId, 2, 'obligatoire', 1]);
    echo "  - Un cadeau d'un admirateur (Object ID: $cadeauAdmirateurId)\n";
    
    // Un costume
    $costumeId = autoInsertObject($pdo, 'outils', 'Costume');
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, groupe_id, type_choix, nb) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(['background', 3, 'outils', $costumeId, 2, 'obligatoire', 1]);
    echo "  - Un costume (Object ID: $costumeId)\n";
    
    // Valider la transaction
    $pdo->commit();
    
    echo "\n✅ Insertion terminée avec succès!\n";
    
    // Vérifier le nombre d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM starting_equipment WHERE src = 'background' AND src_id = 3");
    $count_after = $stmt->fetch()['count'];
    echo "Nombre d'enregistrements après insertion: $count_after\n";
    
    // Afficher un résumé
    echo "\n=== RÉSUMÉ ===\n";
    echo "Choix 1: 10 options d'instruments de musique (a-j)\n";
    echo "  - (a) Chalemie\n";
    echo "  - (b) Cor\n";
    echo "  - (c) Cornemuse\n";
    echo "  - (d) Flûte\n";
    echo "  - (e) Flûte de pan\n";
    echo "  - (f) Luth\n";
    echo "  - (g) Lyre\n";
    echo "  - (h) Tambour\n";
    echo "  - (i) Tympanon\n";
    echo "  - (j) Viole\n";
    echo "Obligatoire: 2 items (cadeau d'un admirateur + costume)\n";
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
