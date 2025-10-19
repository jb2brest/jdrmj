<?php
/**
 * Script pour ajouter la colonne is_npc à la table characters
 * À exécuter une seule fois pour migrer la base de données
 */

require_once 'config/database.php';

try {
    echo "🔄 Début de la migration pour ajouter la colonne is_npc...\n";
    
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM characters LIKE 'is_npc'");
    if ($stmt->rowCount() > 0) {
        echo "✅ La colonne is_npc existe déjà dans la table characters.\n";
        exit(0);
    }
    
    // Ajouter la colonne is_npc
    $pdo->exec("ALTER TABLE characters ADD COLUMN is_npc TINYINT(1) DEFAULT 0");
    echo "✅ Colonne is_npc ajoutée avec succès.\n";
    
    // Ajouter un index pour améliorer les performances
    $pdo->exec("CREATE INDEX idx_characters_is_npc ON characters(is_npc)");
    echo "✅ Index idx_characters_is_npc créé avec succès.\n";
    
    echo "🎉 Migration terminée avec succès !\n";
    echo "📝 La fonctionnalité de création automatique de PNJ est maintenant disponible.\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}
?>
