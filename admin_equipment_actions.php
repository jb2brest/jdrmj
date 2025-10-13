<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/object_auto_insert.php';

// Vérifier que l'utilisateur est admin
User::requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            addEquipment();
            break;
        case 'update':
            updateEquipment();
            break;
        case 'delete':
            deleteEquipment();
            break;
        case 'get_details':
            getEquipmentDetails();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

function addEquipment() {
    global $pdo;
    
    $src = $_POST['src'] ?? '';
    $src_id = (int)($_POST['src_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $type_id = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
    $type_filter = $_POST['type_filter'] ?? null;
    $no_choix = !empty($_POST['no_choix']) ? (int)$_POST['no_choix'] : null;
    $option_letter = $_POST['option_letter'] ?? null;
    $nb = (int)($_POST['nb'] ?? 1);
    $groupe_id = !empty($_POST['groupe_id']) ? (int)$_POST['groupe_id'] : null;
    $type_choix = $_POST['type_choix'] ?? 'obligatoire';
    
    // Validation
    if (empty($src) || $src_id <= 0 || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        return;
    }
    
    if (!in_array($src, ['class', 'race', 'background'])) {
        echo json_encode(['success' => false, 'message' => 'Source invalide']);
        return;
    }
    
    if (!in_array($type, ['weapon', 'armor', 'bouclier', 'outils', 'accessoire', 'sac', 'nourriture', 'instrument'])) {
        echo json_encode(['success' => false, 'message' => 'Type invalide']);
        return;
    }
    
    if (!in_array($type_choix, ['obligatoire', 'à_choisir'])) {
        echo json_encode(['success' => false, 'message' => 'Type de choix invalide']);
        return;
    }
    
    // Vérifier que la source existe
    $sourceTable = $src === 'class' ? 'classes' : ($src === 'race' ? 'races' : 'backgrounds');
    $stmt = $pdo->prepare("SELECT id FROM $sourceTable WHERE id = ?");
    $stmt->execute([$src_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Source introuvable']);
        return;
    }
    
    // Auto-insertion des objets pour les types sac, outils, nourriture, accessoire, instrument
    if (in_array($type, ['sac', 'outils', 'nourriture', 'accessoire', 'instrument']) && !$type_id) {
        // Si pas de type_id spécifié, on ne peut pas auto-insérer
        // L'utilisateur doit spécifier un type_id ou un nom d'objet
        if (empty($_POST['object_name'])) {
            echo json_encode(['success' => false, 'message' => 'Pour les objets de type ' . $type . ', veuillez spécifier un type_id ou un nom d\'objet']);
            return;
        }
        
        $objectName = $_POST['object_name'];
        $type_id = autoInsertObject($pdo, $type, $objectName);
    }
    
    // Insérer l'équipement
    $stmt = $pdo->prepare("
        INSERT INTO starting_equipment 
        (src, src_id, type, type_id, type_filter, no_choix, option_letter, nb, groupe_id, type_choix) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$src, $src_id, $type, $type_id, $type_filter, $no_choix, $option_letter, $nb, $groupe_id, $type_choix]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Équipement ajouté avec succès',
        'id' => $pdo->lastInsertId()
    ]);
}

function updateEquipment() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $src = $_POST['src'] ?? '';
    $src_id = (int)($_POST['src_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $type_id = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
    $type_filter = $_POST['type_filter'] ?? null;
    $no_choix = !empty($_POST['no_choix']) ? (int)$_POST['no_choix'] : null;
    $option_letter = $_POST['option_letter'] ?? null;
    $nb = (int)($_POST['nb'] ?? 1);
    $groupe_id = !empty($_POST['groupe_id']) ? (int)$_POST['groupe_id'] : null;
    $type_choix = $_POST['type_choix'] ?? 'obligatoire';
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        return;
    }
    
    // Validation (même que pour add)
    if (empty($src) || $src_id <= 0 || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        return;
    }
    
    if (!in_array($src, ['class', 'race', 'background'])) {
        echo json_encode(['success' => false, 'message' => 'Source invalide']);
        return;
    }
    
    if (!in_array($type, ['weapon', 'armor', 'bouclier', 'outils', 'accessoire', 'sac', 'nourriture', 'instrument'])) {
        echo json_encode(['success' => false, 'message' => 'Type invalide']);
        return;
    }
    
    if (!in_array($type_choix, ['obligatoire', 'à_choisir'])) {
        echo json_encode(['success' => false, 'message' => 'Type de choix invalide']);
        return;
    }
    
    // Vérifier que l'équipement existe
    $stmt = $pdo->prepare("SELECT id FROM starting_equipment WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Équipement introuvable']);
        return;
    }
    
    // Vérifier que la source existe
    $sourceTable = $src === 'class' ? 'classes' : ($src === 'race' ? 'races' : 'backgrounds');
    $stmt = $pdo->prepare("SELECT id FROM $sourceTable WHERE id = ?");
    $stmt->execute([$src_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Source introuvable']);
        return;
    }
    
    // Mettre à jour l'équipement
    $stmt = $pdo->prepare("
        UPDATE starting_equipment 
        SET src = ?, src_id = ?, type = ?, type_id = ?, type_filter = ?, no_choix = ?, option_letter = ?, nb = ?, groupe_id = ?, type_choix = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$src, $src_id, $type, $type_id, $type_filter, $no_choix, $option_letter, $nb, $groupe_id, $type_choix, $id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Équipement mis à jour avec succès'
    ]);
}

function deleteEquipment() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        return;
    }
    
    // Vérifier que l'équipement existe
    $stmt = $pdo->prepare("SELECT id FROM starting_equipment WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Équipement introuvable']);
        return;
    }
    
    // Supprimer l'équipement
    $stmt = $pdo->prepare("DELETE FROM starting_equipment WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Équipement supprimé avec succès'
    ]);
}

function getEquipmentDetails() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT se.*, 
               CASE 
                   WHEN se.src = 'class' THEN c.name
                   WHEN se.src = 'race' THEN r.name
                   WHEN se.src = 'background' THEN b.name
                   ELSE 'Inconnu'
               END as source_name,
               CASE 
                   WHEN se.type = 'weapon' AND se.type_id IS NOT NULL THEN w.name
                   WHEN se.type = 'armor' AND se.type_id IS NOT NULL THEN a.name
                   WHEN se.type = 'bouclier' AND se.type_id IS NOT NULL THEN a.name
                   WHEN se.type = 'sac' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'outils' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'nourriture' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'accessoire' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type = 'instrument' AND se.type_id IS NOT NULL THEN o.nom
                   WHEN se.type_filter IS NOT NULL THEN se.type_filter
                   ELSE NULL
               END as object_name
        FROM starting_equipment se
        LEFT JOIN classes c ON se.src = 'class' AND se.src_id = c.id
        LEFT JOIN races r ON se.src = 'race' AND se.src_id = r.id
        LEFT JOIN backgrounds b ON se.src = 'background' AND se.src_id = b.id
        LEFT JOIN weapons w ON se.type = 'weapon' AND se.type_id = w.id
        LEFT JOIN armor a ON (se.type = 'armor' OR se.type = 'bouclier') AND se.type_id = a.id
        LEFT JOIN Object o ON (se.type = 'sac' OR se.type = 'outils' OR se.type = 'nourriture' OR se.type = 'accessoire' OR se.type = 'instrument') AND se.type_id = o.id
        WHERE se.id = ?
    ");
    
    $stmt->execute([$id]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipment) {
        echo json_encode(['success' => false, 'message' => 'Équipement introuvable']);
        return;
    }
    
    echo json_encode([
        'success' => true, 
        'equipment' => $equipment
    ]);
}
?>
