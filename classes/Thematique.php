<?php

/**
 * Classe Thematique - Gestion des thématiques
 * 
 * Une thématique contient un nom et une description
 */
class Thematique
{
    private $pdo;
    
    public $id;
    public $nom;
    public $description;
    public $created_by;
    public $created_at;
    public $updated_at;
    
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
        $this->nom = $data['nom'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->created_by = $data['created_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    /**
     * Sauvegarde la thématique en base de données
     */
    public function save()
    {
        try {
            if ($this->id) {
                $stmt = $this->pdo->prepare("
                    UPDATE thematiques 
                    SET nom = ?, description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->nom,
                    $this->description,
                    $this->id
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO thematiques (nom, description, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $this->nom,
                    $this->description,
                    $this->created_by
                ]);
                $this->id = $this->pdo->lastInsertId();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la sauvegarde de la thématique: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouve une thématique par son ID
     */
    public static function findById($id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM thematiques WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? new self($data, $pdo) : null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la thématique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère toutes les thématiques
     */
    public static function getAll(PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM thematiques ORDER BY nom ASC");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $thematiques = [];
            foreach ($results as $data) {
                $thematiques[] = new self($data, $pdo);
            }
            return $thematiques;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des thématiques: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les thématiques créées par un utilisateur
     */
    public static function getByUser($user_id, PDO $pdo = null)
    {
        $pdo = $pdo ?: getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM thematiques WHERE created_by = ? ORDER BY nom ASC");
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $thematiques = [];
            foreach ($results as $data) {
                $thematiques[] = new self($data, $pdo);
            }
            return $thematiques;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des thématiques de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Supprime la thématique
     */
    public function delete()
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer les liaisons avec les informations
            $stmt = $this->pdo->prepare("DELETE FROM thematique_informations WHERE thematique_id = ?");
            $stmt->execute([$this->id]);
            
            // Supprimer la thématique
            $stmt = $this->pdo->prepare("DELETE FROM thematiques WHERE id = ?");
            $stmt->execute([$this->id]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la suppression de la thématique: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajoute une information à la thématique
     */
    public function addInformation($information_id, $ordre = null)
    {
        try {
            // Déterminer l'ordre si non fourni
            if ($ordre === null) {
                $stmt = $this->pdo->prepare("
                    SELECT COALESCE(MAX(ordre), 0) + 1 as next_ordre 
                    FROM thematique_informations 
                    WHERE thematique_id = ?
                ");
                $stmt->execute([$this->id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $ordre = $result['next_ordre'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO thematique_informations (thematique_id, information_id, ordre)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE ordre = ?
            ");
            $stmt->execute([$this->id, $information_id, $ordre, $ordre]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'information à la thématique: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retire une information de la thématique
     */
    public function removeInformation($information_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM thematique_informations 
                WHERE thematique_id = ? AND information_id = ?
            ");
            $stmt->execute([$this->id, $information_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'information de la thématique: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Définit les informations de la thématique (remplace toutes les existantes)
     */
    public function setInformations($information_ids)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer toutes les liaisons existantes
            $stmt = $this->pdo->prepare("DELETE FROM thematique_informations WHERE thematique_id = ?");
            $stmt->execute([$this->id]);
            
            // Ajouter les nouvelles informations
            $ordre = 1;
            foreach ($information_ids as $information_id) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO thematique_informations (thematique_id, information_id, ordre)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$this->id, $information_id, $ordre++]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erreur lors de la définition des informations: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les informations de la thématique (triées par ordre)
     */
    public function getInformations()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, ti.ordre
                FROM informations i
                INNER JOIN thematique_informations ti ON i.id = ti.information_id
                WHERE ti.thematique_id = ?
                ORDER BY ti.ordre ASC, i.titre ASC
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des informations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Déplace une information vers le haut dans l'ordre
     */
    public function moveInformationUp($information_id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer l'ordre actuel de l'information
            $stmt = $this->pdo->prepare("
                SELECT ordre FROM thematique_informations 
                WHERE thematique_id = ? AND information_id = ?
            ");
            $stmt->execute([$this->id, $information_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current || $current['ordre'] <= 1) {
                $this->pdo->rollBack();
                return false; // Déjà en première position
            }
            
            $current_ordre = $current['ordre'];
            $target_ordre = $current_ordre - 1;
            
            // Échanger avec l'information qui a l'ordre précédent
            $stmt = $this->pdo->prepare("
                UPDATE thematique_informations 
                SET ordre = CASE 
                    WHEN ordre = ? THEN ?
                    WHEN ordre = ? THEN ?
                    ELSE ordre
                END
                WHERE thematique_id = ? AND ordre IN (?, ?)
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
     * Déplace une information vers le bas dans l'ordre
     */
    public function moveInformationDown($information_id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer l'ordre actuel de l'information
            $stmt = $this->pdo->prepare("
                SELECT ordre FROM thematique_informations 
                WHERE thematique_id = ? AND information_id = ?
            ");
            $stmt->execute([$this->id, $information_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Récupérer le nombre maximum d'ordres
            $stmt = $this->pdo->prepare("
                SELECT MAX(ordre) as max_ordre 
                FROM thematique_informations 
                WHERE thematique_id = ?
            ");
            $stmt->execute([$this->id]);
            $max = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$max || $current['ordre'] >= $max['max_ordre']) {
                $this->pdo->rollBack();
                return false; // Déjà en dernière position
            }
            
            $current_ordre = $current['ordre'];
            $target_ordre = $current_ordre + 1;
            
            // Échanger avec l'information qui a l'ordre suivant
            $stmt = $this->pdo->prepare("
                UPDATE thematique_informations 
                SET ordre = CASE 
                    WHEN ordre = ? THEN ?
                    WHEN ordre = ? THEN ?
                    ELSE ordre
                END
                WHERE thematique_id = ? AND ordre IN (?, ?)
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
}

