<?php
require_once 'config/database.php';

try {
    // Création de la table experience_levels
    $sql = file_get_contents('database/add_experience_table.sql');
    $pdo->exec($sql);
    
    echo "Table experience_levels créée avec succès.\n";
    
    // Vérification des données importées
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM experience_levels");
    $count = $stmt->fetch()['count'];
    
    echo "Nombre de niveaux importés : $count\n";
    
    // Affichage des premiers niveaux pour vérification
    $stmt = $pdo->query("SELECT * FROM experience_levels ORDER BY level LIMIT 5");
    $levels = $stmt->fetchAll();
    
    echo "\nPremiers niveaux importés :\n";
    foreach ($levels as $level) {
        echo "Niveau {$level['level']}: {$level['experience_points_required']} XP, Bonus: +{$level['proficiency_bonus']}\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de l'importation des données d'expérience : " . $e->getMessage() . "\n";
}
?>
