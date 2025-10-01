<?php
/**
 * Fichier de compatibilité pour les fonctions de campagne
 * 
 * Ce fichier maintient les fonctions existantes tout en utilisant
 * la classe Campaign en arrière-plan pour assurer une transition en douceur.
 */

// S'assurer que toutes les classes nécessaires sont disponibles
if (!class_exists('Campaign')) {
    require_once __DIR__ . '/../classes/init.php';
}

// =====================================================
// FONCTIONS DE COMPATIBILITÉ
// =====================================================

/**
 * Génère un code d'invitation pour une campagne
 * 
 * @param int $length Longueur du code
 * @return string Code d'invitation
 */
if (!function_exists('generateInviteCode')) {
    function generateInviteCode($length = 12) {
        return Campaign::generateInviteCode($length);
    }
}

/**
 * Associe un lieu à une campagne
 * 
 * @param int $placeId ID du lieu
 * @param int $campaignId ID de la campagne
 * @return bool True si associé avec succès
 */
if (!function_exists('associatePlaceToCampaign')) {
    function associatePlaceToCampaign($placeId, $campaignId) {
        global $pdo;
        $campaign = Campaign::findById($campaignId);
        if ($campaign) {
            return $campaign->associatePlace($placeId);
        }
        return false;
    }
}

/**
 * Dissocie un lieu d'une campagne
 * 
 * @param int $placeId ID du lieu
 * @param int $campaignId ID de la campagne
 * @return bool True si dissocié avec succès
 */
if (!function_exists('dissociatePlaceFromCampaign')) {
    function dissociatePlaceFromCampaign($placeId, $campaignId) {
        global $pdo;
        $campaign = Campaign::findById($campaignId);
        if ($campaign) {
            return $campaign->dissociatePlace($placeId);
        }
        return false;
    }
}

/**
 * Obtient les campagnes associées à un lieu
 * 
 * @param int $placeId ID du lieu
 * @return array Liste des campagnes
 */
if (!function_exists('getCampaignsForPlace')) {
    function getCampaignsForPlace($placeId) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, pc.created_at as associated_at
                FROM campaigns c
                JOIN place_campaigns pc ON c.id = pc.campaign_id
                WHERE pc.place_id = ?
                ORDER BY c.title ASC
            ");
            $stmt->execute([$placeId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}

/**
 * Obtient les lieux associés à une campagne
 * 
 * @param int $campaignId ID de la campagne
 * @return array Liste des lieux
 */
if (!function_exists('getPlacesForCampaign')) {
    function getPlacesForCampaign($campaignId) {
        global $pdo;
        $campaign = Campaign::findById($campaignId);
        if ($campaign) {
            return $campaign->getAssociatedPlaces();
        }
        return [];
    }
}

/**
 * Obtient les lieux disponibles pour une campagne
 * 
 * @param int $campaignId ID de la campagne
 * @return array Liste des lieux disponibles
 */
if (!function_exists('getAvailablePlacesForCampaign')) {
    function getAvailablePlacesForCampaign($campaignId) {
        global $pdo;
        $campaign = Campaign::findById($campaignId);
        if ($campaign) {
            return $campaign->getAvailablePlaces();
        }
        return [];
    }
}

/**
 * Obtient les campagnes disponibles pour un lieu
 * 
 * @param int $placeId ID du lieu
 * @return array Liste des campagnes disponibles
 */
if (!function_exists('getAvailableCampaignsForPlace')) {
    function getAvailableCampaignsForPlace($placeId) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT c.*
                FROM campaigns c
                WHERE c.id NOT IN (
                    SELECT pc.campaign_id 
                    FROM place_campaigns pc 
                    WHERE pc.place_id = ?
                )
                ORDER BY c.title ASC
            ");
            $stmt->execute([$placeId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}

/**
 * Met à jour les associations de lieux pour une campagne
 * 
 * @param int $campaignId ID de la campagne
 * @param array $placeIds Liste des IDs de lieux
 * @return bool True si mis à jour avec succès
 */
if (!function_exists('updateCampaignPlaceAssociations')) {
    function updateCampaignPlaceAssociations($campaignId, $placeIds) {
        global $pdo;
        $campaign = Campaign::findById($campaignId);
        if (!$campaign) {
            return false;
        }

        try {
            $pdo->beginTransaction();
            
            // Supprimer toutes les associations existantes
            $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE campaign_id = ?");
            $stmt->execute([$campaignId]);
            
            // Ajouter les nouvelles associations
            if (!empty($placeIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO place_campaigns (place_id, campaign_id, created_at) 
                    VALUES (?, ?, NOW())
                ");
                foreach ($placeIds as $placeId) {
                    $stmt->execute([$placeId, $campaignId]);
                }
            }
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

/**
 * Met à jour les associations de campagnes pour un lieu
 * 
 * @param int $placeId ID du lieu
 * @param array $campaignIds Liste des IDs de campagnes
 * @return bool True si mis à jour avec succès
 */
if (!function_exists('updatePlaceCampaignAssociations')) {
    function updatePlaceCampaignAssociations($placeId, $campaignIds) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Supprimer toutes les associations existantes
            $stmt = $pdo->prepare("DELETE FROM place_campaigns WHERE place_id = ?");
            $stmt->execute([$placeId]);
            
            // Ajouter les nouvelles associations
            if (!empty($campaignIds)) {
                $stmt = $pdo->prepare("
                    INSERT INTO place_campaigns (place_id, campaign_id, created_at) 
                    VALUES (?, ?, NOW())
                ");
                foreach ($campaignIds as $campaignId) {
                    $stmt->execute([$placeId, $campaignId]);
                }
            }
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

// =====================================================
// FONCTIONS UTILITAIRES POUR LA MIGRATION
// =====================================================

/**
 * Obtient une campagne par son ID sous forme d'objet Campaign
 * 
 * @param int $campaignId ID de la campagne
 * @return Campaign|null Campagne trouvée ou null
 */
if (!function_exists('getCampaignObject')) {
    function getCampaignObject($campaignId) {
        global $pdo;
        return Campaign::findById($campaignId);
    }
}

/**
 * Crée une nouvelle campagne en utilisant la classe Campaign
 * 
 * @param array $data Données de la campagne
 * @return Campaign|null Campagne créée ou null
 */
if (!function_exists('createCampaign')) {
    function createCampaign($data) {
        return Campaign::create($data);
    }
}

/**
 * Obtient les campagnes accessibles par un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $userRole Rôle de l'utilisateur
 * @return array Liste des campagnes
 */
if (!function_exists('getAccessibleCampaigns')) {
    function getAccessibleCampaigns($userId, $userRole = 'player') {
        global $pdo;
        return Campaign::getAccessibleCampaigns($userId, $userRole);
    }
}

/**
 * Obtient les campagnes créées par un DM
 * 
 * @param int $dmId ID du DM
 * @return array Liste des campagnes
 */
if (!function_exists('getCampaignsByDM')) {
    function getCampaignsByDM($dmId) {
        global $pdo;
        return Campaign::getCampaignsByDM($dmId);
    }
}
?>
