<?php
/**
 * Fichier de test pour la classe Monde
 * 
 * Ce fichier démontre l'utilisation de la classe Monde et de ses méthodes.
 * Il peut être utilisé pour tester le fonctionnement des classes.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Test de la classe Monde</h1>\n";

try {
    // Obtenir une instance de la base de données
    $pdo = getPDO();
    echo "<p>✅ Connexion à la base de données réussie</p>\n";

    // Test 1: Créer un nouveau monde
    echo "<h2>Test 1: Création d'un nouveau monde</h2>\n";
    
    $monde = new Monde($pdo);
    $monde->setName("Monde de Test")
          ->setDescription("Ceci est un monde créé pour tester la classe Monde")
          ->setCreatedBy(1); // ID utilisateur de test
    
    echo "<p>Monde créé: " . $monde->getName() . "</p>\n";
    echo "<p>Description: " . $monde->getDescription() . "</p>\n";
    
    // Valider les données
    $errors = $monde->validate();
    if (empty($errors)) {
        echo "<p>✅ Validation réussie</p>\n";
        
        // Sauvegarder en base (commenté pour éviter de créer des données de test)
        // $monde->save();
        // echo "<p>✅ Monde sauvegardé en base de données</p>\n";
    } else {
        echo "<p>❌ Erreurs de validation:</p>\n";
        foreach ($errors as $error) {
            echo "<p>- " . $error . "</p>\n";
        }
    }

    // Test 2: Récupérer un monde existant
    echo "<h2>Test 2: Récupération d'un monde existant</h2>\n";
    
    $mondeExistant = Monde::findById($pdo, 1);
    if ($mondeExistant) {
        echo "<p>✅ Monde trouvé: " . $mondeExistant->getName() . "</p>\n";
        echo "<p>ID: " . $mondeExistant->getId() . "</p>\n";
        echo "<p>Créé par: " . $mondeExistant->getCreatedBy() . "</p>\n";
        echo "<p>Nombre de pays: " . $mondeExistant->getCountryCount() . "</p>\n";
    } else {
        echo "<p>❌ Aucun monde trouvé avec l'ID 1</p>\n";
    }

    // Test 3: Récupérer tous les mondes d'un utilisateur
    echo "<h2>Test 3: Récupération des mondes d'un utilisateur</h2>\n";
    
    $mondes = Monde::findByUser($pdo, 1);
    echo "<p>✅ " . count($mondes) . " monde(s) trouvé(s) pour l'utilisateur 1</p>\n";
    
    foreach ($mondes as $monde) {
        echo "<p>- " . $monde->getName() . " (ID: " . $monde->getId() . ")</p>\n";
    }

    // Test 4: Vérifier si un nom existe
    echo "<h2>Test 4: Vérification d'existence d'un nom</h2>\n";
    
    $nomExiste = Monde::nameExists($pdo, "Monde de Test", 1);
    echo "<p>Le nom 'Monde de Test' existe: " . ($nomExiste ? "Oui" : "Non") . "</p>\n";

    // Test 5: Conversion en tableau
    echo "<h2>Test 5: Conversion en tableau</h2>\n";
    
    if ($mondeExistant) {
        $array = $mondeExistant->toArray();
        echo "<p>✅ Conversion en tableau réussie:</p>\n";
        echo "<pre>" . print_r($array, true) . "</pre>\n";
    }

    // Test 6: Représentation textuelle
    echo "<h2>Test 6: Représentation textuelle</h2>\n";
    
    if ($mondeExistant) {
        echo "<p>Représentation textuelle: " . $mondeExistant . "</p>\n";
    }

    echo "<h2>✅ Tous les tests sont terminés</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Note:</strong> Ce fichier de test démontre l'utilisation de la classe Monde. 
Pour l'utiliser en production, décommentez les lignes de sauvegarde et adaptez les IDs utilisateur.</p>\n";
?>
