<?php

/**
 * Classe Character - Gestion des personnages D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux personnages :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des statistiques et compétences
 * - Gestion des sorts et emplacements de sorts
 * - Gestion de l'équipement
 * - Gestion de l'expérience et des niveaux
 * - Gestion des capacités spéciales
 */
class Character
{
    private $pdo;
    
    // Propriétés du personnage
    public $id;
    public $user_id;
    public $name;
    public $race_id;
    public $class_id;
    public $class_archetype_id;
    public $background_id;
    public $level;
    public $experience_points;
    
    // Statistiques de base
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    
    // Informations de combat
    public $armor_class;
    public $initiative;
    public $speed;
    public $hit_points_max;
    public $hit_points_current;
    
    // Compétences et proficiens
    public $proficiency_bonus;
    public $saving_throws;
    public $skills;
    public $languages;
    
    // Équipement et trésor
    public $equipment;
    public $money_gold;
    public $money_silver;
    public $money_copper;
    
    // Informations personnelles
    public $background;
    public $alignment;
    public $personality_traits;
    public $ideals;
    public $bonds;
    public $flaws;
    
    // Sorts
    public $spells_known;
    public $spell_slots;
    
    // Métadonnées
    public $profile_photo;
    public $is_equipped;
    public $equipment_locked;
    public $character_locked;
    public $created_at;
    public $updated_at;
    
    // Relations
    public $race_name;
    public $class_name;
    public $background_name;
    
    // Informations de campagne
    public $campaign_id;
    public $campaign_status;
    public $campaign_title;
    
    /**
     * Constructeur
     */
    public function __construct(PDO $pdo = null, array $data = [])
    {
        $this->pdo = $pdo ?: getPDO();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydratation des données
     */
    private function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Créer un nouveau personnage
     */
    public static function create(array $data, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, background_id, level, experience_points,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, initiative, speed, hit_points_max, hit_points_current,
                    proficiency_bonus, saving_throws, skills, languages,
                    equipment, money_gold, money_silver, money_copper,
                    background, alignment, personality_traits, ideals, bonds, flaws,
                    spells_known, spell_slots, profile_photo,
                    is_equipped, equipment_locked, character_locked
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['name'] ?? 'Nouveau Personnage',
                $data['race_id'],
                $data['class_id'],
                $data['background_id'] ?? null,
                $data['level'] ?? 1,
                $data['experience_points'] ?? 0,
                $data['strength'] ?? 10,
                $data['dexterity'] ?? 10,
                $data['constitution'] ?? 10,
                $data['intelligence'] ?? 10,
                $data['wisdom'] ?? 10,
                $data['charisma'] ?? 10,
                $data['armor_class'] ?? 10,
                $data['initiative'] ?? 0,
                $data['speed'] ?? 30,
                $data['hit_points_max'] ?? 8,
                $data['hit_points_current'] ?? 8,
                $data['proficiency_bonus'] ?? 2,
                $data['saving_throws'] ?? null,
                $data['skills'] ?? null,
                $data['languages'] ?? null,
                $data['equipment'] ?? null,
                $data['money_gold'] ?? 0,
                $data['money_silver'] ?? 0,
                $data['money_copper'] ?? 0,
                $data['background'] ?? null,
                $data['alignment'] ?? 'Neutre',
                $data['personality_traits'] ?? null,
                $data['ideals'] ?? null,
                $data['bonds'] ?? null,
                $data['flaws'] ?? null,
                $data['spells_known'] ?? null,
                $data['spell_slots'] ?? null,
                $data['profile_photo'] ?? null,
                $data['is_equipped'] ?? 0,
                $data['equipment_locked'] ?? 0,
                $data['character_locked'] ?? 0
            ]);
            
            $characterId = $pdo->lastInsertId();
            return self::findById($characterId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un personnage par ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, r.name as race_name, cl.name as class_name, b.name as background_name
                FROM characters c
                LEFT JOIN races r ON c.race_id = r.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN backgrounds b ON c.background_id = b.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new self($pdo, $data) : null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du personnage: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les personnages d'un utilisateur
     */
    public static function findByUserId($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, r.name as race_name, cl.name as class_name, b.name as background_name,
                       ca.campaign_id, ca.status as campaign_status, camp.title as campaign_title
                FROM characters c
                LEFT JOIN races r ON c.race_id = r.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN backgrounds b ON c.background_id = b.id
                LEFT JOIN campaign_applications ca ON c.id = ca.character_id AND ca.status = 'approved'
                LEFT JOIN campaigns camp ON ca.campaign_id = camp.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            $characters = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $characters[] = new self($pdo, $data);
            }
            
            return $characters;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des personnages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les personnages d'un utilisateur (version simplifiée - juste id et name)
     */
    public static function findSimpleByUserId($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT id, name 
                FROM characters 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des personnages simplifiés: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour le personnage
     */
    public function update(array $data)
    {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (property_exists($this, $key) && $key !== 'id') {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $this->id;
            
            $stmt = $this->pdo->prepare("
                UPDATE characters 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                // Mettre à jour les propriétés de l'objet
                foreach ($data as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le personnage
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
            return $stmt->execute([$this->id, $this->user_id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer complètement le personnage avec toutes ses données associées
     * 
     * @return bool True si succès, false sinon
     */
    public function deleteCompletely()
    {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Supprimer l'équipement du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_equipment WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 2. Supprimer les sorts appris
            $stmt = $this->pdo->prepare("DELETE FROM character_spells WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 3. Supprimer les capacités du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_capabilities WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 4. Supprimer les améliorations d'aptitudes
            $stmt = $this->pdo->prepare("DELETE FROM character_ability_improvements WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 5. Supprimer l'utilisation de rage
            $stmt = $this->pdo->prepare("DELETE FROM character_rage_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 6. Supprimer les sessions de création
            $stmt = $this->pdo->prepare("DELETE FROM character_creation_sessions WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 7. Retirer le personnage de tous les lieux
            $stmt = $this->pdo->prepare("DELETE FROM place_players WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 8. Retirer le personnage de tous les groupes
            $stmt = $this->pdo->prepare("DELETE FROM groupe_membres WHERE member_id = ? AND member_type = 'pj'");
            $stmt->execute([$this->id]);
            
            // 9. Supprimer le personnage lui-même
            $stmt = $this->pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
            $stmt->execute([$this->id, $this->user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Personnage non trouvé ou permissions insuffisantes");
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression complète du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si le personnage appartient à un utilisateur
     */
    public function belongsToUser($userId)
    {
        return $this->user_id == $userId;
    }
    
    /**
     * Calculer le bonus de compétence
     */
    public function getProficiencyBonus()
    {
        return 2 + floor(($this->level - 1) / 4);
    }
    
    /**
     * Calculer le modificateur d'une caractéristique
     */
    public function getAbilityModifier($ability)
    {
        if (!property_exists($this, $ability)) {
            return 0;
        }
        
        return floor(($this->$ability - 10) / 2);
    }
    
    /**
     * Obtenir le modificateur de force
     */
    public function getStrengthModifier()
    {
        return $this->getAbilityModifier('strength');
    }
    
    /**
     * Obtenir le modificateur de dextérité
     */
    public function getDexterityModifier()
    {
        return $this->getAbilityModifier('dexterity');
    }
    
    /**
     * Obtenir le modificateur de constitution
     */
    public function getConstitutionModifier()
    {
        return $this->getAbilityModifier('constitution');
    }
    
    /**
     * Obtenir le modificateur d'intelligence
     */
    public function getIntelligenceModifier()
    {
        return $this->getAbilityModifier('intelligence');
    }
    
    /**
     * Obtenir le modificateur de sagesse
     */
    public function getWisdomModifier()
    {
        return $this->getAbilityModifier('wisdom');
    }
    
    /**
     * Obtenir le modificateur de charisme
     */
    public function getCharismaModifier()
    {
        return $this->getAbilityModifier('charisma');
    }
    
    /**
     * Obtenir les compétences D&D avec leurs caractéristiques associées
     * 
     * @return array Tableau des compétences avec leurs caractéristiques
     */
    public static function getSkills()
    {
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

    /**
     * Obtenir toutes les compétences (y compris armure, armes, outils)
     * 
     * @return array Tableau de toutes les compétences
     */
    public static function getAllSkills()
    {
        $skills = self::getSkills();
        $armor = \Item::getArmorProficiencies();
        $weapons = \Item::getWeaponProficiencies();
        $tools = \Item::getToolProficiencies();
        
        return array_merge($skills, $armor, $weapons, $tools);
    }

    /**
     * Obtenir les compétences par catégorie
     * 
     * @return array Tableau des compétences organisées par catégorie
     */
    public static function getSkillsByCategory()
    {
        $allSkills = self::getAllSkills();
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

    /**
     * Obtenir les compétences automatiques d'une classe
     * 
     * @param int $classId ID de la classe
     * @return array Tableau des compétences de classe
     */
    public static function getClassProficiencies($classId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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
        } catch (\PDOException $e) {
            return [
                'armor' => [],
                'weapon' => [],
                'tool' => []
            ];
        }
    }

    /**
     * Obtenir les compétences par catégorie avec les compétences automatiques de classe
     * 
     * @param int|null $classId ID de la classe
     * @return array Tableau des compétences avec les compétences de classe
     */
    public static function getSkillsByCategoryWithClass($classId = null)
    {
        $skillCategories = self::getSkillsByCategory();
        $classProficiencies = $classId ? self::getClassProficiencies($classId) : ['armor' => [], 'weapon' => [], 'tool' => []];
        
        return [
            'categories' => $skillCategories,
            'classProficiencies' => $classProficiencies
        ];
    }

    /**
     * Obtenir les jets de sauvegarde
     * 
     * @return array Tableau des jets de sauvegarde
     */
    public static function getSavingThrows()
    {
        return [
            'Force' => 'strength',
            'Dextérité' => 'dexterity',
            'Constitution' => 'constitution',
            'Intelligence' => 'intelligence',
            'Sagesse' => 'wisdom',
            'Charisme' => 'charisma'
        ];
    }

    /**
     * Calculer la classe d'armure avec armure spécifique
     * 
     * @param int $dexterityModifier Modificateur de dextérité
     * @param string|null $armor Type d'armure
     * @return int Classe d'armure
     */
    public static function calculateArmorClassStatic($dexterityModifier, $armor = null)
    {
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

    /**
     * Calculer le niveau basé sur les points d'expérience
     * 
     * @param int $experiencePoints Points d'expérience
     * @return int Niveau calculé
     */
    public static function calculateLevelFromExperienceStatic($experiencePoints)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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
        } catch (\PDOException $e) {
            // En cas d'erreur, retourner le niveau 1
            return 1;
        }
    }

    /**
     * Calculer le bonus de maîtrise basé sur les points d'expérience
     * 
     * @param int $experiencePoints Points d'expérience
     * @return int Bonus de maîtrise
     */
    public static function calculateProficiencyBonusFromExperience($experiencePoints)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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
        } catch (\PDOException $e) {
            // En cas d'erreur, retourner le bonus de base
            return 2;
        }
    }

    /**
     * Obtenir les points d'expérience requis pour le niveau suivant
     * 
     * @param int $currentLevel Niveau actuel
     * @return int|null Points d'expérience requis
     */
    public static function getExperienceRequiredForNextLevel($currentLevel)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Mettre à jour le niveau et le bonus de maîtrise d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function updateCharacterLevelFromExperience($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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
            $newLevel = self::calculateLevelFromExperienceStatic($experiencePoints);
            $newProficiencyBonus = self::calculateProficiencyBonusFromExperience($experiencePoints);
            
            // Mettre à jour le personnage
            $stmt = $pdo->prepare("
                UPDATE characters 
                SET level = ?, proficiency_bonus = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newLevel, $newProficiencyBonus, $characterId]);
            
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Obtenir tous les historiques
     * 
     * @return array Tableau de tous les historiques
     */
    public static function getAllBackgrounds()
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->query("SELECT * FROM backgrounds ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un historique par ID
     * 
     * @param int $backgroundId ID de l'historique
     * @return array|null Données de l'historique
     */
    public static function getBackgroundById($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
        $stmt->execute([$backgroundId]);
        return $stmt->fetch();
    }

    /**
     * Obtenir les compétences d'un historique
     * 
     * @param int $backgroundId ID de l'historique
     * @return array Tableau des compétences de l'historique
     */
    public static function getBackgroundProficiencies($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
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

    /**
     * Obtenir toutes les langues
     * 
     * @return array Tableau de toutes les langues
     */
    public static function getAllLanguages()
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->query("SELECT * FROM languages ORDER BY type, name");
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les langues par type
     * 
     * @return array Tableau des langues organisées par type
     */
    public static function getLanguagesByType()
    {
        $pdo = \Database::getInstance()->getPdo();
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

    /**
     * Obtenir les langues d'un historique
     * 
     * @param int $backgroundId ID de l'historique
     * @return array Tableau des langues de l'historique
     */
    public static function getBackgroundLanguages($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT languages FROM backgrounds WHERE id = ?");
        $stmt->execute([$backgroundId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['languages']) {
            return [];
        }
        
        return json_decode($result['languages'], true) ?? [];
    }

    /**
     * Vérifier si une classe peut lancer des sorts
     * 
     * @param int $classId ID de la classe
     * @return bool True si la classe peut lancer des sorts
     */
    public static function canCastSpells($classId)
    {
        $pdo = \Database::getInstance()->getPdo();
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

    /**
     * Obtenir les capacités de sorts d'une classe à un niveau donné
     * 
     * @param int $classId ID de la classe
     * @param int $level Niveau du personnage
     * @param int $wisdomModifier Modificateur de sagesse
     * @param int|null $maxSpellsLearned Nombre maximum de sorts appris
     * @param int $intelligenceModifier Modificateur d'intelligence
     * @return array|null Capacités de sorts
     */
    public static function getClassSpellCapabilities($classId, $level, $wisdomModifier = 0, $maxSpellsLearned = null, $intelligenceModifier = 0)
    {
        $pdo = \Database::getInstance()->getPdo();
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

    /**
     * Obtenir les sorts disponibles pour une classe
     * 
     * @param int $classId ID de la classe
     * @return array Tableau des sorts disponibles
     */
    public static function getSpellsForClass($classId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
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

    /**
     * Obtenir les sorts d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Tableau des sorts du personnage
     */
    public static function getCharacterSpells($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
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

    /**
     * Ajouter un sort à un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @return bool Succès de l'opération
     */
    public static function addSpellToCharacter($characterId, $spellId, $prepared = false)
    {
        $pdo = \Database::getInstance()->getPdo();
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
        } catch (\PDOException $e) {
            error_log("Erreur addSpellToCharacter: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retirer un sort d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @return bool Succès de l'opération
     */
    public static function removeSpellFromCharacter($characterId, $spellId)
    {
        $pdo = \Database::getInstance()->getPdo();
        try {
            $stmt = $pdo->prepare("
                DELETE FROM character_spells 
                WHERE character_id = ? AND spell_id = ?
            ");
            $stmt->execute([$characterId, $spellId]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour l'état préparé d'un sort
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @return bool Succès de l'opération
     */
    public static function updateSpellPrepared($characterId, $spellId, $prepared)
    {
        $pdo = \Database::getInstance()->getPdo();
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
        } catch (\PDOException $e) {
            error_log("Erreur updateSpellPrepared: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les utilisations d'emplacements de sorts (méthode statique)
     * 
     * @param int $characterId ID du personnage
     * @return array Utilisation des emplacements de sorts
     */
    public static function getSpellSlotsUsageStatic($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
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
        } catch (\PDOException $e) {
            error_log("Erreur getSpellSlotsUsageStatic: " . $e->getMessage());
            return [
                'level_1_used' => 0, 'level_2_used' => 0, 'level_3_used' => 0,
                'level_4_used' => 0, 'level_5_used' => 0, 'level_6_used' => 0,
                'level_7_used' => 0, 'level_8_used' => 0, 'level_9_used' => 0
            ];
        }
    }

    /**
     * Générer l'équipement final basé sur les choix du joueur
     * 
     * @param int $classId ID de la classe
     * @param array $equipmentChoices Choix d'équipement
     * @param int|null $backgroundId ID de l'historique
     * @param array $weaponChoices Choix d'armes
     * @return array Équipement final
     */
    public static function generateFinalEquipment($classId, $equipmentChoices, $backgroundId = null, $weaponChoices = [])
    {
        $startingEquipment = self::getClassStartingEquipment($classId);
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
                    // Gestion spéciale pour les instruments de musique
                    elseif (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'instrument_choice') {
                        // Récupérer l'instrument sélectionné
                        if (isset($weaponChoices[$index][$equipmentChoices[$index]])) {
                            $selectedInstrument = $weaponChoices[$index][$equipmentChoices[$index]];
                            $finalEquipment[] = $selectedInstrument;
                        } else {
                            // Par défaut, prendre le premier instrument disponible
                            $firstInstrument = $selectedChoice['options'][0]['name'] ?? 'Instrument de musique';
                            $finalEquipment[] = $firstInstrument;
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
                    } elseif (is_array($selectedChoice) && isset($selectedChoice['type']) && $selectedChoice['type'] === 'instrument_choice') {
                        $firstInstrument = $selectedChoice['options'][0]['name'] ?? 'Instrument de musique';
                        $finalEquipment[] = $firstInstrument;
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
        // NOTE: Cette fonction est dépréciée. Utilisez generateFinalEquipmentNew() à la place.
        // L'équipement de background est maintenant géré par la table starting_equipment
        
        return [
            'equipment' => implode("\n", $finalEquipment),
            'gold' => $backgroundGold
        ];
    }

    /**
     * Ajouter l'équipement de départ choisi par le joueur
     * 
     * @param int $characterId ID du personnage
     * @param array $equipmentData Données d'équipement
     * @return bool Succès de l'opération
     */
    public static function addStartingEquipmentToCharacter($characterId, $equipmentData)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            // Parser l'équipement final
            $equipmentLines = explode("\n", $equipmentData['equipment']);
            
            foreach ($equipmentLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Si la ligne est un ID numérique, récupérer le vrai nom depuis starting_equipment
                $displayName = $line;
                if (is_numeric($line)) {
                    $stmt = $pdo->prepare("SELECT * FROM starting_equipment WHERE id = ?");
                    $stmt->execute([$line]);
                    $starting_equipment = $stmt->fetch();
                    
                    if ($starting_equipment) {
                        // Récupérer le nom selon le type
                        switch ($starting_equipment['type']) {
                            case 'weapon':
                                $stmt2 = $pdo->prepare("SELECT name FROM weapons WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: ucfirst($starting_equipment['type']);
                                break;
                                
                            case 'armor':
                                $stmt2 = $pdo->prepare("SELECT name FROM armor WHERE id = ?");
                                $stmt2->execute([$starting_equipment['type_id']]);
                                $displayName = $stmt2->fetchColumn() ?: ucfirst($starting_equipment['type']);
                                break;
                                
                            default:
                                $displayName = ucfirst($starting_equipment['type']);
                                break;
                        }
                    }
                }
                
                // Déterminer le type d'objet et les détails
                $itemType = 'other';
                $weaponId = null;
                $armorId = null;
                
                // Si on a récupéré le type depuis starting_equipment, l'utiliser
                if (is_numeric($line) && isset($starting_equipment)) {
                    switch ($starting_equipment['type']) {
                        case 'weapon':
                            $itemType = 'weapon';
                            $weaponId = $starting_equipment['type_id'];
                            break;
                        case 'armor':
                            $itemType = 'armor';
                            $armorId = $starting_equipment['type_id'];
                            break;
                        case 'sac':
                            $itemType = 'bourse';
                            break;
                        case 'outils':
                            $itemType = 'outil';
                            break;
                        case 'nourriture':
                            $itemType = 'bourse';
                            break;
                        default:
                            $itemType = 'outil';
                            break;
                    }
                } else {
                    // Vérifier si c'est une arme connue (recherche flexible)
                    $weapon = null;
                    
                    // D'abord essayer une correspondance exacte
                    $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
                    $stmt->execute([$displayName]);
                    $weapon = $stmt->fetch();
                    
                    // Si pas trouvé, essayer de chercher sans les articles et avec majuscule
                    if (!$weapon) {
                        $lineWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $displayName);
                        $lineCapitalized = ucfirst($lineWithoutArticle);
                        $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
                        $stmt->execute([$lineCapitalized]);
                        $weapon = $stmt->fetch();
                    }
                    
                    // Si toujours pas trouvé, chercher par correspondance partielle
                    if (!$weapon) {
                        $lineWithoutArticle = preg_replace('/^(une?|le|la|les|du|de|des)\s+/i', '', $displayName);
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
                        $stmt->execute([$displayName]);
                        $armor = $stmt->fetch();
                        if ($armor) {
                            $itemType = 'armor';
                            $armorId = $armor['id'];
                        }
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
                
                // Ajouter l'objet à l'inventaire du personnage dans la table items
                $stmt = $pdo->prepare("
                    INSERT INTO items 
                    (place_id, display_name, object_type, type_precis, description, 
                     is_identified, is_visible, is_equipped, position_x, position_y, 
                     is_on_map, owner_type, owner_id, item_source, quantity, 
                     equipped_slot, notes, obtained_at, obtained_from) 
                    VALUES (NULL, ?, ?, ?, NULL, 
                            1, 0, 0, 0, 0, 
                            0, 'player', ?, 'Équipement de départ', 1, 
                            NULL, 'Équipement de départ', NOW(), 'Sélection équipement de départ')
                ");
                $stmt->execute([
                    $displayName,    // display_name
                    $itemType,       // object_type
                    $itemType,       // type_precis
                    $characterId     // owner_id
                ]);
            }
            
            $pdo->commit();
            return true;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors de l'ajout de l'équipement de départ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer la classe d'armure d'un personnage (version étendue)
     * 
     * @param array $character Données du personnage
     * @param array|null $equippedArmor Armure équipée
     * @param array|null $equippedShield Bouclier équipé
     * @return int Classe d'armure calculée
     */
    public static function calculateArmorClassExtended($character, $equippedArmor = null, $equippedShield = null)
    {
        // Utiliser le modificateur de Dextérité déjà calculé dans la zone "Caractéristiques"
        $dexterityModifier = $character['dexterity_modifier'];
        
        // Récupérer le nom de la classe pour vérifier si c'est un barbare
        $pdo = \Database::getInstance()->getPdo();
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
                // Récupérer le bonus racial de constitution
                $constitutionBonus = 0;
                if (isset($character['race_id'])) {
                    $pdo = getPDO();
                    $stmt = $pdo->prepare("SELECT constitution_bonus FROM races WHERE id = ?");
                    $stmt->execute([$character['race_id']]);
                    $raceData = $stmt->fetch();
                    if ($raceData) {
                        $constitutionBonus = (int)$raceData['constitution_bonus'];
                    }
                }
                
                $tempChar = new Character();
                $tempChar->constitution = $character['constitution'] + $constitutionBonus;
                $constitutionModifier = $tempChar->getAbilityModifier('constitution');
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

    /**
     * Calculer les attaques d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param array $character Données du personnage
     * @return array Tableau des attaques
     */
    public static function calculateCharacterAttacks($characterId, $character)
    {
        $pdo = \Database::getInstance()->getPdo();
        $attacks = [];
        
        // Récupérer les armes équipées
        $stmt = $pdo->prepare("
            SELECT display_name as item_name, object_type, equipped_slot, weapon_id
            FROM items 
            WHERE owner_type = 'player' AND owner_id = ? AND is_equipped = 1 AND object_type = 'weapon'
        ");
        $stmt->execute([$characterId]);
        $equippedWeapons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($equippedWeapons as $weapon) {
            // Récupérer les détails de l'arme depuis la table weapons
            if ($weapon['weapon_id']) {
                $stmt = $pdo->prepare("SELECT * FROM weapons WHERE id = ?");
                $stmt->execute([$weapon['weapon_id']]);
                $weaponDetails = $stmt->fetch();
            } else {
                // Si pas d'ID d'arme, essayer de trouver par nom
                $stmt = $pdo->prepare("SELECT * FROM weapons WHERE name = ?");
                $stmt->execute([$weapon['item_name']]);
                $weaponDetails = $stmt->fetch();
            }
            
            if ($weaponDetails) {
                // Calculer le bonus d'attaque
                $attackBonus = 0;
                
                // Bonus de caractéristique (Force pour armes de mêlée, Dextérité pour armes à distance)
                if (strpos($weaponDetails['properties'], 'finesse') !== false) {
                    // Arme de finesse : utiliser Force ou Dextérité (le plus élevé)
                    $attackBonus += max($character['strength_modifier'] ?? 0, $character['dexterity_modifier'] ?? 0);
                } elseif (strpos($weaponDetails['properties'], 'distance') !== false || 
                         strpos($weaponDetails['properties'], 'lancer') !== false) {
                    // Arme à distance : Dextérité
                    $attackBonus += $character['dexterity_modifier'] ?? 0;
                } else {
                    // Arme de mêlée : Force
                    $attackBonus += $character['strength_modifier'] ?? 0;
                }
                
                // Bonus de maîtrise
                $attackBonus += $character['proficiency_bonus'] ?? 0;
                
                // Déterminer le type d'attaque
                $attackType = 'main_hand';
                if (strpos($weaponDetails['properties'], 'deux mains') !== false) {
                    $attackType = 'two_handed';
                } elseif ($weapon['equipped_slot'] === 'off_hand') {
                    $attackType = 'off_hand';
                }
                
                $attacks[] = [
                    'name' => $weaponDetails['name'],
                    'damage' => $weaponDetails['damage'],
                    'attack_bonus' => $attackBonus,
                    'type' => $attackType,
                    'properties' => $weaponDetails['properties']
                ];
            }
        }
        
        return $attacks;
    }

    /**
     * Obtenir l'équipement équipé d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Tableau de l'équipement équipé
     */
    public static function getCharacterEquippedItems($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT display_name as item_name, object_type as item_type, quantity, notes 
            FROM items 
            WHERE owner_type = 'player' AND owner_id = ? AND is_equipped = 1
            ORDER BY object_type, display_name
        ");
        $stmt->execute([$characterId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupérer l'équipement équipé du personnage dans un format structuré
     * 
     * @param int $characterId ID du personnage
     * @return array Tableau associatif avec les emplacements d'équipement
     */
    public static function getCharacterEquippedItemsStructured($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT display_name as item_name, object_type as item_type, equipped_slot
            FROM items 
            WHERE owner_type = 'player' AND owner_id = ? AND is_equipped = 1
        ");
        $stmt->execute([$characterId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $equippedItems = [
            'main_hand' => '',
            'off_hand' => '',
            'armor' => '',
            'shield' => '',
            'helmet' => '',
            'gloves' => '',
            'boots' => '',
            'ring1' => '',
            'ring2' => '',
            'amulet' => '',
            'cloak' => ''
        ];
        
        foreach ($items as $item) {
            $slot = $item['equipped_slot'] ?? '';
            if (!empty($slot) && isset($equippedItems[$slot])) {
                $equippedItems[$slot] = $item['item_name'];
            }
        }
        
        return $equippedItems;
    }

    /**
     * Synchroniser l'équipement de base avec l'équipement du personnage
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function syncBaseEquipmentToCharacterEquipment($characterId)
    {
        // Cette fonction nécessite une logique complexe
        // Pour l'instant, on retourne true pour maintenir la compatibilité
        // TODO: Implémenter la logique complète
        return true;
    }

    
    /**
     * Calculer la classe d'armure
     */
    public function calculateArmorClass()
    {
        // Classe d'armure de base
        $ac = 10 + $this->getDexterityModifier();
        
        // TODO: Ajouter les bonus d'armure, bouclier, etc.
        // Cette logique sera implémentée plus tard avec la gestion de l'équipement
        
        return $ac;
    }
    
    /**
     * Calculer les points de vie maximum
     */
    public function calculateMaxHitPoints()
    {
        $conMod = $this->getConstitutionModifier();
        $hitDie = 8; // TODO: Récupérer depuis la classe
        
        // Points de vie au niveau 1
        $hp = $hitDie + $conMod;
        
        // Points de vie pour les niveaux suivants
        for ($i = 2; $i <= $this->level; $i++) {
            $hp += floor($hitDie / 2) + 1 + $conMod; // Moyenne arrondie vers le haut
        }
        
        return max(1, $hp);
    }
    
    /**
     * Ajouter de l'expérience
     */
    public function addExperience($amount)
    {
        $this->experience_points += $amount;
        $this->updateLevelFromExperience();
        return $this->update(['experience_points' => $this->experience_points]);
    }
    
    /**
     * Mettre à jour le niveau basé sur l'expérience
     */
    public function updateLevelFromExperience()
    {
        $newLevel = $this->calculateLevelFromExperience($this->experience_points);
        
        if ($newLevel != $this->level) {
            $this->level = $newLevel;
            $this->proficiency_bonus = $this->getProficiencyBonus();
            $this->hit_points_max = $this->calculateMaxHitPoints();
            
            // Si le personnage a plus de PV actuels que maximum, ajuster
            if ($this->hit_points_current > $this->hit_points_max) {
                $this->hit_points_current = $this->hit_points_max;
            }
            
            $this->update([
                'level' => $this->level,
                'proficiency_bonus' => $this->proficiency_bonus,
                'hit_points_max' => $this->hit_points_max,
                'hit_points_current' => $this->hit_points_current
            ]);
        }
    }
    
    /**
     * Calculer le niveau basé sur l'expérience
     */
    private function calculateLevelFromExperience($xp)
    {
        $levels = [
            0, 300, 900, 2700, 6500, 14000, 23000, 34000, 48000, 64000,
            85000, 100000, 120000, 140000, 165000, 195000, 225000, 265000, 305000, 355000
        ];
        
        for ($level = 20; $level >= 1; $level--) {
            if ($xp >= $levels[$level - 1]) {
                return $level;
            }
        }
        
        return 1;
    }
    
    /**
     * Obtenir les sorts du personnage
     */
    public function getSpells()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, cs.prepared, cs.known
                FROM character_spells cs
                JOIN spells s ON cs.spell_id = s.id
                WHERE cs.character_id = ?
                ORDER BY s.level, s.name
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des sorts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajouter un sort au personnage
     */
    public function addSpell($spellId, $prepared = false, $known = true)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_spells (character_id, spell_id, prepared, known)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE prepared = ?, known = ?
            ");
            
            return $stmt->execute([$this->id, $spellId, $prepared, $known, $prepared, $known]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retirer un sort du personnage
     */
    public function removeSpell($spellId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_spells WHERE character_id = ? AND spell_id = ?");
            return $stmt->execute([$this->id, $spellId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'utilisation des emplacements de sorts
     */
    public function getSpellSlotsUsage()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT level, used
                FROM character_spell_slots_usage
                WHERE character_id = ?
                ORDER BY level
            ");
            $stmt->execute([$this->id]);
            
            $usage = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usage[$row['level']] = $row['used'];
            }
            
            return $usage;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation des sorts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Utiliser un emplacement de sort
     */
    public function useSpellSlot($level)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_spell_slots_usage (character_id, level, used)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE used = used + 1
            ");
            
            return $stmt->execute([$this->id, $level]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'utilisation de l'emplacement de sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Libérer un emplacement de sort
     */
    public function freeSpellSlot($level)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE character_spell_slots_usage
                SET used = GREATEST(0, used - 1)
                WHERE character_id = ? AND level = ?
            ");
            
            return $stmt->execute([$this->id, $level]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la libération de l'emplacement de sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réinitialiser l'utilisation des emplacements de sorts
     */
    public function resetSpellSlotsUsage()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_spell_slots_usage WHERE character_id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation des emplacements de sorts: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'équipement équipé du personnage
     */
    public function getEquippedItems()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ce.*, e.name, e.type, e.description, e.rarity
                FROM character_equipment ce
                JOIN equipment e ON ce.equipment_id = e.id
                WHERE ce.character_id = ? AND ce.equipped = 1
                ORDER BY ce.slot
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Équiper un objet
     */
    public function equipItem($itemName, $itemType, $slot)
    {
        try {
            // Déséquiper l'objet actuellement dans ce slot
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = ?
            ");
            $stmt->execute([$this->id, $slot]);
            
            // Équiper le nouvel objet
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 1, equipped_slot = ?
                WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = ?
            ");
            
            return $stmt->execute([$slot, $this->id, $itemName, $itemType]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'équipement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Déséquiper un objet
     */
    public function unequipItem($itemName)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND display_name = ?
            ");
            
            return $stmt->execute([$this->id, $itemName]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors du déséquipement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'utilisation de la rage (pour les barbares)
     */
    public function getRageUsage()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT used, max_uses
                FROM character_rage_usage
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: ['used' => 0, 'max_uses' => 0];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation de la rage: " . $e->getMessage());
            return ['used' => 0, 'max_uses' => 0];
        }
    }
    
    /**
     * Utiliser la rage
     */
    public function useRage()
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_rage_usage (character_id, used, max_uses)
                VALUES (?, 1, 2)
                ON DUPLICATE KEY UPDATE used = used + 1
            ");
            
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'utilisation de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Libérer la rage
     */
    public function freeRage()
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE character_rage_usage
                SET used = GREATEST(0, used - 1)
                WHERE character_id = ?
            ");
            
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la libération de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réinitialiser l'utilisation de la rage
     */
    public function resetRageUsage()
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE character_rage_usage SET used = 0 WHERE character_id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les capacités spéciales du personnage
     */
    public function getCapabilities()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT cc.*, c.name, c.description, c.type
                FROM character_capabilities cc
                JOIN capabilities c ON cc.capability_id = c.id
                WHERE cc.character_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajouter une capacité au personnage
     */
    public function addCapability($capabilityId, $source = 'class', $sourceId = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_capabilities (character_id, capability_id, source, source_id)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE source = ?, source_id = ?
            ");
            
            return $stmt->execute([$this->id, $capabilityId, $source, $sourceId, $source, $sourceId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de la capacité: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retirer une capacité du personnage
     */
    public function removeCapability($capabilityId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_capabilities WHERE character_id = ? AND capability_id = ?");
            return $stmt->execute([$this->id, $capabilityId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la capacité: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les améliorations de caractéristiques du personnage
     */
    public function getAbilityImprovements()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT strength_bonus, dexterity_bonus, constitution_bonus, 
                       intelligence_bonus, wisdom_bonus, charisma_bonus
                FROM character_ability_improvements
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return [
                    'strength' => 0,
                    'dexterity' => 0,
                    'constitution' => 0,
                    'intelligence' => 0,
                    'wisdom' => 0,
                    'charisma' => 0
                ];
            }
            
            return [
                'strength' => (int)$row['strength_bonus'],
                'dexterity' => (int)$row['dexterity_bonus'],
                'constitution' => (int)$row['constitution_bonus'],
                'intelligence' => (int)$row['intelligence_bonus'],
                'wisdom' => (int)$row['wisdom_bonus'],
                'charisma' => (int)$row['charisma_bonus']
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des améliorations: " . $e->getMessage());
            return [
                'strength' => 0,
                'dexterity' => 0,
                'constitution' => 0,
                'intelligence' => 0,
                'wisdom' => 0,
                'charisma' => 0
            ];
        }
    }
    
    /**
     * Sauvegarder les améliorations de caractéristiques
     */
    public function saveAbilityImprovements($improvements)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer les améliorations existantes
            $stmt = $this->pdo->prepare("DELETE FROM character_ability_improvements WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // Ajouter les nouvelles améliorations
            $stmt = $this->pdo->prepare("
                INSERT INTO character_ability_improvements 
                (character_id, strength_bonus, dexterity_bonus, constitution_bonus, 
                 intelligence_bonus, wisdom_bonus, charisma_bonus)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->id,
                (int)($improvements['strength'] ?? 0),
                (int)($improvements['dexterity'] ?? 0),
                (int)($improvements['constitution'] ?? 0),
                (int)($improvements['intelligence'] ?? 0),
                (int)($improvements['wisdom'] ?? 0),
                (int)($improvements['charisma'] ?? 0)
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la sauvegarde des améliorations: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculer les caractéristiques finales avec les améliorations
     */
    public function calculateFinalAbilities($abilityImprovements = null)
    {
        if ($abilityImprovements === null) {
            $abilityImprovements = $this->getAbilityImprovements();
        }
        
        // Récupérer les bonus raciaux
        $race = Race::findById($this->race_id);
        $racialBonuses = [
            'strength' => $race ? ($race->strength_bonus ?? 0) : 0,
            'dexterity' => $race ? ($race->dexterity_bonus ?? 0) : 0,
            'constitution' => $race ? ($race->constitution_bonus ?? 0) : 0,
            'intelligence' => $race ? ($race->intelligence_bonus ?? 0) : 0,
            'wisdom' => $race ? ($race->wisdom_bonus ?? 0) : 0,
            'charisma' => $race ? ($race->charisma_bonus ?? 0) : 0,
        ];
        
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        $finalAbilities = [];
        
        foreach ($abilities as $ability) {
            $base = $this->$ability;
            $improvement = $abilityImprovements[$ability] ?? 0;
            $racialBonus = $racialBonuses[$ability] ?? 0;
            $finalAbilities[$ability] = $base + $improvement + $racialBonus;
        }
        
        return $finalAbilities;
    }
    
    /**
     * Obtenir les améliorations de caractéristiques du personnage (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return array Améliorations par caractéristique
     */
    public static function getCharacterAbilityImprovements($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return [
                'strength' => 0,
                'dexterity' => 0,
                'constitution' => 0,
                'intelligence' => 0,
                'wisdom' => 0,
                'charisma' => 0
            ];
        }
        return $character->getAbilityImprovements();
    }
    
    /**
     * Calculer les caractéristiques finales avec les améliorations (version statique)
     * 
     * @param array $character Données du personnage
     * @param array $abilityImprovements Améliorations de caractéristiques
     * @return array Caractéristiques finales
     */
    public static function calculateFinalAbilitiesStatic($character, $abilityImprovements = null)
    {
        $characterObj = self::findById($character['id']);
        if (!$characterObj) {
            return $character; // Retourner les caractéristiques de base si le personnage n'existe pas
        }
        return $characterObj->calculateFinalAbilities($abilityImprovements);
    }
    
    /**
     * Obtenir les points d'amélioration disponibles pour un niveau donné
     * 
     * @param int $level Niveau du personnage
     * @return int Nombre de points disponibles
     */
    public static function getAvailableAbilityPoints($level)
    {
        // Points d'amélioration aux niveaux 4, 8, 12, 16, 19
        $points = 0;
        if ($level >= 4) $points += 2;
        if ($level >= 8) $points += 2;
        if ($level >= 12) $points += 2;
        if ($level >= 16) $points += 2;
        if ($level >= 19) $points += 2;
        return $points;
    }
    
    /**
     * Calculer les points d'amélioration utilisés
     * 
     * @param array $abilityImprovements Améliorations de caractéristiques
     * @return int Nombre de points utilisés
     */
    public static function getUsedAbilityPoints($abilityImprovements)
    {
        $used = 0;
        foreach ($abilityImprovements as $improvement) {
            $used += $improvement;
        }
        return $used;
    }
    
    /**
     * Calculer les points d'amélioration restants
     * 
     * @param int $level Niveau du personnage
     * @param array $abilityImprovements Améliorations de caractéristiques
     * @return int Nombre de points restants
     */
    public static function getRemainingAbilityPoints($level, $abilityImprovements)
    {
        $available = self::getAvailableAbilityPoints($level);
        $used = self::getUsedAbilityPoints($abilityImprovements);
        return $available - $used;
    }
    
    /**
     * Obtenir les informations de campagne du personnage
     */
    public function getCampaignInfo()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ca.*, c.title as campaign_title, c.description as campaign_description
                FROM campaign_applications ca
                JOIN campaigns c ON ca.campaign_id = c.id
                WHERE ca.character_id = ? AND ca.status = 'approved'
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations de campagne: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convertir en tableau pour l'affichage
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'race_id' => $this->race_id,
            'class_id' => $this->class_id,
            'background_id' => $this->background_id,
            'level' => $this->level,
            'experience_points' => $this->experience_points,
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma,
            'armor_class' => $this->armor_class,
            'initiative' => $this->initiative,
            'speed' => $this->speed,
            'hit_points_max' => $this->hit_points_max,
            'hit_points_current' => $this->hit_points_current,
            'proficiency_bonus' => $this->proficiency_bonus,
            'saving_throws' => $this->saving_throws,
            'skills' => $this->skills,
            'languages' => $this->languages,
            'equipment' => $this->equipment,
            'money_gold' => $this->money_gold,
            'money_silver' => $this->money_silver,
            'money_copper' => $this->money_copper,
            'background' => $this->background,
            'alignment' => $this->alignment,
            'personality_traits' => $this->personality_traits,
            'ideals' => $this->ideals,
            'bonds' => $this->bonds,
            'flaws' => $this->flaws,
            'spells_known' => $this->spells_known,
            'spell_slots' => $this->spell_slots,
            'profile_photo' => $this->profile_photo,
            'is_equipped' => $this->is_equipped,
            'equipment_locked' => $this->equipment_locked,
            'character_locked' => $this->character_locked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'race_name' => $this->race_name,
            'class_name' => $this->class_name,
            'background_name' => $this->background_name,
            'campaign_id' => $this->campaign_id,
            'campaign_status' => $this->campaign_status,
            'campaign_title' => $this->campaign_title
        ];
    }
    
    /**
     * Vérifier si le personnage a déjà choisi son équipement de départ
     * 
     * @return bool True si l'équipement de départ a déjà été choisi, false sinon
     */
    public function hasStartingEquipment()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM character_equipment 
                WHERE character_id = ? 
                AND obtained_from = 'Équipement de départ'
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'équipement de départ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Utiliser un emplacement de sort (version statique)
     * 
     * @param int $characterId ID du personnage
     * @param int $level Niveau du sort
     * @return bool Succès de l'opération
     */
    public static function useSpellSlotStatic($characterId, $level)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->useSpellSlot($level);
    }

    /**
     * Libérer un emplacement de sort (version statique)
     * 
     * @param int $characterId ID du personnage
     * @param int $level Niveau du sort
     * @return bool Succès de l'opération
     */
    public static function freeSpellSlotStatic($characterId, $level)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->freeSpellSlot($level);
    }

    /**
     * Réinitialiser tous les emplacements de sorts utilisés (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function resetSpellSlotsUsageStatic($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->resetSpellSlotsUsage();
    }

    /**
     * Obtenir l'utilisation des rages d'un personnage (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return int Nombre de rages utilisées
     */
    public static function getRageUsageStatic($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return 0;
        }
        return $character->getRageUsage();
    }

    /**
     * Utiliser une rage (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function useRageStatic($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->useRage();
    }

    /**
     * Libérer une rage (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function freeRageStatic($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->freeRage();
    }

    /**
     * Réinitialiser toutes les rages utilisées (version statique)
     * 
     * @param int $characterId ID du personnage
     * @return bool Succès de l'opération
     */
    public static function resetRageUsageStatic($characterId)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->resetRageUsage();
    }

    /**
     * Équiper un objet (version statique)
     * 
     * @param int $characterId ID du personnage
     * @param string $itemName Nom de l'objet
     * @param string $itemType Type de l'objet
     * @param string $slot Emplacement
     * @return bool Succès de l'opération
     */
    public static function equipItemStatic($characterId, $itemName, $itemType, $slot)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->equipItem($itemName, $itemType, $slot);
    }

    /**
     * Déséquiper un objet (version statique)
     * 
     * @param int $characterId ID du personnage
     * @param string $itemName Nom de l'objet
     * @return bool Succès de l'opération
     */
    public static function unequipItemStatic($characterId, $itemName)
    {
        $character = self::findById($characterId);
        if (!$character) {
            return false;
        }
        return $character->unequipItem($itemName);
    }

    /**
     * Mettre à jour les points de vie actuels d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $newHitPoints Nouveaux points de vie actuels
     * @return bool Succès de l'opération
     */
    public static function updateHitPoints($characterId, $newHitPoints)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
            return $stmt->execute([$newHitPoints, $characterId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour des points de vie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour les points d'expérience d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $newExperiencePoints Nouveaux points d'expérience
     * @return bool Succès de l'opération
     */
    public static function updateExperiencePoints($characterId, $newExperiencePoints)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE characters SET experience_points = ? WHERE id = ?");
            return $stmt->execute([$newExperiencePoints, $characterId]);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour des points d'expérience: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le nombre maximum de rages pour une classe et un niveau
     * 
     * @param int $classId ID de la classe
     * @param int $level Niveau du personnage
     * @return int Nombre maximum de rages
     */
    public static function getMaxRages($classId, $level)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT rages FROM class_evolution WHERE class_id = ? AND level = ?");
            $stmt->execute([$classId, $level]);
            $evolution = $stmt->fetch();
            return $evolution ? (int)$evolution['rages'] : 0;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du nombre maximum de rages: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer l'équipement magique complet d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Liste de l'équipement magique
     */
    public static function getCharacterMagicalEquipment($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    i.*,
                    mi.nom as magical_item_nom, 
                    mi.type as magical_item_type, 
                    mi.description as magical_item_description, 
                    mi.source as magical_item_source,
                    i.display_name as item_name,
                    i.object_type as item_type
                FROM items i
                LEFT JOIN magical_items mi ON i.magical_item_id = mi.csv_id
                WHERE i.owner_type = 'player' AND i.owner_id = ? 
                AND (i.magical_item_id IS NULL OR i.magical_item_id NOT IN (SELECT csv_id FROM poisons))
                ORDER BY i.obtained_at DESC
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement magique: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'équipement d'un personnage depuis la table items
     * 
     * @param int $characterId ID du personnage
     * @return array Liste de l'équipement
     */
    public static function getCharacterItems($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    display_name as item_name,
                    object_type as item_type,
                    type_precis,
                    description as item_description,
                    is_equipped as equipped,
                    item_source,
                    quantity,
                    equipped_slot,
                    notes,
                    obtained_at,
                    obtained_from,
                    magical_item_id,
                    weapon_id,
                    armor_id,
                    poison_id
                FROM items 
                WHERE owner_type = 'player' AND owner_id = ?
                ORDER BY obtained_at DESC
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des objets du personnage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les informations d'un poison par son ID CSV
     * 
     * @param int $csvId ID CSV du poison
     * @return array|null Informations du poison ou null si non trouvé
     */
    public static function getPoisonInfo($csvId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des informations du poison: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les informations d'un objet magique par son ID CSV
     * 
     * @param int $csvId ID CSV de l'objet magique
     * @return array|null Informations de l'objet magique ou null si non trouvé
     */
    public static function getMagicalItemInfo($csvId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des informations de l'objet magique: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les poisons d'un personnage depuis la table items
     * 
     * @param int $characterId ID du personnage
     * @return array Liste des poisons du personnage
     */
    public static function getCharacterPoisons($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT i.*, p.nom as poison_nom, p.type as poison_type, p.description as poison_description, p.source as poison_source
                FROM items i
                JOIN poisons p ON i.poison_id = p.csv_id
                WHERE i.owner_type = 'player' AND i.owner_id = ? 
                AND i.poison_id IS NOT NULL
                ORDER BY i.obtained_at DESC
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des poisons du personnage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les personnages d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param bool $equippedOnly Si true, ne retourne que les personnages équipés
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des personnages
     */
    public static function getCharactersByUser($userId, $equippedOnly = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT id, name FROM characters WHERE user_id = ?";
            if ($equippedOnly) {
                $sql .= " AND is_equipped = 1";
            }
            $sql .= " ORDER BY name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si l'équipement de départ a été choisi pour un personnage
     * 
     * @param int $characterId ID du personnage
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return int Nombre d'objets d'équipement de départ
     */
    public static function getStartingEquipmentCount($characterId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM items 
                WHERE owner_type = 'player' AND owner_id = ? 
                AND obtained_from = 'Équipement de départ'
            ");
            $stmt->execute([$characterId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'équipement de départ: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Getters de base pour les propriétés essentielles
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getLevel()
    {
        return $this->level;
    }
    
    public function getClassId()
    {
        return $this->class_id;
    }
    
    public function getRaceId()
    {
        return $this->race_id;
    }
    
    public function getBackgroundId()
    {
        return $this->background_id;
    }
    
    public function getExperiencePoints()
    {
        return $this->experience_points;
    }
    
    public function getHitPointsMax()
    {
        return $this->hit_points_max;
    }
    
    public function getHitPointsCurrent()
    {
        return $this->hit_points_current;
    }
    
    public function getIsEquipped()
    {
        return $this->is_equipped ?? false;
    }

    /**
     * Obtenir l'archetype du personnage
     * @return array|null L'archetype avec ses détails ou null si aucun
     */
    public function getArchetype()
    {
        try {
            if (!$this->class_archetype_id) {
                return null;
            }

            $stmt = $this->pdo->prepare("
                SELECT ca.*, c.name as class_name 
                FROM class_archetypes ca 
                JOIN classes c ON ca.class_id = c.id 
                WHERE ca.id = ?
            ");
            $stmt->execute([$this->class_archetype_id]);
            
            $archetype = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($archetype) {
                // Ajouter le type d'archetype selon la classe
                $archetype['archetype_type'] = $this->getArchetypeType($archetype['class_name']);
            }
            
            return $archetype;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'archetype: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le type d'archetype selon la classe
     * @param string $className Nom de la classe
     * @return string Type d'archetype
     */
    private function getArchetypeType($className)
    {
        switch ($className) {
            case 'Barbare': return 'Voie primitive';
            case 'Paladin': return 'Serment sacré';
            case 'Rôdeur': return 'Archétype de rôdeur';
            case 'Roublard': return 'Archétype de roublard';
            case 'Barde': return 'Collège bardique';
            case 'Clerc': return 'Domaine divin';
            case 'Druide': return 'Cercle druidique';
            case 'Ensorceleur': return 'Origine magique';
            case 'Guerrier': return 'Archétype martial';
            case 'Magicien': return 'Tradition arcanique';
            case 'Moine': return 'Tradition monastique';
            case 'Occultiste': return 'Faveur de pacte';
            default: return 'Spécialisation';
        }
    }

    /**
     * Récupérer les personnages d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des personnages
     */
    public static function getByUserId($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }

}
