<?php
/**
 * Script pour corriger les objets du Barbare avec les bons noms
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
    
    echo "=== CORRECTION DES OBJETS DU BARBARE ===\n\n";
    
    // Mapping correct des objets du Barbare
    $barbarianObjects = [
        // ID 21: sac - Sac à dos (déjà correct)
        21 => ['type' => 'sac', 'nom' => 'Sac à dos'],
        
        // ID 22: outils - Sac de couchage (incorrect, devrait être "Sac de couchage")
        22 => ['type' => 'sac', 'nom' => 'Sac de couchage'],
        
        // ID 23: outils - Gamelle
        23 => ['type' => 'outils', 'nom' => 'Gamelle'],
        
        // ID 24: outils - Boite d'allume-feu
        24 => ['type' => 'outils', 'nom' => 'Boite d\'allume-feu'],
        
        // ID 25: outils - Torche (déjà correct)
        25 => ['type' => 'outils', 'nom' => 'Torche'],
        
        // ID 26: nourriture - Rations de voyage (déjà correct)
        26 => ['type' => 'nourriture', 'nom' => 'Rations de voyage'],
        
        // ID 27: nourriture - Gourde d'eau (incorrect, devrait être "Gourde d'eau")
        27 => ['type' => 'outils', 'nom' => 'Gourde d\'eau'],
        
        // ID 28: outils - Corde de chanvre (15m)
        28 => ['type' => 'outils', 'nom' => 'Corde de chanvre (15m)']
    ];
    
    echo "Correction des objets...\n";
    
    foreach ($barbarianObjects as $equipmentId => $objectData) {
        $type = $objectData['type'];
        $nom = $objectData['nom'];
        
        // Vérifier si l'objet existe dans la table Object
        $stmt = $pdo->prepare("SELECT id FROM Object WHERE type = ? AND nom = ?");
        $stmt->execute([$type, $nom]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $objectId = $existing['id'];
            echo "  - Objet existant trouvé: {$type} '{$nom}' (ID: {$objectId})\n";
        } else {
            // Créer l'objet s'il n'existe pas
            $stmt = $pdo->prepare("INSERT INTO Object (type, nom) VALUES (?, ?)");
            $stmt->execute([$type, $nom]);
            $objectId = $pdo->lastInsertId();
            echo "  - Nouvel objet créé: {$type} '{$nom}' (ID: {$objectId})\n";
        }
        
        // Mettre à jour l'équipement de départ
        $stmt = $pdo->prepare("UPDATE starting_equipment SET type_id = ? WHERE id = ?");
        $stmt->execute([$objectId, $equipmentId]);
        echo "  - Équipement ID {$equipmentId} mis à jour avec Object ID {$objectId}\n";
    }
    
    echo "\n✅ Correction terminée!\n\n";
    
    // Vérifier les résultats
    echo "Vérification des résultats...\n";
    $stmt = $pdo->query("
        SELECT se.id, se.type, se.type_id, o.nom, se.nb
        FROM starting_equipment se
        LEFT JOIN Object o ON se.type_id = o.id
        WHERE se.type IN ('sac', 'outils', 'nourriture', 'accessoire')
        ORDER BY se.type, se.id
    ");
    
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "État final des équipements:\n";
    foreach ($equipment as $item) {
        if ($item['type_id']) {
            echo "  ✅ ID {$item['id']}: {$item['type']} - {$item['nom']} (x{$item['nb']}) [Object ID: {$item['type_id']}]\n";
        } else {
            echo "  ❌ ID {$item['id']}: {$item['type']} - Non lié (x{$item['nb']})\n";
        }
    }
    
    echo "\n=== SCRIPT TERMINÉ ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
