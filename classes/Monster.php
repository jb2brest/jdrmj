<?php
require_once __DIR__ . '/../config/database.php';

class Monster
{
    private $id;
    private $monster_type_id;
    private $name;
    private $description;
    private $current_hit_points;
    private $max_hit_points;
    private $quantity;
    private $is_visible;
    private $is_identified;
    private $created_by;
    private $created_at;
    private $updated_at;
    private $image_url;
    private $pdo;

    public function __construct($id, $monster_type_id, $name, $description, $current_hit_points, $max_hit_points, $quantity, $is_visible, $is_identified, $created_by, $created_at, $updated_at, $image_url = null, $pdo = null)
    {
        $this->id = $id;
        $this->monster_type_id = $monster_type_id;
        $this->name = $name;
        $this->description = $description;
        $this->current_hit_points = $current_hit_points;
        $this->max_hit_points = $max_hit_points;
        $this->quantity = $quantity;
        $this->is_visible = $is_visible;
        $this->is_identified = $is_identified;
        $this->created_by = $created_by;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->image_url = $image_url;
        $this->pdo = $pdo ?: getPDO();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getMonsterTypeId() { return $this->monster_type_id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getCurrentHitPoints() { return $this->current_hit_points; }
    public function getMaxHitPoints() { return $this->max_hit_points; }
    public function getQuantity() { return $this->quantity; }
    public function isVisible() { return $this->is_visible; }
    public function isIdentified() { return $this->is_identified; }
    public function getCreatedBy() { return $this->created_by; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
    public function getImageUrl() { return $this->image_url; }

    /**
     * Crée une nouvelle instance de monstre dans la table 'monsters'.
     *
     * @param int $monsterTypeId L'ID du type de monstre (de dnd_monsters).
     * @param string $name Le nom de l'instance de monstre.
     * @param string $description La description de l'instance de monstre.
     * @param int $quantity La quantité de ce monstre.
     * @param int $createdBy L'ID de l'utilisateur qui a créé le monstre.
     * @return Monster|false L'objet Monster créé ou false en cas d'échec.
     */
    public static function create($monsterTypeId, $name, $description = '', $quantity = 1, $createdBy = null)
    {
        $pdo = getPDO();
        if ($createdBy === null && isset($_SESSION['user_id'])) {
            $createdBy = $_SESSION['user_id'];
        } elseif ($createdBy === null) {
            error_log("Erreur: Impossible de créer un monstre sans user_id et session non définie.");
            return false;
        }

        try {
            // Récupérer les HP du type de monstre
            $stmt = $pdo->prepare("SELECT hit_points FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$monsterTypeId]);
            $dndMonster = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dndMonster) {
                error_log("Erreur: Type de monstre (dnd_monsters) introuvable pour l'ID: " . $monsterTypeId);
                return false;
            }
            $maxHitPoints = $dndMonster['hit_points'];

            $stmt = $pdo->prepare("
                INSERT INTO monsters (monster_type_id, name, description, current_hit_points, max_hit_points, quantity, is_visible, is_identified, created_by, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $monsterTypeId,
                $name,
                $description,
                $maxHitPoints, // Initial current_hit_points to max_hit_points
                $maxHitPoints,
                $quantity,
                0, // is_visible par défaut à 0 (invisible)
                0, // is_identified par défaut à 0 (non identifié)
                $createdBy,
                null // image_url par défaut à null
            ]);

            $id = $pdo->lastInsertId();
            return new self($id, $monsterTypeId, $name, $description, $maxHitPoints, $maxHitPoints, $quantity, 0, 0, $createdBy, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), null, $pdo);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'instance de monstre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère une instance de monstre par son ID.
     *
     * @param int $id L'ID de l'instance de monstre.
     * @return Monster|false L'objet Monster ou false si non trouvé.
     */
    public static function findById($id)
    {
        $pdo = getPDO();
        try {
            $stmt = $pdo->prepare("SELECT * FROM monsters WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new self(
                    $data['id'],
                    $data['monster_type_id'],
                    $data['name'],
                    $data['description'],
                    $data['current_hit_points'],
                    $data['max_hit_points'],
                    $data['quantity'],
                    $data['is_visible'],
                    $data['is_identified'],
                    $data['created_by'],
                    $data['created_at'],
                    $data['updated_at'],
                    $data['image_url'] ?? null,
                    $pdo
                );
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'instance de monstre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute cette instance de monstre à un lieu spécifique dans place_monsters.
     *
     * @param int $placeId L'ID du lieu.
     * @return bool True en cas de succès, false en cas d'échec.
     */
    public function addToPlace($placeId)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO place_monsters (place_id, monster_id, is_visible, is_identified)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$placeId, $this->id, $this->is_visible, $this->is_identified]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du monstre au lieu (place_monsters): " . $e->getMessage());
            return false;
        }
    }

    // Méthodes pour mettre à jour les propriétés (exemple)
    public function updateVisibility($isVisible)
    {
        $this->is_visible = $isVisible;
        try {
            $stmt = $this->pdo->prepare("UPDATE monsters SET is_visible = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$isVisible, $this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la visibilité du monstre: " . $e->getMessage());
            return false;
        }
    }

    public function updateIdentification($isIdentified)
    {
        $this->is_identified = $isIdentified;
        try {
            $stmt = $this->pdo->prepare("UPDATE monsters SET is_identified = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$isIdentified, $this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'identification du monstre: " . $e->getMessage());
            return false;
        }
    }

    public function updateHitPoints($newHitPoints)
    {
        $this->current_hit_points = $newHitPoints;
        try {
            $stmt = $this->pdo->prepare("UPDATE monsters SET current_hit_points = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newHitPoints, $this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points de vie du monstre: " . $e->getMessage());
            return false;
        }
    }

    public function updateImageUrl($imageUrl)
    {
        $this->image_url = $imageUrl;
        try {
            $stmt = $this->pdo->prepare("UPDATE monsters SET image_url = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$imageUrl, $this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'image du monstre: " . $e->getMessage());
            return false;
        }
    }

    public function delete()
    {
        try {
            // Supprimer d'abord de place_monsters
            $stmt = $this->pdo->prepare("DELETE FROM place_monsters WHERE monster_id = ?");
            $stmt->execute([$this->id]);

            // Puis supprimer de monsters
            $stmt = $this->pdo->prepare("DELETE FROM monsters WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du monstre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les actions du monstre depuis la table monster_actions
     * Les actions sont liées au type de monstre (monster_type_id), pas à l'instance
     * 
     * @return array Liste des actions avec toutes les colonnes
     */
    public function getActions()
    {
        if ($this->monster_type_id === null) {
            return [];
        }

        try {
            // Les actions sont liées au type de monstre (dnd_monsters.id), pas à l'instance
            $stmt = $this->pdo->prepare("SELECT * FROM monster_actions WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->monster_type_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des actions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les attaques spéciales du monstre depuis la table monster_special_attacks
     * Les attaques spéciales sont liées au type de monstre (monster_type_id), pas à l'instance
     * 
     * @return array Liste des attaques spéciales avec toutes les colonnes
     */
    public function getSpecialAttacks()
    {
        if ($this->monster_type_id === null) {
            return [];
        }

        try {
            // Les attaques spéciales sont liées au type de monstre (dnd_monsters.id), pas à l'instance
            $stmt = $this->pdo->prepare("SELECT * FROM monster_special_attacks WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->monster_type_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des attaques spéciales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les actions légendaires du monstre depuis la table monster_legendary_actions
     * Les actions légendaires sont liées au type de monstre (monster_type_id), pas à l'instance
     * 
     * @return array Liste des actions légendaires avec toutes les colonnes
     */
    public function getLegendaryActions()
    {
        if ($this->monster_type_id === null) {
            return [];
        }

        try {
            // Les actions légendaires sont liées au type de monstre (dnd_monsters.id), pas à l'instance
            $stmt = $this->pdo->prepare("SELECT * FROM monster_legendary_actions WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->monster_type_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des actions légendaires: " . $e->getMessage());
            return [];
        }
    }
}
?>