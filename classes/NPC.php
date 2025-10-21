<?php

class NPC {
    private $pdo;
    private $id;
    private $name;
    private $class_id;
    private $race_id;
    private $background_id;
    private $archetype_id;
    private $level;
    private $experience;
    private $strength;
    private $dexterity;
    private $constitution;
    private $intelligence;
    private $wisdom;
    private $charisma;
    private $hit_points;
    private $armor_class;
    private $speed;
    private $alignment;
    private $age;
    private $height;
    private $weight;
    private $eyes;
    private $skin;
    private $hair;
    private $backstory;
    private $personality_traits;
    private $ideals;
    private $bonds;
    private $flaws;
    private $starting_equipment;
    private $gold;
    private $spells;
    private $skills;
    private $languages;
    private $profile_photo;
    private $created_by;
    private $world_id;
    private $location_id;
    private $is_active;
    private $created_at;
    private $updated_at;

    public function __construct($pdo = null) {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getClassId() { return $this->class_id; }
    public function getRaceId() { return $this->race_id; }
    public function getBackgroundId() { return $this->background_id; }
    public function getArchetypeId() { return $this->archetype_id; }
    public function getLevel() { return $this->level; }
    public function getExperience() { return $this->experience; }
    public function getStrength() { return $this->strength; }
    public function getDexterity() { return $this->dexterity; }
    public function getConstitution() { return $this->constitution; }
    public function getIntelligence() { return $this->intelligence; }
    public function getWisdom() { return $this->wisdom; }
    public function getCharisma() { return $this->charisma; }
    public function getHitPoints() { return $this->hit_points; }
    public function getArmorClass() { return $this->armor_class; }
    public function getSpeed() { return $this->speed; }
    public function getAlignment() { return $this->alignment; }
    public function getAge() { return $this->age; }
    public function getHeight() { return $this->height; }
    public function getWeight() { return $this->weight; }
    public function getEyes() { return $this->eyes; }
    public function getSkin() { return $this->skin; }
    public function getHair() { return $this->hair; }
    public function getBackstory() { return $this->backstory; }
    public function getPersonalityTraits() { return $this->personality_traits; }
    public function getIdeals() { return $this->ideals; }
    public function getBonds() { return $this->bonds; }
    public function getFlaws() { return $this->flaws; }
    public function getStartingEquipment() { return $this->starting_equipment; }
    public function getGold() { return $this->gold; }
    public function getSpells() { return $this->spells; }
    public function getSkills() { return $this->skills; }
    public function getLanguages() { return $this->languages; }
    public function getProfilePhoto() { return $this->profile_photo; }
    public function getCreatedBy() { return $this->created_by; }
    public function getWorldId() { return $this->world_id; }
    public function getLocationId() { return $this->location_id; }
    public function getIsActive() { return $this->is_active; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setClassId($class_id) { $this->class_id = $class_id; }
    public function setRaceId($race_id) { $this->race_id = $race_id; }
    public function setBackgroundId($background_id) { $this->background_id = $background_id; }
    public function setArchetypeId($archetype_id) { $this->archetype_id = $archetype_id; }
    public function setLevel($level) { $this->level = $level; }
    public function setExperience($experience) { $this->experience = $experience; }
    public function setStrength($strength) { $this->strength = $strength; }
    public function setDexterity($dexterity) { $this->dexterity = $dexterity; }
    public function setConstitution($constitution) { $this->constitution = $constitution; }
    public function setIntelligence($intelligence) { $this->intelligence = $intelligence; }
    public function setWisdom($wisdom) { $this->wisdom = $wisdom; }
    public function setCharisma($charisma) { $this->charisma = $charisma; }
    public function setHitPoints($hit_points) { $this->hit_points = $hit_points; }
    public function setArmorClass($armor_class) { $this->armor_class = $armor_class; }
    public function setSpeed($speed) { $this->speed = $speed; }
    public function setAlignment($alignment) { $this->alignment = $alignment; }
    public function setAge($age) { $this->age = $age; }
    public function setHeight($height) { $this->height = $height; }
    public function setWeight($weight) { $this->weight = $weight; }
    public function setEyes($eyes) { $this->eyes = $eyes; }
    public function setSkin($skin) { $this->skin = $skin; }
    public function setHair($hair) { $this->hair = $hair; }
    public function setBackstory($backstory) { $this->backstory = $backstory; }
    public function setPersonalityTraits($personality_traits) { $this->personality_traits = $personality_traits; }
    public function setIdeals($ideals) { $this->ideals = $ideals; }
    public function setBonds($bonds) { $this->bonds = $bonds; }
    public function setFlaws($flaws) { $this->flaws = $flaws; }
    public function setStartingEquipment($starting_equipment) { $this->starting_equipment = $starting_equipment; }
    public function setGold($gold) { $this->gold = $gold; }
    public function setSpells($spells) { $this->spells = $spells; }
    public function setSkills($skills) { $this->skills = $skills; }
    public function setLanguages($languages) { $this->languages = $languages; }
    public function setProfilePhoto($profile_photo) { $this->profile_photo = $profile_photo; }
    public function setCreatedBy($created_by) { $this->created_by = $created_by; }
    public function setWorldId($world_id) { $this->world_id = $world_id; }
    public function setLocationId($location_id) { $this->location_id = $location_id; }
    public function setIsActive($is_active) { $this->is_active = $is_active; }

    // Méthodes de base de données
    public function create($data) {
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
                ':name' => $data['name'],
                ':class_id' => $data['class_id'],
                ':race_id' => $data['race_id'],
                ':background_id' => $data['background_id'] ?? null,
                ':archetype_id' => $data['archetype_id'] ?? null,
                ':level' => $data['level'] ?? 1,
                ':experience' => $data['experience'] ?? 0,
                ':strength' => $data['strength'],
                ':dexterity' => $data['dexterity'],
                ':constitution' => $data['constitution'],
                ':intelligence' => $data['intelligence'],
                ':wisdom' => $data['wisdom'],
                ':charisma' => $data['charisma'],
                ':hit_points' => $data['hit_points'],
                ':armor_class' => $data['armor_class'],
                ':speed' => $data['speed'],
                ':alignment' => $data['alignment'],
                ':age' => $data['age'] ?? null,
                ':height' => $data['height'] ?? null,
                ':weight' => $data['weight'] ?? null,
                ':eyes' => $data['eyes'] ?? null,
                ':skin' => $data['skin'] ?? null,
                ':hair' => $data['hair'] ?? null,
                ':backstory' => $data['backstory'] ?? null,
                ':personality_traits' => $data['personality_traits'] ?? null,
                ':ideals' => $data['ideals'] ?? null,
                ':bonds' => $data['bonds'] ?? null,
                ':flaws' => $data['flaws'] ?? null,
                ':starting_equipment' => $data['starting_equipment'] ?? null,
                ':gold' => $data['gold'] ?? 0,
                ':spells' => $data['spells'] ?? null,
                ':skills' => $data['skills'] ?? null,
                ':languages' => $data['languages'] ?? null,
                ':profile_photo' => $data['profile_photo'] ?? null,
                ':created_by' => $data['created_by'],
                ':world_id' => $data['world_id'],
                ':location_id' => $data['location_id'] ?? null,
                ':is_active' => $data['is_active'] ?? true
            ]);

            if ($result) {
                $this->id = $this->pdo->lastInsertId();
                return $this->id;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du PNJ: " . $e->getMessage());
            return false;
        }
    }

    public function load($id) {
        try {
            $sql = "SELECT * FROM npcs WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $this->populateFromData($data);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement du PNJ: " . $e->getMessage());
            return false;
        }
    }

    public function update($data) {
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
                ':name' => $data['name'],
                ':class_id' => $data['class_id'],
                ':race_id' => $data['race_id'],
                ':background_id' => $data['background_id'] ?? null,
                ':archetype_id' => $data['archetype_id'] ?? null,
                ':level' => $data['level'] ?? 1,
                ':experience' => $data['experience'] ?? 0,
                ':strength' => $data['strength'],
                ':dexterity' => $data['dexterity'],
                ':constitution' => $data['constitution'],
                ':intelligence' => $data['intelligence'],
                ':wisdom' => $data['wisdom'],
                ':charisma' => $data['charisma'],
                ':hit_points' => $data['hit_points'],
                ':armor_class' => $data['armor_class'],
                ':speed' => $data['speed'],
                ':alignment' => $data['alignment'],
                ':age' => $data['age'] ?? null,
                ':height' => $data['height'] ?? null,
                ':weight' => $data['weight'] ?? null,
                ':eyes' => $data['eyes'] ?? null,
                ':skin' => $data['skin'] ?? null,
                ':hair' => $data['hair'] ?? null,
                ':backstory' => $data['backstory'] ?? null,
                ':personality_traits' => $data['personality_traits'] ?? null,
                ':ideals' => $data['ideals'] ?? null,
                ':bonds' => $data['bonds'] ?? null,
                ':flaws' => $data['flaws'] ?? null,
                ':starting_equipment' => $data['starting_equipment'] ?? null,
                ':gold' => $data['gold'] ?? 0,
                ':spells' => $data['spells'] ?? null,
                ':skills' => $data['skills'] ?? null,
                ':languages' => $data['languages'] ?? null,
                ':profile_photo' => $data['profile_photo'] ?? null,
                ':world_id' => $data['world_id'],
                ':location_id' => $data['location_id'] ?? null,
                ':is_active' => $data['is_active'] ?? true
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du PNJ: " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        try {
            $sql = "DELETE FROM npcs WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $this->id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du PNJ: " . $e->getMessage());
            return false;
        }
    }

    private function populateFromData($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->class_id = $data['class_id'];
        $this->race_id = $data['race_id'];
        $this->background_id = $data['background_id'];
        $this->archetype_id = $data['archetype_id'];
        $this->level = $data['level'];
        $this->experience = $data['experience'];
        $this->strength = $data['strength'];
        $this->dexterity = $data['dexterity'];
        $this->constitution = $data['constitution'];
        $this->intelligence = $data['intelligence'];
        $this->wisdom = $data['wisdom'];
        $this->charisma = $data['charisma'];
        $this->hit_points = $data['hit_points'];
        $this->armor_class = $data['armor_class'];
        $this->speed = $data['speed'];
        $this->alignment = $data['alignment'];
        $this->age = $data['age'];
        $this->height = $data['height'];
        $this->weight = $data['weight'];
        $this->eyes = $data['eyes'];
        $this->skin = $data['skin'];
        $this->hair = $data['hair'];
        $this->backstory = $data['backstory'];
        $this->personality_traits = $data['personality_traits'];
        $this->ideals = $data['ideals'];
        $this->bonds = $data['bonds'];
        $this->flaws = $data['flaws'];
        $this->starting_equipment = $data['starting_equipment'];
        $this->gold = $data['gold'];
        $this->spells = $data['spells'];
        $this->skills = $data['skills'];
        $this->languages = $data['languages'];
        $this->profile_photo = $data['profile_photo'];
        $this->created_by = $data['created_by'];
        $this->world_id = $data['world_id'];
        $this->location_id = $data['location_id'];
        $this->is_active = $data['is_active'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
    }

    // Méthodes statiques
    public static function getAll($pdo, $filters = []) {
        try {
            $sql = "SELECT n.*, c.name as class_name, r.name as race_name, b.name as background_name, 
                           ca.name as archetype_name, u.username as created_by_name, w.name as world_name
                    FROM npcs n
                    LEFT JOIN classes c ON n.class_id = c.id
                    LEFT JOIN races r ON n.race_id = r.id
                    LEFT JOIN backgrounds b ON n.background_id = b.id
                    LEFT JOIN class_archetypes ca ON n.archetype_id = ca.id
                    LEFT JOIN users u ON n.created_by = u.id
                    LEFT JOIN worlds w ON n.world_id = w.id
                    WHERE 1=1";

            $params = [];

            if (!empty($filters['world_id'])) {
                $sql .= " AND n.world_id = :world_id";
                $params[':world_id'] = $filters['world_id'];
            }

            if (!empty($filters['created_by'])) {
                $sql .= " AND n.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }

            if (!empty($filters['is_active'])) {
                $sql .= " AND n.is_active = :is_active";
                $params[':is_active'] = $filters['is_active'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (n.name LIKE :search OR c.name LIKE :search OR r.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $sql .= " ORDER BY n.name ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des PNJ: " . $e->getMessage());
            return [];
        }
    }

    public static function getById($pdo, $id) {
        try {
            $sql = "SELECT n.*, c.name as class_name, r.name as race_name, b.name as background_name, 
                           ca.name as archetype_name, u.username as created_by_name, w.name as world_name
                    FROM npcs n
                    LEFT JOIN classes c ON n.class_id = c.id
                    LEFT JOIN races r ON n.race_id = r.id
                    LEFT JOIN backgrounds b ON n.background_id = b.id
                    LEFT JOIN class_archetypes ca ON n.archetype_id = ca.id
                    LEFT JOIN users u ON n.created_by = u.id
                    LEFT JOIN worlds w ON n.world_id = w.id
                    WHERE n.id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du PNJ: " . $e->getMessage());
            return false;
        }
    }

    public static function count($pdo, $filters = []) {
        try {
            $sql = "SELECT COUNT(*) FROM npcs n WHERE 1=1";
            $params = [];

            if (!empty($filters['world_id'])) {
                $sql .= " AND n.world_id = :world_id";
                $params[':world_id'] = $filters['world_id'];
            }

            if (!empty($filters['created_by'])) {
                $sql .= " AND n.created_by = :created_by";
                $params[':created_by'] = $filters['created_by'];
            }

            if (!empty($filters['is_active'])) {
                $sql .= " AND n.is_active = :is_active";
                $params[':is_active'] = $filters['is_active'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des PNJ: " . $e->getMessage());
            return 0;
        }
    }
}
?>
