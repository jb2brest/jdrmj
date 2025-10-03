<?php
/**
 * Script pour migrer vers le nouveau schéma des tables starting_equipment
 */

require_once 'classes/init.php';

try {
    $pdo = getPDO();
    echo "Connexion à la base de données réussie.\n";
    
    // 1. Sauvegarder les données existantes
    echo "1. Sauvegarde des données existantes...\n";
    
    $existingChoix = $pdo->query("SELECT * FROM starting_equipment_choix")->fetchAll(PDO::FETCH_ASSOC);
    $existingOptions = $pdo->query("SELECT * FROM starting_equipment_options")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   - " . count($existingChoix) . " choix sauvegardés\n";
    echo "   - " . count($existingOptions) . " options sauvegardées\n";
    
    // 2. Supprimer les anciennes tables
    echo "2. Suppression des anciennes tables...\n";
    $pdo->exec("DROP TABLE IF EXISTS starting_equipment_options");
    $pdo->exec("DROP TABLE IF EXISTS starting_equipment_choix");
    echo "   ✓ Anciennes tables supprimées\n";
    
    // 3. Créer les nouvelles tables
    echo "3. Création des nouvelles tables...\n";
    
    // Table starting_equipment_choix
    $pdo->exec("
        CREATE TABLE starting_equipment_choix (
            id INT AUTO_INCREMENT PRIMARY KEY,
            src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
            src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
            no_choix INT NOT NULL COMMENT 'Le numéro du choix',
            option_letter CHAR(1) COMMENT 'La lettre d\'option du package',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_src_src_id (src, src_id),
            INDEX idx_no_choix (no_choix),
            INDEX idx_option_letter (option_letter)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Table starting_equipment_choix créée\n";
    
    // Table starting_equipment_options
    $pdo->exec("
        CREATE TABLE starting_equipment_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            starting_equipment_choix_id INT NOT NULL COMMENT 'ID du choix dont fait partie l\'option',
            src ENUM('class', 'background') NOT NULL COMMENT 'Source: class ou background',
            src_id INT NOT NULL COMMENT 'ID de la classe ou du background concerné',
            type ENUM('armor', 'bouclier', 'instrument', 'nourriture', 'outils', 'sac', 'weapon') NOT NULL COMMENT 'Type d\'équipement',
            type_id INT COMMENT 'ID de l\'équipement dans la table correspondant au type',
            type_filter VARCHAR(100) COMMENT 'Filtre pour sélectionner des armes dans une liste',
            nb INT DEFAULT 1 COMMENT 'Le nombre d\'item',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (starting_equipment_choix_id) REFERENCES starting_equipment_choix(id) ON DELETE CASCADE,
            INDEX idx_starting_equipment_choix_id (starting_equipment_choix_id),
            INDEX idx_src_src_id (src, src_id),
            INDEX idx_type (type),
            INDEX idx_type_id (type_id),
            INDEX idx_type_filter (type_filter)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Table starting_equipment_options créée\n";
    
    // 4. Migrer les données depuis starting_equipment
    echo "4. Migration des données depuis starting_equipment...\n";
    
    // Créer les choix à partir de starting_equipment
    $pdo->exec("
        INSERT INTO starting_equipment_choix (src, src_id, no_choix, option_letter, created_at, updated_at)
        SELECT DISTINCT 
            se.src,
            se.src_id,
            COALESCE(se.no_choix, 0) as no_choix,
            se.option_letter,
            se.created_at,
            se.updated_at
        FROM starting_equipment se
    ");
    
    $choixCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_choix")->fetchColumn();
    echo "   ✓ $choixCount choix créés\n";
    
    // Créer les options à partir de starting_equipment
    $pdo->exec("
        INSERT INTO starting_equipment_options (
            starting_equipment_choix_id, src, src_id, type, type_id, type_filter, nb, created_at, updated_at
        )
        SELECT 
            sc.id as starting_equipment_choix_id,
            se.src,
            se.src_id,
            se.type,
            se.type_id,
            se.type_filter,
            se.nb,
            se.created_at,
            se.updated_at
        FROM starting_equipment se
        INNER JOIN starting_equipment_choix sc ON (
            se.src = sc.src 
            AND se.src_id = sc.src_id 
            AND COALESCE(se.no_choix, 0) = sc.no_choix
            AND (se.option_letter = sc.option_letter OR (se.option_letter IS NULL AND sc.option_letter IS NULL))
        )
    ");
    
    $optionsCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_options")->fetchColumn();
    echo "   ✓ $optionsCount options créées\n";
    
    // 5. Vérification finale
    echo "5. Vérification finale...\n";
    
    $finalChoixCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_choix")->fetchColumn();
    $finalOptionsCount = $pdo->query("SELECT COUNT(*) FROM starting_equipment_options")->fetchColumn();
    
    echo "   - Choix finaux: $finalChoixCount\n";
    echo "   - Options finales: $finalOptionsCount\n";
    
    // Afficher un échantillon des données
    echo "\n6. Échantillon des données migrées:\n";
    $sample = $pdo->query("
        SELECT 
            sc.src,
            sc.src_id,
            sc.no_choix,
            sc.option_letter,
            COUNT(so.id) as nb_options
        FROM starting_equipment_choix sc
        LEFT JOIN starting_equipment_options so ON sc.id = so.starting_equipment_choix_id
        GROUP BY sc.id, sc.src, sc.src_id, sc.no_choix, sc.option_letter
        ORDER BY sc.src, sc.src_id, sc.no_choix
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sample as $row) {
        echo "   - " . implode(' | ', $row) . "\n";
    }
    
    echo "\n✅ Migration vers le nouveau schéma terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
