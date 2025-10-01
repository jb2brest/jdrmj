<?php
/**
 * Démonstration de la classe Region
 * 
 * Ce fichier démontre l'utilisation de la classe Region et de ses méthodes.
 * Il peut être utilisé pour tester le fonctionnement des classes.
 */

// Inclure l'initialisation des classes
require_once 'classes/init.php';

echo "<h1>Démonstration de la classe Region</h1>\n";

try {
    // Obtenir une instance de l'Univers
    $univers = getUnivers();
    echo "<p>✅ Univers initialisé: " . $univers . "</p>\n";

    // Démonstration 1: Créer une région via l'Univers
    echo "<h2>Démonstration 1: Création d'une région via l'Univers</h2>\n";
    
    $region = $univers->createRegion(
        1, // ID du pays
        "Région de Démonstration",
        "Cette région a été créée pour démontrer l'utilisation de la classe Region via l'Univers.",
        "uploads/regions/demo_map.jpg",
        "uploads/regions/demo_coat.png"
    );
    
    echo "<p>✅ Région créée via l'Univers: " . $region->getName() . "</p>\n";
    echo "<p>ID: " . $region->getId() . "</p>\n";
    echo "<p>Pays ID: " . $region->getCountryId() . "</p>\n";
    echo "<p>Description: " . $region->getDescription() . "</p>\n";

    // Démonstration 2: Récupérer toutes les régions via l'Univers
    echo "<h2>Démonstration 2: Récupération de toutes les régions via l'Univers</h2>\n";
    
    $toutesRegions = $univers->getAllRegions();
    echo "<p>✅ " . count($toutesRegions) . " région(s) trouvée(s) dans l'Univers</p>\n";
    
    foreach ($toutesRegions as $region) {
        echo "<p>- " . $region->getName() . " (Pays: " . $region->getCountryName() . ", Monde: " . $region->getWorldName() . ")</p>\n";
    }

    // Démonstration 3: Récupérer une région par ID via l'Univers
    echo "<h2>Démonstration 3: Récupération d'une région par ID via l'Univers</h2>\n";
    
    $regionParId = $univers->getRegionById($region->getId());
    if ($regionParId) {
        echo "<p>✅ Région récupérée par ID: " . $regionParId->getName() . "</p>\n";
    } else {
        echo "<p>❌ Aucune région trouvée avec cet ID</p>\n";
    }

    // Démonstration 4: Hiérarchie complète
    echo "<h2>Démonstration 4: Hiérarchie complète</h2>\n";
    
    if ($region) {
        echo "<p>Hiérarchie de la région '" . $region->getName() . "':</p>\n";
        echo "<ul>\n";
        echo "<li>Monde: " . $region->getWorldName() . "</li>\n";
        echo "<li>Pays: " . $region->getCountryName() . "</li>\n";
        echo "<li>Région: " . $region->getName() . "</li>\n";
        echo "<li>Lieux: " . $region->getPlaceCount() . "</li>\n";
        echo "</ul>\n";
    }

    // Démonstration 5: Relations avec les autres classes
    echo "<h2>Démonstration 5: Relations avec les autres classes</h2>\n";
    
    if ($region) {
        // Récupérer le pays associé
        $pays = $region->getPays();
        if ($pays) {
            echo "<p>✅ Pays associé: " . $pays->getName() . "</p>\n";
            echo "<p>   - Nombre de régions dans ce pays: " . $pays->getRegionCount() . "</p>\n";
        }
        
        // Récupérer le monde associé
        $monde = $region->getMonde();
        if ($monde) {
            echo "<p>✅ Monde associé: " . $monde->getName() . "</p>\n";
            echo "<p>   - Nombre de pays dans ce monde: " . $monde->getCountryCount() . "</p>\n";
        }
    }

    // Démonstration 6: Méthodes statiques
    echo "<h2>Démonstration 6: Méthodes statiques</h2>\n";
    
    // Récupérer toutes les régions d'un pays
    $regionsDuPays = Region::findByCountry(1);
    echo "<p>✅ " . count($regionsDuPays) . " région(s) trouvée(s) pour le pays 1</p>\n";
    
    // Récupérer toutes les régions d'un utilisateur
    $regionsUtilisateur = Region::findByUser(1);
    echo "<p>✅ " . count($regionsUtilisateur) . " région(s) trouvée(s) pour l'utilisateur 1</p>\n";
    
    // Vérifier l'existence d'un nom
    $nomExiste = Region::nameExistsInCountry("Région de Démonstration", 1);
    echo "<p>Le nom 'Région de Démonstration' existe dans le pays 1: " . ($nomExiste ? "Oui" : "Non") . "</p>\n";

    // Démonstration 7: Validation et gestion d'erreurs
    echo "<h2>Démonstration 7: Validation et gestion d'erreurs</h2>\n";
    
    $regionInvalide = new Region();
    $regionInvalide->setCountryId(0) // ID invalide
                   ->setName("") // Nom vide
                   ->setDescription(str_repeat("a", 70000)); // Description trop longue
    
    $errors = $regionInvalide->validate();
    echo "<p>✅ " . count($errors) . " erreur(s) de validation détectée(s):</p>\n";
    foreach ($errors as $error) {
        echo "<p>- " . $error . "</p>\n";
    }

    // Démonstration 8: Conversion et représentation
    echo "<h2>Démonstration 8: Conversion et représentation</h2>\n";
    
    if ($region) {
        // Conversion en tableau
        $array = $region->toArray();
        echo "<p>✅ Conversion en tableau réussie (clés: " . implode(', ', array_keys($array)) . ")</p>\n";
        
        // Représentation textuelle
        echo "<p>Représentation textuelle: " . $region . "</p>\n";
    }

    // Démonstration 9: Statistiques de l'Univers
    echo "<h2>Démonstration 9: Statistiques de l'Univers</h2>\n";
    
    $stats = $univers->getStats();
    echo "<p>✅ Statistiques mises à jour:</p>\n";
    echo "<ul>\n";
    echo "<li>Mondes créés: " . $stats['mondes_created'] . "</li>\n";
    echo "<li>Pays créés: " . $stats['pays_created'] . "</li>\n";
    echo "<li>Régions créées: " . $stats['regions_created'] . "</li>\n";
    echo "<li>Lieux créés: " . $stats['places_created'] . "</li>\n";
    echo "<li>Utilisateurs enregistrés: " . $stats['users_registered'] . "</li>\n";
    echo "</ul>\n";

    // Démonstration 10: Nettoyage
    echo "<h2>Démonstration 10: Nettoyage</h2>\n";
    
    // Supprimer la région de démonstration
    if ($region) {
        $region->delete();
        echo "<p>✅ Région de démonstration supprimée</p>\n";
    }
    
    $univers->cleanup();
    echo "<p>✅ Nettoyage de l'Univers effectué</p>\n";

    echo "<h2>✅ Toutes les démonstrations sont terminées</h2>\n";

} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Résumé de la classe Region:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Classe Region créée avec toutes les fonctionnalités</li>\n";
echo "<li>✅ Gestion des relations avec Pays et Monde</li>\n";
echo "<li>✅ Validation complète des données</li>\n";
echo "<li>✅ Méthodes statiques pour la recherche</li>\n";
echo "<li>✅ Intégration avec l'Univers</li>\n";
echo "<li>✅ Gestion des lieux (relation 1:N)</li>\n";
echo "<li>✅ Hiérarchie complète: Monde → Pays → Région → Lieux</li>\n";
echo "</ul>\n";
?>

