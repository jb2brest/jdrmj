<?php
require_once 'config/database.php';

echo "<h1>Configuration complète du système JDR 4 MJ</h1>";

try {
    echo "<h2>1. Création des tables d'équipement...</h2>";
    createEquipmentTables($pdo);
    
    echo "<h2>2. Création des tables de données...</h2>";
    createDataTables($pdo);
    
    echo "<h2>3. Import des données CSV...</h2>";
    importCSVData($pdo);
    
    echo "<h2>✅ Configuration terminée avec succès !</h2>";
    echo "<p>Toutes les tables et données ont été configurées.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de la configuration</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";

function createEquipmentTables($pdo) {
    $sqlFile = 'database/add_equipment_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL des tables d'équipement introuvable: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        echo "<p><strong>Exécution:</strong> " . htmlspecialchars(substr($query, 0, 100)) . "...</p>";
        
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Avertissement: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

function createDataTables($pdo) {
    $sqlFile = 'database/add_data_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier SQL des tables de données introuvable: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        echo "<p><strong>Exécution:</strong> " . htmlspecialchars(substr($query, 0, 100)) . "...</p>";
        
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Avertissement: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

function importCSVData($pdo) {
    // Import des poisons
    echo "<h3>Import des poisons...</h3>";
    importPoisons($pdo);
    
    // Import des objets magiques
    echo "<h3>Import des objets magiques...</h3>";
    importMagicalItems($pdo);
    
    // Import des monstres (si le fichier existe)
    if (file_exists('aidednddata/monstre.csv')) {
        echo "<h3>Import des monstres...</h3>";
        importMonsters($pdo);
    }
}

function importPoisons($pdo) {
    $csvFile = 'aidednddata/poisons.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des poisons introuvable: $csvFile");
    }
    
    // Vider la table existante
    $pdo->exec("TRUNCATE TABLE poisons");
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        throw new Exception("Impossible d'ouvrir le fichier des poisons");
    }
    
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    $stmt = $pdo->prepare("INSERT INTO poisons (csv_id, nom, cle, description, type, source) VALUES (?, ?, ?, ?, ?, ?)");
    $count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 6) {
            $stmt->execute([
                $data[0], // csv_id
                $data[1], // nom
                $data[2], // cle
                $data[3], // description
                $data[4], // type
                $data[5]  // source
            ]);
            $count++;
        }
    }
    
    fclose($handle);
    echo "<p>✅ $count poisons importés avec succès</p>";
}

function importMagicalItems($pdo) {
    $csvFile = 'aidednddata/objet_magiques.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("Fichier des objets magiques introuvable: $csvFile");
    }
    
    // Vider la table existante
    $pdo->exec("TRUNCATE TABLE magical_items");
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        throw new Exception("Impossible d'ouvrir le fichier des objets magiques");
    }
    
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    $stmt = $pdo->prepare("INSERT INTO magical_items (csv_id, nom, cle, description, type, source) VALUES (?, ?, ?, ?, ?, ?)");
    $count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 6) {
            $stmt->execute([
                $data[0], // csv_id
                $data[1], // nom
                $data[2], // cle
                $data[3], // description
                $data[4], // type
                $data[5]  // source
            ]);
            $count++;
        }
    }
    
    fclose($handle);
    echo "<p>✅ $count objets magiques importés avec succès</p>";
}

function importMonsters($pdo) {
    $csvFile = 'aidednddata/monstre.csv';
    if (!file_exists($csvFile)) {
        echo "<p>⚠️ Fichier des monstres introuvable, ignoré</p>";
        return;
    }
    
    // Vider la table existante
    $pdo->exec("TRUNCATE TABLE dnd_monsters");
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        throw new Exception("Impossible d'ouvrir le fichier des monstres");
    }
    
    // Ignorer l'en-tête
    fgetcsv($handle);
    
    $stmt = $pdo->prepare("INSERT INTO dnd_monsters (csv_id, name, type, size, challenge_rating, hit_points, armor_class) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $count = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) >= 7) {
            $stmt->execute([
                $data[0], // csv_id
                $data[1], // name
                $data[2], // type
                $data[3], // size
                $data[4], // challenge_rating
                (int)$data[5], // hit_points
                (int)$data[6]  // armor_class
            ]);
            $count++;
        }
    }
    
    fclose($handle);
    echo "<p>✅ $count monstres importés avec succès</p>";
}
?>






