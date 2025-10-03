<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'classes/init.php';

header('Content-Type: application/json');

if (!User::isLoggedIn()) {
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
$character = Character::findById($character_id);
if (!$character || $character->getUserId() != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé au personnage.']);
    exit;
}

try {
    // Récupérer la classe du personnage
    $class = $character->getClass();
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Classe du personnage introuvable.']);
        exit;
    }
    
    // Récupérer le sort
    $spell = Sort::findById($spell_id);
    if (!$spell) {
        echo json_encode(['success' => false, 'message' => 'Sort introuvable.']);
        exit;
    }
    
    // Vérifier que le sort est disponible pour cette classe
    if (strpos($spell->getClasses(), $class['name']) === false) {
        echo json_encode(['success' => false, 'message' => 'Ce sort n\'est pas disponible pour votre classe.']);
        exit;
    }
    
    // Vérifier que le personnage ne connaît pas déjà ce sort
    $characterSpells = Sort::getCharacterSpells($character_id);
    foreach ($characterSpells as $characterSpell) {
        if ($characterSpell['id'] == $spell_id) {
            echo json_encode(['success' => false, 'message' => 'Vous connaissez déjà ce sort.']);
            exit;
        }
    }
    
    // Déterminer si le sort doit être automatiquement préparé
    $autoPrepared = false; // Par défaut, non préparé
    
    // Pour l'Ensorceleur, tous les sorts appris sont automatiquement préparés
    if (strpos(strtolower($class['name']), 'ensorceleur') !== false) {
        $autoPrepared = true;
    }
    
    // Ajouter le sort au personnage via la classe Sort
    $success = Sort::addToCharacter($character_id, $spell_id, $autoPrepared, true);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Sort appris avec succès !',
            'spell' => [
                'id' => $spell_id,
                'name' => $spell->getName(),
                'level' => $spell->getLevel()
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du sort.']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>
