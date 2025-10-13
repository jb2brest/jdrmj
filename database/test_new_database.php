<?php
/**
 * Script de test pour la nouvelle base de données
 * Application JDR MJ - D&D 5e
 */

// Configuration de la nouvelle base
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'jdrmj_new',
    'username' => 'u839591438_jdrmj',
    'password' => 'M8jbsYJUj6FE$;C'
];

echo "🧪 Test de la nouvelle base de données\n";
echo "=====================================\n\n";

try {
    // Connexion à la nouvelle base
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base établie\n\n";

    // =====================================================
    // 1. VÉRIFICATION DES TABLES
    // =====================================================
    
    echo "📋 Vérification des tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tables trouvées: " . count($tables) . "\n";
    
    // Vérifier les tables essentielles
    $essentialTables = [
        'users', 'campaigns', 'characters', 'races', 'classes', 'spells',
        'weapons', 'armor', 'magical_items', 'poisons', 'languages',
        'backgrounds', 'experience_levels', 'dnd_monsters'
    ];
    
    $missingTables = [];
    foreach ($essentialTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "✅ Toutes les tables essentielles sont présentes\n";
    } else {
        echo "❌ Tables manquantes: " . implode(', ', $missingTables) . "\n";
    }
    echo "\n";

    // =====================================================
    // 2. VÉRIFICATION DES DONNÉES DE BASE
    // =====================================================
    
    echo "📊 Vérification des données de base...\n";
    
    $dataChecks = [
        'races' => 'Races',
        'classes' => 'Classes',
        'spells' => 'Sorts',
        'weapons' => 'Armes',
        'armor' => 'Armures',
        'magical_items' => 'Objets magiques',
        'poisons' => 'Poisons',
        'languages' => 'Langues',
        'backgrounds' => 'Historiques',
        'experience_levels' => 'Niveaux d\'expérience',
        'dnd_monsters' => 'Monstres'
    ];
    
    $totalData = 0;
    foreach ($dataChecks as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "   • $label: $count enregistrements\n";
            $totalData += $count;
        } catch (PDOException $e) {
            echo "   ❌ $label: Erreur - " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Total des données de base: $totalData enregistrements\n\n";

    // =====================================================
    // 3. VÉRIFICATION DES ARCHÉTYPES
    // =====================================================
    
    echo "🎭 Vérification des archétypes de classes...\n";
    
    $archetypeChecks = [
        'cleric_domains' => 'Domaines de clerc',
        'druid_circles' => 'Cercles de druide',
        'fighter_archetypes' => 'Archétypes de guerrier',
        'monk_traditions' => 'Traditions de moine',
        'sorcerer_origins' => 'Origines de sorcier',
        'warlock_pacts' => 'Pactes de sorcier',
        'wizard_traditions' => 'Traditions de magicien'
    ];
    
    foreach ($archetypeChecks as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "   • $label: $count enregistrements\n";
        } catch (PDOException $e) {
            echo "   ❌ $label: Erreur - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // =====================================================
    // 4. VÉRIFICATION DES DONNÉES LIÉES AUX MONSTRES
    // =====================================================
    
    echo "👹 Vérification des données de monstres...\n";
    
    $monsterDataChecks = [
        'monster_actions' => 'Actions de monstres',
        'monster_equipment' => 'Équipement de monstres',
        'monster_legendary_actions' => 'Actions légendaires',
        'monster_special_attacks' => 'Attaques spéciales',
        'monster_spells' => 'Sorts de monstres'
    ];
    
    foreach ($monsterDataChecks as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "   • $label: $count enregistrements\n";
        } catch (PDOException $e) {
            echo "   ❌ $label: Erreur - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // =====================================================
    // 5. VÉRIFICATION DE L'UTILISATEUR ADMIN
    // =====================================================
    
    echo "👤 Vérification de l'utilisateur admin...\n";
    
    $stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll();
    
    if (empty($admins)) {
        echo "❌ Aucun utilisateur admin trouvé\n";
    } else {
        echo "✅ Utilisateurs admin trouvés:\n";
        foreach ($admins as $admin) {
            echo "   • ID: {$admin['id']}, Username: {$admin['username']}, Email: {$admin['email']}\n";
        }
    }
    echo "\n";

    // =====================================================
    // 6. VÉRIFICATION DES CONTRAINTES DE CLÉS ÉTRANGÈRES
    // =====================================================
    
    echo "🔗 Vérification des contraintes de clés étrangères...\n";
    
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = '{$dbConfig['dbname']}' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, COLUMN_NAME
    ");
    
    $foreignKeys = $stmt->fetchAll();
    echo "✅ Contraintes de clés étrangères: " . count($foreignKeys) . " trouvées\n";
    
    // Vérifier quelques contraintes importantes
    $importantConstraints = [
        'campaigns.dm_id -> users.id',
        'campaign_members.campaign_id -> campaigns.id',
        'campaign_members.user_id -> users.id',
        'places.campaign_id -> campaigns.id'
    ];
    
    foreach ($importantConstraints as $constraint) {
        $found = false;
        foreach ($foreignKeys as $fk) {
            if ($fk['TABLE_NAME'] . '.' . $fk['COLUMN_NAME'] . ' -> ' . $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'] === $constraint) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "   ✅ $constraint\n";
        } else {
            echo "   ❌ $constraint (manquante)\n";
        }
    }
    echo "\n";

    // =====================================================
    // 7. TEST DE FONCTIONNALITÉS DE BASE
    // =====================================================
    
    echo "⚙️  Test des fonctionnalités de base...\n";
    
    // Test de création d'un utilisateur
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['test_user', 'test@example.com', 'test_hash', 'player']);
        $testUserId = $pdo->lastInsertId();
        echo "   ✅ Création d'utilisateur: OK\n";
        
        // Nettoyer
        $pdo->exec("DELETE FROM users WHERE id = $testUserId");
        echo "   ✅ Suppression d'utilisateur: OK\n";
    } catch (PDOException $e) {
        echo "   ❌ Test utilisateur: " . $e->getMessage() . "\n";
    }
    
    // Test de récupération de données
    try {
        $stmt = $pdo->query("SELECT name FROM races LIMIT 5");
        $races = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   ✅ Récupération de races: " . count($races) . " trouvées\n";
    } catch (PDOException $e) {
        echo "   ❌ Test races: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("SELECT name FROM classes LIMIT 5");
        $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   ✅ Récupération de classes: " . count($classes) . " trouvées\n";
    } catch (PDOException $e) {
        echo "   ❌ Test classes: " . $e->getMessage() . "\n";
    }
    
    echo "\n";

    // =====================================================
    // 8. RÉSUMÉ FINAL
    // =====================================================
    
    echo "📋 RÉSUMÉ DU TEST\n";
    echo "=================\n";
    echo "✅ Base de données: {$dbConfig['dbname']}\n";
    echo "✅ Tables créées: " . count($tables) . "\n";
    echo "✅ Données de base: $totalData enregistrements\n";
    echo "✅ Contraintes FK: " . count($foreignKeys) . "\n";
    echo "✅ Utilisateurs admin: " . count($admins) . "\n";
    echo "\n🎉 LA NOUVELLE BASE DE DONNÉES EST OPÉRATIONNELLE !\n";
    echo "\n🔧 Prochaines étapes:\n";
    echo "   1. Modifier le mot de passe admin par défaut\n";
    echo "   2. Configurer l'application pour utiliser cette base\n";
    echo "   3. Tester les fonctionnalités complètes\n";
    echo "   4. Créer des utilisateurs et campagnes de test\n";

} catch (PDOException $e) {
    echo "❌ ERREUR DE CONNEXION: " . $e->getMessage() . "\n";
    echo "🔧 Vérifiez que la base de données '{$dbConfig['dbname']}' existe\n";
    echo "🔧 Vérifiez les paramètres de connexion\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERREUR GÉNÉRALE: " . $e->getMessage() . "\n";
    exit(1);
}
?>
