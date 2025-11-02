<?php
/**
 * Script de correction pour mettre à jour les items des NPCs
 * qui ont owner_type='player' alors qu'ils devraient avoir owner_type='npc'
 */

require_once 'classes/init.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté et est MJ ou Admin
if (!isLoggedIn()) {
    die('Vous devez être connecté pour exécuter ce script.');
}

if (!User::isDMOrAdmin()) {
    die('Vous devez être MJ ou Admin pour exécuter ce script.');
}

$pdo = getPDO();

try {
    // Trouver tous les items avec owner_type='player' dont l'owner_id correspond à un NPC
    $stmt = $pdo->prepare("
        SELECT i.id, i.owner_id, i.display_name, n.name as npc_name
        FROM items i
        INNER JOIN npcs n ON i.owner_id = n.id
        WHERE i.owner_type = 'player'
        ORDER BY i.owner_id, i.id
    ");
    $stmt->execute();
    $itemsToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($itemsToFix)) {
        echo "<h2>Aucun item à corriger</h2>";
        echo "<p>Tous les items des NPCs ont déjà le bon owner_type='npc'.</p>";
        exit;
    }
    
    echo "<h2>Correction des items des NPCs</h2>";
    echo "<p>Nombre d'items à corriger : " . count($itemsToFix) . "</p>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID Item</th><th>ID NPC</th><th>Nom NPC</th><th>Nom Item</th><th>Statut</th></tr>";
    
    $pdo->beginTransaction();
    
    $fixedCount = 0;
    $errorCount = 0;
    
    foreach ($itemsToFix as $item) {
        try {
            // Vérifier que l'owner_id correspond bien à un NPC
            $npcStmt = $pdo->prepare("SELECT id FROM npcs WHERE id = ?");
            $npcStmt->execute([$item['owner_id']]);
            $npc = $npcStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($npc) {
                // Mettre à jour l'owner_type
                $updateStmt = $pdo->prepare("UPDATE items SET owner_type = 'npc' WHERE id = ?");
                $updateStmt->execute([$item['id']]);
                
                echo "<tr><td>{$item['id']}</td><td>{$item['owner_id']}</td><td>{$item['npc_name']}</td><td>{$item['display_name']}</td><td style='color:green;'>✓ Corrigé</td></tr>";
                $fixedCount++;
            } else {
                echo "<tr><td>{$item['id']}</td><td>{$item['owner_id']}</td><td>N/A</td><td>{$item['display_name']}</td><td style='color:orange;'>⚠ NPC non trouvé</td></tr>";
                $errorCount++;
            }
        } catch (Exception $e) {
            echo "<tr><td>{$item['id']}</td><td>{$item['owner_id']}</td><td>{$item['npc_name']}</td><td>{$item['display_name']}</td><td style='color:red;'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
            $errorCount++;
        }
    }
    
    if ($_POST['confirm'] ?? false) {
        $pdo->commit();
        echo "</table>";
        echo "<p style='color:green;'><strong>✓ Correction terminée !</strong></p>";
        echo "<p>Items corrigés : $fixedCount</p>";
        if ($errorCount > 0) {
            echo "<p style='color:orange;'>Items avec erreurs : $errorCount</p>";
        }
    } else {
        $pdo->rollBack();
        echo "</table>";
        echo "<p><strong>Simulation terminée. Aucune modification n'a été effectuée.</strong></p>";
        echo "<p>Items qui seraient corrigés : $fixedCount</p>";
        if ($errorCount > 0) {
            echo "<p style='color:orange;'>Items avec problèmes : $errorCount</p>";
        }
        echo "<form method='POST' style='margin-top:20px;'>";
        echo "<input type='hidden' name='confirm' value='1'>";
        echo "<button type='submit' style='padding:10px 20px; background:#28a745; color:white; border:none; cursor:pointer;'>Confirmer et appliquer les corrections</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red;'><strong>Erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>

