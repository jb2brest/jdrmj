<?php

/**
 * Fichier d'initialisation des classes
 * 
 * Ce fichier configure l'autoloader et initialise les classes
 * nécessaires au fonctionnement de l'application.
 */

// Enregistrer l'autoloader
require_once __DIR__ . '/Autoloader.php';
Autoloader::register();

// Initialiser la connexion à la base de données
try {
    $database = Database::getInstance();
    $pdo = $database->getPdo();
} catch (Exception $e) {
    // En cas d'erreur, on peut utiliser la connexion existante
    // ou afficher une erreur selon le contexte
    error_log("Erreur d'initialisation de la base de données: " . $e->getMessage());
}

// Fonction utilitaire pour obtenir une instance de la base de données
function getDatabase()
{
    return Database::getInstance();
}

// Fonction utilitaire pour obtenir une instance PDO
function getPDO()
{
    return Database::getInstance()->getPdo();
}
