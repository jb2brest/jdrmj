<?php

/**
 * Classe Location - Gestion des lieux (intermédiaire entre Région et Pièces)
 * 
 * Cette classe gère les lieux géographiques (ex: Château, Ville, Forêt)
 * qui contiennent des pièces (Rooms).
 */
class Location
{
    private $pdo;
    
    // Propriétés
    private $id;
    private $name;
    private $description;
    private $region_id;
    private $created_at;
    private $updated_at;
    
    // Propriétés jointes
    private $region_name;
    private $country_name;
    
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
    public function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->region_id = $data['region_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        
        $this->region_name = $data['region_name'] ?? null;
        $this->country_name = $data['country_name'] ?? null;
    }
    
    /**
     * Getters
     */
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getRegionId() { return $this->region_id; }
    
    /**
     * Création d'un nouveau lieu
     */
    public static function create($name, $regionId, $description = '', PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO locations (name, region_id, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$name, $regionId, $description]);
            
            $id = $pdo->lastInsertId();
            return self::findById($id, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur création Location: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver par ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $stmt = $pdo->prepare("
            SELECT l.*, r.name as region_name, c.name as country_name 
            FROM locations l
            LEFT JOIN regions r ON l.region_id = r.id
            LEFT JOIN countries c ON r.country_id = c.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new self($pdo, $data) : null;
    }
    
    /**
     * Trouver tous les lieux d'une région
     */
    public static function findByRegion($regionId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $stmt = $pdo->prepare("
            SELECT l.*, r.name as region_name 
            FROM locations l
            JOIN regions r ON l.region_id = r.id
            WHERE l.region_id = ?
            ORDER BY l.name ASC
        ");
        $stmt->execute([$regionId]);
        
        $locations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $locations[] = new self($pdo, $row);
        }
        return $locations;
    }
    
    /**
     * Récupérer les pièces (Rooms) de ce lieu
     */
    public function getRooms()
    {
        try {
            // Note: Room class uses 'places' table
            $stmt = $this->pdo->prepare("
                SELECT * FROM places WHERE location_id = ? ORDER BY title ASC
            ");
            $stmt->execute([$this->id]);
            
            $rooms = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rooms[] = new Room($this->pdo, $row);
            }
            return $rooms;
            
        } catch (PDOException $e) {
            error_log("Erreur récupération rooms pour location {$this->id}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Assigner une pièce à ce lieu
     */
    public function addRoom($roomId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE places SET location_id = ? WHERE id = ?");
            return $stmt->execute([$this->id, $roomId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Supprimer le lieu (et mettre à jour les pièces contenues)
     */
    public function delete()
    {
        try {
            // Les pièces deviennent orphelines de lieu (set NULL) grâce à la contrainte FK,
            // ou on peut le forcer ici si besoin.
            // FK ON DELETE SET NULL est défini dans la migration.
            
            $stmt = $this->pdo->prepare("DELETE FROM locations WHERE id = ?");
            return $stmt->execute([$this->id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Sauvegarder modifications
     */
    public function save()
    {
        if (!$this->id) return false;
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE locations 
                SET name = ?, description = ?, region_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$this->name, $this->description, $this->region_id, $this->id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
