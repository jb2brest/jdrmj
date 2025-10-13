<?php

/**
 * Classe Autoloader - Chargement automatique des classes
 * 
 * Cette classe implémente un système d'autoloading pour charger
 * automatiquement les classes PHP du projet.
 */
class Autoloader
{
    private static $directories = [];
    private static $registered = false;

    /**
     * Enregistre l'autoloader
     * 
     * @param array $directories Répertoires à inclure dans l'autoloading
     */
    public static function register(array $directories = [])
    {
        if (self::$registered) {
            return;
        }

        // Répertoires par défaut
        $defaultDirectories = [
            __DIR__, // Répertoire des classes
            __DIR__ . '/../includes', // Répertoire des includes
        ];

        self::$directories = array_merge($defaultDirectories, $directories);
        
        spl_autoload_register([self::class, 'load']);
        self::$registered = true;
    }

    /**
     * Charge une classe
     * 
     * @param string $className Nom de la classe à charger
     */
    public static function load($className)
    {
        // Convertir le nom de classe en chemin de fichier
        $fileName = self::classNameToFileName($className);
        
        // Chercher dans tous les répertoires enregistrés
        foreach (self::$directories as $directory) {
            $filePath = $directory . '/' . $fileName;
            
            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }

    /**
     * Convertit un nom de classe en nom de fichier
     * 
     * @param string $className Nom de la classe
     * @return string Nom du fichier
     */
    private static function classNameToFileName($className)
    {
        // Remplacer les backslashes par des slashes (pour les namespaces)
        $fileName = str_replace('\\', '/', $className);
        
        // Ajouter l'extension .php
        return $fileName . '.php';
    }

    /**
     * Ajoute un répertoire à la liste des répertoires de recherche
     * 
     * @param string $directory Chemin du répertoire
     */
    public static function addDirectory($directory)
    {
        if (!in_array($directory, self::$directories)) {
            self::$directories[] = $directory;
        }
    }

    /**
     * Retire un répertoire de la liste des répertoires de recherche
     * 
     * @param string $directory Chemin du répertoire
     */
    public static function removeDirectory($directory)
    {
        $key = array_search($directory, self::$directories);
        if ($key !== false) {
            unset(self::$directories[$key]);
            self::$directories = array_values(self::$directories);
        }
    }

    /**
     * Retourne la liste des répertoires de recherche
     * 
     * @return array Liste des répertoires
     */
    public static function getDirectories()
    {
        return self::$directories;
    }

    /**
     * Vérifie si une classe est chargée
     * 
     * @param string $className Nom de la classe
     * @return bool True si la classe est chargée
     */
    public static function isClassLoaded($className)
    {
        return class_exists($className, false);
    }

    /**
     * Retourne toutes les classes chargées
     * 
     * @return array Liste des classes chargées
     */
    public static function getLoadedClasses()
    {
        return get_declared_classes();
    }
}
