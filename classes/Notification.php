<?php

class Notification
{
    private $id;
    private $userId;
    private $type;
    private $title;
    private $message;
    private $relatedId;
    private $createdAt;
    private $isRead;
    private $pdo;

    public function __construct(PDO $pdo, array $data = [])
    {
        $this->pdo = $pdo;
        $this->id = $data['id'] ?? null;
        $this->userId = $data['user_id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->relatedId = $data['related_id'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->isRead = $data['is_read'] ?? false;
    }

    /**
     * Créer une nouvelle notification
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $type Type de notification
     * @param string $title Titre de la notification
     * @param string $message Message de la notification
     * @param int|null $relatedId ID de l'entité liée (optionnel)
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'opération
     */
    public static function create($userId, $type, $title, $message, $relatedId = null, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, created_at, is_read) 
                VALUES (?, ?, ?, ?, ?, NOW(), 0)
            ");
            return $stmt->execute([$userId, $type, $title, $message, $relatedId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une notification par ID
     * 
     * @param int $id ID de la notification
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return Notification|null Instance de Notification ou null si non trouvée
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return new self($pdo, $data);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer toutes les notifications d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param bool $unreadOnly Si true, ne retourne que les notifications non lues
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des notifications
     */
    public static function getByUserId($userId, $unreadOnly = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $notifications = [];
            foreach ($results as $data) {
                $notifications[] = new self($pdo, $data);
            }
            
            return $notifications;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marquer une notification comme lue
     * 
     * @param int $id ID de la notification
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'opération
     */
    public static function markAsRead($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     * 
     * @param int $userId ID de l'utilisateur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'opération
     */
    public static function markAllAsRead($userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour des notifications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une notification
     * 
     * @param int $id ID de la notification
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return bool Succès de l'opération
     */
    public static function delete($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la notification: " . $e->getMessage());
            return false;
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getMessage() { return $this->message; }
    public function getRelatedId() { return $this->relatedId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getIsRead() { return $this->isRead; }

    /**
     * Convertir l'objet en tableau
     * 
     * @return array Tableau des propriétés
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'related_id' => $this->relatedId,
            'created_at' => $this->createdAt,
            'is_read' => $this->isRead
        ];
    }
}
