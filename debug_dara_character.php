<?php
// Script de debug pour Dara personnage
echo "Debug de Dara personnage\n";
echo "========================\n\n";

require_once 'classes/init.php';

$place_id = 154;

// 1. Chercher Dara dans les personnages
echo "1. Recherche de Dara dans les personnages :\n";
$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT c.*, u.username as owner_name
    FROM characters c
    JOIN users u ON c.user_id = u.id
    WHERE c.name LIKE '%Dara%'
    ORDER BY c.name
");
$stmt->execute();
$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($characters)) {
    foreach ($characters as $character) {
        echo "- Personnage ID: " . $character['id'] . "\n";
        echo "  Nom: " . $character['name'] . "\n";
        echo "  Propriétaire: " . $character['owner_name'] . "\n";
        echo "  Classe ID: " . $character['class_id'] . "\n";
        echo "  Niveau: " . $character['level'] . "\n";
        echo "  Créé le: " . $character['created_at'] . "\n";
        
        // Vérifier si ce personnage est dans le lieu 154
        echo "\n2. Vérification de la présence dans le lieu 154 :\n";
        $stmt2 = $pdo->prepare("
            SELECT pc.*, p.title as place_name
            FROM place_characters pc
            JOIN places p ON pc.place_id = p.id
            WHERE pc.character_id = ? AND pc.place_id = ?
        ");
        $stmt2->execute([$character['id'], $place_id]);
        $place_character = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($place_character) {
            echo "  SUCCÈS: Dara est dans le lieu " . $place_character['place_name'] . "\n";
            echo "  Ajouté le: " . $place_character['created_at'] . "\n";
        } else {
            echo "  PROBLÈME: Dara n'est pas dans le lieu 154\n";
        }
        
        // Vérifier dans quels lieux Dara se trouve
        echo "\n3. Lieux où se trouve Dara :\n";
        $stmt3 = $pdo->prepare("
            SELECT pc.*, p.title as place_name
            FROM place_characters pc
            JOIN places p ON pc.place_id = p.id
            WHERE pc.character_id = ?
            ORDER BY p.title
        ");
        $stmt3->execute([$character['id']]);
        $places = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($places)) {
            foreach ($places as $place) {
                echo "  - Lieu ID: " . $place['place_id'] . " (" . $place['place_name'] . ")\n";
            }
        } else {
            echo "  Aucun lieu trouvé\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
    }
} else {
    echo "Aucun personnage trouvé avec 'Dara' dans le nom\n";
}

// 4. Vérifier tous les personnages pour voir s'il y a des noms similaires
echo "\n4. Tous les personnages :\n";
$stmt = $pdo->prepare("
    SELECT c.id, c.name, u.username as owner_name
    FROM characters c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.name
");
$stmt->execute();
$all_characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_characters as $character) {
    echo "- " . $character['name'] . " (propriétaire: " . $character['owner_name'] . ")\n";
}

echo "\nDebug terminé.\n";
?>
