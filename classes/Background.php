<?php

require_once 'Database.php';

class Background {
    public $id;
    public $name;
    public $description;
    public $skill_proficiencies;
    public $tool_proficiencies;
    public $languages;
    public $feature;
    
    public function __construct($data = null) {
        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->name = $data['name'] ?? '';
            $this->description = $data['description'] ?? '';
            $this->skill_proficiencies = $data['skill_proficiencies'] ?? '';
            $this->tool_proficiencies = $data['tool_proficiencies'] ?? '';
            $this->languages = $data['languages'] ?? '';
            $this->feature = $data['feature'] ?? '';
        }
    }
    
    /**
     * Obtenir un historique par ID (retourne un tableau)
     * 
     * @param int $backgroundId ID de l'historique
     * @return array|null Données de l'historique
     */
    public static function getBackgroundById($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM backgrounds WHERE id = ?");
        $stmt->execute([$backgroundId]);
        return $stmt->fetch();
    }

    /**
     * Obtenir les compétences d'un historique
     * 
     * @param int $backgroundId ID de l'historique
     * @return array Tableau des compétences de l'historique
     */
    public static function getBackgroundProficiencies($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT skill_proficiencies, tool_proficiencies FROM backgrounds WHERE id = ?");
        $stmt->execute([$backgroundId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['skills' => [], 'tools' => []];
        }
        
        return [
            'skills' => json_decode($result['skill_proficiencies'], true) ?? [],
            'tools' => json_decode($result['tool_proficiencies'], true) ?? []
        ];
    }

    /**
     * Obtenir les langues d'un historique
     * 
     * @param int $backgroundId ID de l'historique
     * @return array Tableau des langues de l'historique
     */
    public static function getBackgroundLanguages($backgroundId)
    {
        $pdo = \Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT languages FROM backgrounds WHERE id = ?");
        $stmt->execute([$backgroundId]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['languages']) {
            return [];
        }
        
        return json_decode($result['languages'], true) ?? [];
    }

    /**
     * Trouve un background par son ID
     */
    public static function findById($id) {
        if (!$id) {
            return null;
        }
        
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("
            SELECT id, name, description, skill_proficiencies, 
                   tool_proficiencies, languages, feature
            FROM backgrounds 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new self($data) : null;
    }
    
    /**
     * Récupère les compétences de maîtrise du background
     */
    public function getSkillProficiencies() {
        if ($this->skill_proficiencies) {
            return json_decode($this->skill_proficiencies, true) ?: [];
        }
        return [];
    }
    
    /**
     * Récupère les outils de maîtrise du background
     */
    public function getToolProficiencies() {
        if ($this->tool_proficiencies) {
            return json_decode($this->tool_proficiencies, true) ?: [];
        }
        return [];
    }
    
    /**
     * Récupère les langues du background
     */
    public function getLanguages() {
        if ($this->languages) {
            $languages = json_decode($this->languages, true) ?: [];
            
            // Traiter les descriptions génériques
            $processedLanguages = [];
            foreach ($languages as $lang) {
                if ($lang === 'deux de votre choix') {
                    $processedLanguages[] = 'Nain';
                    $processedLanguages[] = 'Halfelin';
                } elseif ($lang === 'une langue de votre choix') {
                    $processedLanguages[] = 'Elfique';
                } else {
                    $processedLanguages[] = $lang;
                }
            }
            
            return $processedLanguages;
        }
        return [];
    }
    
    /**
     * Convertit l'objet en tableau
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'skill_proficiencies' => $this->skill_proficiencies,
            'tool_proficiencies' => $this->tool_proficiencies,
            'languages' => $this->languages,
            'feature' => $this->feature
        ];
    }
}
