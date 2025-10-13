<?php

/**
 * Classe CampaignEvent
 * Gère les événements de campagne (journal de campagne)
 * Table: campaign_journal
 */
class CampaignEvent
{
    private $id;
    private $campaignId;
    private $title;
    private $content;
    private $isPublic;
    private $createdAt;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO
     * @param array $data Données de l'événement
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
     * 
     * @param array $data Données à hydrater
     */
    private function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->campaignId = $data['campaign_id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->isPublic = (bool)($data['is_public'] ?? false);
        $this->createdAt = $data['created_at'] ?? null;
    }

    /**
     * Obtenir l'instance PDO
     * 
     * @return PDO
     */
    private function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Créer un nouvel événement de campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param string $title Titre de l'événement
     * @param string $content Contenu de l'événement
     * @param bool $isPublic Visibilité publique
     * @param PDO $pdo Instance PDO
     * @return CampaignEvent|null Instance créée ou null en cas d'erreur
     */
    public static function create($campaignId, $title, $content, $isPublic = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO campaign_journal (campaign_id, title, content, is_public) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$campaignId, $title, $content, $isPublic ? 1 : 0]);
            
            if ($result) {
                $eventId = $pdo->lastInsertId();
                return self::findById($eventId, $pdo);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'événement de campagne: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver un événement par son ID
     * 
     * @param int $id ID de l'événement
     * @param PDO $pdo Instance PDO
     * @return CampaignEvent|null Instance trouvée ou null
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM campaign_journal WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'événement: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les événements d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO $pdo Instance PDO
     * @return array Liste des événements
     */
    public static function getByCampaignId($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM campaign_journal WHERE campaign_id = ? ORDER BY created_at DESC");
            $stmt->execute([$campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des événements de campagne: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les événements publics d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO $pdo Instance PDO
     * @return array Liste des événements publics
     */
    public static function getPublicByCampaignId($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM campaign_journal WHERE campaign_id = ? AND is_public = 1 ORDER BY created_at DESC");
            $stmt->execute([$campaignId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des événements publics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour l'événement
     * 
     * @param string $title Nouveau titre
     * @param string $content Nouveau contenu
     * @return bool Succès de la mise à jour
     */
    public function update($title, $content)
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE campaign_journal SET title = ?, content = ? WHERE id = ? AND campaign_id = ?");
            $result = $stmt->execute([$title, $content, $this->id, $this->campaignId]);
            
            if ($result) {
                $this->title = $title;
                $this->content = $content;
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'événement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Basculer la visibilité de l'événement
     * 
     * @return bool Succès de la bascule
     */
    public function toggleVisibility()
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE campaign_journal SET is_public = NOT is_public WHERE id = ? AND campaign_id = ?");
            $result = $stmt->execute([$this->id, $this->campaignId]);
            
            if ($result) {
                $this->isPublic = !$this->isPublic;
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement de la visibilité: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer l'événement
     * 
     * @return bool Succès de la suppression
     */
    public function delete()
    {
        if ($this->id === null) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM campaign_journal WHERE id = ? AND campaign_id = ?");
            return $stmt->execute([$this->id, $this->campaignId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'événement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer tous les événements d'une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @param PDO $pdo Instance PDO
     * @return bool Succès de la suppression
     */
    public static function deleteByCampaignId($campaignId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("DELETE FROM campaign_journal WHERE campaign_id = ?");
            return $stmt->execute([$campaignId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression des événements de campagne: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si l'événement appartient à une campagne
     * 
     * @param int $campaignId ID de la campagne
     * @return bool True si l'événement appartient à la campagne
     */
    public function belongsToCampaign($campaignId)
    {
        return $this->campaignId == $campaignId;
    }

    /**
     * Vérifier si l'événement appartient à un DM
     * 
     * @param int $dmId ID du DM
     * @return bool True si l'événement appartient au DM
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
     * Convertir l'objet en tableau
     * 
     * @return array Données de l'événement
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'title' => $this->title,
            'content' => $this->content,
            'is_public' => $this->isPublic,
            'created_at' => $this->createdAt
        ];
    }

    // Getters
    public function getId() { return $this->id; }
    public function getCampaignId() { return $this->campaignId; }
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getIsPublic() { return $this->isPublic; }
    public function getCreatedAt() { return $this->createdAt; }
}
