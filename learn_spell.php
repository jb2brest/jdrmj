<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['character_id']) || !isset($input['spell_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$character_id = (int)$input['character_id'];
$spell_id = (int)$input['spell_id'];

// Vérifier que le personnage appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT id, class_id FROM characters WHERE id = ? AND user_id = ?");
$stmt->execute([$character_id, $_SESSION['user_id']]);
$character = $stmt->fetch();

if (!$character) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé au personnage.']);
    exit;
}

try {
    // Récupérer le nom de la classe du personnage
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$character['class_id']]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Classe du personnage introuvable.']);
        exit;
    }
    
    // Vérifier que le sort est disponible pour cette classe
    $stmt = $pdo->prepare("
        SELECT id, name, level, classes
        FROM spells
        WHERE id = ? AND classes LIKE ?
    ");
    $stmt->execute([$spell_id, '%' . $class['name'] . '%']);
    $spell = $stmt->fetch();
    
    if (!$spell) {
        echo json_encode(['success' => false, 'message' => 'Ce sort n\'est pas disponible pour votre classe.']);
        exit;
    }
    
    // Vérifier que le personnage ne connaît pas déjà ce sort
    $stmt = $pdo->prepare("SELECT character_id FROM character_spells WHERE character_id = ? AND spell_id = ?");
    $stmt->execute([$character_id, $spell_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous connaissez déjà ce sort.']);
        exit;
    }
    
    // Déterminer si le sort doit être automatiquement préparé
    $autoPrepared = 0; // Par défaut, non préparé
    
    // Pour l'Ensorceleur, tous les sorts appris sont automatiquement préparés
    if (strpos(strtolower($class['name']), 'ensorceleur') !== false) {
        $autoPrepared = 1;
    }
    
    // Ajouter le sort au personnage
    $stmt = $pdo->prepare("
        INSERT INTO character_spells (character_id, spell_id, prepared) 
        VALUES (?, ?, ?)
    ");
    $success = $stmt->execute([$character_id, $spell_id, $autoPrepared]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Sort appris avec succès !',
            'spell' => [
                'id' => $spell_id,
                'name' => $spell['name'],
                'level' => $spell['level']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du sort.']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
