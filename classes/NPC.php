<?php

require_once __DIR__ . '/Item.php';

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
    public $hit_points_current;
    public $hit_points_max;
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
    public $silver;
    public $copper;
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
        $this->hit_points_current = $data['hit_points_current'] ?? 8;
        $this->hit_points_max = $data['hit_points_max'] ?? 8;
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
        $this->silver = $data['silver'] ?? 0;
        $this->copper = $data['copper'] ?? 0;
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
            'hit_points_current' => $this->hit_points_current,
            'hit_points_max' => $this->hit_points_max,
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
            'silver' => $this->silver,
            'copper' => $this->copper,
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
                hit_points_current, hit_points_max, armor_class, speed, alignment, age, height, weight,
                eyes, skin, hair, backstory, personality_traits, ideals, bonds, flaws,
                starting_equipment, gold, silver, copper, spells, skills, languages, profile_photo,
                created_by, world_id, location_id, is_active
            ) VALUES (
                :name, :class_id, :race_id, :background_id, :archetype_id, :level, :experience,
                :strength, :dexterity, :constitution, :intelligence, :wisdom, :charisma,
                :hit_points_current, :hit_points_max, :armor_class, :speed, :alignment, :age, :height, :weight,
                :eyes, :skin, :hair, :backstory, :personality_traits, :ideals, :bonds, :flaws,
                :starting_equipment, :gold, :silver, :copper, :spells, :skills, :languages, :profile_photo,
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
                ':hit_points_current' => $this->hit_points_current,
                ':hit_points_max' => $this->hit_points_max,
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
                ':silver' => $this->silver,
                ':copper' => $this->copper,
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
                hit_points_current = :hit_points_current, hit_points_max = :hit_points_max, armor_class = :armor_class, speed = :speed,
                alignment = :alignment, age = :age, height = :height, weight = :weight,
                eyes = :eyes, skin = :skin, hair = :hair, backstory = :backstory,
                personality_traits = :personality_traits, ideals = :ideals, bonds = :bonds, flaws = :flaws,
                starting_equipment = :starting_equipment, gold = :gold, silver = :silver, copper = :copper, spells = :spells,
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
                ':hit_points_current' => $this->hit_points_current,
                ':hit_points_max' => $this->hit_points_max,
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
                ':silver' => $this->silver,
                ':copper' => $this->copper,
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
            $this->pdo->beginTransaction();
            
            // Supprimer les items du NPC (table items)
            $stmt = $this->pdo->prepare("DELETE FROM items WHERE owner_type = 'npc' AND owner_id = ?");
            $stmt->execute([$this->id]);
            
            // Supprimer le NPC lui-même
            $sql = "DELETE FROM npcs WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => $this->id]);
            
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer complètement le NPC avec toutes ses données associées
     * 
     * @return bool True si succès, false sinon
     */
    public function deleteCompletely()
    {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Supprimer l'équipement du NPC (table items)
            $stmt = $this->pdo->prepare("DELETE FROM items WHERE owner_type = 'npc' AND owner_id = ?");
            $stmt->execute([$this->id]);
            
            // 2. Supprimer l'équipement du NPC (table npc_equipment)
            $stmt = $this->pdo->prepare("DELETE FROM npc_equipment WHERE npc_id = ?");
            $stmt->execute([$this->id]);
            
            // 3. Supprimer les sorts du NPC
            $stmt = $this->pdo->prepare("DELETE FROM npc_spells WHERE npc_id = ?");
            $stmt->execute([$this->id]);
            
            // 4. Supprimer l'utilisation des emplacements de sorts
            $stmt = $this->pdo->prepare("DELETE FROM npc_spell_slots_usage WHERE npc_id = ?");
            $stmt->execute([$this->id]);
            
            // 5. Retirer le NPC de tous les lieux (place_npcs)
            $stmt = $this->pdo->prepare("DELETE FROM place_npcs WHERE npc_character_id = ?");
            $stmt->execute([$this->id]);
            
            // 6. Retirer le NPC de toutes les scènes (scene_npcs)
            $stmt = $this->pdo->prepare("DELETE FROM scene_npcs WHERE npc_character_id = ?");
            $stmt->execute([$this->id]);
            
            // 7. Retirer le NPC de tous les groupes
            $stmt = $this->pdo->prepare("DELETE FROM groupe_membres WHERE member_id = ? AND member_type = 'pnj'");
            $stmt->execute([$this->id]);
            
            // 8. Supprimer le NPC lui-même
            $stmt = $this->pdo->prepare("DELETE FROM npcs WHERE id = ? AND created_by = ?");
            $stmt->execute([$this->id, $this->created_by]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("NPC non trouvé ou permissions insuffisantes");
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression complète du NPC: " . $e->getMessage());
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
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des capacités de base du PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter les langues choisies par l'utilisateur lors de la création
     * 
     * @param array $selectedLanguages Langues choisies par l'utilisateur
     * @param array $fixedLanguages Langues fixes (race/historique)
     * @return bool Succès de l'opération
     */
    public function addChosenLanguages($selectedLanguages = [], $fixedLanguages = [])
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $addedLanguages = 0;
            
            // Ajouter les langues fixes
            foreach ($fixedLanguages as $languageName) {
                $languageName = trim($languageName);
                if (empty($languageName)) continue;
                
                // Trouver l'ID de la langue dans la table languages
                $langStmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
                $langStmt->execute([$languageName]);
                $language = $langStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($language) {
                    // Vérifier si la langue n'est pas déjà assignée
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_languages WHERE npc_id = ? AND language_id = ?");
                    $checkStmt->execute([$this->id, $language['id']]);
                    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($exists['count'] == 0) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO npc_languages (npc_id, language_id, is_active, learned_at)
                            VALUES (?, ?, 1, NOW())
                        ");
                        $insertStmt->execute([$this->id, $language['id']]);
                        $addedLanguages++;
                    }
                }
            }
            
            // Ajouter les langues choisies par l'utilisateur
            foreach ($selectedLanguages as $languageName) {
                $languageName = trim($languageName);
                if (empty($languageName)) continue;
                
                // Trouver l'ID de la langue dans la table languages
                $langStmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
                $langStmt->execute([$languageName]);
                $language = $langStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($language) {
                    // Vérifier si la langue n'est pas déjà assignée
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_languages WHERE npc_id = ? AND language_id = ?");
                    $checkStmt->execute([$this->id, $language['id']]);
                    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($exists['count'] == 0) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO npc_languages (npc_id, language_id, is_active, learned_at)
                            VALUES (?, ?, 1, NOW())
                        ");
                        $insertStmt->execute([$this->id, $language['id']]);
                        $addedLanguages++;
                    }
                }
            }
            
            error_log("Debug NPC::addChosenLanguages - Added " . $addedLanguages . " languages to NPC " . $this->id);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des langues choisies du PNJ: " . $e->getMessage());
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
                // Les langues peuvent être stockées en JSON ou en chaîne séparée par des virgules
                $languages = json_decode($race['race_languages'], true);
                if (!$languages) {
                    // Si ce n'est pas du JSON, traiter comme une chaîne séparée par des virgules
                    $languages = array_map('trim', explode(',', $race['race_languages']));
                }
                
                if ($languages) {
                    foreach ($languages as $languageName) {
                        $languageName = trim($languageName);
                        if (empty($languageName)) continue;
                        
                        // Trouver l'ID de la langue dans la table languages
                        $langStmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
                        $langStmt->execute([$languageName]);
                        $language = $langStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($language) {
                            // Vérifier si la langue n'est pas déjà assignée
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_languages WHERE npc_id = ? AND language_id = ?");
                            $checkStmt->execute([$this->id, $language['id']]);
                            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($exists['count'] == 0) {
                                $insertStmt = $pdo->prepare("
                                    INSERT INTO npc_languages (npc_id, language_id, is_active, learned_at)
                                    VALUES (?, ?, 1, NOW())
                                ");
                                $insertStmt->execute([$this->id, $language['id']]);
                                $addedLanguages++;
                            }
                        }
                    }
                }
            }
            
            // Ajouter les langues d'historique si disponible
            if ($this->background_id) {
                $stmt = $pdo->prepare("
                    SELECT languages
                    FROM backgrounds
                    WHERE id = ?
                ");
                $stmt->execute([$this->background_id]);
                $background = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($background && $background['languages']) {
                    $backgroundLanguages = json_decode($background['languages'], true);
                    if ($backgroundLanguages) {
                        foreach ($backgroundLanguages as $languageName) {
                            // Trouver l'ID de la langue dans la table languages
                            $langStmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
                            $langStmt->execute([$languageName]);
                            $language = $langStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($language) {
                                // Vérifier si la langue n'est pas déjà assignée
                                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_languages WHERE npc_id = ? AND language_id = ?");
                                $checkStmt->execute([$this->id, $language['id']]);
                                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($exists['count'] == 0) {
                                    $insertStmt = $pdo->prepare("
                                        INSERT INTO npc_languages (npc_id, language_id, is_active, learned_at)
                                        VALUES (?, ?, 1, NOW())
                                    ");
                                    $insertStmt->execute([$this->id, $language['id']]);
                                    $addedLanguages++;
                                }
                            }
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
            require_once __DIR__ . '/NPCSkills.php';
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

                        // Trouver l'ID correspondant dans les tables spécialisées
                        $armorId = null;
                        $weaponId = null;
                        $poisonId = null;
                        $shieldId = null;

                        if ($objectType === 'armor') {
                            $stmt = $pdo->prepare("SELECT id FROM armor WHERE name = ?");
                            $stmt->execute([$item]);
                            $armor = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($armor) {
                                $armorId = $armor['id'];
                            }
                        } elseif ($objectType === 'weapon') {
                            $stmt = $pdo->prepare("SELECT id FROM weapons WHERE name = ?");
                            $stmt->execute([$item]);
                            $weapon = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($weapon) {
                                $weaponId = $weapon['id'];
                            }
                        } elseif ($objectType === 'poison') {
                            $stmt = $pdo->prepare("SELECT id FROM poisons WHERE name = ?");
                            $stmt->execute([$item]);
                            $poison = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($poison) {
                                $poisonId = $poison['id'];
                            }
                        } elseif ($objectType === 'shield') {
                            $stmt = $pdo->prepare("SELECT id FROM shields WHERE name = ?");
                            $stmt->execute([$item]);
                            $shield = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($shield) {
                                $shieldId = $shield['id'];
                            }
                        }

                        // Créer un objet dans la table items
                        $stmt = $pdo->prepare("
                            INSERT INTO items (
                                display_name, description, object_type, type_precis,
                                owner_type, owner_id, place_id, is_visible, is_identified, is_equipped,
                                armor_id, weapon_id, poison_id, shield_id,
                                created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
                            1,
                            1,  // Marquer comme équipé
                            $armorId,
                            $weaponId,
                            $poisonId,
                            $shieldId
                        ]);
                        $addedItems++;
                    }
                }

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
    
    

    
    /**
     * Obtenir les capacités du NPC
     * 
     * @return array Liste des capacités
     */
    public function getCapabilities()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Vérifier d'abord si les données dans npc_capabilities sont valides et cohérentes
            $stmt = $pdo->prepare("
                SELECT 
                    c.name,
                    c.description,
                    c.source_type as source,
                    ct.name as type_name,
                    nc.is_active,
                    nc.obtained_at as learned_at
                FROM npc_capabilities nc
                LEFT JOIN capabilities c ON nc.capability_id = c.id
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE nc.npc_id = ? AND nc.is_active = 1 AND c.name IS NOT NULL
                ORDER BY c.name
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier si les capacités sont cohérentes avec la classe et la race du PNJ
            if (!empty($result)) {
                $hasValidClassCapabilities = false;
                $hasValidRaceCapabilities = false;
                
                foreach ($result as $cap) {
                    if ($cap['source'] === 'class') {
                        $hasValidClassCapabilities = true;
                    }
                    if ($cap['source'] === 'race') {
                        $hasValidRaceCapabilities = true;
                    }
                }
                
                // Si on a des capacités de classe et de race valides, les utiliser
                if ($hasValidClassCapabilities && $hasValidRaceCapabilities) {
                    return $result;
                }
            }
            
            // Sinon, générer automatiquement les capacités de base
            return $this->generateBaseCapabilities();
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités du PNJ: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Générer automatiquement les capacités de base du PNJ
     * 
     * @return array Liste des capacités générées
     */
    private function generateBaseCapabilities()
    {
        $pdo = \Database::getInstance()->getPdo();
        $capabilities = [];
        
        try {
            // Récupérer les capacités de classe
            $stmt = $pdo->prepare("
                SELECT c.name, c.description, c.source_type, ct.name as type_name
                FROM capabilities c
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE c.source_type = 'class' AND c.source_id = ? AND c.level_requirement <= ?
                ORDER BY c.level_requirement, c.name
            ");
            $stmt->execute([$this->class_id, $this->level]);
            $classCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les capacités raciales
            $stmt = $pdo->prepare("
                SELECT c.name, c.description, c.source_type, ct.name as type_name
                FROM capabilities c
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE c.source_type = 'race' AND c.source_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$this->race_id]);
            $raceCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combiner toutes les capacités
            $capabilities = array_merge($classCapabilities, $raceCapabilities);
            
            // Ajouter les capacités d'historique si disponible
            if ($this->background_id) {
                $stmt = $pdo->prepare("
                    SELECT c.name, c.description, c.source_type, ct.name as type_name
                    FROM capabilities c
                    LEFT JOIN capability_types ct ON c.type_id = ct.id
                    WHERE c.source_type = 'background' AND c.source_id = ?
                    ORDER BY c.name
                ");
                $stmt->execute([$this->background_id]);
                $backgroundCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $capabilities = array_merge($capabilities, $backgroundCapabilities);
            }
            
            // Ajouter les métadonnées manquantes
            foreach ($capabilities as &$capability) {
                $capability['is_active'] = 1;
                $capability['learned_at'] = date('Y-m-d H:i:s');
                // S'assurer que le champ source est défini
                if (!isset($capability['source'])) {
                    $capability['source'] = $capability['source_type'] ?? 'unknown';
                }
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la génération des capacités de base: " . $e->getMessage());
        }
        
        return $capabilities;
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
     * Obtenir les améliorations de caractéristiques de ce NPC
     * 
     * @return array Améliorations de caractéristiques
     */
    public function getCharacterAbilityImprovements()
    {
        $pdo = \Database::getInstance()->getPdo();
        try {
                $stmt = $pdo->prepare("
                SELECT strength_bonus, dexterity_bonus, constitution_bonus, 
                       intelligence_bonus, wisdom_bonus, charisma_bonus
                FROM npc_ability_improvements
                WHERE npc_id = ?
            ");
            $stmt->execute([$this->id]);
            $improvements = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($improvements) {
                // Convertir en format attendu par view_npc.php
                $result = [];
                if ($improvements['strength_bonus'] > 0) {
                    $result[] = ['ability' => 'strength', 'improvement' => $improvements['strength_bonus']];
                }
                if ($improvements['dexterity_bonus'] > 0) {
                    $result[] = ['ability' => 'dexterity', 'improvement' => $improvements['dexterity_bonus']];
                }
                if ($improvements['constitution_bonus'] > 0) {
                    $result[] = ['ability' => 'constitution', 'improvement' => $improvements['constitution_bonus']];
                }
                if ($improvements['intelligence_bonus'] > 0) {
                    $result[] = ['ability' => 'intelligence', 'improvement' => $improvements['intelligence_bonus']];
                }
                if ($improvements['wisdom_bonus'] > 0) {
                    $result[] = ['ability' => 'wisdom', 'improvement' => $improvements['wisdom_bonus']];
                }
                if ($improvements['charisma_bonus'] > 0) {
                    $result[] = ['ability' => 'charisma', 'improvement' => $improvements['charisma_bonus']];
                }
                return $result;
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des améliorations de caractéristiques: " . $e->getMessage());
            return [];
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
            require_once 'Classe.php';
            $classObj = Classe::findById($npc['class_id']);
            $capabilities = $classObj ? $classObj->getSpellCapabilities($npcLevel, 0, null, 0) : null;
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
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des sorts de base du NPC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute automatiquement les améliorations de caractéristiques selon le niveau
     */
    public static function addAbilityImprovements($npcId) {
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
            
            $level = $npc['level'];
            $addedImprovements = 0;
            
            // Calculer le nombre d'améliorations attendues selon le niveau
            $expectedImprovements = 0;
            if ($level >= 4) $expectedImprovements++;
            if ($level >= 8) $expectedImprovements++;
            if ($level >= 12) $expectedImprovements++;
            if ($level >= 16) $expectedImprovements++;
            if ($level >= 19) $expectedImprovements++;
            
            // Vérifier les améliorations existantes
            $existingStmt = $pdo->prepare("SELECT * FROM npc_ability_improvements WHERE npc_id = ?");
            $existingStmt->execute([$npcId]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                // Créer un enregistrement pour ce NPC
                $insertStmt = $pdo->prepare("
                    INSERT INTO npc_ability_improvements (npc_id, strength_bonus, dexterity_bonus, constitution_bonus, intelligence_bonus, wisdom_bonus, charisma_bonus)
                    VALUES (?, 0, 0, 0, 0, 0, 0)
                ");
                $insertStmt->execute([$npcId]);
                $existing = [
                    'strength_bonus' => 0,
                    'dexterity_bonus' => 0,
                    'constitution_bonus' => 0,
                    'intelligence_bonus' => 0,
                    'wisdom_bonus' => 0,
                    'charisma_bonus' => 0
                ];
            }
            
            // Calculer les améliorations manquantes
            $totalBonus = $existing['strength_bonus'] + $existing['dexterity_bonus'] + $existing['constitution_bonus'] + 
                          $existing['intelligence_bonus'] + $existing['wisdom_bonus'] + $existing['charisma_bonus'];
            $currentImprovements = $totalBonus / 2; // Chaque amélioration donne +2
            $missingImprovements = $expectedImprovements - $currentImprovements;
            
            if ($missingImprovements > 0) {
                // Définir les caractéristiques prioritaires par classe
                $classPriorities = [
                    'Barbare' => ['strength', 'constitution'],
                    'Barde' => ['charisma', 'dexterity'],
                    'Clerc' => ['wisdom', 'constitution'],
                    'Druide' => ['wisdom', 'constitution'],
                    'Guerrier' => ['strength', 'constitution'],
                    'Magicien' => ['intelligence', 'constitution'],
                    'Moine' => ['dexterity', 'wisdom'],
                    'Paladin' => ['strength', 'charisma'],
                    'Rôdeur' => ['dexterity', 'wisdom'],
                    'Roublard' => ['dexterity', 'intelligence'],
                    'Ensorceleur' => ['charisma', 'constitution'],
                    'Occultiste' => ['charisma', 'intelligence']
                ];
                
                $className = $npc['class_name'];
                $priorities = $classPriorities[$className] ?? ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
                
                // Calculer les nouveaux bonus
                $newBonuses = [
                    'strength_bonus' => $existing['strength_bonus'],
                    'dexterity_bonus' => $existing['dexterity_bonus'],
                    'constitution_bonus' => $existing['constitution_bonus'],
                    'intelligence_bonus' => $existing['intelligence_bonus'],
                    'wisdom_bonus' => $existing['wisdom_bonus'],
                    'charisma_bonus' => $existing['charisma_bonus']
                ];
                
                // Ajouter les améliorations manquantes
                for ($i = 0; $i < $missingImprovements; $i++) {
                    // Choisir la caractéristique à améliorer
                    $abilityToImprove = $priorities[$i % count($priorities)];
                    $bonusColumn = $abilityToImprove . '_bonus';
                    $newBonuses[$bonusColumn] += 2;
                    $addedImprovements++;
                }
                
                // Mettre à jour la base de données
                $updateStmt = $pdo->prepare("
                    UPDATE npc_ability_improvements 
                    SET strength_bonus = ?, dexterity_bonus = ?, constitution_bonus = ?, 
                        intelligence_bonus = ?, wisdom_bonus = ?, charisma_bonus = ?
                    WHERE npc_id = ?
                ");
                $updateStmt->execute([
                    $newBonuses['strength_bonus'],
                    $newBonuses['dexterity_bonus'],
                    $newBonuses['constitution_bonus'],
                    $newBonuses['intelligence_bonus'],
                    $newBonuses['wisdom_bonus'],
                    $newBonuses['charisma_bonus'],
                $npcId
            ]);
            
                // Les améliorations sont stockées en base et appliquées via getCharacterAbilityImprovements
                // Les caractéristiques de base restent intactes (niveau 1)
                // PAS de modification des caractéristiques ici !
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des améliorations de caractéristiques: " . $e->getMessage());
            return false;
        }
    }

    






    
    
    /**
     * Calcule le modificateur d'une caractéristique
     */
    public function getAbilityModifier($ability) {
        $value = $this->$ability;
        return floor(($value - 10) / 2);
    }
    
    /**
     * Calcule tous les modificateurs de caractéristiques
     */
    public function getAllAbilityModifiers() {
        return [
            'strength' => $this->getAbilityModifier('strength'),
            'dexterity' => $this->getAbilityModifier('dexterity'),
            'constitution' => $this->getAbilityModifier('constitution'),
            'intelligence' => $this->getAbilityModifier('intelligence'),
            'wisdom' => $this->getAbilityModifier('wisdom'),
            'charisma' => $this->getAbilityModifier('charisma')
        ];
    }
    
    /**
     * Calcule les caractéristiques totales (base + bonus raciaux + améliorations de niveau)
     */
    public function getTotalAbilities() {
        // Récupérer les bonus raciaux
        $race = Race::findById($this->race_id);
        $raceBonuses = $race ? $race->toArray() : [];
        
        // Récupérer les améliorations de niveau
        $abilityImprovements = $this->getCharacterAbilityImprovements();
        
        return [
            'strength' => $this->strength + ($raceBonuses['strength_bonus'] ?? 0) + ($abilityImprovements['strength'] ?? 0),
            'dexterity' => $this->dexterity + ($raceBonuses['dexterity_bonus'] ?? 0) + ($abilityImprovements['dexterity'] ?? 0),
            'constitution' => $this->constitution + ($raceBonuses['constitution_bonus'] ?? 0) + ($abilityImprovements['constitution'] ?? 0),
            'intelligence' => $this->intelligence + ($raceBonuses['intelligence_bonus'] ?? 0) + ($abilityImprovements['intelligence'] ?? 0),
            'wisdom' => $this->wisdom + ($raceBonuses['wisdom_bonus'] ?? 0) + ($abilityImprovements['wisdom'] ?? 0),
            'charisma' => $this->charisma + ($raceBonuses['charisma_bonus'] ?? 0) + ($abilityImprovements['charisma'] ?? 0)
        ];
    }
    
    /**
     * Calcule les modificateurs basés sur les caractéristiques totales
     */
    public function getTotalAbilityModifiers() {
        $totalAbilities = $this->getTotalAbilities();
        
        return [
            'strength' => floor(($totalAbilities['strength'] - 10) / 2),
            'dexterity' => floor(($totalAbilities['dexterity'] - 10) / 2),
            'constitution' => floor(($totalAbilities['constitution'] - 10) / 2),
            'intelligence' => floor(($totalAbilities['intelligence'] - 10) / 2),
            'wisdom' => floor(($totalAbilities['wisdom'] - 10) / 2),
            'charisma' => floor(($totalAbilities['charisma'] - 10) / 2)
        ];
    }
    
    
    
    
    
    
    /**
     * Méthodes d'instance pour remplacer les méthodes statiques
     */
    
    /**
     * Récupère les compétences du NPC (méthode d'instance)
     */
    public function getMySkills() {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT skills
            FROM npcs
            WHERE id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['skills']) {
            return json_decode($result['skills'], true) ?: [];
        }
        
        return [];
    }
    
    /**
     * Récupère les langues du NPC (méthode d'instance)
     */
    public function getMyLanguages() {
        // Utiliser la table npc_languages qui contient les langues choisies lors de la création
        $npcLanguages = $this->getNpcLanguages();
        
        if (!empty($npcLanguages)) {
            // Extraire seulement les noms des langues
            $languages = [];
            foreach ($npcLanguages as $lang) {
                $languages[] = $lang['name'];
            }
            return $languages;
        }
        
        // Si pas de langues dans npc_languages, essayer le champ languages de la table npcs
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT languages
            FROM npcs
            WHERE id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['languages']) {
            $languages = json_decode($result['languages'], true) ?: [];
            if (!empty($languages)) {
                return $languages;
            }
        }
        
        // Si toujours pas de langues, retourner un tableau vide
        // Les langues doivent être ajoutées lors de la création du PNJ
        return [];
    }
    
    
    /**
     * Récupère l'équipement du NPC (méthode d'instance)
     */
    public function getMyEquipment() {
        $pdo = \Database::getInstance()->getPdo();
        // Ne pas filtrer uniquement par owner_type car certains items peuvent être enregistrés avec owner_type='player'
        // alors qu'ils appartiennent à un NPC. Utiliser uniquement owner_id et vérifier que c'est bien un NPC.
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   i.description as item_description,
                   i.object_type as item_type,
                   i.is_equipped as equipped,
                   a.name as armor_name, a.ac_formula as armor_ac_formula,
                   w.name as weapon_name, w.damage as weapon_damage, w.properties as weapon_properties,
                   s.name as shield_name, s.ac_formula as shield_ac_formula,
                   -- Utiliser le nom spécifique selon le type d'équipement, sinon display_name
                   COALESCE(
                       CASE 
                           WHEN i.object_type = 'armor' THEN a.name
                           WHEN i.object_type = 'weapon' THEN w.name
                           WHEN i.object_type = 'shield' THEN s.name
                           ELSE NULL
                       END,
                       i.display_name
                   ) as item_name
            FROM items i
            LEFT JOIN armor a ON i.armor_id = a.id
            LEFT JOIN weapons w ON i.weapon_id = w.id
            LEFT JOIN shields s ON i.shield_id = s.id AND i.object_type = 'shield'
            WHERE i.owner_id = ? AND EXISTS (SELECT 1 FROM npcs WHERE id = i.owner_id)
            ORDER BY i.created_at ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'équipement détaillé du NPC (méthode d'instance)
     */
    public function getMyDetailedEquipment() {
        try {
            // Utiliser la classe Item pour récupérer l'équipement
            $items = Item::findByOwner('npc', $npcId, $pdo);
            
            // Convertir les objets Item en tableaux pour la compatibilité
            $equipment = [];
            foreach ($items as $item) {
                $equipment[] = $item->toArray();
            }
            
            return $equipment;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'équipement détaillé: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les poisons de ce NPC depuis la table items
     * 
     * @return array Liste des poisons du NPC
     */
    public function getCharacterPoisons()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, p.nom as poison_nom, p.type as poison_type, p.description as poison_description, p.source as poison_source
                FROM items i
                JOIN poisons p ON i.poison_id = p.csv_id
                WHERE i.owner_type = 'npc' AND i.owner_id = ? 
                AND i.poison_id IS NOT NULL
                ORDER BY i.obtained_at DESC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des poisons du NPC: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les informations d'un poison (méthode d'instance)
     */
    public function getMyPoisonInfo($poisonId) {
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
     * Récupère les informations d'un objet magique (méthode d'instance)
     */
    public function getMyMagicalItemInfo($itemId) {
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
     * Calcule les caractéristiques finales (méthode d'instance)
     */
    public function calculateMyFinalAbilities($abilityImprovements) {
        $finalAbilities = [
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma
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
     * Calcule les attaques du personnage (méthode d'instance)
     */
    public function calculateMyCharacterAttacks() {
        $pdo = \Database::getInstance()->getPdo();
        $attacks = [];
        
        try {
            // Récupérer les armes équipées avec leurs détails
            // Ne pas filtrer par owner_type car certains items peuvent être enregistrés avec owner_type='player' 
            // alors qu'ils appartiennent à un NPC
            $stmt = $pdo->prepare("
                SELECT 
                    i.*, 
                    i.display_name,
                    w.id as weapon_table_id,
                    w.name as weapon_name, 
                    w.damage as weapon_damage, 
                    w.properties as weapon_properties,
                    w.slot_type as weapon_slot_type
                FROM items i
                LEFT JOIN weapons w ON i.weapon_id = w.id
                WHERE i.owner_id = ? AND i.is_equipped = 1 AND i.object_type = 'weapon'
                ORDER BY i.equipped_slot, i.id
            ");
            $stmt->execute([$this->id]);
            $equippedWeapons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si des armes sont équipées, les utiliser
            if (!empty($equippedWeapons)) {
                foreach ($equippedWeapons as $weapon) {
                    // Utiliser weapon_name si disponible, sinon display_name
                    $weaponName = $weapon['weapon_name'] ?? $weapon['display_name'] ?? '';
                    // Utiliser weapon_damage si disponible, sinon essayer de le trouver
                    $weaponDamage = $weapon['weapon_damage'] ?? null;
                    
                    // Si on n'a pas de weapon_damage mais qu'on a un weapon_id, récupérer depuis la table weapons
                    if (!$weaponDamage && !empty($weapon['weapon_id'])) {
                        $weaponStmt = $pdo->prepare("SELECT damage FROM weapons WHERE id = ?");
                        $weaponStmt->execute([$weapon['weapon_id']]);
                        $weaponData = $weaponStmt->fetch(PDO::FETCH_ASSOC);
                        if ($weaponData) {
                            $weaponDamage = $weaponData['damage'];
                        }
                    }
                    
                    // Si on n'a toujours pas de weapon_damage, récupérer aussi weapon_properties si nécessaire
                    if (!$weaponDamage && !empty($weapon['weapon_id'])) {
                        $weaponStmt = $pdo->prepare("SELECT damage, properties FROM weapons WHERE id = ?");
                        $weaponStmt->execute([$weapon['weapon_id']]);
                        $weaponData = $weaponStmt->fetch(PDO::FETCH_ASSOC);
                        if ($weaponData) {
                            $weaponDamage = $weaponData['damage'] ?? $weaponDamage;
                            if (empty($weapon['weapon_properties']) && !empty($weaponData['properties'])) {
                                $weapon['weapon_properties'] = $weaponData['properties'];
                            }
                        }
                    }
                    
                    if ($weaponName && $weaponDamage) {
                        // Calculer le bonus d'attaque basé sur la caractéristique appropriée
                        $attackBonus = 0;
                        $damageBonus = 0;
                        
                        // Pour la plupart des armes, utiliser la Force
                        // TODO: Détecter les armes de jet (Dextérité) et les armes magiques
                        $strengthModifier = floor(($this->strength - 10) / 2);
                        $dexterityModifier = floor(($this->dexterity - 10) / 2);
                        
                        // Détecter si c'est une arme de jet basée sur les propriétés
                        $isRangedWeapon = false;
                        if (!empty($weapon['weapon_properties'])) {
                            $properties = strtolower($weapon['weapon_properties']);
                            if (strpos($properties, 'jet') !== false || 
                                strpos($properties, 'lancer') !== false || 
                                strpos($properties, 'distance') !== false ||
                                strpos($properties, 'range') !== false) {
                                $isRangedWeapon = true;
                            }
                        }
                        
                        if ($isRangedWeapon) {
                            $attackBonus = $dexterityModifier;
                            $damageBonus = $dexterityModifier;
                        } else {
                            $attackBonus = $strengthModifier;
                            $damageBonus = $strengthModifier;
                        }
                        
                        // Formater les dégâts
                        $damage = $weaponDamage;
                        if ($damageBonus > 0) {
                            $damage .= ' + ' . $damageBonus;
                        } elseif ($damageBonus < 0) {
                            $damage .= ' ' . $damageBonus;
                        }
                        
                        $attacks[] = [
                            'name' => $weaponName,
                            'bonus' => $attackBonus,
                            'damage' => $damage,
                            'type' => $isRangedWeapon ? 'À distance' : 'Corps à corps',
                            'properties' => $weapon['weapon_properties'] ?? ''
                        ];
                    } else {
                        // Log pour débogage
                        error_log("NPC calculateMyCharacterAttacks - Arme ignorée: weapon_id=" . ($weapon['weapon_id'] ?? 'null') . ", weapon_name=" . ($weapon['weapon_name'] ?? 'null') . ", display_name=" . ($weapon['display_name'] ?? 'null') . ", weapon_damage=" . ($weaponDamage ?? 'null'));
                    }
                }
            }
            
            // Si aucune arme équipée, ajouter l'attaque de base
            if (empty($attacks)) {
                $strengthModifier = floor(($this->strength - 10) / 2);
                $attacks[] = [
                    'name' => 'Coup de poing',
                    'bonus' => $strengthModifier,
                    'damage' => '1d4' . ($strengthModifier > 0 ? ' + ' . $strengthModifier : ($strengthModifier < 0 ? ' ' . $strengthModifier : '')),
                    'type' => 'Corps à corps'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul des attaques: " . $e->getMessage());
            // En cas d'erreur, retourner l'attaque de base
            $strengthModifier = floor(($this->strength - 10) / 2);
            $attacks[] = [
                'name' => 'Coup de poing',
                'bonus' => $strengthModifier,
                'damage' => '1d4' . ($strengthModifier > 0 ? ' + ' . $strengthModifier : ($strengthModifier < 0 ? ' ' . $strengthModifier : '')),
                'type' => 'Corps à corps'
            ];
        }
        
        return $attacks;
    }
    
    
    /**
     * Récupère les points d'amélioration restants (méthode d'instance)
     */
    public function getMyRemainingAbilityPoints($abilityImprovements) {
        $totalPoints = 0;
        $usedPoints = 0;
        
        // Calculer les points disponibles selon le niveau
        for ($i = 1; $i <= $this->level; $i++) {
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
     * Met à jour les points de vie (méthode d'instance)
     */
    public function updateMyHitPoints($newHp) {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET hit_points_current = ? WHERE id = ?");
            $result = $stmt->execute([$newHp, $this->id]);
            if ($result) {
                $this->hit_points_current = $newHp; // Mettre à jour la propriété de l'instance
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points de vie: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour l'expérience (méthode d'instance)
     */
    public function updateMyExperiencePoints($newXp) {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET experience = ? WHERE id = ?");
            $result = $stmt->execute([$newXp, $this->id]);
            if ($result) {
                $this->experience = $newXp; // Mettre à jour la propriété de l'instance
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points d'expérience: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour la photo de profil (méthode d'instance)
     */
    public function updateMyProfilePhoto($photoPath) {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET profile_photo = ? WHERE id = ?");
            $result = $stmt->execute([$photoPath, $this->id]);
            if ($result) {
                $this->profile_photo = $photoPath; // Mettre à jour la propriété de l'instance
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la photo de profil: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Synchronise l'équipement de base (méthode d'instance)
     */
    public function syncMyBaseEquipmentToCharacterEquipment() {
        // Cette méthode peut être vide pour les NPCs car ils utilisent déjà la table items
        return true;
    }
    
    /**
     * Récupère le nombre maximum de rages (méthode d'instance)
     */
    public function getMyMaxRages() {
        $pdo = \Database::getInstance()->getPdo();
        try {
            // Récupérer le nombre de rages depuis la table class_evolution
            $stmt = $pdo->prepare("SELECT rages FROM class_evolution WHERE class_id = ? AND level = ?");
            $stmt->execute([$this->class_id, $this->level]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['rages'] !== null) {
                return (int)$result['rages'];
            }
            
            // Si pas de données trouvées, retourner 0
            return 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du nombre maximum de rages: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Récupère l'utilisation des rages (méthode d'instance)
     */
    public function getMyRageUsage() {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT used, max_uses FROM npc_rage_usage 
                WHERE npc_id = ?
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'used' => $result['used'],
                    'max' => $result['max_uses'],
                    'available' => $result['max_uses'] - $result['used']
                ];
            } else {
                // Si aucune entrée n'existe, créer une entrée par défaut
                $stmt = $pdo->prepare("
                    INSERT INTO npc_rage_usage (npc_id, used, max_uses) 
                    VALUES (?, 0, 2)
                ");
                $stmt->execute([$this->id]);
                
                return [
                    'used' => 0,
                    'max' => 2,
                    'available' => 2
                ];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation des rages: " . $e->getMessage());
            return [
                'used' => 0,
                'max' => 2,
                'available' => 2
            ];
        }
    }
    
    /**
     * Calcule la classe d'armure du NPC (méthode d'instance)
     */
    public function getCA() {
        $pdo = \Database::getInstance()->getPdo();
        $ac = 10; // Base par défaut
        
        try {
            // Récupérer les caractéristiques totales (base + race + niveau + équipement + temporaires)
            $totalAbilities = $this->getMyTotalAbilities();
            $dexterityTotal = $totalAbilities['dexterity'];
            $constitutionTotal = $totalAbilities['constitution'];
            
            // Calculer les modificateurs
            $dexBonus = floor(($dexterityTotal - 10) / 2);
            $conBonus = floor(($constitutionTotal - 10) / 2);
            
            // Vérifier si c'est un barbare
            $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
            $stmt->execute([$this->class_id]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            $isBarbarian = $class && strpos(strtolower($class['name']), 'barbare') !== false;
            
            // Récupérer l'armure équipée
            $stmt = $pdo->prepare("
                SELECT a.*
                FROM items i
                JOIN armor a ON i.armor_id = a.id
                WHERE i.owner_type = 'npc' AND i.owner_id = ? AND i.is_equipped = 1 AND i.object_type = 'armor'
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            $equippedArmor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculer l'AC selon l'armure équipée
            if ($equippedArmor && isset($equippedArmor['ac_formula'])) {
                $ac = $this->parseACFormula($equippedArmor['ac_formula'], $dexBonus);
            } else {
                // Pas d'armure, AC de base + modificateur de Dextérité
                $ac = 10 + $dexBonus;
                
                // Particularité des barbares : AC = 10 + modificateur de Dextérité + modificateur de Constitution
                if ($isBarbarian) {
                    $ac += $conBonus;
                }
            }
            
            // Récupérer le bouclier équipé
            $stmt = $pdo->prepare("
                SELECT s.*
                FROM items i
                JOIN shields s ON i.shield_id = s.id
                WHERE i.owner_type = 'npc' AND i.owner_id = ? AND i.is_equipped = 1 AND i.object_type = 'shield'
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            $equippedShield = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Bonus de bouclier
            if ($equippedShield && isset($equippedShield['ac_formula'])) {
                $shieldBonus = $this->parseShieldBonus($equippedShield['ac_formula']);
                $ac += $shieldBonus;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul de la classe d'armure: " . $e->getMessage());
            // En cas d'erreur, retourner l'AC de base
            $ac = 10 + floor(($this->dexterity - 10) / 2);
        }
        
        return $ac;
    }
    
    /**
     * Parse une formule d'AC d'armure
     */
    private function parseACFormula($formula, $dexBonus) {
        // Extraire le nombre de base de la formule (ex: "11 + Mod.Dex" -> 11)
        if (preg_match('/^(\d+)/', $formula, $matches)) {
            $baseAC = (int)$matches[1];
            
            // Vérifier s'il y a une limitation de modificateur de Dextérité
            if (preg_match('/max \+(\d+)/', $formula, $maxMatches)) {
                $maxDex = (int)$maxMatches[1];
                $dexBonus = min($dexBonus, $maxDex);
            }
            
            return $baseAC + $dexBonus;
        }
        
        // Fallback: AC de base + modificateur de Dextérité
        return 10 + $dexBonus;
    }
    
    /**
     * Parse le bonus d'un bouclier
     */
    private function parseShieldBonus($formula) {
        // Extraire le nombre du bonus (ex: "2" -> 2)
        if (preg_match('/^(\d+)/', $formula, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
    
    /**
     * Récupère les bonus d'équipement du NPC (méthode d'instance)
     */
    public function getMyEquipmentBonuses() {
        // Pour l'instant, retourner des bonus à 0
        // Cette méthode peut être étendue pour calculer les vrais bonus d'équipement
        // quand les colonnes de bonus seront ajoutées aux tables armor et weapons
        return [
            'strength' => 0,
            'dexterity' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'wisdom' => 0,
            'charisma' => 0
        ];
    }
    
    /**
     * Récupère les bonus temporaires du NPC (méthode d'instance)
     */
    public function getMyTemporaryBonuses() {
        // Pour l'instant, retourner des bonus à 0
        // Cette méthode peut être étendue pour calculer les vrais bonus temporaires
        return [
            'strength' => 0,
            'dexterity' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'wisdom' => 0,
            'charisma' => 0
        ];
    }
    
    /**
     * Calcule les caractéristiques totales du NPC (méthode d'instance)
     */
    public function getMyTotalAbilities() {
        // Récupérer les objets nécessaires
        $raceObject = Race::findById($this->race_id);
        $abilityImprovements = $this->getCharacterAbilityImprovements();
        $equipmentBonuses = $this->getMyEquipmentBonuses();
        $temporaryBonuses = $this->getMyTemporaryBonuses();
        
        // Convertir les améliorations en tableau simple
        $abilityImprovementsArray = [
            'strength' => 0,
            'dexterity' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'wisdom' => 0,
            'charisma' => 0
        ];
        
        foreach ($abilityImprovements as $improvement) {
            $ability = $improvement['ability'];
            if (isset($abilityImprovementsArray[$ability])) {
                $abilityImprovementsArray[$ability] = $improvement['improvement'];
            }
        }
        
        // Calculer les totaux
        return [
            'strength' => $this->strength + ($raceObject->strength_bonus ?? 0) + $abilityImprovementsArray['strength'] + $equipmentBonuses['strength'] + $temporaryBonuses['strength'],
            'dexterity' => $this->dexterity + ($raceObject->dexterity_bonus ?? 0) + $abilityImprovementsArray['dexterity'] + $equipmentBonuses['dexterity'] + $temporaryBonuses['dexterity'],
            'constitution' => $this->constitution + ($raceObject->constitution_bonus ?? 0) + $abilityImprovementsArray['constitution'] + $equipmentBonuses['constitution'] + $temporaryBonuses['constitution'],
            'intelligence' => $this->intelligence + ($raceObject->intelligence_bonus ?? 0) + $abilityImprovementsArray['intelligence'] + $equipmentBonuses['intelligence'] + $temporaryBonuses['intelligence'],
            'wisdom' => $this->wisdom + ($raceObject->wisdom_bonus ?? 0) + $abilityImprovementsArray['wisdom'] + $equipmentBonuses['wisdom'] + $temporaryBonuses['wisdom'],
            'charisma' => $this->charisma + ($raceObject->charisma_bonus ?? 0) + $abilityImprovementsArray['charisma'] + $equipmentBonuses['charisma'] + $temporaryBonuses['charisma']
        ];
    }
    
    /**
     * Calcule les modificateurs de caractéristiques du NPC (méthode d'instance)
     */
    public function getMyAbilityModifiers() {
        $totalAbilities = $this->getMyTotalAbilities();
        
        return [
            'strength' => floor(($totalAbilities['strength'] - 10) / 2),
            'dexterity' => floor(($totalAbilities['dexterity'] - 10) / 2),
            'constitution' => floor(($totalAbilities['constitution'] - 10) / 2),
            'intelligence' => floor(($totalAbilities['intelligence'] - 10) / 2),
            'wisdom' => floor(($totalAbilities['wisdom'] - 10) / 2),
            'charisma' => floor(($totalAbilities['charisma'] - 10) / 2)
        ];
    }
    
    /**
     * Détecte l'armure et le bouclier équipés (méthode d'instance)
     */
    public function getMyEquippedArmorAndShield() {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $equippedArmor = null;
            $equippedShield = null;
            
            // Récupérer l'armure équipée via le lien direct
            $stmt = $pdo->prepare("
                SELECT a.*
                FROM items i
                JOIN armor a ON i.armor_id = a.id
                WHERE i.owner_type = 'npc' AND i.owner_id = ? AND i.is_equipped = 1 AND i.object_type = 'armor'
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            $equippedArmor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Récupérer le bouclier équipé via le lien direct
            $stmt = $pdo->prepare("
                SELECT s.*
                FROM items i
                JOIN shields s ON i.shield_id = s.id
                WHERE i.owner_type = 'npc' AND i.owner_id = ? AND i.is_equipped = 1 AND i.object_type = 'shield'
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            $equippedShield = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'armor' => $equippedArmor,
                'shield' => $equippedShield
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors de la détection de l'armure et du bouclier: " . $e->getMessage());
            return [
                'armor' => null,
                'shield' => null
            ];
        }
    }
    
    /**
     * Ajouter un équipement à ce NPC (méthode d'instance)
     */
    public function addMyNpcEquipment($sceneId, $equipmentData, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, scene_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $this->id,
                $sceneId,
                $equipmentData['magical_item_id'],
                $equipmentData['item_name'],
                $equipmentData['item_type'],
                $equipmentData['item_description'],
                $equipmentData['item_source'],
                $equipmentData['quantity'],
                $equipmentData['equipped'],
                $equipmentData['notes'],
                $equipmentData['obtained_from']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'équipement au NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les détails d'un équipement de NPC (méthode statique)
     */
    public static function getNpcEquipmentWithDetails($equipmentId, $characterId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT ne.*, sn.name as npc_name, sn.place_id, s.title as scene_title
                FROM npc_equipment ne
                JOIN place_npcs sn ON ne.npc_id = sn.id AND ne.scene_id = sn.place_id
                JOIN places s ON sn.place_id = s.id
                WHERE ne.id = ? AND sn.npc_character_id = ?
            ");
            $stmt->execute([$equipmentId, $characterId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de l'équipement: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajouter un équipement à ce NPC (méthode d'instance)
     */
    public function addMyEquipmentToNpc($placeId, $equipmentData, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("INSERT INTO npc_equipment (npc_id, place_id, magical_item_id, item_name, item_type, item_description, item_source, quantity, equipped, notes, obtained_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $this->id,
                $placeId,
                $equipmentData['magical_item_id'],
                $equipmentData['item_name'],
                $equipmentData['item_type'],
                $equipmentData['item_description'],
                $equipmentData['item_source'],
                $equipmentData['quantity'],
                $equipmentData['equipped'],
                $equipmentData['notes'],
                $equipmentData['obtained_from']
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'équipement au NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un équipement de ce NPC (méthode d'instance)
     */
    public function removeMyEquipmentFromNpc($equipmentId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("DELETE FROM npc_equipment WHERE id = ? AND npc_id = ?");
            return $stmt->execute([$equipmentId, $this->id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'équipement du NPC: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer l'équipement de ce NPC (méthode d'instance)
     */
    public function getMyNpcEquipment($pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT ne.*, sn.name as npc_name, sn.place_id, s.title as scene_title
                FROM npc_equipment ne
                JOIN place_npcs sn ON ne.npc_id = sn.id AND ne.scene_id = sn.place_id
                JOIN places s ON sn.place_id = s.id
                WHERE ne.npc_id = ?
                ORDER BY ne.obtained_at DESC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement du NPC: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Déséquiper un objet de ce NPC (méthode d'instance)
     */
    public function unequipMyItem($itemName, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            
            // Récupérer l'ID de l'objet
            $stmt = $pdo->prepare("SELECT id FROM items WHERE display_name = ? AND owner_type = 'npc' AND owner_id = ?");
            $stmt->execute([$itemName, $this->id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                return ['success' => false, 'message' => 'Objet non trouvé'];
            }
            
            // Déséquiper l'objet
            $stmt = $pdo->prepare("UPDATE items SET is_equipped = 0 WHERE id = ?");
            $result = $stmt->execute([$item['id']]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Objet déséquipé avec succès'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors du déséquipement'];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors du déséquipement: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de base de données'];
        }
    }
    
    /**
     * Récupérer les détails d'un équipement de ce NPC (méthode d'instance)
     */
    public function getMyNpcEquipmentWithDetails($equipmentId, $pdo = null)
    {
        try {
            $pdo = $pdo ?: \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT ne.*, sn.name as npc_name, sn.place_id, s.title as scene_title
                FROM npc_equipment ne
                JOIN place_npcs sn ON ne.npc_id = sn.id AND ne.scene_id = sn.place_id
                JOIN places s ON sn.place_id = s.id
                WHERE ne.id = ? AND ne.npc_id = ?
            ");
            $stmt->execute([$equipmentId, $this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de l'équipement: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Équiper un objet sur un NPC (méthode statique)
     */
    public static function equipItem($npcId, $itemName, $itemType, $slot)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Déséquiper l'objet actuellement dans ce slot
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'npc' AND owner_id = ? AND equipped_slot = ?
            ");
            $stmt->execute([$npcId, $slot]);
            
            // Équiper le nouvel objet
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 1, equipped_slot = ?
                WHERE owner_type = 'npc' AND owner_id = ? AND display_name = ? AND object_type = ?
            ");
            
            $result = $stmt->execute([$slot, $npcId, $itemName, $itemType]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Objet équipé avec succès'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de l\'équipement'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'équipement du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'équipement: ' . $e->getMessage()];
        }
    }
    
    /**
     * Équiper un objet par son ID (méthode statique)
     */
    public static function equipItemById($itemId, $slot = null)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            // Récupérer les informations de l'item
            $stmt = $pdo->prepare("
                SELECT owner_id, object_type, display_name 
                FROM items 
                WHERE id = ? AND owner_type = 'npc'
            ");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                return ['success' => false, 'message' => 'Objet non trouvé'];
            }
            
            $npcId = $item['owner_id'];
            $itemType = $item['object_type'];
            $itemName = $item['display_name'];
            
            // Si aucun slot n'est spécifié, déterminer automatiquement le slot approprié
            if (!$slot) {
                require_once 'classes/SlotManager.php';
                $slot = SlotManager::getSlotForObjectType($itemType, $itemName);
                
                if (!$slot) {
                    return ['success' => false, 'message' => 'Type d\'objet non supporté pour l\'équipement'];
                }
            }
            
            // Vérifier la compatibilité du slot avec le type d'objet
            require_once 'classes/SlotManager.php';
            if (!SlotManager::isSlotCompatible($slot, $itemType)) {
                return ['success' => false, 'message' => 'Slot non compatible avec ce type d\'objet'];
            }
            
            // Déséquiper l'objet actuellement dans ce slot
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'npc' AND owner_id = ? AND equipped_slot = ?
            ");
            $stmt->execute([$npcId, $slot]);
            
            // Équiper le nouvel objet
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 1, equipped_slot = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$slot, $itemId]);
            
            if ($result) {
                $slotName = SlotManager::getSlotDisplayName($slot);
                return ['success' => true, 'message' => "Objet équipé avec succès dans le slot: $slotName"];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de l\'équipement'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'équipement du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'équipement: ' . $e->getMessage()];
        }
    }
    
    /**
     * Déséquiper un objet d'un NPC (méthode statique)
     */
    public static function unequipItem($npcId, $itemName)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'npc' AND owner_id = ? AND display_name = ?
            ");
            
            $result = $stmt->execute([$npcId, $itemName]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Objet déséquipé avec succès'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors du déséquipement'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors du déséquipement du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du déséquipement: ' . $e->getMessage()];
        }
    }
    
    /**
     * Déséquiper un objet par son ID (méthode statique)
     */
    public static function unequipItemById($itemId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE id = ? AND owner_type = 'npc'
            ");
            
            $result = $stmt->execute([$itemId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Objet déséquipé avec succès'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors du déséquipement'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors du déséquipement du PNJ: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du déséquipement: ' . $e->getMessage()];
        }
    }

    /**
     * Mettre à jour la photo de profil d'un PNJ
     */
    public static function updateProfilePhoto($npcId, $photoPath) {
        $pdo = getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE npcs SET profile_photo = ? WHERE id = ?");
            return $stmt->execute([$photoPath, $npcId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la photo de profil du PNJ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer la race du NPC (méthode d'instance)
     */
    public function getRace()
    {
        return Race::findById($this->race_id);
    }

    /**
     * Récupérer la classe du NPC (méthode d'instance)
     */
    public function getClass()
    {
        return Classe::findById($this->class_id);
    }

    /**
     * Récupérer l'archétype du NPC (méthode d'instance)
     */
    public function getArchetype()
    {
        if ($this->archetype_id) {
            require_once 'Classe.php';
            return Classe::getArchetypeById($this->archetype_id);
        }
        return null;
    }

    /**
     * Récupérer l'équipement du NPC sous forme d'objets Item (méthode d'instance)
     */
    public function getEquipment()
    {
        $pdo = \Database::getInstance()->getPdo();
        $items = [];
        
        try {
            // Ne pas filtrer uniquement par owner_type car certains items peuvent être enregistrés avec owner_type='player'
            // alors qu'ils appartiennent à un NPC. Utiliser uniquement owner_id et vérifier que c'est bien un NPC.
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    -- Utiliser le nom spécifique selon le type d'équipement, sinon display_name
                    COALESCE(
                        CASE 
                            WHEN i.object_type = 'armor' THEN a.name
                            WHEN i.object_type = 'weapon' THEN w.name
                            WHEN i.object_type = 'shield' THEN s.name
                            ELSE NULL
                        END,
                        i.display_name
                    ) as name,
                    i.display_name as original_display_name,
                    i.object_type as type,
                    i.type_precis as subtype,
                    i.description,
                    i.is_equipped as equipped,
                    i.equipped_slot,
                    i.quantity,
                    i.notes,
                    i.armor_id,
                    i.weapon_id,
                    i.shield_id,
                    i.magical_item_id,
                    i.poison_id,
                    a.name as armor_name,
                    a.ac_formula as armor_ac_formula,
                    w.name as weapon_name,
                    w.damage as weapon_damage,
                    w.properties as weapon_properties,
                    s.name as shield_name,
                    s.ac_formula as shield_ac_formula,
                    mi.nom as magical_item_name,
                    mi.description as magical_item_description,
                    p.nom as poison_name,
                    p.description as poison_description
                FROM items i
                LEFT JOIN armor a ON i.armor_id = a.id
                LEFT JOIN weapons w ON i.weapon_id = w.id
                LEFT JOIN shields s ON i.shield_id = s.id AND i.object_type = 'shield'
                LEFT JOIN magical_items mi ON i.magical_item_id = mi.id
                LEFT JOIN poisons p ON i.poison_id = p.id
                WHERE i.owner_id = ? AND EXISTS (SELECT 1 FROM npcs WHERE id = i.owner_id)
                ORDER BY i.created_at ASC
            ");
            
            $stmt->execute([$this->id]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item = new stdClass();
                $item->id = $row['id'];
                $item->name = $row['name'];
                $item->type = $row['type'];
                $item->subtype = $row['subtype'];
                $item->description = $row['description'];
                $item->equipped = (bool)$row['equipped'];
                $item->equipped_slot = $row['equipped_slot'];
                $item->quantity = $row['quantity'];
                $item->notes = $row['notes'];
                
                // Ajouter les détails spécifiques selon le type
                if ($row['armor_id']) {
                    $item->armor_name = $row['armor_name'];
                    $item->armor_ac_formula = $row['armor_ac_formula'];
                }
                if ($row['weapon_id']) {
                    $item->weapon_name = $row['weapon_name'];
                    $item->weapon_damage = $row['weapon_damage'];
                    $item->weapon_properties = $row['weapon_properties'];
                }
                if ($row['shield_id']) {
                    $item->shield_name = $row['shield_name'];
                    $item->shield_ac_formula = $row['shield_ac_formula'];
                }
                if ($row['magical_item_id']) {
                    $item->magical_item_name = $row['magical_item_name'];
                    $item->magical_item_description = $row['magical_item_description'];
                }
                if ($row['poison_id']) {
                    $item->poison_name = $row['poison_name'];
                    $item->poison_description = $row['poison_description'];
                }
                
                $items[] = $item;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement du NPC: " . $e->getMessage());
        }
        
        return $items;
    }

    /**
     * Récupérer l'armure équipée du NPC (méthode d'instance)
     */
    public function getMyEquippedArmor()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.display_name as item_name,
                    i.object_type as item_type,
                    i.type_precis as item_subtype,
                    i.description as item_description,
                    i.is_equipped as equipped,
                    i.equipped_slot,
                    i.quantity,
                    i.notes,
                    a.name as armor_name,
                    a.ac_formula as armor_ac_formula
                FROM items i
                LEFT JOIN armor a ON i.armor_id = a.id
                WHERE i.owner_type = 'player' 
                AND i.owner_id = ? 
                AND i.object_type = 'armor' 
                AND i.is_equipped = 1
                LIMIT 1
            ");
            
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $item = new stdClass();
                $item->id = $result['id'];
                $item->name = $result['item_name'];
                $item->type = $result['item_type'];
                $item->subtype = $result['item_subtype'];
                $item->description = $result['item_description'];
                $item->equipped = (bool)$result['equipped'];
                $item->equipped_slot = $result['equipped_slot'];
                $item->quantity = $result['quantity'];
                $item->notes = $result['notes'];
                $item->armor_name = $result['armor_name'];
                $item->armor_ac_formula = $result['armor_ac_formula'];
                return $item;
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'armure équipée du NPC: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier si le NPC est un barbare (méthode d'instance)
     */
    public function isBarbarian()
    {
        $classObject = Classe::findById($this->class_id);
        return $classObject && strpos(strtolower($classObject->name), 'barbare') !== false;
    }


    /**
     * Obtenir l'utilisation de la rage (pour les barbares)
     */
    public function getRageUsage()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT used, max_uses
                FROM npc_rage_usage
                WHERE npc_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: ['used' => 0, 'max_uses' => 0];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation de la rage: " . $e->getMessage());
            return ['used' => 0, 'max_uses' => 0];
        }
    }

    /**
     * Récupérer les données de rage du NPC (méthode d'instance)
     */
    public function getMyRageData()
    {
        if (!$this->isBarbarian()) {
            return null;
        }

        require_once 'Classe.php';
        $classObj = Classe::findById($this->class_id);
        $maxRages = $classObj ? $classObj->getMaxRages($this->level) : 0;
        $rageUsage = $this->getRageUsage();
        $usedRages = is_array($rageUsage) ? $rageUsage['used'] : $rageUsage;

        return [
            'max' => $maxRages,
            'used' => $usedRages,
            'available' => $maxRages - $usedRages
        ];
    }

    /**
     * Vérifier si le NPC peut lancer des sorts (méthode d'instance)
     */
    public function canCastSpells()
    {
        
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM class_evolution 
            WHERE class_id = ? AND (
                cantrips_known > 0 OR 
                spells_known > 0 OR 
                spell_slots_1st > 0 OR 
                spell_slots_2nd > 0 OR 
                spell_slots_3rd > 0 OR 
                spell_slots_4th > 0 OR 
                spell_slots_5th > 0 OR 
                spell_slots_6th > 0 OR 
                spell_slots_7th > 0 OR 
                spell_slots_8th > 0 OR 
                spell_slots_9th > 0
            )
        ");
        $stmt->execute([$this->class_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Récupérer le bouclier équipé du NPC (méthode d'instance)
     */
    public function getMyEquippedShield()
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.display_name as item_name,
                    i.object_type as item_type,
                    i.type_precis as item_subtype,
                    i.description as item_description,
                    i.is_equipped as equipped,
                    i.equipped_slot,
                    i.quantity,
                    i.notes,
                    s.name as shield_name,
                    s.ac_formula as shield_ac_formula
                FROM items i
                LEFT JOIN shields s ON i.shield_id = s.id
                WHERE i.owner_type = 'player' 
                AND i.owner_id = ? 
                AND i.object_type = 'shield' 
                AND i.is_equipped = 1
                LIMIT 1
            ");
            
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $item = new stdClass();
                $item->id = $result['id'];
                $item->name = $result['item_name'];
                $item->type = $result['item_type'];
                $item->subtype = $result['item_subtype'];
                $item->description = $result['item_description'];
                $item->equipped = (bool)$result['equipped'];
                $item->equipped_slot = $result['equipped_slot'];
                $item->quantity = $result['quantity'];
                $item->notes = $result['notes'];
                $item->shield_name = $result['shield_name'];
                $item->shield_ac_formula = $result['shield_ac_formula'];
                return $item;
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du bouclier équipé du NPC: " . $e->getMessage());
            return null;
        }
    }
}
?>
