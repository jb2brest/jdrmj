<?php

/**
 * Classe NPC - Gestion des Personnages Non-Joueurs
 * 
 * Cette classe hérite de Character et gère tous les aspects des NPCs :
 * - Données de base des NPCs (table npcs)
 * - Gestion de la visibilité et identification dans les lieux
 * - Gestion de l'équipement spécifique aux NPCs
 * - Gestion des lieux d'apparition
 * - Gestion des relations avec les campagnes
 */
class NPC extends Character
{
    // Propriétés spécifiques aux NPCs
    public $created_by;
    public $world_id;
    public $location_id;
    public $is_active;
    public $place_id;
    public $is_visible;
    public $is_identified;
    public $description;
    public $npc_character_id; // Référence vers un personnage de joueur si c'est un NPC basé sur un personnage
    
    /**
     * Constructeur de la classe NPC
     * 
     * @param array $data Données du NPC
     * @param PDO $pdo Instance PDO (optionnelle)
     */
    public function __construct($data = [], PDO $pdo = null)
    {
        // Appeler le constructeur parent
        parent::__construct($data, $pdo);
        
        // Initialiser les propriétés spécifiques aux NPCs
        $this->created_by = $data['created_by'] ?? null;
        $this->world_id = $data['world_id'] ?? null;
        $this->location_id = $data['location_id'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->place_id = $data['place_id'] ?? null;
        $this->is_visible = $data['is_visible'] ?? true;
        $this->is_identified = $data['is_identified'] ?? false;
        $this->description = $data['description'] ?? null;
        $this->npc_character_id = $data['npc_character_id'] ?? null;
    }
    
    /**
     * Hydrate l'objet avec les données de la base
     * 
     * @param array $data Données à hydrater
     */
    protected function hydrate($data)
    {
        // Appeler la méthode parent
        parent::hydrate($data);
        
        // Hydrater les propriétés spécifiques aux NPCs
        $this->created_by = $data['created_by'] ?? null;
        $this->world_id = $data['world_id'] ?? null;
        $this->location_id = $data['location_id'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->place_id = $data['place_id'] ?? null;
        $this->is_visible = $data['is_visible'] ?? true;
        $this->is_identified = $data['is_identified'] ?? false;
        $this->description = $data['description'] ?? null;
        $this->npc_character_id = $data['npc_character_id'] ?? null;
    }
    
    /**
     * Sauvegarder le NPC en base de données
     * 
     * @return bool Succès de l'opération
     */
    public function save()
    {
        if ($this->id) {
            return $this->update();
        } else {
            return $this->create();
        }
    }
    
    /**
     * Créer un nouveau NPC en base de données
     * 
     * @return bool Succès de l'opération
     */
    public function create()
    {
        try {
            $sql = "INSERT INTO npcs (
                name, class_id, race_id, background_id, archetype_id, level, experience,
                strength, dexterity, constitution, intelligence, wisdom, charisma,
                hit_points, armor_class, speed, alignment, age, height, weight,
                eyes, skin, hair, backstory, personality_traits, ideals, bonds, flaws,
                starting_equipment, gold, spells, skills, languages, profile_photo,
                created_by, world_id, location_id, is_active
            ) VALUES (
                :name, :class_id, :race_id, :background_id, :archetype_id, :level, :experience,
                :strength, :dexterity, :constitution, :intelligence, :wisdom, :charisma,
                :hit_points, :armor_class, :speed, :alignment, :age, :height, :weight,
                :eyes, :skin, :hair, :backstory, :personality_traits, :ideals, :bonds, :flaws,
                :starting_equipment, :gold, :spells, :skills, :languages, :profile_photo,
                :created_by, :world_id, :location_id, :is_active
            )";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':name' => $this->name,
                ':class_id' => $this->class_id,
                ':race_id' => $this->race_id,
                ':background_id' => $this->background_id,
                ':archetype_id' => $this->archetype_id,
                ':level' => $this->level,
                ':experience' => $this->experience,
                ':strength' => $this->strength,
                ':dexterity' => $this->dexterity,
                ':constitution' => $this->constitution,
                ':intelligence' => $this->intelligence,
                ':wisdom' => $this->wisdom,
                ':charisma' => $this->charisma,
                ':hit_points' => $this->hit_points,
                ':armor_class' => $this->armor_class,
                ':speed' => $this->speed,
                ':alignment' => $this->alignment,
                ':age' => $this->age,
                ':height' => $this->height,
                ':weight' => $this->weight,
                ':eyes' => $this->eyes,
                ':skin' => $this->skin,
                ':hair' => $this->hair,
                ':backstory' => $this->backstory,
                ':personality_traits' => $this->personality_traits,
                ':ideals' => $this->ideals,
                ':bonds' => $this->bonds,
                ':flaws' => $this->flaws,
                ':starting_equipment' => $this->starting_equipment,
                ':gold' => $this->gold,
                ':spells' => $this->spells,
                ':skills' => $this->skills,
                ':languages' => $this->languages,
                ':profile_photo' => $this->profile_photo,
                ':created_by' => $this->created_by,
                ':world_id' => $this->world_id,
                ':location_id' => $this->location_id,
                ':is_active' => $this->is_active
            ]);

            if ($result) {
                $this->id = $this->pdo->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour le NPC en base de données
     * 
     * @return bool Succès de l'opération
     */
    public function update()
    {
        try {
            $sql = "UPDATE npcs SET 
                name = :name, class_id = :class_id, race_id = :race_id, background_id = :background_id,
                archetype_id = :archetype_id, level = :level, experience = :experience,
                strength = :strength, dexterity = :dexterity, constitution = :constitution,
                intelligence = :intelligence, wisdom = :wisdom, charisma = :charisma,
                hit_points = :hit_points, armor_class = :armor_class, speed = :speed,
                alignment = :alignment, age = :age, height = :height, weight = :weight,
                eyes = :eyes, skin = :skin, hair = :hair, backstory = :backstory,
                personality_traits = :personality_traits, ideals = :ideals, bonds = :bonds, flaws = :flaws,
                starting_equipment = :starting_equipment, gold = :gold, spells = :spells,
                skills = :skills, languages = :languages, profile_photo = :profile_photo,
                world_id = :world_id, location_id = :location_id, is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $this->id,
                ':name' => $this->name,
                ':class_id' => $this->class_id,
                ':race_id' => $this->race_id,
                ':background_id' => $this->background_id,
                ':archetype_id' => $this->archetype_id,
                ':level' => $this->level,
                ':experience' => $this->experience,
                ':strength' => $this->strength,
                ':dexterity' => $this->dexterity,
                ':constitution' => $this->constitution,
                ':intelligence' => $this->intelligence,
                ':wisdom' => $this->wisdom,
                ':charisma' => $this->charisma,
                ':hit_points' => $this->hit_points,
                ':armor_class' => $this->armor_class,
                ':speed' => $this->speed,
                ':alignment' => $this->alignment,
                ':age' => $this->age,
                ':height' => $this->height,
                ':weight' => $this->weight,
                ':eyes' => $this->eyes,
                ':skin' => $this->skin,
                ':hair' => $this->hair,
                ':backstory' => $this->backstory,
                ':personality_traits' => $this->personality_traits,
                ':ideals' => $this->ideals,
                ':bonds' => $this->bonds,
                ':flaws' => $this->flaws,
                ':starting_equipment' => $this->starting_equipment,
                ':gold' => $this->gold,
                ':spells' => $this->spells,
                ':skills' => $this->skills,
                ':languages' => $this->languages,
                ':profile_photo' => $this->profile_photo,
                ':world_id' => $this->world_id,
                ':location_id' => $this->location_id,
                ':is_active' => $this->is_active
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le NPC de la base de données
     * 
     * @return bool Succès de l'opération
     */
    public function delete()
    {
        try {
            $sql = "DELETE FROM npcs WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $this->id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un NPC par son ID
     * 
     * @param int $id ID du NPC
     * @param PDO $pdo Instance PDO
     * @return NPC|null Instance du NPC ou null
     */
    public static function findById($id, PDO $pdo = null)
    {
        if (!$pdo) {
            $pdo = \Database::getInstance()->getPdo();
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM npcs WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($data, $pdo);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du NPC: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver des NPCs par lieu
     * 
     * @param int $placeId ID du lieu
     * @param PDO $pdo Instance PDO
     * @return array Liste des NPCs
     */
    public static function findByPlaceId($placeId, PDO $pdo = null)
    {
        if (!$pdo) {
            $pdo = \Database::getInstance()->getPdo();
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT n.*, p.name as place_name 
                FROM npcs n
                LEFT JOIN places p ON n.location_id = p.id
                WHERE n.location_id = ? AND n.is_active = 1
                ORDER BY n.name
            ");
            $stmt->execute([$placeId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $npcs = [];
            foreach ($results as $data) {
                $npcs[] = new self($data, $pdo);
            }
            return $npcs;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des NPCs par lieu: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les informations d'un NPC dans un lieu
     * 
     * @param int $npcId ID du NPC
     * @param PDO $pdo Instance PDO
     * @return array|null Informations du NPC ou null
     */
    public static function getNpcInfoInPlace($npcId, $pdo = null)
    {
        if (!$pdo) {
            $pdo = \Database::getInstance()->getPdo();
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT pn.*, n.name as npc_name, n.level, n.class_id, n.race_id
                FROM place_npcs pn
                LEFT JOIN npcs n ON pn.npc_character_id = n.id
                WHERE pn.id = ?
            ");
            $stmt->execute([$npcId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations du NPC: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajouter automatiquement les capacités de base à un NPC
     * 
     * @return bool Succès de l'opération
     */
    public function addBaseCapabilities()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Récupérer les capacités de la classe
            $stmt = $pdo->prepare("
                SELECT c.name as class_name, c.capabilities as class_capabilities
                FROM classes c
                WHERE c.id = ?
            ");
            $stmt->execute([$this->class_id]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$class) {
                return false;
            }
            
            $addedCapabilities = 0;
            
            // Ajouter les capacités de classe
            if (!empty($class['class_capabilities'])) {
                $capabilities = json_decode($class['class_capabilities'], true);
                if ($capabilities) {
                    foreach ($capabilities as $capability) {
                        // Vérifier si la capacité n'est pas déjà assignée
                        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_capabilities WHERE npc_id = ? AND capability_name = ?");
                        $checkStmt->execute([$this->id, $capability]);
                        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($exists['count'] == 0) {
                            $insertStmt = $pdo->prepare("
                                INSERT INTO npc_capabilities (npc_id, capability_name, is_active, learned_at)
                                VALUES (?, ?, 1, NOW())
                            ");
                            $insertStmt->execute([$this->id, $capability]);
                            $addedCapabilities++;
                        }
                    }
                }
            }
            
            error_log("Debug NPC::addBaseCapabilities - Added " . $addedCapabilities . " capabilities to NPC " . $this->id);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des capacités de base du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter automatiquement les langues de base à un NPC
     * 
     * @return bool Succès de l'opération
     */
    public function addBaseLanguages()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Récupérer les langues de la race
            $stmt = $pdo->prepare("
                SELECT r.name as race_name, r.languages as race_languages
                FROM races r
                WHERE r.id = ?
            ");
            $stmt->execute([$this->race_id]);
            $race = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$race) {
                return false;
            }
            
            $addedLanguages = 0;
            
            // Ajouter les langues de race
            if (!empty($race['race_languages'])) {
                $languages = json_decode($race['race_languages'], true);
                if ($languages) {
                    foreach ($languages as $language) {
                        // Vérifier si la langue n'est pas déjà assignée
                        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_languages WHERE npc_id = ? AND language_name = ?");
                        $checkStmt->execute([$this->id, $language]);
                        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($exists['count'] == 0) {
                            $insertStmt = $pdo->prepare("
                                INSERT INTO npc_languages (npc_id, language_name, is_active, learned_at)
                                VALUES (?, ?, 1, NOW())
                            ");
                            $insertStmt->execute([$this->id, $language]);
                            $addedLanguages++;
                        }
                    }
                }
            }
            
            error_log("Debug NPC::addBaseLanguages - Added " . $addedLanguages . " languages to NPC " . $this->id);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des langues de base du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter automatiquement les compétences de base à un NPC
     * 
     * @return bool Succès de l'opération
     */
    public function addBaseSkills()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Récupérer les données du NPC
            $stmt = $pdo->prepare("
                SELECT n.*, c.name as class_name, r.name as race_name, b.name as background_name
                FROM npcs n
                LEFT JOIN classes c ON n.class_id = c.id
                LEFT JOIN races r ON n.race_id = r.id
                LEFT JOIN backgrounds b ON n.background_id = b.id
                WHERE n.id = ?
            ");
            $stmt->execute([$this->id]);
            $npc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$npc) {
                return false;
            }
            
            $addedSkills = 0;
            
            // Sélectionner automatiquement les compétences selon la classe
            require_once 'classes/NPCSkills.php';
            $selectedSkills = NPCSkills::selectSkillsForNPC($npc['class_name'], $this->id);
            
            if (!empty($selectedSkills)) {
                // Ajouter les compétences sélectionnées
                foreach ($selectedSkills as $skillName) {
                    // Vérifier si la compétence n'est pas déjà assignée
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_skills WHERE npc_id = ? AND skill_name = ?");
                    $checkStmt->execute([$this->id, $skillName]);
                    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($exists['count'] == 0) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO npc_skills (npc_id, skill_name, proficiency_bonus, is_proficient, is_expertise, is_active, learned_at)
                            VALUES (?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $insertStmt->execute([
                            $this->id, 
                            $skillName, 
                            2, // Bonus de maîtrise de base
                            1, // Maîtrisé
                            0  // Pas d'expertise
                        ]);
                        $addedSkills++;
                    }
                }
            }
            
            error_log("Debug NPC::addBaseSkills - Added " . $addedSkills . " skills to NPC " . $this->id);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des compétences de base du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Détecter le type d'équipement basé sur le nom
     * 
     * @param string $itemName Nom de l'équipement
     * @return string Type d'équipement détecté
     */
    public static function detectEquipmentType($itemName)
    {
        $itemName = mb_strtolower(trim($itemName), 'UTF-8');

        // Vérifier d'abord les termes les plus spécifiques

        // Armes spécifiques
        $specificWeapons = ['épée longue', 'longsword', 'épée courte', 'shortsword', 'hache de guerre', 'war axe',
                           'masse de guerre', 'war mace', 'arc long', 'longbow', 'arc court', 'shortbow',
                           'bâton de guerre', 'war staff'];

        foreach ($specificWeapons as $weapon) {
            if (strpos($itemName, $weapon) !== false) {
                return 'weapon';
            }
        }

        // Armures spécifiques
        $specificArmors = ['armure de cuir', 'leather armor', 'armure d\'écailles', 'scale armor',
                         'armure de chaînes', 'chain armor', 'armure de plaques', 'plate armor',
                         'cotte de mailles', 'chain mail', 'armure de cuir cloutée', 'studded leather'];

        foreach ($specificArmors as $armor) {
            if (strpos($itemName, $armor) !== false) {
                return 'armor';
            }
        }

        // Puis vérifier les termes génériques

        // Armes génériques
        $weapons = ['épée', 'sword', 'rapière', 'rapier', 'cimeterre', 'scimitar', 'hache', 'axe',
                   'masse', 'mace', 'dague', 'dagger', 'arc', 'bow', 'javeline', 'javelin',
                   'lance', 'spear', 'bâton', 'staff', 'fléau', 'flail', 'morgenstern', 'morningstar', 'trident'];

        foreach ($weapons as $weapon) {
            if (strpos($itemName, $weapon) !== false) {
                return 'weapon';
            }
        }

        // Armures génériques
        $armors = ['armure', 'armor'];

        foreach ($armors as $armor) {
            if (strpos($itemName, $armor) !== false) {
                return 'armor';
            }
        }

        // Boucliers
        $shields = ['bouclier', 'shield', 'écu', 'buckler'];

        foreach ($shields as $shield) {
            if (strpos($itemName, $shield) !== false) {
                return 'shield';
            }
        }

        // Objets magiques
        $magical = ['bague', 'ring', 'amulette', 'amulet', 'pendentif', 'potion', 'potion de', 'scroll', 'parchemin'];

        foreach ($magical as $magic) {
            if (strpos($itemName, $magic) !== false) {
                return 'magical_item';
            }
        }

        // Par défaut, considérer comme outil
        return 'outil';
    }
    
    /**
     * Ajouter l'équipement de départ à un NPC
     * 
     * @param string $equipmentString Chaîne d'équipement
     * @param int $gold Montant d'or
     * @return bool Succès de l'opération
     */
    public function addStartingEquipment($equipmentString, $gold = 0)
    {
        $pdo = \Database::getInstance()->getPdo();

        try {
            // Ajouter l'or
            if ($gold > 0) {
                $stmt = $pdo->prepare("UPDATE npcs SET gold = ? WHERE id = ?");
                $stmt->execute([$gold, $this->id]);
            }

            // Ajouter l'équipement de départ
            if (!empty($equipmentString)) {
                $equipmentItems = explode(', ', $equipmentString);
                $addedItems = 0;

                foreach ($equipmentItems as $item) {
                    $item = trim($item);
                    if (!empty($item)) {
                        // Détecter le type d'équipement
                        $objectType = self::detectEquipmentType($item);

                        // Créer un objet dans la table items
                        $stmt = $pdo->prepare("
                            INSERT INTO items (
                                display_name, description, object_type, type_precis,
                                owner_type, owner_id, place_id, is_visible, is_identified,
                                created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");

                        $stmt->execute([
                            $item,
                            "Équipement de départ",
                            $objectType,
                            "Équipement",
                            'npc',
                            $this->id,
                            null,
                            1,
                            1
                        ]);
                        $addedItems++;
                    }
                }

                error_log("Debug NPC::addStartingEquipment - Added " . $addedItems . " items to NPC " . $this->id);
            }

            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'équipement de départ du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les informations d'équipement d'un NPC avec détails
     * 
     * @param int $equipmentId ID de l'équipement
     * @param int $characterId ID du personnage
     * @param PDO $pdo Instance PDO
     * @return array|null Informations de l'équipement ou null
     */
    public static function getNpcEquipmentWithDetails($equipmentId, $characterId, $pdo = null)
    {
        if (!$pdo) {
            $pdo = \Database::getInstance()->getPdo();
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT ne.*, mi.name, mi.description, mi.type, mi.rarity, mi.attunement_required
                FROM npc_equipment ne
                LEFT JOIN magical_items mi ON ne.magical_item_id = mi.id
                WHERE ne.id = ? AND ne.character_id = ?
            ");
            $stmt->execute([$equipmentId, $characterId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement du NPC: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajouter un équipement à un NPC
     * 
     * @param int $npcId ID du NPC
     * @param int $placeId ID du lieu
     * @param array $equipmentData Données de l'équipement
     * @return bool Succès de l'opération
     */
    public static function addEquipmentToNpc($npcId, $placeId, $equipmentData)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO npc_equipment (character_id, place_id, magical_item_id, item_name, is_equipped, added_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $npcId,
                $placeId,
                $equipmentData['magical_item_id'],
                $equipmentData['item_name'],
                $equipmentData['is_equipped'] ?? 0
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'équipement au NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un équipement d'un NPC
     * 
     * @param int $equipmentId ID de l'équipement
     * @return bool Succès de l'opération
     */
    public static function removeEquipmentFromNpc($equipmentId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ?");
            return $stmt->execute([$equipmentId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'équipement du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'équipement d'un NPC par personnage
     * 
     * @param int $characterId ID du personnage
     * @return array Liste de l'équipement
     */
    public static function getNpcEquipmentByCharacter($characterId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT ne.*, mi.name, mi.description, mi.type, mi.rarity
                FROM npc_equipment ne
                LEFT JOIN magical_items mi ON ne.magical_item_id = mi.id
                WHERE ne.character_id = ?
                ORDER BY ne.added_at DESC
            ");
            $stmt->execute([$characterId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement du NPC: " . $e->getMessage());
            return [];
        }
    }
}
?>
