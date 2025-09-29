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
    private $pdo;

    /**
     * Constructeur de la classe Monde
     * 
     * @param PDO $pdo Instance de connexion à la base de données
     * @param array $data Données optionnelles pour initialiser l'objet
     */
    public function __construct(PDO $pdo, array $data = [])
    {
        $this->pdo = $pdo;
        
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
            if ($this->id === null) {
                // Création d'un nouveau monde
                $sql = "INSERT INTO worlds (name, description, map_url, created_by) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$this->name, $this->description, $this->map_url, $this->created_by]);
                
                $this->id = $this->pdo->lastInsertId();
                return true;
            } else {
                // Mise à jour d'un monde existant
                $sql = "UPDATE worlds SET name = ?, description = ?, map_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
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
            // Vérifier s'il y a des pays dans ce monde
            $sql = "SELECT COUNT(*) FROM countries WHERE world_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->id]);
            $country_count = $stmt->fetchColumn();

            if ($country_count > 0) {
                throw new Exception("Impossible de supprimer ce monde car il contient $country_count pays. Supprimez d'abord les pays.");
            }

            // Supprimer le monde
            $sql = "DELETE FROM worlds WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
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
     * @param PDO $pdo Instance de connexion à la base de données
     * @param int $id ID du monde
     * @return Monde|null Instance de Monde ou null si non trouvé
     */
    public static function findById(PDO $pdo, int $id)
    {
        try {
            $sql = "SELECT * FROM worlds WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du monde: " . $e->getMessage());
        }
    }

    /**
     * Récupère tous les mondes d'un utilisateur
     * 
     * @param PDO $pdo Instance de connexion à la base de données
     * @param int $user_id ID de l'utilisateur
     * @return array Tableau d'instances de Monde
     */
    public static function findByUser(PDO $pdo, int $user_id)
    {
        try {
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
                $world = new self($pdo, $data);
                $worlds[] = $world;
            }
            return $worlds;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des mondes: " . $e->getMessage());
        }
    }

    /**
     * Vérifie si un nom de monde existe déjà pour un utilisateur
     * 
     * @param PDO $pdo Instance de connexion à la base de données
     * @param string $name Nom du monde
     * @param int $user_id ID de l'utilisateur
     * @param int $exclude_id ID du monde à exclure (pour les mises à jour)
     * @return bool True si le nom existe déjà
     */
    public static function nameExists(PDO $pdo, string $name, int $user_id, int $exclude_id = null)
    {
        try {
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
            $sql = "SELECT COUNT(*) FROM countries WHERE world_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du comptage des pays: " . $e->getMessage());
        }
    }

    /**
     * Récupère les pays de ce monde
     * 
     * @return array Tableau des pays
     */
    public function getCountries()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $sql = "SELECT * FROM countries WHERE world_id = ? ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des pays: " . $e->getMessage());
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
}
