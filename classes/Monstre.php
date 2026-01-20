<?php

/**
 * Classe Monstre - Gestion des monstres D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux monstres :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des statistiques et compétences
 * - Gestion des actions et capacités spéciales
 * - Gestion de l'équipement
 * - Gestion des résistances et immunités
 * - Gestion des sens et langues
 */
class Monstre
{
    private $pdo;
    
    // Propriétés du monstre
    public $id;
    public $csv_id;
    public $name;
    public $type;
    public $size;
    public $alignment;
    public $challenge_rating;
    public $hit_points;
    public $armor_class;
    public $speed;
    public $proficiency_bonus;
    public $description;
    public $actions;
    public $special_abilities;
    public $created_at;
    
    // Statistiques de base
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    
    // Compétences et capacités
    public $competences;
    public $saving_throws;
    public $damage_immunities;
    public $damage_resistances;
    public $condition_immunities;
    public $senses;
    public $languages;
    
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
     * Obtenir l'instance PDO
     * 
     * @return PDO
     */
    private function getPdo()
    {
        return $this->pdo ?: \Database::getInstance()->getPdo();
    }

    /**
     * Hydratation de l'objet avec les données
     */
    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Sauvegarder le monstre (création ou mise à jour)
     */
    public function save()
    {
        try {
            if ($this->id) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE dnd_monsters 
                    SET csv_id = ?, name = ?, type = ?, size = ?, alignment = ?, 
                        challenge_rating = ?, hit_points = ?, armor_class = ?, speed = ?, 
                        proficiency_bonus = ?, description = ?, actions = ?, special_abilities = ?,
                        strength = ?, dexterity = ?, constitution = ?, intelligence = ?, 
                        wisdom = ?, charisma = ?, competences = ?, saving_throws = ?,
                        damage_immunities = ?, damage_resistances = ?, condition_immunities = ?,
                        senses = ?, languages = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->csv_id, $this->name, $this->type, $this->size, $this->alignment,
                    $this->challenge_rating, $this->hit_points, $this->armor_class, $this->speed,
                    $this->proficiency_bonus, $this->description, $this->actions, $this->special_abilities,
                    $this->strength, $this->dexterity, $this->constitution, $this->intelligence,
                    $this->wisdom, $this->charisma, $this->competences, $this->saving_throws,
                    $this->damage_immunities, $this->damage_resistances, $this->condition_immunities,
                    $this->senses, $this->languages, $this->id
                ]);
            } else {
                // Création
                $stmt = $this->pdo->prepare("
                    INSERT INTO dnd_monsters (csv_id, name, type, size, alignment, challenge_rating, 
                        hit_points, armor_class, speed, proficiency_bonus, description, actions, 
                        special_abilities, strength, dexterity, constitution, intelligence, wisdom, 
                        charisma, competences, saving_throws, damage_immunities, damage_resistances, 
                        condition_immunities, senses, languages, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $this->csv_id, $this->name, $this->type, $this->size, $this->alignment,
                    $this->challenge_rating, $this->hit_points, $this->armor_class, $this->speed,
                    $this->proficiency_bonus, $this->description, $this->actions, $this->special_abilities,
                    $this->strength, $this->dexterity, $this->constitution, $this->intelligence,
                    $this->wisdom, $this->charisma, $this->competences, $this->saving_throws,
                    $this->damage_immunities, $this->damage_resistances, $this->condition_immunities,
                    $this->senses, $this->languages
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le monstre
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Charger un monstre par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM dnd_monsters WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du monstre: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Charger un monstre par son CSV ID
     */
    public static function findByCsvId($csvId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM dnd_monsters WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du monstre par CSV ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les monstres par type
     */
    public static function findByType($type, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE type LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $type . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les monstres par taille
     */
    public static function findBySize($size, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE size = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$size]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par taille: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver tous les monstres par challenge rating
     */
    public static function findByChallengeRating($cr, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE challenge_rating = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$cr]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par CR: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Rechercher des monstres par nom
     */
    public static function searchByName($name, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM dnd_monsters 
                WHERE name LIKE ? 
                ORDER BY name ASC
            ");
            $stmt->execute(['%' . $name . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des monstres par nom: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir tous les monstres
     */
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->query("SELECT * FROM dnd_monsters ORDER BY name ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monstres = [];
            foreach ($results as $data) {
                $monstres[] = new self($pdo, $data);
            }
            return $monstres;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les monstres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir le modificateur d'une caractéristique
     */
    public function getAbilityModifier($ability)
    {
        $value = $this->$ability ?? 10;
        return floor(($value - 10) / 2);
    }
    
    /**
     * Obtenir le modificateur de force
     */
    public function getStrengthModifier()
    {
        return $this->getAbilityModifier('strength');
    }
    
    /**
     * Obtenir le modificateur de dextérité
     */
    public function getDexterityModifier()
    {
        return $this->getAbilityModifier('dexterity');
    }
    
    /**
     * Obtenir le modificateur de constitution
     */
    public function getConstitutionModifier()
    {
        return $this->getAbilityModifier('constitution');
    }
    
    /**
     * Obtenir le modificateur d'intelligence
     */
    public function getIntelligenceModifier()
    {
        return $this->getAbilityModifier('intelligence');
    }
    
    /**
     * Obtenir le modificateur de sagesse
     */
    public function getWisdomModifier()
    {
        return $this->getAbilityModifier('wisdom');
    }
    
    /**
     * Obtenir le modificateur de charisme
     */
    public function getCharismaModifier()
    {
        return $this->getAbilityModifier('charisma');
    }
    
    /**
     * Obtenir le nom du monstre
     */
    public function getName()
    {
        return $this->name ?: '';
    }
    
    /**
     * Obtenir le type du monstre
     */
    public function getType()
    {
        return $this->type ?: '';
    }
    
    /**
     * Obtenir la taille du monstre
     */
    public function getSize()
    {
        return $this->size ?: '';
    }
    
    /**
     * Obtenir l'alignement du monstre
     */
    public function getAlignment()
    {
        return $this->alignment ?: '';
    }
    
    /**
     * Obtenir le challenge rating
     */
    public function getChallengeRating()
    {
        return $this->challenge_rating ?: '';
    }
    
    /**
     * Obtenir les points de vie
     */
    public function getHitPoints()
    {
        return $this->hit_points ?: 0;
    }
    
    /**
     * Obtenir la classe d'armure
     */
    public function getArmorClass()
    {
        return $this->armor_class ?: 0;
    }
    
    /**
     * Obtenir la vitesse
     */
    public function getSpeed()
    {
        return $this->speed ?: '';
    }
    
    /**
     * Obtenir le bonus de maîtrise
     */
    public function getProficiencyBonus()
    {
        return $this->proficiency_bonus ?: 0;
    }
    
    /**
     * Obtenir la description
     */
    public function getDescription()
    {
        return $this->description ?: '';
    }
    
    /**
     * Obtenir les actions (propriété)
     */
    public function getActionsProperty()
    {
        return $this->actions ?: '';
    }
    
    /**
     * Obtenir les capacités spéciales
     */
    public function getSpecialAbilities()
    {
        return $this->special_abilities ?: '';
    }
    
    /**
     * Obtenir les résistances aux dégâts
     */
    public function getDamageResistances()
    {
        return $this->damage_resistances ?: '';
    }
    
    /**
     * Obtenir les immunités aux dégâts
     */
    public function getDamageImmunities()
    {
        return $this->damage_immunities ?: '';
    }
    
    /**
     * Obtenir les immunités aux conditions
     */
    public function getConditionImmunities()
    {
        return $this->condition_immunities ?: '';
    }
    
    /**
     * Obtenir les sens
     */
    public function getSenses()
    {
        return $this->senses ?: '';
    }
    
    /**
     * Obtenir les langues
     */
    public function getLanguages()
    {
        return $this->languages ?: '';
    }
    
    /**
     * Récupère les actions du monstre
     * 
     * @return array Liste des actions
     */
    public function getActions()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT name, description FROM monster_actions WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des actions: " . $e->getMessage());
        }
    }

    /**
     * Récupère les actions légendaires du monstre
     * 
     * @return array Liste des actions légendaires
     */
    public function getLegendaryActions()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT name, description FROM monster_legendary_actions WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des actions légendaires: " . $e->getMessage());
        }
    }

    /**
     * Récupère les attaques spéciales du monstre
     * 
     * @return array Liste des attaques spéciales
     */
    public function getSpecialAttacks()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT name, description FROM monster_special_attacks WHERE monster_id = ? ORDER BY name");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des attaques spéciales: " . $e->getMessage());
        }
    }

    /**
     * Récupère les sorts du monstre
     * 
     * @return array Liste des sorts
     */
    public function getSpells()
    {
        if ($this->id === null) {
            return [];
        }

        try {
            $pdo = $this->getPdo();
            // Essayer d'abord avec la structure attendue
            try {
                $stmt = $pdo->prepare("
                    SELECT s.*, ms.description as monster_spell_description
                    FROM monster_spells ms
                    JOIN spells s ON ms.spell_id = s.id
                    WHERE ms.monster_id = ?
                    ORDER BY s.level, s.name
                ");
                $stmt->execute([$this->id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Si la colonne spell_id n'existe pas, essayer une approche alternative
                $stmt = $pdo->prepare("
                    SELECT name, description
                    FROM monster_spells
                    WHERE monster_id = ?
                    ORDER BY name
                ");
                $stmt->execute([$this->id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sorts: " . $e->getMessage());
        }
    }

    /**
     * Récupère les informations d'un monstre dans une pièce spécifique
     * 
     * @param int $npcId ID du NPC monstre dans la pièce
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array|null Informations du monstre dans la pièce ou null
     */
    public static function getMonsterInPlace($npcId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT sn.*, m.id as monster_db_id, m.name as monster_name, m.type, m.size, m.challenge_rating, 
                       m.hit_points as max_hit_points, m.armor_class, m.csv_id,
                       m.strength, m.dexterity, m.constitution, m.intelligence, m.wisdom, m.charisma, 
                       m.competences, m.saving_throws, m.damage_immunities, m.damage_resistances, 
                       m.condition_immunities, m.senses, m.languages, s.id as place_id
                FROM place_npcs sn 
                JOIN dnd_monsters m ON sn.monster_id = m.id 
                JOIN places s ON sn.place_id = s.id
                WHERE sn.id = ? AND sn.monster_id IS NOT NULL
            ");
            $stmt->execute([$npcId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du monstre dans la pièce: " . $e->getMessage());
        }
    }

    /**
     * Récupère l'équipement magique d'un monstre (excluant les poisons)
     * 
     * @param int $npcId ID du NPC monstre dans la pièce
     * @param int $campaignId ID de la campagne
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste de l'équipement magique
     */
    public static function getMonsterMagicalEquipment($npcId, $campaignId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT me.*, mi.nom as magical_item_nom, mi.type as magical_item_type, 
                       mi.description as magical_item_description, mi.source as magical_item_source
                FROM monster_equipment me
                LEFT JOIN magical_items mi ON me.magical_item_id = mi.csv_id
                WHERE me.monster_id = ? AND me.campaign_id = ? 
                AND me.magical_item_id NOT IN (SELECT csv_id FROM poisons)
                ORDER BY me.obtained_at DESC
            ");
            $stmt->execute([$npcId, $campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'équipement magique: " . $e->getMessage());
        }
    }

    /**
     * Récupère les poisons d'un monstre
     * 
     * @param int $npcId ID du NPC monstre dans la pièce
     * @param int $campaignId ID de la campagne
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des poisons
     */
    public static function getMonsterPoisons($npcId, $campaignId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT me.*, p.nom as poison_nom, p.type as poison_type, 
                       p.description as poison_description, p.source as poison_source
                FROM monster_equipment me
                JOIN poisons p ON me.magical_item_id = p.csv_id
                WHERE me.monster_id = ? AND me.campaign_id = ?
                ORDER BY me.obtained_at DESC
            ");
            $stmt->execute([$npcId, $campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des poisons: " . $e->getMessage());
        }
    }

    /**
     * Récupérer un équipement spécifique d'un monstre
     * 
     * @param int $itemId ID de l'équipement
     * @param int $npcId ID du PNJ monstre dans la pièce
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array|null Données de l'équipement ou null si non trouvé
     * @throws Exception En cas d'erreur
     */
    public static function getMonsterEquipmentById($itemId, $npcId, $campaignId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT * FROM monster_equipment WHERE id = ? AND monster_id = ? AND campaign_id = ?");
            $stmt->execute([$itemId, $npcId, $campaignId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
        }
    }

    /**
     * Récupérer les informations d'un monstre dans une pièce
     * 
     * @param int $npcId ID du PNJ monstre dans la pièce
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array|null Données du monstre ou null si non trouvé
     * @throws Exception En cas d'erreur
     */
    public static function getMonsterInfoInPlace($npcId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT sn.name FROM place_npcs sn WHERE sn.id = ?");
            $stmt->execute([$npcId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des informations du monstre: " . $e->getMessage());
        }
    }

    /**
     * Ajouter un équipement à un monstre
     * 
     * @param int $monsterId ID du monstre
     * @param int $campaignId ID de la campagne
     * @param array $equipmentData Données de l'équipement
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'ajout
     * @throws Exception En cas d'erreur
     */
    public static function addMonsterEquipment($monsterId, $campaignId, $equipmentData, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("INSERT INTO monster_equipment (monster_id, campaign_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $monsterId,
                $campaignId,
                $equipmentData['magical_item_id'],
                $equipmentData['item_name'],
                $equipmentData['item_type'],
                $equipmentData['item_description'],
                $equipmentData['item_source'],
                $equipmentData['quantity'],
                $equipmentData['equipped'] ?? 0,
                $equipmentData['notes'],
                $equipmentData['obtained_from']
            ]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de l'équipement: " . $e->getMessage());
        }
    }

    /**
     * Supprimer un équipement d'un monstre
     * 
     * @param int $itemId ID de l'équipement
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de la suppression
     * @throws Exception En cas d'erreur
     */
    public static function deleteMonsterEquipment($itemId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM monster_equipment WHERE id = ?");
            $stmt->execute([$itemId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de l'équipement: " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les points de vie actuels d'un monstre dans une pièce
     * 
     * @param int $npcId ID du PNJ monstre dans la pièce
     * @param int $currentHp Nouveaux points de vie actuels
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de la mise à jour
     * @throws Exception En cas d'erreur
     */
    public static function updateCurrentHitPoints($npcId, $currentHp, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("UPDATE place_npcs SET current_hit_points = ? WHERE id = ?");
            $stmt->execute([$currentHp, $npcId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour des points de vie: " . $e->getMessage());
        }
    }

    /**
     * Convertir l'objet en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'csv_id' => $this->csv_id,
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'alignment' => $this->alignment,
            'challenge_rating' => $this->challenge_rating,
            'hit_points' => $this->hit_points,
            'armor_class' => $this->armor_class,
            'speed' => $this->speed,
            'proficiency_bonus' => $this->proficiency_bonus,
            'description' => $this->description,
            'actions' => $this->actions,
            'special_abilities' => $this->special_abilities,
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma,
            'competences' => $this->competences,
            'saving_throws' => $this->saving_throws,
            'damage_immunities' => $this->damage_immunities,
            'damage_resistances' => $this->damage_resistances,
            'condition_immunities' => $this->condition_immunities,
            'senses' => $this->senses,
            'languages' => $this->languages,
            'created_at' => $this->created_at
        ];
    }
}
