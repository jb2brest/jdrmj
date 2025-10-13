<?php

/**
 * Classe Race - Gestion des races D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux races :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des bonus de caractéristiques
 * - Gestion des traits raciaux
 * - Gestion des langues raciales
 */
class Race
{
    private $pdo;
    
    // Propriétés de la race
    public $id;
    public $name;
    public $description;
    public $strength_bonus;
    public $dexterity_bonus;
    public $constitution_bonus;
    public $intelligence_bonus;
    public $wisdom_bonus;
    public $charisma_bonus;
    public $traits;
    public $languages;
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
     * Créer une nouvelle race
     */
    public static function create(array $data, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO races (
                    name, description, strength_bonus, dexterity_bonus, constitution_bonus,
                    intelligence_bonus, wisdom_bonus, charisma_bonus, traits, languages
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['strength_bonus'] ?? 0,
                $data['dexterity_bonus'] ?? 0,
                $data['constitution_bonus'] ?? 0,
                $data['intelligence_bonus'] ?? 0,
                $data['wisdom_bonus'] ?? 0,
                $data['charisma_bonus'] ?? 0,
                $data['traits'] ?? null,
                $data['languages'] ?? null
            ]);
            
            $raceId = $pdo->lastInsertId();
            return self::findById($raceId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver une race par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM races WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de la race: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver toutes les races
     */
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->query("SELECT * FROM races ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $races = [];
            foreach ($results as $data) {
                $races[] = new self($pdo, $data);
            }
            
            return $races;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des races: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rechercher des races par nom
     */
    public static function searchByName($name, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM races 
                WHERE name LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $name . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $races = [];
            foreach ($results as $data) {
                $races[] = new self($pdo, $data);
            }
            
            return $races;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des races: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour la race
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
                UPDATE races 
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
            error_log("Erreur lors de la mise à jour de la race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer la race
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM races WHERE id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le nom de la race
     */
    public function getName()
    {
        return $this->name ?: '';
    }
    
    /**
     * Obtenir la description de la race
     */
    public function getDescription()
    {
        return $this->description ?: '';
    }
    
    /**
     * Obtenir tous les bonus de caractéristiques
     */
    public function getAbilityBonuses()
    {
        return [
            'strength' => $this->strength_bonus ?: 0,
            'dexterity' => $this->dexterity_bonus ?: 0,
            'constitution' => $this->constitution_bonus ?: 0,
            'intelligence' => $this->intelligence_bonus ?: 0,
            'wisdom' => $this->wisdom_bonus ?: 0,
            'charisma' => $this->charisma_bonus ?: 0
        ];
    }
    
    /**
     * Obtenir les traits raciaux
     */
    public function getTraits()
    {
        return $this->traits ?: '';
    }
    
    /**
     * Obtenir les langues raciales
     */
    public function getLanguages()
    {
        return $this->languages ?: '';
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
            'strength_bonus' => $this->strength_bonus,
            'dexterity_bonus' => $this->dexterity_bonus,
            'constitution_bonus' => $this->constitution_bonus,
            'intelligence_bonus' => $this->intelligence_bonus,
            'wisdom_bonus' => $this->wisdom_bonus,
            'charisma_bonus' => $this->charisma_bonus,
            'traits' => $this->traits,
            'languages' => $this->languages,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
