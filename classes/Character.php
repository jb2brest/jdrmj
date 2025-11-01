<?php

/**
 * Classe Character - Gestion des personnages D&D
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux personnages :
 * - Création, lecture, mise à jour, suppression
 * - Gestion des statistiques et compétences
 * - Gestion des sorts et emplacements de sorts
 * - Gestion de l'équipement
 * - Gestion de l'expérience et des niveaux
 * - Gestion des capacités spéciales
 */
class Character
{
    private $pdo;
    
    // Propriétés du personnage
    public $id;
    public $user_id;
    public $name;
    public $race_id;
    public $class_id;
    public $class_archetype_id;
    public $background_id;
    public $level;
    public $experience_points;
    
    // Statistiques de base
    public $strength;
    public $dexterity;
    public $constitution;
    public $intelligence;
    public $wisdom;
    public $charisma;
    
    // Informations de combat
    public $armor_class;
    public $initiative;
    public $speed;
    public $hit_points_max;
    public $hit_points_current;
    
    // Compétences et proficiens
    public $proficiency_bonus;
    public $saving_throws;
    public $skills;
    public $languages;
    
    // Équipement et trésor
    public $equipment;
    public $gold;
    public $silver;
    public $copper;
    
    // Informations personnelles
    public $background;
    public $alignment;
    public $personality_traits;
    public $ideals;
    public $bonds;
    public $flaws;
    
    // Sorts
    public $spells_known;
    public $spell_slots;
    
    // Métadonnées
    public $profile_photo;
    public $is_equipped;
    public $equipment_locked;
    public $character_locked;
    public $created_at;
    public $updated_at;
    
    // Relations
    public $race_name;
    public $class_name;
    public $background_name;
    
    // Informations de campagne
    public $campaign_id;
    public $campaign_status;
    public $campaign_title;
    
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
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Créer un nouveau personnage
     */
    public static function create(array $data, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            // Générer les compétences et langues de base si elles ne sont pas fournies
            $skills = $data['skills'] ?? null;
            $languages = $data['languages'] ?? null;
            
            if ($skills === null || $languages === null) {
                // Créer un objet temporaire pour générer les compétences et langues
                $tempCharacter = new self($pdo, [
                    'race_id' => $data['race_id'],
                    'class_id' => $data['class_id']
                ]);
                
                if ($skills === null) {
                    // Utiliser les compétences choisies par le joueur si disponibles
                    $skills = json_encode($data['selected_skills']);
                }
                if ($languages === null) {
                    // Utiliser les langues choisies par le joueur si disponibles
                    $languages = json_encode($data['selected_languages']);
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO characters (
                    user_id, name, race_id, class_id, background_id, level, experience_points,
                    strength, dexterity, constitution, intelligence, wisdom, charisma,
                    armor_class, initiative, speed, hit_points_max, hit_points_current,
                    proficiency_bonus, saving_throws, skills, languages,
                    equipment, gold, silver, copper,
                    background, alignment, personality_traits, ideals, bonds, flaws,
                    spells_known, spell_slots, profile_photo,
                    is_equipped, equipment_locked, character_locked
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['name'] ?? 'Nouveau Personnage',
                $data['race_id'],
                $data['class_id'],
                $data['background_id'] ?? null,
                $data['level'] ?? 1,
                $data['experience_points'] ?? 0,
                $data['strength'] ?? 10,
                $data['dexterity'] ?? 10,
                $data['constitution'] ?? 10,
                $data['intelligence'] ?? 10,
                $data['wisdom'] ?? 10,
                $data['charisma'] ?? 10,
                $data['armor_class'] ?? 10,
                $data['initiative'] ?? 0,
                $data['speed'] ?? 30,
                $data['hit_points_max'] ?? 8,
                $data['hit_points_current'] ?? 8,
                $data['proficiency_bonus'] ?? 2,
                $data['saving_throws'] ?? null,
                $skills,
                $languages,
                $data['equipment'] ?? null,
                $data['gold'] ?? 0,
                $data['silver'] ?? 0,
                $data['copper'] ?? 0,
                $data['background'] ?? null,
                $data['alignment'] ?? 'Neutre',
                $data['personality_traits'] ?? null,
                $data['ideals'] ?? null,
                $data['bonds'] ?? null,
                $data['flaws'] ?? null,
                $data['spells_known'] ?? null,
                $data['spell_slots'] ?? null,
                $data['profile_photo'] ?? null,
                $data['is_equipped'] ?? 0,
                $data['equipment_locked'] ?? 0,
                $data['character_locked'] ?? 0
            ]);
            
            $characterId = $pdo->lastInsertId();
            return self::findById($characterId, $pdo);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un personnage par ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, r.name as race_name, cl.name as class_name, b.name as background_name
                FROM characters c
                LEFT JOIN races r ON c.race_id = r.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN backgrounds b ON c.background_id = b.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new self($pdo, $data) : null;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du personnage: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver tous les personnages d'un utilisateur
     */
    public static function findByUserId($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, r.name as race_name, cl.name as class_name, b.name as background_name,
                       ca.campaign_id, ca.status as campaign_status, camp.title as campaign_title
                FROM characters c
                LEFT JOIN races r ON c.race_id = r.id
                LEFT JOIN classes cl ON c.class_id = cl.id
                LEFT JOIN backgrounds b ON c.background_id = b.id
                LEFT JOIN campaign_applications ca ON c.id = ca.character_id AND ca.status = 'approved'
                LEFT JOIN campaigns camp ON ca.campaign_id = camp.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            $characters = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $characters[] = new self($pdo, $data);
            }
            
            return $characters;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche des personnages: " . $e->getMessage());
            return [];
        }
    }
    
    
    /**
     * Mettre à jour le personnage
     */
    public function update(array $data)
    {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (property_exists($this, $key) && $key !== 'id') {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $this->id;
            
            $stmt = $this->pdo->prepare("
                UPDATE characters 
                SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            
            if ($result) {
                // Mettre à jour les propriétés de l'objet
                foreach ($data as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer le personnage
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
            return $stmt->execute([$this->id, $this->user_id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer complètement le personnage avec toutes ses données associées
     * 
     * @return bool True si succès, false sinon
     */
    public function deleteCompletely()
    {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Supprimer l'équipement du personnage (table items)
            $stmt = $this->pdo->prepare("DELETE FROM items WHERE owner_type = 'player' AND owner_id = ?");
            $stmt->execute([$this->id]);
            
            // 2. Supprimer les sorts appris
            $stmt = $this->pdo->prepare("DELETE FROM character_spells WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 3. Supprimer les capacités du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_capabilities WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 4. Supprimer les améliorations d'aptitudes
            $stmt = $this->pdo->prepare("DELETE FROM character_ability_improvements WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 5. Supprimer l'utilisation de rage
            $stmt = $this->pdo->prepare("DELETE FROM character_rage_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 6. Supprimer les compétences du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_skills WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 7. Supprimer les langues du personnage
            $stmt = $this->pdo->prepare("DELETE FROM character_languages WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 8. Supprimer les sessions de création (utilise user_id)
            $stmt = $this->pdo->prepare("DELETE FROM character_creation_sessions WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            
            // 9. Supprimer l'utilisation des emplacements de sorts
            $stmt = $this->pdo->prepare("DELETE FROM spell_slots_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 10. Supprimer l'utilisation de rage (table générale)
            $stmt = $this->pdo->prepare("DELETE FROM rage_usage WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 11. Supprimer les candidatures aux campagnes
            $stmt = $this->pdo->prepare("DELETE FROM campaign_applications WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 12. Retirer le personnage de tous les lieux
            $stmt = $this->pdo->prepare("DELETE FROM place_players WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // 13. Retirer le personnage de tous les groupes
            $stmt = $this->pdo->prepare("DELETE FROM groupe_membres WHERE member_id = ? AND member_type = 'pj'");
            $stmt->execute([$this->id]);
            
            // 14. Supprimer le personnage lui-même
            $stmt = $this->pdo->prepare("DELETE FROM characters WHERE id = ? AND user_id = ?");
            $stmt->execute([$this->id, $this->user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Personnage non trouvé ou permissions insuffisantes");
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression complète du personnage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si le personnage appartient à un utilisateur
     */
    public function belongsToUser($userId)
    {
        return $this->user_id == $userId;
    }
    
    /**
     * Calculer le bonus de compétence
     */
    public function getProficiencyBonus()
    {
        return 2 + floor(($this->level - 1) / 4);
    }
    
    /**
     * Calculer le modificateur d'une caractéristique
     */
    public function getAbilityModifier($ability)
    {
        if (!property_exists($this, $ability)) {
            return 0;
        }
        
        return floor(($this->$ability - 10) / 2);
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
     * Obtenir les sorts de ce personnage
     * 
     * @return array Tableau des sorts du personnage
     */
    public function getCharacterSpells()
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, cs.prepared 
            FROM character_spells cs
            JOIN spells s ON cs.spell_id = s.id
            WHERE cs.character_id = ?
            ORDER BY s.level, s.name
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }





    /**
     * Calculer la classe d'armure d'un personnage (version étendue)
     * 
     * @param array|null $character Données du personnage (optionnel, utilise $this si non fourni)
     * @param array|object|null $equippedArmor Armure équipée (optionnel, récupéré automatiquement si non fourni)
     * @param array|object|null $equippedShield Bouclier équipé (optionnel, récupéré automatiquement si non fourni)
     * @return int Classe d'armure calculée
     */
    public function getCA()
    {
        // Récupérer automatiquement l'armure et le bouclier équipés si non fournis
        $equippedArmor = $this->getMyEquippedArmor();
        $equippedShield = $this->getMyEquippedShield();
        $dexterityModifier = $this->getDexterityModifier();
        $class_id = $this->class_id;
        $race_id = $this->race_id;
        $constitution = $this->constitution;
        
        // Récupérer le nom de la classe pour vérifier si c'est un barbare
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();
        $isBarbarian = $class && strpos(strtolower($class['name']), 'barbare') !== false;
        
        if ($equippedArmor) {
            // CA basée sur l'armure équipée
            // Gérer à la fois les objets et les tableaux
            $acFormula = is_object($equippedArmor) ? $equippedArmor->armor_ac_formula : $equippedArmor['ac_formula'];
            
            if ($acFormula) {
                // Parser la formule de CA
                if (preg_match('/(\d+)\s*\+\s*Mod\.Dex(?: \(max \+(\d+)\))?/', $acFormula, $matches)) {
                    $baseAC = (int)$matches[1];
                    $maxDexBonus = isset($matches[2]) ? (int)$matches[2] : null;
                    
                    $dexBonus = $dexterityModifier;
                    if ($maxDexBonus !== null) {
                        $dexBonus = min($dexBonus, $maxDexBonus);
                    }
                    
                    $ac = $baseAC + $dexBonus;
                } else {
                    // CA fixe (armures lourdes)
                    $ac = (int)$acFormula;
                }
            } else {
                // Pas de formule, AC de base
                $ac = 10 + $dexterityModifier;
            }
        } else {
            // Pas d'armure
            if ($isBarbarian) {
                // Pour les barbares sans armure : CA = 10 + modificateur de Dextérité + modificateur de Constitution
                
                $constitutionModifier = $this->getAbilityModifier('constitution');
                $ac = 10 + $dexterityModifier + $constitutionModifier;
            } else {
                // Pour les autres classes : CA = 10 + modificateur de Dextérité
                $ac = 10 + $dexterityModifier;
            }
        }
        
        // Ajouter le bonus de bouclier si équipé
        if ($equippedShield) {
            // Gérer à la fois les objets et les tableaux
            $shieldFormula = is_object($equippedShield) ? $equippedShield->shield_ac_formula : (isset($equippedShield['ac_bonus']) ? $equippedShield['ac_bonus'] : $equippedShield['ac_formula']);
            
            if ($shieldFormula) {
                // Parser le bonus du bouclier (ex: "2" -> 2)
                if (preg_match('/^(\d+)/', $shieldFormula, $matches)) {
                    $shieldBonus = (int)$matches[1];
                    $ac += $shieldBonus;
                } elseif (is_numeric($shieldFormula)) {
                    // Si c'est déjà un nombre direct
                    $ac += (int)$shieldFormula;
                }
            }
        }
        
        return $ac;
    }
    

    
    /**
     * Calculer les points de vie maximum
     */
    public function calculateMaxHitPoints()
    {
        $conMod = $this->getConstitutionModifier();
        $hitDie = 8; // TODO: Récupérer depuis la classe
        
        // Points de vie au niveau 1
        $hp = $hitDie + $conMod;
        
        // Points de vie pour les niveaux suivants
        for ($i = 2; $i <= $this->level; $i++) {
            $hp += floor($hitDie / 2) + 1 + $conMod; // Moyenne arrondie vers le haut
        }
        
        return max(1, $hp);
    }
    

    
    /**
     * Calculer le niveau basé sur l'expérience
     */
    private function calculateLevelFromExperience($xp)
    {
        $levels = [
            0, 300, 900, 2700, 6500, 14000, 23000, 34000, 48000, 64000,
            85000, 100000, 120000, 140000, 165000, 195000, 225000, 265000, 305000, 355000
        ];
        
        for ($level = 20; $level >= 1; $level--) {
            if ($xp >= $levels[$level - 1]) {
                return $level;
            }
        }
        
        return 1;
    }
    
    /**
     * Obtenir les sorts du personnage
     */
    public function getSpells()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, cs.prepared, cs.known
                FROM character_spells cs
                JOIN spells s ON cs.spell_id = s.id
                WHERE cs.character_id = ?
                ORDER BY s.level, s.name
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des sorts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajouter un sort au personnage
     */
    public function addSpell($spellId, $prepared = false, $known = true)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_spells (character_id, spell_id, prepared, known)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE prepared = ?, known = ?
            ");
            
            return $stmt->execute([$this->id, $spellId, $prepared, $known, $prepared, $known]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retirer un sort du personnage
     */
    public function removeSpell($spellId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_spells WHERE character_id = ? AND spell_id = ?");
            return $stmt->execute([$this->id, $spellId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'utilisation des emplacements de sorts
     */
    public function getSpellSlotsUsage()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT level, used
                FROM character_spell_slots_usage
                WHERE character_id = ?
                ORDER BY level
            ");
            $stmt->execute([$this->id]);
            
            $usage = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usage[$row['level']] = $row['used'];
            }
            
            return $usage;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisation des sorts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Utiliser un emplacement de sort
     */
    public function useSpellSlot($level)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_spell_slots_usage (character_id, level, used)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE used = used + 1
            ");
            
            return $stmt->execute([$this->id, $level]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'utilisation de l'emplacement de sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Libérer un emplacement de sort
     */
    public function freeSpellSlot($level)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE character_spell_slots_usage
                SET used = GREATEST(0, used - 1)
                WHERE character_id = ? AND level = ?
            ");
            
            return $stmt->execute([$this->id, $level]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la libération de l'emplacement de sort: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réinitialiser l'utilisation des emplacements de sorts
     */
    public function resetSpellSlotsUsage()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_spell_slots_usage WHERE character_id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation des emplacements de sorts: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Équiper un objet
     */
    public function equipItem($itemName, $itemType, $slot)
    {
        try {
            // Déséquiper l'objet actuellement dans ce slot
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND equipped_slot = ?
            ");
            $stmt->execute([$this->id, $slot]);
            
            // Équiper le nouvel objet
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 1, equipped_slot = ?
                WHERE owner_type = 'player' AND owner_id = ? AND display_name = ? AND object_type = ?
            ");
            
            return $stmt->execute([$slot, $this->id, $itemName, $itemType]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'équipement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Déséquiper un objet
     */
    public function unequipItem($itemName)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE items 
                SET is_equipped = 0, equipped_slot = NULL 
                WHERE owner_type = 'player' AND owner_id = ? AND display_name = ?
            ");
            
            return $stmt->execute([$this->id, $itemName]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors du déséquipement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'utilisation de la rage (pour les barbares)
     */
    public function getRageUsage()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT used, max_uses
                FROM character_rage_usage
                WHERE character_id = ?
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
     * Utiliser la rage
     */
    public function useRage()
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_rage_usage (character_id, used, max_uses)
                VALUES (?, 1, 2)
                ON DUPLICATE KEY UPDATE used = used + 1
            ");
            
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'utilisation de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Libérer la rage
     */
    public function freeRage()
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE character_rage_usage
                SET used = GREATEST(0, used - 1)
                WHERE character_id = ?
            ");
            
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la libération de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réinitialiser l'utilisation de la rage
     */
    public function resetRageUsage()
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE character_rage_usage SET used = 0 WHERE character_id = ?");
            return $stmt->execute([$this->id]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation de la rage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les capacités spéciales du personnage
     */
    public function getCapabilities()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT cc.*, c.name, c.description, c.type_id, c.source_type, ct.name as type_name
                FROM character_capabilities cc
                JOIN capabilities c ON cc.capability_id = c.id
                LEFT JOIN capability_types ct ON c.type_id = ct.id
                WHERE cc.character_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si aucune capacité n'est assignée, générer automatiquement les capacités de base
            if (empty($result)) {
                return $this->generateBaseCapabilities();
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des capacités: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Générer automatiquement les capacités de base du personnage
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
                    $capability['source'] = $capability['source_type'];
                }
            }
            
            return $capabilities;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la génération des capacités de base du personnage: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajouter une capacité au personnage
     */
    public function addCapability($capabilityId, $source = 'class', $sourceId = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO character_capabilities (character_id, capability_id, source, source_id)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE source = ?, source_id = ?
            ");
            
            return $stmt->execute([$this->id, $capabilityId, $source, $sourceId, $source, $sourceId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de la capacité: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retirer une capacité du personnage
     */
    public function removeCapability($capabilityId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM character_capabilities WHERE character_id = ? AND capability_id = ?");
            return $stmt->execute([$this->id, $capabilityId]);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la capacité: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir les améliorations de caractéristiques du personnage
     */
    public function getAbilityImprovements()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT strength_bonus, dexterity_bonus, constitution_bonus, 
                       intelligence_bonus, wisdom_bonus, charisma_bonus
                FROM character_ability_improvements
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return [
                    'strength' => 0,
                    'dexterity' => 0,
                    'constitution' => 0,
                    'intelligence' => 0,
                    'wisdom' => 0,
                    'charisma' => 0
                ];
            }
            
            return [
                'strength' => (int)$row['strength_bonus'],
                'dexterity' => (int)$row['dexterity_bonus'],
                'constitution' => (int)$row['constitution_bonus'],
                'intelligence' => (int)$row['intelligence_bonus'],
                'wisdom' => (int)$row['wisdom_bonus'],
                'charisma' => (int)$row['charisma_bonus']
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des améliorations: " . $e->getMessage());
            return [
                'strength' => 0,
                'dexterity' => 0,
                'constitution' => 0,
                'intelligence' => 0,
                'wisdom' => 0,
                'charisma' => 0
            ];
        }
    }
    
    /**
     * Sauvegarder les améliorations de caractéristiques
     */
    public function saveAbilityImprovements($improvements)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer les améliorations existantes
            $stmt = $this->pdo->prepare("DELETE FROM character_ability_improvements WHERE character_id = ?");
            $stmt->execute([$this->id]);
            
            // Ajouter les nouvelles améliorations
            $stmt = $this->pdo->prepare("
                INSERT INTO character_ability_improvements 
                (character_id, strength_bonus, dexterity_bonus, constitution_bonus, 
                 intelligence_bonus, wisdom_bonus, charisma_bonus)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->id,
                (int)($improvements['strength'] ?? 0),
                (int)($improvements['dexterity'] ?? 0),
                (int)($improvements['constitution'] ?? 0),
                (int)($improvements['intelligence'] ?? 0),
                (int)($improvements['wisdom'] ?? 0),
                (int)($improvements['charisma'] ?? 0)
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la sauvegarde des améliorations: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculer les caractéristiques finales avec les améliorations
     */
    public function calculateFinalAbilities($abilityImprovements = null)
    {
        if ($abilityImprovements === null) {
            $abilityImprovements = $this->getAbilityImprovements();
        }
        
        // Récupérer les bonus raciaux
        $race = Race::findById($this->race_id);
        $racialBonuses = [
            'strength' => $race ? ($race->strength_bonus ?? 0) : 0,
            'dexterity' => $race ? ($race->dexterity_bonus ?? 0) : 0,
            'constitution' => $race ? ($race->constitution_bonus ?? 0) : 0,
            'intelligence' => $race ? ($race->intelligence_bonus ?? 0) : 0,
            'wisdom' => $race ? ($race->wisdom_bonus ?? 0) : 0,
            'charisma' => $race ? ($race->charisma_bonus ?? 0) : 0,
        ];
        
        $abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        $finalAbilities = [];
        
        foreach ($abilities as $ability) {
            $base = $this->$ability;
            $improvement = $abilityImprovements[$ability] ?? 0;
            $racialBonus = $racialBonuses[$ability] ?? 0;
            $finalAbilities[$ability] = $base + $improvement + $racialBonus;
        }
        
        return $finalAbilities;
    }
        
    
    /**
     * Obtenir les informations de campagne du personnage
     */
    public function getCampaignInfo()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ca.*, c.title as campaign_title, c.description as campaign_description
                FROM campaign_applications ca
                JOIN campaigns c ON ca.campaign_id = c.id
                WHERE ca.character_id = ? AND ca.status = 'approved'
            ");
            $stmt->execute([$this->id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations de campagne: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si le personnage est approuvé dans une campagne
     * 
     * @return bool True si le personnage a un campaign_id non vide et un campaign_status 'approved', false sinon
     */
    public function isApprovedInCampaign()
    {
        return !empty($this->campaign_id) && $this->campaign_status === 'approved';
    }
      








    /**
     * Mettre à jour les points de vie actuels d'un personnage
     * 
     * @param int $newHitPoints Nouveaux points de vie actuels
     * @return bool Succès de l'opération
     */
    public function updateHitPoints($newHitPoints)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE characters SET hit_points_current = ? WHERE id = ?");
            $success = $stmt->execute([$newHitPoints, $this->id]);
            
            if ($success) {
                $this->hit_points_current = $newHitPoints;
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points de vie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour les points d'expérience d'un personnage
     * 
     * @param int $newExperiencePoints Nouveaux points d'expérience
     * @return bool Succès de l'opération
     */
    public function updateExperiencePoints($newExperiencePoints)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE characters SET experience_points = ? WHERE id = ?");
            $success = $stmt->execute([$newExperiencePoints, $this->id]);
            
            if ($success) {
                $this->experience_points = $newExperiencePoints;
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des points d'expérience: " . $e->getMessage());
            return false;
        }
    }





    /**
     * Récupérer tous les poisons de ce personnage depuis la table items
     * 
     * @return array Liste des poisons du personnage
     */
    public function getCharacterPoisons()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, p.nom as poison_nom, p.type as poison_type, p.description as poison_description, p.source as poison_source
                FROM items i
                JOIN poisons p ON i.poison_id = p.csv_id
                WHERE i.owner_type = 'player' AND i.owner_id = ? 
                AND i.poison_id IS NOT NULL
                ORDER BY i.obtained_at DESC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des poisons du personnage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les personnages d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param bool $equippedOnly Si true, ne retourne que les personnages équipés
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des personnages
     */
    public static function getCharactersByUser($userId, $equippedOnly = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT id, name FROM characters WHERE user_id = ?";
            if ($equippedOnly) {
                $sql .= " AND is_equipped = 1";
            }
            $sql .= " ORDER BY name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si l'équipement de départ a été choisi pour ce personnage
     * 
     * @return int Nombre d'objets d'équipement de départ
     */
    public function getStartingEquipmentCount()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM items 
                WHERE owner_type = 'player' AND owner_id = ? 
                AND obtained_from = 'Équipement de départ'
            ");
            $stmt->execute([$this->id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'équipement de départ: " . $e->getMessage());
            return 0;
        }
    }



    /**
     * Récupérer l'équipement d'un personnage
     * 
     * @return array Liste de l'équipement
     */
    public function getCharacterEquipment()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    display_name as item_name,
                    object_type as item_type,
                    type_precis as item_subtype,
                    description as item_description,
                    is_equipped as equipped,
                    equipped_slot,
                    quantity,
                    notes,
                    obtained_at,
                    obtained_from
                FROM items 
                WHERE owner_type = 'player' AND owner_id = ? 
                ORDER BY display_name ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Getters de base pour les propriétés essentielles
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function getClassId()
    {
        return $this->class_id;
    }
    
    public function getIsEquipped()
    {
        return $this->is_equipped ?? false;
    }

    /**
     * Obtenir l'archetype du personnage
     * @return array|null L'archetype avec ses détails ou null si aucun
     */
    public function getArchetype()
    {
        try {
            if (!$this->class_archetype_id) {
                return null;
            }

            $stmt = $this->pdo->prepare("
                SELECT ca.*, c.name as class_name 
                FROM class_archetypes ca 
                JOIN classes c ON ca.class_id = c.id 
                WHERE ca.id = ?
            ");
            $stmt->execute([$this->class_archetype_id]);
            
            $archetype = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($archetype) {
                // Ajouter le type d'archetype selon la classe
                require_once 'Classe.php';
                $archetype['archetype_type'] = Classe::getArchetypeType($archetype['class_name']);
            }
            
            return $archetype;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'archetype: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Récupérer les personnages d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des personnages
     */
    public static function getByUserId($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? ORDER BY name");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Mettre à jour la photo de profil de ce personnage
     */
    public function updateProfilePhoto($photoPath) {
        try {
            $stmt = $this->pdo->prepare("UPDATE characters SET profile_photo = ? WHERE id = ?");
            $success = $stmt->execute([$photoPath, $this->id]);
            if ($success) {
                $this->profile_photo = $photoPath;
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la photo de profil: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer les caractéristiques totales (base + race + améliorations + équipement + temporaires)
     */
    public function getMyTotalAbilities() {
        // Récupérer les objets nécessaires
        $raceObject = Race::findById($this->race_id);
        $abilityImprovements = $this->getAbilityImprovements();
        $equipmentBonuses = $this->getMyEquipmentBonuses();
        $temporaryBonuses = $this->getMyTemporaryBonuses();
        
        // Calculer les totaux
        return [
            'strength' => $this->strength + ($raceObject->strength_bonus ?? 0) + $abilityImprovements['strength'] + $equipmentBonuses['strength'] + $temporaryBonuses['strength'],
            'dexterity' => $this->dexterity + ($raceObject->dexterity_bonus ?? 0) + $abilityImprovements['dexterity'] + $equipmentBonuses['dexterity'] + $temporaryBonuses['dexterity'],
            'constitution' => $this->constitution + ($raceObject->constitution_bonus ?? 0) + $abilityImprovements['constitution'] + $equipmentBonuses['constitution'] + $temporaryBonuses['constitution'],
            'intelligence' => $this->intelligence + ($raceObject->intelligence_bonus ?? 0) + $abilityImprovements['intelligence'] + $equipmentBonuses['intelligence'] + $temporaryBonuses['intelligence'],
            'wisdom' => $this->wisdom + ($raceObject->wisdom_bonus ?? 0) + $abilityImprovements['wisdom'] + $equipmentBonuses['wisdom'] + $temporaryBonuses['wisdom'],
            'charisma' => $this->charisma + ($raceObject->charisma_bonus ?? 0) + $abilityImprovements['charisma'] + $equipmentBonuses['charisma'] + $temporaryBonuses['charisma']
        ];
    }

    /**
     * Calculer les modificateurs de caractéristiques basés sur les caractéristiques totales
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
     * Récupère les bonus d'équipement du personnage (méthode d'instance)
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
     * Récupère les bonus temporaires du personnage (méthode d'instance)
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
     * Récupérer l'équipement du personnage sous forme d'objets Item (méthode d'instance)
     */
    public function getEquipment()
    {
        $pdo = \Database::getInstance()->getPdo();
        $items = [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    i.id,
                    i.display_name as name,
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
                LEFT JOIN shields s ON i.shield_id = s.id
                LEFT JOIN magical_items mi ON i.magical_item_id = mi.id
                LEFT JOIN poisons p ON i.poison_id = p.id
                WHERE i.owner_type = 'player' AND i.owner_id = ?
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
            error_log("Erreur lors de la récupération de l'équipement: " . $e->getMessage());
        }
        
        return $items;
    }

    /**
     * Récupérer l'armure équipée du personnage (méthode d'instance)
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
            error_log("Erreur lors de la récupération de l'armure équipée: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer le bouclier équipé du personnage (méthode d'instance)
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
            error_log("Erreur lors de la récupération du bouclier équipé: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer la race du personnage (méthode d'instance)
     */
    public function getRace()
    {
        return Race::findById($this->race_id);
    }

    /**
     * Récupérer la classe du personnage (méthode d'instance)
     */
    public function getClass()
    {
        return Classe::findById($this->class_id);
    }


    /**
     * Calculer les attaques du personnage (méthode d'instance)
     */
    public function calculateMyCharacterAttacks()
    {
        $pdo = \Database::getInstance()->getPdo();
        $attacks = [];
        
        try {
            // 1. Récupérer les attaques personnalisées du personnage
            $stmt = $pdo->query("SHOW TABLES LIKE 'character_attacks'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        name as attack_name,
                        attack_type,
                        range_type,
                        range_value,
                        damage_dice,
                        damage_type,
                        attack_bonus,
                        damage_bonus,
                        description,
                        is_proficient,
                        'custom' as source_type
                    FROM character_attacks 
                    WHERE character_id = ? 
                    ORDER BY name ASC
                ");
                $stmt->execute([$this->id]);
                $customAttacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $attacks = array_merge($attacks, $customAttacks);
            }
            
            // 2. Récupérer les attaques des objets équipés (armes)
            $stmt = $pdo->prepare("
                SELECT 
                    i.id as item_id,
                    i.display_name as attack_name,
                    w.type as attack_type,
                    CASE 
                        WHEN w.properties LIKE '%lancer%' OR w.properties LIKE '%portée%' THEN 'ranged'
                        ELSE 'melee'
                    END as range_type,
                    CASE 
                        WHEN w.properties LIKE '%lancer%' THEN '20/60'
                        ELSE '5'
                    END as range_value,
                    w.damage as damage_dice,
                    CASE 
                        WHEN w.damage LIKE '%contondant%' THEN 'Contondant'
                        WHEN w.damage LIKE '%perforant%' THEN 'Perforant'
                        WHEN w.damage LIKE '%tranchant%' THEN 'Tranchant'
                        ELSE 'Contondant'
                    END as damage_type,
                    '0' as attack_bonus,
                    '0' as damage_bonus,
                    i.description,
                    '1' as is_proficient,
                    'equipped' as source_type
                FROM items i
                LEFT JOIN weapons w ON i.weapon_id = w.id
                WHERE i.owner_type = 'player' 
                AND i.owner_id = ? 
                AND i.is_equipped = 1
                AND i.object_type = 'weapon'
                AND w.id IS NOT NULL
                ORDER BY i.display_name ASC
            ");
            $stmt->execute([$this->id]);
            $equippedAttacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $attacks = array_merge($attacks, $equippedAttacks);
            
            return $attacks;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des attaques: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Vérifier si le personnage est un barbare (méthode d'instance)
     */
    public function isBarbarian()
    {
        $classObject = Classe::findById($this->class_id);
        return $classObject && strpos(strtolower($classObject->name), 'barbare') !== false;
    }

    /**
     * Récupérer les données de rage du personnage (méthode d'instance)
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
     * Vérifier si le personnage peut lancer des sorts (méthode d'instance)
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

}
