<?php

/**
 * Classe Pays - Gestion des pays de campagne
 * 
 * Cette classe encapsule toutes les fonctionnalités liées à la gestion
 * des pays dans l'application JDR MJ. Un pays appartient à un monde
 * et peut contenir plusieurs régions.
 */
class Pays
{
    // Propriétés privées
    private $id;
    private $world_id;
    private $name;
    private $description;
    private $map_url;
    private $coat_of_arms_url;
    private $created_at;
    private $updated_at;

    /**
     * Constructeur de la classe Pays
     * 
     * @param array $data Données optionnelles pour initialiser l'objet
     */
    public function __construct(array $data = [])
    {
        // Initialiser les propriétés avec les données fournies
        $this->id = $data['id'] ?? null;
        $this->world_id = $data['world_id'] ?? null;
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

    public function getWorldId()
    {
        return $this->world_id;
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

    public function setWorldId(int $world_id)
    {
        $this->world_id = $world_id;
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
     * Valide les données du pays
     * 
     * @return array Tableau des erreurs (vide si aucune erreur)
     */
    public function validate()
    {
        $errors = [];

        if (empty($this->world_id)) {
            $errors[] = "L'ID du monde est requis.";
        } elseif (!is_numeric($this->world_id) || $this->world_id <= 0) {
            $errors[] = "L'ID du monde doit être un nombre positif.";
        }

        if (empty($this->name)) {
            $errors[] = "Le nom du pays est requis.";
        } elseif (strlen($this->name) > 100) {
            $errors[] = "Le nom du pays ne peut pas dépasser 100 caractères.";
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

        // Vérifier que le monde existe
        if (!empty($this->world_id)) {
            try {
                $pdo = $this->getPdo();
                $sql = "SELECT COUNT(*) FROM worlds WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$this->world_id]);
                if ($stmt->fetchColumn() == 0) {
                    $errors[] = "Le monde spécifié n'existe pas.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la vérification du monde: " . $e->getMessage();
            }
        }

        return $errors;
    }

    // ========================================
    // MÉTHODES DE PERSISTANCE
    // ========================================

    /**
     * Sauvegarde le pays en base de données
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
                // Création d'un nouveau pays
                $sql = "INSERT INTO countries (world_id, name, description, map_url, coat_of_arms_url) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$this->world_id, $this->name, $this->description, $this->map_url, $this->coat_of_arms_url]);
                
                $this->id = $pdo->lastInsertId();
                return true;
            } else {
                // Mise à jour d'un pays existant
                $sql = "UPDATE countries SET world_id = ?, name = ?, description = ?, map_url = ?, coat_of_arms_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$this->world_id, $this->name, $this->description, $this->map_url, $this->coat_of_arms_url, $this->id]);
                
                return $result;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Vérifier si c'est une erreur de contrainte unique
                if (strpos($e->getMessage(), 'unique_country_per_world') !== false) {
                    throw new Exception("Un pays avec ce nom existe déjà dans ce monde.");
                }
                throw new Exception("Contrainte de base de données violée: " . $e->getMessage());
            }
            throw new Exception("Erreur lors de la sauvegarde: " . $e->getMessage());
        }
    }

    /**
     * Supprime le pays de la base de données
     * 
     * @return bool True si la suppression a réussi, false sinon
     * @throws Exception En cas d'erreur
     */
    public function delete()
    {
        if ($this->id === null) {
            throw new Exception("Impossible de supprimer un pays qui n'existe pas en base.");
        }

        try {
            $pdo = $this->getPdo();
            
            // Vérifier s'il y a des régions dans ce pays
            $sql = "SELECT COUNT(*) FROM regions WHERE country_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $region_count = $stmt->fetchColumn();

            if ($region_count > 0) {
                throw new Exception("Impossible de supprimer ce pays car il contient $region_count région(s). Supprimez d'abord les régions.");
            }

            // Supprimer le pays
            $sql = "DELETE FROM countries WHERE id = ?";
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
     * Récupère un pays par son ID
     * 
     * @param int $id ID du pays
     * @return Pays|null Instance de Pays ou null si non trouvé
     */
    public static function findById(int $id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT * FROM countries WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($data);
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère un pays par son ID via l'Univers
     * 
     * @param int $id ID du pays
     * @return Pays|null Instance de Pays ou null si non trouvé
     */
    public static function findByIdInUnivers(int $id)
    {
        return self::findById($id);
    }

    /**
     * Récupère tous les pays d'un monde
     * 
     * @param int $world_id ID du monde
     * @return array Tableau d'instances de Pays
     */
    public static function findByWorld(int $world_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM regions WHERE country_id = c.id) as region_count
                    FROM countries c 
                    WHERE c.world_id = ? 
                    ORDER BY c.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$world_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pays = [];
            foreach ($results as $data) {
                $pays[] = new self($data);
            }
            return $pays;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les pays d'un utilisateur (via les mondes)
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Pays
     */
    public static function findByUser(int $user_id)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT c.*, 
                    w.name as world_name,
                    (SELECT COUNT(*) FROM regions WHERE country_id = c.id) as region_count
                    FROM countries c 
                    JOIN worlds w ON c.world_id = w.id
                    WHERE w.created_by = ? 
                    ORDER BY w.name, c.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pays = [];
            foreach ($results as $data) {
                $pays[] = new self($data);
            }
            return $pays;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les pays d'un utilisateur via l'Univers
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Pays
     */
    public static function findByUserInUnivers(int $user_id)
    {
        return self::findByUser($user_id);
    }

    /**
     * Vérifie si un nom de pays existe déjà dans un monde
     * 
     * @param string $name Nom du pays
     * @param int $world_id ID du monde
     * @param int $exclude_id ID du pays à exclure (pour les mises à jour)
     * @return bool True si le nom existe déjà
     */
    public static function nameExistsInWorld(string $name, int $world_id, int $exclude_id = null)
    {
        try {
            $pdo = Univers::getInstance()->getPdo();
            $sql = "SELECT COUNT(*) FROM countries WHERE name = ? AND world_id = ?";
            $params = [$name, $world_id];

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
     * Récupère le monde auquel appartient ce pays
     * 
     * @return Monde|null Instance de Monde ou null si non trouvé
     */
    public function getMonde()
    {
        if ($this->world_id === null) {
            return null;
        }

        try {
            return Monde::findById($this->world_id);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du monde: " . $e->getMessage());
        }
    }

    /**
     * Récupère le nombre de régions dans ce pays
     * 
     * @return int Nombre de régions
     */
    public function getRegionCount()
    {
        if ($this->id === null) {
            return 0;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT COUNT(*) FROM regions WHERE country_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du comptage des régions: " . $e->getMessage());
        }
    }

    /**
     * Récupère les régions de ce pays
     * 
     * @return array Tableau des régions
     */
    public function getRegions()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT * FROM regions WHERE country_id = ? ORDER BY name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $regionsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir les tableaux en objets Region
            $regions = [];
            foreach ($regionsData as $regionData) {
                $regions[] = new Region($regionData);
            }
            
            return $regions;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des régions: " . $e->getMessage());
        }
    }

    /**
     * Récupère le nom du monde auquel appartient ce pays
     * 
     * @return string|null Nom du monde ou null
     */
    public function getWorldName()
    {
        if ($this->world_id === null) {
            return null;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT name FROM worlds WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->world_id]);
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
            'world_id' => $this->world_id,
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
     * @return string Nom du pays
     */
    public function __toString()
    {
        return $this->name;
    }
}
