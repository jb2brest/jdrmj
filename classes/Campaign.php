<?php

/**
 * Classe Campaign - Gestion des campagnes
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux campagnes
 * du système JDR MJ, incluant la création, gestion des membres, et associations.
 */
class Campaign
{
    private $id;
    private $dmId;
    private $title;
    private $description;
    private $gameSystem;
    private $isPublic;
    private $inviteCode;
    private $worldId;
    private $createdAt;
    private $updatedAt;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO pour la base de données (optionnel)
     * @param array $data Données de la campagne (optionnel)
     */
    public function __construct(PDO $pdo = null, array $data = [])
    {
        $this->pdo = $pdo ?: getPDO();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrate l'objet avec des données
     * 
     * @param array $data Données de la campagne
     */
    private function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->dmId = $data['dm_id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->gameSystem = $data['game_system'] ?? 'D&D 5e';
        $this->isPublic = $data['is_public'] ?? true;
        $this->inviteCode = $data['invite_code'] ?? null;
        $this->worldId = $data['world_id'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // =====================================================
    // MÉTHODES DE CRÉATION ET GESTION
    // =====================================================

    /**
     * Crée une nouvelle campagne
     * 
     * @param array $data Données de la campagne
     * @param PDO $pdo Instance PDO (optionnel)
     * @return Campaign|null Campagne créée ou null en cas d'erreur
     */
    public static function create(array $data, PDO $pdo = null)
    {
        // Validation des données requises
        $required = ['dm_id', 'title'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Le champ '$field' est requis");
            }
        }

        // Validation du titre
        if (strlen($data['title']) < 3) {
            throw new InvalidArgumentException("Le titre doit contenir au moins 3 caractères");
        }

        $pdo = $pdo ?: getPDO();

        // Générer un code d'invitation unique
        $inviteCode = self::generateInviteCode();

        // Préparer les données
        $campaignData = [
            'dm_id' => $data['dm_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'game_system' => $data['game_system'] ?? 'D&D 5e',
            'is_public' => $data['is_public'] ?? true,
            'invite_code' => $inviteCode
        ];

        try {
            $pdo->beginTransaction();

            // Insérer la campagne
            $stmt = $pdo->prepare("
                INSERT INTO campaigns (dm_id, title, description, game_system, is_public, invite_code, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $campaignData['dm_id'],
                $campaignData['title'],
                $campaignData['description'],
                $campaignData['game_system'],
                $campaignData['is_public'],
                $campaignData['invite_code']
            ]);

            $campaignData['id'] = $pdo->lastInsertId();

            // Ajouter le DM comme membre de sa propre campagne
            $stmt = $pdo->prepare("
                INSERT INTO campaign_members (campaign_id, user_id, role, joined_at) 
                VALUES (?, ?, 'dm', NOW())
            ");
            $stmt->execute([$campaignData['id'], $campaignData['dm_id']]);

            $pdo->commit();

            return new self($pdo, $campaignData);

        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Erreur lors de la création de la campagne: " . $e->getMessage());
        }
    }

    /**
     * Génère un code d'invitation unique
     * 
     * @param int $length Longueur du code
     * @return string Code d'invitation
     */
    public static function generateInviteCode($length = 12)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // Vérifier l'unicité
            global $pdo;
            $stmt = $pdo->prepare("SELECT 1 FROM campaigns WHERE invite_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }

    /**
     * Met à jour la campagne
     * 
     * @param array $data Nouvelles données
     * @return bool True si mis à jour avec succès
     */
    public function update(array $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            if (strlen($data['title']) < 3) {
                throw new InvalidArgumentException("Le titre doit contenir au moins 3 caractères");
            }
            $fields[] = 'title = ?';
            $values[] = $data['title'];
            $this->title = $data['title'];
        }

        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $values[] = $data['description'];
            $this->description = $data['description'];
        }

        if (isset($data['game_system'])) {
            $fields[] = 'game_system = ?';
            $values[] = $data['game_system'];
            $this->gameSystem = $data['game_system'];
        }

        if (isset($data['is_public'])) {
            $fields[] = 'is_public = ?';
            $values[] = $data['is_public'];
            $this->isPublic = $data['is_public'];
        }

        if (empty($fields)) {
            return true; // Rien à mettre à jour
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $this->id;

        $sql = "UPDATE campaigns SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime la campagne
     * 
     * @return bool True si supprimé avec succès
     */
    public function delete()
    {
        $stmt = $this->pdo->prepare("DELETE FROM campaigns WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // =====================================================
    // MÉTHODES DE RECHERCHE
    // =====================================================

    /**
     * Trouve une campagne par son ID
     * 
     * @param int $id ID de la campagne
     * @return Campaign|null Campagne trouvée ou null
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->execute([$id]);
        $campaignData = $stmt->fetch();

        if ($campaignData) {
            return new self($pdo, $campaignData);
        }

        return null;
    }

    /**
     * Trouve une campagne par son code d'invitation
     * 
     * @param string $inviteCode Code d'invitation
     * @return Campaign|null Campagne trouvée ou null
     */
    public static function findByInviteCode($inviteCode, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE invite_code = ?");
        $stmt->execute([$inviteCode]);
        $campaignData = $stmt->fetch();

        if ($campaignData) {
            return new self($pdo, $campaignData);
        }

        return null;
    }

    /**
     * Obtient toutes les campagnes accessibles par un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $userRole Rôle de l'utilisateur
     * @param PDO $pdo Instance PDO (optionnel)
     * @return array Liste des campagnes
     */
    public static function getAccessibleCampaigns($userId, $userRole = 'player', PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        if ($userRole === 'admin') {
            // Les admins peuvent voir toutes les campagnes
            $stmt = $pdo->prepare("
                SELECT c.*, u.username AS dm_username 
                FROM campaigns c 
                JOIN users u ON c.dm_id = u.id 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
        } elseif ($userRole === 'dm') {
            // Les DM peuvent voir leurs campagnes + les campagnes publiques
            $stmt = $pdo->prepare("
                SELECT c.*, u.username AS dm_username 
                FROM campaigns c 
                JOIN users u ON c.dm_id = u.id 
                WHERE c.dm_id = ? OR c.is_public = 1 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
        } else {
            // Les joueurs peuvent voir les campagnes publiques ET les campagnes où ils sont membres
            $stmt = $pdo->prepare("
                SELECT c.*, u.username AS dm_username 
                FROM campaigns c 
                JOIN users u ON c.dm_id = u.id
                WHERE c.is_public = 1 
                OR EXISTS (
                    SELECT 1 FROM campaign_members cm 
                    WHERE cm.campaign_id = c.id AND cm.user_id = ?
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Obtient les campagnes créées par un DM
     * 
     * @param int $dmId ID du DM
     * @return array Liste des campagnes
     */
    public static function getCampaignsByDM($dmId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $stmt = $pdo->prepare("
            SELECT c.*, u.username AS dm_username 
            FROM campaigns c 
            JOIN users u ON c.dm_id = u.id 
            WHERE c.dm_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$dmId]);
        return $stmt->fetchAll();
    }

    // =====================================================
    // MÉTHODES DE GESTION DES MEMBRES
    // =====================================================

    /**
     * Ajoute un membre à la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $role Rôle du membre ('player' ou 'dm')
     * @return bool True si ajouté avec succès
     */
    public function addMember($userId, $role = 'player')
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO campaign_members (campaign_id, user_id, role, joined_at) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$this->id, $userId, $role]);
        } catch (PDOException $e) {
            // Membre déjà présent
            return false;
        }
    }

    /**
     * Retire un membre de la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si retiré avec succès
     */
    public function removeMember($userId)
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM campaign_members 
            WHERE campaign_id = ? AND user_id = ?
        ");
        return $stmt->execute([$this->id, $userId]);
    }

    /**
     * Obtient les membres de la campagne
     * 
     * @return array Liste des membres
     */
    public function getMembers()
    {
        $stmt = $this->pdo->prepare("
            SELECT cm.*, u.username, u.email, u.role as user_role,
                   c.id as character_id, c.name as character_name, c.hit_points_max
            FROM campaign_members cm
            JOIN users u ON cm.user_id = u.id
            LEFT JOIN characters c ON c.user_id = u.id
            LEFT JOIN campaign_applications ca ON c.id = ca.character_id AND ca.campaign_id = cm.campaign_id AND ca.status = 'approved'
            WHERE cm.campaign_id = ?
            ORDER BY cm.role DESC, cm.joined_at ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Vérifie si un utilisateur est membre de la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si membre
     */
    public function isMember($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM campaign_members 
            WHERE campaign_id = ? AND user_id = ?
        ");
        $stmt->execute([$this->id, $userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Obtient le rôle d'un utilisateur dans la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @return string|null Rôle ou null si pas membre
     */
    public function getUserRole($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT role FROM campaign_members 
            WHERE campaign_id = ? AND user_id = ?
        ");
        $stmt->execute([$this->id, $userId]);
        $result = $stmt->fetch();
        return $result ? $result['role'] : null;
    }

    // =====================================================
    // MÉTHODES DE GESTION DES LIEUX
    // =====================================================

    /**
     * Associe un lieu à la campagne
     * 
     * @param int $placeId ID du lieu
     * @return bool True si associé avec succès
     */
    public function associatePlace($placeId)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO place_campaigns (place_id, campaign_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            return $stmt->execute([$placeId, $this->id]);
        } catch (PDOException $e) {
            // Lieu déjà associé
            return false;
        }
    }

    /**
     * Dissocie un lieu de la campagne
     * 
     * @param int $placeId ID du lieu
     * @return bool True si dissocié avec succès
     */
    public function dissociatePlace($placeId)
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM place_campaigns 
            WHERE place_id = ? AND campaign_id = ?
        ");
        return $stmt->execute([$placeId, $this->id]);
    }

    /**
     * Obtient les lieux associés à la campagne
     * 
     * @return array Liste des lieux
     */
    public function getAssociatedPlaces()
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, pc.created_at as associated_at
            FROM places p
            JOIN place_campaigns pc ON p.id = pc.place_id
            WHERE pc.campaign_id = ?
            ORDER BY p.title ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtient les lieux associés avec la hiérarchie géographique
     * 
     * @return array Liste des lieux avec hiérarchie géographique
     */
    public function getAssociatedPlacesWithGeography()
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, pc.created_at as associated_at,
                   r.name as region_name, r.id as region_id,
                   c.name as country_name, c.id as country_id,
                   w.name as world_name, w.id as world_id
            FROM places p
            JOIN place_campaigns pc ON p.id = pc.place_id
            LEFT JOIN regions r ON p.region_id = r.id
            LEFT JOIN countries c ON r.country_id = c.id
            LEFT JOIN worlds w ON c.world_id = w.id
            WHERE pc.campaign_id = ?
            ORDER BY w.name ASC, c.name ASC, r.name ASC, p.title ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtient les lieux disponibles pour la campagne
     * 
     * @return array Liste des lieux disponibles
     */
    public function getAvailablePlaces()
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*
            FROM places p
            WHERE p.id NOT IN (
                SELECT pc.place_id 
                FROM place_campaigns pc 
                WHERE pc.campaign_id = ?
            )
            ORDER BY p.title ASC
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }

    // =====================================================
    // MÉTHODES DE VÉRIFICATION
    // =====================================================

    /**
     * Vérifie si un utilisateur peut accéder à la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $userRole Rôle de l'utilisateur
     * @return bool True si accès autorisé
     */
    public function canAccess($userId, $userRole = 'player')
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($this->isPublic) {
            return true;
        }

        if ($this->isMember($userId)) {
            return true;
        }

        if ($this->dmId == $userId) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur peut modifier la campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $userRole Rôle de l'utilisateur
     * @return bool True si modification autorisée
     */
    public function canModify($userId, $userRole = 'player')
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($this->dmId == $userId) {
            return true;
        }

        return false;
    }

    // =====================================================
    // GETTERS
    // =====================================================

    public function getId()
    {
        return $this->id;
    }

    public function getDmId()
    {
        return $this->dmId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getGameSystem()
    {
        return $this->gameSystem;
    }

    public function getIsPublic()
    {
        return $this->isPublic;
    }

    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getWorldId()
    {
        return $this->worldId;
    }

    public function setWorldId($worldId)
    {
        $this->worldId = $worldId;
    }

    // =====================================================
    // MÉTHODES UTILITAIRES
    // =====================================================

    /**
     * Convertit l'objet en tableau
     * 
     * @return array Données de la campagne
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'dm_id' => $this->dmId,
            'title' => $this->title,
            'description' => $this->description,
            'game_system' => $this->gameSystem,
            'is_public' => $this->isPublic,
            'invite_code' => $this->inviteCode,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Obtient le label de visibilité en français
     * 
     * @return string Label de visibilité
     */
    public function getVisibilityLabel()
    {
        return $this->isPublic ? 'Publique' : 'Privée';
    }

    /**
     * Obtient le nombre de membres de la campagne
     * 
     * @return int Nombre de membres
     */
    public function getMemberCount()
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM campaign_members 
            WHERE campaign_id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Obtient le nombre de lieux associés
     * 
     * @return int Nombre de lieux
     */
    public function getPlaceCount()
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM place_campaigns 
            WHERE campaign_id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
    
    /**
     * Vérifier si un personnage est dans cette campagne
     * 
     * @param Character $character Le personnage à vérifier
     * @return bool True si le personnage est dans la campagne, false sinon
     */
    public function isCharacterIn(Character $character)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pp.* FROM place_players pp
                INNER JOIN place_campaigns pc ON pp.place_id = pc.place_id
                WHERE pp.character_id = ? AND pc.campaign_id = ?
            ");
            $stmt->execute([$character->id, $this->id]);
            $result = $stmt->fetch();
            
            return $result !== false;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du personnage dans la campagne: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un lieu peut être associé à cette campagne
     */
    public function canAssociatePlace($placeId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id FROM places p
                LEFT JOIN countries c ON p.country_id = c.id
                WHERE p.id = ? AND c.world_id = ? AND p.id NOT IN (
                    SELECT place_id FROM place_campaigns WHERE campaign_id = ?
                )
            ");
            $stmt->execute([$placeId, $this->worldId, $this->id]);
            $place = $stmt->fetch();
            
            return $place !== false;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'association du lieu: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour le monde de la campagne
     */
    public function updateWorld($worldId)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE campaigns SET world_id = ? WHERE id = ? AND dm_id = ?");
            $result = $stmt->execute([$worldId, $this->id, $this->dm_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->worldId = $worldId;
                return ['success' => true, 'message' => 'Monde de la campagne mis à jour avec succès.'];
            } else {
                return ['success' => false, 'message' => 'Aucune modification effectuée.'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du monde: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }

    /**
     * Vérifier si un utilisateur a candidaté à cette campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si l'utilisateur a candidaté
     */
    public function hasUserApplied($userId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM campaign_applications WHERE campaign_id = ? AND player_id = ? LIMIT 1");
            $stmt->execute([$this->id, $userId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de la candidature: " . $e->getMessage());
            return false;
        }
    }
}
