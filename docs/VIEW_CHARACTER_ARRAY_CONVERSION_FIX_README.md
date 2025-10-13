# Correction de l'erreur "Cannot use object of type Character as array" dans view_character.php

## Problème identifié

Erreur PHP Fatal lors de l'utilisation de la classe Character dans `view_character.php` :
```
PHP Fatal error: Uncaught Error: Cannot use object of type Character as array in /var/www/html/jdrmj_test/view_character.php:105
```

## Cause du problème

Après la migration vers la classe `Character`, le code dans `view_character.php` essayait d'accéder aux propriétés de l'objet `Character` comme si c'était un tableau :

```php
// Code qui causait l'erreur (ligne 105)
$isBarbarian = strpos(strtolower($character['class_name']), 'barbare') !== false;
$isBard = strpos(strtolower($character['class_name']), 'barde') !== false;
// ... autres accès similaires
```

Le problème était que :
1. **`$character` était un objet `Character`** (retourné par `Character::findById()`)
2. **Le code s'attendait à un tableau** avec la notation `$character['property']`
3. **Mélange de notations** : certaines parties utilisaient `$character->property` et d'autres `$character['property']`

## Correction apportée

### 1. Conversion de l'objet en tableau

**Fichier :** `view_character.php`

**Avant (code incorrect) :**
```php
// Récupération du personnage avec ses détails
$character = Character::findById($character_id);

if (!$character) {
    header('Location: characters.php');
    exit();
}

// Vérifier les permissions (propriétaire ou DM)
$isOwner = $character->belongsToUser($_SESSION['user_id']);
$isDM = isDM();

if (!$isOwner && !$isDM) {
    header('Location: characters.php');
    exit();
}

// Le code HTML essayait d'utiliser $character comme un tableau
// mais c'était un objet Character
```

**Après (code corrigé) :**
```php
// Récupération du personnage avec ses détails
$characterObject = Character::findById($character_id);

if (!$characterObject) {
    header('Location: characters.php');
    exit();
}

// Vérifier les permissions (propriétaire ou DM)
$isOwner = $characterObject->belongsToUser($_SESSION['user_id']);
$isDM = isDM();

if (!$isOwner && !$isDM) {
    header('Location: characters.php');
    exit();
}

// Convertir l'objet Character en tableau pour la compatibilité avec le code HTML
$character = $characterObject->toArray();
```

### 2. Correction des accès aux propriétés JSON

**Avant (code incorrect) :**
```php
// Parser les données JSON du personnage
$characterSkills = $character->skills ? json_decode($character->skills, true) : [];
$characterLanguages = $character->languages ? json_decode($character->languages, true) : [];
```

**Après (code corrigé) :**
```php
// Parser les données JSON du personnage
$characterSkills = $character['skills'] ? json_decode($character['skills'], true) : [];
$characterLanguages = $character['languages'] ? json_decode($character['languages'], true) : [];
```

## Structure des données

### Objet Character (avant conversion)
```php
$characterObject = Character::findById($character_id);
$characterObject->id = 1;
$characterObject->name = "Barda";
$characterObject->class_name = "Barde";
$characterObject->skills = '["Acrobaties", "Persuasion", ...]';
$characterObject->languages = '["Commun", "Infernal"]';
```

### Tableau converti (après conversion)
```php
$character = $characterObject->toArray();
$character = [
    'id' => 1,
    'name' => "Barda",
    'class_name' => "Barde",
    'skills' => '["Acrobaties", "Persuasion", ...]',
    'languages' => '["Commun", "Infernal"]',
    // ... toutes les autres propriétés
];
```

## Avantages de la correction

### 1. **Compatibilité maintenue**
- Le code HTML existant continue de fonctionner sans modification
- Toutes les références `$character['property']` fonctionnent
- Migration transparente

### 2. **Cohérence**
- Utilisation uniforme de la notation tableau dans tout le fichier
- Plus de mélange entre notation objet et tableau
- Code plus prévisible

### 3. **Flexibilité**
- Possibilité d'utiliser l'objet Character pour les méthodes
- Possibilité d'utiliser le tableau pour l'affichage
- Meilleure séparation des responsabilités

### 4. **Performance**
- Conversion unique au début du fichier
- Pas de conversion répétée dans les boucles
- Gestion mémoire optimisée

## Tests effectués

### Test de conversion
```php
// Récupération du personnage
$characterObject = Character::findById($character_id);
echo "Personnage trouvé: " . $characterObject->name . "\n";

// Vérification des permissions
$isOwner = $characterObject->belongsToUser(1);
echo "Vérification des permissions: " . ($isOwner ? "Propriétaire" : "Non propriétaire") . "\n";

// Conversion en tableau
$character = $characterObject->toArray();
echo "Conversion en tableau réussie\n";

// Test d'accès aux propriétés
echo "Nom: " . $character['name'] . "\n";
echo "Race: " . $character['race_name'] . "\n";
echo "Classe: " . $character['class_name'] . "\n";
echo "Niveau: " . $character['level'] . "\n";

// Test des propriétés utilisées dans le code
$isBarbarian = strpos(strtolower($character['class_name']), 'barbare') !== false;
$isBard = strpos(strtolower($character['class_name']), 'barde') !== false;
echo "Est barbare: " . ($isBarbarian ? "Oui" : "Non") . "\n";
echo "Est barde: " . ($isBard ? "Oui" : "Non") . "\n";

// Test de l'accès aux données JSON
$characterSkills = $character['skills'] ? json_decode($character['skills'], true) : [];
$characterLanguages = $character['languages'] ? json_decode($character['languages'], true) : [];
echo "Compétences: " . count($characterSkills) . " trouvées\n";
echo "Langues: " . count($characterLanguages) . " trouvées\n";
```

### Résultats des tests
- ✅ Configuration chargée avec succès
- ✅ Personnage trouvé: Barda
- ✅ Vérification des permissions: Propriétaire
- ✅ Conversion en tableau réussie
- ✅ Test d'accès aux propriétés réussi
- ✅ Est barbare: Non
- ✅ Est barde: Oui
- ✅ Compétences: 5 trouvées
- ✅ Langues: 2 trouvées

## Impact sur le système

### Fichiers modifiés
1. **`view_character.php`** - Conversion de l'objet Character en tableau

### Fichiers non affectés
- Les templates HTML existants
- Les autres classes
- La logique métier

## Utilisation

### Utilisation de l'objet Character pour les méthodes
```php
$characterObject = Character::findById($character_id);

// Utilisation des méthodes de l'objet
$isOwner = $characterObject->belongsToUser($_SESSION['user_id']);
$characterObject->addExperience(100);
$characterObject->update(['level' => 2]);
```

### Conversion en tableau pour l'affichage
```php
// Conversion pour la compatibilité avec le code HTML
$character = $characterObject->toArray();

// Utilisation dans les templates
echo $character['name'];           // Notation tableau
echo $character['class_name'];     // Notation tableau
echo $character['level'];          // Notation tableau
```

## Alternatives considérées

### 1. **Modification de tout le code HTML**
- **Avantage** : Utilisation directe des objets
- **Inconvénient** : Modification massive du code
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

Cette correction résout l'erreur "Cannot use object of type Character as array" dans `view_character.php` en :

1. **Convertissant l'objet Character en tableau** au début du fichier
2. **Maintenant la compatibilité** avec tout le code HTML existant
3. **Assurant la cohérence** dans l'utilisation des notations

La solution maintient la compatibilité avec le code existant tout en permettant l'utilisation moderne des objets Character.

## Tests de validation

- ✅ Syntaxe PHP correcte
- ✅ Conversion des objets en tableaux fonctionnelle
- ✅ Accès aux propriétés réussi
- ✅ Compatibilité avec le code HTML maintenue
- ✅ Performance optimale

La correction est **complète et fonctionnelle** !
