<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$classId = (int)($_GET['id'] ?? 0);

if ($classId === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de classe invalide'
    ]);
    exit;
}

try {
    $proficiencies = Character::getClassProficiencies($classId);
    
    echo json_encode([
        'success' => true,
        'armorProficiencies' => $proficiencies['armor'],
        'weaponProficiencies' => $proficiencies['weapon'],
        'toolProficiencies' => $proficiencies['tool']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des compétences: ' . $e->getMessage()
    ]);
}
?>

