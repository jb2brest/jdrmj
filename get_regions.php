<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['country_id']) || !is_numeric($_GET['country_id'])) {
    echo json_encode([]);
    exit;
}

$countryId = (int)$_GET['country_id'];
$regions = getRegionsByCountry($countryId);

echo json_encode($regions);
?>
