<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'classes' => []];

try {
    $stmt = $pdo->query("SELECT id, name, hit_dice, hit_point_bonus, armor_proficiencies, weapon_proficiencies, tool_proficiencies, saving_throws, skill_count, skill_choices, starting_equipment FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['classes'] = $classes;

} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    error_log("Erreur get_classes.php: " . $e->getMessage());
}

echo json_encode($response);
?>
