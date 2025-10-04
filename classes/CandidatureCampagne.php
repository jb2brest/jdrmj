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
    
    // Propriétés supplémentaires pour les détails
    private $username;
    private $character_name;

    // Statuts possibles
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    public function __construct(PDO $pdo = null, array $data = [])
    {
        $this->pdo = $pdo ?: getPDO();
        $this->id = $data['id'] ?? null;
        $this->campaignId = $data['campaign_id'] ?? null;
        $this->playerId = $data['player_id'] ?? null;
        $this->characterId = $data['character_id'] ?? null;
        $this->message = $data['message'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->createdAt = $data['created_at'] ?? null;
        
        // Hydrater les propriétés supplémentaires si présentes
        if (isset($data['username'])) {
            $this->username = $data['username'];
        }
        if (isset($data['character_name'])) {
            $this->character_name = $data['character_name'];
        }
    }

    /**
     * Valider les données d'une candidature
     * 
     * @param array $data Données à valider
     * @return array Résultat de la validation ['valid' => bool, 'errors' => array]
     */
    public static function validateData(array $data)
    {
        $errors = [];
        
        // Valider campaign_id
        if (empty($data['campaign_id']) || !is_numeric($data['campaign_id'])) {
            $errors[] = 'ID de campagne invalide';
        }
        
        // Valider player_id
        if (empty($data['player_id']) || !is_numeric($data['player_id'])) {
            $errors[] = 'ID de joueur invalide';
        }
        
        // Valider character_id (optionnel mais doit être numérique si fourni)
        if (!empty($data['character_id']) && !is_numeric($data['character_id'])) {
            $errors[] = 'ID de personnage invalide';
        }
        
        // Valider le message (limite de longueur)
        if (isset($data['message']) && strlen($data['message']) > 1000) {
            $errors[] = 'Le message ne peut pas dépasser 1000 caractères';
        }
        
        // Valider le statut
        if (isset($data['status']) && !in_array($data['status'], [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DECLINED])) {
            $errors[] = 'Statut invalide';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Vérifier si une candidature peut être créée
     * 
     * @param int $campaignId ID de la campagne
     * @param int $playerId ID du joueur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de la vérification ['can_create' => bool, 'reason' => string]
     */
    public static function canCreate($campaignId, $playerId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            // Vérifier si le joueur a déjà une candidature en attente
            if (self::hasPlayerApplied($campaignId, $playerId, self::STATUS_PENDING, $pdo)) {
                return ['can_create' => false, 'reason' => 'Une candidature est déjà en attente'];
            }
            
            // Vérifier si le joueur a déjà une candidature approuvée
            if (self::hasPlayerApplied($campaignId, $playerId, self::STATUS_APPROVED, $pdo)) {
                return ['can_create' => false, 'reason' => 'Le joueur est déjà membre de cette campagne'];
            }
            
            // Vérifier si la campagne existe et est active
            $stmt = $pdo->prepare("SELECT id, title FROM campaigns WHERE id = ?");
            $stmt->execute([$campaignId]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$campaign) {
                return ['can_create' => false, 'reason' => 'Campagne introuvable'];
            }
            
            return ['can_create' => true, 'reason' => 'Candidature autorisée'];
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de création de candidature: " . $e->getMessage());
            return ['can_create' => false, 'reason' => 'Erreur de base de données'];
        }
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
        
        // Valider les données
        $data = [
            'campaign_id' => $campaignId,
            'player_id' => $playerId,
            'character_id' => $characterId,
            'message' => $message,
            'status' => self::STATUS_PENDING
        ];
        
        $validation = self::validateData($data);
        if (!$validation['valid']) {
            error_log("Données de candidature invalides: " . implode(', ', $validation['errors']));
            return null;
        }
        
        // Vérifier si la candidature peut être créée
        $canCreate = self::canCreate($campaignId, $playerId, $pdo);
        if (!$canCreate['can_create']) {
            error_log("Impossible de créer la candidature: " . $canCreate['reason']);
            return null;
        }
        
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
     * Approuver la candidature avec assignation de lieu et ajout comme membre
     * 
     * @param object $campaign Objet Campaign
     * @param array $campaignData Données de la campagne
     * @param int|null $placeId ID du lieu (optionnel)
     * @param int|null $characterId ID du personnage (optionnel)
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de l'opération ['success' => bool, 'message' => string]
     */
    public function approveWithPlaceAssignment($campaign, $campaignData, $placeId = null, $characterId = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: $this->getPdo();
        
        // Vérifier si la candidature peut être modifiée
        if (!$this->canBeModified()) {
            return ['success' => false, 'message' => "Cette candidature ne peut plus être modifiée (statut: " . $this->getStatusLabel() . ")."];
        }
        
        $playerId = $this->getPlayerId();
        $appCharacterId = $this->getCharacterId();
        
        // Utiliser le personnage de la candidature si aucun n'est spécifié
        if (!$characterId && $appCharacterId) {
            $characterId = $appCharacterId;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Mettre à jour le statut
            if (!$this->approve()) {
                throw new Exception("Erreur lors de l'approbation de la candidature");
            }
            
            // Ajouter comme membre si pas déjà présent
            if (!$campaign->addMember($playerId, 'player')) {
                throw new Exception("Erreur lors de l'ajout du membre à la campagne");
            }
            
            // Si un lieu est spécifié, assigner le joueur au lieu
            if ($placeId) {
                // Vérifier que le lieu appartient à cette campagne
                if (Lieu::belongsToCampaign($placeId, $this->getCampaignId())) {
                    // Retirer le joueur de tous les autres lieux de la campagne
                    $campaignPlaceIds = Lieu::getPlaceIdsByCampaign($this->getCampaignId());
                    if (!empty($campaignPlaceIds)) {
                        $placeholders = str_repeat('?,', count($campaignPlaceIds) - 1) . '?';
                        $stmt = $pdo->prepare("
                            DELETE FROM place_players 
                            WHERE player_id = ? AND place_id IN ($placeholders)
                        ");
                        $params = array_merge([$playerId], $campaignPlaceIds);
                        $stmt->execute($params);
                    }
                    
                    // Ajouter le joueur au nouveau lieu
                    if (!Lieu::addPlayerToPlace($placeId, $playerId, $characterId)) {
                        throw new Exception("Erreur lors de l'assignation du joueur au lieu");
                    }
                }
            }
            
            // Notification au joueur
            $title = 'Candidature acceptée';
            $message = 'Votre candidature à la campagne "' . $campaignData['title'] . '" a été acceptée.';
            if ($placeId) {
                $placeObj = Lieu::findById($placeId);
                $place = $placeObj ? ['title' => $placeObj->getTitle()] : null;
                if ($place) {
                    $message .= ' Vous avez été assigné au lieu "' . $place['title'] . '".';
                }
            }
            if (!Notification::create($playerId, 'system', $title, $message, $this->getCampaignId())) {
                throw new Exception("Erreur lors de la création de la notification");
            }
            
            $pdo->commit();
            
            $successMessage = "Candidature approuvée et joueur ajouté à la campagne.";
            if ($placeId) {
                $successMessage .= " Le joueur a été assigné au lieu sélectionné.";
            }
            
            return ['success' => true, 'message' => $successMessage];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors de l'approbation avec assignation: " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors de l'approbation: " . $e->getMessage()];
        }
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

    /**
     * Obtenir les statistiques des candidatures pour une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Statistiques des candidatures
     */
    public static function getStatistics($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM campaign_applications 
                WHERE campaign_id = ? 
                GROUP BY status
            ");
            $stmt->execute([$campaignId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'declined' => 0
            ];
            
            foreach ($results as $result) {
                $stats[$result['status']] = (int)$result['count'];
                $stats['total'] += (int)$result['count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'declined' => 0
            ];
        }
    }

    /**
     * Approuver plusieurs candidatures en lot
     * 
     * @param array $applicationIds Liste des IDs de candidatures
     * @param int $dmId ID du DM
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de l'opération ['success' => bool, 'processed' => int, 'errors' => array]
     */
    public static function bulkApprove($applicationIds, $dmId, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $processed = 0;
        $errors = [];
        
        try {
            $pdo->beginTransaction();
            
            foreach ($applicationIds as $applicationId) {
                $candidature = self::findById($applicationId, $pdo);
                
                if ($candidature && $candidature->belongsToDM($dmId) && $candidature->getCampaignId() == $campaignId) {
                    if ($candidature->approve()) {
                        $processed++;
                    } else {
                        $errors[] = "Erreur lors de l'approbation de la candidature ID: $applicationId";
                    }
                } else {
                    $errors[] = "Candidature ID: $applicationId non trouvée ou accès non autorisé";
                }
            }
            
            $pdo->commit();
            return ['success' => true, 'processed' => $processed, 'errors' => $errors];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors de l'approbation en lot: " . $e->getMessage());
            return ['success' => false, 'processed' => $processed, 'errors' => array_merge($errors, [$e->getMessage()])];
        }
    }

    /**
     * Refuser plusieurs candidatures en lot
     * 
     * @param array $applicationIds Liste des IDs de candidatures
     * @param int $dmId ID du DM
     * @param int $campaignId ID de la campagne
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de l'opération ['success' => bool, 'processed' => int, 'errors' => array]
     */
    public static function bulkDecline($applicationIds, $dmId, $campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        $processed = 0;
        $errors = [];
        
        try {
            $pdo->beginTransaction();
            
            foreach ($applicationIds as $applicationId) {
                $candidature = self::findById($applicationId, $pdo);
                
                if ($candidature && $candidature->belongsToDM($dmId) && $candidature->getCampaignId() == $campaignId) {
                    if ($candidature->decline()) {
                        $processed++;
                    } else {
                        $errors[] = "Erreur lors du refus de la candidature ID: $applicationId";
                    }
                } else {
                    $errors[] = "Candidature ID: $applicationId non trouvée ou accès non autorisé";
                }
            }
            
            $pdo->commit();
            return ['success' => true, 'processed' => $processed, 'errors' => $errors];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors du refus en lot: " . $e->getMessage());
            return ['success' => false, 'processed' => $processed, 'errors' => array_merge($errors, [$e->getMessage()])];
        }
    }

    /**
     * Rechercher des candidatures avec filtres avancés
     * 
     * @param array $filters Filtres de recherche
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des candidatures correspondantes
     */
    public static function search($filters = [], PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "
                SELECT ca.*, u.username, ch.name AS character_name, c.title AS campaign_title
                FROM campaign_applications ca 
                JOIN users u ON ca.player_id = u.id 
                LEFT JOIN characters ch ON ca.character_id = ch.id 
                JOIN campaigns c ON ca.campaign_id = c.id
                WHERE 1=1
            ";
            $params = [];
            
            // Filtre par campagne
            if (!empty($filters['campaign_id'])) {
                $sql .= " AND ca.campaign_id = ?";
                $params[] = $filters['campaign_id'];
            }
            
            // Filtre par statut
            if (!empty($filters['status'])) {
                $sql .= " AND ca.status = ?";
                $params[] = $filters['status'];
            }
            
            // Filtre par joueur
            if (!empty($filters['player_id'])) {
                $sql .= " AND ca.player_id = ?";
                $params[] = $filters['player_id'];
            }
            
            // Filtre par nom d'utilisateur
            if (!empty($filters['username'])) {
                $sql .= " AND u.username LIKE ?";
                $params[] = '%' . $filters['username'] . '%';
            }
            
            // Filtre par nom de personnage
            if (!empty($filters['character_name'])) {
                $sql .= " AND ch.name LIKE ?";
                $params[] = '%' . $filters['character_name'] . '%';
            }
            
            // Filtre par date de création
            if (!empty($filters['date_from'])) {
                $sql .= " AND ca.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND ca.created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Tri
            $orderBy = $filters['order_by'] ?? 'ca.created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY $orderBy $orderDir";
            
            // Limite
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . (int)$filters['limit'];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de candidatures: " . $e->getMessage());
            return [];
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
    public function getUsername() { return $this->username; }
    public function getCharacterName() { return $this->character_name; }
    private function getPdo() { return $this->pdo; }

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
        if ($this->username) {
            $data['username'] = $this->username;
        }
        if ($this->character_name) {
            $data['character_name'] = $this->character_name;
        }
        
        return $data;
    }

    /**
     * Obtenir un résumé de la candidature pour l'affichage
     * 
     * @return array Résumé formaté de la candidature
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'player' => $this->username ?: "Joueur #{$this->playerId}",
            'character' => $this->character_name ?: "Personnage #{$this->characterId}",
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'message' => $this->message,
            'created_at' => $this->createdAt,
            'created_at_formatted' => $this->createdAt ? date('d/m/Y H:i', strtotime($this->createdAt)) : null
        ];
    }

    /**
     * Obtenir le libellé du statut
     * 
     * @return string Libellé du statut
     */
    public function getStatusLabel()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'En attente';
            case self::STATUS_APPROVED:
                return 'Approuvée';
            case self::STATUS_DECLINED:
                return 'Refusée';
            default:
                return 'Inconnu';
        }
    }

    /**
     * Vérifier si la candidature peut être modifiée
     * 
     * @return bool True si la candidature peut être modifiée
     */
    public function canBeModified()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si la candidature peut être annulée
     * 
     * @return bool True si la candidature peut être annulée
     */
    public function canBeRevoked()
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
