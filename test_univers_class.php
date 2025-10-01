<?php
/**
 * Fichier de test pour la classe Univers
 * 
 * Ce fichier démontre l'utilisation de la classe Univers et de ses méthodes.
 * Il peut être utilisé pour tester le fonctionnement de l'Univers unique.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Test de la classe Univers</h1>\n";

try {
    // Test 1: Obtenir l'instance unique de l'Univers
    echo "<h2>Test 1: Instance unique de l'Univers</h2>\n";
    
    $univers1 = Univers::getInstance();
    $univers2 = Univers::getInstance();
    
    if ($univers1 === $univers2) {
        echo "<p>✅ Pattern Singleton fonctionne : même instance</p>\n";
    } else {
        echo "<p>❌ Erreur : instances différentes</p>\n";
    }
    
    echo "<p>Univers : " . $univers1 . "</p>\n";

    // Test 2: Informations de l'application
    echo "<h2>Test 2: Informations de l'application</h2>\n";
    
    echo "<p>Nom de l'application : " . $univers1->getAppName() . "</p>\n";
    echo "<p>Version : " . $univers1->getAppVersion() . "</p>\n";
    echo "<p>Environnement : " . $univers1->getEnvironment() . "</p>\n";

    // Test 3: Connexion PDO
    echo "<h2>Test 3: Connexion PDO</h2>\n";
    
    $pdo = $univers1->getPdo();
    if ($pdo instanceof PDO) {
        echo "<p>✅ Connexion PDO obtenue avec succès</p>\n";
        
        // Test de requête simple
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result['test'] == 1) {
            echo "<p>✅ Requête de test réussie</p>\n";
        } else {
            echo "<p>❌ Erreur dans la requête de test</p>\n";
        }
    } else {
        echo "<p>❌ Erreur : PDO non obtenu</p>\n";
    }

    // Test 4: Statistiques de l'Univers
    echo "<h2>Test 4: Statistiques de l'Univers</h2>\n";
    
    $stats = $univers1->getStats();
    echo "<p>✅ Statistiques chargées :</p>\n";
    echo "<ul>\n";
    echo "<li>Mondes créés : " . $stats['mondes_created'] . "</li>\n";
    echo "<li>Pays créés : " . $stats['pays_created'] . "</li>\n";
    echo "<li>Régions créées : " . $stats['regions_created'] . "</li>\n";
    echo "<li>Lieux créés : " . $stats['places_created'] . "</li>\n";
    echo "<li>Utilisateurs enregistrés : " . $stats['users_registered'] . "</li>\n";
    echo "</ul>\n";

    // Test 5: État de santé de l'Univers
    echo "<h2>Test 5: État de santé de l'Univers</h2>\n";
    
    $health = $univers1->getHealthStatus();
    echo "<p>✅ État de santé :</p>\n";
    echo "<ul>\n";
    echo "<li>Base de données connectée : " . ($health['database_connected'] ? "✅" : "❌") . "</li>\n";
    echo "<li>Tables existantes : " . ($health['tables_exist'] ? "✅" : "❌") . "</li>\n";
    echo "<li>Statistiques chargées : " . ($health['stats_loaded'] ? "✅" : "❌") . "</li>\n";
    echo "<li>Cache fonctionnel : " . ($health['cache_working'] ? "✅" : "❌") . "</li>\n";
    echo "</ul>\n";

    // Test 6: Système de cache
    echo "<h2>Test 6: Système de cache</h2>\n";
    
    $testKey = 'test_cache_key';
    $testValue = 'Valeur de test pour le cache';
    
    $univers1->cache($testKey, $testValue, 60); // Cache pour 60 secondes
    $cachedValue = $univers1->getCache($testKey);
    
    if ($cachedValue === $testValue) {
        echo "<p>✅ Cache fonctionne : valeur récupérée correctement</p>\n";
    } else {
        echo "<p>❌ Erreur dans le cache : valeur différente</p>\n";
    }
    
    // Test de suppression du cache
    $univers1->clearCache($testKey);
    $cachedValueAfterClear = $univers1->getCache($testKey);
    
    if ($cachedValueAfterClear === null) {
        echo "<p>✅ Cache supprimé correctement</p>\n";
    } else {
        echo "<p>❌ Erreur : cache non supprimé</p>\n";
    }

    // Test 7: Récupération des mondes via l'Univers
    echo "<h2>Test 7: Récupération des mondes via l'Univers</h2>\n";
    
    $mondes = $univers1->getAllMondes();
    echo "<p>✅ " . count($mondes) . " monde(s) récupéré(s) via l'Univers</p>\n";
    
    foreach ($mondes as $monde) {
        echo "<p>- " . $monde->getName() . " (ID: " . $monde->getId() . ")</p>\n";
    }

    // Test 8: Récupération des pays via l'Univers
    echo "<h2>Test 8: Récupération des pays via l'Univers</h2>\n";
    
    $pays = $univers1->getAllPays();
    echo "<p>✅ " . count($pays) . " pays récupéré(s) via l'Univers</p>\n";
    
    foreach ($pays as $paysItem) {
        echo "<p>- " . $paysItem->getName() . " dans le monde: " . $paysItem->getWorldName() . "</p>\n";
    }

    // Test 9: Méthodes statiques avec Univers
    echo "<h2>Test 9: Méthodes statiques avec Univers</h2>\n";
    
    // Test Monde::findByIdInUnivers
    if (!empty($mondes)) {
        $premierMonde = $mondes[0];
        $mondeRecupere = Monde::findByIdInUnivers($premierMonde->getId());
        
        if ($mondeRecupere && $mondeRecupere->getId() === $premierMonde->getId()) {
            echo "<p>✅ Monde::findByIdInUnivers fonctionne</p>\n";
        } else {
            echo "<p>❌ Erreur dans Monde::findByIdInUnivers</p>\n";
        }
    }

    // Test Pays::findByIdInUnivers
    if (!empty($pays)) {
        $premierPays = $pays[0];
        $paysRecupere = Pays::findByIdInUnivers($premierPays->getId());
        
        if ($paysRecupere && $paysRecupere->getId() === $premierPays->getId()) {
            echo "<p>✅ Pays::findByIdInUnivers fonctionne</p>\n";
        } else {
            echo "<p>❌ Erreur dans Pays::findByIdInUnivers</p>\n";
        }
    }

    // Test 10: Sauvegarde de l'état
    echo "<h2>Test 10: Sauvegarde de l'état</h2>\n";
    
    $etat = $univers1->saveState();
    if (isset($etat['timestamp']) && isset($etat['stats']) && isset($etat['health'])) {
        echo "<p>✅ État de l'Univers sauvegardé avec succès</p>\n";
        echo "<p>Timestamp : " . date('Y-m-d H:i:s', $etat['timestamp']) . "</p>\n";
    } else {
        echo "<p>❌ Erreur lors de la sauvegarde de l'état</p>\n";
    }

    // Test 11: Conversion en tableau
    echo "<h2>Test 11: Conversion en tableau</h2>\n";
    
    $array = $univers1->toArray();
    if (isset($array['app_name']) && isset($array['version']) && isset($array['stats'])) {
        echo "<p>✅ Conversion en tableau réussie</p>\n";
        echo "<pre>" . print_r($array, true) . "</pre>\n";
    } else {
        echo "<p>❌ Erreur lors de la conversion en tableau</p>\n";
    }

    // Test 12: Nettoyage
    echo "<h2>Test 12: Nettoyage de l'Univers</h2>\n";
    
    $univers1->cleanup();
    echo "<p>✅ Nettoyage de l'Univers effectué</p>\n";

    echo "<h2>✅ Tous les tests sont terminés</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Note:</strong> Ce fichier de test démontre l'utilisation de la classe Univers. 
L'Univers est unique pour tout le site et gère centralement le PDO et les fonctionnalités de l'application.</p>\n";
?>

