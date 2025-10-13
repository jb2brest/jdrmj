<?php

/**
 * Classe StartingEquipmentOption
 * Représente une option d'équipement de départ (un ensemble d'items affectés ensemble)
 */
class StartingEquipmentOption
{
    private $id;
    private $startingEquipmentChoixId;
    private $src;
    private $srcId;
    private $type;
    private $typeId;
    private $typeFilter;
    private $nb;
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
        $this->startingEquipmentChoixId = $data['starting_equipment_choix_id'] ?? null;
        $this->src = $data['src'] ?? null;
        $this->srcId = $data['src_id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->typeId = $data['type_id'] ?? null;
        $this->typeFilter = $data['type_filter'] ?? null;
        $this->nb = $data['nb'] ?? 1;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getStartingEquipmentChoixId() { return $this->startingEquipmentChoixId; }
    public function getSrc() { return $this->src; }
    public function getSrcId() { return $this->srcId; }
    public function getType() { return $this->type; }
    public function getTypeId() { return $this->typeId; }
    public function getTypeFilter() { return $this->typeFilter; }
    public function getNb() { return $this->nb; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    /**
     * Créer une nouvelle option d'équipement
     */
    public static function create(array $data, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                INSERT INTO starting_equipment_options (
                    starting_equipment_choix_id, src, src_id, type, type_id, type_filter, nb, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $data['starting_equipment_choix_id'],
                $data['src'],
                $data['src_id'],
                $data['type'],
                $data['type_id'] ?? null,
                $data['type_filter'] ?? null,
                $data['nb'] ?? 1
            ]);
            
            $option = new self($pdo, [
                'id' => $pdo->lastInsertId(),
                'starting_equipment_choix_id' => $data['starting_equipment_choix_id'],
                'src' => $data['src'],
                'src_id' => $data['src_id'],
                'type' => $data['type'],
                'type_id' => $data['type_id'] ?? null,
                'type_filter' => $data['type_filter'] ?? null,
                'nb' => $data['nb'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $option;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'option d'équipement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver une option par ID
     */
    public static function findById(int $id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM starting_equipment_options WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de l'option d'équipement: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver toutes les options d'un choix
     */
    public static function findByStartingEquipmentChoixId(int $startingEquipmentChoixId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment_options 
                WHERE starting_equipment_choix_id = ? 
                ORDER BY type, type_id
            ");
            $stmt->execute([$startingEquipmentChoixId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $options = [];
            foreach ($results as $data) {
                $options[] = new self($pdo, $data);
            }
            
            return $options;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des options d'équipement: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver toutes les options d'une source (classe ou background)
     */
    public static function findBySource(string $src, int $srcId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment_options 
                WHERE src = ? AND src_id = ? 
                ORDER BY starting_equipment_choix_id, type, type_id
            ");
            $stmt->execute([$src, $srcId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $options = [];
            foreach ($results as $data) {
                $options[] = new self($pdo, $data);
            }
            
            return $options;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des options d'équipement: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour l'option
     */
    public function update(array $data)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE starting_equipment_options 
                SET starting_equipment_choix_id = ?, src = ?, src_id = ?, type = ?, type_id = ?, type_filter = ?, nb = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['starting_equipment_choix_id'] ?? $this->startingEquipmentChoixId,
                $data['src'] ?? $this->src,
                $data['src_id'] ?? $this->srcId,
                $data['type'] ?? $this->type,
                $data['type_id'] ?? $this->typeId,
                $data['type_filter'] ?? $this->typeFilter,
                $data['nb'] ?? $this->nb,
                $this->id
            ]);
            
            // Mettre à jour les propriétés
            $this->startingEquipmentChoixId = $data['starting_equipment_choix_id'] ?? $this->startingEquipmentChoixId;
            $this->src = $data['src'] ?? $this->src;
            $this->srcId = $data['src_id'] ?? $this->srcId;
            $this->type = $data['type'] ?? $this->type;
            $this->typeId = $data['type_id'] ?? $this->typeId;
            $this->typeFilter = $data['type_filter'] ?? $this->typeFilter;
            $this->nb = $data['nb'] ?? $this->nb;
            $this->updatedAt = date('Y-m-d H:i:s');
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'option d'équipement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer l'option
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM starting_equipment_options WHERE id = ?");
            $stmt->execute([$this->id]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'option d'équipement: " . $e->getMessage());
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
            'starting_equipment_choix_id' => $this->startingEquipmentChoixId,
            'src' => $this->src,
            'src_id' => $this->srcId,
            'type' => $this->type,
            'type_id' => $this->typeId,
            'type_filter' => $this->typeFilter,
            'nb' => $this->nb,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Obtenir la description complète de l'option
     */
    public function getFullDescription()
    {
        $parts = [];
        
        if ($this->nb > 1) {
            $parts[] = $this->nb . 'x';
        }
        
        $parts[] = $this->type;
        
        if ($this->typeFilter) {
            $parts[] = '(' . $this->typeFilter . ')';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Obtenir le label du type d'équipement en français
     */
    public function getTypeLabel()
    {
        $labels = [
            'armor' => 'Armure',
            'bouclier' => 'Bouclier',
            'instrument' => 'Instrument',
            'nourriture' => 'Nourriture',
            'outils' => 'Outils',
            'sac' => 'Sac',
            'weapon' => 'Arme'
        ];
        
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Obtenir le nom réel de l'équipement depuis la table correspondante
     */
    public function getRealItemName()
    {
        if (!$this->typeId) {
            return $this->getTypeLabel();
        }

        try {
            $pdo = $this->pdo ?: getPDO();
            
            // Chercher dans la table correspondant au type d'équipement
            switch ($this->type) {
                case 'weapon':
                    $stmt = $pdo->prepare("SELECT name FROM weapons WHERE id = ?");
                    $stmt->execute([$this->typeId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        return $result['name'];
                    }
                    break;
                    
                case 'armor':
                case 'bouclier':
                    $stmt = $pdo->prepare("SELECT name FROM armor WHERE id = ?");
                    $stmt->execute([$this->typeId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        return $result['name'];
                    }
                    break;
                    
                case 'instrument':
                case 'outils':
                case 'nourriture':
                case 'sac':
                    // Ces types utilisent la table Object
                    $stmt = $pdo->prepare("SELECT nom FROM Object WHERE id = ?");
                    $stmt->execute([$this->typeId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        return $result['nom'];
                    }
                    break;
            }
            
            // Si pas trouvé dans la table spécifique, essayer les autres tables
            // Essayer la table magical_items
            $stmt = $pdo->prepare("SELECT nom FROM magical_items WHERE id = ?");
            $stmt->execute([$this->typeId]);
            $result = $stmt->fetch();
            if ($result) {
                return $result['nom'];
            }
            
            // Essayer la table poisons
            $stmt = $pdo->prepare("SELECT nom FROM poisons WHERE id = ?");
            $stmt->execute([$this->typeId]);
            $result = $stmt->fetch();
            if ($result) {
                return $result['nom'];
            }
            
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du nom d'équipement: " . $e->getMessage());
        }
        
        return $this->getTypeLabel();
    }

    /**
     * Obtenir le nom de l'équipement avec quantité
     */
    public function getNameWithQuantity()
    {
        $itemName = $this->getRealItemName();
        
        if ($this->nb > 1) {
            return $this->nb . 'x ' . $itemName;
        }
        
        return $itemName;
    }

    /**
     * Récupérer les armes filtrées par type depuis la table weapons
     */
    public function getFilteredWeapons()
    {
        if ($this->type !== 'weapon' || !$this->typeFilter) {
            return [];
        }

        try {
            $pdo = $this->pdo ?: getPDO();
            
            // Récupérer les armes qui correspondent au filtre de type
            $stmt = $pdo->prepare("
                SELECT id, name, type, damage, weight, price, properties 
                FROM weapons 
                WHERE type = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$this->typeFilter]);
            $weapons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $weapons;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des armes filtrées: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si cette option nécessite un menu déroulant d'armes
     */
    public function needsWeaponDropdown()
    {
        return $this->type === 'weapon' && !empty($this->typeFilter);
    }
}


