<?php
require_once 'classes/init.php';
require_once 'classes/Room.php';
require_once 'classes/Region.php';

// Find a place in region 172
$pdo = getPDO();
$stmt = $pdo->query("SELECT id FROM places WHERE region_id = 172 LIMIT 1");
$placeId = $stmt->fetchColumn();

if (!$placeId) {
    echo "No place found in region 172 to test with.\n";
    exit;
}

echo "Testing with Place ID: $placeId\n";

// Emulate POST request content
$_POST['place_id'] = $placeId;
$_POST['location_id'] = 0; // Try moving to "orphan"

// Mock session
$_SESSION['user_id'] = 1; // Assuming user 1 is admin/DM
// We need to ensure we can run the API script.
// Since the API script checks for session, we might need to "include" it instead of exec'ing it, 
// to share the mock session.

// Capture output
ob_start();
// Include the API script. note: we are in root, script is in api/
// accessing files via ../ won't work if included from root.
// We must carefully set up the environment or chdir.
chdir('api');
include 'update_place_location.php';
$output = ob_get_clean();

echo "\n--- API Output ---\n";
echo $output;
echo "\n------------------\n";
