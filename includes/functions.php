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
    $role = getUserRole();
    return $role === 'dm' || $role === 'admin';
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
// Fonction pour obtenir le contenu d'un sac d'équipement
function getEquipmentPackContents($packName) {
    $packs = [
        'sac d\'exploration souterraine' => [
            'un sac à dos',
            'un pied de biche',
            'un marteau',
            '10 pitons',
            '10 torches',
            'une boite d\'allume-feu',
            '10 jours de rations',
            'corde de chanvre (15m)',
            'une gourde d\'eau'
        ],
        'sac d\'explorateur' => [
            'un sac à dos',
            'un sac de couchage',
            'une gamelle',
            'une boite d\'allume-feu',
            '10 torches',
            '10 jours de rations',
            'corde de chanvre (15m)',
            'une gourde d\'eau'
        ]
    ];
    
    return $packs[$packName] ?? [];
}

// Fonction pour obtenir les armes courantes disponibles
function getCommonWeapons($type = null) {
    global $pdo;
    
    $whereClause = '';
    $params = [];
    
    if ($type) {
        $whereClause = 'WHERE type = ?';
        $params[] = $type;
    } else {
        $whereClause = 'WHERE type IN ("Armes courantes à distance", "Armes courantes de corps à corps")';
    }
    
    $stmt = $pdo->prepare("SELECT name, type FROM weapons $whereClause ORDER BY type, name");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getWarWeapons($type = null) {
    global $pdo;
    
    $whereClause = '';
    $params = [];
    
    if ($type) {
        $whereClause = 'WHERE type = ?';
        $params[] = $type;
    } else {
        $whereClause = 'WHERE type IN ("Armes de guerre à distance", "Armes de guerre de corps à corps")';
    }
    
    $stmt = $pdo->prepare("SELECT name, type FROM weapons $whereClause ORDER BY type, name");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

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
                    
                    // Gestion spéciale pour les armes courantes
                    if (strpos($description, 'n\'importe quelle arme courante') !== false) {
                        $choices[$choice] = [
                            'type' => 'weapon_choice',
                            'description' => $description,
                            'options' => getCommonWeapons()
                        ];
                    }
                    // Gestion spéciale pour les armes de guerre
                    elseif (strpos($description, 'n\'importe quelle arme de guerre') !== false) {
                        // Déterminer le type d'arme selon la description
                        $weaponType = null;
                        if (strpos($description, 'corps à corps') !== false) {
                            $weaponType = 'Armes de guerre de corps à corps';
                        } elseif (strpos($description, 'distance') !== false) {
                            $weaponType = 'Armes de guerre à distance';
                        }
                        
                        $choices[$choice] = [
                            'type' => 'weapon_choice',
                            'description' => $description,
                            'options' => getWarWeapons($weaponType)
                        ];
                    }
                    // Gestion spéciale pour les sacs d'équipement
                    elseif (strpos($description, 'sac d\'exploration souterraine') !== false) {
                        $choices[$choice] = [
                            'type' => 'pack',
                            'description' => $description,
                            'contents' => getEquipmentPackContents('sac d\'exploration souterraine')
                        ];
                    }
                    elseif (strpos($description, 'sac d\'explorateur') !== false) {
                        $choices[$choice] = [
                            'type' => 'pack',
                            'description' => $description,
                            'contents' => getEquipmentPackContents('sac d\'explorateur')
                        ];
                    }
                    else {
                        $choices[$choice] = $description;
                    }
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
function generateFinalEquipment($classId, $equipmentChoices, $backgroundId = null, $weaponChoices = []) {
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
                $selectedChoice = $item[$equipmentChoices[$index]];
                
                // Gestion spéciale pour les armes courantes
                if (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'weapon_choice') {
                    // Récupérer l'arme sélectionnée
                    if (isset($weaponChoices[$index][$equipmentChoices[$index]])) {
                        $selectedWeapon = $weaponChoices[$index][$equipmentChoices[$index]];
                        $finalEquipment[] = $selectedWeapon;
                    } else {
                        // Par défaut, prendre la première arme disponible
                        $firstWeapon = $selectedChoice['options'][0]['name'] ?? 'Arme courante';
                        $finalEquipment[] = $firstWeapon;
                    }
                }
                // Gestion spéciale pour les sacs d'équipement
                elseif (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'pack') {
                    // Ajouter le sac et son contenu
                    $finalEquipment[] = $selectedChoice['description'];
                    $finalEquipment = array_merge($finalEquipment, $selectedChoice['contents']);
                }
                else {
                    $finalEquipment[] = $selectedChoice;
                }
            } else {
                // Si aucun choix n'a été fait, prendre le premier choix par défaut
                $firstChoice = array_keys($item)[0];
                $selectedChoice = $item[$firstChoice];
                
                if (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'weapon_choice') {
                    $firstWeapon = $selectedChoice['options'][0]['name'] ?? 'Arme courante';
                    $finalEquipment[] = $firstWeapon;
                } elseif (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'pack') {
                    $finalEquipment[] = $selectedChoice['description'];
                    $finalEquipment = array_merge($finalEquipment, $selectedChoice['contents']);
                } else {
                    $finalEquipment[] = $selectedChoice;
                }
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

// Fonction pour ajouter l'équipement de départ choisi par le joueur
function addStartingEquipmentToCharacter($characterId, $equipmentData) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Parser l'équipement final
        $equipmentLines = explode("\n", $equipmentData['equipment']);
        
        foreach ($equipmentLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Déterminer le type d'objet et les détails
            $itemType = 'other';
            $weaponId = null;
            $armorId = null;
            
            // Vérifier si c'est une arme connue (recherche flexible)
            $weapon = null;
            
            // D'abord essayer une correspondance exacte
            $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
            $stmt->execute([$line]);
            $weapon = $stmt->fetch();
            
            // Si pas trouvé, essayer de chercher sans les articles et avec majuscule
            if (!$weapon) {
                $lineWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $line);
                $lineCapitalized = ucfirst($lineWithoutArticle);
                $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
                $stmt->execute([$lineCapitalized]);
                $weapon = $stmt->fetch();
            }
            
            // Si toujours pas trouvé, chercher par correspondance partielle
            if (!$weapon) {
                $lineWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $line);
                $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name LIKE ?");
                $stmt->execute(['%' . $lineWithoutArticle . '%']);
                $weapon = $stmt->fetch();
            }
            
            if ($weapon) {
                $itemType = 'weapon';
                $weaponId = $weapon['id'];
            }
            
            // Vérifier si c'est une armure connue
            if ($itemType === 'other') {
                $stmt = $pdo->prepare("SELECT id FROM armor WHERE name = ?");
                $stmt->execute([$line]);
                $armor = $stmt->fetch();
                if ($armor) {
                    $itemType = 'armor';
                    $armorId = $armor['id'];
                }
            }
            
            // Déterminer le type d'objet pour les objets non-armes/armures
            if ($itemType === 'other') {
                // Analyser le nom pour déterminer le type approprié
                $lineLower = mb_strtolower($line, 'UTF-8');
                if (strpos($lineLower, 'sac') !== false) {
                    $itemType = 'bag'; // Les sacs
                } elseif (strpos($lineLower, 'marteau') !== false || strpos($lineLower, 'biche') !== false || 
                          strpos($lineLower, 'piton') !== false || strpos($lineLower, 'torche') !== false || 
                          strpos($lineLower, 'allume-feu') !== false || strpos($lineLower, 'corde') !== false) {
                    $itemType = 'tool'; // Les outils
                } elseif (strpos($lineLower, 'ration') !== false || strpos($lineLower, 'eau') !== false || 
                          strpos($lineLower, 'gourde') !== false) {
                    $itemType = 'consumable'; // Les consommables
                } elseif (strpos($lineLower, 'vêtement') !== false || strpos($lineLower, 'habit') !== false) {
                    $itemType = 'clothing'; // Les vêtements
                } elseif (strpos($lineLower, 'bourse') !== false) {
                    $itemType = 'bag'; // Les bourses
                } else {
                    $itemType = 'misc'; // Par défaut, objets divers
                }
            }
            
            // Ajouter l'objet à l'inventaire du personnage dans la nouvelle table
            $stmt = $pdo->prepare("
                INSERT INTO character_equipment 
                (character_id, item_name, item_type, quantity, equipped, notes, obtained_at, obtained_from) 
                VALUES (?, ?, ?, 1, 0, 'Équipement de départ', NOW(), 'Sélection équipement de départ')
            ");
            $stmt->execute([
                $characterId,    // character_id
                $line,           // item_name
                $itemType        // item_type
            ]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de l'ajout de l'équipement de départ: " . $e->getMessage());
        return false;
    }
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
        
        // Patterns spécifiques pour les pluriels français complexes
        if (strpos($weaponName, 'épée') !== false) {
            $patterns[] = 'deux épées courtes';
            $patterns[] = 'trois épées courtes';
            $patterns[] = 'quatre épées courtes';
            $patterns[] = 'cinq épées courtes';
        }
        
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
        SELECT po.display_name as item_name, po.object_type as item_type, po.equipped_slot, w.hands
        FROM place_objects po
        LEFT JOIN weapons w ON po.display_name = w.name AND po.object_type = 'weapon'
        WHERE po.owner_type = 'player' AND po.owner_id = ? AND po.is_equipped = 1
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
            UPDATE place_objects 
            SET is_equipped = 0, equipped_slot = NULL 
            WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = ?
        ");
        $stmt->execute([$characterId, $slot]);
        
        // Équiper le nouvel objet
        $stmt = $pdo->prepare("
            UPDATE place_objects 
            SET is_equipped = 1, equipped_slot = ? 
            WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = ?
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
    
    // Cas spécial : "deux hachettes" - déséquiper des deux slots
    if (strtolower($itemName) === 'deux hachettes') {
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND item_name = ?
        ");
        return $stmt->execute([$characterId, $itemName]);
    } else {
        // Déséquipement normal
        $stmt = $pdo->prepare("
            UPDATE character_equipment 
            SET equipped = 0, equipped_slot = NULL 
            WHERE character_id = ? AND item_name = ?
        ");
        return $stmt->execute([$characterId, $itemName]);
    }
}

// Fonction pour synchroniser l'équipement de base vers place_objects
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
            $stmt = $pdo->prepare("SELECT id FROM place_objects WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = 'weapon'");
            $stmt->execute([$characterId, $weapon['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter l'arme
                $stmt = $pdo->prepare("
                    INSERT INTO place_objects (place_id, display_name, object_type, type_precis, description, is_identified, is_visible, is_equipped, position_x, position_y, is_on_map, owner_type, owner_id, poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed, magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from) 
                    VALUES (NULL, ?, 'weapon', ?, ?, 1, 0, 0, 0, 0, 0, 'player', ?, NULL, NULL, NULL, 0, 0, 0, NULL, 0, NULL, 'Équipement de base', 1, NULL, NULL, NOW(), 'Équipement de base')
                ");
                $description = "Arme: {$weapon['type']}, {$weapon['hands']} main(s), Dégâts: {$weapon['damage']}";
                if (!empty($weapon['properties'])) {
                    $description .= ", Propriétés: {$weapon['properties']}";
                }
                $stmt->execute([$weapon['name'], $weapon['name'], $description, $characterId]);
            }
        }
        
        // Ajouter les armures
        foreach ($detectedArmor as $armor) {
            // Vérifier si l'armure existe déjà
            $stmt = $pdo->prepare("SELECT id FROM place_objects WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = 'armor'");
            $stmt->execute([$characterId, $armor['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter l'armure
                $stmt = $pdo->prepare("
                    INSERT INTO place_objects (place_id, display_name, object_type, type_precis, description, is_identified, is_visible, is_equipped, position_x, position_y, is_on_map, owner_type, owner_id, poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed, magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from) 
                    VALUES (NULL, ?, 'armor', ?, ?, 1, 0, 0, 0, 0, 0, 'player', ?, NULL, NULL, NULL, 0, 0, 0, NULL, 0, NULL, 'Équipement de base', 1, NULL, NULL, NOW(), 'Équipement de base')
                ");
                $description = "Armure: {$armor['type']}, CA: {$armor['ac_formula']}";
                $stmt->execute([$armor['name'], $armor['name'], $description, $characterId]);
            }
        }
        
        // Ajouter les boucliers
        foreach ($detectedShields as $shield) {
            // Vérifier si le bouclier existe déjà
            $stmt = $pdo->prepare("SELECT id FROM place_objects WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = 'armor'");
            $stmt->execute([$characterId, $shield['name']]);
            
            if (!$stmt->fetch()) {
                // Ajouter le bouclier
                $stmt = $pdo->prepare("
                    INSERT INTO place_objects (place_id, display_name, object_type, type_precis, description, is_identified, is_visible, is_equipped, position_x, position_y, is_on_map, owner_type, owner_id, poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed, magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from) 
                    VALUES (NULL, ?, 'armor', ?, ?, 1, 0, 0, 0, 0, 0, 'player', ?, NULL, NULL, NULL, 0, 0, 0, NULL, 0, NULL, 'Équipement de base', 1, NULL, NULL, NOW(), 'Équipement de base')
                ");
                $description = "Bouclier, Bonus CA: +{$shield['ac_bonus']}";
                $stmt->execute([$shield['name'], $shield['name'], $description, $characterId]);
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
    // Utiliser le nouveau système basé sur la base de données si disponible
    if (function_exists('getClassCapabilities')) {
        return getClassCapabilities(6, $level); // ID 6 = Barbare
    }
    
    // Ancien système (fallback)
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

// Fonction pour obtenir les capacités de paladin selon le niveau
function getPaladinCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez toutes les armures, les boucliers, les armes courantes et les armes de guerre.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Athlétisme, Intimidation, Intuition, Médecine, Persuasion et Religion.'
        ];
    }
    
    // Niveau 1 - Sorts divins
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous avez appris à utiliser la magie divine. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de paladin.'
        ];
    }
    
    // Niveau 1 - Sens divin
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sens divin',
            'description' => 'Vous pouvez utiliser votre action pour détecter la présence de magie divine ou profane dans un rayon de 18 mètres autour de vous.'
        ];
    }
    
    // Niveau 1 - Soins par les mains
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Soins par les mains',
            'description' => 'Vous avez un réservoir de pouvoir de guérison qui se renouvelle quand vous terminez un repos long. Avec ce réservoir, vous pouvez restaurer un nombre de points de vie égal à votre niveau de paladin × 5.'
        ];
    }
    
    // Niveau 2 - Style de combat
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Style de combat',
            'description' => 'Vous adoptez un style de combat particulier. Votre choix vous accorde des avantages au combat.'
        ];
    }
    
    // Niveau 2 - Sorts divins
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 3 - Serment sacré
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Serment sacré',
            'description' => 'Quand vous atteignez le niveau 3, vous prêtez le serment qui lie un paladin pour toujours. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 7, 15 et 20.'
        ];
    }
    
    // Niveau 3 - Sorts divins
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Attaque supplémentaire
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer deux fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 5 - Sorts divins
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 6 - Aura de protection
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Aura de protection',
            'description' => 'Quand vous ou une créature amie dans un rayon de 3 mètres autour de vous devez faire un jet de sauvegarde, la créature gagne un bonus au jet égal à votre modificateur de Charisme (minimum +1).'
        ];
    }
    
    // Niveau 7 - Serment sacré
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Serment sacré',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre serment sacré.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Sorts divins
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 10 - Aura de courage
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Aura de courage',
            'description' => 'Vous et les créatures amies dans un rayon de 3 mètres autour de vous ne pouvez pas être effrayés tant que vous êtes conscient.'
        ];
    }
    
    // Niveau 11 - Frappe divine améliorée
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Frappe divine améliorée',
            'description' => 'Votre frappe divine inflige maintenant 1d8 dégâts radiants supplémentaires.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Sorts divins
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 14 - Aura de purification
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Aura de purification',
            'description' => 'Vous et les créatures amies dans un rayon de 3 mètres autour de vous ne pouvez pas être maudits tant que vous êtes conscient.'
        ];
    }
    
    // Niveau 15 - Serment sacré
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Serment sacré',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre serment sacré.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Sorts divins
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Sorts divins',
            'description' => 'Vous connaissez des sorts supplémentaires de paladin.'
        ];
    }
    
    // Niveau 18 - Aura de protection améliorée
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Aura de protection améliorée',
            'description' => 'Le rayon de votre aura de protection augmente à 9 mètres.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Serment sacré
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Serment sacré',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre serment sacré.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de rôdeur selon le niveau
function getRangerCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures',
            'description' => 'Vous maîtrisez les armures légères, les armures intermédiaires et les boucliers.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armes',
            'description' => 'Vous maîtrisez les armes courantes, les armes de guerre, les arbalètes de poing, les épées longues, les rapières, les épées courtes et les armes à distance simples.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez trois compétences parmi : Athlétisme, Dressage, Investigation, Nature, Perception, Survie, Discrétion et Intuition.'
        ];
    }
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie de la nature. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de rôdeur.'
        ];
    }
    
    // Niveau 1 - Ennemis favoris
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Ennemis favoris',
            'description' => 'Vous avez une expérience significative dans l\'étude, le suivi, la chasse et même la communication avec un certain type d\'ennemi.'
        ];
    }
    
    // Niveau 1 - Terrain de prédilection
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Terrain de prédilection',
            'description' => 'Vous êtes particulièrement familier avec un type d\'environnement naturel et êtes compétent pour voyager et survivre dans de telles régions.'
        ];
    }
    
    // Niveau 2 - Style de combat
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Style de combat',
            'description' => 'Vous adoptez un style de combat particulier. Votre choix vous accorde des avantages au combat.'
        ];
    }
    
    // Niveau 2 - Sorts
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 3 - Archétype de rôdeur
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Archétype de rôdeur',
            'description' => 'Vous choisissez un archétype qui reflète votre approche de la nature et de la chasse. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 7, 11 et 15.'
        ];
    }
    
    // Niveau 3 - Sorts
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 4 - Sorts
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 5 - Attaque supplémentaire
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer deux fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 5 - Sorts
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 6 - Ennemis favoris
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Ennemis favoris',
            'description' => 'Vous gagnez un nouveau type d\'ennemi favori.'
        ];
    }
    
    // Niveau 6 - Sorts
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 7 - Archétype de rôdeur
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Archétype de rôdeur',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de rôdeur.'
        ];
    }
    
    // Niveau 7 - Sorts
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 8 - Terrain de prédilection
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Terrain de prédilection',
            'description' => 'Vous gagnez un nouveau type de terrain de prédilection.'
        ];
    }
    
    // Niveau 8 - Sorts
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 9 - Sorts
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 10 - Ennemis favoris
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Ennemis favoris',
            'description' => 'Vous gagnez un nouveau type d\'ennemi favori.'
        ];
    }
    
    // Niveau 10 - Sorts
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 11 - Archétype de rôdeur
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Archétype de rôdeur',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de rôdeur.'
        ];
    }
    
    // Niveau 11 - Sorts
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 12 - Sorts
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 13 - Sorts
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 14 - Ennemis favoris
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Ennemis favoris',
            'description' => 'Vous gagnez un nouveau type d\'ennemi favori.'
        ];
    }
    
    // Niveau 14 - Sorts
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 15 - Archétype de rôdeur
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Archétype de rôdeur',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de rôdeur.'
        ];
    }
    
    // Niveau 15 - Sorts
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 16 - Sorts
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 17 - Sorts
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 18 - Terrain de prédilection
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Terrain de prédilection',
            'description' => 'Vous gagnez un nouveau type de terrain de prédilection.'
        ];
    }
    
    // Niveau 18 - Sorts
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 19 - Sorts
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    // Niveau 20 - Archétype de rôdeur
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Archétype de rôdeur',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de rôdeur.'
        ];
    }
    
    // Niveau 20 - Sorts
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous connaissez des sorts supplémentaires de rôdeur.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de roublard selon le niveau
function getRogueCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures',
            'description' => 'Vous maîtrisez les armures légères.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armes',
            'description' => 'Vous maîtrisez les armes courantes, les arbalètes de poing, les épées longues, les rapières, les épées courtes et les armes à distance simples.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez les outils de voleur. Votre bonus de maîtrise est doublé pour tous les tests d\'Intelligence (Investigation) et de Sagesse (Perception) que vous effectuez pour rechercher des pièges et des portes secrètes.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez quatre compétences parmi : Acrobaties, Athlétisme, Discrétion, Escamotage, Intimidation, Investigation, Perception, Persuasion, Représentation et Tromperie.'
        ];
    }
    
    // Niveau 1 - Attaque sournoise
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Vous savez comment frapper subtilement et exploiter les failles de la garde d\'un ennemi. Une fois par tour, vous pouvez infliger 1d6 dégâts supplémentaires à une créature que vous touchez avec une attaque d\'arme si vous avez un avantage sur le jet d\'attaque.'
        ];
    }
    
    // Niveau 1 - Argot des voleurs
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Argot des voleurs',
            'description' => 'Vous connaissez l\'argot des voleurs, un mélange secret de dialecte, de jargon et de codes qui vous permet de cacher des messages dans des conversations apparemment normales.'
        ];
    }
    
    // Niveau 2 - Esquive
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Esquive',
            'description' => 'Votre agilité et votre vitesse vous permettent de vous déplacer et d\'agir rapidement. Vous pouvez utiliser une action bonus lors de votre tour pour effectuer l\'action se précipiter, se désengager ou se cacher.'
        ];
    }
    
    // Niveau 3 - Archétype de roublard
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Archétype de roublard',
            'description' => 'Vous choisissez un archétype qui reflète votre style de roublard. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 9, 13 et 17.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Attaque sournoise
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Les dégâts de votre attaque sournoise augmentent à 3d6.'
        ];
    }
    
    // Niveau 5 - Esquive
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Esquive',
            'description' => 'Vous pouvez utiliser votre réaction pour réduire de moitié les dégâts d\'une attaque qui vous touche.'
        ];
    }
    
    // Niveau 6 - Expertise
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Expertise',
            'description' => 'Votre bonus de maîtrise est doublé pour deux compétences de votre choix.'
        ];
    }
    
    // Niveau 7 - Esquive
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Esquive',
            'description' => 'Vous pouvez utiliser votre réaction pour réduire de moitié les dégâts d\'une attaque qui vous touche.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Archétype de roublard
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Archétype de roublard',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de roublard.'
        ];
    }
    
    // Niveau 9 - Attaque sournoise
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Les dégâts de votre attaque sournoise augmentent à 5d6.'
        ];
    }
    
    // Niveau 10 - Amélioration de caractéristique
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 11 - Expertise
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Expertise',
            'description' => 'Votre bonus de maîtrise est doublé pour deux compétences de votre choix.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Archétype de roublard
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Archétype de roublard',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de roublard.'
        ];
    }
    
    // Niveau 13 - Attaque sournoise
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Les dégâts de votre attaque sournoise augmentent à 7d6.'
        ];
    }
    
    // Niveau 14 - Amélioration de caractéristique
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 15 - Esquive
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Esquive',
            'description' => 'Vous pouvez utiliser votre réaction pour réduire de moitié les dégâts d\'une attaque qui vous touche.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Archétype de roublard
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Archétype de roublard',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype de roublard.'
        ];
    }
    
    // Niveau 17 - Attaque sournoise
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Les dégâts de votre attaque sournoise augmentent à 9d6.'
        ];
    }
    
    // Niveau 18 - Esquive
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Esquive',
            'description' => 'Vous pouvez utiliser votre réaction pour réduire de moitié les dégâts d\'une attaque qui vous touche.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Attaque sournoise
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Attaque sournoise',
            'description' => 'Les dégâts de votre attaque sournoise augmentent à 10d6.'
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

// Fonction pour obtenir les capacités de clerc selon le niveau
function getClericCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie divine. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de clerc.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les armures légères, les armures intermédiaires, les boucliers et les armes courantes.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Histoire, Médecine, Perspicacité et Religion.'
        ];
    }
    
    // Niveau 1 - Domaine divin
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Domaine divin',
            'description' => 'Vous choisissez un domaine divin qui reflète votre dévotion à votre divinité. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 2, 6, 8 et 17.'
        ];
    }
    
    // Niveau 2 - Canalisation de divinité
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Canalisation de divinité',
            'description' => 'Vous pouvez utiliser votre canalisation de divinité pour invoquer la puissance divine. Vous commencez avec une utilisation de cette aptitude. Vous récupérez toutes les utilisations dépensées quand vous terminez un repos long.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Destruction des morts-vivants
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Destruction des morts-vivants',
            'description' => 'Quand un mort-vivant de défi 1/2 ou moins échoue à son jet de sauvegarde contre votre canalisation de divinité, il est détruit.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 10 - Intervention divine
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Intervention divine',
            'description' => 'Vous pouvez appeler votre divinité pour intercéder en votre faveur quand vous en avez le plus besoin. Vous implorez votre divinité pour qu\'elle intervienne directement dans le monde. Vous décrivez l\'aide que vous souhaitez et lancez un d100.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 14 - Destruction des morts-vivants améliorée
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Destruction des morts-vivants améliorée',
            'description' => 'Quand un mort-vivant de défi 1 ou moins échoue à son jet de sauvegarde contre votre canalisation de divinité, il est détruit.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 18 - Canalisation de divinité améliorée
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Canalisation de divinité améliorée',
            'description' => 'Vous pouvez utiliser votre canalisation de divinité deux fois entre deux repos longs.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Intervention divine améliorée
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Intervention divine améliorée',
            'description' => 'Votre appel à l\'intervention divine réussit automatiquement, sans que vous ayez besoin de lancer le dé.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de druide selon le niveau
function getDruidCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie de la nature. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de druide.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les armures légères, les armures intermédiaires, les boucliers (mais pas les armures en métal), les frondes, les bâtons, les dagues, les fléchettes, les javelots, les massues, les lances, les bâtons de jet, les cimeterres, les lances courtes, les lances longues et les tridents.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Animal Handling, Arcana, Insight, Medicine, Nature, Perception, Religion et Survival.'
        ];
    }
    
    // Niveau 1 - Cercle druidique
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Cercle druidique',
            'description' => 'Vous choisissez un cercle druidique qui reflète votre lien avec la nature. Votre choix vous accorde des capacités au niveau 2, puis aux niveaux 6, 10 et 14.'
        ];
    }
    
    // Niveau 2 - Cercle druidique
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Cercle druidique',
            'description' => 'Vous approfondissez votre lien avec la nature et gagnez des capacités liées à votre cercle druidique.'
        ];
    }
    
    // Niveau 2 - Forme sauvage
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Forme sauvage',
            'description' => 'Vous pouvez utiliser votre action pour vous transformer en bête que vous avez déjà vue. Vous pouvez utiliser cette aptitude deux fois entre deux repos longs.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 6 - Cercle druidique
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Cercle druidique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre cercle druidique.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 10 - Cercle druidique
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Cercle druidique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre cercle druidique.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 14 - Cercle druidique
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Cercle druidique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre cercle druidique.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 18 - Forme sauvage améliorée
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Forme sauvage améliorée',
            'description' => 'Vous pouvez lancer vos sorts de druide même sous forme sauvage. Vous pouvez effectuer les composantes somatiques et verbales d\'un sort de druide même sous forme sauvage.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Archidruide
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Archidruide',
            'description' => 'Vous pouvez utiliser votre forme sauvage un nombre illimité de fois. De plus, vous pouvez ignorer les composantes verbales et somatiques de vos sorts de druide, et vous ne pouvez pas être désorienté par la magie.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités d'ensorceleur selon le niveau
function getSorcererCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie innée. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts d\'ensorceleur.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les dagues, les fléchettes, les frondes, les bâtons de jet et les arbalètes légères.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Arcana, Deception, Insight, Intimidation, Persuasion et Religion.'
        ];
    }
    
    // Niveau 1 - Origine magique
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Origine magique',
            'description' => 'Vous choisissez une origine magique qui reflète la source de votre pouvoir magique. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 6, 14 et 18.'
        ];
    }
    
    // Niveau 2 - Points de sorcellerie
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 2 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau. Vous pouvez dépenser ces points pour diverses capacités.'
        ];
    }
    
    // Niveau 3 - Métamagie
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Métamagie',
            'description' => 'Vous gagnez la capacité de modifier vos sorts au moment de les lancer. Vous gagnez deux options de métamagie de votre choix.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Points de sorcellerie
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 5 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 6 - Origine magique
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Origine magique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre origine magique.'
        ];
    }
    
    // Niveau 7 - Points de sorcellerie
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 7 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Points de sorcellerie
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 9 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 10 - Métamagie
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Métamagie',
            'description' => 'Vous gagnez une option de métamagie supplémentaire.'
        ];
    }
    
    // Niveau 11 - Points de sorcellerie
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 11 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Points de sorcellerie
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 13 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 14 - Origine magique
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Origine magique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre origine magique.'
        ];
    }
    
    // Niveau 15 - Points de sorcellerie
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 15 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Points de sorcellerie
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Points de sorcellerie',
            'description' => 'Vous avez 17 points de sorcellerie, et vous en gagnez plus à mesure que vous montez de niveau.'
        ];
    }
    
    // Niveau 18 - Origine magique
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Origine magique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre origine magique.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Maître de la sorcellerie
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Maître de la sorcellerie',
            'description' => 'Vous gagnez 4 points de sorcellerie supplémentaires. De plus, vous récupérez 4 points de sorcellerie dépensés quand vous terminez un repos court.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de guerrier selon le niveau
function getFighterCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez toutes les armures, boucliers, armes courantes et armes de guerre.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Acrobatics, Animal Handling, Athletics, History, Insight, Intimidation, Perception et Survival.'
        ];
    }
    
    // Niveau 1 - Style de combat
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Style de combat',
            'description' => 'Vous adoptez un style de combat particulier comme spécialité. Vous ne pouvez pas prendre le même style de combat plus d\'une fois, même si vous avez d\'autres occasions de le faire.'
        ];
    }
    
    // Niveau 1 - Second souffle
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Second souffle',
            'description' => 'Vous avez une réserve limitée d\'endurance que vous pouvez puiser pour vous protéger. En tant qu\'action bonus, vous pouvez récupérer 1d10 + votre niveau de guerrier points de vie.'
        ];
    }
    
    // Niveau 2 - Action supplémentaire
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Action supplémentaire',
            'description' => 'Vous pouvez vous surmener. En tant qu\'action bonus, vous pouvez faire une attaque d\'arme supplémentaire.'
        ];
    }
    
    // Niveau 3 - Archétype martial
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Archétype martial',
            'description' => 'Vous choisissez un archétype martial qui reflète votre style de combat. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 7, 10, 15 et 18.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Attaque supplémentaire
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer deux fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 6 - Amélioration de caractéristique
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 7 - Archétype martial
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Archétype martial',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype martial.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Indomptable
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Indomptable',
            'description' => 'Vous pouvez relancer un jet de sauvegarde que vous venez de faire. Vous devez utiliser le nouveau résultat. Une fois que vous avez utilisé cette aptitude, vous ne pouvez plus l\'utiliser tant que vous n\'avez pas terminé un repos long.'
        ];
    }
    
    // Niveau 10 - Archétype martial
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Archétype martial',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype martial.'
        ];
    }
    
    // Niveau 11 - Attaque supplémentaire
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer trois fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Indomptable
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Indomptable',
            'description' => 'Vous pouvez utiliser votre aptitude Indomptable deux fois entre deux repos longs.'
        ];
    }
    
    // Niveau 14 - Amélioration de caractéristique
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 15 - Archétype martial
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Archétype martial',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype martial.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Action supplémentaire
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Action supplémentaire',
            'description' => 'Vous pouvez utiliser votre aptitude Action supplémentaire deux fois entre deux repos longs.'
        ];
    }
    
    // Niveau 18 - Archétype martial
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Archétype martial',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre archétype martial.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Attaque supplémentaire
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer quatre fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de magicien selon le niveau
function getWizardCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie arcanique. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts de magicien.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les dagues, les fléchettes, les frondes, les bâtons de jet et les arbalètes légères.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Arcana, History, Insight, Investigation, Medicine et Religion.'
        ];
    }
    
    // Niveau 1 - Rituel
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Rituel',
            'description' => 'Vous pouvez lancer un sort de magicien que vous connaissez comme rituel si ce sort a le tag rituel.'
        ];
    }
    
    // Niveau 1 - Récupération de sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Récupération de sorts',
            'description' => 'Vous avez appris à récupérer une partie de votre énergie magique en étudiant votre grimoire. Une fois par jour quand vous terminez un repos court, vous pouvez récupérer des emplacements de sorts dépensés.'
        ];
    }
    
    // Niveau 2 - Tradition arcanique
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Tradition arcanique',
            'description' => 'Vous choisissez une tradition arcanique qui reflète votre approche de la magie. Votre choix vous accorde des capacités au niveau 2, puis aux niveaux 6, 10 et 14.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Récupération de sorts
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Récupération de sorts',
            'description' => 'Vous avez appris à récupérer une partie de votre énergie magique en étudiant votre grimoire. Une fois par jour quand vous terminez un repos court, vous pouvez récupérer des emplacements de sorts dépensés.'
        ];
    }
    
    // Niveau 6 - Tradition arcanique
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Tradition arcanique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition arcanique.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 10 - Tradition arcanique
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Tradition arcanique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition arcanique.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 14 - Tradition arcanique
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Tradition arcanique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition arcanique.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 18 - Maîtrise des sorts
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Maîtrise des sorts',
            'description' => 'Vous avez atteint une telle maîtrise de certains sorts que vous pouvez les lancer à volonté. Choisissez un sort de magicien de niveau 1 et un sort de magicien de niveau 2 que vous connaissez.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Signature de sorts
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Signature de sorts',
            'description' => 'Vous avez maîtrisé deux sorts puissants et pouvez les lancer avec une facilité remarquable. Choisissez deux sorts de magicien de niveau 3 que vous connaissez.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités de moine selon le niveau
function getMonkCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les armes courantes, les épées courtes et les armes de guerre simples.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Acrobatics, Athletics, History, Insight, Religion et Stealth.'
        ];
    }
    
    // Niveau 1 - Arts martiaux
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Arts martiaux',
            'description' => 'Vous maîtrisez les arts martiaux, vous permettant de vous battre efficacement sans armes ni armure.'
        ];
    }
    
    // Niveau 1 - Défense sans armure
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Défense sans armure',
            'description' => 'Quand vous ne portez pas d\'armure, votre classe d\'armure est égale à 10 + votre modificateur de Dextérité + votre modificateur de Sagesse.'
        ];
    }
    
    // Niveau 2 - Ki
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Ki',
            'description' => 'Vous avez appris à puiser dans votre énergie vitale pour accomplir des exploits surnaturels. Vous avez un nombre de points de ki égal à votre niveau de moine.'
        ];
    }
    
    // Niveau 2 - Mouvement sans armure
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Mouvement sans armure',
            'description' => 'Votre vitesse augmente tant que vous ne portez pas d\'armure ni de bouclier.'
        ];
    }
    
    // Niveau 3 - Tradition monastique
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Tradition monastique',
            'description' => 'Vous choisissez une tradition monastique qui reflète votre approche de la vie monastique. Votre choix vous accorde des capacités au niveau 3, puis aux niveaux 6, 11 et 17.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 4 - Esquive rapide
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Esquive rapide',
            'description' => 'Vous pouvez utiliser votre réaction pour dépenser 1 point de ki quand vous êtes touché par une attaque d\'arme de mêlée pour réduire les dégâts que vous subissez d\'un nombre égal à votre niveau de moine + votre modificateur de Sagesse.'
        ];
    }
    
    // Niveau 5 - Attaque supplémentaire
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Attaque supplémentaire',
            'description' => 'Vous pouvez attaquer deux fois, au lieu d\'une, quand vous effectuez l\'action attaquer lors de votre tour.'
        ];
    }
    
    // Niveau 5 - Frappe étourdissante
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Frappe étourdissante',
            'description' => 'Vous pouvez utiliser votre action pour dépenser 1 point de ki et tenter d\'étourdir une créature avec une attaque d\'arme de mêlée.'
        ];
    }
    
    // Niveau 6 - Tradition monastique
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Tradition monastique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition monastique.'
        ];
    }
    
    // Niveau 6 - Frappe de ki
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Frappe de ki',
            'description' => 'Vos attaques d\'arme de mêlée comptent comme magiques pour surmonter la résistance et l\'immunité aux attaques non magiques.'
        ];
    }
    
    // Niveau 7 - Esquive instinctive
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Esquive instinctive',
            'description' => 'Vous pouvez utiliser votre réaction pour dépenser 1 point de ki quand vous êtes touché par une attaque d\'arme de mêlée pour réduire les dégâts que vous subissez d\'un nombre égal à votre niveau de moine + votre modificateur de Sagesse.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Mouvement sans armure
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Mouvement sans armure',
            'description' => 'Votre vitesse augmente tant que vous ne portez pas d\'armure ni de bouclier.'
        ];
    }
    
    // Niveau 10 - Pureté du corps
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Pureté du corps',
            'description' => 'Vous êtes immunisé contre la maladie et le poison.'
        ];
    }
    
    // Niveau 11 - Tradition monastique
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Tradition monastique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition monastique.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Langue de la lumière et des ténèbres
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Langue de la lumière et des ténèbres',
            'description' => 'Vous apprenez à parler, lire et écrire le céleste et l\'abyssal.'
        ];
    }
    
    // Niveau 14 - Diamant de l\'âme
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Diamant de l\'âme',
            'description' => 'Vous êtes immunisé contre la maladie et le poison.'
        ];
    }
    
    // Niveau 15 - Pureté du corps
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Pureté du corps',
            'description' => 'Vous êtes immunisé contre la maladie et le poison.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Tradition monastique
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Tradition monastique',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre tradition monastique.'
        ];
    }
    
    // Niveau 18 - Vide de l\'esprit
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Vide de l\'esprit',
            'description' => 'Vous êtes immunisé contre la maladie et le poison.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Perfection du corps
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Perfection du corps',
            'description' => 'Vous atteignez la perfection physique. Votre Force et votre Dextérité augmentent de 4 chacune. Votre maximum pour ces caractéristiques est maintenant 24.'
        ];
    }
    
    return $capabilities;
}

// Fonction pour obtenir les capacités d'occultiste selon le niveau
function getWarlockCapabilities($level) {
    $capabilities = [];
    
    // Niveau 1 - Maîtrise d'armures et d'armes
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'armures et d\'armes',
            'description' => 'Vous maîtrisez les armures légères et les armes courantes.'
        ];
    }
    
    // Niveau 1 - Maîtrise d'outils
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Maîtrise d\'outils',
            'description' => 'Vous maîtrisez un type d\'outil d\'artisan de votre choix.'
        ];
    }
    
    // Niveau 1 - Compétences
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Compétences',
            'description' => 'Vous choisissez deux compétences parmi : Arcana, Deception, History, Intimidation, Investigation, Nature et Religion.'
        ];
    }
    
    // Niveau 1 - Sorts
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Sorts',
            'description' => 'Vous avez appris à utiliser la magie arcanique. Voir le chapitre 10 pour les règles générales sur la magie et le chapitre 11 pour la liste des sorts d\'occultiste.'
        ];
    }
    
    // Niveau 1 - Faveur de pacte
    if ($level >= 1) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous obtenez une faveur de pacte qui reflète votre lien avec votre patron. Votre choix vous accorde des capacités au niveau 1, puis aux niveaux 3, 7, 15 et 20.'
        ];
    }
    
    // Niveau 2 - Invocations mystiques
    if ($level >= 2) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous avez appris à invoquer des pouvoirs magiques. Vous connaissez deux invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 3 - Faveur de pacte
    if ($level >= 3) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
        ];
    }
    
    // Niveau 4 - Amélioration de caractéristique
    if ($level >= 4) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 5 - Invocations mystiques
    if ($level >= 5) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez trois invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 6 - Faveur de pacte
    if ($level >= 6) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
        ];
    }
    
    // Niveau 7 - Invocations mystiques
    if ($level >= 7) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez quatre invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 8 - Amélioration de caractéristique
    if ($level >= 8) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 9 - Invocations mystiques
    if ($level >= 9) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez cinq invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 10 - Faveur de pacte
    if ($level >= 10) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
        ];
    }
    
    // Niveau 11 - Invocations mystiques
    if ($level >= 11) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez six invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 12 - Amélioration de caractéristique
    if ($level >= 12) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 13 - Invocations mystiques
    if ($level >= 13) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez sept invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 14 - Faveur de pacte
    if ($level >= 14) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
        ];
    }
    
    // Niveau 15 - Invocations mystiques
    if ($level >= 15) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez huit invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 16 - Amélioration de caractéristique
    if ($level >= 16) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 17 - Invocations mystiques
    if ($level >= 17) {
        $capabilities[] = [
            'name' => 'Invocations mystiques',
            'description' => 'Vous connaissez neuf invocations mystiques de votre choix.'
        ];
    }
    
    // Niveau 18 - Faveur de pacte
    if ($level >= 18) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
        ];
    }
    
    // Niveau 19 - Amélioration de caractéristique
    if ($level >= 19) {
        $capabilities[] = [
            'name' => 'Amélioration de caractéristique',
            'description' => 'Vous pouvez augmenter une valeur de caractéristique de votre choix de 2, ou deux valeurs de caractéristique de votre choix de 1. Vous ne pouvez pas augmenter une valeur de caractéristique au-dessus de 20 avec cette aptitude.'
        ];
    }
    
    // Niveau 20 - Faveur de pacte
    if ($level >= 20) {
        $capabilities[] = [
            'name' => 'Faveur de pacte',
            'description' => 'Vous gagnez une nouvelle capacité liée à votre faveur de pacte.'
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

// Fonction pour obtenir les serments sacrés des paladins
function getPaladinOaths() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM paladin_oaths ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getPaladinOaths: " . $e->getMessage());
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

// Fonction pour obtenir les domaines divins
function getClericDomains() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM cleric_domains ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getClericDomains: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les cercles druidiques
function getDruidCircles() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM druid_circles ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getDruidCircles: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les archétypes de rôdeur
function getRangerArchetypes() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM ranger_archetypes ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getRangerArchetypes: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les archétypes de roublard
function getRogueArchetypes() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM rogue_archetypes ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getRogueArchetypes: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les origines magiques
function getSorcererOrigins() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM sorcerer_origins ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getSorcererOrigins: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les archétypes martiaux
function getFighterArchetypes() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM fighter_archetypes ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getFighterArchetypes: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les traditions arcaniques
function getWizardTraditions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM wizard_traditions ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getWizardTraditions: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les traditions monastiques
function getMonkTraditions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM monk_traditions ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getMonkTraditions: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les faveurs de pacte
function getWarlockPacts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM warlock_pacts ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getWarlockPacts: " . $e->getMessage());
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

// Fonction pour obtenir le serment sacré d'un personnage
function getCharacterPaladinOath($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cpo.*, po.name as oath_name, po.description as oath_description,
                   po.level_3_feature, po.level_7_feature, po.level_15_feature, po.level_20_feature
            FROM character_paladin_oaths cpo
            JOIN paladin_oaths po ON cpo.oath_id = po.id
            WHERE cpo.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterPaladinOath: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir l'archétype de rôdeur d'un personnage
function getCharacterRangerArchetype($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cra.*, ra.name as archetype_name, ra.description as archetype_description,
                   ra.level_3_feature, ra.level_7_feature, ra.level_11_feature, ra.level_15_feature
            FROM character_ranger_archetypes cra
            JOIN ranger_archetypes ra ON cra.archetype_id = ra.id
            WHERE cra.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterRangerArchetype: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir l'archétype de roublard d'un personnage
function getCharacterRogueArchetype($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cra.*, ra.name as archetype_name, ra.description as archetype_description,
                   ra.level_3_feature, ra.level_9_feature, ra.level_13_feature, ra.level_17_feature
            FROM character_rogue_archetypes cra
            JOIN rogue_archetypes ra ON cra.archetype_id = ra.id
            WHERE cra.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterRogueArchetype: " . $e->getMessage());
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

// Fonction pour obtenir le domaine divin d'un personnage
function getCharacterClericDomain($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT ccd.*, cd.name as domain_name, cd.description as domain_description,
                   cd.level_1_feature, cd.level_2_feature, cd.level_6_feature, cd.level_8_feature, cd.level_17_feature
            FROM character_cleric_domain ccd
            JOIN cleric_domains cd ON ccd.domain_id = cd.id
            WHERE ccd.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterClericDomain: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir le cercle druidique d'un personnage
function getCharacterDruidCircle($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cdc.*, dc.name as circle_name, dc.description as circle_description,
                   dc.level_2_feature, dc.level_6_feature, dc.level_10_feature, dc.level_14_feature
            FROM character_druid_circle cdc
            JOIN druid_circles dc ON cdc.circle_id = dc.id
            WHERE cdc.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterDruidCircle: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir l'origine magique d'un personnage
function getCharacterSorcererOrigin($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cso.*, so.name as origin_name, so.description as origin_description,
                   so.level_1_feature, so.level_6_feature, so.level_14_feature, so.level_18_feature
            FROM character_sorcerer_origin cso
            JOIN sorcerer_origins so ON cso.origin_id = so.id
            WHERE cso.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterSorcererOrigin: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir l'archétype martial d'un personnage
function getCharacterFighterArchetype($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cfa.*, fa.name as archetype_name, fa.description as archetype_description,
                   fa.level_3_feature, fa.level_7_feature, fa.level_10_feature, fa.level_15_feature, fa.level_18_feature
            FROM character_fighter_archetype cfa
            JOIN fighter_archetypes fa ON cfa.archetype_id = fa.id
            WHERE cfa.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterFighterArchetype: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir la tradition arcanique d'un personnage
function getCharacterWizardTradition($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cwt.*, wt.name as tradition_name, wt.description as tradition_description,
                   wt.level_2_feature, wt.level_6_feature, wt.level_10_feature, wt.level_14_feature
            FROM character_wizard_tradition cwt
            JOIN wizard_traditions wt ON cwt.tradition_id = wt.id
            WHERE cwt.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterWizardTradition: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir la tradition monastique d'un personnage
function getCharacterMonkTradition($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cmt.*, mt.name as tradition_name, mt.description as tradition_description,
                   mt.level_3_feature, mt.level_6_feature, mt.level_11_feature, mt.level_17_feature
            FROM character_monk_tradition cmt
            JOIN monk_traditions mt ON cmt.tradition_id = mt.id
            WHERE cmt.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterMonkTradition: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir la faveur de pacte d'un personnage
function getCharacterWarlockPact($characterId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT cwp.*, wp.name as pact_name, wp.description as pact_description,
                   wp.level_3_feature, wp.level_7_feature, wp.level_15_feature, wp.level_20_feature
            FROM character_warlock_pact cwp
            JOIN warlock_pacts wp ON cwp.pact_id = wp.id
            WHERE cwp.character_id = ?
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getCharacterWarlockPact: " . $e->getMessage());
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

// Fonction pour sauvegarder le choix de serment sacré
function savePaladinOath($characterId, $oathId, $level3Choice = null, $level7Choice = null, $level15Choice = null, $level20Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_paladin_oaths (character_id, oath_id, level_3_choice, level_7_choice, level_15_choice, level_20_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            oath_id = VALUES(oath_id),
            level_3_choice = VALUES(level_3_choice),
            level_7_choice = VALUES(level_7_choice),
            level_15_choice = VALUES(level_15_choice),
            level_20_choice = VALUES(level_20_choice)
        ");
        return $stmt->execute([$characterId, $oathId, $level3Choice, $level7Choice, $level15Choice, $level20Choice]);
    } catch (PDOException $e) {
        error_log("Erreur savePaladinOath: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix d'archétype de rôdeur
function saveRangerArchetype($characterId, $archetypeId, $level3Choice = null, $level7Choice = null, $level11Choice = null, $level15Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_ranger_archetypes (character_id, archetype_id, level_3_choice, level_7_choice, level_11_choice, level_15_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            archetype_id = VALUES(archetype_id),
            level_3_choice = VALUES(level_3_choice),
            level_7_choice = VALUES(level_7_choice),
            level_11_choice = VALUES(level_11_choice),
            level_15_choice = VALUES(level_15_choice)
        ");
        return $stmt->execute([$characterId, $archetypeId, $level3Choice, $level7Choice, $level11Choice, $level15Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveRangerArchetype: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix d'archétype de roublard
function saveRogueArchetype($characterId, $archetypeId, $level3Choice = null, $level9Choice = null, $level13Choice = null, $level17Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_rogue_archetypes (character_id, archetype_id, level_3_choice, level_9_choice, level_13_choice, level_17_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            archetype_id = VALUES(archetype_id),
            level_3_choice = VALUES(level_3_choice),
            level_9_choice = VALUES(level_9_choice),
            level_13_choice = VALUES(level_13_choice),
            level_17_choice = VALUES(level_17_choice)
        ");
        return $stmt->execute([$characterId, $archetypeId, $level3Choice, $level9Choice, $level13Choice, $level17Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveRogueArchetype: " . $e->getMessage());
        return false;
    }
}

// Fonctions pour la hiérarchie géographique

// Fonction pour obtenir tous les mondes d'un utilisateur
function getWorldsByUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM worlds WHERE created_by = ? ORDER BY name");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getWorldsByUser: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir un monde par ID
function getWorldById($worldId, $userId = null) {
    global $pdo;
    try {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM worlds WHERE id = ? AND created_by = ?");
            $stmt->execute([$worldId, $userId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM worlds WHERE id = ?");
            $stmt->execute([$worldId]);
        }
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getWorldById: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir tous les pays d'un monde
function getCountriesByWorld($worldId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM countries WHERE world_id = ? ORDER BY name");
        $stmt->execute([$worldId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getCountriesByWorld: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir tous les pays (pour compatibilité)
function getCountries() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM countries ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getCountries: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les régions d'un pays
function getRegionsByCountry($countryId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM regions WHERE country_id = ? ORDER BY name");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getRegionsByCountry: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir tous les lieux avec leur hiérarchie géographique
function getPlacesWithGeography($campaignId = null) {
    global $pdo;
    try {
        $sql = "
            SELECT p.*, c.name as country_name, r.name as region_name, w.name as world_name
            FROM places p
            LEFT JOIN countries c ON p.country_id = c.id
            LEFT JOIN regions r ON p.region_id = r.id
            LEFT JOIN worlds w ON c.world_id = w.id
        ";
        
        if ($campaignId) {
            $sql .= " 
                INNER JOIN place_campaigns pc ON p.id = pc.place_id 
                WHERE pc.campaign_id = ?
            ";
            $sql .= " ORDER BY w.name, c.name, r.name, p.title";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$campaignId]);
        } else {
            $sql .= " ORDER BY w.name, c.name, r.name, p.title";
            $stmt = $pdo->query($sql);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur getPlacesWithGeography: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir un lieu avec sa hiérarchie géographique
function getPlaceWithGeography($placeId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as country_name, c.description as country_description,
                   r.name as region_name, r.description as region_description
            FROM places p
            LEFT JOIN countries c ON p.country_id = c.id
            LEFT JOIN regions r ON p.region_id = r.id
            WHERE p.id = ?
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur getPlaceWithGeography: " . $e->getMessage());
        return null;
    }
}

// Fonction pour vérifier si des joueurs sont présents dans un lieu
function hasPlayersInPlace($placeId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM place_players pp
            JOIN campaign_members cm ON pp.player_id = cm.user_id
            WHERE pp.place_id = ? AND cm.role = 'player'
        ");
        $stmt->execute([$placeId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Erreur hasPlayersInPlace: " . $e->getMessage());
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

// Fonction pour sauvegarder le choix de domaine divin
function saveClericDomain($characterId, $domainId, $level1Choice = null, $level2Choice = null, $level6Choice = null, $level8Choice = null, $level17Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_cleric_domain (character_id, domain_id, level_1_choice, level_2_choice, level_6_choice, level_8_choice, level_17_choice)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            domain_id = VALUES(domain_id),
            level_1_choice = VALUES(level_1_choice),
            level_2_choice = VALUES(level_2_choice),
            level_6_choice = VALUES(level_6_choice),
            level_8_choice = VALUES(level_8_choice),
            level_17_choice = VALUES(level_17_choice)
        ");
        return $stmt->execute([$characterId, $domainId, $level1Choice, $level2Choice, $level6Choice, $level8Choice, $level17Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveClericDomain: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix de cercle druidique
function saveDruidCircle($characterId, $circleId, $level2Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_druid_circle (character_id, circle_id, level_2_choice, level_6_choice, level_10_choice, level_14_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            circle_id = VALUES(circle_id),
            level_2_choice = VALUES(level_2_choice),
            level_6_choice = VALUES(level_6_choice),
            level_10_choice = VALUES(level_10_choice),
            level_14_choice = VALUES(level_14_choice)
        ");
        return $stmt->execute([$characterId, $circleId, $level2Choice, $level6Choice, $level10Choice, $level14Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveDruidCircle: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix d'origine magique
function saveSorcererOrigin($characterId, $originId, $level1Choice = null, $level6Choice = null, $level14Choice = null, $level18Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_sorcerer_origin (character_id, origin_id, level_1_choice, level_6_choice, level_14_choice, level_18_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            origin_id = VALUES(origin_id),
            level_1_choice = VALUES(level_1_choice),
            level_6_choice = VALUES(level_6_choice),
            level_14_choice = VALUES(level_14_choice),
            level_18_choice = VALUES(level_18_choice)
        ");
        return $stmt->execute([$characterId, $originId, $level1Choice, $level6Choice, $level14Choice, $level18Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveSorcererOrigin: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix d'archétype martial
function saveFighterArchetype($characterId, $archetypeId, $level3Choice = null, $level7Choice = null, $level10Choice = null, $level15Choice = null, $level18Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_fighter_archetype (character_id, archetype_id, level_3_choice, level_7_choice, level_10_choice, level_15_choice, level_18_choice)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            archetype_id = VALUES(archetype_id),
            level_3_choice = VALUES(level_3_choice),
            level_7_choice = VALUES(level_7_choice),
            level_10_choice = VALUES(level_10_choice),
            level_15_choice = VALUES(level_15_choice),
            level_18_choice = VALUES(level_18_choice)
        ");
        return $stmt->execute([$characterId, $archetypeId, $level3Choice, $level7Choice, $level10Choice, $level15Choice, $level18Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveFighterArchetype: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix de tradition arcanique
function saveWizardTradition($characterId, $traditionId, $level2Choice = null, $level6Choice = null, $level10Choice = null, $level14Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_wizard_tradition (character_id, tradition_id, level_2_choice, level_6_choice, level_10_choice, level_14_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            tradition_id = VALUES(tradition_id),
            level_2_choice = VALUES(level_2_choice),
            level_6_choice = VALUES(level_6_choice),
            level_10_choice = VALUES(level_10_choice),
            level_14_choice = VALUES(level_14_choice)
        ");
        return $stmt->execute([$characterId, $traditionId, $level2Choice, $level6Choice, $level10Choice, $level14Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveWizardTradition: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix de tradition monastique
function saveMonkTradition($characterId, $traditionId, $level3Choice = null, $level6Choice = null, $level11Choice = null, $level17Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_monk_tradition (character_id, tradition_id, level_3_choice, level_6_choice, level_11_choice, level_17_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            tradition_id = VALUES(tradition_id),
            level_3_choice = VALUES(level_3_choice),
            level_6_choice = VALUES(level_6_choice),
            level_11_choice = VALUES(level_11_choice),
            level_17_choice = VALUES(level_17_choice)
        ");
        return $stmt->execute([$characterId, $traditionId, $level3Choice, $level6Choice, $level11Choice, $level17Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveMonkTradition: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder le choix de faveur de pacte
function saveWarlockPact($characterId, $pactId, $level3Choice = null, $level7Choice = null, $level15Choice = null, $level20Choice = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_warlock_pact (character_id, pact_id, level_3_choice, level_7_choice, level_15_choice, level_20_choice)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            pact_id = VALUES(pact_id),
            level_3_choice = VALUES(level_3_choice),
            level_7_choice = VALUES(level_7_choice),
            level_15_choice = VALUES(level_15_choice),
            level_20_choice = VALUES(level_20_choice)
        ");
        return $stmt->execute([$characterId, $pactId, $level3Choice, $level7Choice, $level15Choice, $level20Choice]);
    } catch (PDOException $e) {
        error_log("Erreur saveWarlockPact: " . $e->getMessage());
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

// ===== FONCTIONS POUR LA GESTION DES ASSOCIATIONS LIEU-CAMPAGNE =====

// Fonction pour associer un lieu à une campagne
function associatePlaceToCampaign($placeId, $campaignId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO place_campaigns (place_id, campaign_id) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$placeId, $campaignId]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'association lieu-campagne: " . $e->getMessage());
        return false;
    }
}

// Fonction pour dissocier un lieu d'une campagne
function dissociatePlaceFromCampaign($placeId, $campaignId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE place_id = ? AND campaign_id = ?");
        $stmt->execute([$placeId, $campaignId]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de la dissociation lieu-campagne: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir toutes les campagnes associées à un lieu
function getCampaignsForPlace($placeId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, pc.created_at as associated_at
            FROM campaigns c
            JOIN place_campaigns pc ON c.id = pc.campaign_id
            WHERE pc.place_id = ?
            ORDER BY c.title
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des campagnes pour un lieu: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir tous les lieux associés à une campagne
function getPlacesForCampaign($campaignId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pc.created_at as associated_at
            FROM places p
            JOIN place_campaigns pc ON p.id = pc.place_id
            WHERE pc.campaign_id = ?
            ORDER BY p.title
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des lieux pour une campagne: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir tous les lieux disponibles (non associés à une campagne spécifique)
function getAvailablePlacesForCampaign($campaignId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*
            FROM places p
            WHERE p.id NOT IN (
                SELECT place_id 
                FROM place_campaigns 
                WHERE campaign_id = ?
            )
            ORDER BY p.title
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des lieux disponibles: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir toutes les campagnes disponibles (non associées à un lieu spécifique)
function getAvailableCampaignsForPlace($placeId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*
            FROM campaigns c
            WHERE c.id NOT IN (
                SELECT campaign_id 
                FROM place_campaigns 
                WHERE place_id = ?
            )
            ORDER BY c.title
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des campagnes disponibles: " . $e->getMessage());
        return [];
    }
}

// Fonction pour mettre à jour les associations d'un lieu avec plusieurs campagnes
function updatePlaceCampaignAssociations($placeId, $campaignIds) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer toutes les associations existantes pour ce lieu
        $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE place_id = ?");
        $stmt->execute([$placeId]);
        
        // Ajouter les nouvelles associations
        if (!empty($campaignIds)) {
            $stmt = $pdo->prepare("INSERT INTO place_campaigns (place_id, campaign_id) VALUES (?, ?)");
            foreach ($campaignIds as $campaignId) {
                $stmt->execute([$placeId, $campaignId]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la mise à jour des associations lieu-campagne: " . $e->getMessage());
        return false;
    }
}

// Fonction pour mettre à jour les associations d'une campagne avec plusieurs lieux
function updateCampaignPlaceAssociations($campaignId, $placeIds) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer toutes les associations existantes pour cette campagne
        $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE campaign_id = ?");
        $stmt->execute([$campaignId]);
        
        // Ajouter les nouvelles associations
        if (!empty($placeIds)) {
            $stmt = $pdo->prepare("INSERT INTO place_campaigns (place_id, campaign_id) VALUES (?, ?)");
            foreach ($placeIds as $placeId) {
                $stmt->execute([$placeId, $campaignId]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la mise à jour des associations campagne-lieu: " . $e->getMessage());
        return false;
    }
}

// ===== FONCTIONS POUR LA CRÉATION DE PERSONNAGE EN 9 ÉTAPES =====

// Fonction pour créer une nouvelle session de création de personnage
function createCharacterCreationSession($userId) {
    global $pdo;
    
    $sessionId = uniqid('char_creation_', true);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO character_creation_sessions (user_id, session_id, step, data) 
            VALUES (?, ?, 1, '{}')
        ");
        $stmt->execute([$userId, $sessionId]);
        return $sessionId;
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la session: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les données d'une session de création
function getCharacterCreationData($userId, $sessionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM character_creation_sessions 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$userId, $sessionId]);
        $session = $stmt->fetch();
        
        if ($session) {
            $session['data'] = json_decode($session['data'], true);
        }
        
        return $session;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des données de session: " . $e->getMessage());
        return false;
    }
}

// Fonction pour sauvegarder les données d'une étape
function saveCharacterCreationStep($userId, $sessionId, $step, $data) {
    global $pdo;
    
    try {
        // Récupérer les données existantes
        $existingData = getCharacterCreationData($userId, $sessionId);
        if (!$existingData) {
            return false;
        }
        
        // Fusionner les nouvelles données avec les existantes
        $mergedData = array_merge($existingData['data'], $data);
        
        $stmt = $pdo->prepare("
            UPDATE character_creation_sessions 
            SET step = ?, data = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$step, json_encode($mergedData), $userId, $sessionId]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de la sauvegarde de l'étape: " . $e->getMessage());
        return false;
    }
}

// Fonction pour finaliser la création du personnage
function finalizeCharacterCreation($userId, $sessionId) {
    global $pdo;
    
    try {
        $sessionData = getCharacterCreationData($userId, $sessionId);
        if (!$sessionData || $sessionData['step'] < 9) {
            return false;
        }
        
        $data = $sessionData['data'];
        
        $pdo->beginTransaction();
        
        // Créer le personnage
        $stmt = $pdo->prepare("
            INSERT INTO characters (
                user_id, name, race_id, class_id, background_id, level, experience_points,
                strength, dexterity, constitution, intelligence, wisdom, charisma,
                armor_class, speed, hit_points_max, hit_points_current, proficiency_bonus,
                alignment, personality_traits, ideals, bonds, flaws,
                skills, languages, money_gold, profile_photo,
                is_equipped, equipment_locked, character_locked
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $data['name'] ?? 'Nouveau Personnage',
            $data['race_id'],
            $data['class_id'],
            $data['background_id'],
            $data['level'] ?? 1,
            $data['experience_points'] ?? 0,
            $data['strength'] ?? 10,
            $data['dexterity'] ?? 10,
            $data['constitution'] ?? 10,
            $data['intelligence'] ?? 10,
            $data['wisdom'] ?? 10,
            $data['charisma'] ?? 10,
            $data['armor_class'] ?? 10,
            $data['speed'] ?? 30,
            $data['hit_points_max'] ?? 8,
            $data['hit_points_current'] ?? 8,
            $data['proficiency_bonus'] ?? 2,
            $data['alignment'] ?? 'Neutre',
            $data['personality_traits'] ?? '',
            $data['ideals'] ?? '',
            $data['bonds'] ?? '',
            $data['flaws'] ?? '',
            json_encode($data['selected_skills'] ?? []),
            json_encode($data['selected_languages'] ?? []),
            $data['money_gold'] ?? 0,
            $data['profile_photo'] ?? null,
            0, // is_equipped
            0, // equipment_locked
            0  // character_locked
        ]);
        
        $characterId = $pdo->lastInsertId();
        
        // Assigner les capacités au personnage
        if (function_exists('updateCharacterCapabilities')) {
            updateCharacterCapabilities($characterId);
        }
        
        // Supprimer la session de création
        $stmt = $pdo->prepare("DELETE FROM character_creation_sessions WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$userId, $sessionId]);
        
        $pdo->commit();
        return $characterId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur lors de la finalisation de la création: " . $e->getMessage());
        return false;
    }
}

// Fonction pour nettoyer les sessions expirées (plus de 24h)
function cleanupExpiredCharacterSessions() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM character_creation_sessions 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erreur lors du nettoyage des sessions: " . $e->getMessage());
        return false;
    }
}
