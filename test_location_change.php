<?php
/**
 * Script de test pour simuler un changement de lieu d'un joueur
 * Ce script peut être utilisé pour tester la mise à jour automatique
 */

require_once 'config/database.php';

echo "=== Test de changement de lieu ===\n\n";

try {
    // Afficher l'état actuel
    echo "État actuel des joueurs:\n";
    $stmt = $pdo->query("
        SELECT 
            u.username, 
            p.title as place_title,
            p.id as place_id
        FROM users u
        LEFT JOIN place_players pp ON u.id = pp.player_id
        LEFT JOIN places p ON pp.place_id = p.id
        WHERE u.id IN (1, 2)
        ORDER BY u.id
    ");
    $players = $stmt->fetchAll();
    
    foreach ($players as $player) {
        if ($player['place_title']) {
            echo "- {$player['username']}: {$player['place_title']} (ID: {$player['place_id']})\n";
        } else {
            echo "- {$player['username']}: Aucun lieu\n";
        }
    }
    
    echo "\n";
    
    // Simuler un changement de lieu pour Robin
    echo "Simulation: Déplacer Robin vers un autre lieu...\n";
    
    // Trouver un autre lieu dans la même campagne
    $stmt = $pdo->prepare("
        SELECT p.id, p.title 
        FROM places p 
        JOIN campaigns c ON p.campaign_id = c.id
        WHERE c.id = (SELECT campaign_id FROM places WHERE id = 8)
        AND p.id != 8
        LIMIT 1
    ");
    $stmt->execute();
    $new_place = $stmt->fetch();
    
    if ($new_place) {
        // Déplacer Robin vers le nouveau lieu
        $stmt = $pdo->prepare("UPDATE place_players SET place_id = ? WHERE player_id = 1");
        $stmt->execute([$new_place['id']]);
        
        echo "✓ Robin déplacé vers: {$new_place['title']} (ID: {$new_place['id']})\n";
        echo "\nLa page view_scene_player.php devrait maintenant se recharger automatiquement !\n";
        
        // Remettre Robin à sa place originale après 10 secondes
        echo "\nRemise en place automatique dans 10 secondes...\n";
        sleep(10);
        
        $stmt = $pdo->prepare("UPDATE place_players SET place_id = 8 WHERE player_id = 1");
        $stmt->execute();
        
        echo "✓ Robin remis dans son lieu original\n";
        
    } else {
        echo "✗ Aucun autre lieu trouvé dans la campagne\n";
    }
    
    echo "\n=== Test terminé ===\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
