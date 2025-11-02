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
     * Obtenir les informations d'un joueur spécifique dans ce lieu
     */
    public function getPlayerInfo($playerId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.username, ch.id AS character_id, ch.name AS character_name 
                FROM place_players sp 
                JOIN users u ON sp.player_id = u.id 
                LEFT JOIN characters ch ON sp.character_id = ch.id 
                WHERE sp.place_id = ? AND sp.player_id = ?
            ");
            $stmt->execute([$this->id, $playerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations du joueur: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si un joueur est présent dans ce lieu
     */
    public function isPlayerPresent($playerId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM place_players WHERE place_id = ? AND player_id = ?");
            $stmt->execute([$this->id, $playerId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de présence du joueur: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le character_id d'un joueur dans ce lieu
     */
    public function getPlayerCharacterId($playerId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT character_id FROM place_players WHERE place_id = ? AND player_id = ?");
            $stmt->execute([$this->id, $playerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['character_id'] : null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du character_id du joueur: " . $e->getMessage());
            return null;
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
     * Obtenir tous les PNJ présents dans ce lieu avec informations détaillées (pour view_place.php)
     */
    public function getAllNpcsDetailed()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, 
                       c.profile_photo AS character_profile_photo, c.hit_points_current, c.hit_points_max,
                       n.profile_photo AS npc_profile_photo
                FROM place_npcs sn 
                LEFT JOIN characters c ON sn.npc_character_id = c.id 
                LEFT JOIN npcs n ON sn.npc_character_id = n.id
                WHERE sn.place_id = ? AND sn.monster_id IS NULL 
                ORDER BY sn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des PNJ détaillés: " . $e->getMessage());
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
                SELECT pm.id, pm.monster_id, pm.is_visible, pm.is_identified,
                       m.name, m.description, m.current_hit_points, m.max_hit_points, m.quantity,
                       dt.name as type_name, dt.type, dt.size, dt.challenge_rating, dt.hit_points, dt.armor_class, dt.csv_id
                FROM place_monsters pm
                JOIN monsters m ON pm.monster_id = m.id
                JOIN dnd_monsters dt ON m.monster_type_id = dt.id
                WHERE pm.place_id = ? AND pm.is_visible = 1
                ORDER BY m.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des monstres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les monstres présents dans ce lieu (pour le MJ)
     */
    public function getAllMonsters()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pm.id, pm.monster_id, pm.is_visible, pm.is_identified,
                       m.name, m.description, m.current_hit_points, m.max_hit_points, m.quantity,
                       dt.name as type_name, dt.type, dt.size, dt.challenge_rating, dt.hit_points, dt.armor_class, dt.csv_id
                FROM place_monsters pm
                JOIN monsters m ON pm.monster_id = m.id
                JOIN dnd_monsters dt ON m.monster_type_id = dt.id
                WHERE pm.place_id = ?
                ORDER BY m.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les monstres: " . $e->getMessage());
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
     * Obtenir les objets non attribués du lieu (pour l'interface MJ)
     */
    public function getUnassignedObjects()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, display_name, object_type, type_precis, description, is_visible, is_identified, is_equipped,
                       position_x, position_y, is_on_map, owner_type, owner_id,
                       poison_id, weapon_id, armor_id, gold_coins, silver_coins, copper_coins, letter_content, is_sealed
                FROM items 
                WHERE place_id = ? AND (owner_type = 'place' OR owner_type IS NULL)
                ORDER BY display_name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des objets non attribués: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Recharger tous les objets du lieu (pour view_place.php)
     */
    public function reloadAllObjects()
    {
        return $this->getAllObjects();
    }
    
    /**
     * Recharger les objets visibles du lieu (pour view_place.php)
     */
    public function reloadVisibleObjects()
    {
        return $this->getVisibleObjects();
    }
    
    /**
     * Ajouter un personnage du MJ comme PNJ dans ce lieu
     */
    public function addDmCharacterAsNpc($characterId, $campaignId)
    {
        try {
            // IMPORTANT: Retirer ce personnage de tous les autres lieux de la campagne
            // Un personnage ne peut pas se trouver dans deux lieux à la fois
            $stmt = $this->pdo->prepare("
                DELETE FROM place_npcs 
                WHERE npc_character_id = ? AND place_id IN (
                    SELECT p.id FROM places p
                    INNER JOIN place_campaigns pc ON p.id = pc.place_id
                    WHERE pc.campaign_id = ?
                )
            ");
            $stmt->execute([$characterId, $campaignId]);
            
            // Récupérer les informations du personnage
            $character = Character::findById($characterId);
            if (!$character) {
                return ['success' => false, 'message' => 'Personnage introuvable.'];
            }
            
            // Ajouter le personnage au nouveau lieu
            $stmt = $this->pdo->prepare("INSERT INTO place_npcs (place_id, name, npc_character_id) VALUES (?, ?, ?)");
            $stmt->execute([$this->id, $character->name, $characterId]);
            
            return ['success' => true, 'message' => 'PNJ (personnage du MJ) ajouté au lieu (retiré automatiquement des autres lieux).'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du personnage MJ comme PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout du PNJ: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ajouter un joueur dans ce lieu
     */
    public function addPlayer($playerId, $characterId, $campaignId)
    {
        try {
            // Vérifier que le joueur n'est pas déjà dans le lieu
            if ($this->isPlayerPresent($playerId)) {
                return ['success' => false, 'message' => 'Ce joueur est déjà présent dans ce lieu.'];
            }
            
            // IMPORTANT: Retirer le joueur de tous les autres lieux de la campagne
            // Un personnage ne peut pas se trouver dans deux lieux à la fois
            $stmt = $this->pdo->prepare("
                DELETE FROM place_players 
                WHERE player_id = ? AND place_id IN (
                    SELECT p.id FROM places p
                    INNER JOIN place_campaigns pc ON p.id = pc.place_id
                    WHERE pc.campaign_id = ?
                )
            ");
            $stmt->execute([$playerId, $campaignId]);
            
            // Ajouter le joueur au nouveau lieu
            $stmt = $this->pdo->prepare("INSERT INTO place_players (place_id, player_id, character_id) VALUES (?, ?, ?)");
            $stmt->execute([$this->id, $playerId, $characterId]);
            
            return ['success' => true, 'message' => 'Joueur ajouté au lieu (retiré automatiquement des autres lieux).'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du joueur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout du joueur: ' . $e->getMessage()];
        }
    }
    
    /**
     * Retirer un joueur de ce lieu
     */
    public function removePlayer($playerId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM place_players WHERE place_id = ? AND player_id = ?");
            $stmt->execute([$this->id, $playerId]);
            
            return ['success' => true, 'message' => 'Joueur retiré du lieu.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du retrait du joueur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du retrait du joueur: ' . $e->getMessage()];
        }
    }
    
    /**
     * Retirer un PNJ de ce lieu
     */
    public function removeNpc($npcName)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM place_npcs WHERE place_id = ? AND name = ?");
            $stmt->execute([$this->id, $npcName]);
            
            return ['success' => true, 'message' => 'PNJ retiré du lieu.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du retrait du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du retrait du PNJ: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ajouter un monstre dans ce lieu
     */
    public function addMonster($monsterTypeId, $quantity = 1)
    {
        try {
            // Récupérer les informations du type de monstre
            $stmt = $this->pdo->prepare("SELECT name, hit_points FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$monsterTypeId]);
            $monsterType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$monsterType) {
                return ['success' => false, 'message' => 'Type de monstre introuvable.'];
            }
            
            // Créer l'instance de monstre
            $monsterName = $monsterType['name'];
            if ($quantity > 1) {
                $monsterName .= " (x{$quantity})";
            }
            
            $monster = Monster::create($monsterTypeId, $monsterName, '', $quantity);
            
            if (!$monster) {
                return ['success' => false, 'message' => 'Erreur lors de la création du monstre.'];
            }
            
            // Ajouter le monstre au lieu (invisible par défaut)
            $success = $monster->addToPlace($this->id);
            
            if ($success) {
                return ['success' => true, 'message' => 'Monstre ajouté au lieu.'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de l\'ajout du monstre au lieu.'];
            }
            
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout du monstre: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout du monstre: ' . $e->getMessage()];
        }
    }
    
    /**
     * Retirer un monstre de ce lieu
     */
    public function removeMonster($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM place_npcs WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
            $stmt->execute([$this->id, $npcId]);
            
            return ['success' => true, 'message' => 'Monstre retiré du lieu.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du retrait du monstre: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du retrait du monstre: ' . $e->getMessage()];
        }
    }
    
    /**
     * Basculer la visibilité d'un PNJ
     */
    public function toggleNpcVisibility($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE place_npcs SET is_visible = NOT is_visible WHERE place_id = ? AND id = ? AND monster_id IS NULL");
            $stmt->execute([$this->id, $npcId]);
            
            return ['success' => true, 'message' => 'Visibilité du PNJ mise à jour.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement de visibilité du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du basculement de visibilité: ' . $e->getMessage()];
        }
    }
    
    /**
     * Vérifier s'il y a des joueurs dans un lieu
     * 
     * @param int $placeId ID du lieu
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool True si il y a des joueurs, false sinon
     * @throws Exception En cas d'erreur
     */
    public static function hasPlayersInPlace($placeId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM place_players WHERE place_id = ?");
            $stmt->execute([$placeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification des joueurs dans le lieu: " . $e->getMessage());
        }
    }

    /**
     * Récupérer les informations détaillées d'un monstre dans le lieu
     */
    public function getMonsterDetails($npcId)
    {
        if ($this->id === null) {
            return null;
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("
                SELECT sn.*, m.id as monster_db_id, m.name as monster_name, m.type, m.size, m.challenge_rating, 
                       m.hit_points as max_hit_points, m.armor_class, m.csv_id,
                       m.strength, m.dexterity, m.constitution, m.intelligence, m.wisdom, m.charisma, 
                       m.competences, m.saving_throws, m.damage_immunities, m.damage_resistances, 
                       m.condition_immunities, m.senses, m.languages
                FROM place_npcs sn 
                JOIN dnd_monsters m ON sn.monster_id = m.id 
                WHERE sn.id = ? AND sn.place_id = ? AND sn.monster_id IS NOT NULL
            ");
            $stmt->execute([$npcId, $this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des détails du monstre: " . $e->getMessage());
        }
    }

    /**
     * Basculer la visibilité d'un monstre
     */
    public function toggleMonsterVisibility($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE place_npcs SET is_visible = NOT is_visible WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
            $stmt->execute([$this->id, $npcId]);
            
            return ['success' => true, 'message' => 'Visibilité du monstre mise à jour.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement de visibilité du monstre: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du basculement de visibilité: ' . $e->getMessage()];
        }
    }
    
    /**
     * Basculer l'identification d'un PNJ
     */
    public function toggleNpcIdentification($npcId)
    {
        try {
            // Log de début
            error_log("DEBUG toggleNpcIdentification - Début: place_id={$this->id}, npc_id={$npcId}");
            
            // Vérifier l'état actuel avant modification
            $stmt_check = $this->pdo->prepare("SELECT is_identified, npc_character_id FROM place_npcs WHERE place_id = ? AND id = ? AND monster_id IS NULL");
            $stmt_check->execute([$this->id, $npcId]);
            $current_state = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($current_state) {
                error_log("DEBUG toggleNpcIdentification - État actuel: is_identified=" . ($current_state['is_identified'] ? 'true' : 'false') . ", npc_character_id=" . $current_state['npc_character_id']);
            } else {
                error_log("DEBUG toggleNpcIdentification - ERREUR: PNJ non trouvé avec place_id={$this->id}, npc_id={$npcId}");
                return ['success' => false, 'message' => 'PNJ non trouvé.'];
            }
            
            // Exécuter la mise à jour
            $stmt = $this->pdo->prepare("UPDATE place_npcs SET is_identified = NOT is_identified WHERE place_id = ? AND id = ? AND monster_id IS NULL");
            $result = $stmt->execute([$this->id, $npcId]);
            $rows_affected = $stmt->rowCount();
            
            error_log("DEBUG toggleNpcIdentification - Résultat SQL: success=" . ($result ? 'true' : 'false') . ", rows_affected={$rows_affected}");
            
            // Vérifier l'état après modification
            $stmt_check->execute([$this->id, $npcId]);
            $new_state = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($new_state) {
                error_log("DEBUG toggleNpcIdentification - Nouvel état: is_identified=" . ($new_state['is_identified'] ? 'true' : 'false') . ", npc_character_id=" . $new_state['npc_character_id']);
            }
            
            return ['success' => true, 'message' => 'Identification du PNJ mise à jour.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement d'identification du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du basculement d\'identification: ' . $e->getMessage()];
        }
    }
    
    /**
     * Basculer l'identification d'un monstre
     */
    public function toggleMonsterIdentification($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE place_npcs SET is_identified = NOT is_identified WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
            $stmt->execute([$this->id, $npcId]);
            
            return ['success' => true, 'message' => 'Identification du monstre mise à jour.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement d'identification du monstre: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du basculement d\'identification: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mettre à jour l'URL de la carte et les notes du lieu
     */
    public function updateMapUrl($mapUrl, $notes)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE places SET map_url = ?, notes = ? WHERE id = ?");
            $stmt->execute([$mapUrl, $notes, $this->id]);
            
            // Mettre à jour les propriétés de l'objet
            $this->map_url = $mapUrl;
            $this->notes = $notes;
            
            return ['success' => true, 'message' => 'Plan du lieu mis à jour.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la carte: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mettre à jour le titre du lieu
     */
    public function updateTitle($title)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE places SET title = ? WHERE id = ?");
            $result = $stmt->execute([$title, $this->id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Mettre à jour la propriété de l'objet
                $this->title = $title;
                return ['success' => true, 'message' => 'Nom du lieu mis à jour avec succès.'];
            } else {
                return ['success' => false, 'message' => 'Aucune modification effectuée.'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du titre: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mettre à jour les informations du lieu
     */
    public function updatePlace($title, $notes, $countryId = null, $regionId = null)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE places SET title = ?, notes = ?, country_id = ?, region_id = ? WHERE id = ?");
            $stmt->execute([$title, $notes, $countryId, $regionId, $this->id]);
            
            // Mettre à jour les propriétés de l'objet
            $this->title = $title;
            $this->notes = $notes;
            $this->country_id = $countryId;
            $this->region_id = $regionId;
            
            return ['success' => true, 'message' => 'Lieu mis à jour avec succès.'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du lieu: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    /**
     * Récupérer les informations d'un poison
     */
    public static function getPoisonInfo($poisonId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, nom, description FROM poisons WHERE id = ?");
            $stmt->execute([$poisonId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du poison: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'un objet magique
     */
    public static function getMagicalItemInfo($itemId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, nom, description FROM magical_items WHERE id = ?");
            $stmt->execute([$itemId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'objet magique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'une arme
     */
    public static function getWeaponInfo($weaponId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, name as nom, CONCAT('Dégâts: ', damage, ' | Poids: ', weight, ' | Prix: ', price, ' | Type: ', type) as description FROM weapons WHERE id = ?");
            $stmt->execute([$weaponId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'arme: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'une armure
     */
    public static function getArmorInfo($armorId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, name as nom, CONCAT('CA: ', ac_formula, ' | Poids: ', weight, ' | Prix: ', price, ' | Type: ', type) as description FROM armor WHERE id = ?");
            $stmt->execute([$armorId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'armure: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'un objet magique par csv_id
     */
    public static function getMagicalItemInfoByCsvId($csvId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM magical_items WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'objet magique par csv_id: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'un poison par csv_id
     */
    public static function getPoisonInfoByCsvId($csvId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du poison par csv_id: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'un PNJ dans ce lieu
     */
    public function getNpcInfo($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ?");
            $stmt->execute([$npcId, $this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du PNJ: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les informations d'un monstre dans ce lieu
     */
    public function getMonsterInfo($monsterId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT name FROM place_npcs WHERE id = ? AND place_id = ? AND monster_id IS NOT NULL");
            $stmt->execute([$monsterId, $this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du monstre: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si un PNJ existe dans ce lieu
     */
    public function npcExists($npcId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM place_npcs WHERE place_id = ? AND id = ?");
            $stmt->execute([$this->id, $npcId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un monstre existe dans ce lieu
     */
    public function monsterExists($monsterId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM place_npcs WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
            $stmt->execute([$this->id, $monsterId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les informations d'un objet dans ce lieu
     */
    public function getObjectInfo($objectId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ? AND place_id = ?");
            $stmt->execute([$objectId, $this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'objet: " . $e->getMessage());
            return null;
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

    /**
     * Vérifier si un lieu appartient à une campagne
     * 
     * @param int $placeId ID du lieu
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool True si le lieu appartient à la campagne
     */
    public static function belongsToCampaign($placeId, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 1 FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE p.id = ? AND pc.campaign_id = ?
                LIMIT 1
            ");
            $stmt->execute([$placeId, $campaignId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'appartenance du lieu à la campagne: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer un nouveau lieu
     * 
     * @param string $title Titre du lieu
     * @param string $mapUrl URL de la carte (optionnel)
     * @param string $notes Notes du lieu (optionnel)
     * @param int $position Position du lieu (par défaut 0)
     * @param int $countryId ID du pays (optionnel)
     * @param int $regionId ID de la région (optionnel)
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return int|null ID du lieu créé ou null si échec
     */
    public static function create($title, $mapUrl = '', $notes = '', $position = 0, $countryId = null, $regionId = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO places (title, map_url, notes, position, country_id, region_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $success = $stmt->execute([$title, $mapUrl, $notes, $position, $countryId, $regionId]);
            
            if ($success) {
                return $pdo->lastInsertId();
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du lieu: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier que plusieurs lieux appartiennent à une campagne
     * 
     * @param array $placeIds IDs des lieux
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool True si tous les lieux appartiennent à la campagne
     */
    public static function allBelongToCampaign($placeIds, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        if (empty($placeIds)) {
            return false;
        }
        
        try {
            $placeholders = str_repeat('?,', count($placeIds) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE p.id IN ($placeholders) AND pc.campaign_id = ?
            ");
            $params = array_merge($placeIds, [$campaignId]);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == count($placeIds);
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'appartenance des lieux à la campagne: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les lieux d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des IDs des lieux de la campagne
     */
    public static function getPlaceIdsByCampaign($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.id FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE pc.campaign_id = ?
            ");
            $stmt->execute([$campaignId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lieux de la campagne: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajouter un joueur à un lieu
     * 
     * @param int $placeId ID du lieu
     * @param int $playerId ID du joueur
     * @param int $characterId ID du personnage
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'opération
     */
    public static function addPlayerToPlace($placeId, $playerId, $characterId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO place_players (place_id, player_id, character_id) VALUES (?, ?, ?)");
            return $stmt->execute([$placeId, $playerId, $characterId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du joueur au lieu: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer la position d'un lieu dans une campagne
     * 
     * @param int $placeId ID du lieu
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return int|null Position du lieu ou null si non trouvé
     */
    public static function getPositionInCampaign($placeId, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.position FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE p.id = ? AND pc.campaign_id = ?
            ");
            $stmt->execute([$placeId, $campaignId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (int)$result['position'] : null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la position du lieu: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver un lieu adjacent par position dans une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param int $position Position recherchée
     * @param int $excludePlaceId ID du lieu à exclure de la recherche
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return Lieu|null Lieu trouvé ou null
     */
    public static function findByPositionInCampaign($campaignId, $position, $excludePlaceId = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "
                SELECT p.* FROM places p
                INNER JOIN place_campaigns pc ON p.id = pc.place_id
                WHERE pc.campaign_id = ? AND p.position = ?
            ";
            $params = [$campaignId, $position];
            
            if ($excludePlaceId) {
                $sql .= " AND p.id != ?";
                $params[] = $excludePlaceId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du lieu par position: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour la position du lieu
     * 
     * @param int $newPosition Nouvelle position
     * @return bool Succès de la mise à jour
     */
    public function setPosition($newPosition)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE places SET position = ? WHERE id = ?");
            $result = $stmt->execute([$newPosition, $this->id]);
            
            if ($result) {
                $this->position = $newPosition;
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la position: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour le lieu (sauvegarder les modifications)
     * 
     * @return bool Succès de la mise à jour
     */
    public function update()
    {
        return $this->save();
    }

    /**
     * Transférer un joueur d'un lieu à un autre
     * 
     * @param int $fromPlaceId ID du lieu source
     * @param int $toPlaceId ID du lieu destination
     * @param int $playerId ID du joueur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès du transfert
     */
    public static function transferPlayer($fromPlaceId, $toPlaceId, $playerId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE place_players SET place_id = ? WHERE place_id = ? AND player_id = ?");
            return $stmt->execute([$toPlaceId, $fromPlaceId, $playerId]);
        } catch (PDOException $e) {
            error_log("Erreur lors du transfert du joueur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transférer un PNJ d'un lieu à un autre
     * 
     * @param int $fromPlaceId ID du lieu source
     * @param int $toPlaceId ID du lieu destination
     * @param int $npcId ID du PNJ
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès du transfert
     */
    public static function transferNpc($fromPlaceId, $toPlaceId, $npcId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE place_npcs SET place_id = ? WHERE place_id = ? AND id = ? AND monster_id IS NULL");
            return $stmt->execute([$toPlaceId, $fromPlaceId, $npcId]);
        } catch (PDOException $e) {
            error_log("Erreur lors du transfert du PNJ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transférer un monstre d'un lieu à un autre
     * 
     * @param int $fromPlaceId ID du lieu source
     * @param int $toPlaceId ID du lieu destination
     * @param int $monsterId ID du monstre
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès du transfert
     */
    public static function transferMonster($fromPlaceId, $toPlaceId, $monsterId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE place_npcs SET place_id = ? WHERE place_id = ? AND id = ? AND monster_id IS NOT NULL");
            return $stmt->execute([$toPlaceId, $fromPlaceId, $monsterId]);
        } catch (PDOException $e) {
            error_log("Erreur lors du transfert du monstre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transférer une entité d'un lieu à un autre
     * 
     * @param string $entityType Type d'entité ('player', 'npc', 'monster')
     * @param int $fromPlaceId ID du lieu source
     * @param int $toPlaceId ID du lieu destination
     * @param int $entityId ID de l'entité
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat du transfert ['success' => bool, 'message' => string]
     */
    public static function transferEntity($entityType, $fromPlaceId, $toPlaceId, $entityId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $pdo->beginTransaction();
            
            $success = false;
            $message = '';
            
            switch ($entityType) {
                case 'player':
                    $success = self::transferPlayer($fromPlaceId, $toPlaceId, $entityId, $pdo);
                    $message = $success ? "Joueur transféré avec succès." : "Erreur lors du transfert du joueur.";
                    break;
                    
                case 'npc':
                    $success = self::transferNpc($fromPlaceId, $toPlaceId, $entityId, $pdo);
                    $message = $success ? "PNJ transféré avec succès." : "Erreur lors du transfert du PNJ.";
                    break;
                    
                case 'monster':
                    $success = self::transferMonster($fromPlaceId, $toPlaceId, $entityId, $pdo);
                    $message = $success ? "Monstre transféré avec succès." : "Erreur lors du transfert du monstre.";
                    break;
                    
                default:
                    $message = "Type d'entité non reconnu.";
                    break;
            }
            
            if ($success) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
            
            return ['success' => $success, 'message' => $message];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors du transfert d'entité: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors du transfert : " . $e->getMessage()];
        }
    }

    /**
     * Récupérer les lieux disponibles dans un monde qui ne sont pas encore associés à une campagne
     * 
     * @param int $worldId ID du monde
     * @param int $campaignId ID de la campagne (pour exclure les lieux déjà associés)
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des lieux disponibles avec informations géographiques
     */
    public static function getAvailablePlacesInWorld($worldId, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.id, p.title, p.notes, p.map_url, 
                       c.name as country_name, r.name as region_name
                FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions r ON p.region_id = r.id
                WHERE c.world_id = ? AND p.id NOT IN (
                    SELECT place_id FROM place_campaigns WHERE campaign_id = ?
                )
                ORDER BY c.name, r.name, p.title
            ");
            $stmt->execute([$worldId, $campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lieux disponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer tous les joueurs pour plusieurs lieux
     * 
     * @param array $placeIds IDs des lieux
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Tableau associatif [place_id => [joueurs]]
     */
    public static function getPlayersForPlaces($placeIds, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        if (empty($placeIds)) {
            return [];
        }
        
        try {
            $in = implode(',', array_fill(0, count($placeIds), '?'));
            $stmt = $pdo->prepare("
                SELECT pp.place_id, pp.player_id, u.username, ch.id AS character_id, ch.name AS character_name 
                FROM place_players pp 
                JOIN users u ON pp.player_id = u.id 
                LEFT JOIN characters ch ON pp.character_id = ch.id 
                WHERE pp.place_id IN ($in) 
                ORDER BY u.username ASC
            ");
            $stmt->execute($placeIds);
            
            $placePlayers = [];
            foreach ($stmt->fetchAll() as $row) {
                $placePlayers[$row['place_id']][] = $row;
            }
            
            return $placePlayers;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des joueurs pour les lieux: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer tous les PNJ (non-monstres) pour plusieurs lieux
     * 
     * @param array $placeIds IDs des lieux
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Tableau associatif [place_id => [pnjs]]
     */
    public static function getNpcsForPlaces($placeIds, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        if (empty($placeIds)) {
            return [];
        }
        
        try {
            $in = implode(',', array_fill(0, count($placeIds), '?'));
            $stmt = $pdo->prepare("
                SELECT pn.place_id, pn.id, pn.name, pn.description, pn.npc_character_id 
                FROM place_npcs pn 
                WHERE pn.place_id IN ($in) AND pn.monster_id IS NULL 
                ORDER BY pn.name ASC
            ");
            $stmt->execute($placeIds);
            
            $placeNpcs = [];
            foreach ($stmt->fetchAll() as $row) {
                $placeNpcs[$row['place_id']][] = $row;
            }
            
            return $placeNpcs;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des PNJ pour les lieux: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer tous les monstres pour plusieurs lieux
     * 
     * @param array $placeIds IDs des lieux
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Tableau associatif [place_id => [monstres]]
     */
    public static function getMonstersForPlaces($placeIds, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        if (empty($placeIds)) {
            return [];
        }
        
        try {
            $in = implode(',', array_fill(0, count($placeIds), '?'));
            $stmt = $pdo->prepare("
                SELECT pn.place_id, pn.id, pn.name, pn.description, pn.monster_id, pn.quantity, pn.current_hit_points, 
                       m.type, m.size, m.challenge_rating 
                FROM place_npcs pn 
                JOIN dnd_monsters m ON pn.monster_id = m.id 
                WHERE pn.place_id IN ($in) AND pn.monster_id IS NOT NULL 
                ORDER BY pn.name ASC
            ");
            $stmt->execute($placeIds);
            
            $placeMonsters = [];
            foreach ($stmt->fetchAll() as $row) {
                $placeMonsters[$row['place_id']][] = $row;
            }
            
            return $placeMonsters;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des monstres pour les lieux: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les entités (joueurs, PNJ, monstres) pour plusieurs lieux
     * 
     * @param array $placeIds IDs des lieux
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Tableau avec ['players' => [...], 'npcs' => [...], 'monsters' => [...]]
     */
    public static function getAllEntitiesForPlaces($placeIds, PDO $pdo = null)
    {
        return [
            'players' => self::getPlayersForPlaces($placeIds, $pdo),
            'npcs' => self::getNpcsForPlaces($placeIds, $pdo),
            'monsters' => self::getMonstersForPlaces($placeIds, $pdo)
        ];
    }

    /**
     * Récupérer tous les lieux
     * 
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste de tous les lieux
     */
    public static function getAllPlaces(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->query("SELECT id, title, region_id FROM places ORDER BY title");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les lieux: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir toutes les entités (PNJ et monstres) d'un utilisateur avec filtres
     */
    public static function getEntitiesByUser($userId, $filters = [])
    {
        $pdo = getPDO();
        
        try {
            $entities = [];
            
            // Récupérer les PNJ depuis place_npcs (seulement si pas de filtre type ou si type = PNJ)
            if (empty($filters['type']) || $filters['type'] === 'PNJ') {
                $npcs = self::getNpcsByUser($userId, $filters);
                foreach ($npcs as $npc) {
                    $npc['entity_type'] = 'PNJ';
                    $npc['view_id'] = $npc['npc_character_id'];
                    $entities[] = $npc;
                }
            }
            
            // Récupérer les monstres depuis monsters + place_monsters (seulement si pas de filtre type ou si type = Monstre)
            if (empty($filters['type']) || $filters['type'] === 'Monstre') {
                $monsters = self::getMonstersByUser($userId, $filters);
                foreach ($monsters as $monster) {
                    $monster['entity_type'] = 'Monstre';
                    $monster['view_id'] = $monster['id'];
                    $entities[] = $monster;
                }
            }
            
            // Trier par nom
            usort($entities, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            return $entities;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des entités: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les PNJ d'un utilisateur
     */
    public static function getNpcsByUser($userId, $filters = [])
    {
        $pdo = getPDO();
        
        try {
            $entities = [];
            
            // Partie 1: Récupérer les PNJ depuis place_npcs (associés à un lieu)
            $whereClause = "w.created_by = ? AND pn.monster_id IS NULL";
            $params = [$userId];
            
            if (!empty($filters['world'])) {
                $whereClause .= " AND w.id = ?";
                $params[] = $filters['world'];
            }
            
            if (!empty($filters['country'])) {
                $whereClause .= " AND c.id = ?";
                $params[] = $filters['country'];
            }
            
            if (!empty($filters['region'])) {
                $whereClause .= " AND reg.id = ?";
                $params[] = $filters['region'];
            }
            
            if (!empty($filters['place'])) {
                $whereClause .= " AND p.id = ?";
                $params[] = $filters['place'];
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    pn.id,
                    pn.name,
                    pn.description,
                    pn.profile_photo,
                    pn.is_visible,
                    pn.is_identified,
                    pn.quantity,
                    pn.current_hit_points,
                    pn.npc_character_id,
                    p.title as place_name,
                    p.id as place_id,
                    c.name as country_name,
                    c.id as country_id,
                    reg.name as region_name,
                    reg.id as region_id,
                    w.name as world_name,
                    w.id as world_id
                FROM place_npcs pn
                JOIN places p ON pn.place_id = p.id
                JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions reg ON p.region_id = reg.id
                JOIN worlds w ON c.world_id = w.id
                WHERE $whereClause
            ");
            $stmt->execute($params);
            $placeNpcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($placeNpcs as $npc) {
                $entities[] = $npc;
            }
            
            // Partie 2: Récupérer les NPCs directement depuis npcs (pas encore associés à un lieu)
            $whereClause2 = "n.created_by = ? AND n.is_active = 1";
            $params2 = [$userId];
            
            // Vérifier si le NPC est déjà dans place_npcs (pour éviter les doublons)
            $excludeIds = [];
            foreach ($placeNpcs as $npc) {
                if (!empty($npc['npc_character_id'])) {
                    $excludeIds[] = $npc['npc_character_id'];
                }
            }
            
            if (!empty($filters['world'])) {
                $whereClause2 .= " AND n.world_id = ?";
                $params2[] = $filters['world'];
            }
            
            // Pour les filtres country/region/place, on ne les applique que si le NPC est dans un lieu
            // Sinon, on ignore ces filtres pour les NPCs sans lieu
            if (empty($filters['country']) && empty($filters['region']) && empty($filters['place'])) {
                // Pas de filtres de lieu, on récupère tous les NPCs sans lieu
                if (!empty($excludeIds)) {
                    $in = implode(',', array_fill(0, count($excludeIds), '?'));
                    $whereClause2 .= " AND n.id NOT IN ($in)";
                    $params2 = array_merge($params2, $excludeIds);
                }
                
                $stmt2 = $pdo->prepare("
                    SELECT 
                        NULL as id,
                        n.name,
                        n.backstory as description,
                        n.profile_photo,
                        1 as is_visible,
                        1 as is_identified,
                        1 as quantity,
                        n.hit_points_current as current_hit_points,
                        n.id as npc_character_id,
                        NULL as place_name,
                        n.location_id as place_id,
                        NULL as country_name,
                        NULL as country_id,
                        NULL as region_name,
                        NULL as region_id,
                        w.name as world_name,
                        w.id as world_id
                    FROM npcs n
                    LEFT JOIN worlds w ON n.world_id = w.id
                    WHERE $whereClause2
                ");
                $stmt2->execute($params2);
                $directNpcs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($directNpcs as $npc) {
                    $entities[] = $npc;
                }
            }
            
            // Trier par nom
            usort($entities, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            return $entities;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les monstres d'un utilisateur
     */
    public static function getMonstersByUser($userId, $filters = [])
    {
        $pdo = getPDO();
        
        try {
            $whereClause = "m.created_by = ?";
            $params = [$userId];
            
            if (!empty($filters['world'])) {
                $whereClause .= " AND w.id = ?";
                $params[] = $filters['world'];
            }
            
            if (!empty($filters['country'])) {
                $whereClause .= " AND c.id = ?";
                $params[] = $filters['country'];
            }
            
            if (!empty($filters['region'])) {
                $whereClause .= " AND reg.id = ?";
                $params[] = $filters['region'];
            }
            
            if (!empty($filters['place'])) {
                $whereClause .= " AND p.id = ?";
                $params[] = $filters['place'];
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.name,
                    m.description,
                    m.is_visible,
                    m.is_identified,
                    m.quantity,
                    m.current_hit_points,
                    m.max_hit_points,
                    dt.name as type_name,
                    dt.type as monster_type,
                    dt.challenge_rating,
                    p.title as place_name,
                    p.id as place_id,
                    c.name as country_name,
                    c.id as country_id,
                    reg.name as region_name,
                    reg.id as region_id,
                    w.name as world_name,
                    w.id as world_id
                FROM monsters m
                JOIN place_monsters pm ON m.id = pm.monster_id
                JOIN dnd_monsters dt ON m.monster_type_id = dt.id
                JOIN places p ON pm.place_id = p.id
                JOIN countries c ON p.country_id = c.id
                LEFT JOIN regions reg ON p.region_id = reg.id
                JOIN worlds w ON c.world_id = w.id
                WHERE $whereClause
                ORDER BY w.name, c.name, p.title, m.name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des monstres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer une entité (PNJ ou monstre)
     */
    public static function deleteEntity($entityId, $userId, $entityType = null)
    {
        $pdo = getPDO();
        
        try {
            if ($entityType === 'PNJ') {
                // Essayer d'abord comme un PNJ dans place_npcs
                $stmt = $pdo->prepare("
                    SELECT pn.id FROM place_npcs pn
                    JOIN places p ON pn.place_id = p.id
                    JOIN countries c ON p.country_id = c.id
                    JOIN worlds w ON c.world_id = w.id
                    WHERE pn.id = ? AND pn.monster_id IS NULL AND w.created_by = ?
                ");
                $stmt->execute([$entityId, $userId]);
                $entity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($entity) {
                    // C'est un PNJ dans place_npcs
                    // Récupérer le npc_character_id pour supprimer les items
                    $stmt = $pdo->prepare("SELECT npc_character_id FROM place_npcs WHERE id = ?");
                    $stmt->execute([$entityId]);
                    $npcData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $npcCharacterId = $npcData['npc_character_id'] ?? null;
                    
                    // Supprimer les items du PNJ (table items)
                    if ($npcCharacterId) {
                        $stmt = $pdo->prepare("DELETE FROM items WHERE owner_type = 'npc' AND owner_id = ?");
                        $stmt->execute([$npcCharacterId]);
                    }
                    
                    // Supprimer l'équipement du PNJ (table npc_equipment - ancien système)
                    $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE npc_id = ?");
                    $stmt->execute([$entityId]);
                    
                    // Supprimer le PNJ
                    $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE id = ?");
                    $stmt->execute([$entityId]);
                } else {
                    // Essayer comme un PNJ direct (sans lieu) depuis la table npcs
                    $stmt = $pdo->prepare("
                        SELECT n.id FROM npcs n
                        JOIN worlds w ON n.world_id = w.id
                        WHERE n.id = ? AND n.is_active = 1 AND w.created_by = ?
                    ");
                    $stmt->execute([$entityId, $userId]);
                    $entity = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$entity) {
                        return false;
                    }
                    
                    // Supprimer les items du PNJ (table items)
                    $stmt = $pdo->prepare("DELETE FROM items WHERE owner_type = 'npc' AND owner_id = ?");
                    $stmt->execute([$entityId]);
                    
                    // Supprimer l'équipement du PNJ (table npc_equipment - ancien système)
                    $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE npc_id = ?");
                    $stmt->execute([$entityId]);
                    
                    // Supprimer le PNJ directement depuis npcs
                    $stmt = $pdo->prepare("DELETE FROM npcs WHERE id = ?");
                    $stmt->execute([$entityId]);
                }
                
                return true;
                
            } elseif ($entityType === 'Monstre') {
                // Supprimer un monstre depuis monsters
                $stmt = $pdo->prepare("
                    SELECT m.id FROM monsters m
                    WHERE m.id = ? AND m.created_by = ?
                ");
                $stmt->execute([$entityId, $userId]);
                $entity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entity) {
                    return false;
                }
                
                // Supprimer les positions du monstre
                $stmt = $pdo->prepare("DELETE FROM place_monsters WHERE monster_id = ?");
                $stmt->execute([$entityId]);
                
                // Supprimer le monstre
                $stmt = $pdo->prepare("DELETE FROM monsters WHERE id = ?");
                $stmt->execute([$entityId]);
                
                return true;
                
            } else {
                // Essayer de déterminer le type d'entité
                // D'abord vérifier si c'est un PNJ
                $stmt = $pdo->prepare("
                    SELECT pn.id FROM place_npcs pn
                    JOIN places p ON pn.place_id = p.id
                    JOIN countries c ON p.country_id = c.id
                    JOIN worlds w ON c.world_id = w.id
                    WHERE pn.id = ? AND pn.monster_id IS NULL AND w.created_by = ?
                ");
                $stmt->execute([$entityId, $userId]);
                $entity = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($entity) {
                    // C'est un PNJ dans place_npcs
                    // Récupérer le npc_character_id pour supprimer les items
                    $stmt = $pdo->prepare("SELECT npc_character_id FROM place_npcs WHERE id = ?");
                    $stmt->execute([$entityId]);
                    $npcData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $npcCharacterId = $npcData['npc_character_id'] ?? null;
                    
                    // Supprimer les items du PNJ (table items)
                    if ($npcCharacterId) {
                        $stmt = $pdo->prepare("DELETE FROM items WHERE owner_type = 'npc' AND owner_id = ?");
                        $stmt->execute([$npcCharacterId]);
                    }
                    
                    // Supprimer l'équipement du PNJ (table npc_equipment - ancien système)
                    $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE npc_id = ?");
                    $stmt->execute([$entityId]);
                    
                    $stmt = $pdo->prepare("DELETE FROM place_npcs WHERE id = ?");
                    $stmt->execute([$entityId]);
                } else {
                    // Essayer comme un monstre
                    $stmt = $pdo->prepare("
                        SELECT m.id FROM monsters m
                        WHERE m.id = ? AND m.created_by = ?
                    ");
                    $stmt->execute([$entityId, $userId]);
                    $entity = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($entity) {
                        $stmt = $pdo->prepare("DELETE FROM place_monsters WHERE monster_id = ?");
                        $stmt->execute([$entityId]);
                        
                        $stmt = $pdo->prepare("DELETE FROM monsters WHERE id = ?");
                        $stmt->execute([$entityId]);
                    } else {
                        return false;
                    }
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'entité: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir tous les mondes
     */
    public static function getAllWorlds()
    {
        $pdo = getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des mondes: " . $e->getMessage());
            return [];
        }
    }
}
