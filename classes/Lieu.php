<?php

/**
 * Classe Lieu - Gestion des lieux D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux lieux :
 * - Création, lecture, mise à jour, suppression
 * - Gestion de la hiérarchie géographique (monde, pays, région)
 * - Gestion des objets dans les lieux
 * - Gestion des tokens (pions) sur les plans
 * - Gestion des associations avec les campagnes
 */
class Lieu
{
    private $pdo;
    
    // Propriétés du lieu
    public $id;
    public $title;
    public $map_url;
    public $notes;
    public $position;
    public $country_id;
    public $region_id;
    public $created_at;
    public $updated_at;
    
    // Propriétés géographiques (chargées à la demande)
    public $country_name;
    public $region_name;
    public $world_name;
    public $world_id;
    
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
     * Sauvegarder le lieu (création ou mise à jour)
     */
    public function save()
    {
        try {
            if ($this->id) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE places 
                    SET title = ?, map_url = ?, notes = ?, position = ?, 
                        country_id = ?, region_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->title,
                    $this->map_url,
                    $this->notes,
                    $this->position,
                    $this->country_id,
                    $this->region_id,
                    $this->id
                ]);
            } else {
                // Création
                $stmt = $this->pdo->prepare("
                    INSERT INTO places (title, map_url, notes, position, country_id, region_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $this->title,
                    $this->map_url,
                    $this->notes,
                    $this->position,
                    $this->country_id,
                    $this->region_id
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du lieu: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le lieu
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM places WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du lieu: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger un lieu par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM places WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du lieu: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les lieux d'une campagne
     */
    public static function findByCampaign($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as country_name, r.name as region_name, w.name as world_name, w.id as world_id
                FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                LEFT JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions r ON p.region_id = r.id
                LEFT JOIN worlds w ON c.world_id = w.id
                WHERE pc.campaign_id = ?
                ORDER BY w.name, c.name, r.name, p.title
            ");
            $stmt->execute([$campaignId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lieux = [];
            foreach ($results as $data) {
                $lieux[] = new self($pdo, $data);
            }
            return $lieux;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des lieux de campagne: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les lieux d'une région
     */
    public static function findByRegion($regionId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as country_name, r.name as region_name, w.name as world_name, w.id as world_id
                FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions r ON p.region_id = r.id
                LEFT JOIN worlds w ON c.world_id = w.id
                WHERE p.region_id = ?
                ORDER BY p.title
            ");
            $stmt->execute([$regionId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lieux = [];
            foreach ($results as $data) {
                $lieux[] = new self($pdo, $data);
            }
            return $lieux;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des lieux de région: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les lieux d'un pays
     */
    public static function findByCountry($countryId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as country_name, r.name as region_name, w.name as world_name, w.id as world_id
                FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions r ON p.region_id = r.id
                LEFT JOIN worlds w ON c.world_id = w.id
                WHERE p.country_id = ?
                ORDER BY r.name, p.title
            ");
            $stmt->execute([$countryId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $lieux = [];
            foreach ($results as $data) {
                $lieux[] = new self($pdo, $data);
            }
            return $lieux;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des lieux de pays: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Charger les informations géographiques complètes
     */
    public function loadGeography()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.name as country_name, r.name as region_name, w.name as world_name, w.id as world_id
                FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions r ON p.region_id = r.id
                LEFT JOIN worlds w ON c.world_id = w.id
                WHERE p.id = ?
            ");
            $stmt->execute([$this->id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $this->country_name = $data['country_name'];
                $this->region_name = $data['region_name'];
                $this->world_name = $data['world_name'];
                $this->world_id = $data['world_id'];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement de la géographie: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir le chemin géographique complet
     */
    public function getFullPath()
    {
        if (!$this->world_name) {
            $this->loadGeography();
        }
        
        $path = [];
        if ($this->world_name) $path[] = $this->world_name;
        if ($this->country_name) $path[] = $this->country_name;
        if ($this->region_name) $path[] = $this->region_name;
        $path[] = $this->title;
        
        return implode(' > ', $path);
    }
    
    /**
     * Obtenir les objets dans ce lieu
     */
    public function getObjects()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM items 
                WHERE place_id = ? 
                ORDER BY display_name
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des objets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les tokens (pions) dans ce lieu
     */
    public function getTokens()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM place_tokens 
                WHERE place_id = ? 
                ORDER BY token_type, entity_id
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des tokens: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les positions de tous les pions dans ce lieu
     * Retourne un tableau associatif avec les positions formatées
     */
    public function getTokenPositions()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT token_type, entity_id, position_x, position_y, is_on_map
                FROM place_tokens 
                WHERE place_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $tokenPositions = [];
            while ($row = $stmt->fetch()) {
                $tokenPositions[$row['token_type'] . '_' . $row['entity_id']] = [
                    'x' => (int)$row['position_x'],
                    'y' => (int)$row['position_y'],
                    'is_on_map' => (bool)$row['is_on_map']
                ];
            }
            
            return $tokenPositions;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des positions des tokens: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les campagnes associées à ce lieu
     */
    public function getCampaigns()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.* FROM campaigns c
                INNER JOIN place_campaigns pc ON c.id = pc.campaign_id
                WHERE pc.place_id = ?
                ORDER BY c.title
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des campagnes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les personnages d'un joueur présents dans ce lieu
     */
    public function getPlayerCharacters($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.id, c.name, c.level, c.profile_photo, c.class_id, cl.name as class_name
                FROM characters c
                LEFT JOIN classes cl ON c.class_id = cl.id
                WHERE c.user_id = ? AND c.id IN (
                    SELECT sp.character_id FROM place_players sp WHERE sp.place_id = ? AND sp.character_id IS NOT NULL
                )
                ORDER BY c.name ASC
            ");
            $stmt->execute([$userId, $this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages du joueur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les joueurs présents dans ce lieu
     */
    public function getAllPlayers()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sp.player_id, u.username, c.id as character_id, c.name as character_name, c.profile_photo, c.level, cl.name as class_name
                FROM place_players sp 
                JOIN users u ON sp.player_id = u.id 
                LEFT JOIN characters c ON sp.character_id = c.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                WHERE sp.place_id = ?
                ORDER BY u.username ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les joueurs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les joueurs présents dans ce lieu avec informations détaillées (pour view_place.php)
     */
    public function getAllPlayersDetailed()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sp.player_id, u.username, ch.id AS character_id, ch.name AS character_name, ch.profile_photo, ch.class_id, ch.hit_points_current, ch.hit_points_max 
                FROM place_players sp 
                JOIN users u ON sp.player_id = u.id 
                LEFT JOIN characters ch ON sp.character_id = ch.id 
                WHERE sp.place_id = ? 
                ORDER BY u.username ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des joueurs détaillés: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les PNJ présents dans ce lieu (seulement ceux visibles)
     */
    public function getVisibleNpcs()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo
                FROM place_npcs sn 
                LEFT JOIN characters c ON sn.npc_character_id = c.id
                WHERE sn.place_id = ? AND sn.monster_id IS NULL AND sn.is_visible = 1
                ORDER BY sn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les PNJ présents dans ce lieu (pour view_place.php - sans filtres de visibilité)
     */
    public function getAllNpcs()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo 
                FROM place_npcs sn 
                LEFT JOIN characters c ON sn.npc_character_id = c.id 
                WHERE sn.place_id = ? 
                ORDER BY sn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les monstres présents dans ce lieu (seulement ceux visibles)
     */
    public function getVisibleMonsters()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sn.id, sn.name, sn.description, sn.monster_id, sn.quantity, sn.current_hit_points, sn.is_visible, sn.is_identified,
                       m.type, m.size, m.challenge_rating, m.hit_points, m.armor_class, m.csv_id
                FROM place_npcs sn 
                JOIN dnd_monsters m ON sn.monster_id = m.id 
                WHERE sn.place_id = ? AND sn.monster_id IS NOT NULL AND sn.is_visible = 1
                ORDER BY sn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des monstres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les objets présents dans ce lieu (seulement ceux visibles et non attribués)
     */
    public function getVisibleObjects()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, display_name, object_type, type_precis, description, is_visible, is_identified, is_equipped,
                       position_x, position_y, is_on_map, owner_type, owner_id,
                       poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed
                FROM items 
                WHERE place_id = ? AND is_visible = 1 AND (owner_type = 'place' OR owner_type IS NULL)
                ORDER BY display_name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des objets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir TOUS les objets présents dans ce lieu (y compris ceux attribués) - pour le MJ
     */
    public function getAllObjects()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, display_name, object_type, type_precis, description, is_visible, is_identified, is_equipped,
                       position_x, position_y, is_on_map, owner_type, owner_id,
                       poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed
                FROM items 
                WHERE place_id = ?
                ORDER BY display_name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les objets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Associer ce lieu à une campagne
     */
    public function addToCampaign($campaignId)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO place_campaigns (place_id, campaign_id, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
            ");
            $stmt->execute([$this->id, $campaignId]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'association du lieu à la campagne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dissocier ce lieu d'une campagne
     */
    public function removeFromCampaign($campaignId)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM place_campaigns 
                WHERE place_id = ? AND campaign_id = ?
            ");
            $stmt->execute([$this->id, $campaignId]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la dissociation du lieu de la campagne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si ce lieu a une carte
     */
    public function hasMap()
    {
        return !empty($this->map_url);
    }
    
    /**
     * Obtenir l'URL de la carte
     */
    public function getMapUrl()
    {
        return $this->map_url ?: null;
    }
    
    /**
     * Obtenir les notes du lieu
     */
    public function getNotes()
    {
        return $this->notes ?: '';
    }
    
    /**
     * Obtenir le titre du lieu
     */
    public function getTitle()
    {
        return $this->title ?: '';
    }
    
    /**
     * Obtenir la position du lieu
     */
    public function getPosition()
    {
        return $this->position ?: 0;
    }
    
    /**
     * Obtenir l'ID du pays
     */
    public function getCountryId()
    {
        return $this->country_id;
    }
    
    /**
     * Obtenir l'ID de la région
     */
    public function getRegionId()
    {
        return $this->region_id;
    }
    
    /**
     * Convertir l'objet en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'map_url' => $this->map_url,
            'notes' => $this->notes,
            'position' => $this->position,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'country_name' => $this->country_name,
            'region_name' => $this->region_name,
            'world_name' => $this->world_name,
            'world_id' => $this->world_id
        ];
    }
}
