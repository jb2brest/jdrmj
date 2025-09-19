<?php
/**
 * Script d'initialisation simplifié d'une nouvelle base de données
 * Application JDR MJ - D&D 5e
 * 
 * Ce script copie la structure et les données de base depuis la base existante
 */

// Configuration
$sourceDb = [
    'host' => 'localhost',
    'dbname' => 'u839591438_jdrmj',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C'
];

$targetDb = [
    'host' => 'localhost',
    'dbname' => 'jdrmj_new',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C'
];

echo "🚀 Initialisation simplifiée d'une nouvelle base de données JDR MJ\n";
echo "================================================================\n\n";

try {
    // =====================================================
    // 1. CONNEXION À LA BASE SOURCE
    // =====================================================
    
    echo "📡 Connexion à la base source...\n";
    $sourceDsn = "mysql:host={$sourceDb['host']};dbname={$sourceDb['dbname']};charset=utf8mb4";
    $sourcePdo = new PDO($sourceDsn, $sourceDb['username'], $sourceDb['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    echo "✅ Connexion à la base source établie\n\n";

    // =====================================================
    // 2. CRÉATION DE LA NOUVELLE BASE DE DONNÉES
    // =====================================================
    
    echo "🏗️  Création de la nouvelle base de données...\n";
    $createDbDsn = "mysql:host={$targetDb['host']};charset=utf8mb4";
    $createDbPdo = new PDO($createDbDsn, $targetDb['username'], $targetDb['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    $createDbPdo->exec("DROP DATABASE IF EXISTS {$targetDb['dbname']}");
    $createDbPdo->exec("CREATE DATABASE {$targetDb['dbname']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données '{$targetDb['dbname']}' créée\n\n";

    // =====================================================
    // 3. CONNEXION À LA NOUVELLE BASE
    // =====================================================
    
    echo "🔗 Connexion à la nouvelle base...\n";
    $targetDsn = "mysql:host={$targetDb['host']};dbname={$targetDb['dbname']};charset=utf8mb4";
    $targetPdo = new PDO($targetDsn, $targetDb['username'], $targetDb['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    echo "✅ Connexion à la nouvelle base établie\n\n";

    // =====================================================
    // 4. EXPORTATION DE LA STRUCTURE COMPLÈTE
    // =====================================================
    
    echo "📋 Exportation de la structure complète...\n";
    
    // Obtenir toutes les tables
    $stmt = $sourcePdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tables trouvées: " . count($tables) . "\n";
    
    // Exporter la structure de chaque table
    foreach ($tables as $table) {
        try {
            // Obtenir la structure de la table
            $stmt = $sourcePdo->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            $createSql = $createTable['Create Table'];
            
            // Exécuter la création de la table
            $targetPdo->exec($createSql);
            echo "   ✅ Table '$table' créée\n";
            
        } catch (PDOException $e) {
            echo "   ❌ Erreur table '$table': " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // =====================================================
    // 5. EXPORTATION DES DONNÉES DE BASE (SANS DONNÉES UTILISATEUR)
    // =====================================================
    
    echo "📤 Exportation des données de base...\n";
    
    // Tables de données de base (sans données utilisateur)
    $baseDataTables = [
        'races', 'classes', 'spells', 'weapons', 'armor', 'magical_items', 
        'poisons', 'languages', 'backgrounds', 'experience_levels',
        'cleric_domains', 'druid_circles', 'fighter_archetypes', 
        'monk_traditions', 'sorcerer_origins', 'warlock_pacts', 
        'wizard_traditions', 'dnd_monsters', 'monster_actions', 
        'monster_equipment', 'monster_legendary_actions', 
        'monster_special_attacks', 'monster_spells', 'countries', 'regions'
    ];
    
    $importedCount = 0;
    
    foreach ($baseDataTables as $table) {
        try {
            // Vérifier si la table existe
            $stmt = $sourcePdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) {
                echo "   ⚠️  Table '$table' non trouvée, ignorée\n";
                continue;
            }
            
            // Récupérer les données
            $stmt = $sourcePdo->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll();
            
            if (empty($rows)) {
                echo "   ⚠️  Table '$table' vide, ignorée\n";
                continue;
            }
            
            // Obtenir les colonnes
            $stmt = $sourcePdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $columnList = implode(', ', $columns);
            
            // Préparer l'insertion
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            $insertSql = "INSERT INTO $table ($columnList) VALUES ($placeholders)";
            $insertStmt = $targetPdo->prepare($insertSql);
            
            // Insérer les données
            foreach ($rows as $row) {
                $insertStmt->execute(array_values($row));
            }
            
            echo "   ✅ Table '$table': " . count($rows) . " enregistrements importés\n";
            $importedCount += count($rows);
            
        } catch (PDOException $e) {
            echo "   ❌ Erreur table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📥 Importation des données terminée: $importedCount enregistrements\n\n";

    // =====================================================
    // 6. CRÉATION D'UN UTILISATEUR ADMIN
    // =====================================================
    
    echo "👤 Création de l'utilisateur admin...\n";
    
    try {
        // Vérifier si la table users existe
        $stmt = $targetPdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->fetch()) {
            // Créer un utilisateur admin
            $stmt = $targetPdo->prepare("INSERT INTO users (username, email, password_hash, role, is_dm) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@jdrmj.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE]);
            echo "   ✅ Utilisateur admin créé (admin/admin123)\n";
        } else {
            echo "   ⚠️  Table 'users' non trouvée, utilisateur admin non créé\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur création admin: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // =====================================================
    // 7. VÉRIFICATION FINALE
    // =====================================================
    
    echo "🔍 Vérification finale...\n";
    
    // Compter les tables créées
    $stmt = $targetPdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tables créées: " . count($tables) . "\n";
    
    // Vérifier l'utilisateur admin
    try {
        $stmt = $targetPdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetch()['count'];
        echo "✅ Utilisateurs admin: $adminCount\n";
    } catch (PDOException $e) {
        echo "⚠️  Table users non accessible\n";
    }
    
    // Compter les données de base
    $totalData = 0;
    foreach ($baseDataTables as $table) {
        try {
            $stmt = $targetPdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $totalData += $count;
        } catch (PDOException $e) {
            // Table n'existe pas ou erreur
        }
    }
    echo "✅ Données de base importées: $totalData enregistrements\n";

    // =====================================================
    // 8. MESSAGE DE SUCCÈS
    // =====================================================
    
    echo "\n🎉 INITIALISATION TERMINÉE AVEC SUCCÈS !\n";
    echo "========================================\n";
    echo "📊 Résumé:\n";
    echo "   • Base de données: {$targetDb['dbname']}\n";
    echo "   • Tables créées: " . count($tables) . "\n";
    echo "   • Données importées: $totalData enregistrements\n";
    echo "   • Utilisateur admin: admin/admin123\n";
    echo "\n🔧 Prochaines étapes:\n";
    echo "   1. Modifier le mot de passe admin\n";
    echo "   2. Configurer l'application pour utiliser cette base\n";
    echo "   3. Tester les fonctionnalités\n";
    echo "\n✨ La nouvelle base est prête à l'emploi !\n";

} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "🔧 Vérifiez la configuration de la base de données\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR GÉNÉRALE: " . $e->getMessage() . "\n";
    exit(1);
}
?>
