<?php
/**
 * Test d'accès à view_character.php
 */

session_start();
require_once 'classes/init.php';

try {
    echo "🧪 Test d'accès à view_character.php\n";
    echo "=" . str_repeat("=", 40) . "\n";
    
    // Simuler une session utilisateur
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Jean';
    $_SESSION['role'] = 'admin';
    
    echo "✅ Session simulée (Admin)\n";
    
    // Récupérer un personnage existant
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, name FROM characters LIMIT 1");
    $character = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$character) {
        echo "❌ Aucun personnage trouvé\n";
        exit();
    }
    
    $characterId = $character['id'];
    echo "✅ Personnage trouvé (ID: $characterId) - " . $character['name'] . "\n";
    
    // Test de l'inclusion du template
    echo "\n🔍 Test de l'inclusion du template:\n";
    
    // Simuler les variables nécessaires
    $template_vars = [
        'character' => $character,
        'page_title' => 'Fiche de personnage',
        'js_vars' => [
            'canEdit' => true,
            'isOwnerDM' => true,
            'characterId' => $characterId
        ]
    ];
    
    // Capturer la sortie
    ob_start();
    include_once 'templates/view_character_template.php';
    $output = ob_get_clean();
    
    if (strlen($output) > 100) {
        echo "   ✅ Template chargé avec succès (" . strlen($output) . " caractères)\n";
        echo "   📋 Contenu: " . substr($output, 0, 100) . "...\n";
    } else {
        echo "   ❌ Template vide ou erreur\n";
        echo "   📄 Sortie: " . $output . "\n";
    }
    
    // Test de view_character.php complet
    echo "\n🌐 Test de view_character.php complet:\n";
    $_GET['id'] = $characterId;
    
    ob_start();
    include_once 'view_character.php';
    $fullOutput = ob_get_clean();
    
    if (strlen($fullOutput) > 100) {
        echo "   ✅ Page complète chargée avec succès (" . strlen($fullOutput) . " caractères)\n";
        
        // Vérifier la présence d'éléments clés
        $checks = [
            'Titre du personnage' => strpos($fullOutput, $character['name']) !== false,
            'Bootstrap CSS' => strpos($fullOutput, 'bootstrap') !== false,
            'JavaScript' => strpos($fullOutput, 'jdrmj.js') !== false,
            'HTML structure' => strpos($fullOutput, '<html') !== false
        ];
        
        foreach ($checks as $check => $result) {
            echo "      - $check: " . ($result ? '✅' : '❌') . "\n";
        }
    } else {
        echo "   ❌ Page vide ou erreur\n";
        echo "   📄 Sortie: " . $fullOutput . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test terminé\n";
?>
