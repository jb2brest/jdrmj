<?php
// Test avec session admin simulée
session_start();

// Simuler une session admin
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_test';
$_SESSION['role'] = 'admin';
$_SESSION['is_dm'] = 1;

echo "🧪 Test avec session admin simulée\n\n";

// Inclure les fonctions
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "🔍 Vérification des fonctions:\n";
echo "   isLoggedIn(): " . (isLoggedIn() ? 'Oui' : 'Non') . "\n";
echo "   isAdmin(): " . (isAdmin() ? 'Oui' : 'Non') . "\n";
echo "   isDM(): " . (isDM() ? 'Oui' : 'Non') . "\n";

// Test de la fonction getApplicationVersion
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

echo "\n📊 Version de l'application:\n";
$appVersion = getApplicationVersion();
foreach ($appVersion as $key => $value) {
    echo "   $key: $value\n";
}
?>
