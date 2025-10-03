<?php

/**
 * Classe StartingEquipmentChoix
 * Représente un choix d'équipement de départ (soit des items par défaut, soit des options à choisir)
 */
class StartingEquipmentChoix
{
    private $id;
    private $src;
    private $srcId;
    private $noChoix;
    private $optionLetter;
    private $options;
    private $createdAt;
    private $updatedAt;
    private $pdo;

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
     * Hydratation des données
     */
    private function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->src = $data['src'] ?? null;
        $this->srcId = $data['src_id'] ?? null;
        $this->noChoix = $data['no_choix'] ?? null;
        $this->optionLetter = $data['option_letter'] ?? null;
        $this->options = $data['options'] ?? [];
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getSrc() { return $this->src; }
    public function getSrcId() { return $this->srcId; }
    public function getNoChoix() { return $this->noChoix; }
    public function getOptionLetter() { return $this->optionLetter; }
    public function getOptions() { return $this->options; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    /**
     * Créer un nouveau choix d'équipement
     */
    public static function create(array $data, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                INSERT INTO starting_equipment_choix (
                    src, src_id, no_choix, option_letter, created_at, updated_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $data['src'],
                $data['src_id'],
                $data['no_choix'],
                $data['option_letter'] ?? null
            ]);
            
            $choix = new self($pdo, [
                'id' => $pdo->lastInsertId(),
                'src' => $data['src'],
                'src_id' => $data['src_id'],
                'no_choix' => $data['no_choix'],
                'option_letter' => $data['option_letter'] ?? null,
                'options' => [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $choix;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du choix d'équipement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un choix par ID
     */
    public static function findById(int $id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM starting_equipment_choix WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                // Charger les options
                $data['options'] = StartingEquipmentOption::findByStartingEquipmentChoixId($data['id'], $pdo);
                
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du choix d'équipement: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver tous les choix pour une source (classe ou background)
     */
    public static function findBySource(string $src, int $srcId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment_choix 
                WHERE src = ? AND src_id = ? 
                ORDER BY no_choix
            ");
            $stmt->execute([$src, $srcId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $choix = [];
            foreach ($results as $data) {
                // Charger les options
                $data['options'] = StartingEquipmentOption::findByStartingEquipmentChoixId($data['id'], $pdo);
                
                $choix[] = new self($pdo, $data);
            }
            
            return $choix;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des choix d'équipement: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les choix pour une classe par nom
     */
    public static function findByClassName(string $className, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT sc.* FROM starting_equipment_choix sc
                INNER JOIN classes c ON sc.src_id = c.id
                WHERE sc.src = 'class' AND c.name = ?
                ORDER BY sc.no_choix
            ");
            $stmt->execute([$className]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $choix = [];
            foreach ($results as $data) {
                // Charger les options
                $data['options'] = StartingEquipmentOption::findByStartingEquipmentChoixId($data['id'], $pdo);
                
                $choix[] = new self($pdo, $data);
            }
            
            return $choix;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des choix d'équipement par classe: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les choix pour un background par nom
     */
    public static function findByBackgroundName(string $backgroundName, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT sc.* FROM starting_equipment_choix sc
                INNER JOIN backgrounds b ON sc.src_id = b.id
                WHERE sc.src = 'background' AND b.name = ?
                ORDER BY sc.no_choix
            ");
            $stmt->execute([$backgroundName]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $choix = [];
            foreach ($results as $data) {
                // Charger les options
                $data['options'] = StartingEquipmentOption::findByStartingEquipmentChoixId($data['id'], $pdo);
                
                $choix[] = new self($pdo, $data);
            }
            
            return $choix;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des choix d'équipement par background: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour le choix
     */
    public function update(array $data)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE starting_equipment_choix 
                SET option_letter = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['option_letter'] ?? $this->optionLetter,
                $this->id
            ]);
            
            // Mettre à jour les propriétés
            $this->optionLetter = $data['option_letter'] ?? $this->optionLetter;
            $this->updatedAt = date('Y-m-d H:i:s');
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du choix d'équipement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer le choix
     */
    public function delete()
    {
        try {
            // Supprimer d'abord les options
            foreach ($this->options as $option) {
                $option->delete();
            }
            
            // Supprimer le choix
            $stmt = $this->pdo->prepare("DELETE FROM starting_equipment_choix WHERE id = ?");
            $stmt->execute([$this->id]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du choix d'équipement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajouter une option au choix
     */
    public function addOption(array $optionData)
    {
        try {
            $optionData['starting_equipment_choix_id'] = $this->id;
            $option = StartingEquipmentOption::create($optionData, $this->pdo);
            
            if ($option) {
                $this->options[] = $option;
                return $option;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout d'une option: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convertir en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'src' => $this->src,
            'src_id' => $this->srcId,
            'no_choix' => $this->noChoix,
            'option_letter' => $this->optionLetter,
            'options' => array_map(function($option) { return $option->toArray(); }, $this->options),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Vérifier si c'est un choix avec options
     */
    public function hasOptions()
    {
        return !empty($this->options);
    }

    /**
     * Obtenir la description complète du choix
     */
    public function getFullDescription()
    {
        $description = "Choix " . $this->noChoix;
        if ($this->optionLetter) {
            $description .= " - Option " . strtoupper($this->optionLetter);
        }
        return $description;
    }

    /**
     * Obtenir le nombre d'options disponibles
     */
    public function getOptionsCount()
    {
        return count($this->options);
    }

    /**
     * Obtenir une option par lettre
     */
    public function getOptionByLetter(string $letter)
    {
        foreach ($this->options as $option) {
            if ($option->getOptionLetter() === $letter) {
                return $option;
            }
        }
        return null;
    }
}


