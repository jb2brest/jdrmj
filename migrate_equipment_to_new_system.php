<?php
/**
 * Script de migration de l'équipement vers le nouveau système
 * Migre l'équipement depuis items vers character_equipment
 */

require_once 'config/database.php';

echo "<h1>Migration de l'équipement vers le nouveau système</h1>\n";

try {
    // 1. Créer les tables d'équipement si elles n'existent pas
    echo "<h2>1. Création des tables d'équipement...</h2>\n";
    
    $sql = file_get_contents('database/create_equipment_tables.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Tables d'équipement créées avec succès<br>\n";
    
    // 2. Migrer l'équipement des personnages depuis items
    echo "<h2>2. Migration de l'équipement des personnages...</h2>\n";
    
    // Récupérer tous les objets des personnages depuis items
    $stmt = $pdo->prepare("
        SELECT 
            po.*,
            c.name as character_name
        FROM items po
        JOIN characters c ON po.owner_id = c.id
        WHERE po.owner_type = 'player'
        AND po.owner_id IS NOT NULL
        ORDER BY po.owner_id, po.obtained_at
    ");
    $stmt->execute();
    $equipmentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migratedCount = 0;
    $skippedCount = 0;
    
    foreach ($equipmentItems as $item) {
        // Déterminer le type d'objet
        $itemType = 'Objet';
        if ($item['weapon_id']) {
            $itemType = 'Arme';
        } elseif ($item['armor_id']) {
            $itemType = 'Armure';
        } elseif ($item['magical_item_id']) {
            $itemType = 'Objet magique';
        } elseif ($item['poison_id']) {
            $itemType = 'Poison';
        }
        
        // Déterminer la source
        $source = $item['item_source'] ?? 'Inconnue';
        if (empty($source)) {
            $source = $item['obtained_from'] ?? 'Inconnue';
        }
        
        // Déterminer si l'objet est équipé
        $equipped = false;
        if ($item['is_equipped'] == 1) {
            $equipped = true;
        }
        
        // Insérer dans character_equipment
        try {
            $stmt = $pdo->prepare("
                INSERT INTO character_equipment 
                (character_id, magical_item_id, item_name, item_type, item_description, 
                 item_source, quantity, equipped, notes, obtained_at, obtained_from)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $item['owner_id'],
                $item['magical_item_id'],
                $item['display_name'],
                $itemType,
                $item['description'],
                $source,
                $item['quantity'] ?? 1,
                $equipped,
                $item['notes'],
                $item['obtained_at'] ?? date('Y-m-d H:i:s'),
                $item['obtained_from'] ?? 'Migration'
            ]);
            
            $migratedCount++;
            
        } catch (Exception $e) {
            echo "⚠️ Erreur lors de la migration de l'objet '{$item['display_name']}' pour le personnage '{$item['character_name']}': " . $e->getMessage() . "<br>\n";
            $skippedCount++;
        }
    }
    
    echo "✓ $migratedCount objets migrés avec succès<br>\n";
    if ($skippedCount > 0) {
        echo "⚠️ $skippedCount objets ignorés à cause d'erreurs<br>\n";
    }
    
    // 3. Migrer l'équipement des PNJ
    echo "<h2>3. Migration de l'équipement des PNJ...</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            po.*,
            n.name as npc_name
        FROM items po
        JOIN npcs n ON po.owner_id = n.id
        WHERE po.owner_type = 'npc'
        AND po.owner_id IS NOT NULL
        ORDER BY po.owner_id, po.obtained_at
    ");
    $stmt->execute();
    $npcEquipmentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $npcMigratedCount = 0;
    $npcSkippedCount = 0;
    
    foreach ($npcEquipmentItems as $item) {
        // Déterminer le type d'objet
        $itemType = 'Objet';
        if ($item['weapon_id']) {
            $itemType = 'Arme';
        } elseif ($item['armor_id']) {
            $itemType = 'Armure';
        } elseif ($item['magical_item_id']) {
            $itemType = 'Objet magique';
        } elseif ($item['poison_id']) {
            $itemType = 'Poison';
        }
        
        // Déterminer la source
        $source = $item['item_source'] ?? 'Inconnue';
        if (empty($source)) {
            $source = $item['obtained_from'] ?? 'Inconnue';
        }
        
        // Déterminer si l'objet est équipé
        $equipped = false;
        if ($item['is_equipped'] == 1) {
            $equipped = true;
        }
        
        // Récupérer le scene_id du PNJ
        $sceneId = $item['place_id'] ?? null;
        
        // Insérer dans npc_equipment
        try {
            $stmt = $pdo->prepare("
                INSERT INTO npc_equipment 
                (npc_id, scene_id, magical_item_id, item_name, item_type, item_description, 
                 item_source, quantity, equipped, notes, obtained_at, obtained_from)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $item['owner_id'],
                $sceneId,
                $item['magical_item_id'],
                $item['display_name'],
                $itemType,
                $item['description'],
                $source,
                $item['quantity'] ?? 1,
                $equipped,
                $item['notes'],
                $item['obtained_at'] ?? date('Y-m-d H:i:s'),
                $item['obtained_from'] ?? 'Migration'
            ]);
            
            $npcMigratedCount++;
            
        } catch (Exception $e) {
            echo "⚠️ Erreur lors de la migration de l'objet '{$item['display_name']}' pour le PNJ '{$item['npc_name']}': " . $e->getMessage() . "<br>\n";
            $npcSkippedCount++;
        }
    }
    
    echo "✓ $npcMigratedCount objets de PNJ migrés avec succès<br>\n";
    if ($npcSkippedCount > 0) {
        echo "⚠️ $npcSkippedCount objets de PNJ ignorés à cause d'erreurs<br>\n";
    }
    
    // 4. Migrer l'équipement des monstres
    echo "<h2>4. Migration de l'équipement des monstres...</h2>\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            po.*,
            m.name as monster_name
        FROM items po
        JOIN monsters m ON po.owner_id = m.id
        WHERE po.owner_type = 'monster'
        AND po.owner_id IS NOT NULL
        ORDER BY po.owner_id, po.obtained_at
    ");
    $stmt->execute();
    $monsterEquipmentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $monsterMigratedCount = 0;
    $monsterSkippedCount = 0;
    
    foreach ($monsterEquipmentItems as $item) {
        // Déterminer le type d'objet
        $itemType = 'Objet';
        if ($item['weapon_id']) {
            $itemType = 'Arme';
        } elseif ($item['armor_id']) {
            $itemType = 'Armure';
        } elseif ($item['magical_item_id']) {
            $itemType = 'Objet magique';
        } elseif ($item['poison_id']) {
            $itemType = 'Poison';
        }
        
        // Déterminer la source
        $source = $item['item_source'] ?? 'Inconnue';
        if (empty($source)) {
            $source = $item['obtained_from'] ?? 'Inconnue';
        }
        
        // Déterminer si l'objet est équipé
        $equipped = false;
        if ($item['is_equipped'] == 1) {
            $equipped = true;
        }
        
        // Récupérer le scene_id du monstre
        $sceneId = $item['place_id'] ?? null;
        
        // Insérer dans monster_equipment
        try {
            $stmt = $pdo->prepare("
                INSERT INTO monster_equipment 
                (monster_id, scene_id, magical_item_id, item_name, item_type, item_description, 
                 item_source, quantity, equipped, notes, obtained_at, obtained_from)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $item['owner_id'],
                $sceneId,
                $item['magical_item_id'],
                $item['display_name'],
                $itemType,
                $item['description'],
                $source,
                $item['quantity'] ?? 1,
                $equipped,
                $item['notes'],
                $item['obtained_at'] ?? date('Y-m-d H:i:s'),
                $item['obtained_from'] ?? 'Migration'
            ]);
            
            $monsterMigratedCount++;
            
        } catch (Exception $e) {
            echo "⚠️ Erreur lors de la migration de l'objet '{$item['display_name']}' pour le monstre '{$item['monster_name']}': " . $e->getMessage() . "<br>\n";
            $monsterSkippedCount++;
        }
    }
    
    echo "✓ $monsterMigratedCount objets de monstres migrés avec succès<br>\n";
    if ($monsterSkippedCount > 0) {
        echo "⚠️ $monsterSkippedCount objets de monstres ignorés à cause d'erreurs<br>\n";
    }
    
    // 5. Vérification finale
    echo "<h2>5. Vérification finale...</h2>\n";
    
    // Compter les objets dans les nouvelles tables
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM character_equipment");
    $stmt->execute();
    $characterEquipmentCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM npc_equipment");
    $stmt->execute();
    $npcEquipmentCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM monster_equipment");
    $stmt->execute();
    $monsterEquipmentCount = $stmt->fetchColumn();
    
    echo "📊 Statistiques finales:<br>\n";
    echo "- Objets de personnages migrés: $characterEquipmentCount<br>\n";
    echo "- Objets de PNJ migrés: $npcEquipmentCount<br>\n";
    echo "- Objets de monstres migrés: $monsterEquipmentCount<br>\n";
    echo "- Total objets migrés: " . ($characterEquipmentCount + $npcEquipmentCount + $monsterEquipmentCount) . "<br>\n";
    
    // Vérifier quelques exemples
    echo "<h3>Exemples d'objets migrés:</h3>\n";
    
    $stmt = $pdo->prepare("
        SELECT ce.item_name, ce.item_type, ce.item_source, c.name as character_name
        FROM character_equipment ce
        JOIN characters c ON ce.character_id = c.id
        ORDER BY ce.obtained_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($examples as $example) {
        echo "- {$example['item_name']} ({$example['item_type']}) - {$example['character_name']} - Source: {$example['item_source']}<br>\n";
    }
    
    echo "<h2>✅ Migration terminée avec succès !</h2>\n";
    echo "<p>L'équipement a été migré vers le nouveau système. Vous pouvez maintenant utiliser les pages d'équipement dédiées.</p>\n";
    echo "<p><strong>Note:</strong> Les anciens objets dans items sont conservés pour référence, mais le nouveau système utilise les tables d'équipement dédiées.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors de la migration</h2>\n";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
h1 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; }
h3 { color: #666; margin-top: 20px; }
</style>
