<?php
/**
 * Test final de l'API save_dice_roll.php
 */

echo "🧪 Test final de l'API save_dice_roll.php\n";
echo str_repeat("=", 40) . "\n\n";

// Démarrer la session
session_start();
$_SESSION['user_id'] = 2;

// Simuler les données JSON
$testData = [
    'campaign_id' => 120,
    'dice_sides' => 20,
    'quantity' => 1,
    'results' => [15],
    'total' => 15,
    'max_result' => 15,
    'min_result' => 15,
    'is_hidden' => false
];

echo "📊 Données de test:\n";
print_r($testData);
echo "\n";

// Simuler une requête POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simuler l'input JSON
$jsonData = json_encode($testData);

// Créer un fichier temporaire avec les données JSON
$tempFile = tmpfile();
fwrite($tempFile, $jsonData);
rewind($tempFile);

// Rediriger php://input vers notre fichier temporaire
stream_wrapper_unregister('php');
stream_wrapper_register('php', 'TestStreamWrapper');

class TestStreamWrapper {
    private $tempFile;
    
    public function __construct() {
        global $tempFile;
        $this->tempFile = $tempFile;
    }
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        return fread($this->tempFile, $count);
    }
    
    public function stream_eof() {
        return feof($this->tempFile);
    }
    
    public function stream_stat() {
        return fstat($this->tempFile);
    }
}

// Capturer la sortie
ob_start();

// Inclure l'API
include 'api/save_dice_roll.php';

// Récupérer la sortie
$output = ob_get_clean();

echo "📄 Réponse de l'API:\n";
echo $output . "\n\n";

// Décoder la réponse
$response = json_decode($output, true);
if ($response) {
    echo "📊 Réponse décodée:\n";
    print_r($response);
} else {
    echo "❌ Erreur de décodage JSON\n";
}

echo "\n🎯 Test terminé!\n";
?>
