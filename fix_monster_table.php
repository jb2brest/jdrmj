<?php
require_once 'config/database.php';

echo "<h1>Correction de la table dnd_monsters</h1>";

try {
    // Vérifier si la table existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'dnd_monsters'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>✗ Table 'dnd_monsters' n'existe pas</p>";
        echo "<p>Veuillez d'abord exécuter update_database.php</p>";
        exit();
    }
    
    echo "<p style='color: blue;'>ℹ Table 'dnd_monsters' trouvée</p>";
    
    // Vérifier la structure actuelle de la colonne challenge_rating
    $stmt = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'challenge_rating'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<p><strong>Type actuel de challenge_rating :</strong> " . $column['Type'] . "</p>";
        
        // Si c'est un DECIMAL, le modifier en VARCHAR
        if (strpos($column['Type'], 'decimal') !== false) {
            echo "<p>Modification de la colonne challenge_rating de DECIMAL vers VARCHAR...</p>";
            
            try {
                $pdo->exec("ALTER TABLE dnd_monsters MODIFY COLUMN challenge_rating VARCHAR(20)");
                echo "<p style='color: green;'>✅ Colonne challenge_rating modifiée avec succès</p>";
                
                // Vérifier la nouvelle structure
                $stmt = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'challenge_rating'");
                $newColumn = $stmt->fetch();
                echo "<p><strong>Nouveau type de challenge_rating :</strong> " . $newColumn['Type'] . "</p>";
                
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Erreur lors de la modification : " . htmlspecialchars($e->getMessage()) . "</p>";
                
                // Essayer une approche alternative : supprimer et recréer la colonne
                echo "<p>Tentative de recréation de la colonne...</p>";
                try {
                    $pdo->exec("ALTER TABLE dnd_monsters DROP COLUMN challenge_rating");
                    $pdo->exec("ALTER TABLE dnd_monsters ADD COLUMN challenge_rating VARCHAR(20) AFTER size");
                    echo "<p style='color: green;'>✅ Colonne challenge_rating recréée avec succès</p>";
                } catch (PDOException $e2) {
                    echo "<p style='color: red;'>❌ Erreur lors de la recréation : " . htmlspecialchars($e2->getMessage()) . "</p>";
                }
            }
            
        } else {
            echo "<p style='color: blue;'>ℹ La colonne challenge_rating est déjà du bon type</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Colonne challenge_rating non trouvée</p>";
        echo "<p>Ajout de la colonne challenge_rating...</p>";
        
        try {
            $pdo->exec("ALTER TABLE dnd_monsters ADD COLUMN challenge_rating VARCHAR(20) AFTER size");
            echo "<p style='color: green;'>✅ Colonne challenge_rating ajoutée avec succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur lors de l'ajout : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Vérifier et ajouter la colonne csv_id si absente
    $stmt = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'csv_id'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Ajout de la colonne csv_id...</p>";
        try {
            $pdo->exec("ALTER TABLE dnd_monsters ADD COLUMN csv_id VARCHAR(50) UNIQUE AFTER id");
            $pdo->exec("ALTER TABLE dnd_monsters ADD INDEX idx_csv_id (csv_id)");
            echo "<p style='color: green;'>✅ Colonne csv_id ajoutée avec succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Avertissement lors de l'ajout de csv_id : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Colonne csv_id existe déjà</p>";
    }
    
    // Vérifier et ajouter l'index fulltext si absent
    $indexes = $pdo->query("SHOW INDEX FROM dnd_monsters WHERE Key_name = 'idx_search'");
    if ($indexes->rowCount() == 0) {
        echo "<p>Ajout de l'index fulltext...</p>";
        try {
            $pdo->exec("ALTER TABLE dnd_monsters ADD FULLTEXT idx_search (name, type)");
            echo "<p style='color: green;'>✅ Index fulltext ajouté avec succès</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Avertissement lors de l'ajout de l'index fulltext : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Index fulltext existe déjà</p>";
    }
    
    echo "<h2>✅ Correction terminée !</h2>";
    echo "<p>La table dnd_monsters est maintenant prête pour l'import des monstres.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de la correction</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
echo "<p><a href='update_database.php'>Relancer update_database.php</a></p>";
?>













