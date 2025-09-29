<?php

/**
 * Classe Database - Gestion des connexions à la base de données
 * 
 * Cette classe encapsule la gestion des connexions PDO et fournit
 * des méthodes utilitaires pour les opérations de base de données.
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    /**
     * Constructeur privé pour implémenter le pattern Singleton
     * 
     * @param array $config Configuration de la base de données
     */
    private function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->dbname = $config['dbname'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->charset = $config['charset'] ?? 'utf8mb4';

        $this->connect();
    }

    /**
     * Obtient l'instance unique de la classe Database
     * 
     * @param array $config Configuration de la base de données (optionnel)
     * @return Database Instance unique
     */
    public static function getInstance(array $config = null)
    {
        if (self::$instance === null) {
            if ($config === null) {
                // Charger la configuration depuis le fichier de config existant
                $config = self::loadConfig();
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Charge la configuration depuis le fichier de config existant
     * 
     * @return array Configuration de la base de données
     */
    private static function loadConfig()
    {
        // Essayer de charger depuis config/database.php
        if (file_exists(__DIR__ . '/../config/database.php')) {
            require_once __DIR__ . '/../config/database.php';
            return [
                'host' => $host ?? 'localhost',
                'dbname' => $dbname ?? '',
                'username' => $username ?? '',
                'password' => $password ?? '',
                'charset' => 'utf8mb4'
            ];
        }

        // Essayer de charger depuis config/database.test.php
        if (file_exists(__DIR__ . '/../config/database.test.php')) {
            require_once __DIR__ . '/../config/database.test.php';
            return [
                'host' => $host ?? 'localhost',
                'dbname' => $dbname ?? '',
                'username' => $username ?? '',
                'password' => $password ?? '',
                'charset' => 'utf8mb4'
            ];
        }

        throw new Exception("Aucun fichier de configuration de base de données trouvé.");
    }

    /**
     * Établit la connexion à la base de données
     * 
     * @throws Exception En cas d'erreur de connexion
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

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
     * Exécute une requête SELECT et retourne tous les résultats
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Résultats de la requête
     */
    public function selectAll(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'exécution de la requête SELECT: " . $e->getMessage());
        }
    }

    /**
     * Exécute une requête SELECT et retourne le premier résultat
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|null Premier résultat ou null
     */
    public function selectOne(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'exécution de la requête SELECT: " . $e->getMessage());
        }
    }

    /**
     * Exécute une requête INSERT, UPDATE ou DELETE
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return int Nombre de lignes affectées
     */
    public function execute(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'exécution de la requête: " . $e->getMessage());
        }
    }

    /**
     * Exécute une requête INSERT et retourne l'ID de la dernière ligne insérée
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return int ID de la dernière ligne insérée
     */
    public function insert(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'insertion: " . $e->getMessage());
        }
    }

    /**
     * Démarre une transaction
     * 
     * @return bool True si la transaction a été démarrée
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction
     * 
     * @return bool True si la transaction a été validée
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Annule une transaction
     * 
     * @return bool True si la transaction a été annulée
     */
    public function rollback()
    {
        return $this->pdo->rollback();
    }

    /**
     * Vérifie si une transaction est en cours
     * 
     * @return bool True si une transaction est en cours
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Échappe une chaîne pour l'utiliser dans une requête SQL
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    public function quote(string $string)
    {
        return $this->pdo->quote($string);
    }

    /**
     * Prépare une requête SQL
     * 
     * @param string $sql Requête SQL
     * @return PDOStatement Statement préparé
     */
    public function prepare(string $sql)
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * Ferme la connexion à la base de données
     */
    public function close()
    {
        $this->pdo = null;
        self::$instance = null;
    }

    /**
     * Empêche la duplication de l'instance
     */
    public function __clone()
    {
        throw new Exception("Le clonage de cette classe n'est pas autorisé.");
    }

    /**
     * Empêche la désérialisation
     */
    public function __wakeup()
    {
        throw new Exception("La désérialisation de cette classe n'est pas autorisée.");
    }
}
