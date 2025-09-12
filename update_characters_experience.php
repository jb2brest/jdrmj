<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    // D'abord, importer les données d'expérience
    echo "Importation des données d'expérience...\n";
    $sql = file_get_contents('database/add_experience_table.sql');
    $pdo->exec($sql);
    echo "Données d'expérience importées.\n\n";
    
    // Mettre à jour tous les personnages existants
    echo "Mise à jour des personnages existants...\n";
    
    $stmt = $pdo->query("SELECT id, experience_points FROM characters");
    $characters = $stmt->fetchAll();
    
    $updatedCount = 0;
    foreach ($characters as $character) {
        if (updateCharacterLevelFromExperience($character['id'])) {
            $updatedCount++;
        }
    }
    
    echo "Nombre de personnages mis à jour : $updatedCount\n\n";
    
    // Afficher quelques exemples
    $stmt = $pdo->query("
        SELECT c.name, c.level, c.experience_points, c.proficiency_bonus 
        FROM characters c 
        ORDER BY c.experience_points DESC 
        LIMIT 5
    ");
    $examples = $stmt->fetchAll();
    
    echo "Exemples de personnages mis à jour :\n";
    foreach ($examples as $char) {
        echo "- {$char['name']}: Niveau {$char['level']}, {$char['experience_points']} XP, Bonus +{$char['proficiency_bonus']}\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour : " . $e->getMessage() . "\n";
}
?>
