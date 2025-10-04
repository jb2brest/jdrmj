<?php

/**
 * Classe Monde - Gestion des mondes de campagne
 * 
 * Cette classe encapsule toutes les fonctionnalités liées à la gestion
 * des mondes dans l'application JDR MJ.
 */
class Monde
{
    // Propriétés privées
    private $id;
    private $name;
    private $description;
    private $map_url;
    private $created_by;
    private $created_at;
    private $updated_at;

    /**
     * Constructeur de la classe Monde
     * 
     * @param array $data Données optionnelles pour initialiser l'objet
     */
    public function __construct(array $data = [])
    {
        // Initialiser les propriétés avec les données fournies
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->map_url = $data['map_url'] ?? '';
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    // ========================================
    // MÉTHODES PRIVÉES
    // ========================================

    /**
     * Obtient l'instance PDO depuis l'Univers
     * 
     * @return PDO Instance PDO
     */
    private function getPdo()
    {
        return Univers::getInstance()->getPdo();
    }

    // ========================================
    // GETTERS
    // ========================================

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getMapUrl()
    {
        return $this->map_url;
    }

    public function getCreatedBy()
    {
        return $this->created_by;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    // ========================================
    // SETTERS
    // ========================================

    public function setName(string $name)
    {
        $this->name = trim($name);
        return $this;
    }

    public function setDescription(string $description)
    {
        $this->description = trim($description);
        return $this;
    }

    public function setMapUrl(string $map_url)
    {
        $this->map_url = trim($map_url);
        return $this;
    }

    public function setCreatedBy(int $created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    // ========================================
    // MÉTHODES DE VALIDATION
    // ========================================

    /**
     * Valide les données du monde
     * 
     * @return array Tableau des erreurs (vide si aucune erreur)
     */
    public function validate()
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = "Le nom du monde est requis.";
        } elseif (strlen($this->name) > 100) {
            $errors[] = "Le nom du monde ne peut pas dépasser 100 caractères.";
        }

        if (strlen($this->description) > 65535) {
            $errors[] = "La description est trop longue.";
        }

        if (!empty($this->map_url) && strlen($this->map_url) > 255) {
            $errors[] = "L'URL de la carte ne peut pas dépasser 255 caractères.";
        }

        if (empty($this->created_by)) {
            $errors[] = "L'ID du créateur est requis.";
        }

        return $errors;
    }

    // ========================================
    // MÉTHODES DE PERSISTANCE
    // ========================================

    /**
     * Sauvegarde le monde en base de données
     * 
     * @return bool True si la sauvegarde a réussi, false sinon
     * @throws Exception En cas d'erreur de validation ou de base de données
     */
    public function save()
    {
        // Valider les données
        $errors = $this->validate();
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        try {
            $pdo = $this->getPdo();
            
            if ($this->id === null) {
                // Création d'un nouveau monde
                $sql = "INSERT INTO worlds (name, description, map_url, created_by) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$this->name, $this->description, $this->map_url, $this->created_by]);
                
                $this->id = $pdo->lastInsertId();
                return true;
            } else {
                // Mise à jour d'un monde existant
                $sql = "UPDATE worlds SET name = ?, description = ?, map_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$this->name, $this->description, $this->map_url, $this->id]);
                
                return $result;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Un monde avec ce nom existe déjà.");
            }
            throw new Exception("Erreur lors de la sauvegarde: " . $e->getMessage());
        }
    }

    /**
     * Supprime le monde de la base de données
     * 
     * @return bool True si la suppression a réussi, false sinon
     * @throws Exception En cas d'erreur
     */
    public function delete()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de supprimer un monde qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            
            // Vérifier s'il y a des pays dans ce monde
            $sql = "SELECT COUNT(*) FROM countries WHERE world_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $country_count = $stmt->fetchColumn();

            if ($country_count > 0) {
                throw new Exception("Impossible de supprimer ce monde car il contient $country_count pays. Supprimez d'abord les pays.");
            }

            // Supprimer le monde
            $sql = "DELETE FROM worlds WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$this->id]);

            if ($result && $stmt->rowCount() > 0) {
                // Supprimer l'image associée si elle existe
                if (!empty($this->map_url) && file_exists($this->map_url)) {
                    unlink($this->map_url);
                }
                return true;
            }

            return false;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression: " . $e->getMessage());
        }
    }

    // ========================================
    // MÉTHODES STATIQUES
    // ========================================

    /**
     * Récupère un monde par son ID
     * 
     * @param int $id ID du monde
     * @return Monde|null Instance de Monde ou null si non trouvé
     */
    public static function findById(int $id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT * FROM worlds WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($data);
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du monde: " . $e->getMessage());
        }
    }

    /**
     * Récupère un monde par son ID via l'Univers
     * 
     * @param int $id ID du monde
     * @return Monde|null Instance de Monde ou null si non trouvé
     */
    public static function findByIdInUnivers(int $id)
    {
        return self::findById($id);
    }

    /**
     * Récupère tous les mondes d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Monde
     */
    public static function findByUser(int $user_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT w.*, 
                    (SELECT COUNT(*) FROM countries WHERE world_id = w.id) as country_count
                    FROM worlds w 
                    WHERE w.created_by = ? 
                    ORDER BY w.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $worlds = [];
            foreach ($results as $data) {
                $world = new self($data);
                $worlds[] = $world;
            }
            return $worlds;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des mondes: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les mondes d'un utilisateur via l'Univers
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Monde
     */
    public static function findByUserInUnivers(int $user_id)
    {
        return self::findByUser($user_id);
    }

    /**
     * Récupère une liste simple des mondes d'un utilisateur (id et name seulement)
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau associatif avec id et name
     */
    public static function getSimpleListByUser(int $user_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT id, name FROM worlds WHERE created_by = ? ORDER BY name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la liste des mondes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un nom de monde existe déjà pour un utilisateur
     * 
     * @param string $name Nom du monde
     * @param int $user_id ID de l'utilisateur
     * @param int $exclude_id ID du monde à exclure (pour les mises à jour)
     * @return bool True si le nom existe déjà
     */
    public static function nameExists(string $name, int $user_id, int $exclude_id = null)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT COUNT(*) FROM worlds WHERE name = ? AND created_by = ?";
            $params = [$name, $user_id];

            if ($exclude_id !== null) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification du nom: " . $e->getMessage());
        }
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Récupère le nombre de pays dans ce monde
     * 
     * @return int Nombre de pays
     */
    public function getCountryCount()
    {
        if ($this->id === null) {
            return 0;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT COUNT(*) FROM countries WHERE world_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du comptage des pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère les pays de ce monde
     * 
     * @return array Tableau des objets Pays
     */
    public function getCountries()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT * FROM countries WHERE world_id = ? ORDER BY name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $countriesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir les tableaux en objets Pays
            $countries = [];
            foreach ($countriesData as $countryData) {
                $countries[] = new Pays($countryData);
            }
            
            return $countries;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pays: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les PNJs du monde (via la hiérarchie pays → régions → lieux)
     * 
     * @return array Tableau des PNJs avec leurs informations
     */
    public function getNpcs()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("
                SELECT 
                    pn.id,
                    pn.name,
                    pn.description,
                    pn.profile_photo,
                    pn.is_visible,
                    pn.is_identified,
                    c.name AS character_name,
                    c.profile_photo AS character_profile_photo,
                    cl.name AS class_name,
                    r.name AS race_name,
                    pl.title AS place_name,
                    co.name AS country_name,
                    reg.name AS region_name,
                    'PNJ' AS type
                FROM place_npcs pn
                JOIN places pl ON pn.place_id = pl.id
                LEFT JOIN countries co ON pl.country_id = co.id
                LEFT JOIN regions reg ON pl.region_id = reg.id
                LEFT JOIN characters c ON pn.npc_character_id = c.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN races r ON c.race_id = r.id
                WHERE co.world_id = ? AND pn.monster_id IS NULL
                ORDER BY pn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des PNJs: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les monstres du monde (via la hiérarchie pays → régions → lieux)
     * 
     * @return array Tableau des monstres avec leurs informations
     */
    public function getMonsters()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
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
                    pn.monster_id,
                    dm.name AS monster_name,
                    dm.type,
                    dm.size,
                    dm.challenge_rating,
                    dm.hit_points,
                    dm.armor_class,
                    pl.title AS place_name,
                    co.name AS country_name,
                    reg.name AS region_name,
                    'Monstre' AS type
                FROM place_npcs pn
                JOIN places pl ON pn.place_id = pl.id
                LEFT JOIN countries co ON pl.country_id = co.id
                LEFT JOIN regions reg ON pl.region_id = reg.id
                JOIN dnd_monsters dm ON pn.monster_id = dm.id
                WHERE co.world_id = ? AND pn.monster_id IS NOT NULL
                ORDER BY pn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des monstres: " . $e->getMessage());
        }
    }

    /**
     * Convertit l'objet en tableau associatif
     * 
     * @return array Représentation en tableau de l'objet
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'map_url' => $this->map_url,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Représentation textuelle de l'objet
     * 
     * @return string Nom du monde
     */
    public function __toString()
    {
        return $this->name;
    }

    // ========================================
    // MÉTHODES DE LOCALISATION
    // ========================================

    /**
     * Localise un personnage/joueur dans le monde
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int|null $campaign_id ID de la campagne (optionnel)
     * @return array|null Informations sur le lieu où se trouve le personnage
     */
    public static function localizeCharacter(int $user_id, ?int $campaign_id = null)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            
            if ($campaign_id) {
                // Si un campaign_id est spécifié, chercher dans cette campagne
                $place = Database::fetch("
                    SELECT p.*, c.title as campaign_title, c.dm_id, c.id as campaign_id
                    FROM places p 
                    INNER JOIN place_campaigns pc ON p.id = pc.place_id
                    JOIN campaigns c ON pc.campaign_id = c.id 
                    JOIN place_players pp ON p.id = pp.place_id 
                    WHERE pp.player_id = ? AND c.id = ?
                    LIMIT 1
                ", [$user_id, $campaign_id]);
                
                if (!$place) {
                    // Vérifier si le joueur est membre de la campagne
                    $membership = Database::fetch(
                        "SELECT cm.role FROM campaign_members cm WHERE cm.campaign_id = ? AND cm.user_id = ?",
                        [$campaign_id, $user_id]
                    );
                    
                    if ($membership) {
                        // Le joueur est membre mais pas assigné à un lieu
                        return [
                            'status' => 'member_no_place',
                            'campaign_id' => $campaign_id,
                            'message' => 'Vous êtes membre de cette campagne mais n\'êtes pas encore assigné à un lieu spécifique.'
                        ];
                    } else {
                        // Le joueur n'est pas membre de cette campagne
                        return [
                            'status' => 'not_member',
                            'campaign_id' => $campaign_id,
                            'message' => 'Vous n\'êtes pas membre de cette campagne.'
                        ];
                    }
                }
                
                return [
                    'status' => 'found',
                    'place' => $place,
                    'message' => 'Personnage localisé avec succès'
                ];
                
            } else {
                // Comportement original : chercher n'importe quel lieu où se trouve le joueur
                $place = Database::fetch("
                    SELECT p.*, c.title as campaign_title, c.dm_id, c.id as campaign_id
                    FROM places p 
                    INNER JOIN place_campaigns pc ON p.id = pc.place_id
                    JOIN campaigns c ON pc.campaign_id = c.id 
                    JOIN place_players pp ON p.id = pp.place_id 
                    WHERE pp.player_id = ?
                    LIMIT 1
                ", [$user_id]);
                
                if (!$place) {
                    // Vérifier si le joueur est membre d'au moins une campagne
                    $membership = Database::fetch(
                        "SELECT cm.role FROM campaign_members cm WHERE cm.user_id = ?",
                        [$user_id]
                    );
                    
                    if ($membership) {
                        // Le joueur est membre mais pas assigné à un lieu
                        return [
                            'status' => 'member_no_place_any',
                            'message' => 'Vous êtes membre d\'une campagne mais n\'êtes pas encore assigné à un lieu spécifique.'
                        ];
                    } else {
                        // Le joueur n'est membre d'aucune campagne
                        return [
                            'status' => 'no_campaigns',
                            'message' => 'Vous n\'êtes membre d\'aucune campagne.'
                        ];
                    }
                }
                
                return [
                    'status' => 'found',
                    'place' => $place,
                    'message' => 'Personnage localisé avec succès'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erreur lors de la localisation du personnage: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Erreur lors de la localisation du personnage.'
            ];
        }
    }
}
