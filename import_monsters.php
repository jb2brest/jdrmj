<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

if (!isDM()) {
    header('Location: index.php');
    exit();
}

// Vérifier si des monstres existent déjà
$stmt = $pdo->query("SELECT COUNT(*) as count FROM dnd_monsters");
$count = $stmt->fetch()['count'];

if ($count > 0) {
    echo "<h2>Des monstres existent déjà dans la base de données</h2>";
    echo "<p>Nombre de monstres : $count</p>";
    echo "<p>Voulez-vous vider la table et réimporter ?</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='clear_and_import'>";
    echo "<button type='submit' class='btn btn-danger'>Vider et réimporter</button> ";
    echo "<a href='index.php' class='btn btn-secondary'>Retour à l'accueil</a>";
    echo "</form>";
    exit();
}

// Traitement de l'import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_and_import') {
    // Vider la table
    $pdo->exec("DELETE FROM dnd_monsters");
    echo "<p style='color: green;'>✓ Table vidée</p>";
}

// Vérifier que le fichier existe
if (!file_exists('monstres.csv')) {
    echo "<h2>Erreur</h2>";
    echo "<p>Le fichier 'monstres.csv' n'existe pas dans le répertoire.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit();
}

// Lire le fichier CSV
$handle = fopen('monstres.csv', 'r');
if (!$handle) {
    echo "<h2>Erreur</h2>";
    echo "<p>Impossible d'ouvrir le fichier 'monstres.csv'.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit();
}

// Lire l'en-tête
$header = fgetcsv($handle);
if (!$header) {
    echo "<h2>Erreur</h2>";
    echo "<p>Le fichier CSV est vide ou corrompu.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit();
}

// Préparer la requête d'insertion
$stmt = $pdo->prepare("
    INSERT INTO dnd_monsters (name, type, size, alignment, challenge_rating, hit_points, armor_class, speed, proficiency_bonus, description, actions, special_abilities) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$inserted = 0;
$errors = 0;
$line = 2; // Commencer à la ligne 2 (après l'en-tête)

while (($data = fgetcsv($handle)) !== false) {
    try {
        // Nettoyer et valider les données
        $name = trim($data[1] ?? '');
        $type = trim($data[2] ?? '');
        $size = trim($data[3] ?? '');
        $alignment = trim($data[4] ?? '');
        $challenge_rating = trim($data[5] ?? '0');
        $hit_points = (int)($data[6] ?? 0);
        $armor_class = (int)($data[7] ?? 0);
        $speed = trim($data[8] ?? '');
        $proficiency_bonus = (int)($data[9] ?? 0);
        $description = trim($data[10] ?? '');
        $actions = trim($data[11] ?? '');
        $special_abilities = trim($data[12] ?? '');
        
        // Convertir le challenge rating (remplacer la virgule par un point)
        $challenge_rating = str_replace(',', '.', $challenge_rating);
        $challenge_rating = (float)$challenge_rating;
        
        // Valider les données essentielles
        if (empty($name)) {
            echo "<p style='color: orange;'>⚠ Ligne $line : Nom manquant, ignorée</p>";
            $errors++;
            $line++;
            continue;
        }
        
        // Insérer dans la base de données
        $stmt->execute([
            $name,
            $type,
            $size,
            $alignment,
            $challenge_rating,
            $hit_points,
            $armor_class,
            $speed,
            $proficiency_bonus,
            $description,
            $actions,
            $special_abilities
        ]);
        
        $inserted++;
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Ligne $line : Erreur lors de l'insertion de '$name' : " . $e->getMessage() . "</p>";
        $errors++;
    }
    
    $line++;
}

fclose($handle);

echo "<h2>Import terminé</h2>";
echo "<p style='color: green;'>✓ $inserted monstres importés avec succès</p>";

if ($errors > 0) {
    echo "<p style='color: orange;'>⚠ $errors erreurs rencontrées</p>";
}

echo "<div style='margin-top: 20px;'>";
echo "<a href='bestiary.php' class='btn btn-primary'>Parcourir le bestiaire</a> ";
echo "<a href='my_monsters.php' class='btn btn-warning'>Ma collection</a> ";
echo "<a href='index.php' class='btn btn-secondary'>Retour à l'accueil</a>";
echo "</div>";

// Afficher quelques statistiques
$stmt = $pdo->query("SELECT COUNT(*) as total, MIN(challenge_rating) as min_cr, MAX(challenge_rating) as max_cr FROM dnd_monsters");
$stats = $stmt->fetch();

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
echo "<h4>Statistiques du bestiaire</h4>";
echo "<p><strong>Total de monstres :</strong> " . $stats['total'] . "</p>";
echo "<p><strong>CR minimum :</strong> " . $stats['min_cr'] . "</p>";
echo "<p><strong>CR maximum :</strong> " . $stats['max_cr'] . "</p>";
echo "</div>";
?>
