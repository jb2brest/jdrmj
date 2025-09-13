<?php
require_once 'config/database.php';

try {
    // Exécuter le script SQL
    $sql = file_get_contents('database/add_armor_proficiencies.sql');
    $pdo->exec($sql);
    
    echo "Colonnes armor_proficiencies, weapon_proficiencies et tool_proficiencies ajoutées avec succès.\n";
    
    // Vérifier les données
    $stmt = $pdo->query("SELECT name, armor_proficiencies, weapon_proficiencies, tool_proficiencies FROM classes LIMIT 3");
    $classes = $stmt->fetchAll();
    
    echo "\nExemples de classes mises à jour :\n";
    foreach ($classes as $class) {
        echo "- {$class['name']}:\n";
        echo "  Armure: {$class['armor_proficiencies']}\n";
        echo "  Armes: {$class['weapon_proficiencies']}\n";
        echo "  Outils: {$class['tool_proficiencies']}\n\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour : " . $e->getMessage() . "\n";
}
?>

