<?php

/**
 * Classe Race - Gestion des races D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux races :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des bonus raciaux
 * - Gestion des traits raciaux
 */

class Race
{
    private $pdo;
    
    // Propriétés de la race
    public $id;
    public $name;
    public $description;
    public $image;
    public $strength_bonus;
    public $dexterity_bonus;
    public $constitution_bonus;
    public $intelligence_bonus;
    public $wisdom_bonus;
    public $charisma_bonus;
    public $size;
    public $speed;
    public $vision;
    public $languages;
    public $traits;
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
    public function create()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO races 
                (name, description, image, strength_bonus, dexterity_bonus, constitution_bonus, 
                 intelligence_bonus, wisdom_bonus, charisma_bonus, size, speed, vision, 
                 languages, traits, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $this->name,
                $this->description,
                $this->image,
                $this->strength_bonus,
                $this->dexterity_bonus,
                $this->constitution_bonus,
                $this->intelligence_bonus,
                $this->wisdom_bonus,
                $this->charisma_bonus,
                $this->size,
                $this->speed,
                $this->vision,
                $this->languages,
                $this->traits
            ]);
            
            if ($result) {
                $this->id = $pdo->lastInsertId();
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création Race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver une race par ID
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
            error_log("Erreur recherche Race: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver toutes les races
     */
    public function getAll()
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->query("SELECT * FROM races ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $races = [];
            foreach ($results as $data) {
                $races[] = new self($pdo, $data);
            }
            
            return $races;
            
        } catch (PDOException $e) {
            error_log("Erreur récupération races: " . $e->getMessage());
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
            $stmt = $pdo->prepare("SELECT * FROM races WHERE name LIKE ? ORDER BY name ASC");
            $stmt->execute(["%$name%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $races = [];
            foreach ($results as $data) {
                $races[] = new self($pdo, $data);
            }
            
            return $races;
            
        } catch (PDOException $e) {
            error_log("Erreur recherche races par nom: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour la race
     */
    public function update()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE races 
                SET name = ?, description = ?, image = ?, strength_bonus = ?, dexterity_bonus = ?, 
                    constitution_bonus = ?, intelligence_bonus = ?, wisdom_bonus = ?, charisma_bonus = ?, 
                    size = ?, speed = ?, vision = ?, languages = ?, traits = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $this->name,
                $this->description,
                $this->image,
                $this->strength_bonus,
                $this->dexterity_bonus,
                $this->constitution_bonus,
                $this->intelligence_bonus,
                $this->wisdom_bonus,
                $this->charisma_bonus,
                $this->size,
                $this->speed,
                $this->vision,
                $this->languages,
                $this->traits,
                $this->id
            ]);
            
            if ($result) {
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur mise à jour Race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer la race
     */
    public function delete()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM races WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur suppression Race: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convertir en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'strength_bonus' => $this->strength_bonus,
            'dexterity_bonus' => $this->dexterity_bonus,
            'constitution_bonus' => $this->constitution_bonus,
            'intelligence_bonus' => $this->intelligence_bonus,
            'wisdom_bonus' => $this->wisdom_bonus,
            'charisma_bonus' => $this->charisma_bonus,
            'size' => $this->size,
            'speed' => $this->speed,
            'vision' => $this->vision,
            'languages' => $this->languages,
            'traits' => $this->traits,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>