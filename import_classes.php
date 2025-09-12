<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier que l'utilisateur est connecté et est un MJ
requireLogin();
if (!isDM()) {
    die('Accès refusé. Seuls les MJ peuvent importer les classes.');
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'imported_classes' => 0, 'imported_evolutions' => 0];

try {
    // Exécuter le script SQL pour créer les tables
    $sqlFile = 'database/create_classes_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Le fichier SQL $sqlFile n'existe pas.");
    }
    
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    
    // Importer les classes de base
    $classesFile = './aidednddata/Aidedd - Classe.csv';
    if (!file_exists($classesFile)) {
        throw new Exception("Le fichier CSV des classes n'existe pas à $classesFile");
    }
    
    $file = fopen($classesFile, 'r');
    if ($file === FALSE) {
        throw new Exception("Impossible d'ouvrir le fichier CSV des classes.");
    }
    
    // Lire l'en-tête
    $header = fgetcsv($file);
    
    $importedClasses = 0;
    $stmt = $pdo->prepare("INSERT INTO classes (name, hit_dice, hit_point_bonus, armor_proficiencies, weapon_proficiencies, tool_proficiencies, saving_throws, skill_count, skill_choices, starting_equipment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($data = fgetcsv($file)) !== FALSE) {
        if (count($data) >= 10) {
            $name = trim($data[0]);
            $hitDice = trim($data[1]);
            $hitPointBonus = trim($data[2]);
            $armorProficiencies = trim($data[3]);
            $weaponProficiencies = trim($data[4]);
            $toolProficiencies = trim($data[5]);
            $savingThrows = trim($data[6]);
            $skillCount = intval($data[7]);
            $skillChoices = trim($data[8]);
            $startingEquipment = trim($data[9]);
            
            $stmt->execute([
                $name,
                $hitDice,
                $hitPointBonus,
                $armorProficiencies,
                $weaponProficiencies,
                $toolProficiencies,
                $savingThrows,
                $skillCount,
                $skillChoices,
                $startingEquipment
            ]);
            
            $importedClasses++;
        }
    }
    fclose($file);
    
    // Importer les évolutions de classe
    $evolutionFile = './aidednddata/Aidedd - EvolutionClasses.csv';
    if (!file_exists($evolutionFile)) {
        throw new Exception("Le fichier CSV des évolutions n'existe pas à $evolutionFile");
    }
    
    $file = fopen($evolutionFile, 'r');
    if ($file === FALSE) {
        throw new Exception("Impossible d'ouvrir le fichier CSV des évolutions.");
    }
    
    // Lire l'en-tête
    $header = fgetcsv($file);
    
    $importedEvolutions = 0;
    $stmt = $pdo->prepare("INSERT INTO class_evolution (class_id, level, proficiency_bonus, features, sneak_attack, martial_arts, ki_points, unarmored_movement, sorcery_points, rages, rage_damage, cantrips_known, spells_known, spell_slots_1st, spell_slots_2nd, spell_slots_3rd, spell_slots_4th, spell_slots_5th, spell_slots_6th, spell_slots_7th, spell_slots_8th, spell_slots_9th) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($data = fgetcsv($file)) !== FALSE) {
        if (count($data) >= 3) {
            $className = trim($data[0]);
            $level = intval($data[1]);
            
            // Trouver l'ID de la classe
            $classStmt = $pdo->prepare("SELECT id FROM classes WHERE name = ?");
            $classStmt->execute([$className]);
            $class = $classStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($class) {
                $classId = $class['id'];
                $proficiencyBonus = isset($data[2]) ? intval($data[2]) : null;
                $features = isset($data[3]) ? trim($data[3]) : null;
                $sneakAttack = isset($data[4]) ? trim($data[4]) : null;
                $martialArts = isset($data[5]) ? trim($data[5]) : null;
                $kiPoints = isset($data[6]) ? intval($data[6]) : null;
                $unarmoredMovement = isset($data[7]) ? trim($data[7]) : null;
                $sorceryPoints = isset($data[8]) ? intval($data[8]) : null;
                $rages = isset($data[9]) ? intval($data[9]) : null;
                $rageDamage = isset($data[10]) ? trim($data[10]) : null;
                $cantripsKnown = isset($data[11]) ? intval($data[11]) : null;
                $spellsKnown = isset($data[12]) ? intval($data[12]) : null;
                $spellSlots1st = isset($data[13]) ? intval($data[13]) : null;
                $spellSlots2nd = isset($data[14]) ? intval($data[14]) : null;
                $spellSlots3rd = isset($data[15]) ? intval($data[15]) : null;
                $spellSlots4th = isset($data[16]) ? intval($data[16]) : null;
                $spellSlots5th = isset($data[17]) ? intval($data[17]) : null;
                $spellSlots6th = isset($data[18]) ? intval($data[18]) : null;
                $spellSlots7th = isset($data[19]) ? intval($data[19]) : null;
                $spellSlots8th = isset($data[20]) ? intval($data[20]) : null;
                $spellSlots9th = isset($data[21]) ? intval($data[21]) : null;
                
                $stmt->execute([
                    $classId,
                    $level,
                    $proficiencyBonus,
                    $features,
                    $sneakAttack,
                    $martialArts,
                    $kiPoints,
                    $unarmoredMovement,
                    $sorceryPoints,
                    $rages,
                    $rageDamage,
                    $cantripsKnown,
                    $spellsKnown,
                    $spellSlots1st,
                    $spellSlots2nd,
                    $spellSlots3rd,
                    $spellSlots4th,
                    $spellSlots5th,
                    $spellSlots6th,
                    $spellSlots7th,
                    $spellSlots8th,
                    $spellSlots9th
                ]);
                
                $importedEvolutions++;
            }
        }
    }
    fclose($file);
    
    $response['success'] = true;
    $response['message'] = "Importation réussie !";
    $response['imported_classes'] = $importedClasses;
    $response['imported_evolutions'] = $importedEvolutions;
    
} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    error_log("Erreur import_classes.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Erreur: ' . $e->getMessage();
    error_log("Erreur import_classes.php: " . $e->getMessage());
}

echo json_encode($response);
?>
