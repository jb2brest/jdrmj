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
    public $gold;
    public $silver;
    public $copper;
    
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
            // Générer les compétences et langues de base si elles ne sont pas fournies
            $skills = $data['skills'] ?? null;
            $languages = $data['languages'] ?? null;
            
            if ($skills === null || $languages === null) {
                // Créer un objet temporaire pour générer les compétences et langues
                $tempCharacter = new self($pdo, [
                    'race_id' => $data['race_id'],
                    'class_id' => $data['class_id']
                ]);
                
                if ($skills === null) {
                    // Utiliser les compétences choisies par le joueur si disponibles
                    if (isset($data['selected_skills']) && !empty($data['selected_skills'])) {
                        $skills = json_encode($data['selected_skills']);
                    } else {
                        $skills = json_encode($tempCharacter->generateBaseSkills());
                    }
                }
                if ($languages === null) {
                    // Utiliser les langues choisies par le joueur si disponibles
                    if (isset($data['selected_languages']) && !empty($data['selected_languages'])) {
                        $languages = json_encode($data['selected_languages']);
                    } else {
                        $languages = json_encode($tempCharacter->generateBaseLanguages());
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, background_id, level, experience_points,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, initiative, speed, hit_points_max, hit_points_current,
                    proficiency_bonus, saving_throws, skills, languages,
                    equipment, gold, silver, copper,
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
                $skills,
                $languages,
                $data['equipment'] ?? null,
                $data['gold'] ?? 0,
                $data['silver'] ?? 0,
                $data['copper'] ?? 0,
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
            
            // 1. Supprimer l'équipement du personnage (table items)
            $stmt = $this->pdo->prepare("DELETE FROM items WHERE owner_type = 'player' AND owner_id = ?");
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
            
            // 6. Supprimer les compétences du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_skills WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 7. Supprimer les langues du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_languages WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 8. Supprimer les sessions de création (utilise user_id)
            $stmt = $this->pdo->prepare("DELETE FROM character_creation_sessions WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            
            // 9. Supprimer l'utilisation des emplacements de sorts
            $stmt = $this->pdo->prepare("DELETE FROM spell_slots_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 10. Supprimer l'utilisation de rage (table générale)
            $stmt = $this->pdo->prepare("DELETE FROM rage_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 11. Supprimer les candidatures aux campagnes
            $stmt = $this->pdo->prepare("DELETE FROM campaign_applications WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 12. Retirer le personnage de tous les lieux
            $stmt = $this->pdo->prepare("DELETE FROM place_players WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 13. Retirer le personnage de tous les groupes
            $stmt = $this->pdo->prepare("DELETE FROM groupe_membres WHERE member_id = ? AND member_type = 'pj'");
            $stmt->execute([$this->id]);
            
            // 14. Supprimer le personnage lui-même
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
                     is_on_map, owner_type, owner_id, armor_id, weapon_id, poison_id, shield_id,
                     item_source, quantity, equipped_slot, notes, obtained_at, obtained_from) 
                    VALUES (NULL, ?, ?, ?, NULL, 
                            1, 0, 0, 0, 0, 
                            0, 'player', ?, ?, ?, ?, NULL,
                            'Équipement de départ', 1, NULL, 'Équipement de départ', NOW(), 'Sélection équipement de départ')
                ");
                $stmt->execute([
                    $displayName,    // display_name
                    $itemType,       // object_type
                    $itemType,       // type_precis
                    $characterId,    // owner_id
                    $armorId,        // armor_id
                    $weaponId,       // weapon_id
                    null,            // poison_id
                    null             // shield_id
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
                SELECT cc.*, c.name, c.description, c.type_id, c.source_type, ct.name as type_name
                FROM character_capabilities cc
                JOIN capabilities c ON cc.capability_id = c.id
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE cc.character_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si aucune capacité n'est assignée, générer automatiquement les capacités de base
            if (empty($result)) {
                return $this->generateBaseCapabilities();
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Générer automatiquement les capacités de base du personnage
     * 
     * @return array Liste des capacités générées
     */
    private function generateBaseCapabilities()
    {
        $pdo = \Database::getInstance()->getPdo();
        $capabilities = [];
        
        try {
            // Récupérer les capacités de classe
            $stmt = $pdo->prepare("
                SELECT c.name, c.description, c.source_type, ct.name as type_name
                FROM capabilities c
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE c.source_type = 'class' AND c.source_id = ? AND c.level_requirement <= ?
                ORDER BY c.level_requirement, c.name
            ");
            $stmt->execute([$this->class_id, $this->level]);
            $classCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les capacités raciales
            $stmt = $pdo->prepare("
                SELECT c.name, c.description, c.source_type, ct.name as type_name
                FROM capabilities c
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE c.source_type = 'race' AND c.source_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$this->race_id]);
            $raceCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combiner toutes les capacités
            $capabilities = array_merge($classCapabilities, $raceCapabilities);
            
            // Ajouter les capacités d'historique si disponible
            if ($this->background_id) {
                $stmt = $pdo->prepare("
                    SELECT c.name, c.description, c.source_type, ct.name as type_name
                    FROM capabilities c
                    LEFT JOIN capability_types ct ON c.type_id = ct.id
                    WHERE c.source_type = 'background' AND c.source_id = ?
                    ORDER BY c.name
                ");
                $stmt->execute([$this->background_id]);
                $backgroundCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $capabilities = array_merge($capabilities, $backgroundCapabilities);
            }
            
            // Ajouter les métadonnées manquantes
            foreach ($capabilities as &$capability) {
                $capability['is_active'] = 1;
                $capability['learned_at'] = date('Y-m-d H:i:s');
                // S'assurer que le champ source est défini
                if (!isset($capability['source'])) {
                    $capability['source'] = $capability['source_type'];
                }
            }
            
            return $capabilities;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la génération des capacités de base du personnage: " . $e->getMessage());
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
            'class_archetype_id' => $this->class_archetype_id,
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
            'gold' => $this->gold,
            'silver' => $this->silver,
            'copper' => $this->copper,
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
     * Supprimer un objet du personnage
     * 
     * @param int $itemId ID de l'objet
     * @return bool Succès de l'opération
     */
    public static function deleteItem($itemId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            return $stmt->execute([$itemId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'objet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer l'équipement d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Liste de l'équipement
     */
    public static function getCharacterEquipment($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    display_name as item_name,
                    object_type as item_type,
                    type_precis as item_subtype,
                    description as item_description,
                    is_equipped as equipped,
                    equipped_slot,
                    quantity,
                    notes,
                    obtained_at,
                    obtained_from
                FROM items 
                WHERE owner_type = 'player' AND owner_id = ? 
                ORDER BY display_name ASC
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les capacités d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Liste des capacités
     */
    public static function getCharacterCapabilities($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        // Debug temporaire
        error_log("Debug Character::getCharacterCapabilities - Character ID: " . $characterId);
        
        try {
            $stmt = $pdo->prepare("
                SELECT cc.*, c.name, c.description 
                FROM character_capabilities cc
                LEFT JOIN capabilities c ON cc.capability_id = c.id
                WHERE cc.character_id = ? 
                ORDER BY c.name ASC
            ");
            $stmt->execute([$characterId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug temporaire
            error_log("Debug Character::getCharacterCapabilities - Result count: " . count($result));
            if (empty($result)) {
                // Vérifier si le personnage existe dans character_capabilities
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM character_capabilities WHERE character_id = ?");
                $checkStmt->execute([$characterId]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Debug Character::getCharacterCapabilities - Character capabilities count in DB: " . $checkResult['count']);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Récupérer les détails d'un archétype par son ID
     * 
     * @param int $archetypeId ID de l'archétype
     * @return array|null Détails de l'archétype
     */
    public static function getArchetypeById($archetypeId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT ca.*, c.name as class_name 
                FROM class_archetypes ca 
                JOIN classes c ON ca.class_id = c.id 
                WHERE ca.id = ?
            ");
            $stmt->execute([$archetypeId]);
            $archetype = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($archetype) {
                // Ajouter le type d'archetype selon la classe
                $archetype['archetype_type'] = self::getArchetypeTypeStatic($archetype['class_name']);
            }
            
            return $archetype;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'archétype: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le type d'archetype selon la classe (version statique)
     * @param string $className Nom de la classe
     * @return string Type d'archetype
     */
    private static function getArchetypeTypeStatic($className)
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

    /**
     * Récupérer les attaques d'un personnage (attaques personnalisées + attaques des objets équipés)
     */
    public static function getCharacterAttacks($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $attacks = [];
        
        try {
            // 1. Récupérer les attaques personnalisées du personnage
            $stmt = $pdo->query("SHOW TABLES LIKE 'character_attacks'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        name as attack_name,
                        attack_type,
                        range_type,
                        range_value,
                        damage_dice,
                        damage_type,
                        attack_bonus,
                        damage_bonus,
                        description,
                        is_proficient,
                        'custom' as source_type
                    FROM character_attacks 
                    WHERE character_id = ? 
                    ORDER BY name ASC
                ");
                $stmt->execute([$characterId]);
                $customAttacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $attacks = array_merge($attacks, $customAttacks);
            }
            
            // 2. Récupérer les attaques des objets équipés (armes)
            $stmt = $pdo->prepare("
                SELECT 
                    i.id as item_id,
                    i.display_name as attack_name,
                    w.type as attack_type,
                    CASE 
                        WHEN w.properties LIKE '%lancer%' OR w.properties LIKE '%portée%' THEN 'ranged'
                        ELSE 'melee'
                    END as range_type,
                    CASE 
                        WHEN w.properties LIKE '%lancer%' THEN '20/60'
                        ELSE '5'
                    END as range_value,
                    w.damage as damage_dice,
                    CASE 
                        WHEN w.damage LIKE '%contondant%' THEN 'Contondant'
                        WHEN w.damage LIKE '%perforant%' THEN 'Perforant'
                        WHEN w.damage LIKE '%tranchant%' THEN 'Tranchant'
                        ELSE 'Contondant'
                    END as damage_type,
                    '0' as attack_bonus,
                    '0' as damage_bonus,
                    i.description,
                    '1' as is_proficient,
                    'equipped' as source_type
                FROM items i
                LEFT JOIN weapons w ON i.weapon_id = w.id
                WHERE i.owner_type = 'player' 
                AND i.owner_id = ? 
                AND i.is_equipped = 1
                AND i.object_type = 'weapon'
                AND w.id IS NOT NULL
                ORDER BY i.display_name ASC
            ");
            $stmt->execute([$characterId]);
            $equippedAttacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $attacks = array_merge($attacks, $equippedAttacks);
            
            return $attacks;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des attaques: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour la photo de profil d'un personnage
     */
    public static function updateProfilePhoto($characterId, $photoPath) {
        $pdo = getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE characters SET profile_photo = ? WHERE id = ?");
            return $stmt->execute([$photoPath, $characterId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la photo de profil: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer les caractéristiques totales (base + race + améliorations + équipement + temporaires)
     */
    public function getMyTotalAbilities() {
        // Récupérer les objets nécessaires
        $raceObject = Race::findById($this->race_id);
        $abilityImprovements = $this->getAbilityImprovements();
        $equipmentBonuses = $this->getMyEquipmentBonuses();
        $temporaryBonuses = $this->getMyTemporaryBonuses();
        
        // Calculer les totaux
        return [
            'strength' => $this->strength + ($raceObject->strength_bonus ?? 0) + $abilityImprovements['strength'] + $equipmentBonuses['strength'] + $temporaryBonuses['strength'],
            'dexterity' => $this->dexterity + ($raceObject->dexterity_bonus ?? 0) + $abilityImprovements['dexterity'] + $equipmentBonuses['dexterity'] + $temporaryBonuses['dexterity'],
            'constitution' => $this->constitution + ($raceObject->constitution_bonus ?? 0) + $abilityImprovements['constitution'] + $equipmentBonuses['constitution'] + $temporaryBonuses['constitution'],
            'intelligence' => $this->intelligence + ($raceObject->intelligence_bonus ?? 0) + $abilityImprovements['intelligence'] + $equipmentBonuses['intelligence'] + $temporaryBonuses['intelligence'],
            'wisdom' => $this->wisdom + ($raceObject->wisdom_bonus ?? 0) + $abilityImprovements['wisdom'] + $equipmentBonuses['wisdom'] + $temporaryBonuses['wisdom'],
            'charisma' => $this->charisma + ($raceObject->charisma_bonus ?? 0) + $abilityImprovements['charisma'] + $equipmentBonuses['charisma'] + $temporaryBonuses['charisma']
        ];
    }

    /**
     * Calculer les modificateurs de caractéristiques basés sur les caractéristiques totales
     */
    public function getMyAbilityModifiers() {
        $totalAbilities = $this->getMyTotalAbilities();
        
        return [
            'strength' => floor(($totalAbilities['strength'] - 10) / 2),
            'dexterity' => floor(($totalAbilities['dexterity'] - 10) / 2),
            'constitution' => floor(($totalAbilities['constitution'] - 10) / 2),
            'intelligence' => floor(($totalAbilities['intelligence'] - 10) / 2),
            'wisdom' => floor(($totalAbilities['wisdom'] - 10) / 2),
            'charisma' => floor(($totalAbilities['charisma'] - 10) / 2)
        ];
    }

    /**
     * Récupère les bonus d'équipement du personnage (méthode d'instance)
     */
    public function getMyEquipmentBonuses() {
        // Pour l'instant, retourner des bonus à 0
        // Cette méthode peut être étendue pour calculer les vrais bonus d'équipement
        // quand les colonnes de bonus seront ajoutées aux tables armor et weapons
        return [
            'strength' => 0,
            'dexterity' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'wisdom' => 0,
            'charisma' => 0
        ];
    }

    /**
     * Récupère les bonus temporaires du personnage (méthode d'instance)
     */
    public function getMyTemporaryBonuses() {
        // Pour l'instant, retourner des bonus à 0
        // Cette méthode peut être étendue pour calculer les vrais bonus temporaires
        return [
            'strength' => 0,
            'dexterity' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'wisdom' => 0,
            'charisma' => 0
        ];
    }

    /**
     * Génère les compétences de base selon la classe et la race
     */
    public function generateBaseSkills() {
        $skills = [];
        
        // Compétences de classe selon les règles D&D
        $classSkills = [
            1 => ['Athlétisme'], // Barbare
            2 => ['Histoire', 'Perspicacité'], // Barde
            3 => ['Religion'], // Clerc
            4 => ['Survie'], // Druide
            5 => ['Athlétisme', 'Intimidation'], // Guerrier
            6 => ['Athlétisme', 'Survie'], // Moine
            7 => ['Arcane', 'Histoire'], // Magicien
            8 => ['Athlétisme', 'Intimidation'], // Paladin
            9 => ['Survie', 'Discrétion'], // Rôdeur
            10 => ['Escamotage', 'Perspicacité'], // Roublard
            11 => ['Persuasion', 'Intimidation'], // Sorcier
            12 => ['Arcane', 'Religion'] // Ensorceleur
        ];
        
        // Compétences de race selon les règles D&D
        $raceSkills = [
            1 => ['Athlétisme'], // Humain
            2 => ['Athlétisme'], // Nain
            3 => ['Athlétisme'], // Elfe
            4 => ['Athlétisme'], // Halfelin
            5 => ['Athlétisme'], // Dragonné
            6 => ['Perception'], // Haut-elfe
            7 => ['Athlétisme'], // Demi-orc
            8 => ['Athlétisme'], // Tieffelin
            9 => ['Athlétisme'], // Gnome
            10 => ['Athlétisme'] // Demi-elfe
        ];
        
        // Ajouter les compétences de classe
        if (isset($classSkills[$this->class_id])) {
            $skills = array_merge($skills, $classSkills[$this->class_id]);
        }
        
        // Ajouter les compétences de race
        if (isset($raceSkills[$this->race_id])) {
            $skills = array_merge($skills, $raceSkills[$this->race_id]);
        }
        
        // Supprimer les doublons
        return array_unique($skills);
    }

    /**
     * Génère les compétences obligatoires (fixes) selon la classe et la race
     */
    public function generateFixedSkills() {
        $fixedSkills = [];
        
        // Compétences obligatoires de classe selon les règles D&D
        $classFixedSkills = [
            1 => [], // Barbare - pas de compétences fixes
            2 => [], // Barde - pas de compétences fixes
            3 => [], // Clerc - pas de compétences fixes
            4 => [], // Druide - pas de compétences fixes
            5 => [], // Guerrier - pas de compétences fixes
            6 => [], // Moine - pas de compétences fixes
            7 => [], // Magicien - pas de compétences fixes
            8 => [], // Paladin - pas de compétences fixes
            9 => [], // Rôdeur - pas de compétences fixes
            10 => [], // Roublard - pas de compétences fixes
            11 => [], // Sorcier - pas de compétences fixes
            12 => [] // Ensorceleur - pas de compétences fixes
        ];
        
        // Compétences obligatoires de race selon les règles D&D
        $raceFixedSkills = [
            1 => [], // Humain - pas de compétences fixes
            2 => [], // Nain - pas de compétences fixes
            3 => [], // Elfe - pas de compétences fixes
            4 => [], // Halfelin - pas de compétences fixes
            5 => [], // Dragonné - pas de compétences fixes
            6 => ['Perception'], // Haut-elfe - Perception obligatoire
            7 => [], // Demi-orc - pas de compétences fixes
            8 => [], // Tieffelin - pas de compétences fixes
            9 => [], // Gnome - pas de compétences fixes
            10 => [] // Demi-elfe - pas de compétences fixes
        ];
        
        // Ajouter les compétences obligatoires de classe
        if (isset($classFixedSkills[$this->class_id])) {
            $fixedSkills = array_merge($fixedSkills, $classFixedSkills[$this->class_id]);
        }
        
        // Ajouter les compétences obligatoires de race
        if (isset($raceFixedSkills[$this->race_id])) {
            $fixedSkills = array_merge($fixedSkills, $raceFixedSkills[$this->race_id]);
        }
        
        // Supprimer les doublons
        return array_unique($fixedSkills);
    }

    /**
     * Génère les compétences au choix selon la classe
     */
    public function generateSkillChoices() {
        $skillChoices = [];
        
        // Compétences au choix de classe selon les règles D&D
        $classSkillChoices = [
            1 => ['Athlétisme', 'Intimidation', 'Nature', 'Perception', 'Survie'], // Barbare - 2 au choix
            2 => ['Acrobaties', 'Animaux', 'Arcane', 'Athlétisme', 'Escamotage', 'Histoire', 'Intuition', 'Intimidation', 'Investigation', 'Médecine', 'Nature', 'Perception', 'Perspicacité', 'Religion', 'Représentation'], // Barde - 3 au choix
            3 => ['Histoire', 'Médecine', 'Perspicacité', 'Religion'], // Clerc - 2 au choix
            4 => ['Animaux', 'Arcane', 'Athlétisme', 'Intuition', 'Médecine', 'Nature', 'Perception', 'Religion', 'Survie'], // Druide - 2 au choix
            5 => ['Acrobaties', 'Animaux', 'Athlétisme', 'Histoire', 'Intimidation', 'Perception', 'Survie'], // Guerrier - 2 au choix
            6 => ['Acrobaties', 'Athlétisme', 'Histoire', 'Intuition', 'Religion', 'Stealth'], // Moine - 2 au choix
            7 => ['Arcane', 'Histoire', 'Investigation', 'Médecine'], // Magicien - 2 au choix
            8 => ['Athlétisme', 'Intimidation', 'Médecine', 'Perspicacité', 'Religion'], // Paladin - 2 au choix
            9 => ['Animaux', 'Athlétisme', 'Intuition', 'Investigation', 'Nature', 'Perception', 'Survie', 'Stealth'], // Rôdeur - 3 au choix
            10 => ['Acrobaties', 'Athlétisme', 'Escamotage', 'Intimidation', 'Investigation', 'Perception', 'Perspicacité', 'Représentation'], // Roublard - 4 au choix
            11 => ['Arcane', 'Intimidation', 'Investigation', 'Religion', 'Perspicacité'], // Sorcier - 2 au choix
            12 => ['Arcane', 'Religion', 'Intuition', 'Médecine'] // Ensorceleur - 2 au choix
        ];
        
        return $classSkillChoices[$this->class_id] ?? [];
    }

    /**
     * Génère les langues de base selon la classe et la race
     */
    public function generateBaseLanguages() {
        $languages = [];
        
        // Langues de classe selon les règles D&D
        $classLanguages = [
            1 => [], // Barbare
            2 => [], // Barde
            3 => [], // Clerc
            4 => [], // Druide
            5 => [], // Guerrier
            6 => [], // Moine
            7 => [], // Magicien
            8 => [], // Paladin
            9 => [], // Rôdeur
            10 => [], // Roublard
            11 => [], // Sorcier
            12 => [] // Ensorceleur
        ];
        
        // Langues de race selon les règles D&D
        $raceLanguages = [
            1 => ['Commun'], // Humain
            2 => ['Commun', 'Nain'], // Nain
            3 => ['Commun', 'Elfique'], // Elfe
            4 => ['Commun', 'Halfelin'], // Halfelin
            5 => ['Commun', 'Draconique'], // Dragonné
            6 => ['Commun', 'Elfique'], // Haut-elfe
            7 => ['Commun', 'Orc'], // Demi-orc
            8 => ['Commun', 'Infernal'], // Tieffelin
            9 => ['Commun', 'Gnome'], // Gnome
            10 => ['Commun', 'Elfique'] // Demi-elfe
        ];
        
        // Ajouter les langues de classe
        if (isset($classLanguages[$this->class_id])) {
            $languages = array_merge($languages, $classLanguages[$this->class_id]);
        }
        
        // Ajouter les langues de race
        if (isset($raceLanguages[$this->race_id])) {
            $languages = array_merge($languages, $raceLanguages[$this->race_id]);
        }
        
        // Supprimer les doublons
        return array_unique($languages);
    }

    /**
     * Génère les langues obligatoires (fixes) selon la classe et la race
     */
    public function generateFixedLanguages() {
        $fixedLanguages = [];
        
        // Langues obligatoires de classe selon les règles D&D
        $classFixedLanguages = [
            1 => [], // Barbare - pas de langues fixes
            2 => [], // Barde - pas de langues fixes
            3 => [], // Clerc - pas de langues fixes
            4 => [], // Druide - pas de langues fixes
            5 => [], // Guerrier - pas de langues fixes
            6 => [], // Moine - pas de langues fixes
            7 => [], // Magicien - pas de langues fixes
            8 => [], // Paladin - pas de langues fixes
            9 => [], // Rôdeur - pas de langues fixes
            10 => [], // Roublard - pas de langues fixes
            11 => [], // Sorcier - pas de langues fixes
            12 => [] // Ensorceleur - pas de langues fixes
        ];
        
        // Langues obligatoires de race selon les règles D&D
        $raceFixedLanguages = [
            1 => ['Commun'], // Humain - Commun obligatoire
            2 => ['Commun', 'Nain'], // Nain - Commun et Nain obligatoires
            3 => ['Commun', 'Elfique'], // Elfe - Commun et Elfique obligatoires
            4 => ['Commun', 'Halfelin'], // Halfelin - Commun et Halfelin obligatoires
            5 => ['Commun', 'Draconique'], // Dragonné - Commun et Draconique obligatoires
            6 => ['Commun', 'Elfique'], // Haut-elfe - Commun et Elfique obligatoires
            7 => ['Commun', 'Orc'], // Demi-orc - Commun et Orc obligatoires
            8 => ['Commun', 'Infernal'], // Tieffelin - Commun et Infernal obligatoires
            9 => ['Commun', 'Gnome'], // Gnome - Commun et Gnome obligatoires
            10 => ['Commun', 'Elfique'] // Demi-elfe - Commun et Elfique obligatoires
        ];
        
        // Ajouter les langues obligatoires de classe
        if (isset($classFixedLanguages[$this->class_id])) {
            $fixedLanguages = array_merge($fixedLanguages, $classFixedLanguages[$this->class_id]);
        }
        
        // Ajouter les langues obligatoires de race
        if (isset($raceFixedLanguages[$this->race_id])) {
            $fixedLanguages = array_merge($fixedLanguages, $raceFixedLanguages[$this->race_id]);
        }
        
        // Supprimer les doublons
        return array_unique($fixedLanguages);
    }

    /**
     * Génère les langues au choix selon la classe et la race
     */
    public function generateLanguageChoices() {
        $languageChoices = [];
        
        // Langues au choix de classe selon les règles D&D
        $classLanguageChoices = [
            1 => [], // Barbare - pas de langues au choix
            2 => [], // Barde - pas de langues au choix
            3 => [], // Clerc - pas de langues au choix
            4 => [], // Druide - pas de langues au choix
            5 => [], // Guerrier - pas de langues au choix
            6 => [], // Moine - pas de langues au choix
            7 => [], // Magicien - pas de langues au choix
            8 => [], // Paladin - pas de langues au choix
            9 => [], // Rôdeur - pas de langues au choix
            10 => [], // Roublard - pas de langues au choix
            11 => [], // Sorcier - pas de langues au choix
            12 => [] // Ensorceleur - pas de langues au choix
        ];
        
        // Langues au choix de race selon les règles D&D
        $raceLanguageChoices = [
            1 => [], // Humain - pas de langues au choix
            2 => [], // Nain - pas de langues au choix
            3 => [], // Elfe - pas de langues au choix
            4 => [], // Halfelin - pas de langues au choix
            5 => [], // Dragonné - pas de langues au choix
            6 => ['Une langue de votre choix'], // Haut-elfe - 1 langue au choix
            7 => [], // Demi-orc - pas de langues au choix
            8 => [], // Tieffelin - pas de langues au choix
            9 => [], // Gnome - pas de langues au choix
            10 => [] // Demi-elfe - pas de langues au choix
        ];
        
        // Ajouter les langues au choix de classe
        if (isset($classLanguageChoices[$this->class_id])) {
            $languageChoices = array_merge($languageChoices, $classLanguageChoices[$this->class_id]);
        }
        
        // Ajouter les langues au choix de race
        if (isset($raceLanguageChoices[$this->race_id])) {
            $languageChoices = array_merge($languageChoices, $raceLanguageChoices[$this->race_id]);
        }
        
        // Supprimer les doublons
        return array_unique($languageChoices);
    }

    /**
     * Récupérer l'équipement du personnage sous forme d'objets Item (méthode d'instance)
     */
    public function getEquipment()
    {
        $pdo = \Database::getInstance()->getPdo();
        $items = [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.display_name as name,
                    i.object_type as type,
                    i.type_precis as subtype,
                    i.description,
                    i.is_equipped as equipped,
                    i.equipped_slot,
                    i.quantity,
                    i.notes,
                    i.armor_id,
                    i.weapon_id,
                    i.shield_id,
                    i.magical_item_id,
                    i.poison_id,
                    a.name as armor_name,
                    a.ac_formula as armor_ac_formula,
                    w.name as weapon_name,
                    w.damage as weapon_damage,
                    w.properties as weapon_properties,
                    s.name as shield_name,
                    s.ac_formula as shield_ac_formula,
                    mi.nom as magical_item_name,
                    mi.description as magical_item_description,
                    p.nom as poison_name,
                    p.description as poison_description
                FROM items i
                LEFT JOIN armor a ON i.armor_id = a.id
                LEFT JOIN weapons w ON i.weapon_id = w.id
                LEFT JOIN shields s ON i.shield_id = s.id
                LEFT JOIN magical_items mi ON i.magical_item_id = mi.id
                LEFT JOIN poisons p ON i.poison_id = p.id
                WHERE i.owner_type = 'player' AND i.owner_id = ?
                ORDER BY i.created_at ASC
            ");
            
            $stmt->execute([$this->id]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item = new stdClass();
                $item->id = $row['id'];
                $item->name = $row['name'];
                $item->type = $row['type'];
                $item->subtype = $row['subtype'];
                $item->description = $row['description'];
                $item->equipped = (bool)$row['equipped'];
                $item->equipped_slot = $row['equipped_slot'];
                $item->quantity = $row['quantity'];
                $item->notes = $row['notes'];
                
                // Ajouter les détails spécifiques selon le type
                if ($row['armor_id']) {
                    $item->armor_name = $row['armor_name'];
                    $item->armor_ac_formula = $row['armor_ac_formula'];
                }
                if ($row['weapon_id']) {
                    $item->weapon_name = $row['weapon_name'];
                    $item->weapon_damage = $row['weapon_damage'];
                    $item->weapon_properties = $row['weapon_properties'];
                }
                if ($row['shield_id']) {
                    $item->shield_name = $row['shield_name'];
                    $item->shield_ac_formula = $row['shield_ac_formula'];
                }
                if ($row['magical_item_id']) {
                    $item->magical_item_name = $row['magical_item_name'];
                    $item->magical_item_description = $row['magical_item_description'];
                }
                if ($row['poison_id']) {
                    $item->poison_name = $row['poison_name'];
                    $item->poison_description = $row['poison_description'];
                }
                
                $items[] = $item;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
        }
        
        return $items;
    }

    /**
     * Récupérer l'équipement du personnage (méthode d'instance)
     */
    public function getMyEquipment()
    {
        return self::getCharacterEquipment($this->id);
    }

    /**
     * Récupérer l'armure équipée du personnage (méthode d'instance)
     */
    public function getMyEquippedArmor()
    {
        $equipment = $this->getMyEquipment();
        foreach ($equipment as $item) {
            if ($item['equipped'] && $item['item_type'] === 'armor') {
                return $item;
            }
        }
        return null;
    }

    /**
     * Récupérer le bouclier équipé du personnage (méthode d'instance)
     */
    public function getMyEquippedShield()
    {
        $equipment = $this->getMyEquipment();
        foreach ($equipment as $item) {
            if ($item['equipped'] && $item['item_type'] === 'shield') {
                return $item;
            }
        }
        return null;
    }

    /**
     * Récupérer la race du personnage (méthode d'instance)
     */
    public function getRace()
    {
        return Race::findById($this->race_id);
    }

    /**
     * Récupérer la classe du personnage (méthode d'instance)
     */
    public function getClass()
    {
        return Classe::findById($this->class_id);
    }


    /**
     * Calculer les attaques du personnage (méthode d'instance)
     */
    public function calculateMyCharacterAttacks()
    {
        return self::getCharacterAttacks($this->id);
    }

    /**
     * Calculer la classe d'armure du personnage (méthode d'instance)
     */
    public function calculateMyArmorClass()
    {
        return $this->calculateArmorClass();
    }

    /**
     * Vérifier si le personnage est un barbare (méthode d'instance)
     */
    public function isBarbarian()
    {
        $classObject = Classe::findById($this->class_id);
        return $classObject && strpos(strtolower($classObject->name), 'barbare') !== false;
    }

    /**
     * Récupérer les données de rage du personnage (méthode d'instance)
     */
    public function getMyRageData()
    {
        if (!$this->isBarbarian()) {
            return null;
        }

        $maxRages = self::getMaxRages($this->class_id, $this->level);
        $rageUsage = self::getRageUsageStatic($this->id);
        $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;

        return [
            'max' => $maxRages,
            'used' => $usedRages,
            'available' => $maxRages - $usedRages
        ];
    }

    /**
     * Vérifier si le personnage peut lancer des sorts (méthode d'instance)
     */
    public function canCastSpells()
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
        $stmt->execute([$this->class_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Récupérer les poisons du personnage (méthode d'instance)
     */
    public function getMyCharacterPoisons()
    {
        return self::getCharacterPoisons($this->id);
    }
}
