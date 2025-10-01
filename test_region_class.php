<?php
/**
 * Fichier de test pour la classe Region
 * 
 * Ce fichier démontre l'utilisation de la classe Region et de ses méthodes.
 * Il peut être utilisé pour tester le fonctionnement des classes.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Test de la classe Region</h1>\n";

try {
    // Obtenir une instance de la base de données
    $pdo = getPDO();
    echo "<p>✅ Connexion à la base de données réussie</p>\n";

    // Test 1: Créer une nouvelle région
    echo "<h2>Test 1: Création d'une nouvelle région</h2>\n";
    
    $region = new Region();
    $region->setCountryId(1) // ID d'un pays existant
           ->setName("Région de Test")
           ->setDescription("Ceci est une région créée pour tester la classe Region")
           ->setMapUrl("uploads/regions/test_map.jpg")
           ->setCoatOfArmsUrl("uploads/regions/test_coat.png");
    
    echo "<p>Région créée: " . $region->getName() . "</p>\n";
    echo "<p>Description: " . $region->getDescription() . "</p>\n";
    echo "<p>Pays ID: " . $region->getCountryId() . "</p>\n";
    
    // Valider les données
    $errors = $region->validate();
    if (empty($errors)) {
        echo "<p>✅ Validation réussie</p>\n";
        
        // Sauvegarder en base (commenté pour éviter de créer des données de test)
        // $region->save();
        // echo "<p>✅ Région sauvegardée en base de données</p>\n";
    } else {
        echo "<p>❌ Erreurs de validation:</p>\n";
        foreach ($errors as $error) {
            echo "<p>- " . $error . "</p>\n";
        }
    }

    // Test 2: Récupérer une région existante
    echo "<h2>Test 2: Récupération d'une région existante</h2>\n";
    
    $regionExistant = Region::findById(1);
    if ($regionExistant) {
        echo "<p>✅ Région trouvée: " . $regionExistant->getName() . "</p>\n";
        echo "<p>ID: " . $regionExistant->getId() . "</p>\n";
        echo "<p>Pays ID: " . $regionExistant->getCountryId() . "</p>\n";
        echo "<p>Nombre de lieux: " . $regionExistant->getPlaceCount() . "</p>\n";
        echo "<p>Nom du pays: " . $regionExistant->getCountryName() . "</p>\n";
        echo "<p>Nom du monde: " . $regionExistant->getWorldName() . "</p>\n";
    } else {
        echo "<p>❌ Aucune région trouvée avec l'ID 1</p>\n";
    }

    // Test 3: Récupérer toutes les régions d'un pays
    echo "<h2>Test 3: Récupération des régions d'un pays</h2>\n";
    
    $regionsDuPays = Region::findByCountry(1);
    echo "<p>✅ " . count($regionsDuPays) . " région(s) trouvée(s) pour le pays 1</p>\n";
    
    foreach ($regionsDuPays as $region) {
        echo "<p>- " . $region->getName() . " (ID: " . $region->getId() . ")</p>\n";
    }

    // Test 4: Récupérer toutes les régions d'un utilisateur
    echo "<h2>Test 4: Récupération des régions d'un utilisateur</h2>\n";
    
    $regionsUtilisateur = Region::findByUser(1);
    echo "<p>✅ " . count($regionsUtilisateur) . " région(s) trouvée(s) pour l'utilisateur 1</p>\n";
    
    foreach ($regionsUtilisateur as $region) {
        echo "<p>- " . $region->getName() . " dans le pays: " . $region->getCountryName() . " (monde: " . $region->getWorldName() . ")</p>\n";
    }

    // Test 5: Vérifier si un nom existe dans un pays
    echo "<h2>Test 5: Vérification d'existence d'un nom dans un pays</h2>\n";
    
    $nomExiste = Region::nameExistsInCountry("Région de Test", 1);
    echo "<p>Le nom 'Région de Test' existe dans le pays 1: " . ($nomExiste ? "Oui" : "Non") . "</p>\n";

    // Test 6: Récupérer le pays associé
    echo "<h2>Test 6: Récupération du pays associé</h2>\n";
    
    if ($regionExistant) {
        $pays = $regionExistant->getPays();
        if ($pays) {
            echo "<p>✅ Pays associé trouvé: " . $pays->getName() . "</p>\n";
        } else {
            echo "<p>❌ Aucun pays associé trouvé</p>\n";
        }
    }

    // Test 7: Récupérer le monde associé
    echo "<h2>Test 7: Récupération du monde associé</h2>\n";
    
    if ($regionExistant) {
        $monde = $regionExistant->getMonde();
        if ($monde) {
            echo "<p>✅ Monde associé trouvé: " . $monde->getName() . "</p>\n";
        } else {
            echo "<p>❌ Aucun monde associé trouvé</p>\n";
        }
    }

    // Test 8: Récupérer les lieux de la région
    echo "<h2>Test 8: Récupération des lieux de la région</h2>\n";
    
    if ($regionExistant) {
        $lieux = $regionExistant->getPlaces();
        echo "<p>✅ " . count($lieux) . " lieu(x) trouvé(s) pour la région " . $regionExistant->getName() . "</p>\n";
        
        foreach ($lieux as $lieu) {
            echo "<p>- " . $lieu['name'] . "</p>\n";
        }
    }

    // Test 9: Conversion en tableau
    echo "<h2>Test 9: Conversion en tableau</h2>\n";
    
    if ($regionExistant) {
        $array = $regionExistant->toArray();
        echo "<p>✅ Conversion en tableau réussie:</p>\n";
        echo "<pre>" . print_r($array, true) . "</pre>\n";
    }

    // Test 10: Représentation textuelle
    echo "<h2>Test 10: Représentation textuelle</h2>\n";
    
    if ($regionExistant) {
        echo "<p>Représentation textuelle: " . $regionExistant . "</p>\n";
    }

    // Test 11: Test de validation avec erreurs
    echo "<h2>Test 11: Test de validation avec erreurs</h2>\n";
    
    $regionInvalide = new Region();
    $regionInvalide->setCountryId(0) // ID invalide
                   ->setName("") // Nom vide
                   ->setDescription(str_repeat("a", 70000)); // Description trop longue
    
    $errors = $regionInvalide->validate();
    echo "<p>✅ " . count($errors) . " erreur(s) de validation détectée(s):</p>\n";
    foreach ($errors as $error) {
        echo "<p>- " . $error . "</p>\n";
    }

    // Test 12: Hiérarchie complète
    echo "<h2>Test 12: Hiérarchie complète</h2>\n";
    
    if ($regionExistant) {
        echo "<p>Hiérarchie de la région '" . $regionExistant->getName() . "':</p>\n";
        echo "<ul>\n";
        echo "<li>Monde: " . $regionExistant->getWorldName() . "</li>\n";
        echo "<li>Pays: " . $regionExistant->getCountryName() . "</li>\n";
        echo "<li>Région: " . $regionExistant->getName() . "</li>\n";
        echo "<li>Lieux: " . $regionExistant->getPlaceCount() . "</li>\n";
        echo "</ul>\n";
    }

    echo "<h2>✅ Tous les tests sont terminés</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Note:</strong> Ce fichier de test démontre l'utilisation de la classe Region. 
Pour l'utiliser en production, décommentez les lignes de sauvegarde et adaptez les IDs pays/utilisateur.</p>\n";
?>

