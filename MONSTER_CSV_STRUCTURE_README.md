# Structure Réelle du Fichier CSV des Monstres

## Description
Ce document décrit la structure réelle du fichier `aidednddata/monstre.csv` et les corrections apportées pour l'import dans la base de données.

## Problème Identifié

### Erreur Initiale
```
SQLSTATE[HY000]: General error: 1366 Incorrect decimal value: ' neutre bon' for column 'challenge_rating' at row 1
```

### Cause
Le code d'import supposait une structure de colonnes incorrecte :
- **Attendu** : `csv_id, name, type, size, challenge_rating, hit_points, armor_class`
- **Réel** : Structure complètement différente avec 21 colonnes

## Structure Réelle du CSV

### En-têtes des Colonnes
```csv
Id,Nom,Type,Taille,Alignement,CA,PV,Force,Dexterite,Constitution,Intelligence,Sagesse,Charisme,Competences,Jets de sauvegarde,Immunités aux dégâts,Résistances aux dégâts,Immunités aux états,Sens,Langues,FP
```

### Mapping des Colonnes
| Index | Nom CSV | Description | Utilisation |
|-------|---------|-------------|-------------|
| 0 | `Id` | Identifiant unique | `csv_id` |
| 1 | `Nom` | Nom du monstre | `name` |
| 2 | `Type` | Type de créature | `type` |
| 3 | `Taille` | Taille (P, M, G, TG, Gig) | `size` |
| 4 | `Alignement` | Alignement moral | Non utilisé |
| 5 | `CA` | Classe d'Armure | `armor_class` |
| 6 | `PV` | Points de Vie | `hit_points` |
| 7-19 | `Force` à `Langues` | Statistiques et capacités | Non utilisées |
| 20 | `FP` | Facteur de Puissance (Challenge Rating) | `challenge_rating` |

## Exemples de Données

### Ligne 1 : Aarakocra
```csv
0,Aarakocra,Humanoïde (aarakocra) , M, neutre bon, 12,13 (3d8),10 (+0),14 (+2),10 (+0),11 (+0),12 (+1),11 (+0), Perception +5,,,,, Perception passive 15," aérien, aarakocra", 1/4 (50 PX)
```

**Extraction :**
- **csv_id** : `0`
- **name** : `Aarakocra`
- **type** : `Humanoïde (aarakocra)`
- **size** : `M`
- **challenge_rating** : `1/4 (50 PX)` → `1/4`
- **hit_points** : `13 (3d8)` → `13`
- **armor_class** : `12` → `12`

### Ligne 2 : Aboleth
```csv
1,Aboleth,Aberration , G, loyal mauvais, 17 (armure naturelle),135 (18d10 + 36),21 (+5),9 (-1),15 (+2),18 (+4),15 (+2),18 (+4)," Histoire +12, Perception +10"," Con +6, Int +8, Sag +6",,,," vision dans le noir 36 m, Perception passive 20"," profond, télépathie 36 m", 10 (5900 PX)
```

**Extraction :**
- **csv_id** : `1`
- **name** : `Aboleth`
- **type** : `Aberration`
- **size** : `G`
- **challenge_rating** : `10 (5900 PX)` → `10`
- **hit_points** : `135 (18d10 + 36)` → `135`
- **armor_class** : `17 (armure naturelle)` → `17`

## Fonctions de Nettoyage Implémentées

### 1. `cleanChallengeRating($fp_value)`
```php
function cleanChallengeRating($fp_value) {
    $fp_value = trim($fp_value);
    
    // Extraire le CR numérique (ex: "1/4 (50 PX)" -> "1/4")
    if (preg_match('/^(\d+\/\d+|\d+)/', $fp_value, $matches)) {
        return $matches[1];
    }
    
    // Si pas de match, retourner la valeur brute nettoyée
    return $fp_value;
}
```

**Exemples :**
- `"1/4 (50 PX)"` → `"1/4"`
- `"10 (5900 PX)"` → `"10"`
- `" neutre bon"` → `"neutre bon"`

### 2. `extractHitPoints($pv_value)`
```php
function extractHitPoints($pv_value) {
    $pv_value = trim($pv_value);
    
    // Extraire le nombre de points de vie (ex: "13 (3d8)" -> 13)
    if (preg_match('/^(\d+)/', $pv_value, $matches)) {
        return (int)$matches[1];
    }
    
    // Valeur par défaut si pas de match
    return 0;
}
```

**Exemples :**
- `"13 (3d8)"` → `13`
- `"135 (18d10 + 36)"` → `135`
- `"1 (1d4 - 1)"` → `1`

### 3. `extractArmorClass($ca_value)`
```php
function extractArmorClass($ca_value) {
    $ca_value = trim($ca_value);
    
    // Extraire la CA numérique (ex: "12 (armure naturelle)" -> 12)
    if (preg_match('/^(\d+)/', $ca_value, $matches)) {
        return (int)$matches[1];
    }
    
    // Valeur par défaut si pas de match
    return 10;
}
```

**Exemples :**
- `"12"` → `12`
- `"17 (armure naturelle)"` → `17`
- `"14 (armure naturelle), 11 lorsqu'il est à terre"` → `14`

## Code d'Import Corrigé

### Structure de la Requête
```php
$stmt = $pdo->prepare("INSERT INTO dnd_monsters (csv_id, name, type, size, challenge_rating, hit_points, armor_class) VALUES (?, ?, ?, ?, ?, ?, ?)");
```

### Boucle d'Import
```php
while (($data = fgetcsv($handle)) !== false) {
    if (count($data) >= 21) { // Le CSV a 21 colonnes
        // Extraire et nettoyer les valeurs
        $csv_id = $data[0];
        $name = $data[1];
        $type = $data[2];
        $size = $data[3];
        
        // Challenge Rating (dernière colonne FP)
        $challenge_rating = cleanChallengeRating($data[20]); // FP
        
        // Points de vie (colonne PV)
        $hit_points = extractHitPoints($data[6]); // PV
        
        // Classe d'armure (colonne CA)
        $armor_class = extractArmorClass($data[5]); // CA
        
        // Nettoyer les valeurs
        $name = trim($name);
        $type = trim($type);
        $size = trim($size);
        
        // Insérer seulement si les données sont valides
        if (!empty($name) && !empty($type)) {
            $stmt->execute([
                $csv_id, $name, $type, $size,
                $challenge_rating, $hit_points, $armor_class
            ]);
            $count++;
        }
    }
}
```

## Validation des Données

### Vérifications Effectuées
1. **Nombre de colonnes** : Vérification que le CSV a au moins 21 colonnes
2. **Données obligatoires** : `name` et `type` ne doivent pas être vides
3. **Nettoyage** : Suppression des espaces en début/fin de chaîne
4. **Extraction** : Parsing des valeurs complexes (PV, CA, CR)

### Valeurs par Défaut
- **hit_points** : `0` si impossible d'extraire
- **armor_class** : `10` si impossible d'extraire
- **challenge_rating** : Valeur brute si impossible d'extraire

## Gestion des Erreurs

### Types d'Erreurs Gérées
1. **Format de données** : Extraction des valeurs numériques depuis des chaînes complexes
2. **Données manquantes** : Valeurs par défaut pour les champs numériques
3. **Validation** : Vérification de l'intégrité des données avant insertion

### Robustesse
- **Regex** : Extraction robuste des valeurs numériques
- **Fallbacks** : Valeurs par défaut en cas d'échec
- **Validation** : Vérification des données avant insertion

## Tests et Validation

### Vérification des Données Importées
```sql
-- Compter les monstres importés
SELECT COUNT(*) FROM dnd_monsters;

-- Vérifier la structure des données
SELECT csv_id, name, type, size, challenge_rating, hit_points, armor_class 
FROM dnd_monsters 
LIMIT 5;

-- Vérifier les valeurs extrêmes
SELECT MIN(hit_points), MAX(hit_points), MIN(armor_class), MAX(armor_class) 
FROM dnd_monsters;
```

### Validation des Fonctions de Nettoyage
```php
// Test des fonctions de nettoyage
echo cleanChallengeRating("1/4 (50 PX)"); // Doit retourner "1/4"
echo extractHitPoints("13 (3d8)"); // Doit retourner 13
echo extractArmorClass("17 (armure naturelle)"); // Doit retourner 17
```

## Extensions Futures

### Améliorations Possibles
1. **Support des fractions** : Conversion des CR fractionnaires en décimal
2. **Parsing des dés** : Extraction des formules de dés (3d8, 18d10 + 36)
3. **Validation avancée** : Vérification des plages de valeurs
4. **Logs détaillés** : Enregistrement des transformations de données

### Support d'Autres Colonnes
- **Alignement** : Stockage de l'alignement moral
- **Compétences** : Parsing des bonus de compétences
- **Langues** : Stockage des langues connues
- **Sens** : Stockage des sens spéciaux

---

**Statut** : ✅ **STRUCTURE CSV CORRIGÉE**

La structure réelle du fichier CSV des monstres a été identifiée et corrigée. Les fonctions de nettoyage des données permettent maintenant un import robuste et fiable des monstres dans la base de données.












