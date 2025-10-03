<?php
// Configuration de la base de données - Multi-environnement
// Ce fichier détecte automatiquement l'environnement et charge la configuration appropriée

/**
 * Détection automatique de l'environnement
 */
function detectEnvironment() {
    // 1. Vérifier la variable d'environnement (getenv fonctionne aussi en CLI)
    $appEnv = getenv('APP_ENV') ?: (isset($_ENV['APP_ENV']) ? $_ENV['APP_ENV'] : null);
    if ($appEnv) {
        return $appEnv;
    }
    
    // 2. Vérifier le nom du serveur
    if (isset($_SERVER['SERVER_NAME'])) {
        $server = $_SERVER['SERVER_NAME'];
        if (strpos($server, 'test') !== false || strpos($server, 'localhost') !== false) {
            return 'test';
        }
        if (strpos($server, 'staging') !== false) {
            return 'staging';
        }
    }
    
    // 3. Vérifier le chemin du script
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($script, 'test') !== false) {
            return 'test';
        }
        if (strpos($script, 'staging') !== false) {
            return 'staging';
        }
    }
    
    // 4. Vérifier si on est sur localhost
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
            return 'test';
        }
    }
    
    // 5. Par défaut, considérer comme production
    return 'production';
}

/**
 * Chargement de la configuration de base de données
 */
function loadDatabaseConfig($environment = null) {
    if ($environment === null) {
        $environment = detectEnvironment();
    }
    
    $configFile = __DIR__ . "/database.{$environment}.php";
    
    if (!file_exists($configFile)) {
        throw new Exception("Configuration de base de données non trouvée pour l'environnement: {$environment}");
    }
    
    $config = require $configFile;
    
    // Validation de la configuration
    $required = ['host', 'dbname', 'username', 'password'];
    foreach ($required as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Configuration de base de données incomplète: {$key} manquant");
        }
    }
    
    return $config;
}

// Chargement de la configuration
try {
    $dbConfig = loadDatabaseConfig();
    
    // Définition des constantes pour la compatibilité
    define('DB_HOST', $dbConfig['host']);
    define('DB_NAME', $dbConfig['dbname']);
    define('DB_USER', $dbConfig['username']);
    define('DB_PASS', $dbConfig['password']);
    define('DB_ENV', detectEnvironment());
    
    // Initialisation du singleton Database
    require_once __DIR__ . '/../classes/Database.php';
    $database = Database::getInstance($dbConfig);
    
    // Création de la variable $pdo pour la compatibilité avec le code existant
    $pdo = $database->getPdo();
    
    /**
     * Fonction globale pour obtenir l'instance PDO via le singleton Database
     * 
     * @return PDO Instance PDO
     */
    function getPDO() {
        return Database::getInstance()->getPdo();
    }
    
    // Log de l'environnement (uniquement en mode debug)
    if (defined('DEBUG') && DEBUG) {
        error_log("Connexion DB - Environnement: " . DB_ENV . " - Host: " . DB_HOST . " - DB: " . DB_NAME);
    }
    
} catch (Exception $e) {
    // Log de l'erreur
    error_log("Erreur de configuration DB: " . $e->getMessage());
    
    // Affichage d'une erreur générique en production
    if (detectEnvironment() === 'production') {
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    } else {
        die("Erreur de configuration DB: " . $e->getMessage());
    }
}
?>
