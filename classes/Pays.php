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
     * Obtient l'instance PDO
     * 
     * @return PDO Instance PDO
     */
    private function getPdo()
    {
        return getPDO();
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
            $pdo = getPDO();
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
            $pdo = getPDO();
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
            $pdo = getPDO();
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
            $pdo = getPDO();
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
     * @return array|null Données du monde ou null si non trouvé
     */
    public function getMonde()
    {
        if ($this->world_id === null) {
            return null;
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT * FROM worlds WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->world_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
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
            
            // Retourner les données brutes pour éviter les dépendances
            return $regionsData;
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
     * Récupère les accès interrégionaux pour ce pays
     * 
     * Cette méthode retourne la liste des accès entre deux pièces de deux régions différentes,
     * dont au moins une des régions est à l'intérieur du pays concerné.
     * 
     * @return array Tableau des accès interrégionaux avec informations détaillées
     * @throws Exception En cas d'erreur de base de données
     */
    public function getInterRegionAccesses()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            
            // Récupérer les accès entre pièces de régions différentes
            // où au moins une des régions appartient à ce pays
            $sql = "
                SELECT 
                    a.*,
                    fp.title as from_place_name,
                    fp.region_id as from_region_id,
                    fr.name as from_region_name,
                    fc.name as from_country_name,
                    tp.title as to_place_name,
                    tp.region_id as to_region_id,
                    tr.name as to_region_name,
                    tc.name as to_country_name
                FROM accesses a
                JOIN places fp ON a.from_place_id = fp.id
                JOIN places tp ON a.to_place_id = tp.id
                LEFT JOIN regions fr ON fp.region_id = fr.id
                LEFT JOIN regions tr ON tp.region_id = tr.id
                LEFT JOIN countries fc ON fr.country_id = fc.id
                LEFT JOIN countries tc ON tr.country_id = tc.id
                WHERE (fr.country_id = ? OR tr.country_id = ?)
                  AND fp.region_id != tp.region_id
                ORDER BY a.from_place_id, a.name
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id, $this->id]);
            $accesses = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            return $accesses;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des accès interrégionaux: " . $e->getMessage());
        }
    }

    /**
     * Récupère les pièces externes connectés à ce pays
     * 
     * Cette méthode retourne les pièces qui sont dans d'autres pays/régions
     * mais qui ont des accès avec des pièces de ce pays.
     * 
     * @return array Tableau des pièces externes avec informations détaillées
     * @throws Exception En cas d'erreur de base de données
     */
    public function getExternalPlaces()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            
            // Récupérer les pièces externes (dans d'autres pays/régions)
            // qui ont des accès avec des pièces de ce pays
            $sql = "
                SELECT DISTINCT
                    p.*,
                    r.name as region_name,
                    c.name as country_name
                FROM places p
                JOIN regions r ON p.region_id = r.id
                JOIN countries c ON r.country_id = c.id
                WHERE c.id != ?
                  AND p.id IN (
                      SELECT DISTINCT a.to_place_id
                      FROM accesses a
                      JOIN places fp ON a.from_place_id = fp.id
                      JOIN regions fr ON fp.region_id = fr.id
                      WHERE fr.country_id = ?
                      
                      UNION
                      
                      SELECT DISTINCT a.from_place_id
                      FROM accesses a
                      JOIN places tp ON a.to_place_id = tp.id
                      JOIN regions tr ON tp.region_id = tr.id
                      WHERE tr.country_id = ?
                  )
                ORDER BY c.name, r.name, p.title
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->id, $this->id, $this->id]);
            $externalPlaces = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            return $externalPlaces;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pièces externes: " . $e->getMessage());
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

    /**
     * Récupérer tous les pays
     * 
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste de tous les pays
     */
    public static function getAllCountries(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->query("SELECT id, name, world_id FROM countries ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les pays: " . $e->getMessage());
            return [];
        }
    }
}
