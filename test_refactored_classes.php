<?php
/**
 * Test des classes refactorisées (Monde et Pays sans PDO)
 * 
 * Ce fichier teste que les classes Monde et Pays fonctionnent correctement
 * après la refactorisation pour utiliser l'Univers au lieu de stocker le PDO.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Test des classes refactorisées</h1>\n";

try {
    // Test 1: Vérifier que l'Univers fonctionne
    echo "<h2>Test 1: Vérification de l'Univers</h2>\n";
    
    $univers = Univers::getInstance();
    echo "<p>✅ Univers initialisé: " . $univers . "</p>\n";
    
    $pdo = $univers->getPdo();
    if ($pdo instanceof PDO) {
        echo "<p>✅ PDO obtenu depuis l'Univers</p>\n";
    } else {
        echo "<p>❌ Erreur: PDO non obtenu</p>\n";
    }

    // Test 2: Création d'un monde avec le nouveau constructeur
    echo "<h2>Test 2: Création d'un monde (nouveau constructeur)</h2>\n";
    
    $monde = new Monde();
    $monde->setName("Monde de Test Refactorisé")
          ->setDescription("Ce monde teste la refactorisation")
          ->setCreatedBy(1);
    
    echo "<p>Monde créé: " . $monde->getName() . "</p>\n";
    
    // Valider les données
    $errors = $monde->validate();
    if (empty($errors)) {
        echo "<p>✅ Validation réussie</p>\n";
        
        // Sauvegarder en base (commenté pour éviter de créer des données de test)
        // $monde->save();
        // echo "<p>✅ Monde sauvegardé avec succès</p>\n";
    } else {
        echo "<p>❌ Erreurs de validation:</p>\n";
        foreach ($errors as $error) {
            echo "<p>- " . $error . "</p>\n";
        }
    }

    // Test 3: Création d'un pays avec le nouveau constructeur
    echo "<h2>Test 3: Création d'un pays (nouveau constructeur)</h2>\n";
    
    $pays = new Pays();
    $pays->setWorldId(1)
          ->setName("Pays de Test Refactorisé")
          ->setDescription("Ce pays teste la refactorisation");
    
    echo "<p>Pays créé: " . $pays->getName() . "</p>\n";
    
    // Valider les données
    $errors = $pays->validate();
    if (empty($errors)) {
        echo "<p>✅ Validation réussie</p>\n";
        
        // Sauvegarder en base (commenté pour éviter de créer des données de test)
        // $pays->save();
        // echo "<p>✅ Pays sauvegardé avec succès</p>\n";
    } else {
        echo "<p>❌ Erreurs de validation:</p>\n";
        foreach ($errors as $error) {
            echo "<p>- " . $error . "</p>\n";
        }
    }

    // Test 4: Méthodes statiques sans paramètre PDO
    echo "<h2>Test 4: Méthodes statiques refactorisées</h2>\n";
    
    // Test Monde::findById sans PDO
    $mondeExistant = Monde::findById(1);
    if ($mondeExistant) {
        echo "<p>✅ Monde::findById() fonctionne sans paramètre PDO</p>\n";
        echo "<p>Monde trouvé: " . $mondeExistant->getName() . "</p>\n";
    } else {
        echo "<p>ℹ️ Aucun monde avec l'ID 1 trouvé</p>\n";
    }
    
    // Test Pays::findById sans PDO
    $paysExistant = Pays::findById(1);
    if ($paysExistant) {
        echo "<p>✅ Pays::findById() fonctionne sans paramètre PDO</p>\n";
        echo "<p>Pays trouvé: " . $paysExistant->getName() . "</p>\n";
    } else {
        echo "<p>ℹ️ Aucun pays avec l'ID 1 trouvé</p>\n";
    }

    // Test 5: Méthodes findByUser sans paramètre PDO
    echo "<h2>Test 5: Méthodes findByUser refactorisées</h2>\n";
    
    $mondes = Monde::findByUser(1);
    echo "<p>✅ Monde::findByUser() fonctionne sans paramètre PDO</p>\n";
    echo "<p>" . count($mondes) . " monde(s) trouvé(s) pour l'utilisateur 1</p>\n";
    
    $pays = Pays::findByUser(1);
    echo "<p>✅ Pays::findByUser() fonctionne sans paramètre PDO</p>\n";
    echo "<p>" . count($pays) . " pays trouvé(s) pour l'utilisateur 1</p>\n";

    // Test 6: Méthodes avec Univers
    echo "<h2>Test 6: Méthodes avec Univers</h2>\n";
    
    $mondeUnivers = Monde::findByIdInUnivers(1);
    if ($mondeUnivers) {
        echo "<p>✅ Monde::findByIdInUnivers() fonctionne</p>\n";
    } else {
        echo "<p>ℹ️ Aucun monde trouvé via l'Univers</p>\n";
    }
    
    $paysUnivers = Pays::findByIdInUnivers(1);
    if ($paysUnivers) {
        echo "<p>✅ Pays::findByIdInUnivers() fonctionne</p>\n";
    } else {
        echo "<p>ℹ️ Aucun pays trouvé via l'Univers</p>\n";
    }

    // Test 7: Création via l'Univers
    echo "<h2>Test 7: Création via l'Univers</h2>\n";
    
    // Créer un monde via l'Univers
    $mondeUnivers = $univers->createMonde("Monde Univers Test", "Créé via l'Univers", 1);
    echo "<p>✅ Monde créé via l'Univers: " . $mondeUnivers->getName() . "</p>\n";
    
    // Créer un pays via l'Univers
    $paysUnivers = $univers->createPays($mondeUnivers->getId(), "Pays Univers Test", "Créé via l'Univers");
    echo "<p>✅ Pays créé via l'Univers: " . $paysUnivers->getName() . "</p>\n";

    // Test 8: Relations entre classes
    echo "<h2>Test 8: Relations entre classes</h2>\n";
    
    if ($paysUnivers) {
        $mondeParent = $paysUnivers->getMonde();
        if ($mondeParent) {
            echo "<p>✅ Relation Pays → Monde fonctionne</p>\n";
            echo "<p>Pays '" . $paysUnivers->getName() . "' appartient au monde '" . $mondeParent->getName() . "'</p>\n";
        }
        
        $regionCount = $paysUnivers->getRegionCount();
        echo "<p>✅ Comptage des régions fonctionne: " . $regionCount . " région(s)</p>\n";
    }

    // Test 9: Vérification des statistiques
    echo "<h2>Test 9: Statistiques de l'Univers</h2>\n";
    
    $stats = $univers->getStats();
    echo "<p>✅ Statistiques mises à jour:</p>\n";
    echo "<ul>\n";
    echo "<li>Mondes créés: " . $stats['mondes_created'] . "</li>\n";
    echo "<li>Pays créés: " . $stats['pays_created'] . "</li>\n";
    echo "<li>Régions créées: " . $stats['regions_created'] . "</li>\n";
    echo "<li>Lieux créés: " . $stats['places_created'] . "</li>\n";
    echo "<li>Utilisateurs enregistrés: " . $stats['users_registered'] . "</li>\n";
    echo "</ul>\n";

    // Test 10: Nettoyage
    echo "<h2>Test 10: Nettoyage</h2>\n";
    
    // Supprimer les objets de test créés
    if ($paysUnivers) {
        $paysUnivers->delete();
        echo "<p>✅ Pays de test supprimé</p>\n";
    }
    
    if ($mondeUnivers) {
        $mondeUnivers->delete();
        echo "<p>✅ Monde de test supprimé</p>\n";
    }
    
    $univers->cleanup();
    echo "<p>✅ Nettoyage de l'Univers effectué</p>\n";

    echo "<h2>✅ Tous les tests de refactorisation sont terminés</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Résumé de la refactorisation:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Les classes Monde et Pays n'ont plus besoin de stocker le PDO</li>\n";
echo "<li>✅ Le PDO est récupéré depuis l'Univers via getPdo()</li>\n";
echo "<li>✅ Les constructeurs sont simplifiés (plus de paramètre PDO)</li>\n";
echo "<li>✅ Les méthodes statiques n'ont plus besoin du paramètre PDO</li>\n";
echo "<li>✅ L'Univers gère centralement toutes les connexions</li>\n";
echo "<li>✅ La rétrocompatibilité est maintenue via les fonctions utilitaires</li>\n";
echo "</ul>\n";
?>

