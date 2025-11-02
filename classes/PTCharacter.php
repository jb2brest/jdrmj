<?php

/**
 * Classe PTCharacter - Gestion des personnages temporaires
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux personnages temporaires :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des étapes de création
 * - Gestion des choix d'équipement
 */

class PTCharacter
{
    private $pdo;
    
    // Propriétés du personnage temporaire
    public $id;
    public $user_id;
    public $character_type;
    public $step;
    public $class_id;
    public $race_id;
    public $background_id;
    public $name;
    public $level;
    public $experience;
    public $alignment;
    public $personality_traits;
    public $ideals;
    public $bonds;
    public $flaws;
    public $backstory;
    public $age;
    public $height;
    public $weight;
    public $eyes;
    public $skin;
    public $hair;
    public $profile_photo;
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    public $hit_points_max;
    public $hit_points_current;
    public $armor_class;
    public $speed;
    public $proficiency_bonus;
    public $gold;
    public $silver;
    public $copper;
    public $selected_skills;
    public $selected_languages;
    public $selected_equipment;
    public $is_equipped;
    public $equipment_locked;
    public $character_locked;
    public $place_id; // Pour les NPCs uniquement : ID du lieu où le PNJ sera ajouté
    public $created_at;
    public $updated_at;
    
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
     */
    private function getPdo()
    {
        return $this->pdo ?: \Database::getInstance()->getPdo();
    }
    
    /**
     * Hydratation de l'objet avec les données
     */
    private function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Créer un nouveau personnage temporaire
     */
    public function create()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO PT_characters 
                (user_id, character_type, step, class_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $this->user_id,
                $this->character_type,
                $this->step,
                $this->class_id
            ]);
            
            if ($result) {
                $this->id = $pdo->lastInsertId();
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création PTCharacter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un personnage temporaire par ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM PT_characters WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erreur recherche PTCharacter: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les personnages temporaires d'un utilisateur
     */
    public static function findByUserId($user_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM PT_characters WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $characters = [];
            foreach ($results as $data) {
                $characters[] = new self($pdo, $data);
            }
            
            return $characters;
            
        } catch (PDOException $e) {
            error_log("Erreur recherche PTCharacter par user: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mettre à jour le personnage temporaire
     */
    public function update()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE PT_characters 
                SET user_id = ?, character_type = ?, step = ?, class_id = ?, race_id = ?, 
                    background_id = ?, name = ?, level = ?, experience = ?, alignment = ?,
                    personality_traits = ?, ideals = ?, bonds = ?, flaws = ?, backstory = ?,
                    age = ?, height = ?, weight = ?, eyes = ?, skin = ?, hair = ?, 
                    profile_photo = ?, strength = ?, dexterity = ?, constitution = ?, 
                    intelligence = ?, wisdom = ?, charisma = ?, hit_points_max = ?, 
                    hit_points_current = ?, armor_class = ?, speed = ?, proficiency_bonus = ?,
                    gold = ?, silver = ?, copper = ?, selected_skills = ?, selected_languages = ?,
                    selected_equipment = ?, is_equipped = ?, equipment_locked = ?, 
                    character_locked = ?, place_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $this->user_id, $this->character_type, $this->step, $this->class_id, $this->race_id,
                $this->background_id, $this->name, $this->level, $this->experience, $this->alignment,
                $this->personality_traits, $this->ideals, $this->bonds, $this->flaws, $this->backstory,
                $this->age, $this->height, $this->weight, $this->eyes, $this->skin, $this->hair,
                $this->profile_photo, $this->strength, $this->dexterity, $this->constitution,
                $this->intelligence, $this->wisdom, $this->charisma, $this->hit_points_max,
                $this->hit_points_current, $this->armor_class, $this->speed, $this->proficiency_bonus,
                $this->gold, $this->silver, $this->copper, $this->selected_skills, $this->selected_languages,
                $this->selected_equipment, $this->is_equipped, $this->equipment_locked,
                $this->character_locked, $this->place_id, $this->id
            ]);
            
            if ($result) {
                $pdo->commit();
                return true;
            }
            
            $pdo->rollBack();
            return false;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur mise à jour PTCharacter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le personnage temporaire et toutes ses données associées
     * 
     * @return bool True si succès, false sinon
     */
    public function delete()
    {
        $pdo = $this->getPdo();
        
        try {
            $pdo->beginTransaction();
            
            // 1. Supprimer les choix d'équipement temporaires
            $stmt = $pdo->prepare("DELETE FROM PT_equipment_choices WHERE pt_character_id = ?");
            $stmt->execute([$this->id]);
            
            // 2. Supprimer les items temporaires
            $stmt = $pdo->prepare("DELETE FROM PT_items WHERE pt_character_id = ?");
            $stmt->execute([$this->id]);
            
            // 3. Supprimer les capacités temporaires
            $stmt = $pdo->prepare("DELETE FROM PT_capabilities WHERE pt_character_id = ?");
            $stmt->execute([$this->id]);
            
            // 3. Supprimer le personnage temporaire lui-même
            // (Les clés étrangères avec ON DELETE CASCADE devraient supprimer automatiquement 
            // PT_equipment_choices et PT_capabilities, mais on les supprime explicitement pour être sûr)
            $stmt = $pdo->prepare("DELETE FROM PT_characters WHERE id = ? AND user_id = ?");
            $stmt->execute([$this->id, $this->user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Personnage temporaire non trouvé ou permissions insuffisantes");
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur suppression PTCharacter: " . $e->getMessage());
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
            'user_id' => $this->user_id,
            'character_type' => $this->character_type,
            'step' => $this->step,
            'class_id' => $this->class_id,
            'race_id' => $this->race_id,
            'background_id' => $this->background_id,
            'name' => $this->name,
            'level' => $this->level,
            'experience' => $this->experience,
            'alignment' => $this->alignment,
            'personality_traits' => $this->personality_traits,
            'ideals' => $this->ideals,
            'bonds' => $this->bonds,
            'flaws' => $this->flaws,
            'backstory' => $this->backstory,
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'eyes' => $this->eyes,
            'skin' => $this->skin,
            'hair' => $this->hair,
            'profile_photo' => $this->profile_photo,
            'strength' => $this->strength,
            'dexterity' => $this->dexterity,
            'constitution' => $this->constitution,
            'intelligence' => $this->intelligence,
            'wisdom' => $this->wisdom,
            'charisma' => $this->charisma,
            'hit_points_max' => $this->hit_points_max,
            'hit_points_current' => $this->hit_points_current,
            'armor_class' => $this->armor_class,
            'speed' => $this->speed,
            'proficiency_bonus' => $this->proficiency_bonus,
            'gold' => $this->gold,
            'silver' => $this->silver,
            'copper' => $this->copper,
            'selected_skills' => $this->selected_skills,
            'selected_languages' => $this->selected_languages,
            'selected_equipment' => $this->selected_equipment,
            'is_equipped' => $this->is_equipped,
            'equipment_locked' => $this->equipment_locked,
            'character_locked' => $this->character_locked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>
