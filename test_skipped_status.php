<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test de l'affichage des tests SKIPPED\n";
echo "======================================\n\n";

require_once 'admin_versions.php';

$data = getTestReports();
$stats = calculateTestStatistics($data);

echo "Total tests: " . $stats['total_tests'] . "\n";
echo "Skipped tests: " . $stats['skipped_tests'] . "\n";
echo "Tests by category: " . json_encode(array_keys($stats['tests_by_category'])) . "\n\n";

// Chercher test_user_logout
foreach ($stats['tests_by_category'] as $category => $tests) {
    foreach ($tests as $test) {
        if ($test['name'] == 'test_user_logout') {
            echo "Test trouvé dans catégorie '$category':\n";
            echo json_encode($test, JSON_PRETTY_PRINT) . "\n";
        }
    }
}
?>
