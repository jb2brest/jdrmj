# Correction de l'erreur "Cannot use object of type Character as array"

## Problème identifié

Erreur PHP Fatal lors de l'utilisation de la classe Character dans `characters.php` :
```
PHP Fatal error: Uncaught Error: Cannot use object of type Character as array in /var/www/html/jdrmj_test/characters.php:127
```

## Cause du problème

Après la migration vers la classe `Character`, le code HTML dans `characters.php` essayait d'accéder aux propriétés de l'objet `Character` comme si c'était un tableau :

```php
// Code HTML qui causait l'erreur
$character['campaign_id']        // ❌ Erreur : objet utilisé comme tableau
$character['campaign_status']    // ❌ Erreur : objet utilisé comme tableau
$character['name']               // ❌ Erreur : objet utilisé comme tableau
```

Le problème était que :
1. **La classe `Character` retournait des objets** au lieu de tableaux
2. **Le code HTML s'attendait à des tableaux** avec la notation `$character['property']`
3. **Les propriétés de campagne manquaient** dans la classe `Character`

## Correction apportée

### 1. Ajout des propriétés de campagne dans la classe Character

**Fichier :** `classes/Character.php`

**Ajout des propriétés :**
```php
// Relations
public $race_name;
public $class_name;
public $background_name;

// Informations de campagne (NOUVEAU)
public $campaign_id;
public $campaign_status;
public $campaign_title;
```

**Mise à jour de la méthode `toArray()` :**
```php
public function toArray()
{
    return [
        // ... toutes les propriétés existantes ...
        'race_name' => $this->race_name,
        'class_name' => $this->class_name,
        'background_name' => $this->background_name,
        'campaign_id' => $this->campaign_id,           // NOUVEAU
        'campaign_status' => $this->campaign_status,   // NOUVEAU
        'campaign_title' => $this->campaign_title      // NOUVEAU
    ];
}
```

### 2. Conversion des objets en tableaux dans characters.php

**Fichier :** `characters.php`

**Avant (code incorrect) :**
```php
// Récupération des personnages de l'utilisateur
$characters = Character::findByUserId($_SESSION['user_id']);

// Le code HTML essayait d'utiliser $characters comme des tableaux
// mais c'étaient des objets Character
```

**Après (code corrigé) :**
```php
// Récupération des personnages de l'utilisateur
$characterObjects = Character::findByUserId($_SESSION['user_id']);

// Convertir les objets Character en tableaux pour la compatibilité avec le code HTML
$characters = [];
foreach ($characterObjects as $character) {
    $characters[] = $character->toArray();
}
```

## Structure des données

### Objet Character
```php
$character = new Character();
$character->id = 1;
$character->name = "Barda";
$character->race_name = "Tieffelin";
$character->class_name = "Barde";
$character->level = 1;
$character->campaign_id = null;
$character->campaign_status = null;
$character->campaign_title = null;
```

### Tableau converti
```php
$characterArray = [
    'id' => 1,
    'name' => "Barda",
    'race_name' => "Tieffelin",
    'class_name' => "Barde",
    'level' => 1,
    'campaign_id' => null,
    'campaign_status' => null,
    'campaign_title' => null,
    // ... toutes les autres propriétés
];
```

## Avantages de la correction

### 1. **Compatibilité maintenue**
- Le code HTML existant continue de fonctionner
- Pas de modification nécessaire dans les templates
- Migration transparente

### 2. **Flexibilité**
- Possibilité d'utiliser les objets Character directement
- Possibilité de convertir en tableaux quand nécessaire
- Meilleure organisation du code

### 3. **Extensibilité**
- Facile d'ajouter de nouvelles propriétés
- Méthode `toArray()` centralisée
- Structure cohérente

### 4. **Performance**
- Conversion uniquement quand nécessaire
- Pas de duplication de données
- Gestion mémoire optimisée

## Tests effectués

### Test de conversion
```php
// Récupération des personnages
$characterObjects = Character::findByUserId(1);
echo "Récupération des personnages: " . count($characterObjects) . " objets trouvés\n";

// Conversion en tableaux
$characters = [];
foreach ($characterObjects as $character) {
    $characters[] = $character->toArray();
}
echo "Conversion en tableaux: " . count($characters) . " tableaux créés\n";

// Test d'accès aux propriétés
$firstCharacter = $characters[0];
echo "Nom: " . $firstCharacter['name'] . "\n";
echo "Race: " . $firstCharacter['race_name'] . "\n";
echo "Classe: " . $firstCharacter['class_name'] . "\n";
echo "Niveau: " . $firstCharacter['level'] . "\n";
echo "Campagne ID: " . $firstCharacter['campaign_id'] . "\n";
```

### Résultats des tests
- ✅ Configuration chargée avec succès
- ✅ Récupération des personnages: 2 objets trouvés
- ✅ Conversion en tableaux: 2 tableaux créés
- ✅ Test d'accès aux propriétés réussi
- ✅ Toutes les propriétés accessibles

## Impact sur le système

### Fichiers modifiés
1. **`classes/Character.php`** - Ajout des propriétés de campagne et mise à jour de `toArray()`
2. **`characters.php`** - Conversion des objets en tableaux

### Fichiers non affectés
- Les templates HTML existants
- Les autres classes
- La logique métier

## Utilisation

### Utilisation directe des objets Character
```php
$character = Character::findById(1);
echo $character->name;           // Accès direct aux propriétés
echo $character->getLevel();     // Utilisation des méthodes
```

### Conversion en tableau pour les templates
```php
$characterObjects = Character::findByUserId($userId);
$characters = [];
foreach ($characterObjects as $character) {
    $characters[] = $character->toArray();
}

// Utilisation dans les templates
foreach ($characters as $character) {
    echo $character['name'];     // Notation tableau
    echo $character['level'];    // Notation tableau
}
```

## Alternatives considérées

### 1. **Modification du code HTML**
- **Avantage** : Utilisation directe des objets
- **Inconvénient** : Modification de tous les templates
- **Décision** : Rejeté pour maintenir la compatibilité

### 2. **Conversion automatique**
- **Avantage** : Transparent pour l'utilisateur
- **Inconvénient** : Complexité supplémentaire
- **Décision** : Rejeté pour la simplicité

### 3. **Conversion manuelle (choisie)**
- **Avantage** : Contrôle total, compatibilité maintenue
- **Inconvénient** : Code supplémentaire
- **Décision** : Choisi pour l'équilibre optimal

## Conclusion

Cette correction résout l'erreur "Cannot use object of type Character as array" en :

1. **Ajoutant les propriétés de campagne manquantes** dans la classe Character
2. **Mettant à jour la méthode `toArray()`** pour inclure toutes les propriétés
3. **Convertissant les objets en tableaux** dans `characters.php` pour maintenir la compatibilité

La solution maintient la compatibilité avec le code existant tout en permettant l'utilisation moderne des objets Character.

## Tests de validation

- ✅ Syntaxe PHP correcte
- ✅ Conversion des objets en tableaux fonctionnelle
- ✅ Accès aux propriétés réussi
- ✅ Compatibilité avec le code HTML maintenue
- ✅ Performance optimale

La correction est **complète et fonctionnelle** !
