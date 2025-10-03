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
                UPDATE character_equipment 
                SET equipped = 0 
                WHERE character_id = ? AND slot = ?
            ");
            $stmt->execute([$this->id, $slot]);
            
            // Équiper le nouvel objet
            $stmt = $this->pdo->prepare("
                UPDATE character_equipment 
                SET equipped = 1, slot = ?
                WHERE character_id = ? AND equipment_id = (
                    SELECT id FROM equipment WHERE name = ? AND type = ?
                )
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
                UPDATE character_equipment 
                SET equipped = 0 
                WHERE character_id = ? AND equipment_id = (
                    SELECT id FROM equipment WHERE name = ?
                )
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
                SELECT ability, improvement
                FROM character_ability_improvements
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $improvements = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $improvements[$row['ability']] = $row['improvement'];
            }
            
            return $improvements;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des améliorations: " . $e->getMessage());
            return [];
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
                INSERT INTO character_ability_improvements (character_id, ability, improvement)
                VALUES (?, ?, ?)
            ");
            
            foreach ($improvements as $ability => $improvement) {
                if ($improvement > 0) {
                    $stmt->execute([$this->id, $ability, $improvement]);
                }
            }
            
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
        
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        $finalAbilities = [];
        
        foreach ($abilities as $ability) {
            $base = $this->$ability;
            $improvement = $abilityImprovements[$ability] ?? 0;
            $finalAbilities[$ability] = $base + $improvement;
        }
        
        return $finalAbilities;
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
}
