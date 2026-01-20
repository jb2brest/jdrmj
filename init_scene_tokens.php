<?php
require_once 'config/database.php';

echo "<h1>Initialisation des Pions de Pièce</h1>";

try {
    // Créer la table si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS place_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            place_id INT NOT NULL,
            token_type ENUM('player', 'npc', 'monster') NOT NULL,
            entity_id INT NOT NULL,
            position_x INT DEFAULT 0,
            position_y INT DEFAULT 0,
            is_on_map BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
            UNIQUE KEY unique_scene_entity (place_id, token_type, entity_id),
            INDEX idx_place_id (place_id),
            INDEX idx_token_type (token_type),
            INDEX idx_entity_id (entity_id)
        )
    ");
    echo "✅ Table place_tokens créée/vérifiée<br>";

    // Initialiser les pions pour toutes les pièces existantes
    $stmt = $pdo->query("SELECT id FROM places ORDER BY id");
    $places = $stmt->fetchAll();

    foreach ($places as $scene) {
        $place_id = $scene['id'];
        echo "<h2>Pièce ID: $place_id</h2>";

        // Initialiser les pions des joueurs
        $stmt = $pdo->prepare("
            SELECT sp.player_id, sp.character_id, u.username, ch.name as character_name, ch.profile_photo
            FROM place_players sp 
            JOIN users u ON sp.player_id = u.id 
            LEFT JOIN characters ch ON sp.character_id = ch.id 
            WHERE sp.place_id = ?
        ");
        $stmt->execute([$place_id]);
        $players = $stmt->fetchAll();

        foreach ($players as $player) {
            // Créer un pion pour chaque joueur, même sans personnage
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map) 
                VALUES (?, 'player', ?, 0, 0, FALSE)
            ");
            $stmt->execute([$place_id, $player['player_id']]);
            $characterName = $player['character_name'] ?: $player['username'];
            echo "✅ Pion joueur: {$characterName} (ID: {$player['player_id']})<br>";
        }

        // Initialiser les pions des PNJ
        $stmt = $pdo->prepare("
            SELECT sn.id, sn.name, sn.profile_photo, c.profile_photo as character_profile_photo
            FROM place_npcs sn 
            LEFT JOIN characters c ON sn.npc_character_id = c.id 
            WHERE sn.place_id = ? AND sn.monster_id IS NULL
        ");
        $stmt->execute([$place_id]);
        $npcs = $stmt->fetchAll();

        foreach ($npcs as $npc) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map) 
                VALUES (?, 'npc', ?, 0, 0, FALSE)
            ");
            $stmt->execute([$place_id, $npc['id']]);
            echo "✅ Pion PNJ: {$npc['name']} (ID: {$npc['id']})<br>";
        }

        // Initialiser les pions des monstres
        $stmt = $pdo->prepare("
            SELECT sn.id, sn.name, sn.quantity, m.type, m.size
            FROM place_npcs sn 
            JOIN dnd_monsters m ON sn.monster_id = m.id 
            WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL
        ");
        $stmt->execute([$place_id]);
        $monsters = $stmt->fetchAll();

        foreach ($monsters as $monster) {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO place_tokens (place_id, token_type, entity_id, position_x, position_y, is_on_map) 
                VALUES (?, 'monster', ?, 0, 0, FALSE)
            ");
            $stmt->execute([$place_id, $monster['id']]);
            echo "✅ Pion monstre: {$monster['name']} (ID: {$monster['id']})<br>";
        }
    }

    echo "<h2>✅ Initialisation terminée</h2>";
    echo "<p>Tous les pions ont été initialisés avec des positions par défaut (côté du plan).</p>";

} catch (Exception $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
