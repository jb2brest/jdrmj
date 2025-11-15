<?php

/**
 * Classe Groupe - Gestion des groupes de PNJ, PJ et Monstres
 * 
 * Cette classe gère les groupes avec :
 * - Hiérarchie des membres (niveau 1 = dirigeant)
 * - Quartier général (QG) dans un lieu
 * - Membres de différents types (PNJ, PJ, Monstres)
 */
class Groupe
{
    private $pdo;
    
    // Propriétés du groupe
    public $id;
    public $name;
    public $description;
    public $crest_image; // Image du blason
    public $is_secret; // Groupe secret ou public
    public $headquarters_place_id; // ID du lieu QG
    public $created_by; // ID de l'utilisateur créateur
    public $created_at;
    public $updated_at;
    
    /**
     * Constructeur de la classe Groupe
     * 
     * @param array $data Données du groupe
     * @param PDO $pdo Instance PDO (optionnelle)
     */
    public function __construct($data = [], PDO $pdo = null)
    {
        $this->pdo = $pdo ?: getPdo();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate l'objet avec les données
     * 
     * @param array $data Données à hydrater
     */
    protected function hydrate($data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->crest_image = $data['crest_image'] ?? null;
        $this->is_secret = $data['is_secret'] ?? false;
        $this->headquarters_place_id = $data['headquarters_place_id'] ?? null;
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    /**
     * Sauvegarde le groupe en base de données
     * 
     * @return bool True si succès, false sinon
     */
    public function save()
    {
        try {
            if ($this->id) {
                // Mise à jour
                $stmt = $this->pdo->prepare("
                    UPDATE groupes 
                    SET name = ?, description = ?, crest_image = ?, is_secret = ?, headquarters_place_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->name,
                    $this->description,
                    $this->crest_image,
                    $this->is_secret ? 1 : 0,
                    $this->headquarters_place_id,
                    $this->id
                ]);
            } else {
                // Création
                $stmt = $this->pdo->prepare("
                    INSERT INTO groupes (name, description, crest_image, is_secret, headquarters_place_id, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $this->name,
                    $this->description,
                    $this->crest_image,
                    $this->is_secret ? 1 : 0,
                    $this->headquarters_place_id,
                    $this->created_by
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du groupe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime le groupe et tous ses membres
     * 
     * @return bool True si succès, false sinon
     */
    public function delete()
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer tous les membres du groupe
            $stmt = $this->pdo->prepare("DELETE FROM groupe_membres WHERE groupe_id = ?");
            $stmt->execute([$this->id]);
            
            // Supprimer le groupe
            $stmt = $this->pdo->prepare("DELETE FROM groupes WHERE id = ?");
            $stmt->execute([$this->id]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression du groupe: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute un membre au groupe
     * 
     * @param int $member_id ID du membre
     * @param string $member_type Type du membre ('pnj', 'pj', 'monster')
     * @param int $hierarchy_level Niveau hiérarchique (1 = dirigeant)
     * @param bool|null $is_secret Statut secret du membre (null = défaut selon le groupe)
     * @return bool True si succès, false sinon
     */
    public function addMember($member_id, $member_type, $hierarchy_level = 2, $is_secret = null)
    {
        try {
            // Déterminer le statut secret par défaut selon le groupe
            if ($is_secret === null) {
                $is_secret = $this->is_secret; // Par défaut, même statut que le groupe
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO groupe_membres (groupe_id, member_id, member_type, hierarchy_level, is_secret, joined_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$this->id, $member_id, $member_type, $hierarchy_level, $is_secret ? 1 : 0]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du membre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un membre du groupe
     * 
     * @param int $member_id ID du membre
     * @param string $member_type Type du membre
     * @return bool True si succès, false sinon
     */
    public function removeMember($member_id, $member_type)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM groupe_membres 
                WHERE groupe_id = ? AND member_id = ? AND member_type = ?
            ");
            $stmt->execute([$this->id, $member_id, $member_type]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du membre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime complètement un membre PJ du groupe et de la base de données
     * 
     * @param int $member_id ID du membre PJ
     * @param int $user_id ID de l'utilisateur propriétaire
     * @return bool True si succès, false sinon
     */
    public function deleteMemberCompletely($member_id, $user_id)
    {
        try {
            // Vérifier que c'est bien un PJ
            $stmt = $this->pdo->prepare("
                SELECT member_type FROM groupe_membres 
                WHERE groupe_id = ? AND member_id = ? AND member_type = 'pj'
            ");
            $stmt->execute([$this->id, $member_id]);
            $member = $stmt->fetch();
            
            if (!$member) {
                return false; // Pas un PJ ou pas dans ce groupe
            }
            
            // Charger le personnage
            require_once __DIR__ . '/Character.php';
            $character = Character::findById($member_id);
            
            if (!$character || !$character->belongsToUser($user_id)) {
                return false; // Personnage non trouvé ou pas le propriétaire
            }
            
            // Supprimer complètement le personnage
            if ($character->deleteCompletely()) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression complète du membre: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour le niveau hiérarchique d'un membre
     * 
     * @param int $member_id ID du membre
     * @param string $member_type Type du membre
     * @param int $hierarchy_level Nouveau niveau hiérarchique
     * @return bool True si succès, false sinon
     */
    public function updateMemberHierarchy($member_id, $member_type, $hierarchy_level)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE groupe_membres 
                SET hierarchy_level = ?
                WHERE groupe_id = ? AND member_id = ? AND member_type = ?
            ");
            $stmt->execute([$hierarchy_level, $this->id, $member_id, $member_type]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la hiérarchie: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour le statut secret d'un membre
     * 
     * @param int $member_id ID du membre
     * @param string $member_type Type du membre
     * @param bool $is_secret Nouveau statut secret
     * @return bool True si succès, false sinon
     */
    public function updateMemberSecretStatus($member_id, $member_type, $is_secret)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE groupe_membres 
                SET is_secret = ?
                WHERE groupe_id = ? AND member_id = ? AND member_type = ?
            ");
            $result = $stmt->execute([$is_secret ? 1 : 0, $this->id, $member_id, $member_type]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du statut secret: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les membres du groupe avec leurs informations
     * 
     * @return array Liste des membres
     */
    public function getMembers()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    gm.member_id,
                    gm.member_type,
                    gm.hierarchy_level,
                    gm.is_secret,
                    gm.joined_at,
                    CASE 
                        WHEN gm.member_type = 'pnj' THEN pn.name
                        WHEN gm.member_type = 'pj' THEN c.name
                        WHEN gm.member_type = 'monster' THEN dm.name
                    END as member_name,
                    CASE 
                        WHEN gm.member_type = 'pnj' THEN pn.profile_photo
                        WHEN gm.member_type = 'pj' THEN c.profile_photo
                        WHEN gm.member_type = 'monster' THEN NULL
                    END as member_photo,
                    CASE 
                        WHEN gm.member_type = 'pnj' THEN pl.title
                        WHEN gm.member_type = 'pj' THEN 'Personnage Joueur'
                        WHEN gm.member_type = 'monster' THEN pl3.title
                    END as member_location
                FROM groupe_membres gm
                LEFT JOIN place_npcs pn ON gm.member_type = 'pnj' AND gm.member_id = pn.id
                LEFT JOIN places pl ON pn.place_id = pl.id
                LEFT JOIN characters c ON gm.member_type = 'pj' AND gm.member_id = c.id
                LEFT JOIN place_monsters pm ON gm.member_type = 'monster' AND gm.member_id = pm.id
                LEFT JOIN dnd_monsters dm ON pm.monster_id = dm.id
                LEFT JOIN places pl3 ON pm.place_id = pl3.id
                WHERE gm.groupe_id = ?
                ORDER BY gm.hierarchy_level ASC, gm.joined_at ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des membres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère le dirigeant du groupe (niveau 1)
     * 
     * @return array|null Informations du dirigeant
     */
    public function getLeader()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    gm.member_id,
                    gm.member_type,
                    gm.hierarchy_level,
                    gm.is_secret,
                    CASE 
                        WHEN gm.member_type = 'pnj' THEN pn.name
                        WHEN gm.member_type = 'pj' THEN c.name
                        WHEN gm.member_type = 'monster' THEN dm.name
                    END as member_name
                FROM groupe_membres gm
                LEFT JOIN place_npcs pn ON gm.member_type = 'pnj' AND gm.member_id = pn.id
                LEFT JOIN characters c ON gm.member_type = 'pj' AND gm.member_id = c.id
                LEFT JOIN place_monsters pm ON gm.member_type = 'monster' AND gm.member_id = pm.id
                LEFT JOIN dnd_monsters dm ON pm.monster_id = dm.id
                WHERE gm.groupe_id = ? AND gm.hierarchy_level = 1
                LIMIT 1
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du dirigeant: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les informations du QG
     * 
     * @return array|null Informations du QG
     */
    public function getHeadquarters()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.title,
                    p.map_url,
                    co.name as country_name,
                    reg.name as region_name
                FROM places p
                LEFT JOIN countries co ON p.country_id = co.id
                LEFT JOIN regions reg ON p.region_id = reg.id
                WHERE p.id = ?
            ");
            $stmt->execute([$this->headquarters_place_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du QG: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouve un groupe par son ID
     * 
     * @param int $id ID du groupe
     * @param PDO $pdo Instance PDO (optionnelle)
     * @return Groupe|null Instance du groupe ou null
     */
    public static function findById($id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPdo();
            $stmt = $pdo->prepare("SELECT * FROM groupes WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($data, $pdo);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du groupe: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère tous les groupes d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param PDO $pdo Instance PDO (optionnelle)
     * @return array Liste des groupes
     */
    public static function findByUser($user_id, PDO $pdo = null)
    {
        try {
            $pdo = $pdo ?: getPdo();
            $stmt = $pdo->prepare("
                SELECT g.*, p.title as headquarters_name
                FROM groupes g
                LEFT JOIN places p ON g.headquarters_place_id = p.id
                WHERE g.created_by = ?
                ORDER BY g.name ASC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des groupes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upload un blason pour le groupe
     * 
     * @param array $file Fichier uploadé ($_FILES['crest'])
     * @return array Résultat de l'upload ['success' => bool, 'message' => string, 'filename' => string]
     */
    public function uploadCrest($file)
    {
        // Vérifier qu'un fichier a été uploadé
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Aucun fichier uploadé ou erreur d\'upload'];
        }

        // Vérifier la taille du fichier (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fichier trop volumineux (max 2MB)'];
        }

        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF, WebP'];
        }

        // Créer le dossier s'il n'existe pas
        $uploadDir = 'uploads/group_crests/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'groupe_' . $this->id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Supprimer l'ancien blason s'il existe
        if ($this->crest_image && file_exists($this->crest_image)) {
            unlink($this->crest_image);
        }

        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->crest_image = $filepath;
            return ['success' => true, 'message' => 'Blason uploadé avec succès', 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload du fichier'];
        }
    }

    /**
     * Supprime le blason du groupe
     * 
     * @return bool True si succès, false sinon
     */
    public function deleteCrest()
    {
        if ($this->crest_image && file_exists($this->crest_image)) {
            unlink($this->crest_image);
        }
        $this->crest_image = null;
        return true;
    }

    /**
     * Convertit l'objet en tableau associatif
     * 
     * @return array Représentation en tableau de l'objet
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'crest_image' => $this->crest_image,
            'is_secret' => $this->is_secret,
            'headquarters_place_id' => $this->headquarters_place_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    
    /**
     * Récupère les groupes auxquels appartient un personnage/NPC/monstre avec leurs niveaux hiérarchiques
     * 
     * @param int $target_id ID du personnage/NPC/monstre
     * @param string $target_type Type ('PJ', 'PNJ', ou 'Monster')
     * @param PDO $pdo Instance PDO (optionnelle)
     * @return array Liste des groupes avec leurs informations
     */
    public static function getGroupMemberships($target_id, $target_type, PDO $pdo = null) {
        if (!$pdo) {
            $pdo = getPdo();
        }
        
        $group_memberships = [];
        
        try {
            // Déterminer le type de membre
            $member_type = '';
            if ($target_type === 'PJ') {
                $member_type = 'pj';
            } elseif ($target_type === 'PNJ') {
                $member_type = 'pnj';
            } elseif ($target_type === 'Monster') {
                $member_type = 'monster';
            }
            
            if (!$member_type) {
                return [];
            }
            
            // Pour les NPC, il faut convertir npcs.id en place_npcs.id
            // car groupe_membres.member_id référence place_npcs.id pour les NPC
            $target_id_for_query = $target_id;
            if ($target_type === 'PNJ') {
                $stmt = $pdo->prepare("
                    SELECT id FROM place_npcs WHERE npc_character_id = ? LIMIT 1
                ");
                $stmt->execute([$target_id]);
                $place_npc = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($place_npc) {
                    $target_id_for_query = $place_npc['id'];
                }
            }
            
            // Récupérer les groupes avec leurs informations
            $stmt = $pdo->prepare("
                SELECT 
                    gm.groupe_id,
                    gm.hierarchy_level,
                    g.name as groupe_name,
                    g.description as groupe_description
                FROM groupe_membres gm
                INNER JOIN groupes g ON gm.groupe_id = g.id
                WHERE gm.member_id = ? AND gm.member_type = ?
                ORDER BY g.name ASC, gm.hierarchy_level ASC
            ");
            $stmt->execute([$target_id_for_query, $member_type]);
            $group_memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $group_memberships;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des groupes: " . $e->getMessage());
            return [];
        }
    }
}
?>
