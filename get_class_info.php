<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'class' => null];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'ID de classe manquant ou invalide';
    echo json_encode($response);
    exit;
}

$classId = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($class) {
        $response['success'] = true;
        $response['class'] = $class;
    } else {
        $response['message'] = 'Classe non trouvée';
    }

} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    error_log("Erreur get_class_info.php: " . $e->getMessage());
}

echo json_encode($response);
?>
