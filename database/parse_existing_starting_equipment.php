<?php
/**
 * Script pour parser et migrer les données d'équipement de départ existantes
 * vers la nouvelle table starting_equipment
 */

require_once 'config/database.php';

// Fonction pour parser l'équipement de classe existant
function parseClassEquipment($equipmentText, $classId) {
    if (!$equipmentText) {
        return [];
    }
    
    $equipmentItems = [];
    $lines = explode("\n", trim($equipmentText));
    $groupId = 1;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Vérifier si la ligne contient des choix (a), (b), (c)
        if (preg_match('/\([abc]\)/', $line)) {
            // C'est un choix d'équipement
            if (preg_match_all('/\(([abc])\)\s*([^(]+?)(?=\s*\([abc]\)|$)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $choice = $match[1];
                    $description = trim($match[2]);
                    $description = preg_replace('/\s+ou\s*$/', '', $description);
                    
                    // Déterminer le type d'équipement
                    $type = determineEquipmentType($description);
                    
                    $equipmentItems[] = [
                        'src' => 'class',
                        'src_id' => $classId,
                        'type' => $type,
                        'type_id' => null, // À déterminer plus tard
                        'option_indice' => $choice,
                        'groupe_id' => $groupId,
                        'type_choix' => 'à_choisir'
                    ];
                }
            }
            $groupId++;
        } else {
            // C'est un équipement fixe
            $type = determineEquipmentType($line);
            
            $equipmentItems[] = [
                'src' => 'class',
                'src_id' => $classId,
                'type' => $type,
                'type_id' => null,
                'option_indice' => null,
                'groupe_id' => $groupId,
                'type_choix' => 'obligatoire'
            ];
            $groupId++;
        }
    }
    
    return $equipmentItems;
}

// Fonction pour déterminer le type d'équipement à partir de la description
function determineEquipmentType($description) {
    $description = strtolower($description);
    
    if (strpos($description, 'arme') !== false || 
        strpos($description, 'épée') !== false || 
        strpos($description, 'hache') !== false ||
        strpos($description, 'arc') !== false ||
        strpos($description, 'carreaux') !== false ||
        strpos($description, 'flèches') !== false ||
        strpos($description, 'javelines') !== false) {
        return 'Arme';
    }
    
    if (strpos($description, 'armure') !== false || 
        strpos($description, 'cotte') !== false ||
        strpos($description, 'cuir') !== false) {
        return 'Armure';
    }
    
    if (strpos($description, 'bouclier') !== false) {
        return 'Bouclier';
    }
    
    if (strpos($description, 'sac') !== false) {
        return 'Sac';
    }
    
    if (strpos($description, 'outil') !== false) {
        return 'Outils';
    }
    
    return 'Accessoire';
}

// Fonction pour parser l'équipement de background
function parseBackgroundEquipment($equipmentText, $backgroundId) {
    if (!$equipmentText) {
        return [];
    }
    
    $equipmentItems = [];
    $parts = preg_split('/[,.]/', $equipmentText);
    $groupId = 1;
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        // Ignorer les mentions de pièces d'or (gérées séparément)
        if (preg_match('/\d+\s*po/i', $part)) {
            continue;
        }
        
        $type = determineEquipmentType($part);
        
        $equipmentItems[] = [
            'src' => 'background',
            'src_id' => $backgroundId,
            'type' => $type,
            'type_id' => null,
            'option_indice' => null,
            'groupe_id' => $groupId,
            'type_choix' => 'obligatoire'
        ];
        $groupId++;
    }
    
    return $equipmentItems;
}

try {
    echo "Début de la migration des données d'équipement de départ...\n";
    
    // Créer la table starting_equipment
    $createTableSQL = file_get_contents(__DIR__ . '/create_starting_equipment_table.sql');
    $pdo->exec($createTableSQL);
    echo "Table starting_equipment créée.\n";
    
    // Récupérer toutes les classes
    $stmt = $pdo->query("SELECT id, name, starting_equipment FROM classes WHERE starting_equipment IS NOT NULL AND starting_equipment != ''");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Traitement de " . count($classes) . " classes...\n";
    
    foreach ($classes as $class) {
        echo "Traitement de la classe: " . $class['name'] . "\n";
        
        $equipmentItems = parseClassEquipment($class['starting_equipment'], $class['id']);
        
        foreach ($equipmentItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO starting_equipment 
                (src, src_id, type, type_id, option_indice, groupe_id, type_choix) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['src'],
                $item['src_id'],
                $item['type'],
                $item['type_id'],
                $item['option_indice'],
                $item['groupe_id'],
                $item['type_choix']
            ]);
        }
        
        echo "  - " . count($equipmentItems) . " items d'équipement ajoutés.\n";
    }
    
    // Récupérer tous les backgrounds
    $stmt = $pdo->query("SELECT id, name, equipment FROM backgrounds WHERE equipment IS NOT NULL AND equipment != ''");
    $backgrounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Traitement de " . count($backgrounds) . " backgrounds...\n";
    
    foreach ($backgrounds as $background) {
        echo "Traitement du background: " . $background['name'] . "\n";
        
        $equipmentItems = parseBackgroundEquipment($background['equipment'], $background['id']);
        
        foreach ($equipmentItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO starting_equipment 
                (src, src_id, type, type_id, option_indice, groupe_id, type_choix) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['src'],
                $item['src_id'],
                $item['type'],
                $item['type_id'],
                $item['option_indice'],
                $item['groupe_id'],
                $item['type_choix']
            ]);
        }
        
        echo "  - " . count($equipmentItems) . " items d'équipement ajoutés.\n";
    }
    
    echo "Migration terminée avec succès!\n";
    echo "Vérification des données...\n";
    
    // Vérifier les données migrées
    $stmt = $pdo->query("SELECT src, COUNT(*) as count FROM starting_equipment GROUP BY src");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $result) {
        echo "  - " . $result['src'] . ": " . $result['count'] . " items\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur lors de la migration: " . $e->getMessage() . "\n";
    error_log("Erreur migration starting_equipment: " . $e->getMessage());
}
?>
