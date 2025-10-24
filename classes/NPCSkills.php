<?php

/**
 * Classe NPCSkills - Gestion des compétences pour les NPCs
 * 
 * Cette classe gère la sélection automatique des compétences pour les NPCs
 * selon les règles D&D et les spécificités de chaque classe.
 */
class NPCSkills
{
    /**
     * Compétences D&D disponibles
     */
    public static function getAvailableSkills()
    {
        return [
            'Acrobaties' => 'Dextérité',
            'Arcanes' => 'Intelligence',
            'Athlétisme' => 'Force',
            'Discrétion' => 'Dextérité',
            'Dressage' => 'Sagesse',
            'Escamotage' => 'Dextérité',
            'Histoire' => 'Intelligence',
            'Intimidation' => 'Charisme',
            'Investigation' => 'Intelligence',
            'Médecine' => 'Sagesse',
            'Nature' => 'Intelligence',
            'Perception' => 'Sagesse',
            'Perspicacité' => 'Sagesse',
            'Persuasion' => 'Charisme',
            'Religion' => 'Intelligence',
            'Représentation' => 'Charisme',
            'Survie' => 'Sagesse',
            'Tromperie' => 'Charisme'
        ];
    }

    /**
     * Compétences recommandées par classe
     */
    public static function getClassRecommendedSkills()
    {
        return [
            'Barbare' => ['Athlétisme', 'Intimidation', 'Nature', 'Perception', 'Survie'],
            'Barde' => ['Acrobaties', 'Arcanes', 'Escamotage', 'Intimidation', 'Investigation', 'Médecine', 'Nature', 'Perception', 'Perspicacité', 'Religion', 'Représentation', 'Survie'],
            'Clerc' => ['Histoire', 'Médecine', 'Perspicacité', 'Religion'],
            'Druide' => ['Arcanes', 'Médecine', 'Nature', 'Perception', 'Religion', 'Survie'],
            'Guerrier' => ['Acrobaties', 'Athlétisme', 'Intimidation', 'Nature', 'Perception', 'Survie'],
            'Magicien' => ['Arcanes', 'Histoire', 'Investigation', 'Médecine', 'Religion'],
            'Moine' => ['Acrobaties', 'Athlétisme', 'Histoire', 'Investigation', 'Religion', 'Survie'],
            'Paladin' => ['Athlétisme', 'Intimidation', 'Médecine', 'Perspicacité', 'Religion'],
            'Rôdeur' => ['Athlétisme', 'Investigation', 'Nature', 'Perception', 'Survie'],
            'Roublard' => ['Acrobaties', 'Athlétisme', 'Discrétion', 'Escamotage', 'Intimidation', 'Investigation', 'Perception', 'Perspicacité', 'Représentation', 'Survie'],
            'Ensorceleur' => ['Arcanes', 'Intimidation', 'Médecine', 'Perspicacité', 'Religion'],
            'Occultiste' => ['Arcanes', 'Intimidation', 'Investigation', 'Nature', 'Religion']
        ];
    }

    /**
     * Nombre de compétences maîtrisées par classe
     */
    public static function getClassSkillCount()
    {
        return [
            'Barbare' => 2,
            'Barde' => 3,
            'Clerc' => 2,
            'Druide' => 2,
            'Guerrier' => 2,
            'Magicien' => 2,
            'Moine' => 2,
            'Paladin' => 2,
            'Rôdeur' => 3,
            'Roublard' => 4,
            'Ensorceleur' => 2,
            'Occultiste' => 2
        ];
    }

    /**
     * Sélectionner automatiquement les compétences pour un NPC
     * 
     * @param string $className Nom de la classe
     * @param int $npcId ID du NPC (pour éviter les doublons)
     * @return array Liste des compétences sélectionnées
     */
    public static function selectSkillsForNPC($className, $npcId = null)
    {
        $recommendedSkills = self::getClassRecommendedSkills()[$className] ?? [];
        $skillCount = self::getClassSkillCount()[$className] ?? 2;
        
        if (empty($recommendedSkills)) {
            return [];
        }
        
        // Sélectionner aléatoirement le nombre requis de compétences
        $selectedSkills = [];
        $availableSkills = $recommendedSkills;
        
        for ($i = 0; $i < min($skillCount, count($availableSkills)); $i++) {
            $randomIndex = array_rand($availableSkills);
            $selectedSkills[] = $availableSkills[$randomIndex];
            unset($availableSkills[$randomIndex]);
            $availableSkills = array_values($availableSkills); // Réindexer
        }
        
        return $selectedSkills;
    }

    /**
     * Ajouter des compétences à un NPC
     * 
     * @param int $npcId ID du NPC
     * @param array $skills Liste des compétences à ajouter
     * @return bool Succès de l'opération
     */
    public static function addSkillsToNPC($npcId, $skills)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $addedSkills = 0;
            
            foreach ($skills as $skillName) {
                // Vérifier si la compétence n'est pas déjà assignée
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM npc_skills WHERE npc_id = ? AND skill_name = ?");
                $checkStmt->execute([$npcId, $skillName]);
                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists['count'] == 0) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO npc_skills (npc_id, skill_name, proficiency_bonus, is_proficient, is_expertise, is_active, learned_at)
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $insertStmt->execute([
                        $npcId, 
                        $skillName, 
                        2, // Bonus de maîtrise de base
                        1, // Maîtrisé
                        0  // Pas d'expertise
                    ]);
                    $addedSkills++;
                }
            }
            
            error_log("Debug NPCSkills::addSkillsToNPC - Added " . $addedSkills . " skills to NPC " . $npcId);
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout des compétences au PNJ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les compétences d'un NPC
     * 
     * @param int $npcId ID du NPC
     * @return array Liste des compétences
     */
    public static function getNPCSkills($npcId)
    {
        $pdo = \Database::getInstance()->getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT skill_name, proficiency_bonus, is_proficient, is_expertise
                FROM npc_skills
                WHERE npc_id = ? AND is_active = 1
                ORDER BY skill_name
            ");
            $stmt->execute([$npcId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des compétences du PNJ: " . $e->getMessage());
            return [];
        }
    }
}
?>
