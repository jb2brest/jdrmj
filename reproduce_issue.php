<?php
require_once 'classes/init.php';
require_once 'classes/Location.php';
require_once 'classes/Region.php';

// Setup test data
$regionId = 172; // From user report
$locationName = "Chateau d'Ignis"; // From user report
$locationDescription = "Test description";

// Create a mock PDO if necessary, or use real one
// Assuming we are connected to DB

try {
    echo "Attempting to create duplicate location...\n";
    // First creation (might succeed or fail if already exists)
    try {
        Location::create($locationName, $regionId, $locationDescription);
    } catch (Exception $e) {
        // Ignore first error, we want to force duplicate
    }
    
    // Second creation - SHOULD FAIL with duplicate
    $result = Location::create($locationName, $regionId, $locationDescription);
    
    echo "Unexpected success! Location created.\n";
} catch (Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
} catch (Throwable $t) {
    echo "Caught Throwable: " . $t->getMessage() . "\n";
    echo "Class: " . get_class($t) . "\n";
}
