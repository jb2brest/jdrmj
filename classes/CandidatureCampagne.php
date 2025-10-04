<?php

class CandidatureCampagne
{
    private $id;
    private $campaignId;
    private $playerId;
    private $characterId;
    private $message;
    private $status;
    private $createdAt;
    private $pdo;

    // Statuts possibles
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    public function __construct(PDO $pdo, array $data = [])
    {
        $this->pdo = $pdo;
        $this->id = $data['id'] ?? null;
        $this->campaignId = $data['campaign_id'] ?? null;
        $this->playerId = $data['player_id'] ?? null;
        $this->characterId = $data['character_id'] ?? null;
        $this->message = $data['message'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->createdAt = $data['created_at'] ?? null;
    }

    /**
     * Créer une nouvelle candidature
     * 
     * @param int $campaignId ID de la campagne
     * @param int $playerId ID du joueur
     * @param int $characterId ID du personnage
     * @param string $message Message de candidature
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return CandidatureCampagne|null Instance de CandidatureCampagne ou null si échec
     */
    public static function create($campaignId, $playerId, $characterId, $message = '', PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO campaign_applications (campaign_id, player_id, character_id, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $success = $stmt->execute([$campaignId, $playerId, $characterId, $message, self::STATUS_PENDING]);
            
            if ($success) {
                $id = $pdo->lastInsertId();
                return self::findById($id, $pdo);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la candidature: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer une candidature par ID
     * 
     * @param int $id ID de la candidature
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return CandidatureCampagne|null Instance de CandidatureCampagne ou null si non trouvée
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM campaign_applications WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la candidature: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer toutes les candidatures d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des candidatures avec détails utilisateur et personnage
     */
    public static function getByCampaignId($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT ca.*, u.username, ch.name AS character_name 
                FROM campaign_applications ca 
                JOIN users u ON ca.player_id = u.id 
                LEFT JOIN characters ch ON ca.character_id = ch.id 
                WHERE ca.campaign_id = ? 
                ORDER BY ca.created_at DESC
            ");
            $stmt->execute([$campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des candidatures: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer la candidature d'un joueur pour une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param int $playerId ID du joueur
     * @param string $status Statut de la candidature (optionnel)
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array|null Données de la candidature ou null si non trouvée
     */
    public static function getByCampaignAndPlayer($campaignId, $playerId, $status = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT * FROM campaign_applications WHERE campaign_id = ? AND player_id = ?";
            $params = [$campaignId, $playerId];
            
            if ($status !== null) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la candidature: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier si un joueur a déjà postulé à une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param int $playerId ID du joueur
     * @param string $status Statut de la candidature (par défaut 'pending')
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool True si le joueur a déjà postulé
     */
    public static function hasPlayerApplied($campaignId, $playerId, $status = self::STATUS_PENDING, PDO $pdo = null)
    {
        $candidature = self::getByCampaignAndPlayer($campaignId, $playerId, $status, $pdo);
        return $candidature !== null;
    }

    /**
     * Récupérer les personnages acceptés dans une campagne pour un joueur
     * 
     * @param int $campaignId ID de la campagne
     * @param int $playerId ID du joueur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des IDs des personnages acceptés
     */
    public static function getAcceptedCharacters($campaignId, $playerId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT character_id 
                FROM campaign_applications 
                WHERE campaign_id = ? AND player_id = ? AND status = ?
            ");
            $stmt->execute([$campaignId, $playerId, self::STATUS_APPROVED]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des personnages acceptés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour le statut de la candidature
     * 
     * @param string $status Nouveau statut
     * @return bool Succès de l'opération
     */
    public function updateStatus($status)
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DECLINED])) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE campaign_applications SET status = ? WHERE id = ?");
            $success = $stmt->execute([$status, $this->id]);
            
            if ($success) {
                $this->status = $status;
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Approuver la candidature
     * 
     * @return bool Succès de l'opération
     */
    public function approve()
    {
        return $this->updateStatus(self::STATUS_APPROVED);
    }

    /**
     * Refuser la candidature
     * 
     * @return bool Succès de l'opération
     */
    public function decline()
    {
        return $this->updateStatus(self::STATUS_DECLINED);
    }

    /**
     * Remettre la candidature en attente
     * 
     * @return bool Succès de l'opération
     */
    public function setPending()
    {
        return $this->updateStatus(self::STATUS_PENDING);
    }

    /**
     * Supprimer la candidature
     * 
     * @return bool Succès de l'opération
     */
    public function delete()
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM campaign_applications WHERE id = ?");
            return $stmt->execute([$this->id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la candidature: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si la candidature appartient à un DM
     * 
     * @param int $dmId ID du DM
     * @return bool True si la candidature appartient au DM
     */
    public function belongsToDM($dmId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM campaigns 
                WHERE id = ? AND dm_id = ?
            ");
            $stmt->execute([$this->campaignId, $dmId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du DM: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Annuler l'acceptation d'une candidature
     * 
     * @param int $dmId ID du DM
     * @param int $campaignId ID de la campagne
     * @param object $campaign Objet Campaign pour retirer le membre
     * @param array $campaignData Données de la campagne pour la notification
     * @return array Résultat de l'opération ['success' => bool, 'message' => string]
     */
    public function revokeAcceptance($dmId, $campaignId, $campaign, $campaignData)
    {
        // Vérifier que la candidature appartient au DM et est approuvée
        if (!$this->belongsToDM($dmId) || $this->getCampaignId() != $campaignId || $this->getStatus() != self::STATUS_APPROVED) {
            return ['success' => false, 'message' => 'Candidature approuvée introuvable.'];
        }

        $playerId = $this->getPlayerId();
        
        try {
            $this->pdo->beginTransaction();
            
            // Revenir à pending
            if (!$this->setPending()) {
                throw new Exception("Erreur lors de la mise à jour du statut");
            }
            
            // Retirer le membre de la campagne
            if (!$campaign->removeMember($playerId)) {
                throw new Exception("Erreur lors de la suppression du membre");
            }
            
            // Notifier le joueur
            $title = 'Acceptation annulée';
            $message = 'Votre acceptation dans la campagne "' . $campaignData['title'] . '" a été annulée par le MJ. Votre candidature est de nouveau en attente.';
            if (!Notification::create($playerId, 'system', $title, $message, $campaignId)) {
                throw new Exception("Erreur lors de la création de la notification");
            }
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Acceptation annulée. Candidature remise en attente et joueur retiré.'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de l'annulation de l'acceptation: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'annulation de l\'acceptation.'];
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getCampaignId() { return $this->campaignId; }
    public function getPlayerId() { return $this->playerId; }
    public function getCharacterId() { return $this->characterId; }
    public function getMessage() { return $this->message; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }

    /**
     * Convertir l'objet en tableau
     * 
     * @return array Tableau des propriétés
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'player_id' => $this->playerId,
            'character_id' => $this->characterId,
            'message' => $this->message,
            'status' => $this->status,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Convertir l'objet en tableau avec détails utilisateur et personnage
     * 
     * @return array Tableau des propriétés avec détails
     */
    public function toArrayWithDetails()
    {
        $data = $this->toArray();
        
        // Ajouter les détails utilisateur et personnage si disponibles
        if (isset($this->username)) {
            $data['username'] = $this->username;
        }
        if (isset($this->character_name)) {
            $data['character_name'] = $this->character_name;
        }
        
        return $data;
    }
}
