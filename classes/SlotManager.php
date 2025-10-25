<?php

/**
 * Classe SlotManager - Gestion des slots d'équipement
 * 
 * Cette classe gère la logique des slots d'équipement pour les objets D&D
 */
class SlotManager
{
    /**
     * Détermine le slot approprié pour un type d'objet
     * 
     * @param string $objectType Type de l'objet
     * @param string $objectName Nom de l'objet (optionnel, pour des cas spéciaux)
     * @return string|null Slot approprié ou null si non applicable
     */
    public static function getSlotForObjectType($objectType, $objectName = '')
    {
        switch ($objectType) {
            case 'weapon':
                // Utiliser les données de la table weapons si disponible
                $slot = self::getWeaponSlotFromDatabase($objectName);
                if ($slot) {
                    return $slot;
                }
                
                // Fallback sur la logique précédente
                if (self::isTwoHandedWeapon($objectName)) {
                    return 'deux_mains';
                }
                return 'main_principale';
                
            case 'shield':
                return 'main_secondaire';
                
            case 'armor':
                return 'poitrine';
                
            default:
                return null;
        }
    }
    
    /**
     * Récupère le slot d'une arme depuis la base de données
     * 
     * @param string $weaponName Nom de l'arme
     * @return string|null Slot de l'arme ou null si non trouvée
     */
    private static function getWeaponSlotFromDatabase($weaponName)
    {
        try {
            $pdo = \Database::getInstance()->getPdo();
            
            // Rechercher l'arme par nom exact
            $stmt = $pdo->prepare("SELECT slot_type FROM weapons WHERE name = ?");
            $stmt->execute([$weaponName]);
            $weapon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($weapon && $weapon['slot_type']) {
                return $weapon['slot_type'];
            }
            
            // Recherche approximative si pas de correspondance exacte
            $stmt = $pdo->prepare("SELECT slot_type FROM weapons WHERE name LIKE ?");
            $stmt->execute(['%' . $weaponName . '%']);
            $weapon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($weapon && $weapon['slot_type']) {
                return $weapon['slot_type'];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du slot d'arme: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifie si une arme est à deux mains
     * 
     * @param string $weaponName Nom de l'arme
     * @return bool True si l'arme est à deux mains
     */
    private static function isTwoHandedWeapon($weaponName)
    {
        $twoHandedKeywords = [
            'deux mains', 'two-handed', 'two handed', '2 mains', '2-mains',
            'arc long', 'longbow', 'arbalète lourde', 'heavy crossbow',
            'hallebarde', 'halberd', 'lance', 'spear', 'pique', 'pike',
            'bâton', 'staff', 'bâton de guerre', 'war staff'
        ];
        
        $weaponName = strtolower($weaponName);
        
        foreach ($twoHandedKeywords as $keyword) {
            if (strpos($weaponName, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifie si un slot est compatible avec un type d'objet
     * 
     * @param string $slot Slot à vérifier
     * @param string $objectType Type de l'objet
     * @return bool True si compatible
     */
    public static function isSlotCompatible($slot, $objectType)
    {
        $compatibilities = [
            'main_principale' => ['weapon'],
            'main_secondaire' => ['weapon', 'shield'],
            'deux_mains' => ['weapon'],
            'poitrine' => ['armor']
        ];
        
        return isset($compatibilities[$slot]) && 
               in_array($objectType, $compatibilities[$slot]);
    }
    
    /**
     * Obtient tous les slots disponibles
     * 
     * @return array Liste des slots disponibles
     */
    public static function getAvailableSlots()
    {
        return [
            'main_principale' => 'Main principale',
            'main_secondaire' => 'Main secondaire', 
            'deux_mains' => 'Deux mains',
            'poitrine' => 'Poitrine'
        ];
    }
    
    /**
     * Obtient le nom affiché d'un slot
     * 
     * @param string $slot Slot
     * @return string Nom affiché
     */
    public static function getSlotDisplayName($slot)
    {
        $slots = self::getAvailableSlots();
        return $slots[$slot] ?? $slot;
    }
}
?>
