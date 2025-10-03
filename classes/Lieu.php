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
                SELECT sn.id, sn.name, sn.description, sn.npc_character_id, sn.profile_photo, sn.is_visible, sn.is_identified, c.profile_photo AS character_profile_photo, c.hit_points_current, c.hit_points_max 
                FROM place_npcs sn 
                LEFT JOIN characters c ON sn.npc_character_id = c.id 
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
    public function addMonster($monsterId, $quantity = 1)
    {
        try {
            // Récupérer les informations du monstre
            $stmt = $this->pdo->prepare("SELECT name, hit_points FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$monsterId]);
            $monster = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$monster) {
                return ['success' => false, 'message' => 'Monstre introuvable.'];
            }
            
            $monsterName = $monster['name'];
            if ($quantity > 1) {
                $monsterName .= " (x{$quantity})";
            }
            
            // Ajouter le monstre au lieu
            $stmt = $this->pdo->prepare("INSERT INTO place_npcs (place_id, name, monster_id, quantity, current_hit_points) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$this->id, $monsterName, $monsterId, $quantity, $monster['hit_points']]);
            
            return ['success' => true, 'message' => 'Monstre ajouté au lieu.'];
            
        } catch (PDOException $e) {
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
            $stmt = $this->pdo->prepare("UPDATE place_npcs SET is_identified = NOT is_identified WHERE place_id = ? AND id = ? AND monster_id IS NULL");
            $stmt->execute([$this->id, $npcId]);
            
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
}
