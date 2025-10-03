<?php

/**
 * Classe PNJ - Gestion des Personnages Non-Joueurs
 * 
 * Cette classe hérite de Character et ajoute des fonctionnalités spécifiques aux PNJs :
 * - Gestion de la visibilité et identification
 * - Gestion de l'équipement spécifique aux PNJs
 * - Gestion des lieux d'apparition
 * - Gestion des relations avec les campagnes
 */
class PNJ extends Character
{
    // Propriétés spécifiques aux PNJs
    public $place_id;
    public $is_visible;
    public $is_identified;
    public $profile_photo;
    public $description;
    public $npc_character_id; // Référence vers un personnage de joueur si c'est un PNJ basé sur un personnage
    
    /**
     * Constructeur de la classe PNJ
     * 
     * @param array $data Données du PNJ
     * @param PDO $pdo Instance PDO (optionnelle)
     */
    public function __construct($data = [], PDO $pdo = null)
    {
        // Appeler le constructeur parent
        parent::__construct($data, $pdo);
        
        // Initialiser les propriétés spécifiques aux PNJs
        $this->place_id = $data['place_id'] ?? null;
        $this->is_visible = $data['is_visible'] ?? true;
        $this->is_identified = $data['is_identified'] ?? false;
        $this->profile_photo = $data['profile_photo'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->npc_character_id = $data['npc_character_id'] ?? null;
    }
    
    /**
     * Hydrate l'objet avec les données de la base
     * 
     * @param array $data Données à hydrater
     */
    protected function hydrate($data)
    {
        // Appeler la méthode parent
        parent::hydrate($data);
        
        // Hydrater les propriétés spécifiques aux PNJs
        $this->place_id = $data['place_id'] ?? null;
        $this->is_visible = (bool)($data['is_visible'] ?? true);
        $this->is_identified = (bool)($data['is_identified'] ?? false);
        $this->profile_photo = $data['profile_photo'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->npc_character_id = $data['npc_character_id'] ?? null;
    }
    
    /**
     * Sauvegarde le PNJ dans la base de données
     * 
     * @return bool True si la sauvegarde a réussi
     * @throws Exception En cas d'erreur
     */
    public function save()
    {
        try {
            $pdo = $this->getPdo();
            
            if ($this->id === null) {
                // Création d'un nouveau PNJ
                $sql = "INSERT INTO place_npcs (place_id, name, description, profile_photo, is_visible, is_identified, npc_character_id, monster_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $this->place_id,
                    $this->name,
                    $this->description,
                    $this->profile_photo,
                    $this->is_visible ? 1 : 0,
                    $this->is_identified ? 1 : 0,
                    $this->npc_character_id
                ]);
                
                $this->id = $pdo->lastInsertId();
                return true;
            } else {
                // Mise à jour d'un PNJ existant
                $sql = "UPDATE place_npcs SET place_id = ?, name = ?, description = ?, profile_photo = ?, is_visible = ?, is_identified = ?, npc_character_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $this->place_id,
                    $this->name,
                    $this->description,
                    $this->profile_photo,
                    $this->is_visible ? 1 : 0,
                    $this->is_identified ? 1 : 0,
                    $this->npc_character_id,
                    $this->id
                ]);
                
                return $result;
            }
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la sauvegarde du PNJ: " . $e->getMessage());
        }
    }
    
    /**
     * Supprime le PNJ de la base de données
     * 
     * @return bool True si la suppression a réussi
     * @throws Exception En cas d'erreur
     */
    public function delete()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de supprimer un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            
            // Supprimer l'équipement du PNJ
            $sql = "DELETE FROM npc_equipment WHERE npc_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            
            // Supprimer le PNJ
            $sql = "DELETE FROM place_npcs WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$this->id]);

            if ($result && $stmt->rowCount() > 0) {
                $this->id = null;
                return true;
            }

            return false;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression du PNJ: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère un PNJ par son ID
     * 
     * @param int $id ID du PNJ
     * @param PDO $pdo Instance PDO (optionnelle)
     * @return PNJ|null Instance du PNJ ou null si non trouvé
     */
    public static function findById($id, PDO $pdo = null)
    {
        if ($pdo === null) {
            $pdo = getPDO();
        }

        try {
            $sql = "SELECT * FROM place_npcs WHERE id = ? AND monster_id IS NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($data, $pdo);
            }

            return null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du PNJ: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les PNJs d'un lieu
     * 
     * @param int $placeId ID du lieu
     * @param PDO $pdo Instance PDO (optionnelle)
     * @return array Liste des PNJs
     */
    public static function findByPlaceId($placeId, PDO $pdo = null)
    {
        if ($pdo === null) {
            $pdo = getPDO();
        }

        try {
            $sql = "SELECT * FROM place_npcs WHERE place_id = ? AND monster_id IS NULL ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$placeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $npcs = [];
            foreach ($results as $data) {
                $npcs[] = new self($data, $pdo);
            }

            return $npcs;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des PNJs: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère l'équipement du PNJ
     * 
     * @return array Liste de l'équipement
     */
    public function getEquipment()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT * FROM npc_equipment WHERE npc_id = ? ORDER BY obtained_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
        }
    }
    
    /**
     * Ajoute un objet à l'équipement du PNJ
     * 
     * @param array $itemData Données de l'objet
     * @return bool True si l'ajout a réussi
     */
    public function addEquipment($itemData)
    {
        if ($this->id === null) {
            throw new Exception("Impossible d'ajouter de l'équipement à un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            $sql = "INSERT INTO npc_equipment (npc_id, place_id, magical_item_id, equipped, obtained_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $this->id,
                $this->place_id,
                $itemData['magical_item_id'],
                $itemData['equipped'] ?? 0
            ]);
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de l'équipement: " . $e->getMessage());
        }
    }
    
    /**
     * Retire un objet de l'équipement du PNJ
     * 
     * @param int $equipmentId ID de l'équipement
     * @return bool True si le retrait a réussi
     */
    public function removeEquipment($equipmentId)
    {
        if ($this->id === null) {
            throw new Exception("Impossible de retirer de l'équipement d'un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            $sql = "DELETE FROM npc_equipment WHERE id = ? AND npc_id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$equipmentId, $this->id]);
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du retrait de l'équipement: " . $e->getMessage());
        }
    }
    
    /**
     * Met à jour le statut d'équipement d'un objet
     * 
     * @param int $equipmentId ID de l'équipement
     * @param bool $equipped Statut d'équipement
     * @return bool True si la mise à jour a réussi
     */
    public function updateEquipmentStatus($equipmentId, $equipped)
    {
        if ($this->id === null) {
            throw new Exception("Impossible de mettre à jour l'équipement d'un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            $sql = "UPDATE npc_equipment SET equipped = ? WHERE id = ? AND npc_id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$equipped ? 1 : 0, $equipmentId, $this->id]);
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour de l'équipement: " . $e->getMessage());
        }
    }
    
    /**
     * Bascule la visibilité du PNJ
     * 
     * @return bool True si le basculement a réussi
     */
    public function toggleVisibility()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de basculer la visibilité d'un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            $sql = "UPDATE place_npcs SET is_visible = NOT is_visible WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->is_visible = !$this->is_visible;
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du basculement de la visibilité: " . $e->getMessage());
        }
    }
    
    /**
     * Bascule l'identification du PNJ
     * 
     * @return bool True si le basculement a réussi
     */
    public function toggleIdentification()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de basculer l'identification d'un PNJ qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            $sql = "UPDATE place_npcs SET is_identified = NOT is_identified WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->is_identified = !$this->is_identified;
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du basculement de l'identification: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les informations du personnage associé (si applicable)
     * 
     * @return Character|null Instance du personnage ou null
     */
    public function getAssociatedCharacter()
    {
        if ($this->npc_character_id === null) {
            return null;
        }

        try {
            return Character::findById($this->npc_character_id);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Convertit le PNJ en tableau associatif
     * 
     * @return array Données du PNJ
     */
    public function toArray()
    {
        $data = parent::toArray();
        
        // Ajouter les propriétés spécifiques aux PNJs
        $data['place_id'] = $this->place_id;
        $data['is_visible'] = $this->is_visible;
        $data['is_identified'] = $this->is_identified;
        $data['profile_photo'] = $this->profile_photo;
        $data['description'] = $this->description;
        $data['npc_character_id'] = $this->npc_character_id;
        
        return $data;
    }
    
    // ========================================
    // GETTERS ET SETTERS SPÉCIFIQUES AUX PNJs
    // ========================================
    
    public function getPlaceId()
    {
        return $this->place_id;
    }
    
    public function setPlaceId($placeId)
    {
        $this->place_id = $placeId;
    }
    
    public function getIsVisible()
    {
        return $this->is_visible;
    }
    
    public function setIsVisible($isVisible)
    {
        $this->is_visible = $isVisible;
    }
    
    public function getIsIdentified()
    {
        return $this->is_identified;
    }
    
    public function setIsIdentified($isIdentified)
    {
        $this->is_identified = $isIdentified;
    }
    
    public function getProfilePhoto()
    {
        return $this->profile_photo;
    }
    
    public function setProfilePhoto($profilePhoto)
    {
        $this->profile_photo = $profilePhoto;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    public function getNpcCharacterId()
    {
        return $this->npc_character_id;
    }
    
    public function setNpcCharacterId($npcCharacterId)
    {
        $this->npc_character_id = $npcCharacterId;
    }
}
