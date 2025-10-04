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
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
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
