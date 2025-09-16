<?php
require_once 'config/database.php';

echo "<h1>Import des données CSV dans la base de données</h1>";

try {
    // 1. Import des poisons
    echo "<h2>1. Import des poisons...</h2>";
    importPoisons($pdo);
    
    // 2. Import des objets magiques
    echo "<h2>2. Import des objets magiques...</h2>";
    importMagicalItems($pdo);
    
    // 3. Import des monstres (si le fichier existe)
    if (file_exists('aidednddata/monstre.csv')) {
        echo "<h2>3. Import des monstres...</h2>";
        importMonsters($pdo);
    }
    
    echo "<h2>✅ Import terminé avec succès !</h2>";
    echo "<p>Toutes les données ont été importées dans la base de données.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de l'import</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";

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











