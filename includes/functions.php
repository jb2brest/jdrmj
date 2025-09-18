<?php
session_start();

// Fonction pour calculer le modificateur d'une caractéristique
function getAbilityModifier($score) {
    return floor(($score - 10) / 2);
}

// Fonction pour calculer le bonus de maîtrise selon le niveau
function getProficiencyBonus($level) {
    return floor(($level - 1) / 4) + 2;
}

// Fonction pour calculer les points de vie maximum selon la classe
function calculateMaxHP($level, $hitDie, $constitutionModifier) {
    // Extraire le nombre de faces du dé (ex: "1d12" -> 12)
    $diceFaces = (int) substr($hitDie, strpos($hitDie, 'd') + 1);
    
    $hp = $diceFaces + $constitutionModifier; // Premier niveau
    for ($i = 2; $i <= $level; $i++) {
        $hp += rand(1, $diceFaces) + $constitutionModifier;
    }
    return max(1, $hp); // Minimum 1 PV
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour vérifier le rôle de l'utilisateur
function getUserRole() {
    // Si le rôle est déjà en session, le retourner
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    
    // Sinon, le récupérer depuis la base de données
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['role'] = $user['role']; // Mettre en cache
            return $user['role'];
        }
    }
    
    return 'player'; // Par défaut
}

// Fonction pour vérifier si l'utilisateur est MJ
function isDM() {
    return getUserRole() === 'dm';
}

// Fonction pour vérifier si l'utilisateur est joueur
function isPlayer() {
    return getUserRole() === 'player';
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return getUserRole() === 'admin';
}

// Fonction pour vérifier si l'utilisateur est MJ ou admin
function isDMOrAdmin() {
    $role = getUserRole();
    return $role === 'dm' || $role === 'admin';
}

// Fonction pour vérifier si l'utilisateur a des privilèges élevés (MJ ou admin)
function hasElevatedPrivileges() {
    return isDMOrAdmin();
}

// Fonction pour rediriger si l'utilisateur n'est pas MJ
function requireDM() {
    requireLogin();
    if (!isDM()) {
        header('Location: profile.php?error=dm_required');
        exit();
    }
}

// Fonction pour rediriger si l'utilisateur n'est pas MJ ou admin
function requireDMOrAdmin() {
    requireLogin();
    if (!isDMOrAdmin()) {
        header('Location: profile.php?error=dm_or_admin_required');
        exit();
    }
}

// Fonction pour rediriger si l'utilisateur n'est pas admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: profile.php?error=admin_required');
        exit();
    }
}

// Fonction pour obtenir les informations complètes de l'utilisateur
function getUserInfo($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Fonction pour obtenir le niveau d'expérience en français
function getExperienceLevelLabel($level) {
    switch ($level) {
        case 'debutant':
            return 'Débutant';
        case 'intermediaire':
            return 'Intermédiaire';
        case 'expert':
            return 'Expert';
        default:
            return 'Débutant';
    }
}

// Fonction pour obtenir le label du rôle en français
function getRoleLabel($role) {
    switch ($role) {
        case 'player':
            return 'Joueur';
        case 'dm':
            return 'Maître du Jeu';
        case 'admin':
            return 'Administrateur';
        default:
            return 'Joueur';
    }
}

// Fonction pour obtenir la couleur du rôle (pour l'affichage)
function getRoleColor($role) {
    switch ($role) {
        case 'player':
            return 'primary';
        case 'dm':
            return 'success';
        case 'admin':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Fonction pour nettoyer les données d'entrée
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    // Ne pas encoder en HTML ici, cela sera fait lors de l'affichage
    return $data;
}

// Fonction pour afficher les messages d'erreur/succès
function displayMessage($message, $type = 'info') {
    $alertClass = '';
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        default:
            $alertClass = 'alert-info';
    }
    
    return "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Fonction pour obtenir les compétences D&D
function getSkills() {
    return [
        'Acrobaties' => 'Dextérité',
        'Arcanes' => 'Intelligence',
        'Athlétisme' => 'Force',
        'Discrétion' => 'Dextérité',
        'Dressage' => 'Sagesse',
        'Escamotage' => 'Dextérité',
        'Histoire' => 'Intelligence',
        'Intimidation' => 'Charisme',
        'Investigation' => 'Intelligence',
        'Médecine' => 'Sagesse',
        'Nature' => 'Intelligence',
        'Perception' => 'Sagesse',
        'Perspicacité' => 'Sagesse',
        'Persuasion' => 'Charisme',
        'Religion' => 'Intelligence',
        'Représentation' => 'Charisme',
        'Survie' => 'Sagesse',
        'Tromperie' => 'Charisme'
    ];
}

// Fonction pour obtenir les compétences d'armure
function getArmorProficiencies() {
    return [
        'Armure légère' => 'Armure',
        'Armure intermédiaire' => 'Armure',
        'Armure lourde' => 'Armure',
        'Bouclier' => 'Armure'
    ];
}

// Fonction pour obtenir les compétences d'armes
function getWeaponProficiencies() {
    return [
        'Armes courantes' => 'Arme',
        'Armes de guerre' => 'Arme',
        'Armes simples' => 'Arme',
        'Armes à distance' => 'Arme',
        'Armes de mêlée' => 'Arme',
        'Armes d\'hast' => 'Arme',
        'Armes de lancer' => 'Arme'
    ];
}

// Fonction pour obtenir les compétences d'outils
function getToolProficiencies() {
    return [
        'Outils d\'artisan' => 'Outil',
        'Instruments de musique' => 'Outil',
        'Jeux' => 'Outil',
        'Véhicules' => 'Outil',
        'Outils de voleur' => 'Outil',
        'Outils de forgeron' => 'Outil',
        'Outils de charpentier' => 'Outil',
        'Outils de cuisinier' => 'Outil',
        'Outils de tanneur' => 'Outil',
        'Outils de tisserand' => 'Outil',
        'Outils de verrier' => 'Outil',
        'Outils de potier' => 'Outil',
        'Outils de cordonnier' => 'Outil',
        'Outils de bijoutier' => 'Outil',
        'Outils de calligraphe' => 'Outil',
        'Outils de cartographe' => 'Outil',
        'Outils de navigateur' => 'Outil',
        'Outils de herboriste' => 'Outil',
        'Outils d\'alchimiste' => 'Outil',
        'Outils de mécanicien' => 'Outil'
    ];
}

// Fonction pour obtenir toutes les compétences (y compris armure, armes, outils)
function getAllSkills() {
    $skills = getSkills();
    $armor = getArmorProficiencies();
    $weapons = getWeaponProficiencies();
    $tools = getToolProficiencies();
    
    return array_merge($skills, $armor, $weapons, $tools);
}

// Fonction pour obtenir les compétences par catégorie
function getSkillsByCategory() {
    $allSkills = getAllSkills();
    $categories = [
        'Compétences' => [],
        'Armure' => [],
        'Arme' => [],
        'Outil' => []
    ];
    
    foreach ($allSkills as $skill => $category) {
        if (in_array($category, ['Force', 'Dextérité', 'Intelligence', 'Sagesse', 'Charisme'])) {
            $categories['Compétences'][$skill] = $category;
        } else {
            $categories[$category][] = $skill;
        }
    }
    
    return $categories;
}

// Fonction pour obtenir les compétences automatiques d'une classe
function getClassProficiencies($classId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT armor_proficiencies, weapon_proficiencies, tool_proficiencies 
            FROM classes 
            WHERE id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            return [
                'armor' => [],
                'weapon' => [],
                'tool' => []
            ];
        }
        
        return [
            'armor' => json_decode($class['armor_proficiencies'] ?? '[]', true) ?: [],
            'weapon' => json_decode($class['weapon_proficiencies'] ?? '[]', true) ?: [],
            'tool' => json_decode($class['tool_proficiencies'] ?? '[]', true) ?: []
        ];
    } catch (PDOException $e) {
        return [
            'armor' => [],
            'weapon' => [],
            'tool' => []
        ];
    }
}

// Fonction pour obtenir les compétences par catégorie avec les compétences automatiques de classe
function getSkillsByCategoryWithClass($classId = null) {
    $skillCategories = getSkillsByCategory();
    $classProficiencies = $classId ? getClassProficiencies($classId) : ['armor' => [], 'weapon' => [], 'tool' => []];
    
    return [
        'categories' => $skillCategories,
        'classProficiencies' => $classProficiencies
    ];
}

// Fonction pour obtenir les jets de sauvegarde
function getSavingThrows() {
    return [
        'Force' => 'strength',
        'Dextérité' => 'dexterity',
        'Constitution' => 'constitution',
        'Intelligence' => 'intelligence',
        'Sagesse' => 'wisdom',
        'Charisme' => 'charisma'
    ];
}

// Fonction pour calculer la classe d'armure
function calculateArmorClass($dexterityModifier, $armor = null) {
    $baseAC = 10 + $dexterityModifier;
    
    if ($armor) {
        // Logique pour différents types d'armure
        switch ($armor) {
            case 'armure de cuir':
                $baseAC = 11 + $dexterityModifier;
                break;
            case 'armure de cuir clouté':
                $baseAC = 12 + $dexterityModifier;
                break;
            case 'cotte de mailles':
                $baseAC = 16;
                $baseAC = min($baseAC, 16); // Max 16 avec Dextérité
                break;
            case 'armure de plates':
                $baseAC = 18;
                break;
        }
    }
    
    return $baseAC;
}

// Fonction pour calculer le niveau basé sur les points d'expérience
function calculateLevelFromExperience($experiencePoints) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT level 
            FROM experience_levels 
            WHERE experience_points_required <= ? 
            ORDER BY experience_points_required DESC 
            LIMIT 1
        ");
        $stmt->execute([$experiencePoints]);
        $result = $stmt->fetch();
        
        return $result ? $result['level'] : 1;
    } catch (PDOException $e) {
        // En cas d'erreur, retourner le niveau 1
        return 1;
    }
}

// Fonction pour calculer le bonus de maîtrise basé sur les points d'expérience
function calculateProficiencyBonusFromExperience($experiencePoints) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT proficiency_bonus 
            FROM experience_levels 
            WHERE experience_points_required <= ? 
            ORDER BY experience_points_required DESC 
            LIMIT 1
        ");
        $stmt->execute([$experiencePoints]);
        $result = $stmt->fetch();
        
        return $result ? $result['proficiency_bonus'] : 2;
    } catch (PDOException $e) {
        // En cas d'erreur, retourner le bonus de base
        return 2;
    }
}

// Fonction pour obtenir les points d'expérience requis pour le niveau suivant
function getExperienceRequiredForNextLevel($currentLevel) {
    global $pdo;
    
    try {
        $nextLevel = $currentLevel + 1;
        $stmt = $pdo->prepare("
            SELECT experience_points_required 
            FROM experience_levels 
            WHERE level = ?
        ");
        $stmt->execute([$nextLevel]);
        $result = $stmt->fetch();
        
        return $result ? $result['experience_points_required'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

// Fonction pour mettre à jour le niveau et le bonus de maîtrise d'un personnage
function updateCharacterLevelFromExperience($characterId) {
    global $pdo;
    
    try {
        // Récupérer les points d'expérience du personnage
        $stmt = $pdo->prepare("SELECT experience_points FROM characters WHERE id = ?");
        $stmt->execute([$characterId]);
        $character = $stmt->fetch();
        
        if (!$character) {
            return false;
        }
        
        $experiencePoints = $character['experience_points'];
        
        // Calculer le nouveau niveau et bonus de maîtrise
        $newLevel = calculateLevelFromExperience($experiencePoints);
        $newProficiencyBonus = calculateProficiencyBonusFromExperience($experiencePoints);
        
        // Mettre à jour le personnage
        $stmt = $pdo->prepare("
            UPDATE characters 
            SET level = ?, proficiency_bonus = ? 
            WHERE id = ?
        ");
        $stmt->execute([$newLevel, $newProficiencyBonus, $characterId]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fonction pour obtenir tous les historiques
function getAllBackgrounds() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM backgrounds ORDER BY name");
    return $stmt->fetchAll();
}

// Fonction pour obtenir un historique par ID
function getBackgroundById($backgroundId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
    $stmt->execute([$backgroundId]);
    return $stmt->fetch();
}

// Fonction pour obtenir les compétences d'un historique
function getBackgroundProficiencies($backgroundId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT skill_proficiencies, tool_proficiencies FROM backgrounds WHERE id = ?");
    $stmt->execute([$backgroundId]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return ['skills' => [], 'tools' => []];
    }
    
    return [
        'skills' => json_decode($result['skill_proficiencies'], true) ?? [],
        'tools' => json_decode($result['tool_proficiencies'], true) ?? []
    ];
}

// Fonction pour obtenir toutes les langues
function getAllLanguages() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM languages ORDER BY type, name");
    return $stmt->fetchAll();
}

// Fonction pour obtenir les langues par type
function getLanguagesByType() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM languages ORDER BY type, name");
    $languages = $stmt->fetchAll();
    
    $result = [
        'standard' => [],
        'exotique' => []
    ];
    
    foreach ($languages as $language) {
        $result[$language['type']][] = $language;
    }
    
    return $result;
}

// Fonction pour obtenir les langues d'un historique
function getBackgroundLanguages($backgroundId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT languages FROM backgrounds WHERE id = ?");
    $stmt->execute([$backgroundId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['languages']) {
        return [];
    }
    
    return json_decode($result['languages'], true) ?? [];
}

// Fonction pour parser l'équipement de départ d'une classe
function parseStartingEquipment($equipmentText) {
    if (!$equipmentText) {
        return [];
    }
    
    $equipmentChoices = [];
    $lines = explode("\n", trim($equipmentText));
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Vérifier si la ligne contient des choix (a), (b), (c)
        if (preg_match('/\([abc]\)/', $line)) {
            // C'est un choix d'équipement
            $choices = [];
            
            // Extraire tous les choix (a), (b), (c) avec leurs descriptions
            if (preg_match_all('/\(([abc])\)\s*([^(]+?)(?=\s*\([abc]\)|$)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $choice = $match[1];
                    $description = trim($match[2]);
                    
                    // Nettoyer la description (enlever les "ou" en fin)
                    $description = preg_replace('/\s+ou\s*$/', '', $description);
                    
                    $choices[$choice] = $description;
                }
            }
            
            if (!empty($choices)) {
                $equipmentChoices[] = $choices;
            }
        } else {
            // C'est un équipement fixe (sans choix)
            $equipmentChoices[] = ['fixed' => $line];
        }
    }
    
    return $equipmentChoices;
}

// Fonction pour obtenir l'équipement de départ d'une classe
function getClassStartingEquipment($classId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT starting_equipment FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['starting_equipment']) {
        return [];
    }
    
    return parseStartingEquipment($result['starting_equipment']);
}

// Fonction pour obtenir l'équipement d'un historique
function getBackgroundEquipment($backgroundId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT equipment FROM backgrounds WHERE id = ?");
    $stmt->execute([$backgroundId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['equipment']) {
        return '';
    }
    
    return $result['equipment'];
}

// Fonction pour vérifier si une classe peut lancer des sorts
function canCastSpells($classId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM class_evolution 
        WHERE class_id = ? AND (
            cantrips_known > 0 OR 
            spells_known > 0 OR 
            spell_slots_1st > 0 OR 
            spell_slots_2nd > 0 OR 
            spell_slots_3rd > 0 OR 
            spell_slots_4th > 0 OR 
            spell_slots_5th > 0 OR 
            spell_slots_6th > 0 OR 
            spell_slots_7th > 0 OR 
            spell_slots_8th > 0 OR 
            spell_slots_9th > 0
        )
    ");
    $stmt->execute([$classId]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Fonction pour obtenir les capacités de sorts d'une classe à un niveau donné
function getClassSpellCapabilities($classId, $level, $wisdomModifier = 0, $maxSpellsLearned = null, $intelligenceModifier = 0) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT cantrips_known, spells_known, 
               spell_slots_1st, spell_slots_2nd, spell_slots_3rd, 
               spell_slots_4th, spell_slots_5th, spell_slots_6th, 
               spell_slots_7th, spell_slots_8th, spell_slots_9th
        FROM class_evolution 
        WHERE class_id = ? AND level = ?
    ");
    $stmt->execute([$classId, $level]);
    $capabilities = $stmt->fetch();
    
    if ($capabilities) {
        // Récupérer le nom de la classe
        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        // Sorts appris : utiliser le champ personnalisé ou la valeur par défaut
        $spellsLearned = $maxSpellsLearned !== null ? $maxSpellsLearned : $capabilities['spells_known'];
        
        // Calculer les sorts préparés selon la classe
        $spellsPrepared = $capabilities['spells_known']; // Valeur par défaut
        
        if ($class) {
            $className = strtolower($class['name']);
            
            // Pour les clercs, les sorts préparés = niveau + modificateur de Sagesse
            if (strpos($className, 'clerc') !== false) {
                $spellsPrepared = $level + $wisdomModifier;
            }
            // Pour les druides, les sorts préparés = niveau + modificateur de Sagesse (comme le Clerc)
            elseif (strpos($className, 'druide') !== false) {
                $spellsPrepared = $level + $wisdomModifier;
            }
            // Pour les mages, les sorts préparés = niveau + modificateur d'Intelligence
            elseif (strpos($className, 'magicien') !== false) {
                $spellsPrepared = $level + $intelligenceModifier;
            }
            // Pour les ensorceleurs, les sorts préparés = nombre de sorts appris (ils sont automatiquement préparés)
            elseif (strpos($className, 'ensorceleur') !== false) {
                $spellsPrepared = $spellsLearned; // Tous les sorts appris sont automatiquement préparés
            }
            // Pour les bardes, les sorts préparés = nombre de sorts appris (ils sont automatiquement préparés)
            elseif (strpos($className, 'barde') !== false) {
                $spellsPrepared = $spellsLearned; // Tous les sorts appris sont automatiquement préparés
            }
        }
        
        // Ajouter les deux valeurs au tableau de retour
        $capabilities['spells_learned'] = $spellsLearned;
        $capabilities['spells_prepared'] = $spellsPrepared;
    }
    
    return $capabilities;
}

// Fonction pour obtenir les sorts disponibles pour une classe
function getSpellsForClass($classId) {
    global $pdo;
    
    // Récupérer le nom de la classe
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
    
    if (!$class) {
        return [];
    }
    
    $className = $class['name'];
    
    // Rechercher les sorts qui contiennent le nom de la classe
    $stmt = $pdo->prepare("
        SELECT * FROM spells 
        WHERE classes LIKE ?
        ORDER BY level, name
    ");
    
    $stmt->execute(["%$className%"]);
    return $stmt->fetchAll();
}

// Fonction pour obtenir les sorts d'un personnage
function getCharacterSpells($characterId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, cs.prepared 
        FROM character_spells cs
        JOIN spells s ON cs.spell_id = s.id
        WHERE cs.character_id = ?
        ORDER BY s.level, s.name
    ");
    $stmt->execute([$characterId]);
    return $stmt->fetchAll();
}

// Fonction pour ajouter un sort à un personnage
function addSpellToCharacter($characterId, $spellId, $prepared = false) {
    global $pdo;
    try {
        // Récupérer la classe du personnage pour déterminer si c'est un barde
        $stmt = $pdo->prepare("
            SELECT c.class_id, cl.name as class_name 
            FROM characters c 
            JOIN classes cl ON c.class_id = cl.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$characterId]);
        $character = $stmt->fetch();
        
        // Pour les bardes, tous les sorts sont automatiquement préparés
        if ($character && strpos(strtolower($character['class_name']), 'barde') !== false) {
            $prepared = true;
        }
        
        // S'assurer que $prepared est un entier (0 ou 1)
        $prepared = $prepared ? 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO character_spells (character_id, spell_id, prepared) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE prepared = ?
        ");
        $stmt->execute([$characterId, $spellId, $prepared, $prepared]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur addSpellToCharacter: " . $e->getMessage());
        return false;
    }
}

// Fonction pour retirer un sort d'un personnage
function removeSpellFromCharacter($characterId, $spellId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            DELETE FROM character_spells 
            WHERE character_id = ? AND spell_id = ?
        ");
        $stmt->execute([$characterId, $spellId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fonction pour mettre à jour l'état préparé d'un sort
function updateSpellPrepared($characterId, $spellId, $prepared) {
    global $pdo;
    try {
        // Récupérer la classe du personnage pour déterminer si c'est un barde
        $stmt = $pdo->prepare("
            SELECT c.class_id, cl.name as class_name 
            FROM characters c 
            JOIN classes cl ON c.class_id = cl.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$characterId]);
        $character = $stmt->fetch();
        
        // Pour les bardes, les sorts ne peuvent pas être dépréparés
        if ($character && strpos(strtolower($character['class_name']), 'barde') !== false && !$prepared) {
            return false; // Empêcher la dépréparation pour les bardes
        }
        
        // S'assurer que $prepared est un entier (0 ou 1)
        $prepared = $prepared ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE character_spells 
            SET prepared = ? 
            WHERE character_id = ? AND spell_id = ?
        ");
        $stmt->execute([$prepared, $characterId, $spellId]);
        
        // Vérifier si une ligne a été mise à jour
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erreur updateSpellPrepared: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les utilisations d'emplacements de sorts
function getSpellSlotsUsage($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT level_1_used, level_2_used, level_3_used, level_4_used, level_5_used,
                   level_6_used, level_7_used, level_8_used, level_9_used
            FROM spell_slots_usage 
            WHERE character_id = ?
        ");
        $stmt->execute([$characterId]);
        $usage = $stmt->fetch();
        
        if (!$usage) {
            // Créer un enregistrement vide si il n'existe pas
            $stmt = $pdo->prepare("
                INSERT INTO spell_slots_usage (character_id) VALUES (?)
            ");
            $stmt->execute([$characterId]);
            
            return [
                'level_1_used' => 0, 'level_2_used' => 0, 'level_3_used' => 0,
                'level_4_used' => 0, 'level_5_used' => 0, 'level_6_used' => 0,
                'level_7_used' => 0, 'level_8_used' => 0, 'level_9_used' => 0
            ];
        }
        
        return $usage;
    } catch (PDOException $e) {
        error_log("Erreur getSpellSlotsUsage: " . $e->getMessage());
        return [
            'level_1_used' => 0, 'level_2_used' => 0, 'level_3_used' => 0,
            'level_4_used' => 0, 'level_5_used' => 0, 'level_6_used' => 0,
            'level_7_used' => 0, 'level_8_used' => 0, 'level_9_used' => 0
        ];
    }
}


// Fonction pour parser l'équipement d'historique et extraire les pièces d'or
function parseBackgroundEquipment($equipmentText) {
    if (!$equipmentText) {
        return ['items' => [], 'gold' => 0];
    }
    
    $items = [];
    $gold = 0;
    
    // Diviser le texte par virgules et points
    $parts = preg_split('/[,.]/', $equipmentText);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        // Chercher les mentions de bourse avec des pièces d'or
        if (preg_match('/bourse.*?(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Remplacer la mention de la bourse par juste "une bourse"
            $part = preg_replace('/bourse.*?(\d+)\s*po/i', 'une bourse', $part);
        }
        
        // Chercher d'autres mentions de pièces d'or
        if (preg_match('/(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Supprimer la mention des pièces d'or
            $part = preg_replace('/\d+\s*po/i', '', $part);
        }
        
        // Nettoyer le texte
        $part = trim($part);
        if (!empty($part)) {
            $items[] = $part;
        }
    }
    
    return ['items' => $items, 'gold' => $gold];
}

// Fonction améliorée pour parser l'équipement d'historique et extraire les pièces d'or
function parseBackgroundEquipmentImproved($equipmentText) {
    if (!$equipmentText) {
        return ['items' => [], 'gold' => 0];
    }
    
    $items = [];
    $gold = 0;
    
    // Diviser le texte par virgules et points
    $parts = preg_split('/[,.]/', $equipmentText);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        // Chercher les mentions de bourse avec des pièces d'or
        if (preg_match('/bourse.*?(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Remplacer la mention de la bourse par juste "une bourse"
            $part = preg_replace('/bourse.*?(\d+)\s*po/i', 'une bourse', $part);
        }
        
        // Chercher d'autres mentions de pièces d'or
        if (preg_match('/(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Supprimer la mention des pièces d'or
            $part = preg_replace('/\d+\s*po/i', '', $part);
        }
        
        // Nettoyer le texte
        $part = trim($part);
        if (!empty($part)) {
            $items[] = $part;
        }
    }
    
    return ['items' => $items, 'gold' => $gold];
}

// Fonction pour parser l'équipement d'historique et extraire les pièces d'or (version corrigée)
function parseBackgroundEquipmentFixed($equipmentText) {
    if (!$equipmentText) {
        return ['items' => [], 'gold' => 0];
    }
    
    $items = [];
    $gold = 0;
    
    // Diviser le texte par virgules et points
    $parts = preg_split('/[,.]/', $equipmentText);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        // Chercher les mentions de bourse avec des pièces d'or
        if (preg_match('/bourse.*?(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Remplacer la mention de la bourse par juste "une bourse"
            $part = preg_replace('/bourse.*?(\d+)\s*po/i', 'une bourse', $part);
        }
        
        // Chercher d'autres mentions de pièces d'or
        if (preg_match('/(\d+)\s*po/i', $part, $matches)) {
            $gold += (int)$matches[1];
            // Supprimer la mention des pièces d'or
            $part = preg_replace('/\d+\s*po/i', '', $part);
        }
        
        // Nettoyer le texte
        $part = trim($part);
        if (!empty($part)) {
            $items[] = $part;
        }
    }
    
    return ['items' => $items, 'gold' => $gold];
}

// Fonction pour parser l'équipement d'historique et extraire les pièces d'or (version simple)
function parseBackgroundEquipmentSimple($equipmentText) {
    if (!$equipmentText) {
        return ['items' => [], 'gold' => 0];
    }
    
    $items = [];
    $gold = 0;
    
    // Chercher toutes les mentions de pièces d'or
    if (preg_match_all('/(\d+)\s*po/i', $equipmentText, $matches)) {
        foreach ($matches[1] as $amount) {
            $gold += (int)$amount;
        }
    }
    
    // Supprimer toutes les mentions de pièces d'or du texte
    $cleanText = preg_replace('/\d+\s*po/i', '', $equipmentText);
    
    // Nettoyer les mentions de bourse
    $cleanText = preg_replace('/bourse\s+contenant/i', 'bourse', $cleanText);
    
    // Diviser le texte par virgules et points
    $parts = preg_split('/[,.]/', $cleanText);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (!empty($part)) {
            $items[] = $part;
        }
    }
    
    return ['items' => $items, 'gold' => $gold];
}

// Fonction pour générer l'équipement final basé sur les choix du joueur
function generateFinalEquipment($classId, $equipmentChoices, $backgroundId = null) {
    $startingEquipment = getClassStartingEquipment($classId);
    $finalEquipment = [];
    $backgroundGold = 0;
    
    foreach ($startingEquipment as $index => $item) {
        if (isset($item['fixed'])) {
            // Équipement fixe
            $finalEquipment[] = $item['fixed'];
        } else {
            // Choix d'équipement
            if (isset($equipmentChoices[$index]) && isset($item[$equipmentChoices[$index]])) {
                $finalEquipment[] = $item[$equipmentChoices[$index]];
            } else {
                // Si aucun choix n'a été fait, prendre le premier choix par défaut
                $firstChoice = array_keys($item)[0];
                $finalEquipment[] = $item[$firstChoice];
            }
        }
    }
    
    // Ajouter l'équipement de l'historique (parsé)
    if ($backgroundId) {
        $backgroundEquipment = getBackgroundEquipment($backgroundId);
        if ($backgroundEquipment) {
            $parsed = parseBackgroundEquipmentSimple($backgroundEquipment);
            $finalEquipment = array_merge($finalEquipment, $parsed['items']);
            $backgroundGold = $parsed['gold'];
        }
    }
    
    return [
        'equipment' => implode("\n", $finalEquipment),
        'gold' => $backgroundGold
    ];
}

// Fonction pour détecter les armes dans l'équipement d'un personnage
function detectWeaponsInEquipment($equipmentText) {
    global $pdo;
    
    $weapons = [];
    $stmt = $pdo->query("SELECT name, hands, type, damage, properties FROM weapons");
    $allWeapons = $stmt->fetchAll();
    
    foreach ($allWeapons as $weapon) {
        // Rechercher l'arme dans le texte d'équipement (insensible à la casse)
        $weaponName = mb_strtolower($weapon['name'], 'UTF-8');
        $equipmentLower = mb_strtolower($equipmentText, 'UTF-8');
        
        // Vérifier différentes variations du nom
        $patterns = [
            $weaponName, // Nom exact
            $weaponName . 's', // Pluriel simple
            $weaponName . 'es', // Pluriel en -es
            'une ' . $weaponName, // Avec article "une"
            'un ' . $weaponName, // Avec article "un"
            'deux ' . $weaponName . 's', // Avec nombre et pluriel
            'trois ' . $weaponName . 's', // Avec nombre et pluriel
            'quatre ' . $weaponName . 's', // Avec nombre et pluriel
            'cinq ' . $weaponName . 's', // Avec nombre et pluriel
        ];
        
        $found = false;
        foreach ($patterns as $pattern) {
            if (stripos($equipmentText, $pattern) !== false) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            $weapons[] = [
                'name' => $weapon['name'],
                'hands' => $weapon['hands'],
                'type' => $weapon['type'],
                'damage' => $weapon['damage'],
                'properties' => $weapon['properties']
            ];
        }
    }
    
    return $weapons;
}

// Fonction pour détecter les armures dans l'équipement d'un personnage
function detectArmorInEquipment($equipmentText) {
    global $pdo;
    
    $armor = [];
    $stmt = $pdo->query("SELECT name, ac_formula, type FROM armor WHERE type != 'Bouclier'");
    $allArmor = $stmt->fetchAll();
    
    foreach ($allArmor as $armorItem) {
        // Rechercher l'armure dans le texte d'équipement (insensible à la casse)
        // Gérer les variations comme "armure d'écailles" vs "Écailles"
        $armorName = mb_strtolower($armorItem['name'], 'UTF-8');
        $equipmentLower = mb_strtolower($equipmentText, 'UTF-8');
        
        if (stripos($equipmentText, $armorItem['name']) !== false || 
            stripos($equipmentText, "armure d'" . $armorName) !== false ||
            stripos($equipmentText, "armure de " . $armorName) !== false ||
            stripos($equipmentText, "armure d'" . $armorItem['name']) !== false ||
            stripos($equipmentText, "armure de " . $armorItem['name']) !== false) {
            $armor[] = [
                'name' => $armorItem['name'],
                'ac_formula' => $armorItem['ac_formula'],
                'type' => $armorItem['type']
            ];
        }
    }
    
    return $armor;
}

// Fonction pour détecter les boucliers dans l'équipement d'un personnage
function detectShieldsInEquipment($equipmentText) {
    global $pdo;
    
    $shields = [];
    $stmt = $pdo->query("SELECT name, ac_formula FROM armor WHERE type = 'Bouclier'");
    $allShields = $stmt->fetchAll();
    
    foreach ($allShields as $shield) {
        // Rechercher le bouclier dans le texte d'équipement (insensible à la casse)
        if (stripos($equipmentText, $shield['name']) !== false) {
            // Extraire le bonus de CA de la formule
            $acBonus = 2; // Par défaut
            if (preg_match('/(\d+)/', $shield['ac_formula'], $matches)) {
                $acBonus = (int)$matches[1];
            }
            
            $shields[] = [
                'name' => $shield['name'],
                'ac_bonus' => $acBonus
            ];
        }
    }
    
    return $shields;
}

// Fonction pour calculer la classe d'armure d'un personnage (version étendue)
function calculateArmorClassExtended($character, $equippedArmor = null, $equippedShield = null) {
    // Utiliser le modificateur de Dextérité déjà calculé dans la zone "Caractéristiques"
    $dexterityModifier = $character['dexterity_modifier'];
    
    // Récupérer le nom de la classe pour vérifier si c'est un barbare
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
    $stmt->execute([$character['class_id']]);
    $class = $stmt->fetch();
    $isBarbarian = $class && strpos(strtolower($class['name']), 'barbare') !== false;
    
    if ($equippedArmor) {
        // CA basée sur l'armure équipée
        $acFormula = $equippedArmor['ac_formula'];
        
        // Parser la formule de CA
        if (preg_match('/(\d+)\s*\+\s*Mod\.Dex(?: \(max \+(\d+)\))?/', $acFormula, $matches)) {
            $baseAC = (int)$matches[1];
            $maxDexBonus = isset($matches[2]) ? (int)$matches[2] : null;
            
            $dexBonus = $dexterityModifier;
            if ($maxDexBonus !== null) {
                $dexBonus = min($dexBonus, $maxDexBonus);
            }
            
            $ac = $baseAC + $dexBonus;
        } else {
            // CA fixe (armures lourdes)
            $ac = (int)$acFormula;
        }
    } else {
        // Pas d'armure
        if ($isBarbarian) {
            // Pour les barbares sans armure : CA = 10 + modificateur de Dextérité + modificateur de Constitution
            $constitutionModifier = getAbilityModifier($character['constitution'] + $character['constitution_bonus']);
            $ac = 10 + $dexterityModifier + $constitutionModifier;
        } else {
            // Pour les autres classes : CA = 10 + modificateur de Dextérité
            $ac = 10 + $dexterityModifier;
        }
    }
    
    // Ajouter le bonus de bouclier si équipé
    if ($equippedShield) {
        $ac += $equippedShield['ac_bonus'];
    }
    
    return $ac;
}

// Fonction pour obtenir l'équipement équipé d'un personnage
function getCharacterEquippedItems($characterId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT ce.item_name, ce.item_type, ce.equipped_slot, w.hands
        FROM character_equipment ce
        LEFT JOIN weapons w ON ce.item_name = w.name AND ce.item_type = 'weapon'
        WHERE ce.character_id = ? AND ce.is_equipped = 1
    ");
    $stmt->execute([$characterId]);
    
    $equipped = [
        'main_hand' => null,
        'off_hand' => null,
        'armor' => null,
        'shield' => null
    ];
    
    while ($row = $stmt->fetch()) {
        $equipped[$row['equipped_slot']] = $row['item_name'];
        
        // Si c'est une arme à deux mains équipée dans main_hand, l'ajouter aussi dans off_hand
        if ($row['item_type'] === 'weapon' && $row['hands'] == 2 && $row['equipped_slot'] === 'main_hand') {
            $equipped['off_hand'] = $row['item_name'];
        }
        
        // Si c'est un bouclier équipé dans off_hand, l'ajouter aussi dans shield
        if ($row['item_type'] === 'shield' && $row['equipped_slot'] === 'off_hand') {
            $equipped['shield'] = $row['item_name'];
        }
    }
    
    return $equipped;
}

// Fonction pour équiper un objet
function equipItem($characterId, $itemName, $itemType, $slot) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Déséquiper l'objet actuellement dans ce slot
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET is_equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND equipped_slot = ?
        ");
        $stmt->execute([$characterId, $slot]);
        
        // Équiper le nouvel objet
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET is_equipped = 1, equipped_slot = ? 
            WHERE character_id = ? AND item_name = ? AND item_type = ?
        ");
        $stmt->execute([$slot, $characterId, $itemName, $itemType]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Fonction pour déséquiper un objet
function unequipItem($characterId, $itemName) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE character_equipment 
        SET is_equipped = 0, equipped_slot = NULL 
        WHERE character_id = ? AND item_name = ?
    ");
    return $stmt->execute([$characterId, $itemName]);
}

// Fonction pour synchroniser l'équipement de base vers character_equipment
function syncBaseEquipmentToCharacterEquipment($characterId) {
    global $pdo;
    
    try {
        // Récupérer le personnage
        $stmt = $pdo->prepare("SELECT equipment FROM characters WHERE id = ?");
        $stmt->execute([$characterId]);
        $character = $stmt->fetch();
        
        if (!$character || empty($character['equipment'])) {
            return false;
        }
        
        // Détecter les armes, armures et boucliers
        $detectedWeapons = detectWeaponsInEquipment($character['equipment']);
        $detectedArmor = detectArmorInEquipment($character['equipment']);
        $detectedShields = detectShieldsInEquipment($character['equipment']);
        
        $pdo->beginTransaction();
        
        // Ajouter les armes
        foreach ($detectedWeapons as $weapon) {
            // Vérifier si l'arme existe déjà
            $stmt = $pdo->prepare("SELECT id FROM character_equipment WHERE character_id = ? AND item_name = ? AND item_type = 'weapon'");
            $stmt->execute([$characterId, $weapon['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter l'arme
                $stmt = $pdo->prepare("
                    INSERT INTO character_equipment (character_id, item_name, item_type, item_description, item_source, quantity, is_equipped, equipped_slot, obtained_from) 
                    VALUES (?, ?, 'weapon', ?, 'Équipement de base', 1, 0, NULL, 'Équipement de base')
                ");
                $description = "Arme: {$weapon['type']}, {$weapon['hands']} main(s), Dégâts: {$weapon['damage']}";
                if (!empty($weapon['properties'])) {
                    $description .= ", Propriétés: {$weapon['properties']}";
                }
                $stmt->execute([$characterId, $weapon['name'], $description]);
            }
        }
        
        // Ajouter les armures
        foreach ($detectedArmor as $armor) {
            // Vérifier si l'armure existe déjà
            $stmt = $pdo->prepare("SELECT id FROM character_equipment WHERE character_id = ? AND item_name = ? AND item_type = 'armor'");
            $stmt->execute([$characterId, $armor['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter l'armure
                $stmt = $pdo->prepare("
                    INSERT INTO character_equipment (character_id, item_name, item_type, item_description, item_source, quantity, is_equipped, equipped_slot, obtained_from) 
                    VALUES (?, ?, 'armor', ?, 'Équipement de base', 1, 0, NULL, 'Équipement de base')
                ");
                $description = "Armure: {$armor['type']}, CA: {$armor['ac_formula']}";
                $stmt->execute([$characterId, $armor['name'], $description]);
            }
        }
        
        // Ajouter les boucliers
        foreach ($detectedShields as $shield) {
            // Vérifier si le bouclier existe déjà
            $stmt = $pdo->prepare("SELECT id FROM character_equipment WHERE character_id = ? AND item_name = ? AND item_type = 'shield'");
            $stmt->execute([$characterId, $shield['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter le bouclier
                $stmt = $pdo->prepare("
                    INSERT INTO character_equipment (character_id, item_name, item_type, item_description, item_source, quantity, is_equipped, equipped_slot, obtained_from) 
                    VALUES (?, ?, 'shield', ?, 'Équipement de base', 1, 0, NULL, 'Équipement de base')
                ");
                $description = "Bouclier, Bonus CA: +{$shield['ac_bonus']}";
                $stmt->execute([$characterId, $shield['name'], $description]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la synchronisation de l'équipement: " . $e->getMessage());
        return false;
    }
}

// Fonction pour utiliser un emplacement de sort
function useSpellSlot($characterId, $level) {
    global $pdo;
    
    // Vérifier qu'il reste des emplacements disponibles
    $usage = getSpellSlotsUsage($characterId);
    $key = "level_{$level}_used";
    $used = isset($usage[$key]) ? $usage[$key] : 0;
    
    // Récupérer les capacités du personnage pour connaître le maximum
    $stmt = $pdo->prepare("SELECT class_id, level FROM characters WHERE id = ?");
    $stmt->execute([$characterId]);
    $character = $stmt->fetch();
    
    if (!$character) return false;
    
    // Récupérer les bonus raciaux
    $stmt = $pdo->prepare("SELECT c.wisdom, r.wisdom_bonus FROM characters c JOIN races r ON c.race_id = r.id WHERE c.id = ?");
    $stmt->execute([$characterId]);
    $race = $stmt->fetch();
    $wisdomModifier = $race ? floor(($race['wisdom'] + $race['wisdom_bonus'] - 10) / 2) : 0;
    
    $capabilities = getClassSpellCapabilities($character['class_id'], $character['level'], $wisdomModifier);
    $suffix = $level == 1 ? 'st' : ($level == 2 ? 'nd' : ($level == 3 ? 'rd' : 'th'));
    $maxKey = "spell_slots_{$level}{$suffix}";
    $maxSlots = isset($capabilities[$maxKey]) ? $capabilities[$maxKey] : 0;
    
    if ($used >= $maxSlots) {
        return false; // Pas d'emplacements disponibles
    }
    
    // Incrémenter le compteur
    $stmt = $pdo->prepare("
        INSERT INTO spell_slots_usage (character_id, level_1_used, level_2_used, level_3_used, level_4_used, level_5_used, level_6_used, level_7_used, level_8_used, level_9_used)
        VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0, 0)
        ON DUPLICATE KEY UPDATE 
        level_{$level}_used = level_{$level}_used + 1
    ");
    return $stmt->execute([$characterId]);
}

// Fonction pour libérer un emplacement de sort
function freeSpellSlot($characterId, $level) {
    global $pdo;
    
    // Vérifier qu'il y a des emplacements utilisés
    $usage = getSpellSlotsUsage($characterId);
    $key = "level_{$level}_used";
    $used = isset($usage[$key]) ? $usage[$key] : 0;
    
    if ($used <= 0) {
        return false; // Aucun emplacement utilisé à libérer
    }
    
    // Décrémenter le compteur
    $stmt = $pdo->prepare("
        UPDATE spell_slots_usage 
        SET level_{$level}_used = GREATEST(level_{$level}_used - 1, 0)
        WHERE character_id = ?
    ");
    return $stmt->execute([$characterId]);
}

// Fonction pour remettre à zéro tous les emplacements de sorts utilisés (long repos)
function resetSpellSlotsUsage($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO spell_slots_usage (character_id, level_1_used, level_2_used, level_3_used, 
                                         level_4_used, level_5_used, level_6_used, level_7_used, 
                                         level_8_used, level_9_used) 
            VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0, 0)
            ON DUPLICATE KEY UPDATE 
            level_1_used = 0, level_2_used = 0, level_3_used = 0,
            level_4_used = 0, level_5_used = 0, level_6_used = 0,
            level_7_used = 0, level_8_used = 0, level_9_used = 0
        ");
        $stmt->execute([$characterId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur resetSpellSlotsUsage: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir l'utilisation des rages d'un personnage
function getRageUsage($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT rages_used 
            FROM rage_usage 
            WHERE character_id = ?
        ");
        $stmt->execute([$characterId]);
        $usage = $stmt->fetch();
        
        if (!$usage) {
            // Créer un enregistrement vide si il n'existe pas
            $stmt = $pdo->prepare("
                INSERT INTO rage_usage (character_id, rages_used) VALUES (?, 0)
            ");
            $stmt->execute([$characterId]);
            return 0;
        }
        
        return $usage['rages_used'];
    } catch (PDOException $e) {
        error_log("Erreur getRageUsage: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour utiliser une rage
function useRage($characterId) {
    global $pdo;
    
    // Vérifier qu'il reste des rages disponibles
    $usage = getRageUsage($characterId);
    
    // Récupérer les capacités du personnage pour connaître le maximum
    $stmt = $pdo->prepare("SELECT class_id, level FROM characters WHERE id = ?");
    $stmt->execute([$characterId]);
    $character = $stmt->fetch();
    
    if (!$character) return false;
    
    // Récupérer le nombre maximum de rages pour ce niveau
    $stmt = $pdo->prepare("SELECT rages FROM class_evolution WHERE class_id = ? AND level = ?");
    $stmt->execute([$character['class_id'], $character['level']]);
    $evolution = $stmt->fetch();
    
    if (!$evolution) return false;
    
    $maxRages = $evolution['rages'];
    
    if ($usage >= $maxRages) {
        return false; // Pas de rages disponibles
    }
    
    // Incrémenter le compteur
    $stmt = $pdo->prepare("
        INSERT INTO rage_usage (character_id, rages_used) 
        VALUES (?, 1)
        ON DUPLICATE KEY UPDATE 
        rages_used = rages_used + 1
    ");
    return $stmt->execute([$characterId]);
}

// Fonction pour libérer une rage (annuler une utilisation)
function freeRage($characterId) {
    global $pdo;
    
    // Vérifier qu'il y a des rages utilisées
    $usage = getRageUsage($characterId);
    
    if ($usage <= 0) {
        return false; // Aucune rage utilisée à libérer
    }
    
    // Décrémenter le compteur
    $stmt = $pdo->prepare("
        UPDATE rage_usage 
        SET rages_used = GREATEST(rages_used - 1, 0)
        WHERE character_id = ?
    ");
    return $stmt->execute([$characterId]);
}

// Fonction pour réinitialiser toutes les rages (long repos)
function resetRageUsage($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rage_usage (character_id, rages_used) 
            VALUES (?, 0)
            ON DUPLICATE KEY UPDATE 
            rages_used = 0
        ");
        $stmt->execute([$characterId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur resetRageUsage: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir les capacités des barbares selon leur niveau
function getBarbarianCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Rage
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Rage',
            'description' => 'En combat, vous pouvez entrer dans un état de rage. Pendant votre rage, vous obtenez les avantages suivants si vous ne portez pas d\'armure lourde : +2 aux dégâts de mêlée avec les armes de Force, résistance aux dégâts contondants, perforants et tranchants, et avantage aux jets de sauvegarde de Force.'
        ];
    }
    
    // Niveau 1 - Défense sans armure
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Défense sans armure',
            'description' => 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + votre modificateur de Dextérité + votre modificateur de Constitution.'
        ];
    }
    
    // Niveau 2 - Danger sense
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Sens du danger',
            'description' => 'Vous avez un avantage aux jets de sauvegarde de Dextérité contre les effets que vous pouvez voir, comme les pièges et les sorts. Pour bénéficier de cet avantage, vous ne devez pas être aveuglé, assourdi ou neutralisé.'
        ];
    }
    
    // Niveau 2 - Attaque supplémentaire
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer deux fois, au lieu d\'une, chaque fois que vous effectuez l\'action Attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 3 - Voie primitive
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Voie primitive',
            'description' => 'Vous choisissez une voie primitive qui reflète la nature de votre rage. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 6, 10 et 14.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Mouvement rapide
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Mouvement rapide',
            'description' => 'Votre vitesse augmente de 3 mètres tant que vous ne portez pas d\'armure lourde.'
        ];
    }
    
    // Niveau 7 - Instinct sauvage
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Instinct sauvage',
            'description' => 'Vous avez un avantage aux jets d\'initiative. De plus, si vous êtes surpris au début du combat et que vous n\'êtes pas encore incapable d\'agir, vous pouvez agir normalement lors de votre premier tour.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Critique brutal
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Critique brutal',
            'description' => 'Vous pouvez relancer un dé de dégâts d\'arme de mêlée une fois par tour quand vous obtenez un coup critique avec une attaque de mêlée.'
        ];
    }
    
    // Niveau 11 - Rage implacable
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Rage implacable',
            'description' => 'Votre rage peut vous maintenir en vie malgré des blessures mortelles. Si vous tombez à 0 points de vie pendant votre rage, vous pouvez faire un jet de sauvegarde de Constitution. Si vous réussissez, vous tombez à 1 point de vie à la place.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 15 - Rage persistante
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Rage persistante',
            'description' => 'Votre rage est si féroce qu\'elle se termine prématurément seulement si vous tombez inconscient ou si vous choisissez de la terminer.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 18 - Indomptable
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Indomptable',
            'description' => 'Vous avez un avantage aux jets de sauvegarde de Force. De plus, quand vous faites un jet de sauvegarde de Force, vous pouvez utiliser votre réaction pour relancer le dé si vous échouez. Vous devez utiliser le nouveau résultat.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Champion primitif
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Champion primitif',
            'description' => 'Vous incarnez la puissance de la nature sauvage. Votre Force et votre Constitution augmentent de 4. Votre maximum pour ces caractéristiques est maintenant 24.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de barde selon le niveau
function getBardCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Inspiration bardique
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Inspiration bardique',
            'description' => 'Vous pouvez inspirer les autres par vos mots ou votre musique. Pour ce faire, vous utilisez une action bonus lors de votre tour pour choisir une créature autre que vous dans un rayon de 18 mètres qui peut vous entendre. Cette créature gagne un dé d\'inspiration bardique, un d6.'
        ];
    }
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie de la même manière que les clercs. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de barde.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armes',
            'description' => 'Vous maîtrisez les armes courantes, les arbalètes de poing, les épées longues, les rapières, les épées courtes et les armes à distance simples.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armures
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures',
            'description' => 'Vous maîtrisez les armures légères.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez trois instruments de musique de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez trois compétences parmi : Acrobaties, Arcanes, Athlétisme, Bluff, Histoire, Intimidation, Investigation, Médecine, Nature, Perception, Perspicacité, Religion, Représentation, Supercherie et Survie.'
        ];
    }
    
    // Niveau 2 - Chanson de repos
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Chanson de repos',
            'description' => 'Vous pouvez utiliser la musique ou la poésie pour aider vos alliés à récupérer. Si vous ou vos alliés qui peuvent entendre votre performance passez un repos court, chacun récupère des points de vie supplémentaires égaux à votre modificateur de Charisme (minimum 1).'
        ];
    }
    
    // Niveau 2 - Jack de tous les métiers
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Jack de tous les métiers',
            'description' => 'Vous pouvez ajouter la moitié de votre bonus de maîtrise, arrondi au supérieur, à tout test de caractéristique que vous effectuez et qui n\'inclut déjà pas votre bonus de maîtrise.'
        ];
    }
    
    // Niveau 3 - Collège bardique
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Collège bardique',
            'description' => 'Vous approfondissez votre formation dans un collège bardique de votre choix : le Collège du Savoir, le Collège de la Gloire, le Collège des Swords, le Collège de la Valor, le Collège des Whispers, ou le Collège des Glamour.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Inspiration bardique améliorée
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Inspiration bardique améliorée',
            'description' => 'Votre dé d\'inspiration bardique devient un d8. Au niveau 10, il devient un d10, et au niveau 15, il devient un d12.'
        ];
    }
    
    // Niveau 6 - Contre-charme
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Contre-charme',
            'description' => 'Vous gagnez la capacité d\'utiliser des mots de pouvoir pour perturber les effets magiques qui influencent l\'esprit. En tant qu\'action, vous pouvez commencer une performance qui dure jusqu\'à la fin de votre prochain tour. Pendant cette performance, vous et tous les alliés amicaux dans un rayon de 9 mètres avez un avantage aux jets de sauvegarde contre être charmés ou terrorisés.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 10 - Expertise magique
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Expertise magique',
            'description' => 'Vous pouvez choisir deux sorts de n\'importe quelle classe. Un sort que vous choisissez doit être d\'un niveau que vous pouvez lancer, comme indiqué dans le tableau du barde. Les sorts choisis comptent comme des sorts de barde pour vous, mais ils ne comptent pas dans le nombre de sorts de barde que vous connaissez.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 14 - Expertise magique améliorée
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Expertise magique améliorée',
            'description' => 'Vous pouvez choisir deux sorts de n\'importe quelle classe. Un sort que vous choisissez doit être d\'un niveau que vous pouvez lancer, comme indiqué dans le tableau du barde. Les sorts choisis comptent comme des sorts de barde pour vous, mais ils ne comptent pas dans le nombre de sorts de barde que vous connaissez.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 18 - Expertise magique supérieure
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Expertise magique supérieure',
            'description' => 'Vous pouvez choisir deux sorts de n\'importe quelle classe. Un sort que vous choisissez doit être d\'un niveau que vous pouvez lancer, comme indiqué dans le tableau du barde. Les sorts choisis comptent comme des sorts de barde pour vous, mais ils ne comptent pas dans le nombre de sorts de barde que vous connaissez.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Inspiration supérieure
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Inspiration supérieure',
            'description' => 'Quand vous lancez un dé d\'inspiration bardique, vous pouvez relancer le dé si le résultat est inférieur à votre modificateur de Charisme. Vous devez utiliser le nouveau résultat.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les voies primitives des barbares
function getBarbarianPaths() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM barbarian_paths ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getBarbarianPaths: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les collèges bardiques
function getBardColleges() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM bard_colleges ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getBardColleges: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir la voie primitive d'un personnage
function getCharacterBarbarianPath($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cbp.*, bp.name as path_name, bp.description as path_description,
                   bp.level_3_feature, bp.level_6_feature, bp.level_10_feature, bp.level_14_feature
            FROM character_barbarian_path cbp
            JOIN barbarian_paths bp ON cbp.path_id = bp.id
            WHERE cbp.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterBarbarianPath: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir le collège bardique d'un personnage
function getCharacterBardCollege($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cbc.*, bc.name as college_name, bc.description as college_description,
                   bc.level_3_feature, bc.level_6_feature, bc.level_14_feature
            FROM character_bard_college cbc
            JOIN bard_colleges bc ON cbc.college_id = bc.id
            WHERE cbc.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterBardCollege: " . $e->getMessage());
        return null;
    }
}

// Fonction pour sauvegarder le choix de voie primitive
function saveBarbarianPath($characterId, $pathId, $level3Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_barbarian_path (character_id, path_id, level_3_choice, level_6_choice, level_10_choice, level_14_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            path_id = VALUES(path_id),
            level_3_choice = VALUES(level_3_choice),
            level_6_choice = VALUES(level_6_choice),
            level_10_choice = VALUES(level_10_choice),
            level_14_choice = VALUES(level_14_choice)
        ");
        return $stmt->execute([$characterId, $pathId, $level3Choice, $level6Choice, $level10Choice, $level14Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveBarbarianPath: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix de collège bardique
function saveBardCollege($characterId, $collegeId, $level3Choice = null, $level6Choice = null, $level14Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_bard_college (character_id, college_id, level_3_choice, level_6_choice, level_14_choice)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            college_id = VALUES(college_id),
            level_3_choice = VALUES(level_3_choice),
            level_6_choice = VALUES(level_6_choice),
            level_14_choice = VALUES(level_14_choice)
        ");
        return $stmt->execute([$characterId, $collegeId, $level3Choice, $level6Choice, $level14Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveBardCollege: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir les améliorations de caractéristiques d'un personnage
function getCharacterAbilityImprovements($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT strength_bonus, dexterity_bonus, constitution_bonus, 
                   intelligence_bonus, wisdom_bonus, charisma_bonus
            FROM character_ability_improvements 
            WHERE character_id = ?
        ");
        $stmt->execute([$characterId]);
        $improvements = $stmt->fetch();
        
        if (!$improvements) {
            // Créer un enregistrement vide si il n'existe pas
            $stmt = $pdo->prepare("
                INSERT INTO character_ability_improvements (character_id) VALUES (?)
            ");
            $stmt->execute([$characterId]);
            return [
                'strength_bonus' => 0,
                'dexterity_bonus' => 0,
                'constitution_bonus' => 0,
                'intelligence_bonus' => 0,
                'wisdom_bonus' => 0,
                'charisma_bonus' => 0
            ];
        }
        
        return $improvements;
    } catch (PDOException $e) {
        error_log("Erreur getCharacterAbilityImprovements: " . $e->getMessage());
        return [
            'strength_bonus' => 0,
            'dexterity_bonus' => 0,
            'constitution_bonus' => 0,
            'intelligence_bonus' => 0,
            'wisdom_bonus' => 0,
            'charisma_bonus' => 0
        ];
    }
}

// Fonction pour sauvegarder les améliorations de caractéristiques
function saveCharacterAbilityImprovements($characterId, $improvements) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_ability_improvements 
            (character_id, strength_bonus, dexterity_bonus, constitution_bonus, 
             intelligence_bonus, wisdom_bonus, charisma_bonus)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            strength_bonus = VALUES(strength_bonus),
            dexterity_bonus = VALUES(dexterity_bonus),
            constitution_bonus = VALUES(constitution_bonus),
            intelligence_bonus = VALUES(intelligence_bonus),
            wisdom_bonus = VALUES(wisdom_bonus),
            charisma_bonus = VALUES(charisma_bonus)
        ");
        return $stmt->execute([
            $characterId,
            $improvements['strength_bonus'],
            $improvements['dexterity_bonus'],
            $improvements['constitution_bonus'],
            $improvements['intelligence_bonus'],
            $improvements['wisdom_bonus'],
            $improvements['charisma_bonus']
        ]);
    } catch (PDOException $e) {
        error_log("Erreur saveCharacterAbilityImprovements: " . $e->getMessage());
        return false;
    }
}

// Fonction pour calculer les caractéristiques finales avec les bonus de niveau
function calculateFinalAbilities($character, $abilityImprovements) {
    return [
        'strength' => min(20, $character['strength'] + ($character['strength_bonus'] ?? 0) + ($abilityImprovements['strength_bonus'] ?? 0)),
        'dexterity' => min(20, $character['dexterity'] + ($character['dexterity_bonus'] ?? 0) + ($abilityImprovements['dexterity_bonus'] ?? 0)),
        'constitution' => min(20, $character['constitution'] + ($character['constitution_bonus'] ?? 0) + ($abilityImprovements['constitution_bonus'] ?? 0)),
        'intelligence' => min(20, $character['intelligence'] + ($character['intelligence_bonus'] ?? 0) + ($abilityImprovements['intelligence_bonus'] ?? 0)),
        'wisdom' => min(20, $character['wisdom'] + ($character['wisdom_bonus'] ?? 0) + ($abilityImprovements['wisdom_bonus'] ?? 0)),
        'charisma' => min(20, $character['charisma'] + ($character['charisma_bonus'] ?? 0) + ($abilityImprovements['charisma_bonus'] ?? 0))
    ];
}

// Fonction pour calculer le nombre de points d'amélioration disponibles selon le niveau
function getAvailableAbilityPoints($level) {
    // En D&D 5e, les améliorations de caractéristiques sont aux niveaux 4, 8, 12, 16, 19
    $improvementLevels = [4, 8, 12, 16, 19];
    $totalPoints = 0;
    
    foreach ($improvementLevels as $improvementLevel) {
        if ($level >= $improvementLevel) {
            $totalPoints += 2; // 2 points par amélioration
        }
    }
    
    return $totalPoints;
}

// Fonction pour calculer le nombre de points d'amélioration utilisés
function getUsedAbilityPoints($abilityImprovements) {
    return ($abilityImprovements['strength_bonus'] ?? 0) +
           ($abilityImprovements['dexterity_bonus'] ?? 0) +
           ($abilityImprovements['constitution_bonus'] ?? 0) +
           ($abilityImprovements['intelligence_bonus'] ?? 0) +
           ($abilityImprovements['wisdom_bonus'] ?? 0) +
           ($abilityImprovements['charisma_bonus'] ?? 0);
}

// Fonction pour calculer les points restants
function getRemainingAbilityPoints($level, $abilityImprovements) {
    $available = getAvailableAbilityPoints($level);
    $used = getUsedAbilityPoints($abilityImprovements);
    return max(0, $available - $used);
}
