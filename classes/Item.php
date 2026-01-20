<?php

/**
 * Classe Item - Gestion des objets dans les pièces
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux objets
 * du système JDR MJ, incluant la création, gestion et manipulation des objets.
 */
class Item
{
    private $id;
    private $placeId;
    private $displayName;
    private $objectType;
    private $typePrecis;
    private $description;
    private $isIdentified;
    private $isVisible;
    private $isEquipped;
    private $positionX;
    private $positionY;
    private $isOnMap;
    private $ownerType;
    private $ownerId;
    private $poisonId;
    private $weaponId;
    private $armorId;
    private $goldCoins;
    private $silverCoins;
    private $copperCoins;
    private $letterContent;
    private $isSealed;
    private $createdAt;
    private $updatedAt;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO pour la base de données (optionnel)
     * @param array $data Données de l'objet (optionnel)
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
        $this->placeId = $data['place_id'] ?? null;
        $this->displayName = $data['display_name'] ?? null;
        $this->objectType = $data['object_type'] ?? null;
        $this->typePrecis = $data['type_precis'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->isIdentified = $data['is_identified'] ?? false;
        $this->isVisible = $data['is_visible'] ?? true;
        $this->isEquipped = $data['is_equipped'] ?? false;
        $this->positionX = $data['position_x'] ?? 0;
        $this->positionY = $data['position_y'] ?? 0;
        $this->isOnMap = $data['is_on_map'] ?? false;
        $this->ownerType = $data['owner_type'] ?? 'place';
        $this->ownerId = $data['owner_id'] ?? null;
        $this->poisonId = $data['poison_id'] ?? null;
        $this->weaponId = $data['weapon_id'] ?? null;
        $this->armorId = $data['armor_id'] ?? null;
        $this->goldCoins = $data['gold_coins'] ?? 0;
        $this->silverCoins = $data['silver_coins'] ?? 0;
        $this->copperCoins = $data['copper_coins'] ?? 0;
        $this->letterContent = $data['letter_content'] ?? null;
        $this->isSealed = $data['is_sealed'] ?? false;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    /**
     * Créer un nouvel objet
     * 
     * @param array $data Données de l'objet
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Item|false L'objet créé ou false en cas d'erreur
     */
    public static function create(array $data, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                INSERT INTO items (
                    place_id, display_name, object_type, type_precis, description,
                    is_identified, is_visible, is_equipped, position_x, position_y, is_on_map,
                    owner_type, owner_id, poison_id, weapon_id, armor_id,
                    gold_coins, silver_coins, copper_coins, letter_content, is_sealed
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['place_id'],
                $data['display_name'],
                $data['object_type'],
                $data['type_precis'] ?? null,
                $data['description'] ?? null,
                $data['is_identified'] ?? false,
                $data['is_visible'] ?? true,
                $data['is_equipped'] ?? false,
                $data['position_x'] ?? 0,
                $data['position_y'] ?? 0,
                $data['is_on_map'] ?? false,
                $data['owner_type'] ?? 'place',
                $data['owner_id'] ?? null,
                $data['poison_id'] ?? null,
                $data['weapon_id'] ?? null,
                $data['armor_id'] ?? null,
                $data['gold_coins'] ?? 0,
                $data['silver_coins'] ?? 0,
                $data['copper_coins'] ?? 0,
                $data['letter_content'] ?? null,
                $data['is_sealed'] ?? false
            ]);
            
            $objectId = $pdo->lastInsertId();
            return self::findById($objectId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'objet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un objet par son ID
     * 
     * @param int $id ID de l'objet
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Item|null L'objet trouvé ou null
     */
    public static function findById(int $id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de l'objet: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver tous les objets d'une pièce
     * 
     * @param int $placeId ID de la pièce
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des objets
     */
    public static function findByPlaceId(int $placeId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE place_id = ? ORDER BY display_name");
            $stmt->execute([$placeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $objects = [];
            foreach ($results as $data) {
                $objects[] = new self($pdo, $data);
            }
            
            return $objects;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des objets de la pièce: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouver tous les objets d'un propriétaire
     * 
     * @param string $ownerType Type de propriétaire
     * @param int $ownerId ID du propriétaire
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des objets
     */
    public static function findByOwner(string $ownerType, int $ownerId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE owner_type = ? AND owner_id = ? ORDER BY display_name");
            $stmt->execute([$ownerType, $ownerId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $objects = [];
            foreach ($results as $data) {
                $objects[] = new self($pdo, $data);
            }
            
            return $objects;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des objets du propriétaire: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour l'objet
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
                UPDATE items 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'objet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer l'objet
     * 
     * @return bool True si succès, false sinon
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'objet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Déplacer l'objet
     * 
     * @param int $x Position X
     * @param int $y Position Y
     * @param bool $onMap Si l'objet est sur la carte
     * @return bool True si succès, false sinon
     */
    public function move(int $x, int $y, bool $onMap = true)
    {
        return $this->update([
            'position_x' => $x,
            'position_y' => $y,
            'is_on_map' => $onMap
        ]);
    }

    /**
     * Changer le propriétaire de l'objet
     * 
     * @param string $ownerType Type de propriétaire
     * @param int $ownerId ID du propriétaire
     * @return bool True si succès, false sinon
     */
    public function changeOwner(string $ownerType, int $ownerId)
    {
        return $this->update([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId
        ]);
    }

    /**
     * Équiper/déséquiper l'objet
     * 
     * @param bool $equipped True pour équiper, false pour déséquiper
     * @return bool True si succès, false sinon
     */
    public function setEquipped(bool $equipped)
    {
        return $this->update(['is_equipped' => $equipped]);
    }

    /**
     * Rendre l'objet visible/invisible
     * 
     * @param bool $visible True pour visible, false pour invisible
     * @return bool True si succès, false sinon
     */
    public function setVisible(bool $visible)
    {
        return $this->update(['is_visible' => $visible]);
    }

    /**
     * Identifier/désidentifier l'objet
     * 
     * @param bool $identified True pour identifier, false pour désidentifier
     * @return bool True si succès, false sinon
     */
    public function setIdentified(bool $identified)
    {
        return $this->update(['is_identified' => $identified]);
    }

    // =====================================================
    // GETTERS
    // =====================================================

    public function getId()
    {
        return $this->id;
    }

    public function getPlaceId()
    {
        return $this->placeId;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getObjectType()
    {
        return $this->objectType;
    }

    public function getTypePrecis()
    {
        return $this->typePrecis;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getIsIdentified()
    {
        return $this->isIdentified;
    }

    public function getIsVisible()
    {
        return $this->isVisible;
    }

    public function getIsEquipped()
    {
        return $this->isEquipped;
    }

    public function getPositionX()
    {
        return $this->positionX;
    }

    public function getPositionY()
    {
        return $this->positionY;
    }

    public function getIsOnMap()
    {
        return $this->isOnMap;
    }

    public function getOwnerType()
    {
        return $this->ownerType;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function getPoisonId()
    {
        return $this->poisonId;
    }

    public function getWeaponId()
    {
        return $this->weaponId;
    }

    public function getArmorId()
    {
        return $this->armorId;
    }

    public function getGoldCoins()
    {
        return $this->goldCoins;
    }

    public function getSilverCoins()
    {
        return $this->silverCoins;
    }

    public function getCopperCoins()
    {
        return $this->copperCoins;
    }

    public function getLetterContent()
    {
        return $this->letterContent;
    }

    public function getIsSealed()
    {
        return $this->isSealed;
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
     * @return array Données de l'objet
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'place_id' => $this->placeId,
            'display_name' => $this->displayName,
            'object_type' => $this->objectType,
            'type_precis' => $this->typePrecis,
            'description' => $this->description,
            'is_identified' => $this->isIdentified,
            'is_visible' => $this->isVisible,
            'is_equipped' => $this->isEquipped,
            'position_x' => $this->positionX,
            'position_y' => $this->positionY,
            'is_on_map' => $this->isOnMap,
            'owner_type' => $this->ownerType,
            'owner_id' => $this->ownerId,
            'poison_id' => $this->poisonId,
            'weapon_id' => $this->weaponId,
            'armor_id' => $this->armorId,
            'gold_coins' => $this->goldCoins,
            'silver_coins' => $this->silverCoins,
            'copper_coins' => $this->copperCoins,
            'letter_content' => $this->letterContent,
            'is_sealed' => $this->isSealed,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Récupérer les informations d'un poison par son ID CSV
     * 
     * @param int $csvId ID CSV du poison
     * @return array|null Informations du poison ou null si non trouvé
     */
    public function getPoisonInfo($csvId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT nom, type, description, source FROM poisons WHERE csv_id = ?");
            $stmt->execute([$csvId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des informations du poison: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le label du type d'objet en français
     * 
     * @return string Label du type
     */
    public function getObjectTypeLabel()
    {
        $labels = [
            'poison' => 'Poison',
            'weapon' => 'Arme',
            'armor' => 'Armure',
            'bourse' => 'Bourse',
            'letter' => 'Lettre'
        ];
        
        return $labels[$this->objectType] ?? ucfirst($this->objectType);
    }

    /**
     * Obtenir le label du type de propriétaire en français
     * 
     * @return string Label du type de propriétaire
     */
    public function getOwnerTypeLabel()
    {
        $labels = [
            'place' => 'Pièce',
            'player' => 'Joueur',
            'npc' => 'PNJ',
            'monster' => 'Monstre'
        ];
        
        return $labels[$this->ownerType] ?? ucfirst($this->ownerType);
    }

    /**
     * Calculer la valeur totale en pièces de cuivre
     * 
     * @return int Valeur totale en pièces de cuivre
     */
    public function getTotalValueInCopper()
    {
        return ($this->goldCoins * 100) + ($this->silverCoins * 10) + $this->copperCoins;
    }

    /**
     * Obtenir la valeur formatée
     * 
     * @return string Valeur formatée
     */
    public function getFormattedValue()
    {
        $total = $this->getTotalValueInCopper();
        $gold = intval($total / 100);
        $silver = intval(($total % 100) / 10);
        $copper = $total % 10;
        
        $parts = [];
        if ($gold > 0) $parts[] = $gold . ' PO';
        if ($silver > 0) $parts[] = $silver . ' PA';
        if ($copper > 0) $parts[] = $copper . ' PC';
        
        return empty($parts) ? '0 PC' : implode(', ', $parts);
    }

    /**
     * Obtenir les compétences d'armure
     * 
     * @return array Tableau des compétences d'armure
     */
    public static function getArmorProficiencies()
    {
        return [
            'Armure légère' => 'Armure',
            'Armure intermédiaire' => 'Armure',
            'Armure lourde' => 'Armure',
            'Bouclier' => 'Armure'
        ];
    }

    /**
     * Obtenir les compétences d'armes
     * 
     * @return array Tableau des compétences d'armes
     */
    public static function getWeaponProficiencies()
    {
        return [
            'Armes courantes' => 'Arme',
            'Armes de guerre' => 'Arme',
            'Armes simples' => 'Arme',
            'Armes à distance' => 'Arme',
            'Armes de mêlée' => 'Arme',
            'Armes d\'hast' => 'Arme',
            'Armes de lancer' => 'Arme'
        ];
    }

    /**
     * Obtenir les compétences d'outils
     * 
     * @return array Tableau des compétences d'outils
     */
    public static function getToolProficiencies()
    {
        return [
            'Outils d\'artisan' => 'Outil',
            'Instruments de musique' => 'Outil',
            'Jeux' => 'Outil',
            'Véhicules' => 'Outil',
            'Outils de voleur' => 'Outil',
            'Outils de forgeron' => 'Outil',
            'Outils de charpentier' => 'Outil',
            'Outils de cuisinier' => 'Outil',
            'Outils de tanneur' => 'Outil',
            'Outils de tisserand' => 'Outil',
            'Outils de verrier' => 'Outil',
            'Outils de potier' => 'Outil',
            'Outils de cordonnier' => 'Outil',
            'Outils de bijoutier' => 'Outil',
            'Outils de calligraphe' => 'Outil',
            'Outils de cartographe' => 'Outil',
            'Outils de navigateur' => 'Outil',
            'Outils de herboriste' => 'Outil',
            'Outils d\'alchimiste' => 'Outil',
            'Outils de mécanicien' => 'Outil'
        ];
    }

    /**
     * Obtenir les armes courantes disponibles
     * 
     * @param string|null $type Type d'arme spécifique
     * @return array Tableau des armes courantes
     */
    public static function getCommonWeapons($type = null)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $whereClause = '';
        $params = [];
        
        if ($type) {
            $whereClause = 'WHERE type = ?';
            $params[] = $type;
        } else {
            $whereClause = 'WHERE type IN ("Armes courantes à distance", "Armes courantes de corps à corps")';
        }
        
        $stmt = $pdo->prepare("SELECT name, type FROM weapons $whereClause ORDER BY type, name");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les armes de guerre disponibles
     * 
     * @param string|null $type Type d'arme spécifique
     * @return array Tableau des armes de guerre
     */
    public static function getWarWeapons($type = null)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $whereClause = '';
        $params = [];
        
        if ($type) {
            $whereClause = 'WHERE type = ?';
            $params[] = $type;
        } else {
            $whereClause = 'WHERE type IN ("Armes de guerre à distance", "Armes de guerre de corps à corps")';
        }
        
        $stmt = $pdo->prepare("SELECT name, type FROM weapons $whereClause ORDER BY type, name");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les instruments de musique disponibles
     * 
     * @return array Tableau des instruments de musique
     */
    public static function getMusicalInstruments()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $stmt = $pdo->prepare("SELECT nom as name FROM Object WHERE type = 'instrument' ORDER BY nom");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Détecter les armes dans l'équipement d'un personnage
     * 
     * @param string $equipmentText Texte d'équipement à analyser
     * @return array Tableau des armes détectées
     */
    public static function detectWeaponsInEquipment($equipmentText)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $weapons = [];
        $stmt = $pdo->query("SELECT name, hands, type, damage, properties FROM weapons");
        $allWeapons = $stmt->fetchAll();
        
        foreach ($allWeapons as $weapon) {
            // Rechercher l'arme dans le texte d'équipement (insensible à la casse)
            $weaponName = mb_strtolower($weapon['name'], 'UTF-8');
            $equipmentLower = mb_strtolower($equipmentText, 'UTF-8');
            
            // Vérifier différentes variations du nom
            $patterns = [
                $weaponName, // Nom exact
                $weaponName . 's', // Pluriel simple
                $weaponName . 'es', // Pluriel en -es
                'une ' . $weaponName, // Avec article "une"
                'un ' . $weaponName, // Avec article "un"
                'deux ' . $weaponName . 's', // Avec nombre et pluriel
                'trois ' . $weaponName . 's', // Avec nombre et pluriel
                'quatre ' . $weaponName . 's', // Avec nombre et pluriel
                'cinq ' . $weaponName . 's', // Avec nombre et pluriel
            ];
            
            // Patterns spécifiques pour les pluriels français complexes
            if (strpos($weaponName, 'épée') !== false) {
                $patterns[] = 'deux épées courtes';
                $patterns[] = 'trois épées courtes';
                $patterns[] = 'quatre épées courtes';
                $patterns[] = 'cinq épées courtes';
            }
            
            $found = false;
            foreach ($patterns as $pattern) {
                if (stripos($equipmentText, $pattern) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                $weapons[] = [
                    'name' => $weapon['name'],
                    'hands' => $weapon['hands'],
                    'type' => $weapon['type'],
                    'damage' => $weapon['damage'],
                    'properties' => $weapon['properties']
                ];
            }
        }
        
        return $weapons;
    }

    /**
     * Détecter les armures dans l'équipement d'un personnage
     * 
     * @param string $equipmentText Texte d'équipement à analyser
     * @return array Tableau des armures détectées
     */
    public static function detectArmorInEquipment($equipmentText)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $armor = [];
        $stmt = $pdo->query("SELECT name, ac_formula, type FROM armor WHERE type != 'Bouclier'");
        $allArmor = $stmt->fetchAll();
        
        foreach ($allArmor as $armorItem) {
            // Rechercher l'armure dans le texte d'équipement (insensible à la casse)
            // Gérer les variations comme "armure d'écailles" vs "Écailles"
            $armorName = mb_strtolower($armorItem['name'], 'UTF-8');
            $equipmentLower = mb_strtolower($equipmentText, 'UTF-8');
            
            if (stripos($equipmentText, $armorItem['name']) !== false || 
                stripos($equipmentText, "armure d'" . $armorName) !== false ||
                stripos($equipmentText, "armure de " . $armorName) !== false ||
                stripos($equipmentText, "armure d'" . $armorItem['name']) !== false ||
                stripos($equipmentText, "armure de " . $armorItem['name']) !== false) {
                $armor[] = [
                    'name' => $armorItem['name'],
                    'ac_formula' => $armorItem['ac_formula'],
                    'type' => $armorItem['type']
                ];
            }
        }
        
        return $armor;
    }

    /**
     * Détecter les boucliers dans l'équipement d'un personnage
     * 
     * @param string $equipmentText Texte d'équipement à analyser
     * @return array Tableau des boucliers détectés
     */
    public static function detectShieldsInEquipment($equipmentText)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        $shields = [];
        $stmt = $pdo->query("SELECT name, ac_formula FROM armor WHERE type = 'Bouclier'");
        $allShields = $stmt->fetchAll();
        
        foreach ($allShields as $shield) {
            // Rechercher le bouclier dans le texte d'équipement (insensible à la casse)
            if (stripos($equipmentText, $shield['name']) !== false) {
                // Extraire le bonus de CA de la formule
                $acBonus = 2; // Par défaut
                if (preg_match('/(\d+)/', $shield['ac_formula'], $matches)) {
                    $acBonus = (int)$matches[1];
                }
                
                $shields[] = [
                    'name' => $shield['name'],
                    'ac_bonus' => $acBonus
                ];
            }
        }
        
        return $shields;
    }

    /**
     * Trouver un objet par son ID et son propriétaire
     * 
     * @param int $id ID de l'objet
     * @param string $ownerType Type de propriétaire
     * @param int $ownerId ID du propriétaire
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Item|null L'objet trouvé ou null
     */
    public static function findByIdAndOwner(int $id, string $ownerType, int $ownerId, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND owner_type = ? AND owner_id = ?");
            $stmt->execute([$id, $ownerType, $ownerId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de l'objet par ID et propriétaire: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprimer un objet par son ID (version statique)
     * 
     * @param int $id ID de l'objet
     * @param PDO $pdo Instance PDO (optionnel)
     * @return bool True si succès, false sinon
     */
    public static function deleteById(int $id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'objet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer un objet avec toutes les données étendues (pour les transferts)
     * 
     * @param array $data Données complètes de l'objet
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Item|false L'objet créé ou false en cas d'erreur
     */
    public static function createExtended(array $data, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPDO();
            
            $stmt = $pdo->prepare("
                INSERT INTO items (
                    place_id, display_name, object_type, type_precis, description,
                    is_identified, is_visible, is_equipped, position_x, position_y, is_on_map,
                    owner_type, owner_id, poison_id, weapon_id, armor_id,
                    gold_coins, silver_coins, copper_coins, letter_content, is_sealed,
                    magical_item_id, item_source, quantity, equipped_slot, notes, obtained_at, obtained_from
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['place_id'] ?? null,
                $data['display_name'],
                $data['object_type'],
                $data['type_precis'] ?? null,
                $data['description'] ?? null,
                $data['is_identified'] ?? false,
                $data['is_visible'] ?? true,
                $data['is_equipped'] ?? false,
                $data['position_x'] ?? 0,
                $data['position_y'] ?? 0,
                $data['is_on_map'] ?? false,
                $data['owner_type'] ?? 'place',
                $data['owner_id'] ?? null,
                $data['poison_id'] ?? null,
                $data['weapon_id'] ?? null,
                $data['armor_id'] ?? null,
                $data['gold_coins'] ?? 0,
                $data['silver_coins'] ?? 0,
                $data['copper_coins'] ?? 0,
                $data['letter_content'] ?? null,
                $data['is_sealed'] ?? false,
                $data['magical_item_id'] ?? null,
                $data['item_source'] ?? null,
                $data['quantity'] ?? 1,
                $data['equipped_slot'] ?? null,
                $data['notes'] ?? null,
                $data['obtained_at'] ?? date('Y-m-d H:i:s'),
                $data['obtained_from'] ?? null
            ]);
            
            $objectId = $pdo->lastInsertId();
            return self::findById($objectId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'objet étendu: " . $e->getMessage());
            return false;
        }
    }
}
