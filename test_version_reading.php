<?php
// Test de lecture du fichier VERSION

function getApplicationVersion() {
    $versionFile = __DIR__ . '/VERSION';
    echo "🔍 Fichier VERSION: $versionFile\n";
    
    if (!file_exists($versionFile)) {
        echo "❌ Fichier VERSION non trouvé\n";
        return [
            'VERSION' => 'unknown',
            'DEPLOY_DATE' => 'unknown',
            'ENVIRONMENT' => 'unknown',
            'GIT_COMMIT' => 'unknown',
            'BUILD_ID' => 'unknown',
            'RELEASE_NOTES' => 'unknown'
        ];
    }
    
    echo "✅ Fichier VERSION trouvé\n";
    $content = file_get_contents($versionFile);
    echo "📄 Contenu du fichier:\n";
    echo "---\n$content\n---\n";
    
    $lines = explode("\n", $content);
    $version = [];
    
    foreach ($lines as $line) {
        echo "🔍 Ligne: '$line'\n";
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $version[$key] = $value;
            echo "   ✅ Ajouté: $key = $value\n";
        } else {
            echo "   ⚠️  Ignoré (pas de '=')\n";
        }
    }
    
    echo "\n📊 Résultat final:\n";
    print_r($version);
    
    return $version;
}

echo "🧪 Test de lecture du fichier VERSION\n\n";
$result = getApplicationVersion();
?>
