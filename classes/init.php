<?php

/**
 * Fichier d'initialisation des classes
 * 
 * Ce fichier configure l'autoloader et initialise l'Univers
 * unique de l'application JDR MJ.
 */

// Enregistrer l'autoloader
require_once __DIR__ . '/Autoloader.php';
Autoloader::register();

// Charger la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Charger les classes principales
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Campaign.php';
require_once __DIR__ . '/Character.php';
require_once __DIR__ . '/Item.php';
require_once __DIR__ . '/StartingEquipment.php';
require_once __DIR__ . '/StartingEquipmentChoix.php';
require_once __DIR__ . '/StartingEquipmentOption.php';

// Initialiser l'Univers unique
try {
    $univers = Univers::getInstance();
} catch (Exception $e) {
    // En cas d'erreur, on peut utiliser la connexion existante
    // ou afficher une erreur selon le contexte
    error_log("Erreur d'initialisation de l'Univers: " . $e->getMessage());
}

// Fonction utilitaire pour obtenir l'Univers
function getUnivers()
{
    return Univers::getInstance();
}

// Fonction utilitaire pour obtenir une instance PDO (rétrocompatibilité)
function getPDO()
{
    return Database::getInstance()->getPdo();
}

// Fonction utilitaire pour obtenir une instance de la base de données (rétrocompatibilité)
function getDatabase()
{
    return Database::getInstance();
}
