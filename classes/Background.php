<?php

/**
 * Classe Background - Gestion des historiques D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux historiques :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des compétences d'historique
 * - Gestion des équipements de départ
 */

class Background
{
    private $pdo;
    
    // Propriétés de l'historique
    public $id;
    public $name;
    public $description;
    public $skill_proficiencies;
    public $tool_proficiencies;
    public $languages;
    public $equipment;
    public $feature;
    public $suggested_characteristics;
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
     * Créer un nouvel historique
     */
    public function create()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO backgrounds 
                (name, description, skill_proficiencies, tool_proficiencies, languages, 
                 equipment, feature, suggested_characteristics, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $this->name,
                $this->description,
                $this->skill_proficiencies,
                $this->tool_proficiencies,
                $this->languages,
                $this->equipment,
                $this->feature,
                $this->suggested_characteristics
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
            error_log("Erreur création Background: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un historique par ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur recherche Background: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les historiques
     */
    public function getAll()
    {
        $pdo = $this->getPdo();
        
        try {
            $stmt = $pdo->query("SELECT * FROM backgrounds ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $backgrounds = [];
            foreach ($results as $data) {
                $backgrounds[] = new self($pdo, $data);
            }
            
            return $backgrounds;
            
        } catch (PDOException $e) {
            error_log("Erreur récupération backgrounds: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rechercher des historiques par nom
     */
    public static function searchByName($name, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE name LIKE ? ORDER BY name ASC");
            $stmt->execute(["%$name%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $backgrounds = [];
            foreach ($results as $data) {
                $backgrounds[] = new self($pdo, $data);
            }
            
            return $backgrounds;
            
        } catch (PDOException $e) {
            error_log("Erreur recherche backgrounds par nom: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour l'historique
     */
    public function update()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE backgrounds 
                SET name = ?, description = ?, skill_proficiencies = ?, tool_proficiencies = ?, 
                    languages = ?, equipment = ?, feature = ?, suggested_characteristics = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $this->name,
                $this->description,
                $this->skill_proficiencies,
                $this->tool_proficiencies,
                $this->languages,
                $this->equipment,
                $this->feature,
                $this->suggested_characteristics,
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
            error_log("Erreur mise à jour Background: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer l'historique
     */
    public function delete()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM backgrounds WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur suppression Background: " . $e->getMessage());
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
            'skill_proficiencies' => $this->skill_proficiencies,
            'tool_proficiencies' => $this->tool_proficiencies,
            'languages' => $this->languages,
            'equipment' => $this->equipment,
            'feature' => $this->feature,
            'suggested_characteristics' => $this->suggested_characteristics,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>