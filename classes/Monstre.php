<?php

/**
 * Classe Monstre - Gestion des monstres D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux monstres :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des statistiques et compétences
 * - Gestion des actions et capacités spéciales
 * - Gestion de l'équipement
 * - Gestion des résistances et immunités
 * - Gestion des sens et langues
 */
class Monstre
{
    private $pdo;
    
    // Propriétés du monstre
    public $id;
    public $csv_id;
    public $name;
    public $type;
    public $size;
    public $alignment;
    public $challenge_rating;
    public $hit_points;
    public $armor_class;
    public $speed;
    public $proficiency_bonus;
    public $description;
    public $actions;
    public $special_abilities;
    public $created_at;
    
    // Statistiques de base
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    
    // Compétences et capacités
    public $competences;
    public $saving_throws;
    public $damage_immunities;
    public $damage_resistances;
    public $condition_immunities;
    public $senses;
    public $languages;
    
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
     * Hydratation de l'objet avec les données
     */
    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Sauvegarder le monstre (création ou mise à jour)
     */
    public function save()
    {
        try {
            if ($this->id) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE dnd_monsters 
                    SET csv_id = ?, name = ?, type = ?, size = ?, alignment = ?, 
                        challenge_rating = ?, hit_points = ?, armor_class = ?, speed = ?, 
                        proficiency_bonus = ?, description = ?, actions = ?, special_abilities = ?,
                        strength = ?, dexterity = ?, constitution = ?, intelligence = ?, 
                        wisdom = ?, charisma = ?, competences = ?, saving_throws = ?,
                        damage_immunities = ?, damage_resistances = ?, condition_immunities = ?,
                        senses = ?, languages = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->csv_id, $this->name, $this->type, $this->size, $this->alignment,
                    $this->challenge_rating, $this->hit_points, $this->armor_class, $this->speed,
                    $this->proficiency_bonus, $this->description, $this->actions, $this->special_abilities,
                    $this->strength, $this->dexterity, $this->constitution, $this->intelligence,
                    $this->wisdom, $this->charisma, $this->competences, $this->saving_throws,
                    $this->damage_immunities, $this->damage_resistances, $this->condition_immunities,
                    $this->senses, $this->languages, $this->id
                ]);
            } else {
                // Création
                $stmt = $this->pdo->prepare("
                    INSERT INTO dnd_monsters (csv_id, name, type, size, alignment, challenge_rating, 
                        hit_points, armor_class, speed, proficiency_bonus, description, actions, 
                        special_abilities, strength, dexterity, constitution, intelligence, wisdom, 
                        charisma, competences, saving_throws, damage_immunities, damage_resistances, 
                        condition_immunities, senses, languages, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $this->csv_id, $this->name, $this->type, $this->size, $this->alignment,
                    $this->challenge_rating, $this->hit_points, $this->armor_class, $this->speed,
                    $this->proficiency_bonus, $this->description, $this->actions, $this->special_abilities,
                    $this->strength, $this->dexterity, $this->constitution, $this->intelligence,
                    $this->wisdom, $this->charisma, $this->competences, $this->saving_throws,
                    $this->damage_immunities, $this->damage_resistances, $this->condition_immunities,
                    $this->senses, $this->languages
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le monstre
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger un monstre par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du monstre: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Charger un monstre par son CSV ID
     */
    public static function findByCsvId($csvId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM dnd_monsters WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du monstre par CSV ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les monstres par type
     */
    public static function findByType($type, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE type LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $type . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les monstres par taille
     */
    public static function findBySize($size, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE size = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$size]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par taille: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les monstres par challenge rating
     */
    public static function findByChallengeRating($cr, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE challenge_rating = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$cr]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par CR: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rechercher des monstres par nom
     */
    public static function searchByName($name, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE name LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $name . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par nom: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les monstres
     */
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->query("SELECT * FROM dnd_monsters ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les monstres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir le modificateur d'une caractéristique
     */
    public function getAbilityModifier($ability)
    {
        $value = $this->$ability ?? 10;
        return floor(($value - 10) / 2);
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
     * Obtenir le nom du monstre
     */
    public function getName()
    {
        return $this->name ?: '';
    }
    
    /**
     * Obtenir le type du monstre
     */
    public function getType()
    {
        return $this->type ?: '';
    }
    
    /**
     * Obtenir la taille du monstre
     */
    public function getSize()
    {
        return $this->size ?: '';
    }
    
    /**
     * Obtenir l'alignement du monstre
     */
    public function getAlignment()
    {
        return $this->alignment ?: '';
    }
    
    /**
     * Obtenir le challenge rating
     */
    public function getChallengeRating()
    {
        return $this->challenge_rating ?: '';
    }
    
    /**
     * Obtenir les points de vie
     */
    public function getHitPoints()
    {
        return $this->hit_points ?: 0;
    }
    
    /**
     * Obtenir la classe d'armure
     */
    public function getArmorClass()
    {
        return $this->armor_class ?: 0;
    }
    
    /**
     * Obtenir la vitesse
     */
    public function getSpeed()
    {
        return $this->speed ?: '';
    }
    
    /**
     * Obtenir le bonus de maîtrise
     */
    public function getProficiencyBonus()
    {
        return $this->proficiency_bonus ?: 0;
    }
    
    /**
     * Obtenir la description
     */
    public function getDescription()
    {
        return $this->description ?: '';
    }
    
    /**
     * Obtenir les actions
     */
    public function getActions()
    {
        return $this->actions ?: '';
    }
    
    /**
     * Obtenir les capacités spéciales
     */
    public function getSpecialAbilities()
    {
        return $this->special_abilities ?: '';
    }
    
    /**
     * Obtenir les résistances aux dégâts
     */
    public function getDamageResistances()
    {
        return $this->damage_resistances ?: '';
    }
    
    /**
     * Obtenir les immunités aux dégâts
     */
    public function getDamageImmunities()
    {
        return $this->damage_immunities ?: '';
    }
    
    /**
     * Obtenir les immunités aux conditions
     */
    public function getConditionImmunities()
    {
        return $this->condition_immunities ?: '';
    }
    
    /**
     * Obtenir les sens
     */
    public function getSenses()
    {
        return $this->senses ?: '';
    }
    
    /**
     * Obtenir les langues
     */
    public function getLanguages()
    {
        return $this->languages ?: '';
    }
    
    /**
     * Convertir l'objet en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'csv_id' => $this->csv_id,
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'alignment' => $this->alignment,
            'challenge_rating' => $this->challenge_rating,
            'hit_points' => $this->hit_points,
            'armor_class' => $this->armor_class,
            'speed' => $this->speed,
            'proficiency_bonus' => $this->proficiency_bonus,
            'description' => $this->description,
            'actions' => $this->actions,
            'special_abilities' => $this->special_abilities,
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma,
            'competences' => $this->competences,
            'saving_throws' => $this->saving_throws,
            'damage_immunities' => $this->damage_immunities,
            'damage_resistances' => $this->damage_resistances,
            'condition_immunities' => $this->condition_immunities,
            'senses' => $this->senses,
            'languages' => $this->languages,
            'created_at' => $this->created_at
        ];
    }
}
