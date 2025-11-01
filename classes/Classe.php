<?php

/**
 * Classe Classe - Gestion des classes D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux classes :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des dés de vie
 * - Gestion des compétences de classe
 * - Gestion des capacités spéciales
 */
class Classe
{
    private $pdo;
    
    // Propriétés de la classe
    public $id;
    public $name;
    public $description;
    public $hit_dice;
    public $primary_ability;
    public $saving_throw_proficiencies;
    public $skill_proficiencies;
    public $armor_proficiencies;
    public $weapon_proficiencies;
    public $tool_proficiencies;
    public $starting_equipment;
    public $class_features;
    public $spellcasting_ability;
    public $recommended_strength;
    public $recommended_dexterity;
    public $recommended_constitution;
    public $recommended_intelligence;
    public $recommended_wisdom;
    public $recommended_charisma;
    public $created_at;
    public $updated_at;
    
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
     * Obtenir l'instance PDO
     * 
     * @return PDO
     */
    private function getPdo()
    {
        return $this->pdo ?: \Database::getInstance()->getPdo();
    }
    
    /**
     * Hydratation de l'objet avec les données
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
     * Créer une nouvelle classe
     */
    public static function create(array $data, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO classes (
                    name, description, hit_dice, primary_ability, saving_throw_proficiencies,
                    skill_proficiencies, armor_proficiencies, weapon_proficiencies, tool_proficiencies,
                    starting_equipment, class_features, spellcasting_ability
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['hit_dice'] ?? 'd8',
                $data['primary_ability'] ?? null,
                $data['saving_throw_proficiencies'] ?? null,
                $data['skill_proficiencies'] ?? null,
                $data['armor_proficiencies'] ?? null,
                $data['weapon_proficiencies'] ?? null,
                $data['tool_proficiencies'] ?? null,
                $data['starting_equipment'] ?? null,
                $data['class_features'] ?? null,
                $data['spellcasting_ability'] ?? null
            ]);
            
            $classId = $pdo->lastInsertId();
            return self::findById($classId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la classe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver une classe par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de la classe: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver toutes les classes
     */
    public function getAll()
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->query("SELECT * FROM classes ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $classes = [];
            foreach ($results as $data) {
                $classes[] = new self($pdo, $data);
            }
            
            return $classes;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des classes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rechercher des classes par nom
     */
    public static function searchByName($name, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM classes 
                WHERE name LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $name . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $classes = [];
            foreach ($results as $data) {
                $classes[] = new self($pdo, $data);
            }
            
            return $classes;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des classes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour la classe
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
                UPDATE classes 
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
            error_log("Erreur lors de la mise à jour de la classe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer la classe
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM classes WHERE id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la classe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le nom de la classe
     */
    public function getName()
    {
        return $this->name ?: '';
    }
    
    /**
     * Obtenir la description de la classe
     */
    public function getDescription()
    {
        return $this->description ?: '';
    }
    
    /**
     * Obtenir le dé de vie
     */
    public function getHitDice()
    {
        return $this->hit_dice ?: 'd8';
    }
    
    /**
     * Obtenir la capacité principale
     */
    public function getPrimaryAbility()
    {
        return $this->primary_ability ?: '';
    }
    
    /**
     * Obtenir les compétences de sauvegarde
     */
    public function getSavingThrowProficiencies()
    {
        return $this->saving_throw_proficiencies ?: '';
    }
    
    /**
     * Obtenir les compétences de classe
     */
    public function getSkillProficiencies()
    {
        return $this->skill_proficiencies ?: '';
    }
    
    /**
     * Obtenir les compétences d'armure
     */
    public function getArmorProficiencies()
    {
        return $this->armor_proficiencies ?: '';
    }
    
    /**
     * Obtenir les compétences d'armes
     */
    public function getWeaponProficiencies()
    {
        return $this->weapon_proficiencies ?: '';
    }
    
    /**
     * Obtenir les compétences d'outils
     */
    public function getToolProficiencies()
    {
        return $this->tool_proficiencies ?: '';
    }
    
    /**
     * Obtenir l'équipement de départ
     */
    public function getStartingEquipment()
    {
        return $this->starting_equipment ?: '';
    }
    
    /**
     * Obtenir les capacités de classe
     */
    public function getClassFeatures()
    {
        return $this->class_features ?: '';
    }
    
    /**
     * Obtenir la capacité de lancement de sorts
     */
    public function getSpellcastingAbility()
    {
        return $this->spellcasting_ability ?: '';
    }
    
    /**
     * Vérifier si la classe peut lancer des sorts
     */
    public function canCastSpells()
    {
        return !empty($this->spellcasting_ability);
    }
    
    /**
     * Obtenir les capacités de sorts pour un niveau donné
     * 
     * @param int $level Niveau du personnage
     * @param int $wisdomModifier Modificateur de Sagesse (par défaut 0)
     * @param int|null $maxSpellsLearned Nombre maximum de sorts appris (par défaut null, utilise la valeur par défaut)
     * @param int $intelligenceModifier Modificateur d'Intelligence (par défaut 0)
     * @return array|null Tableau des capacités de sorts ou null si non trouvé
     */
    public function getSpellCapabilities($level, $wisdomModifier = 0, $maxSpellsLearned = null, $intelligenceModifier = 0)
    {
        $pdo = $this->getPdo();
        
        $stmt = $pdo->prepare("
            SELECT cantrips_known, spells_known, 
                   spell_slots_1st, spell_slots_2nd, spell_slots_3rd, 
                   spell_slots_4th, spell_slots_5th, spell_slots_6th, 
                   spell_slots_7th, spell_slots_8th, spell_slots_9th
            FROM class_evolution 
            WHERE class_id = ? AND level = ?
        ");
        $stmt->execute([$this->id, $level]);
        $capabilities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($capabilities) {
            // Sorts appris : utiliser le champ personnalisé ou la valeur par défaut
            $spellsLearned = $maxSpellsLearned !== null ? $maxSpellsLearned : $capabilities['spells_known'];
            
            // Calculer les sorts préparés selon la classe
            $spellsPrepared = $capabilities['spells_known']; // Valeur par défaut
            
            $className = strtolower($this->name);
            
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
            
            // Ajouter les deux valeurs au tableau de retour
            $capabilities['spells_learned'] = $spellsLearned;
            $capabilities['spells_prepared'] = $spellsPrepared;
        }
        
        return $capabilities;
    }
    
    /**
     * Obtenir les sorts disponibles pour cette classe
     * 
     * @return array Tableau des sorts disponibles
     */
    public function getSpells()
    {
        $pdo = $this->getPdo();
        
        if (!$this->name) {
            return [];
        }
        
        // Rechercher les sorts qui contiennent le nom de la classe
        $stmt = $pdo->prepare("
            SELECT * FROM spells 
            WHERE classes LIKE ?
            ORDER BY level, name
        ");
        
        $stmt->execute(["%{$this->name}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ajouter un sort à un personnage en tenant compte des règles spécifiques de cette classe
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @return bool Succès de l'opération
     */
    public function addSpellToCharacter($characterId, $spellId, $prepared = false)
    {
        $pdo = $this->getPdo();
        
        try {
            // Pour les bardes, tous les sorts sont automatiquement préparés
            $className = strtolower($this->name);
            if (strpos($className, 'barde') !== false) {
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
    public function removeSpellFromCharacter($characterId, $spellId)
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM character_spells 
                WHERE character_id = ? AND spell_id = ?
            ");
            $stmt->execute([$characterId, $spellId]);
            return true;
        } catch (\PDOException $e) {
            error_log("Erreur removeSpellFromCharacter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour l'état préparé d'un sort pour un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @return bool Succès de l'opération
     */
    public function updateSpellPrepared($characterId, $spellId, $prepared)
    {
        $pdo = $this->getPdo();
        
        try {
            // Pour les bardes, les sorts ne peuvent pas être dépréparés
            $className = strtolower($this->name);
            if (strpos($className, 'barde') !== false && !$prepared) {
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
     * Récupérer les utilisations d'emplacements de sorts pour un personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Utilisation des emplacements de sorts
     */
    public function getSpellSlotsUsage($characterId)
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT level_1_used, level_2_used, level_3_used, level_4_used, level_5_used,
                       level_6_used, level_7_used, level_8_used, level_9_used
                FROM spell_slots_usage 
                WHERE character_id = ?
            ");
            $stmt->execute([$characterId]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            error_log("Erreur getSpellSlotsUsage: " . $e->getMessage());
            return [
                'level_1_used' => 0, 'level_2_used' => 0, 'level_3_used' => 0,
                'level_4_used' => 0, 'level_5_used' => 0, 'level_6_used' => 0,
                'level_7_used' => 0, 'level_8_used' => 0, 'level_9_used' => 0
            ];
        }
    }
    
    /**
     * Récupérer le nombre maximum de rages pour un niveau donné de cette classe
     * 
     * @param int $level Niveau du personnage
     * @return int Nombre maximum de rages
     */
    public function getMaxRages($level)
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT rages FROM class_evolution WHERE class_id = ? AND level = ?");
            $stmt->execute([$this->id, $level]);
            $evolution = $stmt->fetch(PDO::FETCH_ASSOC);
            return $evolution ? (int)$evolution['rages'] : 0;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération du nombre maximum de rages: " . $e->getMessage());
            return 0;
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
                $archetype['archetype_type'] = self::getArchetypeType($archetype['class_name']);
            }
            
            return $archetype;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'archétype: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir le type d'archetype selon la classe
     * 
     * @param string $className Nom de la classe
     * @return string Type d'archetype
     */
    private static function getArchetypeType($className)
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
     * Convertir en tableau pour l'affichage
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hit_dice' => $this->hit_dice,
            'primary_ability' => $this->primary_ability,
            'saving_throw_proficiencies' => $this->saving_throw_proficiencies,
            'skill_proficiencies' => $this->skill_proficiencies,
            'armor_proficiencies' => $this->armor_proficiencies,
            'weapon_proficiencies' => $this->weapon_proficiencies,
            'tool_proficiencies' => $this->tool_proficiencies,
            'starting_equipment' => $this->starting_equipment,
            'class_features' => $this->class_features,
            'spellcasting_ability' => $this->spellcasting_ability,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
