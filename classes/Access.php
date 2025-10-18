<?php

/**
 * Classe Access - Gestion des accès entre lieux
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux accès :
 * - Création, lecture, mise à jour, suppression
 * - Gestion de la visibilité, ouverture et pièges
 * - Gestion des positions sur la carte
 */
class Access
{
    private $pdo;
    
    // Propriétés de l'accès
    public $id;
    public $from_place_id;
    public $to_place_id;
    public $name;
    public $description;
    public $is_visible;
    public $is_open;
    public $is_trapped;
    public $trap_description;
    public $trap_difficulty;
    public $trap_damage;
    public $position_x;
    public $position_y;
    public $is_on_map;
    public $created_at;
    public $updated_at;
    
    // Propriétés des lieux (chargées à la demande)
    public $from_place_name;
    public $to_place_name;
    
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
     * Sauvegarder l'accès (création ou mise à jour)
     */
    public function save()
    {
        try {
            if ($this->id) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE accesses 
                    SET from_place_id = ?, to_place_id = ?, name = ?, description = ?, 
                        is_visible = ?, is_open = ?, is_trapped = ?, trap_description = ?, 
                        trap_difficulty = ?, trap_damage = ?, position_x = ?, position_y = ?, 
                        is_on_map = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->from_place_id,
                    $this->to_place_id,
                    $this->name,
                    $this->description,
                    $this->is_visible ? 1 : 0,
                    $this->is_open ? 1 : 0,
                    $this->is_trapped ? 1 : 0,
                    $this->trap_description,
                    $this->trap_difficulty,
                    $this->trap_damage,
                    $this->position_x,
                    $this->position_y,
                    $this->is_on_map ? 1 : 0,
                    $this->id
                ]);
            } else {
                // Création
                $stmt = $this->pdo->prepare("
                    INSERT INTO accesses (from_place_id, to_place_id, name, description, 
                                        is_visible, is_open, is_trapped, trap_description, 
                                        trap_difficulty, trap_damage, position_x, position_y, 
                                        is_on_map, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $this->from_place_id,
                    $this->to_place_id,
                    $this->name,
                    $this->description,
                    $this->is_visible ? 1 : 0,
                    $this->is_open ? 1 : 0,
                    $this->is_trapped ? 1 : 0,
                    $this->trap_description,
                    $this->trap_difficulty,
                    $this->trap_damage,
                    $this->position_x,
                    $this->position_y,
                    $this->is_on_map ? 1 : 0
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde de l'accès: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer l'accès
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM accesses WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'accès: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger un accès par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       fp.title as from_place_name, 
                       tp.title as to_place_name
                FROM accesses a
                LEFT JOIN places fp ON a.from_place_id = fp.id
                LEFT JOIN places tp ON a.to_place_id = tp.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement de l'accès: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer tous les accès d'un lieu (sortants)
     */
    public static function getFromPlace($place_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       fp.title as from_place_name, 
                       tp.title as to_place_name
                FROM accesses a
                LEFT JOIN places fp ON a.from_place_id = fp.id
                LEFT JOIN places tp ON a.to_place_id = tp.id
                WHERE a.from_place_id = ?
                ORDER BY a.name
            ");
            $stmt->execute([$place_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $accesses = [];
            foreach ($results as $data) {
                $accesses[] = new self($pdo, $data);
            }
            
            return $accesses;
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des accès sortants: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les accès vers un lieu (entrants)
     */
    public static function getToPlace($place_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       fp.title as from_place_name, 
                       tp.title as to_place_name
                FROM accesses a
                LEFT JOIN places fp ON a.from_place_id = fp.id
                LEFT JOIN places tp ON a.to_place_id = tp.id
                WHERE a.to_place_id = ?
                ORDER BY a.name
            ");
            $stmt->execute([$place_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $accesses = [];
            foreach ($results as $data) {
                $accesses[] = new self($pdo, $data);
            }
            
            return $accesses;
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des accès entrants: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les accès d'un lieu (sortants et entrants)
     */
    public static function getAllForPlace($place_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, 
                       fp.title as from_place_name, 
                       tp.title as to_place_name
                FROM accesses a
                LEFT JOIN places fp ON a.from_place_id = fp.id
                LEFT JOIN places tp ON a.to_place_id = tp.id
                WHERE a.from_place_id = ? OR a.to_place_id = ?
                ORDER BY a.name
            ");
            $stmt->execute([$place_id, $place_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $accesses = [];
            foreach ($results as $data) {
                $accesses[] = new self($pdo, $data);
            }
            
            return $accesses;
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement de tous les accès: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les lieux accessibles depuis un lieu donné
     */
    public static function getAccessiblePlaces($place_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.id, p.title, p.map_url, p.notes,
                       a.name as access_name, a.description as access_description,
                       a.is_visible, a.is_open, a.is_trapped,
                       a.trap_description, a.trap_difficulty, a.trap_damage
                FROM places p
                INNER JOIN accesses a ON p.id = a.to_place_id
                WHERE a.from_place_id = ?
                ORDER BY p.title
            ");
            $stmt->execute([$place_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des lieux accessibles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les lieux disponibles pour créer un accès
     */
    public static function getAvailablePlaces($exclude_place_id = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT id, title FROM places ORDER BY title";
            $params = [];
            
            if ($exclude_place_id) {
                $sql = "SELECT id, title FROM places WHERE id != ? ORDER BY title";
                $params[] = $exclude_place_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des lieux disponibles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifier si un accès existe déjà entre deux lieux
     */
    public static function existsBetween($from_place_id, $to_place_id, $name = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT COUNT(*) FROM accesses WHERE from_place_id = ? AND to_place_id = ?";
            $params = [$from_place_id, $to_place_id];
            
            if ($name) {
                $sql .= " AND name = ?";
                $params[] = $name;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'existence de l'accès: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer un nouvel accès
     */
    public static function create($from_place_id, $to_place_id, $name, $description = '', 
                                 $is_visible = true, $is_open = true, $is_trapped = false,
                                 $trap_description = '', $trap_difficulty = null, $trap_damage = '',
                                 $position_x = 0, $position_y = 0, $is_on_map = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO accesses (from_place_id, to_place_id, name, description, 
                                    is_visible, is_open, is_trapped, trap_description, 
                                    trap_difficulty, trap_damage, position_x, position_y, 
                                    is_on_map, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $success = $stmt->execute([
                $from_place_id, $to_place_id, $name, $description,
                $is_visible ? 1 : 0, $is_open ? 1 : 0, $is_trapped ? 1 : 0,
                $trap_description, $trap_difficulty, $trap_damage,
                $position_x, $position_y, $is_on_map ? 1 : 0
            ]);
            
            if ($success) {
                return $pdo->lastInsertId();
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'accès: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir le statut de l'accès sous forme de texte
     */
    public function getStatusText()
    {
        $status = [];
        
        if (!$this->is_visible) {
            $status[] = "Caché";
        }
        
        if (!$this->is_open) {
            $status[] = "Fermé";
        }
        
        if ($this->is_trapped) {
            $status[] = "Piégé";
        }
        
        return empty($status) ? "Normal" : implode(", ", $status);
    }
    
    /**
     * Obtenir la classe CSS pour l'affichage du statut
     */
    public function getStatusClass()
    {
        if ($this->is_trapped) {
            return "text-danger";
        } elseif (!$this->is_open) {
            return "text-warning";
        } elseif (!$this->is_visible) {
            return "text-info";
        } else {
            return "text-success";
        }
    }
    
    /**
     * Obtenir l'icône pour l'affichage du statut
     */
    public function getStatusIcon()
    {
        if ($this->is_trapped) {
            return "fas fa-exclamation-triangle";
        } elseif (!$this->is_open) {
            return "fas fa-lock";
        } elseif (!$this->is_visible) {
            return "fas fa-eye-slash";
        } else {
            return "fas fa-check-circle";
        }
    }
}
