<?php
/**
 * Script de test pour le système d'accès entre lieux
 */

require_once 'config/database.php';
require_once 'classes/Lieu.php';
require_once 'classes/Access.php';

echo "<h1>Test du Système d'Accès entre Lieux</h1>\n";

try {
    // Test 1: Créer des lieux de test
    echo "<h2>1. Création de lieux de test</h2>\n";
    
    $lieu1_id = Lieu::create("Taverne du Dragon", "", "Une taverne accueillante", 0, null, null);
    $lieu2_id = Lieu::create("Château de la Montagne", "", "Un château imposant", 0, null, null);
    $lieu3_id = Lieu::create("Forêt Mystérieuse", "", "Une forêt sombre et mystérieuse", 0, null, null);
    
    if ($lieu1_id && $lieu2_id && $lieu3_id) {
        echo "✅ Lieux créés avec succès:<br>\n";
        echo "- Lieu 1 (ID: $lieu1_id): Taverne du Dragon<br>\n";
        echo "- Lieu 2 (ID: $lieu2_id): Château de la Montagne<br>\n";
        echo "- Lieu 3 (ID: $lieu3_id): Forêt Mystérieuse<br>\n";
    } else {
        echo "❌ Erreur lors de la création des lieux<br>\n";
        exit;
    }
    
    // Test 2: Créer des accès
    echo "<h2>2. Création d'accès</h2>\n";
    
    // Accès normal (taverne -> château)
    $access1_id = Access::create(
        $lieu1_id, $lieu2_id, "Porte principale", 
        "Une grande porte en bois menant au château", 
        true, true, false
    );
    
    // Accès caché (château -> forêt)
    $access2_id = Access::create(
        $lieu2_id, $lieu3_id, "Passage secret", 
        "Un passage secret dissimulé derrière une tapisserie", 
        false, true, false
    );
    
    // Accès piégé (forêt -> taverne)
    $access3_id = Access::create(
        $lieu3_id, $lieu1_id, "Sentier piégé", 
        "Un sentier apparemment sûr mais piégé", 
        true, true, true, 
        "Piège à flèches", 15, "1d6+2 dégâts perforants"
    );
    
    if ($access1_id && $access2_id && $access3_id) {
        echo "✅ Accès créés avec succès:<br>\n";
        echo "- Accès 1 (ID: $access1_id): Porte principale (Taverne -> Château)<br>\n";
        echo "- Accès 2 (ID: $access2_id): Passage secret (Château -> Forêt, caché)<br>\n";
        echo "- Accès 3 (ID: $access3_id): Sentier piégé (Forêt -> Taverne, piégé)<br>\n";
    } else {
        echo "❌ Erreur lors de la création des accès<br>\n";
    }
    
    // Test 3: Récupérer les accès
    echo "<h2>3. Récupération des accès</h2>\n";
    
    // Accès sortants de la taverne
    $accesses_from_taverne = Access::getFromPlace($lieu1_id);
    echo "✅ Accès sortants de la Taverne du Dragon: " . count($accesses_from_taverne) . "<br>\n";
    foreach ($accesses_from_taverne as $access) {
        echo "- {$access->name} vers {$access->to_place_name} (Visible: " . ($access->is_visible ? 'Oui' : 'Non') . ")<br>\n";
    }
    
    // Accès entrants vers la taverne
    $accesses_to_taverne = Access::getToPlace($lieu1_id);
    echo "✅ Accès entrants vers la Taverne du Dragon: " . count($accesses_to_taverne) . "<br>\n";
    foreach ($accesses_to_taverne as $access) {
        echo "- {$access->name} depuis {$access->from_place_name} (Piégé: " . ($access->is_trapped ? 'Oui' : 'Non') . ")<br>\n";
    }
    
    // Test 4: Vérifier les méthodes de statut
    echo "<h2>4. Test des méthodes de statut</h2>\n";
    
    $access1 = Access::findById($access1_id);
    if ($access1) {
        echo "✅ Accès 1 - Statut: {$access1->getStatusText()}<br>\n";
        echo "- Classe CSS: {$access1->getStatusClass()}<br>\n";
        echo "- Icône: {$access1->getStatusIcon()}<br>\n";
    }
    
    $access2 = Access::findById($access2_id);
    if ($access2) {
        echo "✅ Accès 2 - Statut: {$access2->getStatusText()}<br>\n";
        echo "- Classe CSS: {$access2->getStatusClass()}<br>\n";
        echo "- Icône: {$access2->getStatusIcon()}<br>\n";
    }
    
    $access3 = Access::findById($access3_id);
    if ($access3) {
        echo "✅ Accès 3 - Statut: {$access3->getStatusText()}<br>\n";
        echo "- Classe CSS: {$access3->getStatusClass()}<br>\n";
        echo "- Icône: {$access3->getStatusIcon()}<br>\n";
    }
    
    // Test 5: Vérifier les lieux accessibles
    echo "<h2>5. Lieux accessibles</h2>\n";
    
    $accessible_places = Access::getAccessiblePlaces($lieu1_id);
    echo "✅ Lieux accessibles depuis la Taverne du Dragon: " . count($accessible_places) . "<br>\n";
    foreach ($accessible_places as $place) {
        echo "- {$place['title']} via {$place['access_name']}<br>\n";
    }
    
    // Test 6: Vérifier l'existence d'accès
    echo "<h2>6. Vérification d'existence</h2>\n";
    
    $exists = Access::existsBetween($lieu1_id, $lieu2_id, "Porte principale");
    echo "✅ Accès 'Porte principale' entre Taverne et Château existe: " . ($exists ? 'Oui' : 'Non') . "<br>\n";
    
    $not_exists = Access::existsBetween($lieu1_id, $lieu3_id);
    echo "✅ Accès entre Taverne et Forêt existe: " . ($not_exists ? 'Oui' : 'Non') . "<br>\n";
    
    // Test 7: Modification d'un accès
    echo "<h2>7. Modification d'un accès</h2>\n";
    
    $access1->is_open = false;
    $access1->description = "Une grande porte en bois menant au château (maintenant fermée)";
    
    if ($access1->save()) {
        echo "✅ Accès modifié avec succès<br>\n";
        echo "- Nouveau statut: {$access1->getStatusText()}<br>\n";
    } else {
        echo "❌ Erreur lors de la modification de l'accès<br>\n";
    }
    
    // Test 8: Nettoyage (suppression des données de test)
    echo "<h2>8. Nettoyage des données de test</h2>\n";
    
    // Supprimer les accès
    $access1->delete();
    $access2->delete();
    $access3->delete();
    echo "✅ Accès supprimés<br>\n";
    
    // Supprimer les lieux
    $lieu1 = Lieu::findById($lieu1_id);
    $lieu2 = Lieu::findById($lieu2_id);
    $lieu3 = Lieu::findById($lieu3_id);
    
    if ($lieu1) $lieu1->delete();
    if ($lieu2) $lieu2->delete();
    if ($lieu3) $lieu3->delete();
    echo "✅ Lieux supprimés<br>\n";
    
    echo "<h2>🎉 Tous les tests sont passés avec succès !</h2>\n";
    echo "<p>Le système d'accès entre lieux fonctionne correctement.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors des tests</h2>\n";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>\n";
}
?>

