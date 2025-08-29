<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isDM()) {
    header('Location: index.php');
    exit();
}

echo "<h2>Correction de la colonne challenge_rating</h2>";

// Vérifier la structure actuelle de la colonne
$stmt = $pdo->query("SHOW COLUMNS FROM dnd_monsters LIKE 'challenge_rating'");
$column = $stmt->fetch();

if (!$column) {
    echo "<p style='color: red;'>✗ Colonne challenge_rating non trouvée</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit();
}

echo "<p>Type actuel de challenge_rating : <strong>" . $column['Type'] . "</strong></p>";

// Vérifier si la modification est nécessaire
if (strpos($column['Type'], 'decimal(3,2)') !== false) {
    echo "<p>Modification de la colonne challenge_rating...</p>";
    
    try {
        $pdo->exec("ALTER TABLE dnd_monsters MODIFY COLUMN challenge_rating DECIMAL(4,2)");
        echo "<p style='color: green;'>✓ Colonne challenge_rating modifiée avec succès</p>";
        echo "<p>Nouveau type : DECIMAL(4,2) - Supporte les CR jusqu'à 99.99</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Erreur lors de la modification : " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ La colonne challenge_rating est déjà correcte</p>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='import_monsters.php' class='btn btn-primary'>Réessayer l'import</a> ";
echo "<a href='bestiary.php' class='btn btn-success'>Parcourir le bestiaire</a> ";
echo "<a href='index.php' class='btn btn-secondary'>Retour à l'accueil</a>";
echo "</div>";
?>
