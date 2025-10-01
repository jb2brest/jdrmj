<?php
/**
 * Fichier de compatibilité pour les fonctions utilisateur
 * 
 * Ce fichier maintient les fonctions existantes tout en utilisant
 * la classe User en arrière-plan pour assurer une transition en douceur.
 */

// S'assurer que la classe User est disponible
if (!class_exists('User')) {
    require_once __DIR__ . '/../classes/User.php';
}

// Vérifier si les fonctions existent déjà pour éviter les conflits

// =====================================================
// FONCTIONS DE COMPATIBILITÉ
// =====================================================

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool True si connecté
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return User::isLoggedIn();
    }
}

/**
 * Redirige vers la page de connexion si non connecté
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        User::requireLogin();
    }
}

/**
 * Obtient le rôle de l'utilisateur actuel
 * 
 * @return string Rôle de l'utilisateur
 */
if (!function_exists('getUserRole')) {
    function getUserRole() {
        return User::getCurrentUserRole();
    }
}

/**
 * Vérifie si l'utilisateur actuel est un MJ
 * 
 * @return bool True si MJ
 */
if (!function_exists('isDM')) {
    function isDM() {
        return User::isDM();
    }
}

/**
 * Vérifie si l'utilisateur actuel est un joueur
 * 
 * @return bool True si joueur
 */
if (!function_exists('isPlayer')) {
    function isPlayer() {
        return User::isPlayer();
    }
}

/**
 * Vérifie si l'utilisateur actuel est admin
 * 
 * @return bool True si admin
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return User::isAdmin();
    }
}

/**
 * Vérifie si l'utilisateur actuel est MJ ou admin
 * 
 * @return bool True si MJ ou admin
 */
if (!function_exists('isDMOrAdmin')) {
    function isDMOrAdmin() {
        return User::isDMOrAdmin();
    }
}

/**
 * Obtient les informations complètes de l'utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array|null Données de l'utilisateur ou null
 */
if (!function_exists('getUserInfo')) {
    function getUserInfo($user_id) {
        global $pdo;
        $user = User::findById($pdo, $user_id);
        return $user ? $user->toArray() : null;
    }
}

/**
 * Obtient le niveau d'expérience en français
 * 
 * @param string $level Niveau d'expérience
 * @return string Label du niveau
 */
if (!function_exists('getExperienceLevelLabel')) {
    function getExperienceLevelLabel($level) {
        return User::getExperienceLevelLabel($level);
    }
}

// =====================================================
// FONCTIONS UTILITAIRES POUR LA MIGRATION
// =====================================================

/**
 * Obtient l'utilisateur actuel sous forme d'objet User
 * 
 * @return User|null Utilisateur actuel ou null
 */
function getCurrentUserObject() {
    global $pdo;
    return User::getCurrentUser($pdo);
}

/**
 * Crée un nouvel utilisateur en utilisant la classe User
 * 
 * @param array $data Données de l'utilisateur
 * @return User|null Utilisateur créé ou null
 */
function createUser($data) {
    global $pdo;
    return User::create($pdo, $data);
}

/**
 * Trouve un utilisateur par son nom d'utilisateur
 * 
 * @param string $username Nom d'utilisateur
 * @return User|null Utilisateur trouvé ou null
 */
function findUserByUsername($username) {
    global $pdo;
    return User::findByUsername($pdo, $username);
}

/**
 * Trouve un utilisateur par son email
 * 
 * @param string $email Email de l'utilisateur
 * @return User|null Utilisateur trouvé ou null
 */
function findUserByEmail($email) {
    global $pdo;
    return User::findByEmail($pdo, $email);
}
?>
