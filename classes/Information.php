<?php

/**
 * Classe Information - Gestion des informations
 * 
 * Une information contient un titre, description, niveau de confidentialité,
 * statut et une image optionnelle
 */
class Information
{
    private $pdo;
    
    public $id;
    public $titre;
    public $description;
    public $niveau_confidentialite;
    public $statut;
    public $image_path;
    public $created_by;
    public $created_at;
    public $updated_at;
    
    // Niveaux de confidentialité disponibles
    const NIVEAUX = [
        'archi_connu' => 'Archi connu',
        'connu' => 'Connu',
        'connu_du_milieu' => 'Connu du milieu',
        'confidentiel' => 'Confidentiel',
        'secret' => 'Secret'
    ];
    
    // Statuts disponibles
    const STATUTS = [
        'vraie' => 'Vraie',
        'fausse' => 'Fausse',
        'a_verifier' => 'À vérifier'
    ];
    
    public function __construct($data = [], PDO $pdo = null)
    {
        $this->pdo = $pdo ?: getPdo();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    protected function hydrate($data)
    {
        $this->id = $data['id'] ?? null;
        $this->titre = $data['titre'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->niveau_confidentialite = $data['niveau_confidentialite'] ?? 'connu';
        $this->statut = $data['statut'] ?? 'a_verifier';
        $this->image_path = $data['image_path'] ?? null;
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    /**
     * Sauvegarde l'information en base de données
     */
    public function save()
    {
        try {
            if ($this->id) {
                $stmt = $this->pdo->prepare("
                    UPDATE informations 
                    SET titre = ?, description = ?, niveau_confidentialite = ?, statut = ?, image_path = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->titre,
                    $this->description,
                    $this->niveau_confidentialite,
                    $this->statut,
                    $this->image_path,
                    $this->id
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO informations (titre, description, niveau_confidentialite, statut, image_path, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $this->titre,
                    $this->description,
                    $this->niveau_confidentialite,
                    $this->statut,
                    $this->image_path,
                    $this->created_by
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde de l'information: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouve une information par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM informations WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new self($data, $pdo) : null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'information: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère toutes les informations
     */
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM informations ORDER BY titre ASC");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $informations = [];
            foreach ($results as $data) {
                $informations[] = new self($data, $pdo);
            }
            return $informations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les informations créées par un utilisateur
     */
    public static function getByUser($user_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM informations WHERE created_by = ? ORDER BY titre ASC");
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $informations = [];
            foreach ($results as $data) {
                $informations[] = new self($data, $pdo);
            }
            return $informations;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Supprime l'information
     */
    public function delete()
    {
        try {
            // Supprimer l'image si elle existe
            if ($this->image_path && file_exists($this->image_path)) {
                @unlink($this->image_path);
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM informations WHERE id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'information: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un accès joueur à l'information
     */
    public function addPlayerAccess($player_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO information_access (information_id, access_type, player_id)
                VALUES (?, 'player', ?)
                ON DUPLICATE KEY UPDATE id = id
            ");
            $stmt->execute([$this->id, $player_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'accès joueur: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un accès PNJ à l'information
     */
    public function addNpcAccess($npc_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO information_access (information_id, access_type, npc_id)
                VALUES (?, 'npc', ?)
                ON DUPLICATE KEY UPDATE id = id
            ");
            $stmt->execute([$this->id, $npc_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'accès PNJ: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un accès monstre à l'information
     */
    public function addMonsterAccess($monster_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO information_access (information_id, access_type, npc_id)
                VALUES (?, 'monster', ?)
                ON DUPLICATE KEY UPDATE id = id
            ");
            $stmt->execute([$this->id, $monster_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'accès monstre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un accès groupe à l'information pour un niveau spécifique
     */
    public function addGroupAccess($groupe_id, $niveau)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO information_access (information_id, access_type, groupe_id, niveau)
                VALUES (?, 'group', ?, ?)
                ON DUPLICATE KEY UPDATE id = id
            ");
            $stmt->execute([$this->id, $groupe_id, $niveau]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'accès groupe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute plusieurs niveaux d'accès pour un groupe
     */
    public function addGroupAccessLevels($groupe_id, $niveaux)
    {
        try {
            foreach ($niveaux as $niveau) {
                $this->addGroupAccess($groupe_id, $niveau);
            }
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout des niveaux d'accès groupe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime tous les accès d'un groupe à l'information
     */
    public function removeGroupAccess($groupe_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM information_access 
                WHERE information_id = ? AND access_type = 'group' AND groupe_id = ?
            ");
            $stmt->execute([$this->id, $groupe_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'accès groupe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les accès à l'information
     */
    public function getAccesses()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    ia.*,
                    c.name as player_name,
                    pn.name as npc_name,
                    dm.name as monster_name,
                    g.name as groupe_name
                FROM information_access ia
                LEFT JOIN characters c ON ia.access_type = 'player' AND ia.player_id = c.id
                LEFT JOIN place_npcs pn ON (ia.access_type = 'npc' OR ia.access_type = 'monster') AND ia.npc_id = pn.id
                LEFT JOIN dnd_monsters dm ON ia.access_type = 'monster' AND pn.monster_id = dm.id
                LEFT JOIN groupes g ON ia.access_type = 'group' AND ia.groupe_id = g.id
                WHERE ia.information_id = ?
                ORDER BY ia.access_type, ia.groupe_id, ia.niveau, ia.created_at
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des accès: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les niveaux d'accès pour un groupe spécifique
     */
    public function getGroupAccessLevels($groupe_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT niveau 
                FROM information_access 
                WHERE information_id = ? AND access_type = 'group' AND groupe_id = ?
                ORDER BY niveau ASC
            ");
            $stmt->execute([$this->id, $groupe_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des niveaux d'accès: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Supprime tous les accès à l'information
     */
    public function clearAccesses()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM information_access WHERE information_id = ?");
            $stmt->execute([$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression des accès: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload d'une image pour l'information
     */
    public function uploadImage($file)
    {
        $upload_dir = 'uploads/informations/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload du fichier'];
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé'];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'info_' . $this->id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Supprimer l'ancienne image si elle existe
            if ($this->image_path && file_exists($this->image_path)) {
                @unlink($this->image_path);
            }
            
            $this->image_path = $filepath;
            return ['success' => true, 'path' => $filepath];
        } else {
            return ['success' => false, 'error' => 'Impossible de déplacer le fichier'];
        }
    }
    
    /**
     * Ajoute une sous-information à cette information
     */
    public function addSubInformation($child_information_id, $ordre = null)
    {
        try {
            // Vérifier qu'on n'essaie pas d'ajouter soi-même comme sous-information
            if ($child_information_id == $this->id) {
                return false;
            }
            
            // Déterminer l'ordre si non fourni
            if ($ordre === null) {
                $stmt = $this->pdo->prepare("
                    SELECT COALESCE(MAX(ordre), 0) + 1 as next_ordre 
                    FROM information_informations 
                    WHERE parent_information_id = ?
                ");
                $stmt->execute([$this->id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $ordre = $result['next_ordre'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO information_informations (parent_information_id, child_information_id, ordre)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE ordre = ?
            ");
            $stmt->execute([$this->id, $child_information_id, $ordre, $ordre]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de la sous-information: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retire une sous-information de cette information
     */
    public function removeSubInformation($child_information_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM information_informations 
                WHERE parent_information_id = ? AND child_information_id = ?
            ");
            $stmt->execute([$this->id, $child_information_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la sous-information: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Définit les sous-informations de cette information (remplace toutes les existantes)
     */
    public function setSubInformations($child_information_ids)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer toutes les liaisons existantes
            $stmt = $this->pdo->prepare("DELETE FROM information_informations WHERE parent_information_id = ?");
            $stmt->execute([$this->id]);
            
            // Ajouter les nouvelles sous-informations
            $ordre = 1;
            foreach ($child_information_ids as $child_id) {
                if ($child_id != $this->id) { // Éviter les boucles
                    $stmt = $this->pdo->prepare("
                        INSERT INTO information_informations (parent_information_id, child_information_id, ordre)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$this->id, $child_id, $ordre++]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la définition des sous-informations: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les sous-informations de cette information (triées par ordre)
     */
    public function getSubInformations()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, ii.ordre
                FROM informations i
                INNER JOIN information_informations ii ON i.id = ii.child_information_id
                WHERE ii.parent_information_id = ?
                ORDER BY ii.ordre ASC, i.titre ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des sous-informations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Déplace une sous-information vers le haut dans l'ordre
     */
    public function moveSubInformationUp($child_information_id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer l'ordre actuel de la sous-information
            $stmt = $this->pdo->prepare("
                SELECT ordre FROM information_informations 
                WHERE parent_information_id = ? AND child_information_id = ?
            ");
            $stmt->execute([$this->id, $child_information_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current || $current['ordre'] <= 1) {
                $this->pdo->rollBack();
                return false; // Déjà en première position
            }
            
            $current_ordre = $current['ordre'];
            $target_ordre = $current_ordre - 1;
            
            // Échanger avec la sous-information qui a l'ordre précédent
            $stmt = $this->pdo->prepare("
                UPDATE information_informations 
                SET ordre = CASE 
                    WHEN ordre = ? THEN ?
                    WHEN ordre = ? THEN ?
                    ELSE ordre
                END
                WHERE parent_information_id = ? AND ordre IN (?, ?)
            ");
            $stmt->execute([$current_ordre, $target_ordre, $target_ordre, $current_ordre, $this->id, $current_ordre, $target_ordre]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors du déplacement vers le haut: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Déplace une sous-information vers le bas dans l'ordre
     */
    public function moveSubInformationDown($child_information_id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer l'ordre actuel de la sous-information
            $stmt = $this->pdo->prepare("
                SELECT ordre FROM information_informations 
                WHERE parent_information_id = ? AND child_information_id = ?
            ");
            $stmt->execute([$this->id, $child_information_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Récupérer le nombre maximum d'ordres
            $stmt = $this->pdo->prepare("
                SELECT MAX(ordre) as max_ordre 
                FROM information_informations 
                WHERE parent_information_id = ?
            ");
            $stmt->execute([$this->id]);
            $max = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$max || $current['ordre'] >= $max['max_ordre']) {
                $this->pdo->rollBack();
                return false; // Déjà en dernière position
            }
            
            $current_ordre = $current['ordre'];
            $target_ordre = $current_ordre + 1;
            
            // Échanger avec la sous-information qui a l'ordre suivant
            $stmt = $this->pdo->prepare("
                UPDATE information_informations 
                SET ordre = CASE 
                    WHEN ordre = ? THEN ?
                    WHEN ordre = ? THEN ?
                    ELSE ordre
                END
                WHERE parent_information_id = ? AND ordre IN (?, ?)
            ");
            $stmt->execute([$current_ordre, $target_ordre, $target_ordre, $current_ordre, $this->id, $current_ordre, $target_ordre]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors du déplacement vers le bas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si cette information peut être ajoutée comme sous-information
     * (évite les boucles infinies)
     */
    public function canBeSubInformationOf($potential_parent_id)
    {
        if ($potential_parent_id == $this->id) {
            return false; // Ne peut pas être sa propre sous-information
        }
        
        // Vérifier récursivement si le parent potentiel est déjà une sous-information de cette information
        // (ce qui créerait une boucle)
        try {
            $visited = [];
            $to_check = [$potential_parent_id];
            
            while (!empty($to_check)) {
                $current_id = array_shift($to_check);
                
                if ($current_id == $this->id) {
                    return false; // Boucle détectée
                }
                
                if (in_array($current_id, $visited)) {
                    continue; // Éviter les boucles infinies
                }
                
                $visited[] = $current_id;
                
                // Récupérer les parents de l'information actuelle
                $stmt = $this->pdo->prepare("
                    SELECT parent_information_id 
                    FROM information_informations 
                    WHERE child_information_id = ?
                ");
                $stmt->execute([$current_id]);
                $parents = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($parents as $parent_id) {
                    if (!in_array($parent_id, $visited)) {
                        $to_check[] = $parent_id;
                    }
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de la boucle: " . $e->getMessage());
            return false;
        }
    }
}


