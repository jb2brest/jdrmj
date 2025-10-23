<?php
/**
 * Fichier de compatibilité pour la base de données
 * 
 * Ce fichier fournit une compatibilité avec l'ancien système
 * en créant une variable $pdo globale qui utilise le singleton Database.
 */

// S'assurer que la classe Database est disponible
if (!class_exists('Database')) {
    require_once __DIR__ . '/../classes/Database.php';
}

// Créer la variable $pdo globale pour la compatibilité
if (!isset($pdo)) {
    $pdo = Database::getPDO();
}

// =====================================================
// FONCTIONS DE COMPATIBILITÉ
// =====================================================

/**
 * Obtient l'instance PDO via le singleton Database
 * 
 * @return PDO Instance PDO
 */
if (!function_exists('getPDO')) {
    function getPDO() {
        return Database::getPDO();
    }
}

/**
 * Prépare une requête SQL via le singleton Database
 * 
 * @param string $sql Requête SQL
 * @return PDOStatement Statement préparé
 */
if (!function_exists('prepareQuery')) {
    function prepareQuery($sql) {
        return Database::prepare($sql);
    }
}

/**
 * Exécute une requête SQL via le singleton Database
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return PDOStatement Statement exécuté
 */
if (!function_exists('executeQuery')) {
    function executeQuery($sql, $params = []) {
        return Database::execute($sql, $params);
    }
}

/**
 * Obtient un résultat via le singleton Database
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return array|null Premier résultat ou null
 */
if (!function_exists('fetchQuery')) {
    function fetchQuery($sql, $params = []) {
        return Database::fetch($sql, $params);
    }
}

/**
 * Obtient tous les résultats via le singleton Database
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres
 * @return array Tous les résultats
 */
if (!function_exists('fetchAllQuery')) {
    function fetchAllQuery($sql, $params = []) {
        return Database::fetchAll($sql, $params);
    }
}

/**
 * Obtient le dernier ID inséré via le singleton Database
 * 
 * @return string Dernier ID inséré
 */
if (!function_exists('getLastInsertId')) {
    function getLastInsertId() {
        return Database::lastInsertId();
    }
}

/**
 * Commence une transaction via le singleton Database
 * 
 * @return bool True si la transaction a commencé
 */
if (!function_exists('beginTransaction')) {
    function beginTransaction() {
        return Database::beginTransaction();
    }
}

/**
 * Valide une transaction via le singleton Database
 * 
 * @return bool True si la transaction a été validée
 */
if (!function_exists('commitTransaction')) {
    function commitTransaction() {
        return Database::commit();
    }
}

/**
 * Annule une transaction via le singleton Database
 * 
 * @return bool True si la transaction a été annulée
 */
if (!function_exists('rollbackTransaction')) {
    function rollbackTransaction() {
        return Database::rollBack();
    }
}
?>
