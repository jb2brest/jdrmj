<?php
/**
 * Fichier de test pour la classe Pays
 * 
 * Ce fichier démontre l'utilisation de la classe Pays et de ses méthodes.
 * Il peut être utilisé pour tester le fonctionnement des classes.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Test de la classe Pays</h1>\n";

try {
    // Obtenir une instance de la base de données
    $pdo = getPDO();
    echo "<p>✅ Connexion à la base de données réussie</p>\n";

    // Test 1: Créer un nouveau pays
    echo "<h2>Test 1: Création d'un nouveau pays</h2>\n";
    
    $pays = new Pays($pdo);
    $pays->setWorldId(1) // ID d'un monde existant
          ->setName("Royaume de Test")
          ->setDescription("Ceci est un pays créé pour tester la classe Pays")
          ->setMapUrl("uploads/countries/test_map.jpg")
          ->setCoatOfArmsUrl("uploads/countries/test_coat.png");
    
    echo "<p>Pays créé: " . $pays->getName() . "</p>\n";
    echo "<p>Description: " . $pays->getDescription() . "</p>\n";
    echo "<p>Monde ID: " . $pays->getWorldId() . "</p>\n";
    
    // Valider les données
    $errors = $pays->validate();
    if (empty($errors)) {
        echo "<p>✅ Validation réussie</p>\n";
        
        // Sauvegarder en base (commenté pour éviter de créer des données de test)
        // $pays->save();
        // echo "<p>✅ Pays sauvegardé en base de données</p>\n";
    } else {
        echo "<p>❌ Erreurs de validation:</p>\n";
        foreach ($errors as $error) {
            echo "<p>- " . $error . "</p>\n";
        }
    }

    // Test 2: Récupérer un pays existant
    echo "<h2>Test 2: Récupération d'un pays existant</h2>\n";
    
    $paysExistant = Pays::findById($pdo, 1);
    if ($paysExistant) {
        echo "<p>✅ Pays trouvé: " . $paysExistant->getName() . "</p>\n";
        echo "<p>ID: " . $paysExistant->getId() . "</p>\n";
        echo "<p>Monde ID: " . $paysExistant->getWorldId() . "</p>\n";
        echo "<p>Nombre de régions: " . $paysExistant->getRegionCount() . "</p>\n";
        echo "<p>Nom du monde: " . $paysExistant->getWorldName() . "</p>\n";
    } else {
        echo "<p>❌ Aucun pays trouvé avec l'ID 1</p>\n";
    }

    // Test 3: Récupérer tous les pays d'un monde
    echo "<h2>Test 3: Récupération des pays d'un monde</h2>\n";
    
    $paysDuMonde = Pays::findByWorld($pdo, 1);
    echo "<p>✅ " . count($paysDuMonde) . " pays trouvé(s) pour le monde 1</p>\n";
    
    foreach ($paysDuMonde as $pays) {
        echo "<p>- " . $pays->getName() . " (ID: " . $pays->getId() . ")</p>\n";
    }

    // Test 4: Récupérer tous les pays d'un utilisateur
    echo "<h2>Test 4: Récupération des pays d'un utilisateur</h2>\n";
    
    $paysUtilisateur = Pays::findByUser($pdo, 1);
    echo "<p>✅ " . count($paysUtilisateur) . " pays trouvé(s) pour l'utilisateur 1</p>\n";
    
    foreach ($paysUtilisateur as $pays) {
        echo "<p>- " . $pays->getName() . " dans le monde: " . $pays->getWorldName() . "</p>\n";
    }

    // Test 5: Vérifier si un nom existe dans un monde
    echo "<h2>Test 5: Vérification d'existence d'un nom dans un monde</h2>\n";
    
    $nomExiste = Pays::nameExistsInWorld($pdo, "Royaume de Test", 1);
    echo "<p>Le nom 'Royaume de Test' existe dans le monde 1: " . ($nomExiste ? "Oui" : "Non") . "</p>\n";

    // Test 6: Récupérer le monde associé
    echo "<h2>Test 6: Récupération du monde associé</h2>\n";
    
    if ($paysExistant) {
        $monde = $paysExistant->getMonde();
        if ($monde) {
            echo "<p>✅ Monde associé trouvé: " . $monde->getName() . "</p>\n";
        } else {
            echo "<p>❌ Aucun monde associé trouvé</p>\n";
        }
    }

    // Test 7: Récupérer les régions du pays
    echo "<h2>Test 7: Récupération des régions du pays</h2>\n";
    
    if ($paysExistant) {
        $regions = $paysExistant->getRegions();
        echo "<p>✅ " . count($regions) . " région(s) trouvée(s) pour le pays " . $paysExistant->getName() . "</p>\n";
        
        foreach ($regions as $region) {
            echo "<p>- " . $region['name'] . "</p>\n";
        }
    }

    // Test 8: Conversion en tableau
    echo "<h2>Test 8: Conversion en tableau</h2>\n";
    
    if ($paysExistant) {
        $array = $paysExistant->toArray();
        echo "<p>✅ Conversion en tableau réussie:</p>\n";
        echo "<pre>" . print_r($array, true) . "</pre>\n";
    }

    // Test 9: Représentation textuelle
    echo "<h2>Test 9: Représentation textuelle</h2>\n";
    
    if ($paysExistant) {
        echo "<p>Représentation textuelle: " . $paysExistant . "</p>\n";
    }

    // Test 10: Test de validation avec erreurs
    echo "<h2>Test 10: Test de validation avec erreurs</h2>\n";
    
    $paysInvalide = new Pays($pdo);
    $paysInvalide->setWorldId(0) // ID invalide
                 ->setName("") // Nom vide
                 ->setDescription(str_repeat("a", 70000)); // Description trop longue
    
    $errors = $paysInvalide->validate();
    echo "<p>✅ " . count($errors) . " erreur(s) de validation détectée(s):</p>\n";
    foreach ($errors as $error) {
        echo "<p>- " . $error . "</p>\n";
    }

    echo "<h2>✅ Tous les tests sont terminés</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Note:</strong> Ce fichier de test démontre l'utilisation de la classe Pays. 
Pour l'utiliser en production, décommentez les lignes de sauvegarde et adaptez les IDs monde/utilisateur.</p>\n";
?>

