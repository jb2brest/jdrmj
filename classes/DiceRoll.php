<?php

/**
 * Classe DiceRoll - Gestion des lancers de dés
 */
class DiceRoll
{
    private $pdo;
    
    // Propriétés du lancer de dés
    public $id;
    public $user_id;
    public $place_id;
    public $dice_count;
    public $dice_sides;
    public $modifier;
    public $dice_rolls;
    public $total;
    public $is_hidden;
    public $created_at;
    
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
     * Hydratation de l'objet avec les données
     */
    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Récupérer les lancers de dés d'un lieu
     * 
     * @param int $placeId ID du lieu
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des lancers de dés
     */
    public static function getByPlaceId($placeId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            // Vérifier d'abord si la table existe et a les bonnes colonnes
            $stmt = $pdo->query("SHOW TABLES LIKE 'dice_rolls'");
            if ($stmt->rowCount() == 0) {
                return [];
            }
            
            // Vérifier les colonnes de la table
            $stmt = $pdo->query("SHOW COLUMNS FROM dice_rolls LIKE 'place_id'");
            if ($stmt->rowCount() == 0) {
                // Si pas de colonne place_id, retourner tous les lancers
                $stmt = $pdo->prepare("
                    SELECT dr.*, u.username 
                    FROM dice_rolls dr 
                    JOIN users u ON dr.user_id = u.id 
                    ORDER BY dr.id DESC 
                    LIMIT 50
                ");
                $stmt->execute();
            } else {
                // Si colonne place_id existe, filtrer par lieu
                $stmt = $pdo->prepare("
                    SELECT dr.*, u.username 
                    FROM dice_rolls dr 
                    JOIN users u ON dr.user_id = u.id 
                    WHERE dr.place_id = ? 
                    ORDER BY dr.id DESC 
                    LIMIT 50
                ");
                $stmt->execute([$placeId]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lancers de dés: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les lancers de dés d'une campagne
     *
     * @param int $campaignId ID de la campagne
     * @param bool $showHidden Afficher les jets masqués
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Liste des lancers de dés
     */
    public static function getByCampaignId($campaignId, $showHidden = false, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            // Vérifier d'abord si la table existe
            $stmt = $pdo->query("SHOW TABLES LIKE 'dice_rolls'");
            if ($stmt->rowCount() == 0) {
                return [];
            }
            
            $sql = "
                SELECT dr.*, u.username
                FROM dice_rolls dr 
                JOIN users u ON dr.user_id = u.id 
                WHERE dr.campaign_id = ?
            ";
            
            $params = [$campaignId];
            
            if (!$showHidden) {
                $sql .= " AND (dr.is_hidden IS NULL OR dr.is_hidden = 0)";
            }
            
            $sql .= " ORDER BY dr.rolled_at DESC LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lancers de dés de la campagne: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sauvegarder un lancer de dés
     * 
     * @param array $data Données du lancer
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de la sauvegarde
     */
    public static function save($data, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO dice_rolls (user_id, place_id, dice_count, dice_sides, modifier, dice_rolls, total, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $stmt->execute([
                $data['user_id'],
                $data['place_id'],
                $data['dice_count'],
                $data['dice_sides'],
                $data['modifier'],
                json_encode($data['dice_rolls']),
                $data['total']
            ]);
            
            if ($success) {
                return [
                    'success' => true,
                    'id' => $pdo->lastInsertId(),
                    'message' => 'Lancer de dés sauvegardé'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la sauvegarde'
                ];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde du lancer de dés: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Basculer la visibilité d'un lancer de dés
     * 
     * @param int $rollId ID du lancer
     * @param int $userId ID de l'utilisateur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de l'opération
     */
    public static function toggleVisibility($rollId, $userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                UPDATE dice_rolls 
                SET is_hidden = NOT is_hidden
                WHERE id = ? AND user_id = ?
            ");
            
            $success = $stmt->execute([$rollId, $userId]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Visibilité mise à jour'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors du basculement de visibilité: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Supprimer un lancer de dés
     * 
     * @param int $rollId ID du lancer
     * @param int $userId ID de l'utilisateur
     * @param PDO|null $pdo Instance PDO (optionnelle)
     * @return array Résultat de l'opération
     */
    public static function delete($rollId, $userId, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPDO();
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM dice_rolls 
                WHERE id = ? AND user_id = ?
            ");
            
            $success = $stmt->execute([$rollId, $userId]);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Lancer de dés supprimé'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la suppression'
                ];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du lancer de dés: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ];
        }
    }
}
?>
