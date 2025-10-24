<?php

/**
 * Classe NPC - Gestion des Personnages Non-Joueurs
 * 
 * Cette classe gère tous les aspects des NPCs :
 * - Données de base des NPCs (table npcs)
 * - Gestion de la visibilité et identification dans les lieux
 * - Gestion de l'équipement spécifique aux NPCs
 * - Gestion des lieux d'apparition
 * - Gestion des relations avec les campagnes
 */
class NPC
{
    // Propriétés de base
    public $id;
    public $name;
    public $class_id;
    public $race_id;
    public $background_id;
    public $archetype_id;
    public $level;
    public $experience;
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    public $hit_points;
    public $armor_class;
    public $speed;
    public $alignment;
    public $age;
    public $height;
    public $weight;
    public $eyes;
    public $skin;
    public $hair;
    public $backstory;
    public $personality_traits;
    public $ideals;
    public $bonds;
    public $flaws;
    public $starting_equipment;
    public $gold;
    public $spells;
    public $skills;
    public $languages;
    public $profile_photo;
    public $created_at;
    public $updated_at;
    
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
    
    private $pdo;
    
    /**
     * Constructeur de la classe NPC
     * 
     * @param array $data Données du NPC
     * @param PDO $pdo Instance PDO (optionnelle)
     */
    public function __construct($data = [], PDO $pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getInstance()->getPdo();
        $this->hydrate($data);
    }
    
    /**
     * Hydrate l'objet avec les données de la base
     * 
     * @param array $data Données à hydrater
     */
    protected function hydrate($data)
    {
        // Propriétés de base
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->class_id = $data['class_id'] ?? null;
        $this->race_id = $data['race_id'] ?? null;
        $this->background_id = $data['background_id'] ?? null;
        $this->archetype_id = $data['archetype_id'] ?? null;
        $this->level = $data['level'] ?? 1;
        $this->experience = $data['experience'] ?? 0;
        $this->strength = $data['strength'] ?? 10;
        $this->dexterity = $data['dexterity'] ?? 10;
        $this->constitution = $data['constitution'] ?? 10;
        $this->intelligence = $data['intelligence'] ?? 10;
        $this->wisdom = $data['wisdom'] ?? 10;
        $this->charisma = $data['charisma'] ?? 10;
        $this->hit_points = $data['hit_points'] ?? 8;
        $this->armor_class = $data['armor_class'] ?? 10;
        $this->speed = $data['speed'] ?? 30;
        $this->alignment = $data['alignment'] ?? 'Neutre';
        $this->age = $data['age'] ?? null;
        $this->height = $data['height'] ?? null;
        $this->weight = $data['weight'] ?? null;
        $this->eyes = $data['eyes'] ?? null;
        $this->skin = $data['skin'] ?? null;
        $this->hair = $data['hair'] ?? null;
        $this->backstory = $data['backstory'] ?? null;
        $this->personality_traits = $data['personality_traits'] ?? null;
        $this->ideals = $data['ideals'] ?? null;
        $this->bonds = $data['bonds'] ?? null;
        $this->flaws = $data['flaws'] ?? null;
        $this->starting_equipment = $data['starting_equipment'] ?? null;
        $this->gold = $data['gold'] ?? 0;
        $this->spells = $data['spells'] ?? null;
        $this->skills = $data['skills'] ?? null;
        $this->languages = $data['languages'] ?? null;
        $this->profile_photo = $data['profile_photo'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        
        // Propriétés spécifiques aux NPCs
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
     * Convertir l'objet en tableau
     * 
     * @return array Tableau des propriétés
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'class_id' => $this->class_id,
            'race_id' => $this->race_id,
            'background_id' => $this->background_id,
            'archetype_id' => $this->archetype_id,
            'level' => $this->level,
            'experience' => $this->experience,
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma,
            'hit_points' => $this->hit_points,
            'armor_class' => $this->armor_class,
            'speed' => $this->speed,
            'alignment' => $this->alignment,
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'eyes' => $this->eyes,
            'skin' => $this->skin,
            'hair' => $this->hair,
            'backstory' => $this->backstory,
            'personality_traits' => $this->personality_traits,
            'ideals' => $this->ideals,
            'bonds' => $this->bonds,
            'flaws' => $this->flaws,
            'starting_equipment' => $this->starting_equipment,
            'gold' => $this->gold,
            'spells' => $this->spells,
            'skills' => $this->skills,
            'languages' => $this->languages,
            'profile_photo' => $this->profile_photo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'world_id' => $this->world_id,
            'location_id' => $this->location_id,
            'is_active' => $this->is_active,
            'place_id' => $this->place_id,
            'is_visible' => $this->is_visible,
            'is_identified' => $this->is_identified,
            'description' => $this->description,
            'npc_character_id' => $this->npc_character_id
        ];
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
            // Récupérer les informations de la classe
            $stmt = $pdo->prepare("
                SELECT c.name as class_name
                FROM classes c
                WHERE c.id = ?
            ");
            $stmt->execute([$this->class_id]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$class) {
                return false;
            }
            
            $addedCapabilities = 0;
            
            // Définir les capacités de base par classe (logique simplifiée)
            $classCapabilities = [
                'Barbare' => ['Rage', 'Défense sans armure'],
                'Barde' => ['Inspiration bardique', 'Magie'],
                'Clerc' => ['Magie divine', 'Sorts de clerc'],
                'Druide' => ['Magie de la nature', 'Transformation sauvage'],
                'Guerrier' => ['Style de combat', 'Second souffle'],
                'Magicien' => ['Magie d\'arcane', 'Récupération d\'arcane'],
                'Moine' => ['Arts martiaux', 'Ki'],
                'Paladin' => ['Magie divine', 'Sorts de paladin'],
                'Rôdeur' => ['Magie de la nature', 'Sorts de rôdeur'],
                'Roublard' => ['Attaque sournoise', 'Expertise'],
                'Ensorceleur' => ['Magie d\'arcane', 'Points de sorcellerie'],
                'Occultiste' => ['Magie d\'arcane', 'Pacte mystique']
            ];
            
            $className = $class['class_name'];
            $capabilities = $classCapabilities[$className] ?? [];
            
            // Ajouter les capacités de classe
            foreach ($capabilities as $capability) {
                // Vérifier si la capacité n'est pas déjà assignée
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_capabilities WHERE npc_id = ? AND notes = ?");
                $checkStmt->execute([$this->id, $capability]);
                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists['count'] == 0) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO npc_capabilities (npc_id, capability_id, is_active, notes, obtained_at)
                        VALUES (?, 1, 1, ?, NOW())
                    ");
                    $insertStmt->execute([$this->id, $capability]);
                    $addedCapabilities++;
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
    
    /**
     * Obtenir les capacités du NPC
     * 
     * @return array Liste des capacités
     */
    public function getCapabilities()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT notes as capability_name, is_active, obtained_at as learned_at
                FROM npc_capabilities
                WHERE npc_id = ? AND is_active = 1
                ORDER BY notes
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités du PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les langues du NPC
     * 
     * @return array Liste des langues
     */
    public function getNpcLanguages()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT nl.*, l.name, l.type, l.typical_races, l.writing
                FROM npc_languages nl
                LEFT JOIN languages l ON nl.language_id = l.id
                WHERE nl.npc_id = ? AND nl.is_active = 1
                ORDER BY l.name
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des langues du PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les compétences du NPC
     * 
     * @return array Liste des compétences
     */
    public function getNpcSkills()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT skill_name, proficiency_bonus, is_proficient, is_expertise
                FROM npc_skills
                WHERE npc_id = ? AND is_active = 1
                ORDER BY skill_name
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des compétences du PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les détails d'un historique par ID
     * 
     * @param int $backgroundId ID de l'historique
     * @return array|null Détails de l'historique ou null
     */
    public static function getBackgroundById($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
            $stmt->execute([$backgroundId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'historique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir le nombre maximum de rages pour une classe et un niveau
     * 
     * @param int $classId ID de la classe
     * @param int $level Niveau
     * @return int Nombre maximum de rages
     */
    public static function getMaxRages($classId, $level)
    {
        // Logique simplifiée pour les barbares
        if ($classId == 1) { // ID de la classe Barbare
            return $level >= 20 ? 999 : $level;
        }
        return 0;
    }
    
    /**
     * Obtenir l'utilisation des rages pour un NPC
     * 
     * @param int $npcId ID du NPC
     * @return array Utilisation des rages
     */
    public static function getRageUsageStatic($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT used_rages FROM npcs 
                WHERE id = ?
            ");
            $stmt->execute([$npcId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? ['used' => $result['used_rages']] : ['used' => 0];
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation des rages: " . $e->getMessage());
            return ['used' => 0];
        }
    }
    
    /**
     * Obtenir les améliorations de caractéristiques d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @return array Améliorations de caractéristiques
     */
    public static function getCharacterAbilityImprovements($npcId)
    {
        // Table npc_ability_improvements n'existe pas encore, retourner un tableau vide
        return [];
    }
    
    /**
     * Calculer les caractéristiques finales d'un NPC
     * 
     * @param array $character Données du personnage
     * @param array $abilityImprovements Améliorations de caractéristiques
     * @return array Caractéristiques finales
     */
    public static function calculateFinalAbilitiesStatic($character, $abilityImprovements)
    {
        $finalAbilities = [
            'strength' => $character['strength'],
            'dexterity' => $character['dexterity'],
            'constitution' => $character['constitution'],
            'intelligence' => $character['intelligence'],
            'wisdom' => $character['wisdom'],
            'charisma' => $character['charisma']
        ];
        
        // Ajouter les améliorations
        foreach ($abilityImprovements as $improvement) {
            $ability = $improvement['ability'];
            if (isset($finalAbilities[$ability])) {
                $finalAbilities[$ability] += $improvement['improvement'];
            }
        }
        
        return $finalAbilities;
    }
    
    /**
     * Obtenir les points d'amélioration restants
     * 
     * @param int $level Niveau
     * @param array $abilityImprovements Améliorations existantes
     * @return int Points restants
     */
    public static function getRemainingAbilityPoints($level, $abilityImprovements)
    {
        $totalPoints = 0;
        $usedPoints = 0;
        
        // Calculer les points disponibles selon le niveau
        for ($i = 1; $i <= $level; $i++) {
            if ($i % 4 == 0) {
                $totalPoints += 2; // Points d'amélioration tous les 4 niveaux
            }
        }
        
        // Calculer les points utilisés
        foreach ($abilityImprovements as $improvement) {
            $usedPoints += $improvement['improvement'];
        }
        
        return max(0, $totalPoints - $usedPoints);
    }
    
    /**
     * Synchroniser l'équipement de base vers la table items
     * 
     * @param int $npcId ID du NPC
     * @return bool Succès de l'opération
     */
    public static function syncBaseEquipmentToCharacterEquipment($npcId)
    {
        // Cette méthode peut être vide pour les NPCs car ils utilisent déjà la table items
        return true;
    }
    
    /**
     * Calculer les attaques d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @param array $character Données du personnage
     * @return array Attaques calculées
     */
    public static function calculateCharacterAttacks($npcId, $character)
    {
        // Logique simplifiée pour les attaques
        $attacks = [];
        
        // Attaque de base (coup de poing)
        $attacks[] = [
            'name' => 'Coup de poing',
            'bonus' => floor(($character['strength'] - 10) / 2),
            'damage' => '1d4 + ' . floor(($character['strength'] - 10) / 2),
            'type' => 'Corps à corps'
        ];
        
        return $attacks;
    }
    
    /**
     * Calculer la classe d'armure étendue
     * 
     * @param array $character Données du personnage
     * @param array $equippedArmor Armure équipée
     * @param array $equippedShield Bouclier équipé
     * @return int Classe d'armure
     */
    public static function calculateArmorClassExtended($character, $equippedArmor, $equippedShield)
    {
        $ac = 10; // Base
        
        // Bonus de Dextérité
        $dexBonus = floor(($character['dexterity'] - 10) / 2);
        $ac += $dexBonus;
        
        // Bonus d'armure
        if ($equippedArmor) {
            $ac += $equippedArmor['ac_bonus'] ?? 0;
        }
        
        // Bonus de bouclier
        if ($equippedShield) {
            $ac += $equippedShield['ac_bonus'] ?? 0;
        }
        
        return $ac;
    }
    
    /**
     * Mettre à jour les points de vie d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @param int $newHp Nouveaux points de vie
     * @return bool Succès de l'opération
     */
    public static function updateHitPoints($npcId, $newHp)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET hit_points = ? WHERE id = ?");
            return $stmt->execute([$newHp, $npcId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points de vie: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour les points d'expérience d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @param int $newXp Nouveaux points d'expérience
     * @return bool Succès de l'opération
     */
    public static function updateExperiencePoints($npcId, $newXp)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET experience = ? WHERE id = ?");
            return $stmt->execute([$newXp, $npcId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points d'expérience: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les poisons d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @return array Liste des poisons
     */
    public static function getCharacterPoisons($npcId)
    {
        // Table npc_poisons n'existe pas encore, retourner un tableau vide
        return [];
    }
    
    /**
     * Obtenir les informations d'un poison
     * 
     * @param int $poisonId ID du poison
     * @return array|null Informations du poison ou null
     */
    public static function getPoisonInfo($poisonId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM poisons WHERE id = ?");
            $stmt->execute([$poisonId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations du poison: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir les informations d'un objet magique
     * 
     * @param int $itemId ID de l'objet magique
     * @return array|null Informations de l'objet ou null
     */
    public static function getMagicalItemInfo($itemId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM magical_items WHERE id = ?");
            $stmt->execute([$itemId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations de l'objet magique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir les sorts d'un NPC (comme pour les PJ)
     * 
     * @param int $npcId ID du NPC
     * @return array Liste des sorts
     */
    public static function getNpcSpells($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, ns.prepared 
                FROM npc_spells ns
                JOIN spells s ON ns.spell_id = s.id
                WHERE ns.npc_id = ?
                ORDER BY s.level, s.name
            ");
            $stmt->execute([$npcId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des sorts du NPC: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les capacités de sorts d'une classe pour un NPC
     * 
     * @param int $classId ID de la classe
     * @param int $level Niveau du NPC
     * @param int $wisdomModifier Modificateur de sagesse
     * @param int $intelligenceModifier Modificateur d'intelligence
     * @return array|null Capacités de sorts
     */
    public static function getClassSpellCapabilities($classId, $level, $wisdomModifier = 0, $intelligenceModifier = 0)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT cantrips_known, spells_known, 
                   spell_slots_1st, spell_slots_2nd, spell_slots_3rd, 
                   spell_slots_4th, spell_slots_5th, spell_slots_6th, 
                   spell_slots_7th, spell_slots_8th, spell_slots_9th
            FROM class_evolution 
            WHERE class_id = ? AND level = ?
        ");
        $stmt->execute([$classId, $level]);
        $capabilities = $stmt->fetch();
        
        if ($capabilities) {
            // Récupérer le nom de la classe
            $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
            $stmt->execute([$classId]);
            $class = $stmt->fetch();
            
            // Calculer le modificateur de caractéristique d'incantation
            $spellcastingAbility = 0;
            switch ($classId) {
                case 2: // Barde
                case 7: // Magicien
                case 10: // Occultiste
                case 11: // Ensorceleur
                    $spellcastingAbility = $intelligenceModifier;
                    break;
                case 3: // Clerc
                case 4: // Druide
                case 9: // Paladin
                case 5: // Rôdeur
                    $spellcastingAbility = $wisdomModifier;
                    break;
            }
            
            return [
                'class_name' => $class['name'],
                'cantrips_known' => $capabilities['cantrips_known'],
                'spells_known' => $capabilities['spells_known'],
                'spell_slots' => [
                    1 => $capabilities['spell_slots_1st'],
                    2 => $capabilities['spell_slots_2nd'],
                    3 => $capabilities['spell_slots_3rd'],
                    4 => $capabilities['spell_slots_4th'],
                    5 => $capabilities['spell_slots_5th'],
                    6 => $capabilities['spell_slots_6th'],
                    7 => $capabilities['spell_slots_7th'],
                    8 => $capabilities['spell_slots_8th'],
                    9 => $capabilities['spell_slots_9th']
                ],
                'spellcasting_ability' => $spellcastingAbility
            ];
        }
        
        return null;
    }
    
    /**
     * Obtenir l'utilisation des emplacements de sorts d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @return array Utilisation des emplacements
     */
    public static function getSpellSlotsUsage($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT spell_level, slots_used 
                FROM npc_spell_slots_usage 
                WHERE npc_id = ?
            ");
            $stmt->execute([$npcId]);
            $usage = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($usage as $row) {
                $result[$row['spell_level']] = $row['slots_used'];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation des emplacements: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Utiliser un emplacement de sort
     * 
     * @param int $npcId ID du NPC
     * @param int $spellLevel Niveau du sort
     * @return bool Succès de l'opération
     */
    public static function useSpellSlot($npcId, $spellLevel)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Vérifier si l'emplacement existe déjà
            $stmt = $pdo->prepare("SELECT slots_used FROM npc_spell_slots_usage WHERE npc_id = ? AND spell_level = ?");
            $stmt->execute([$npcId, $spellLevel]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Mettre à jour
                $stmt = $pdo->prepare("UPDATE npc_spell_slots_usage SET slots_used = slots_used + 1 WHERE npc_id = ? AND spell_level = ?");
                $stmt->execute([$npcId, $spellLevel]);
            } else {
                // Créer
                $stmt = $pdo->prepare("INSERT INTO npc_spell_slots_usage (npc_id, spell_level, slots_used) VALUES (?, ?, 1)");
                $stmt->execute([$npcId, $spellLevel]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'utilisation de l'emplacement de sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restaurer tous les emplacements de sorts (repos long)
     * 
     * @param int $npcId ID du NPC
     * @return bool Succès de l'opération
     */
    public static function restoreSpellSlots($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("DELETE FROM npc_spell_slots_usage WHERE npc_id = ?");
            $stmt->execute([$npcId]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la restauration des emplacements de sorts: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter un sort à un NPC
     * 
     * @param int $npcId ID du NPC
     * @param int $spellId ID du sort
     * @return bool Succès de l'opération
     */
    public static function addSpellToNpc($npcId, $spellId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Vérifier si le sort n'est pas déjà préparé
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
            $checkStmt->execute([$npcId, $spellId]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists['count'] == 0) {
                $insertStmt = $pdo->prepare("INSERT INTO npc_spells (npc_id, spell_id, prepared) VALUES (?, ?, 1)");
                $insertStmt->execute([$npcId, $spellId]);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du sort au NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retirer un sort d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @param int $spellId ID du sort
     * @return bool Succès de l'opération
     */
    public static function removeSpellFromNpc($npcId, $spellId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
            $deleteStmt->execute([$npcId, $spellId]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du sort du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter automatiquement les sorts de base à un NPC selon sa classe
     * 
     * @param int $npcId ID du NPC
     * @return bool Succès de l'opération
     */
    public static function addBaseSpells($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Récupérer les informations du NPC
            $stmt = $pdo->prepare("
                SELECT n.*, c.name as class_name
                FROM npcs n
                LEFT JOIN classes c ON n.class_id = c.id
                WHERE n.id = ?
            ");
            $stmt->execute([$npcId]);
            $npc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$npc) {
                return false;
            }
            
            $addedSpells = 0;
            
            // Définir les sorts de base par classe et niveau
            $classSpells = [
                'Magicien' => [
                    0 => ['Lumière', 'Prestidigitation', 'Thaumaturgie'], // Sorts mineurs
                    1 => ['Bouclier', 'Détection de la magie', 'Projectile magique', 'Soins des blessures']
                ],
                'Clerc' => [
                    0 => ['Lumière', 'Thaumaturgie', 'Guidance'], // Sorts mineurs
                    1 => ['Soins des blessures', 'Bénédiction', 'Détection du mal', 'Protection contre le mal']
                ],
                'Druide' => [
                    0 => ['Lumière', 'Guidance', 'Prestidigitation'], // Sorts mineurs
                    1 => ['Soins des blessures', 'Entrelacement', 'Détection de la magie', 'Parler avec les animaux']
                ],
                'Barde' => [
                    0 => ['Lumière', 'Prestidigitation', 'Thaumaturgie'], // Sorts mineurs
                    1 => ['Charme-personne', 'Détection de la magie', 'Soins des blessures', 'Déguisement']
                ],
                'Paladin' => [
                    0 => ['Lumière', 'Thaumaturgie', 'Guidance'], // Sorts mineurs
                    1 => ['Soins des blessures', 'Détection du mal', 'Protection contre le mal', 'Bénédiction']
                ],
                'Rôdeur' => [
                    0 => ['Lumière', 'Guidance', 'Prestidigitation'], // Sorts mineurs
                    1 => ['Détection de la magie', 'Parler avec les animaux', 'Soins des blessures', 'Entrelacement']
                ],
                'Ensorceleur' => [
                    0 => ['Lumière', 'Prestidigitation', 'Thaumaturgie'], // Sorts mineurs
                    1 => ['Bouclier', 'Détection de la magie', 'Projectile magique', 'Charme-personne']
                ],
                'Occultiste' => [
                    0 => ['Lumière', 'Prestidigitation', 'Thaumaturgie'], // Sorts mineurs
                    1 => ['Détection de la magie', 'Charme-personne', 'Bouclier', 'Projectile magique']
                ]
            ];
            
            $className = $npc['class_name'];
            $npcLevel = $npc['level'];
            
            // Calculer les capacités de sorts pour respecter les limites
            $capabilities = self::getClassSpellCapabilities($npc['class_id'], $npcLevel, 0, 0);
            $maxCantrips = $capabilities['cantrips_known'] ?? 0;
            $maxPrepared = $capabilities['spells_known'] ?? 0;
            
            // Compter les sorts existants
            $existingCantrips = 0;
            $existingPreparedSpells = 0;
            $stmt = $pdo->prepare("
                SELECT s.level, COUNT(*) as count
                FROM npc_spells ns
                JOIN spells s ON ns.spell_id = s.id
                WHERE ns.npc_id = ?
                GROUP BY s.level
            ");
            $stmt->execute([$npcId]);
            $existingSpells = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($existingSpells as $spell) {
                if ($spell['level'] == 0) {
                    $existingCantrips = $spell['count'];
                } else {
                    $existingPreparedSpells += $spell['count'];
                }
            }
            
            // Ajouter les sorts selon le niveau du NPC
            if (isset($classSpells[$className])) {
                $cantripsAdded = 0;
                $preparedSpellsAdded = 0;
                
                // D'abord, ajouter les sorts mineurs jusqu'à la limite
                if (isset($classSpells[$className][0]) && $npcLevel >= 1) {
                    foreach ($classSpells[$className][0] as $spellName) {
                        if ($existingCantrips + $cantripsAdded >= $maxCantrips) {
                            break; // Limite de sorts mineurs atteinte
                        }
                        
                        // Trouver l'ID du sort
                        $spellStmt = $pdo->prepare("SELECT id FROM spells WHERE name = ? LIMIT 1");
                        $spellStmt->execute([$spellName]);
                        $spell = $spellStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($spell) {
                            // Vérifier si le sort n'est pas déjà préparé
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
                            $checkStmt->execute([$npcId, $spell['id']]);
                            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($exists['count'] == 0) {
                                $insertStmt = $pdo->prepare("INSERT INTO npc_spells (npc_id, spell_id, prepared) VALUES (?, ?, 1)");
                                $insertStmt->execute([$npcId, $spell['id']]);
                                $addedSpells++;
                                $cantripsAdded++;
                            }
                        }
                    }
                }
                
                // Ensuite, ajouter les sorts de niveau 1+ jusqu'à la limite
                if (isset($classSpells[$className][1]) && $npcLevel >= 1) {
                    foreach ($classSpells[$className][1] as $spellName) {
                        if ($existingPreparedSpells + $preparedSpellsAdded >= $maxPrepared) {
                            break; // Limite de sorts préparés atteinte
                        }
                        
                        // Trouver l'ID du sort
                        $spellStmt = $pdo->prepare("SELECT id FROM spells WHERE name = ? LIMIT 1");
                        $spellStmt->execute([$spellName]);
                        $spell = $spellStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($spell) {
                            // Vérifier si le sort n'est pas déjà préparé
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_spells WHERE npc_id = ? AND spell_id = ?");
                            $checkStmt->execute([$npcId, $spell['id']]);
                            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($exists['count'] == 0) {
                                $insertStmt = $pdo->prepare("INSERT INTO npc_spells (npc_id, spell_id, prepared) VALUES (?, ?, 1)");
                                $insertStmt->execute([$npcId, $spell['id']]);
                                $addedSpells++;
                                $preparedSpellsAdded++;
                            }
                        }
                    }
                }
            }
            
            error_log("Debug NPC::addBaseSpells - Added " . $addedSpells . " spells to NPC " . $npcId);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des sorts de base du NPC: " . $e->getMessage());
            return false;
        }
    }
    
}
?>
