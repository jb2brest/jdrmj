<?php

/**
 * Classe Univers - Point central unique de l'application
 * 
 * Cette classe représente l'univers unique de l'application JDR MJ.
 * Elle gère la connexion PDO de manière centralisée et fournit
 * l'accès à toutes les fonctionnalités de l'application.
 * 
 * L'Univers est invisible aux utilisateurs et sert de couche
 * d'abstraction entre l'application et la base de données.
 */
class Univers
{
    // Instance unique (Singleton)
    private static $instance = null;
    
    // Connexion PDO centralisée
    private $pdo;
    
    // Configuration de l'application
    private $config;
    
    // Cache des objets fréquemment utilisés
    private $cache = [];
    
    // Statistiques de l'application
    private $stats = [
        'mondes_created' => 0,
        'pays_created' => 0,
        'regions_created' => 0,
        'places_created' => 0,
        'users_registered' => 0
    ];

    /**
     * Constructeur privé pour implémenter le pattern Singleton
     * 
     * @param array $config Configuration de l'application
     */
    private function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeDatabase();
        $this->loadStats();
    }

    /**
     * Obtient l'instance unique de l'Univers
     * 
     * @param array $config Configuration optionnelle
     * @return Univers Instance unique
     */
    public static function getInstance(array $config = null)
    {
        if (self::$instance === null) {
            if ($config === null) {
                $config = self::loadDefaultConfig();
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Charge la configuration par défaut
     * 
     * @return array Configuration par défaut
     */
    private static function loadDefaultConfig()
    {
        // Essayer de charger depuis config/database.test.php (priorité pour l'environnement de test)
        if (file_exists(__DIR__ . '/../config/database.test.php')) {
            $dbConfig = require __DIR__ . '/../config/database.test.php';
            return [
                'database' => [
                    'host' => $dbConfig['host'] ?? 'localhost',
                    'dbname' => $dbConfig['dbname'] ?? '',
                    'username' => $dbConfig['username'] ?? '',
                    'password' => $dbConfig['password'] ?? '',
                    'charset' => $dbConfig['charset'] ?? 'utf8mb4'
                ],
                'app' => [
                    'name' => 'JDR MJ Test',
                    'version' => '2.0.0',
                    'environment' => 'test'
                ]
            ];
        }

        // Essayer de charger depuis config/database.php
        if (file_exists(__DIR__ . '/../config/database.php')) {
            $dbConfig = require __DIR__ . '/../config/database.php';
            return [
                'database' => [
                    'host' => $dbConfig['host'] ?? 'localhost',
                    'dbname' => $dbConfig['dbname'] ?? '',
                    'username' => $dbConfig['username'] ?? '',
                    'password' => $dbConfig['password'] ?? '',
                    'charset' => $dbConfig['charset'] ?? 'utf8mb4'
                ],
                'app' => [
                    'name' => 'JDR MJ',
                    'version' => '2.0.0',
                    'environment' => 'production'
                ]
            ];
        }

        throw new Exception("Aucun fichier de configuration trouvé.");
    }

    /**
     * Initialise la connexion à la base de données
     * 
     * @throws Exception En cas d'erreur de connexion
     */
    private function initializeDatabase()
    {
        try {
            $dbConfig = $this->config['database'];
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']}"
            ];

            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à l'Univers: " . $e->getMessage());
        }
    }

    /**
     * Charge les statistiques de l'application
     */
    private function loadStats()
    {
        try {
            // Compter les mondes
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM worlds");
            $this->stats['mondes_created'] = (int)$stmt->fetchColumn();

            // Compter les pays
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM countries");
            $this->stats['pays_created'] = (int)$stmt->fetchColumn();

            // Compter les régions
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM regions");
            $this->stats['regions_created'] = (int)$stmt->fetchColumn();

            // Compter les lieux
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM places");
            $this->stats['places_created'] = (int)$stmt->fetchColumn();

            // Compter les utilisateurs
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            $this->stats['users_registered'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // En cas d'erreur, garder les stats à 0
            error_log("Erreur lors du chargement des statistiques: " . $e->getMessage());
        }
    }

    // ========================================
    // GETTERS
    // ========================================

    /**
     * Obtient l'instance PDO
     * 
     * @return PDO Instance PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Obtient la configuration de l'application
     * 
     * @return array Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Obtient les statistiques de l'application
     * 
     * @return array Statistiques
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Obtient le nom de l'application
     * 
     * @return string Nom de l'application
     */
    public function getAppName()
    {
        return $this->config['app']['name'] ?? 'JDR MJ';
    }

    /**
     * Obtient la version de l'application
     * 
     * @return string Version
     */
    public function getAppVersion()
    {
        return $this->config['app']['version'] ?? '1.0.0';
    }

    /**
     * Obtient l'environnement de l'application
     * 
     * @return string Environnement
     */
    public function getEnvironment()
    {
        return $this->config['app']['environment'] ?? 'production';
    }

    // ========================================
    // MÉTHODES DE GESTION DES MONDES
    // ========================================

    /**
     * Crée un nouveau monde dans l'univers
     * 
     * @param string $name Nom du monde
     * @param string $description Description
     * @param int $created_by ID du créateur
     * @param string $map_url URL de la carte
     * @return Monde Instance du monde créé
     * @throws Exception En cas d'erreur
     */
    public function createMonde(string $name, string $description, int $created_by, string $map_url = '')
    {
        $monde = new Monde();
        $monde->setName($name)
              ->setDescription($description)
              ->setCreatedBy($created_by)
              ->setMapUrl($map_url);
        
        $monde->save();
        $this->stats['mondes_created']++;
        
        return $monde;
    }

    /**
     * Récupère tous les mondes de l'univers
     * 
     * @return array Collection de mondes
     */
    public function getAllMondes()
    {
        try {
            $sql = "SELECT * FROM worlds ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $mondes = [];
            foreach ($results as $data) {
                $mondes[] = new Monde($data);
            }
            return $mondes;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des mondes: " . $e->getMessage());
        }
    }

    /**
     * Récupère un monde par son ID
     * 
     * @param int $id ID du monde
     * @return Monde|null Instance du monde ou null
     */
    public function getMondeById(int $id)
    {
        return Monde::findById($this->pdo, $id);
    }

    // ========================================
    // MÉTHODES DE GESTION DES PAYS
    // ========================================

    /**
     * Crée un nouveau pays dans l'univers
     * 
     * @param int $world_id ID du monde
     * @param string $name Nom du pays
     * @param string $description Description
     * @param string $map_url URL de la carte
     * @param string $coat_of_arms_url URL du blason
     * @return Pays Instance du pays créé
     * @throws Exception En cas d'erreur
     */
    public function createPays(int $world_id, string $name, string $description, string $map_url = '', string $coat_of_arms_url = '')
    {
        $pays = new Pays();
        $pays->setWorldId($world_id)
             ->setName($name)
             ->setDescription($description)
             ->setMapUrl($map_url)
             ->setCoatOfArmsUrl($coat_of_arms_url);
        
        $pays->save();
        $this->stats['pays_created']++;
        
        return $pays;
    }

    /**
     * Récupère tous les pays de l'univers
     * 
     * @return array Collection de pays
     */
    public function getAllPays()
    {
        try {
            $sql = "SELECT c.*, w.name as world_name FROM countries c 
                    JOIN worlds w ON c.world_id = w.id 
                    ORDER BY w.name, c.name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $pays = [];
            foreach ($results as $data) {
                $pays[] = new Pays($data);
            }
            return $pays;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pays: " . $e->getMessage());
        }
    }

    /**
     * Crée une nouvelle région dans l'univers
     * 
     * @param int $country_id ID du pays
     * @param string $name Nom de la région
     * @param string $description Description de la région
     * @param string $map_url URL de la carte
     * @param string $coat_of_arms_url URL du blason
     * @return Region Instance de la région créée
     * @throws Exception En cas d'erreur
     */
    public function createRegion(int $country_id, string $name, string $description, string $map_url = '', string $coat_of_arms_url = '')
    {
        $region = new Region();
        $region->setCountryId($country_id)
               ->setName($name)
               ->setDescription($description)
               ->setMapUrl($map_url)
               ->setCoatOfArmsUrl($coat_of_arms_url);
        
        $region->save();
        $this->stats['regions_created']++;
        
        return $region;
    }

    /**
     * Récupère toutes les régions de l'univers
     * 
     * @return array Collection de régions
     * @throws Exception En cas d'erreur
     */
    public function getAllRegions()
    {
        try {
            $sql = "SELECT r.*, c.name as country_name, w.name as world_name
                    FROM regions r 
                    JOIN countries c ON r.country_id = c.id
                    JOIN worlds w ON c.world_id = w.id
                    ORDER BY w.name, c.name, r.name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $regions = [];
            foreach ($results as $data) {
                $regions[] = new Region($data);
            }
            return $regions;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des régions: " . $e->getMessage());
        }
    }

    /**
     * Récupère une région par son ID
     * 
     * @param int $id ID de la région
     * @return Region|null Instance de la région ou null si non trouvé
     * @throws Exception En cas d'erreur
     */
    public function getRegionById(int $id)
    {
        return Region::findById($id);
    }

    // ========================================
    // MÉTHODES DE CACHE
    // ========================================

    /**
     * Met en cache un objet
     * 
     * @param string $key Clé du cache
     * @param mixed $value Valeur à mettre en cache
     * @param int $ttl Durée de vie en secondes
     */
    public function cache(string $key, $value, int $ttl = 3600)
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }

    /**
     * Récupère un objet du cache
     * 
     * @param string $key Clé du cache
     * @return mixed|null Valeur en cache ou null
     */
    public function getCache(string $key)
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $cached = $this->cache[$key];
        if (time() > $cached['expires']) {
            unset($this->cache[$key]);
            return null;
        }

        return $cached['value'];
    }

    /**
     * Supprime un objet du cache
     * 
     * @param string $key Clé du cache
     */
    public function clearCache(string $key = null)
    {
        if ($key === null) {
            $this->cache = [];
        } else {
            unset($this->cache[$key]);
        }
    }

    // ========================================
    // MÉTHODES DE MAINTENANCE
    // ========================================

    /**
     * Vérifie l'état de l'univers
     * 
     * @return array État de l'univers
     */
    public function getHealthStatus()
    {
        $status = [
            'database_connected' => false,
            'tables_exist' => false,
            'stats_loaded' => false,
            'cache_working' => false
        ];

        try {
            // Test de connexion
            $this->pdo->query("SELECT 1");
            $status['database_connected'] = true;

            // Test des tables
            $tables = ['worlds', 'countries', 'regions', 'places', 'users'];
            $existing_tables = [];
            foreach ($tables as $table) {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->fetch()) {
                    $existing_tables[] = $table;
                }
            }
            $status['tables_exist'] = count($existing_tables) === count($tables);

            // Test des stats
            $status['stats_loaded'] = $this->stats['mondes_created'] >= 0;

            // Test du cache
            $this->cache('test', 'value', 1);
            $status['cache_working'] = $this->getCache('test') === 'value';

        } catch (Exception $e) {
            error_log("Erreur lors de la vérification de l'état: " . $e->getMessage());
        }

        return $status;
    }

    /**
     * Nettoie l'univers (cache, logs, etc.)
     */
    public function cleanup()
    {
        $this->clearCache();
        $this->loadStats(); // Recharger les stats
    }

    /**
     * Sauvegarde l'état de l'univers
     * 
     * @return array État sauvegardé
     */
    public function saveState()
    {
        return [
            'timestamp' => time(),
            'stats' => $this->stats,
            'config' => $this->config,
            'health' => $this->getHealthStatus()
        ];
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Convertit l'univers en tableau
     * 
     * @return array Représentation en tableau
     */
    public function toArray()
    {
        return [
            'app_name' => $this->getAppName(),
            'version' => $this->getAppVersion(),
            'environment' => $this->getEnvironment(),
            'stats' => $this->stats,
            'health' => $this->getHealthStatus()
        ];
    }

    /**
     * Représentation textuelle de l'univers
     * 
     * @return string Description de l'univers
     */
    public function __toString()
    {
        return "Univers " . $this->getAppName() . " v" . $this->getAppVersion() . " (" . $this->getEnvironment() . ")";
    }

    /**
     * Empêche la duplication de l'instance
     */
    public function __clone()
    {
        throw new Exception("Le clonage de l'Univers n'est pas autorisé.");
    }

    /**
     * Empêche la désérialisation
     */
    public function __wakeup()
    {
        throw new Exception("La désérialisation de l'Univers n'est pas autorisée.");
    }
}
