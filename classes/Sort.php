<?php

/**
 * Classe Sort - Gestion des sorts D&D 5e
 */
class Sort
{
    private $id;
    private $name;
    private $level;
    private $school;
    private $castingTime;
    private $rangeSp;
    private $components;
    private $duration;
    private $description;
    private $classes;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param array $data Données du sort
     * @param PDO $pdo Instance PDO (optionnel)
     */
    public function __construct($data = [], $pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getInstance()->getPdo();
        $this->hydrate($data);
    }

    /**
     * Hydratation de l'objet
     * 
     * @param array $data Données à hydrater
     */
    private function hydrate($data)
    {
        if (isset($data['id'])) $this->id = (int)$data['id'];
        if (isset($data['name'])) $this->name = $data['name'];
        if (isset($data['level'])) $this->level = (int)$data['level'];
        if (isset($data['school'])) $this->school = $data['school'];
        if (isset($data['casting_time'])) $this->castingTime = $data['casting_time'];
        if (isset($data['range_sp'])) $this->rangeSp = $data['range_sp'];
        if (isset($data['components'])) $this->components = $data['components'];
        if (isset($data['duration'])) $this->duration = $data['duration'];
        if (isset($data['description'])) $this->description = $data['description'];
        if (isset($data['classes'])) $this->classes = $data['classes'];
    }

    /**
     * Obtenir l'instance PDO
     * 
     * @return PDO
     */
    private function getPdo()
    {
        return $this->pdo ?: \Database::getInstance()->getPdo();
    }

    /**
     * Sauvegarder le sort
     * 
     * @return bool Succès de l'opération
     */
    public function save()
    {
        try {
            $pdo = $this->getPdo();
            
            if ($this->id === null) {
                // Insertion
                $stmt = $pdo->prepare("
                    INSERT INTO spells (name, level, school, casting_time, range_sp, components, duration, description, classes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $this->name,
                    $this->level,
                    $this->school,
                    $this->castingTime,
                    $this->rangeSp,
                    $this->components,
                    $this->duration,
                    $this->description,
                    $this->classes
                ]);
                
                if ($result) {
                    $this->id = $pdo->lastInsertId();
                }
                
                return $result;
            } else {
                // Mise à jour
                $stmt = $pdo->prepare("
                    UPDATE spells 
                    SET name = ?, level = ?, school = ?, casting_time = ?, range_sp = ?, 
                        components = ?, duration = ?, description = ?, classes = ?
                    WHERE id = ?
                ");
                return $stmt->execute([
                    $this->name,
                    $this->level,
                    $this->school,
                    $this->castingTime,
                    $this->rangeSp,
                    $this->components,
                    $this->duration,
                    $this->description,
                    $this->classes,
                    $this->id
                ]);
            }
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la sauvegarde du sort: " . $e->getMessage());
        }
    }

    /**
     * Supprimer le sort
     * 
     * @return bool Succès de l'opération
     */
    public function delete()
    {
        if ($this->id === null) {
            return false;
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("DELETE FROM spells WHERE id = ?");
            $result = $stmt->execute([$this->id]);
            
            if ($result) {
                $this->id = null;
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression du sort: " . $e->getMessage());
        }
    }

    /**
     * Trouver un sort par son ID
     * 
     * @param int $id ID du sort
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Sort|null Instance du sort ou null
     */
    public static function findById($id, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $data ? new self($data, $pdo) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du sort: " . $e->getMessage());
        }
    }

    /**
     * Trouver un sort par son nom
     * 
     * @param string $name Nom du sort
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Sort|null Instance du sort ou null
     */
    public static function findByName($name, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE name = ?");
            $stmt->execute([$name]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $data ? new self($data, $pdo) : null;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du sort: " . $e->getMessage());
        }
    }

    /**
     * Trouver tous les sorts
     * 
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts
     */
    public static function findAll($pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells ORDER BY level, name");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sorts = [];
            foreach ($results as $data) {
                $sorts[] = new self($data, $pdo);
            }
            return $sorts;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts: " . $e->getMessage());
        }
    }

    /**
     * Trouver les sorts par niveau
     * 
     * @param int $level Niveau du sort
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts
     */
    public static function findByLevel($level, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE level = ? ORDER BY name");
            $stmt->execute([$level]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sorts = [];
            foreach ($results as $data) {
                $sorts[] = new self($data, $pdo);
            }
            return $sorts;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts: " . $e->getMessage());
        }
    }

    /**
     * Trouver les sorts par école
     * 
     * @param string $school École de magie
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts
     */
    public static function findBySchool($school, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE school = ? ORDER BY level, name");
            $stmt->execute([$school]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sorts = [];
            foreach ($results as $data) {
                $sorts[] = new self($data, $pdo);
            }
            return $sorts;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts: " . $e->getMessage());
        }
    }

    /**
     * Trouver les sorts par classe
     * 
     * @param string $className Nom de la classe
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts
     */
    public static function findByClass($className, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE classes LIKE ? ORDER BY level, name");
            $stmt->execute(["%$className%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sorts = [];
            foreach ($results as $data) {
                $sorts[] = new self($data, $pdo);
            }
            return $sorts;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts: " . $e->getMessage());
        }
    }

    /**
     * Rechercher des sorts par nom
     * 
     * @param string $search Terme de recherche
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts
     */
    public static function searchByName($search, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM spells WHERE name LIKE ? ORDER BY level, name");
            $stmt->execute(["%$search%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sorts = [];
            foreach ($results as $data) {
                $sorts[] = new self($data, $pdo);
            }
            return $sorts;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la recherche des sorts: " . $e->getMessage());
        }
    }

    /**
     * Obtenir les sorts d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts du personnage
     */
    public static function getCharacterSpells($characterId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT s.*, cs.prepared, cs.known
                FROM character_spells cs
                JOIN spells s ON cs.spell_id = s.id
                WHERE cs.character_id = ?
                ORDER BY s.level, s.name
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts du personnage: " . $e->getMessage());
        }
    }

    /**
     * Ajouter un sort à un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @param bool $known Si le sort est connu
     * @param PDO $pdo Instance PDO (optionnel)
     * @return bool Succès de l'opération
     */
    public static function addToCharacter($characterId, $spellId, $prepared = false, $known = true, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO character_spells (character_id, spell_id, prepared, known)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE prepared = ?, known = ?
            ");
            return $stmt->execute([$characterId, $spellId, $prepared, $known, $prepared, $known]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout du sort au personnage: " . $e->getMessage());
        }
    }

    /**
     * Retirer un sort d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param PDO $pdo Instance PDO (optionnel)
     * @return bool Succès de l'opération
     */
    public static function removeFromCharacter($characterId, $spellId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM character_spells WHERE character_id = ? AND spell_id = ?");
            return $stmt->execute([$characterId, $spellId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression du sort du personnage: " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour le statut d'un sort d'un personnage
     * 
     * @param int $characterId ID du personnage
     * @param int $spellId ID du sort
     * @param bool $prepared Si le sort est préparé
     * @param PDO $pdo Instance PDO (optionnel)
     * @return bool Succès de l'opération
     */
    public static function updateCharacterSpellStatus($characterId, $spellId, $prepared, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                UPDATE character_spells 
                SET prepared = ? 
                WHERE character_id = ? AND spell_id = ?
            ");
            return $stmt->execute([$prepared, $characterId, $spellId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du statut du sort: " . $e->getMessage());
        }
    }

    /**
     * Obtenir les sorts d'un monstre
     * 
     * @param int $monsterId ID du monstre
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des sorts du monstre
     */
    public static function getMonsterSpells($monsterId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT s.*, ms.description as monster_spell_description
                FROM monster_spells ms
                JOIN spells s ON ms.spell_id = s.id
                WHERE ms.monster_id = ?
                ORDER BY s.level, s.name
            ");
            $stmt->execute([$monsterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts du monstre: " . $e->getMessage());
        }
    }

    /**
     * Obtenir le label du niveau du sort
     * 
     * @return string Label du niveau
     */
    public function getLevelLabel()
    {
        if ($this->level == 0) {
            return "Tour de magie";
        }
        return "Niveau " . $this->level;
    }

    /**
     * Obtenir le label de l'école de magie
     * 
     * @return string Label de l'école
     */
    public function getSchoolLabel()
    {
        $schools = [
            'Abjuration' => 'Abjuration',
            'Conjuration' => 'Invocation',
            'Divination' => 'Divination',
            'Enchantment' => 'Enchantement',
            'Evocation' => 'Évocation',
            'Illusion' => 'Illusion',
            'Necromancy' => 'Nécromancie',
            'Transmutation' => 'Transmutation'
        ];
        
        return $schools[$this->school] ?? $this->school;
    }

    /**
     * Convertir l'objet en tableau
     * 
     * @return array Données du sort
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'school' => $this->school,
            'casting_time' => $this->castingTime,
            'range_sp' => $this->rangeSp,
            'components' => $this->components,
            'duration' => $this->duration,
            'description' => $this->description,
            'classes' => $this->classes
        ];
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getLevel() { return $this->level; }
    public function getSchool() { return $this->school; }
    public function getCastingTime() { return $this->castingTime; }
    public function getRangeSp() { return $this->rangeSp; }
    public function getComponents() { return $this->components; }
    public function getDuration() { return $this->duration; }
    public function getDescription() { return $this->description; }
    public function getClasses() { return $this->classes; }

    // Setters
    public function setName($name) { $this->name = $name; }
    public function setLevel($level) { $this->level = (int)$level; }
    public function setSchool($school) { $this->school = $school; }
    public function setCastingTime($castingTime) { $this->castingTime = $castingTime; }
    public function setRangeSp($rangeSp) { $this->rangeSp = $rangeSp; }
    public function setComponents($components) { $this->components = $components; }
    public function setDuration($duration) { $this->duration = $duration; }
    public function setDescription($description) { $this->description = $description; }
    public function setClasses($classes) { $this->classes = $classes; }
}
