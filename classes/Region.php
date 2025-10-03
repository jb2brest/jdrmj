<?php

/**
 * Classe Region - Gestion des régions de campagne
 * 
 * Cette classe encapsule toutes les fonctionnalités liées à la gestion
 * des régions dans l'application JDR MJ. Une région appartient à un pays
 * et peut contenir plusieurs lieux.
 */
class Region
{
    // Propriétés privées
    private $id;
    private $country_id;
    private $name;
    private $description;
    private $map_url;
    private $coat_of_arms_url;
    private $created_at;
    private $updated_at;

    /**
     * Constructeur de la classe Region
     * 
     * @param array $data Données optionnelles pour initialiser l'objet
     */
    public function __construct(array $data = [])
    {
        // Initialiser les propriétés avec les données fournies
        $this->id = $data['id'] ?? null;
        $this->country_id = $data['country_id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->map_url = $data['map_url'] ?? '';
        $this->coat_of_arms_url = $data['coat_of_arms_url'] ?? '';
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

    public function getCountryId()
    {
        return $this->country_id;
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

    public function getCoatOfArmsUrl()
    {
        return $this->coat_of_arms_url;
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

    public function setCountryId(int $country_id)
    {
        $this->country_id = $country_id;
        return $this;
    }

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

    public function setCoatOfArmsUrl(string $coat_of_arms_url)
    {
        $this->coat_of_arms_url = trim($coat_of_arms_url);
        return $this;
    }

    // ========================================
    // MÉTHODES DE VALIDATION
    // ========================================

    /**
     * Valide les données de la région
     * 
     * @return array Tableau des erreurs (vide si aucune erreur)
     */
    public function validate()
    {
        $errors = [];

        if (empty($this->country_id)) {
            $errors[] = "L'ID du pays est requis.";
        } elseif (!is_numeric($this->country_id) || $this->country_id <= 0) {
            $errors[] = "L'ID du pays doit être un nombre positif.";
        }

        if (empty($this->name)) {
            $errors[] = "Le nom de la région est requis.";
        } elseif (strlen($this->name) > 100) {
            $errors[] = "Le nom de la région ne peut pas dépasser 100 caractères.";
        }

        if (strlen($this->description) > 65535) {
            $errors[] = "La description est trop longue.";
        }

        if (!empty($this->map_url) && strlen($this->map_url) > 255) {
            $errors[] = "L'URL de la carte ne peut pas dépasser 255 caractères.";
        }

        if (!empty($this->coat_of_arms_url) && strlen($this->coat_of_arms_url) > 255) {
            $errors[] = "L'URL du blason ne peut pas dépasser 255 caractères.";
        }

        // Vérifier que le pays existe
        if (!empty($this->country_id)) {
            try {
                $pdo = $this->getPdo();
                $sql = "SELECT COUNT(*) FROM countries WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$this->country_id]);
                if ($stmt->fetchColumn() == 0) {
                    $errors[] = "Le pays spécifié n'existe pas.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la vérification du pays: " . $e->getMessage();
            }
        }

        return $errors;
    }

    // ========================================
    // MÉTHODES DE PERSISTANCE
    // ========================================

    /**
     * Sauvegarde la région en base de données
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
                // Création d'une nouvelle région
                $sql = "INSERT INTO regions (country_id, name, description, map_url, coat_of_arms_url) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$this->country_id, $this->name, $this->description, $this->map_url, $this->coat_of_arms_url]);
                
                $this->id = $pdo->lastInsertId();
                return true;
            } else {
                // Mise à jour d'une région existante
                $sql = "UPDATE regions SET country_id = ?, name = ?, description = ?, map_url = ?, coat_of_arms_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$this->country_id, $this->name, $this->description, $this->map_url, $this->coat_of_arms_url, $this->id]);
                
                return $result;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Vérifier si c'est une erreur de contrainte unique
                if (strpos($e->getMessage(), 'unique_region_per_country') !== false) {
                    throw new Exception("Une région avec ce nom existe déjà dans ce pays.");
                }
                throw new Exception("Contrainte de base de données violée: " . $e->getMessage());
            }
            throw new Exception("Erreur lors de la sauvegarde: " . $e->getMessage());
        }
    }

    /**
     * Supprime la région de la base de données
     * 
     * @return bool True si la suppression a réussi, false sinon
     * @throws Exception En cas d'erreur
     */
    public function delete()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de supprimer une région qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            
            // Vérifier s'il y a des lieux dans cette région
            $sql = "SELECT COUNT(*) FROM places WHERE region_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $place_count = $stmt->fetchColumn();

            if ($place_count > 0) {
                throw new Exception("Impossible de supprimer cette région car elle contient $place_count lieu(x). Supprimez d'abord les lieux.");
            }

            // Supprimer la région
            $sql = "DELETE FROM regions WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$this->id]);

            if ($result && $stmt->rowCount() > 0) {
                // Supprimer les images associées si elles existent
                if (!empty($this->map_url) && file_exists($this->map_url)) {
                    unlink($this->map_url);
                }
                if (!empty($this->coat_of_arms_url) && file_exists($this->coat_of_arms_url)) {
                    unlink($this->coat_of_arms_url);
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
     * Récupère une région par son ID
     * 
     * @param int $id ID de la région
     * @return Region|null Instance de Region ou null si non trouvé
     */
    public static function findById(int $id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT * FROM regions WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($data);
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de la région: " . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les régions d'un pays
     * 
     * @param int $country_id ID du pays
     * @return array Tableau d'instances de Region
     */
    public static function findByCountry(int $country_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT r.*, 
                    (SELECT COUNT(*) FROM places WHERE region_id = r.id) as place_count
                    FROM regions r 
                    WHERE r.country_id = ? 
                    ORDER BY r.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$country_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $regions = [];
            foreach ($results as $data) {
                $regions[] = new self($data);
            }
            return $regions;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des régions: " . $e->getMessage());
        }
    }

    /**
     * Récupère toutes les régions d'un utilisateur (via les pays et mondes)
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Region
     */
    public static function findByUser(int $user_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT r.*, 
                    c.name as country_name,
                    w.name as world_name,
                    (SELECT COUNT(*) FROM places WHERE region_id = r.id) as place_count
                    FROM regions r 
                    JOIN countries c ON r.country_id = c.id
                    JOIN worlds w ON c.world_id = w.id
                    WHERE w.created_by = ? 
                    ORDER BY w.name, c.name, r.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $regions = [];
            foreach ($results as $data) {
                $regions[] = new self($data);
            }
            return $regions;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des régions: " . $e->getMessage());
        }
    }

    /**
     * Vérifie si un nom de région existe déjà dans un pays
     * 
     * @param string $name Nom de la région
     * @param int $country_id ID du pays
     * @param int $exclude_id ID de la région à exclure (pour les mises à jour)
     * @return bool True si le nom existe déjà
     */
    public static function nameExistsInCountry(string $name, int $country_id, int $exclude_id = null)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT COUNT(*) FROM regions WHERE name = ? AND country_id = ?";
            $params = [$name, $country_id];

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
     * Récupère le pays auquel appartient cette région
     * 
     * @return Pays|null Instance de Pays ou null si non trouvé
     */
    public function getPays()
    {
        if ($this->country_id === null) {
            return null;
        }

        try {
            return Pays::findById($this->country_id);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère le monde auquel appartient cette région (via le pays)
     * 
     * @return Monde|null Instance de Monde ou null si non trouvé
     */
    public function getMonde()
    {
        if ($this->country_id === null) {
            return null;
        }

        try {
            $pays = $this->getPays();
            if ($pays) {
                return $pays->getMonde();
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du monde: " . $e->getMessage());
        }
    }

    /**
     * Récupère le nombre de lieux dans cette région
     * 
     * @return int Nombre de lieux
     */
    public function getPlaceCount()
    {
        if ($this->id === null) {
            return 0;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT COUNT(*) FROM places WHERE region_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du comptage des lieux: " . $e->getMessage());
        }
    }

    /**
     * Récupère les lieux de cette région
     * 
     * @return array Tableau des lieux
     */
    public function getPlaces()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT * FROM places WHERE region_id = ? ORDER BY title";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des lieux: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les PNJs de la région (via la hiérarchie région → lieux)
     * 
     * @return array Liste des PNJs
     * @throws Exception En cas d'erreur
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
                    'PNJ' AS type
                FROM place_npcs pn
                JOIN places pl ON pn.place_id = pl.id
                LEFT JOIN characters c ON pn.npc_character_id = c.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN races r ON c.race_id = r.id
                WHERE pl.region_id = ? AND pn.monster_id IS NULL
                ORDER BY pn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des PNJs: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les monstres de la région (via la hiérarchie région → lieux)
     * 
     * @return array Liste des monstres
     * @throws Exception En cas d'erreur
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
                    dm.name AS monster_name,
                    dm.type,
                    dm.size,
                    dm.challenge_rating,
                    dm.hit_points,
                    dm.armor_class,
                    pl.title AS place_name,
                    'Monstre' AS type
                FROM place_npcs pn
                JOIN places pl ON pn.place_id = pl.id
                JOIN dnd_monsters dm ON pn.monster_id = dm.id
                WHERE pl.region_id = ? AND pn.monster_id IS NOT NULL
                ORDER BY pn.name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des monstres: " . $e->getMessage());
        }
    }

    /**
     * Récupère le nom du pays auquel appartient cette région
     * 
     * @return string|null Nom du pays ou null
     */
    public function getCountryName()
    {
        if ($this->country_id === null) {
            return null;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT name FROM countries WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->country_id]);
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du nom du pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère le nom du monde auquel appartient cette région
     * 
     * @return string|null Nom du monde ou null
     */
    public function getWorldName()
    {
        if ($this->country_id === null) {
            return null;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT w.name FROM worlds w 
                    JOIN countries c ON w.id = c.world_id 
                    WHERE c.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->country_id]);
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du nom du monde: " . $e->getMessage());
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
            'country_id' => $this->country_id,
            'name' => $this->name,
            'description' => $this->description,
            'map_url' => $this->map_url,
            'coat_of_arms_url' => $this->coat_of_arms_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Représentation textuelle de l'objet
     * 
     * @return string Nom de la région
     */
    public function __toString()
    {
        return $this->name;
    }
}

