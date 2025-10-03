<?php

/**
 * Classe StartingEquipment - Gestion de l'équipement de départ
 * 
 * Cette classe encapsule toutes les fonctionnalités liées à l'équipement de départ
 * du système JDR MJ, incluant la création, gestion et récupération des équipements.
 */
class StartingEquipment
{
    private $id;
    private $src;
    private $srcId;
    private $type;
    private $typeId;
    private $typeFilter;
    private $noChoix;
    private $optionLetter;
    private $typeChoix;
    private $nb;
    private $groupeId;
    private $createdAt;
    private $updatedAt;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO pour la base de données (optionnel)
     * @param array $data Données de l'équipement de départ (optionnel)
     */
    public function __construct(PDO $pdo = null, array $data = [])
    {
        $this->pdo = $pdo ?: getPDO();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrater l'objet avec des données
     * 
     * @param array $data Données à hydrater
     */
    private function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->src = $data['src'] ?? null;
        $this->srcId = $data['src_id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->typeId = $data['type_id'] ?? null;
        $this->typeFilter = $data['type_filter'] ?? null;
        $this->noChoix = $data['no_choix'] ?? null;
        $this->optionLetter = $data['option_letter'] ?? null;
        $this->typeChoix = $data['type_choix'] ?? 'obligatoire';
        $this->nb = $data['nb'] ?? 1;
        $this->groupeId = $data['groupe_id'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    /**
     * Créer un nouvel équipement de départ
     * 
     * @param array $data Données de l'équipement
     * @param PDO $pdo Instance PDO (optionnel)
     * @return StartingEquipment|false L'équipement créé ou false en cas d'erreur
     */
    public static function create(array $data, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                INSERT INTO starting_equipment (
                    src, src_id, type, type_id, type_filter, no_choix, 
                    option_letter, type_choix, nb, groupe_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['src'],
                $data['src_id'],
                $data['type'],
                $data['type_id'] ?? null,
                $data['type_filter'] ?? null,
                $data['no_choix'] ?? null,
                $data['option_letter'] ?? null,
                $data['type_choix'] ?? 'obligatoire',
                $data['nb'] ?? 1,
                $data['groupe_id'] ?? null
            ]);
            
            $equipmentId = $pdo->lastInsertId();
            return self::findById($equipmentId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'équipement de départ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un équipement de départ par son ID
     * 
     * @param int $id ID de l'équipement
     * @param PDO $pdo Instance PDO (optionnel)
     * @return StartingEquipment|null L'équipement trouvé ou null
     */
    public static function findById(int $id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM starting_equipment WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de l'équipement de départ: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver tous les équipements de départ d'une source
     * 
     * @param string $src Source (class, background, race)
     * @param int $srcId ID de la source
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des équipements
     */
    public static function findBySource(string $src, int $srcId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment 
                WHERE src = ? AND src_id = ? 
                ORDER BY no_choix, option_letter
            ");
            $stmt->execute([$src, $srcId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des équipements de départ: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les équipements de départ d'un type
     * 
     * @param string $type Type d'équipement
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des équipements
     */
    public static function findByType(string $type, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment 
                WHERE type = ? 
                ORDER BY src, src_id, no_choix
            ");
            $stmt->execute([$type]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des équipements par type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les équipements de départ d'un groupe
     * 
     * @param int $groupeId ID du groupe
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des équipements
     */
    public static function findByGroupe(int $groupeId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment 
                WHERE groupe_id = ? 
                ORDER BY no_choix, option_letter
            ");
            $stmt->execute([$groupeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des équipements par groupe: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les équipements obligatoires d'une source
     * 
     * @param string $src Source (class, background, race)
     * @param int $srcId ID de la source
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des équipements obligatoires
     */
    public static function findObligatoryBySource(string $src, int $srcId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment 
                WHERE src = ? AND src_id = ? AND type_choix = 'obligatoire'
                ORDER BY no_choix
            ");
            $stmt->execute([$src, $srcId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des équipements obligatoires: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les équipements à choisir d'une source
     * 
     * @param string $src Source (class, background, race)
     * @param int $srcId ID de la source
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des équipements à choisir
     */
    public static function findChoicesBySource(string $src, int $srcId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT * FROM starting_equipment 
                WHERE src = ? AND src_id = ? AND type_choix = 'à_choisir'
                ORDER BY no_choix, option_letter
            ");
            $stmt->execute([$src, $srcId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des équipements à choisir: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour l'équipement de départ
     * 
     * @param array $data Données à mettre à jour
     * @return bool True si succès, false sinon
     */
    public function update(array $data)
    {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                    $this->$key = $value;
                }
            }
            
            if (empty($fields)) {
                return true;
            }
            
            $values[] = $this->id;
            
            $stmt = $this->pdo->prepare("
                UPDATE starting_equipment 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'équipement de départ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer l'équipement de départ
     * 
     * @return bool True si succès, false sinon
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM starting_equipment WHERE id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'équipement de départ: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // GETTERS
    // =====================================================

    public function getId()
    {
        return $this->id;
    }

    public function getSrc()
    {
        return $this->src;
    }

    public function getSrcId()
    {
        return $this->srcId;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTypeId()
    {
        return $this->typeId;
    }

    public function getTypeFilter()
    {
        return $this->typeFilter;
    }

    public function getNoChoix()
    {
        return $this->noChoix;
    }

    public function getOptionLetter()
    {
        return $this->optionLetter;
    }

    public function getTypeChoix()
    {
        return $this->typeChoix;
    }

    public function getNb()
    {
        return $this->nb;
    }

    public function getGroupeId()
    {
        return $this->groupeId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // =====================================================
    // MÉTHODES UTILITAIRES
    // =====================================================

    /**
     * Convertit l'objet en tableau
     * 
     * @return array Données de l'équipement de départ
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'src' => $this->src,
            'src_id' => $this->srcId,
            'type' => $this->type,
            'type_id' => $this->typeId,
            'type_filter' => $this->typeFilter,
            'no_choix' => $this->noChoix,
            'option_letter' => $this->optionLetter,
            'type_choix' => $this->typeChoix,
            'nb' => $this->nb,
            'groupe_id' => $this->groupeId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Obtenir le label de la source en français
     * 
     * @return string Label de la source
     */
    public function getSrcLabel()
    {
        $labels = [
            'class' => 'Classe',
            'background' => 'Historique',
            'race' => 'Race'
        ];
        
        return $labels[$this->src] ?? ucfirst($this->src);
    }

    /**
     * Obtenir le label du type d'équipement en français
     * 
     * @return string Label du type
     */
    public function getTypeLabel()
    {
        $labels = [
            'Outils' => 'Outils',
            'Armure' => 'Armure',
            'Bouclier' => 'Bouclier',
            'Arme' => 'Arme',
            'Accessoire' => 'Accessoire',
            'Sac' => 'Sac'
        ];
        
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Obtenir le label du type de choix en français
     * 
     * @return string Label du type de choix
     */
    public function getTypeChoixLabel()
    {
        $labels = [
            'obligatoire' => 'Obligatoire',
            'à_choisir' => 'À choisir'
        ];
        
        return $labels[$this->typeChoix] ?? $this->typeChoix;
    }

    /**
     * Vérifier si l'équipement est obligatoire
     * 
     * @return bool True si obligatoire, false sinon
     */
    public function isObligatory()
    {
        return $this->typeChoix === 'obligatoire';
    }

    /**
     * Vérifier si l'équipement est à choisir
     * 
     * @return bool True si à choisir, false sinon
     */
    public function isChoice()
    {
        return $this->typeChoix === 'à_choisir';
    }

    /**
     * Obtenir la description complète de l'équipement
     * 
     * @return string Description complète
     */
    public function getFullDescription()
    {
        $parts = [];
        
        if ($this->nb > 1) {
            $parts[] = $this->nb . 'x';
        }
        
        $parts[] = $this->getTypeLabel();
        
        if ($this->typeFilter) {
            $parts[] = '(' . $this->typeFilter . ')';
        }
        
        if ($this->optionLetter) {
            $parts[] = '[' . strtoupper($this->optionLetter) . ']';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Obtenir le nom de l'équipement avec quantité
     * 
     * @return string Nom avec quantité
     */
    public function getNameWithQuantity()
    {
        if ($this->nb > 1) {
            return $this->nb . ' ' . $this->getTypeLabel();
        }
        
        return $this->getTypeLabel();
    }

    /**
     * Récupérer les choix d'équipement de départ pour une classe
     * 
     * @param string $className Nom de la classe
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des choix d'équipement
     */
    public static function getStartingEquipementOptionForClass(string $className, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT se.* FROM starting_equipment se
                INNER JOIN classes c ON se.src_id = c.id
                WHERE se.src = 'class' AND c.name = ?
                ORDER BY se.no_choix, se.option_letter
            ");
            $stmt->execute([$className]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des équipements de classe: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les choix d'équipement de départ pour un background
     * 
     * @param string $backgroundName Nom du background
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des choix d'équipement
     */
    public static function getStartingEquipementOptionForBackground(string $backgroundName, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                SELECT se.* FROM starting_equipment se
                INNER JOIN backgrounds b ON se.src_id = b.id
                WHERE se.src = 'background' AND b.name = ?
                ORDER BY se.no_choix, se.option_letter
            ");
            $stmt->execute([$backgroundName]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $equipments = [];
            foreach ($results as $data) {
                $equipments[] = new self($pdo, $data);
            }
            
            return $equipments;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des équipements de background: " . $e->getMessage());
            return [];
        }
    }
}
