<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Test simple de la fonction getApplicationVersion
function getApplicationVersion() {
    $versionFile = __DIR__ . '/VERSION';
    if (!file_exists($versionFile)) {
        return [
            'VERSION' => 'unknown',
            'DEPLOY_DATE' => 'unknown',
            'ENVIRONMENT' => 'unknown',
            'GIT_COMMIT' => 'unknown',
            'BUILD_ID' => 'unknown',
            'RELEASE_NOTES' => 'unknown'
        ];
    }
    
    $content = file_get_contents($versionFile);
    $lines = explode("\n", $content);
    $version = [];
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $version[trim($key)] = trim($value);
        }
    }
    
    return $version;
}

echo "🧪 Test de la fonction getApplicationVersion\n\n";
$appVersion = getApplicationVersion();

echo "📊 Version de l'application:\n";
foreach ($appVersion as $key => $value) {
    echo "   $key: $value\n";
}

echo "\n🔍 Test de connexion à la base de données:\n";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "   ✅ Connexion à la base de données OK\n";
} catch (Exception $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
}
?>
