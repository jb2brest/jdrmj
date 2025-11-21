<?php
/**
 * Classe pour générer automatiquement des noms de PNJ
 * en fonction de leurs caractéristiques (race, classe, background)
 */

class NpcNameGenerator
{
    private static $firstNames = [
        'elfe' => [
            'Aerendil', 'Lirael', 'Thalion', 'Elenwe', 'Faelar', 'Nymira', 'Calenor', 'Sylthas', 'Vaelis', 'Elaria',
            'Thirion', 'Maelis', 'Arannis', 'Yavanna', 'Lorandel', 'Serelith', 'Eryndor', 'Naeris', 'Velion', 'Althaea',
            'Syrandel', 'Caelir', 'Miralith', 'Thalindra', 'Elion', 'Nyssara', 'Vaelion', 'Liraen', 'Faelwyn', 'Elarion'
        ],
        'mage' => [
            'Alzareth', 'Merion', 'Valthor', 'Ysara', 'Cedryn', 'Thamior', 'Zephira', 'Ordan', 'Elowen', 'Galdrin',
            'Nerissa', 'Malrik', 'Iskander', 'Virelia', 'Dorian', 'Selwyn', 'Azemar', 'Caldris', 'Ysolde', 'Tharion',
            'Esmira', 'Kaelith', 'Morwen', 'Zareth', 'Elsinor', 'Vireon', 'Althric', 'Lysandra', 'Elaran', 'Quenlor'
        ],
        'guerrier' => [
            'Baldric', 'Seraphin', 'Kaelen', 'Rowgar', 'Thrain', 'Elric', 'Maegor', 'Brynden', 'Garrick', 'Alaric',
            'Isolde', 'Thorne', 'Cedric', 'Varron', 'Edran', 'Galen', 'Roderic', 'Ysarn', 'Kaelis', 'Brannor',
            'Talia', 'Durnan', 'Elira', 'Halric', 'Fenric', 'Sigrun', 'Avelyn', 'Tharic', 'Maelis', 'Roneth'
        ],
        'noble' => [
            'Velisar', 'Ysandre', 'Malveris', 'Elsinne', 'Thalor', 'Virellia', 'Caelric', 'Lysanor', 'Orlien', 'Seraphia',
            'Drelan', 'Ismira', 'Vaelith', 'Thandor', 'Elenys', 'Morian', 'Caldrith', 'Nyssara', 'Vireon', 'Alzaria',
            'Thalindra', 'Zepharion', 'Lorien', 'Ysaria', 'Maelric', 'Elarion', 'Serelis', 'Vaelis', 'Tharion', 'Elowyn'
        ],
        'feerique' => [
            'Nylieth', 'Faerwyn', 'Elindra', 'Sylvaris', 'Caelith', 'Mirenor', 'Thalwyn', 'Elaria', 'Vaelira', 'Lyselle',
            'Aerendyl', 'Nymira', 'Serelith', 'Elenwe', 'Vaelion', 'Calenor', 'Thirion', 'Maelis', 'Arannis', 'Yavanna',
            'Lorandel', 'Eryndor', 'Naeris', 'Velion', 'Althaea', 'Syrandel', 'Caelir', 'Miralith', 'Thalindra', 'Elion'
        ],
        'dragon' => [
            'Zarion', 'Valthorax', 'Draconis', 'Myrgal', 'Thazur', 'Kaelzor', 'Virex', 'Azarion', 'Thalgrin', 'Morzeth',
            'Ysarnax', 'Caldrion', 'Zepharion', 'Eldrith', 'Tharion', 'Malzeth', 'Orzakar', 'Vaelgrin', 'Nyssor', 'Galzeth',
            'Thandorax', 'Elzuron', 'Virexar', 'Kaelgrin', 'Zarionis', 'Morgral', 'Azarion', 'Thalzor', 'Valthor', 'Eldrakar'
        ],
        'exotique' => [
            'Azemir', 'Thalios', 'Virelia', 'Caldran', 'Ysoria', 'Elzeth', 'Morian', 'Zephira', 'Alzareth', 'Nyssara',
            'Tharion', 'Lysandra', 'Vaelion', 'Cedryn', 'Ordan', 'Elowen', 'Galdrin', 'Nerissa', 'Malrik', 'Iskander',
            'Vireon', 'Althric', 'Ysolde', 'Thamior', 'Dorian', 'Selwyn', 'Azemar', 'Caldris', 'Elaran', 'Quenlor'
        ],
        'servant' => [
            'Alda', 'Brisel', 'Toman', 'Yselle', 'Gervaise', 'Lorette', 'Darnel', 'Maudric', 'Elsin', 'Roder',
            'Sybelle', 'Guilhem', 'Tilda', 'Osric', 'Mirelle', 'Jorren', 'Alwine', 'Beric', 'Ysolde', 'Fendrel',
            'Marla', 'Cedric', 'Thalia', 'Gaspard', 'Renna', 'Hobrin', 'Elric', 'Ysarn', 'Melka', 'Doriane',
            'Brann', 'Odric', 'Lienor'
        ],
        'cuisinier' => [
            'Benoît', 'Mirel', 'Guislaine', 'Tavin', 'Rosel', 'Jarnot', 'Elber', 'Maelis', 'Gerva', 'Thamiel',
            'Odette', 'Brannor', 'Ysandre', 'Colric', 'Lysa', 'Durnan', 'Elira', 'Halric', 'Fenric', 'Sigrun',
            'Avelyn', 'Tharic', 'Maelis', 'Roneth', 'Tilda', 'Gaspard', 'Renna', 'Hobrin', 'Elric', 'Ysarn'
        ],
        'marchand' => [
            'Varnel', 'Ysara', 'Cedryn', 'Thamior', 'Zephira', 'Ordan', 'Elowen', 'Galdrin', 'Nerissa', 'Malrik',
            'Iskander', 'Virelia', 'Dorian', 'Selwyn', 'Azemar', 'Caldris', 'Ysolde', 'Tharion', 'Esmira', 'Kaelith',
            'Morwen', 'Zareth', 'Elsinor', 'Vireon', 'Althric', 'Lysandra', 'Elaran', 'Quenlor', 'Brisel', 'Toman'
        ],
        'scribe' => [
            'Alzareth', 'Merion', 'Valthor', 'Ysara', 'Cedryn', 'Thamior', 'Zephira', 'Ordan', 'Elowen', 'Galdrin',
            'Nerissa', 'Malrik', 'Iskander', 'Virelia', 'Dorian', 'Selwyn', 'Azemar', 'Caldris', 'Ysolde', 'Tharion',
            'Esmira', 'Kaelith', 'Morwen', 'Zareth', 'Elsinor', 'Vireon', 'Althric', 'Lysandra', 'Elaran', 'Quenlor'
        ],
        'artisan' => [
            'Baldric', 'Seraphin', 'Kaelen', 'Rowgar', 'Thrain', 'Elric', 'Maegor', 'Brynden', 'Garrick', 'Alaric',
            'Isolde', 'Thorne', 'Cedric', 'Varron', 'Edran', 'Galen', 'Roderic', 'Ysarn', 'Kaelis', 'Brannor',
            'Talia', 'Durnan', 'Elira', 'Halric', 'Fenric', 'Sigrun', 'Avelyn', 'Tharic', 'Maelis', 'Roneth'
        ]
    ];
    
    private static $lastNames = [
        'elfe' => [
            'Sylvariel', 'Elaranor', 'Thalindor', 'Vaelwyn', 'Calenmir',
            'Nyssariel', 'Lorandelis', 'Faelthas', 'Eryndoril', 'Altharion'
        ],
        'mage' => [
            'Zarandel', 'Iskaveth', 'Thamioris', 'Virellan', 'Azemarion',
            'Caldrithas', 'Ysolmire', 'Selvaran', 'Morwenar', 'Quenloris'
        ],
        'guerrier' => [
            'Brannorvald', 'Kaelthorn', 'Durnanric', 'Tharicson', 'Halgrin',
            'Fenrickar', 'Sigrundal', 'Avelmarch', 'Ronethal', 'Cedrivar'
        ],
        'noble' => [
            'Velisarion', 'Ysandrith', 'Malveran', 'Thaloriel', 'Virellith',
            'Caelricar', 'Lysanoré', 'Orlienval', 'Seraphion', 'Drelanir'
        ],
        'feerique' => [
            'Faerwynne', 'Elindralis', 'Sylvariselle', 'Thalwynor', 'Vaelirae',
            'Lyselith', 'Aerendyll', 'Serelithar', 'Nyliethis', 'Calenoré'
        ],
        'dragon' => [
            'Valthorax', 'Draconir', 'Thazurion', 'Kaelzorak', 'Azarionis',
            'Thalgrinor', 'Morzethal', 'Ysarnaxor', 'Galzethar', 'Eldrakarion'
        ],
        'exotique' => [
            'Azemir', 'Thalios', 'Virelia', 'Caldran', 'Ysoria', 'Elzeth', 'Morian', 'Zephira', 'Alzareth', 'Nyssara',
            'Tharion', 'Lysandra', 'Vaelion', 'Cedryn', 'Ordan', 'Elowen', 'Galdrin', 'Nerissa', 'Malrik', 'Iskander',
            'Vireon', 'Althric', 'Ysolde', 'Thamior', 'Dorian', 'Selwyn', 'Azemar', 'Caldris', 'Elaran', 'Quenlor'
        ],
        'servant' => [
            'Brisefeuille', 'Tarnelot', 'Mireval', 'Dorneval', 'Chapelain',
            'Guilberteau', 'Roselune', 'Varnier', 'Lormont', 'Sauveterre'
        ],
        'cuisinier' => [
            'Fourneau', 'Painchaud', 'Becquet', 'Tartelune', 'Grisépice',
            'Marmelot', 'Rôtelard', 'Cuvelier', 'Bouchardon', 'Saulnier'
        ],
        'marchand' => [
            'Chandebise', 'Valargent', 'Tasselain', 'Brocart', 'Deniard',
            'Foirelune', 'Merceron', 'Grosvenor', 'Tirelune', 'Baillemarché'
        ],
        'scribe' => [
            'Plumargent', 'Scriptel', 'Lettrier', 'Calepin', 'Parcheminier',
            'Clairval', 'Sceaufort', 'Archivon', 'Vermeil', 'Lignac'
        ],
        'artisan' => [
            'Forgeclaire', 'Marteval', 'Cuiron', 'Tremblacier', 'Boisvert',
            'Tannegarde', 'Fayard', 'Riveton', 'Martelune', 'Chaudronnier'
        ]
    ];
    
    /**
     * Détermine la catégorie de nom en fonction des caractéristiques du PNJ
     * 
     * @param int $race_id ID de la race
     * @param int $class_id ID de la classe
     * @param int|null $background_id ID du background
     * @return string Catégorie de nom
     */
    public static function determineCategory($race_id, $class_id, $background_id = null)
    {
        // Récupérer les noms de la race, classe et background
        $race = null;
        $class = null;
        $background = null;
        
        try {
            $race = Race::findById($race_id);
        } catch (Exception $e) {
            error_log("Erreur récupération race: " . $e->getMessage());
        }
        
        try {
            $class = Classe::findById($class_id);
        } catch (Exception $e) {
            error_log("Erreur récupération classe: " . $e->getMessage());
        }
        
        if ($background_id) {
            try {
                $background = Background::findById($background_id);
            } catch (Exception $e) {
                error_log("Erreur récupération background: " . $e->getMessage());
            }
        }
        
        $race_name = $race ? strtolower($race->name) : '';
        $class_name = $class ? strtolower($class->name) : '';
        $background_name = $background ? strtolower($background->name) : '';
        
        // Vérifier d'abord le background (priorité)
        if ($background) {
            if (stripos($background_name, 'servant') !== false || 
                stripos($background_name, 'domestique') !== false || 
                stripos($background_name, 'intendant') !== false) {
                return 'servant';
            }
            if (stripos($background_name, 'cuisinier') !== false || 
                stripos($background_name, 'boulanger') !== false || 
                stripos($background_name, 'tavernier') !== false) {
                return 'cuisinier';
            }
            if (stripos($background_name, 'marchand') !== false || 
                stripos($background_name, 'colporteur') !== false || 
                stripos($background_name, 'négociant') !== false) {
                return 'marchand';
            }
            if (stripos($background_name, 'scribe') !== false || 
                stripos($background_name, 'secrétaire') !== false || 
                stripos($background_name, 'messager') !== false) {
                return 'scribe';
            }
            if (stripos($background_name, 'artisan') !== false || 
                stripos($background_name, 'forgeron') !== false || 
                stripos($background_name, 'tanneur') !== false) {
                return 'artisan';
            }
            if (stripos($background_name, 'noble') !== false || 
                stripos($background_name, 'aristocrate') !== false) {
                return 'noble';
            }
        }
        
        // Vérifier la race
        if (stripos($race_name, 'elfe') !== false || 
            stripos($race_name, 'elf') !== false ||
            stripos($race_name, 'elv') !== false) {
            return 'elfe';
        }
        if (stripos($race_name, 'dragon') !== false || 
            stripos($race_name, 'draconique') !== false ||
            stripos($race_name, 'drakéide') !== false ||
            stripos($race_name, 'draconide') !== false) {
            return 'dragon';
        }
        if (stripos($race_name, 'fée') !== false || 
            stripos($race_name, 'féerique') !== false ||
            stripos($race_name, 'fey') !== false ||
            stripos($race_name, 'feywild') !== false) {
            return 'feerique';
        }
        
        // Vérifier la classe
        if (stripos($class_name, 'magicien') !== false || 
            stripos($class_name, 'sorcier') !== false ||
            stripos($class_name, 'ensorceleur') !== false ||
            stripos($class_name, 'barde') !== false ||
            stripos($class_name, 'clerc') !== false ||
            stripos($class_name, 'druide') !== false ||
            stripos($class_name, 'occultiste') !== false ||
            stripos($class_name, 'magie') !== false ||
            stripos($class_name, 'wizard') !== false ||
            stripos($class_name, 'sorcerer') !== false ||
            stripos($class_name, 'warlock') !== false) {
            return 'mage';
        }
        if (stripos($class_name, 'guerrier') !== false || 
            stripos($class_name, 'paladin') !== false ||
            stripos($class_name, 'rôdeur') !== false ||
            stripos($class_name, 'barbare') !== false ||
            stripos($class_name, 'fighter') !== false ||
            stripos($class_name, 'ranger') !== false ||
            stripos($class_name, 'barbarian') !== false) {
            return 'guerrier';
        }
        
        // Par défaut, utiliser une catégorie générique
        return 'exotique';
    }
    
    /**
     * Génère un prénom aléatoire pour une catégorie donnée
     * 
     * @param string $category Catégorie de nom
     * @return string Prénom généré
     */
    public static function generateFirstName($category)
    {
        if (!isset(self::$firstNames[$category])) {
            $category = 'exotique';
        }
        
        $names = self::$firstNames[$category];
        return $names[array_rand($names)];
    }
    
    /**
     * Génère un nom de famille aléatoire pour une catégorie donnée
     * 
     * @param string $category Catégorie de nom
     * @return string Nom de famille généré
     */
    public static function generateLastName($category)
    {
        if (!isset(self::$lastNames[$category])) {
            $category = 'exotique';
        }
        
        $names = self::$lastNames[$category];
        return $names[array_rand($names)];
    }
    
    /**
     * Génère un nom complet (prénom + nom) pour un PNJ
     * 
     * @param int $race_id ID de la race
     * @param int $class_id ID de la classe
     * @param int $background_id ID du background
     * @return string Nom complet généré
     */
    public static function generateFullName($race_id, $class_id, $background_id)
    {
        $category = self::determineCategory($race_id, $class_id, $background_id);
        $firstName = self::generateFirstName($category);
        $lastName = self::generateLastName($category);
        
        return $firstName . ' ' . $lastName;
    }
    
    /**
     * Génère plusieurs suggestions de noms
     * 
     * @param int $race_id ID de la race
     * @param int $class_id ID de la classe
     * @param int $background_id ID du background
     * @param int $count Nombre de suggestions (défaut: 5)
     * @return array Liste de noms complets
     */
    public static function generateSuggestions($race_id, $class_id, $background_id, $count = 5)
    {
        $category = self::determineCategory($race_id, $class_id, $background_id);
        $suggestions = [];
        
        for ($i = 0; $i < $count; $i++) {
            $firstName = self::generateFirstName($category);
            $lastName = self::generateLastName($category);
            $suggestions[] = $firstName . ' ' . $lastName;
        }
        
        // Supprimer les doublons
        $suggestions = array_unique($suggestions);
        
        // Si on n'a pas assez de suggestions uniques, en générer plus
        while (count($suggestions) < $count && count($suggestions) < 20) {
            $firstName = self::generateFirstName($category);
            $lastName = self::generateLastName($category);
            $name = $firstName . ' ' . $lastName;
            if (!in_array($name, $suggestions)) {
                $suggestions[] = $name;
            }
        }
        
        return array_slice($suggestions, 0, $count);
    }
}

