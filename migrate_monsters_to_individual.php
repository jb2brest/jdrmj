<?php
require_once 'config/database.php';

echo "<h1>Migration des Monstres vers des Entités Individuelles</h1>";

try {
    // Vérifier s'il y a des monstres avec quantity > 1
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM scene_npcs 
        WHERE monster_id IS NOT NULL AND quantity > 1
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<p style='color: blue;'>ℹ Aucun monstre avec quantity > 1 trouvé. La migration n'est pas nécessaire.</p>";
        echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
        exit();
    }
    
    echo "<p><strong>Monstres à migrer :</strong> {$result['count']}</p>";
    
    // Récupérer tous les monstres avec quantity > 1
    $stmt = $pdo->prepare("
        SELECT id, scene_id, name, monster_id, quantity 
        FROM scene_npcs 
        WHERE monster_id IS NOT NULL AND quantity > 1
        ORDER BY scene_id, name
    ");
    $stmt->execute();
    $monstersToMigrate = $stmt->fetchAll();
    
    echo "<h3>Détail des monstres à migrer :</h3>";
    echo "<ul>";
    foreach ($monstersToMigrate as $monster) {
        echo "<li><strong>{$monster['name']}</strong> (Scene ID: {$monster['scene_id']}) - Quantité: {$monster['quantity']}</li>";
    }
    echo "</ul>";
    
    // Confirmation de l'utilisateur
    if (!isset($_POST['confirm_migration'])) {
        echo "<form method='POST'>";
        echo "<p><strong>Attention :</strong> Cette migration va :</p>";
        echo "<ul>";
        echo "<li>Supprimer les entrées avec quantity > 1</li>";
        echo "<li>Créer des entrées individuelles pour chaque monstre</li>";
        echo "<li>Permettre la gestion individuelle de chaque monstre</li>";
        echo "</ul>";
        echo "<p>Êtes-vous sûr de vouloir procéder à cette migration ?</p>";
        echo "<button type='submit' name='confirm_migration' class='btn btn-warning'>Confirmer la Migration</button>";
        echo "</form>";
        echo "<p><a href='index.php'>Annuler et retourner à l'accueil</a></p>";
        exit();
    }
    
    echo "<h3>Début de la migration...</h3>";
    
    $totalMigrated = 0;
    $totalCreated = 0;
    
    foreach ($monstersToMigrate as $monster) {
        echo "<p>Migration de <strong>{$monster['name']}</strong> (Scene ID: {$monster['scene_id']})...</p>";
        
        // Récupérer les informations du monstre depuis dnd_monsters
        $stmt = $pdo->prepare("SELECT name as monster_name FROM dnd_monsters WHERE id = ?");
        $stmt->execute([$monster['monster_id']]);
        $monsterInfo = $stmt->fetch();
        
        if ($monsterInfo) {
            $baseName = $monsterInfo['monster_name'];
            $quantity = $monster['quantity'];
            
            // Supprimer l'entrée originale
            $stmt = $pdo->prepare("DELETE FROM scene_npcs WHERE id = ?");
            $stmt->execute([$monster['id']]);
            
            // Créer des entrées individuelles
            for ($i = 0; $i < $quantity; $i++) {
                $individualName = $baseName;
                if ($quantity > 1) {
                    $individualName .= " #" . ($i + 1);
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO scene_npcs (scene_id, name, monster_id, quantity) 
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$monster['scene_id'], $individualName, $monster['monster_id']]);
                $totalCreated++;
            }
            
            $totalMigrated++;
            echo "<p style='color: green;'>✅ {$quantity} entrées créées pour {$baseName}</p>";
        } else {
            echo "<p style='color: red;'>❌ Erreur : Monstre introuvable dans dnd_monsters (ID: {$monster['monster_id']})</p>";
        }
    }
    
    echo "<h2>✅ Migration terminée avec succès !</h2>";
    echo "<p><strong>Résumé :</strong></p>";
    echo "<ul>";
    echo "<li>Monstres migrés : {$totalMigrated}</li>";
    echo "<li>Entrées individuelles créées : {$totalCreated}</li>";
    echo "</ul>";
    
    echo "<p><strong>Avantages de cette migration :</strong></p>";
    echo "<ul>";
    echo "<li>✅ Chaque monstre peut être géré individuellement</li>";
    echo "<li>✅ Suppression individuelle des monstres</li>";
    echo "<li>✅ Feuille de personnage individuelle pour chaque monstre</li>";
    echo "<li>✅ Équipement individuel possible</li>";
    echo "<li>✅ Meilleur contrôle du MJ sur chaque créature</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de la migration</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
echo "<p><a href='view_scene.php?id=1'>Voir une scène</a></p>";
?>











