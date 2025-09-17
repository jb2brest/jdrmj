<?php
/**
 * Script de test de la configuration des bases de données
 * Usage: php test_database_config.php [test|staging|production]
 */

// Fonction pour tester la configuration
function testDatabaseConfig($environment = null) {
    echo "=== Test de Configuration de Base de Données ===\n";
    echo "Environnement: " . ($environment ?: "auto-détection") . "\n\n";
    
    try {
        // Forcer l'environnement si spécifié
        if ($environment) {
            $_ENV['APP_ENV'] = $environment;
        }
        
        // Charger la configuration
        require_once 'config/database.php';
        
        echo "✅ Configuration chargée avec succès\n";
        echo "   Environnement détecté: " . DB_ENV . "\n";
        echo "   Host: " . DB_HOST . "\n";
        echo "   Base de données: " . DB_NAME . "\n";
        echo "   Utilisateur: " . DB_USER . "\n";
        echo "   Mot de passe: " . (strlen(DB_PASS) > 0 ? str_repeat('*', strlen(DB_PASS)) : 'NON DÉFINI') . "\n\n";
        
        // Tester la connexion
        echo "🔌 Test de connexion...\n";
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result['test'] == 1) {
            echo "✅ Connexion réussie\n\n";
        } else {
            echo "❌ Échec de la connexion\n\n";
            return false;
        }
        
        // Tester les tables principales
        echo "📋 Test des tables principales...\n";
        $tables = ['users', 'characters', 'campaigns', 'monsters'];
        $existingTables = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                    echo "   ✅ Table '$table' existe\n";
                } else {
                    echo "   ⚠️  Table '$table' n'existe pas\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Erreur lors de la vérification de '$table': " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Résumé:\n";
        echo "   Tables existantes: " . count($existingTables) . "/" . count($tables) . "\n";
        echo "   Environnement: " . DB_ENV . "\n";
        echo "   Base de données: " . DB_NAME . "\n";
        
        if (count($existingTables) == count($tables)) {
            echo "   Statut: ✅ Configuration complète\n";
        } elseif (count($existingTables) > 0) {
            echo "   Statut: ⚠️  Configuration partielle\n";
        } else {
            echo "   Statut: ❌ Configuration incomplète\n";
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        return false;
    }
}

// Fonction pour afficher l'aide
function showHelp() {
    echo "Usage: php test_database_config.php [test|staging|production]\n\n";
    echo "Options:\n";
    echo "  test       - Tester la configuration de test\n";
    echo "  staging    - Tester la configuration de staging\n";
    echo "  production - Tester la configuration de production\n";
    echo "  (aucun)    - Test avec auto-détection de l'environnement\n\n";
    echo "Exemples:\n";
    echo "  php test_database_config.php\n";
    echo "  php test_database_config.php test\n";
    echo "  php test_database_config.php staging\n";
}

// Fonction principale
function main() {
    global $argv;
    
    $environment = isset($argv[1]) ? $argv[1] : null;
    
    if ($environment === 'help' || $environment === '-h' || $environment === '--help') {
        showHelp();
        exit(0);
    }
    
    if ($environment && !in_array($environment, ['test', 'staging', 'production'])) {
        echo "❌ Environnement non reconnu: $environment\n";
        echo "Environnements disponibles: test, staging, production\n";
        exit(1);
    }
    
    $success = testDatabaseConfig($environment);
    
    if ($success) {
        echo "\n🎉 Test terminé avec succès !\n";
        exit(0);
    } else {
        echo "\n💥 Test échoué !\n";
        exit(1);
    }
}

// Exécution
main();
?>
