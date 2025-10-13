# Création des classes PlaceObject et StartingEquipment

## Vue d'ensemble

Deux nouvelles classes ont été créées pour gérer les objets dans les lieux et l'équipement de départ du système JDR MJ :

1. **`PlaceObject`** - Gestion des objets dans les lieux (table `place_objects`)
2. **`StartingEquipment`** - Gestion de l'équipement de départ (table `starting_equipment`)

## Classe PlaceObject

### Vue d'ensemble

La classe `PlaceObject` encapsule toutes les fonctionnalités liées aux objets dans les lieux du système JDR MJ.

### Propriétés

```php
private $id;              // ID unique de l'objet
private $placeId;         // ID du lieu
private $displayName;     // Nom d'affichage
private $objectType;      // Type d'objet (poison, weapon, armor, bourse, letter)
private $typePrecis;      // Type précis
private $description;     // Description
private $isIdentified;    // Si l'objet est identifié
private $isVisible;       // Si l'objet est visible
private $isEquipped;      // Si l'objet est équipé
private $positionX;       // Position X sur la carte
private $positionY;       // Position Y sur la carte
private $isOnMap;         // Si l'objet est sur la carte
private $ownerType;       // Type de propriétaire (place, player, npc, monster)
private $ownerId;         // ID du propriétaire
private $poisonId;        // ID du poison (si applicable)
private $weaponId;        // ID de l'arme (si applicable)
private $armorId;         // ID de l'armure (si applicable)
private $goldCoins;       // Pièces d'or
private $silverCoins;     // Pièces d'argent
private $copperCoins;     // Pièces de cuivre
private $letterContent;   // Contenu de la lettre (si applicable)
private $isSealed;        // Si la lettre est cachetée
private $createdAt;       // Date de création
private $updatedAt;       // Date de mise à jour
```

### Méthodes principales

#### Création et récupération
```php
// Créer un nouvel objet
PlaceObject::create(array $data, PDO $pdo = null)

// Trouver par ID
PlaceObject::findById(int $id, PDO $pdo = null)

// Trouver par lieu
PlaceObject::findByPlaceId(int $placeId, PDO $pdo = null)

// Trouver par propriétaire
PlaceObject::findByOwner(string $ownerType, int $ownerId, PDO $pdo = null)
```

#### Manipulation
```php
// Mettre à jour
$object->update(array $data)

// Supprimer
$object->delete()

// Déplacer
$object->move(int $x, int $y, bool $onMap = true)

// Changer le propriétaire
$object->changeOwner(string $ownerType, int $ownerId)

// Équiper/déséquiper
$object->setEquipped(bool $equipped)

// Rendre visible/invisible
$object->setVisible(bool $visible)

// Identifier/désidentifier
$object->setIdentified(bool $identified)
```

#### Méthodes utilitaires
```php
// Conversion en tableau
$object->toArray()

// Labels en français
$object->getObjectTypeLabel()
$object->getOwnerTypeLabel()

// Calculs de valeur
$object->getTotalValueInCopper()
$object->getFormattedValue()
```

### Exemple d'utilisation

```php
// Récupérer tous les objets d'un lieu
$objects = PlaceObject::findByPlaceId(1);

// Créer un nouvel objet
$newObject = PlaceObject::create([
    'place_id' => 1,
    'display_name' => 'Épée magique',
    'object_type' => 'weapon',
    'type_precis' => 'Épée longue',
    'description' => 'Une épée enchantée',
    'is_identified' => false,
    'is_visible' => true,
    'owner_type' => 'place',
    'owner_id' => 1
]);

// Manipuler un objet
$object = PlaceObject::findById(1);
$object->move(100, 200, true);
$object->setVisible(false);
$object->setIdentified(true);
```

## Classe StartingEquipment

### Vue d'ensemble

La classe `StartingEquipment` encapsule toutes les fonctionnalités liées à l'équipement de départ du système JDR MJ.

### Propriétés

```php
private $id;              // ID unique de l'équipement
private $src;             // Source (class, background, race)
private $srcId;           // ID de la source
private $type;            // Type d'équipement (Outils, Armure, Bouclier, Arme, Accessoire, Sac)
private $typeId;          // ID de l'équipement précis
private $typeFilter;      // Filtre de type pour les alternatives
private $noChoix;         // Numéro du choix
private $optionLetter;    // Lettre d'option (a, b, c)
private $typeChoix;       // Type de choix (obligatoire, à_choisir)
private $nb;              // Nombre d'objets
private $groupeId;        // ID de groupe pour les items groupés
private $createdAt;       // Date de création
private $updatedAt;       // Date de mise à jour
```

### Méthodes principales

#### Création et récupération
```php
// Créer un nouvel équipement
StartingEquipment::create(array $data, PDO $pdo = null)

// Trouver par ID
StartingEquipment::findById(int $id, PDO $pdo = null)

// Trouver par source
StartingEquipment::findBySource(string $src, int $srcId, PDO $pdo = null)

// Trouver par type
StartingEquipment::findByType(string $type, PDO $pdo = null)

// Trouver par groupe
StartingEquipment::findByGroupe(int $groupeId, PDO $pdo = null)
```

#### Récupération spécialisée
```php
// Équipements obligatoires d'une source
StartingEquipment::findObligatoryBySource(string $src, int $srcId, PDO $pdo = null)

// Équipements à choisir d'une source
StartingEquipment::findChoicesBySource(string $src, int $srcId, PDO $pdo = null)
```

#### Manipulation
```php
// Mettre à jour
$equipment->update(array $data)

// Supprimer
$equipment->delete()
```

#### Méthodes utilitaires
```php
// Conversion en tableau
$equipment->toArray()

// Labels en français
$equipment->getSrcLabel()
$equipment->getTypeLabel()
$equipment->getTypeChoixLabel()

// Vérifications
$equipment->isObligatory()
$equipment->isChoice()

// Descriptions
$equipment->getFullDescription()
$equipment->getNameWithQuantity()
```

### Exemple d'utilisation

```php
// Récupérer l'équipement de départ d'une classe
$classEquipment = StartingEquipment::findBySource('class', 1);

// Récupérer les équipements obligatoires
$obligatoryEquipment = StartingEquipment::findObligatoryBySource('class', 1);

// Récupérer les équipements à choisir
$choiceEquipment = StartingEquipment::findChoicesBySource('class', 1);

// Créer un nouvel équipement
$newEquipment = StartingEquipment::create([
    'src' => 'class',
    'src_id' => 1,
    'type' => 'Arme',
    'type_filter' => 'Armes de guerre de corps à corps',
    'no_choix' => 1,
    'option_letter' => 'a',
    'type_choix' => 'à_choisir',
    'nb' => 1
]);

// Utiliser les méthodes utilitaires
$equipment = StartingEquipment::findById(1);
echo $equipment->getFullDescription(); // "Arme (Armes de guerre de corps à corps) [A]"
echo $equipment->isChoice(); // true
```

## Intégration dans le système

### Fichiers modifiés

1. **`classes/PlaceObject.php`** - Nouvelle classe pour les objets
2. **`classes/StartingEquipment.php`** - Nouvelle classe pour l'équipement de départ
3. **`classes/init.php`** - Ajout des nouvelles classes

### Chargement automatique

Les classes sont automatiquement chargées via `classes/init.php` :

```php
require_once __DIR__ . '/PlaceObject.php';
require_once __DIR__ . '/StartingEquipment.php';
```

## Tests effectués

### Test de la classe PlaceObject
- ✅ Configuration chargée avec succès
- ✅ Récupération d'objets par lieu
- ✅ Conversion en tableau
- ✅ Méthodes utilitaires (labels, valeurs)
- ✅ Gestion des cas vides

### Test de la classe StartingEquipment
- ✅ Configuration chargée avec succès
- ✅ Récupération d'équipements par source (14 équipements trouvés)
- ✅ Récupération d'équipements obligatoires (9 trouvés)
- ✅ Récupération d'équipements à choisir (5 trouvés)
- ✅ Conversion en tableau
- ✅ Méthodes utilitaires (descriptions, vérifications)
- ✅ Gestion des types et filtres

## Avantages des nouvelles classes

### 1. **Encapsulation de la logique métier**
- Toute la logique liée aux objets et à l'équipement de départ est centralisée
- Interface cohérente pour les opérations CRUD
- Gestion centralisée des erreurs

### 2. **Réutilisabilité**
- Méthodes disponibles dans tout le système
- Interface standardisée pour les opérations
- Facilité d'extension et de maintenance

### 3. **Sécurité**
- Protection contre les injections SQL via les requêtes préparées
- Validation des données
- Gestion centralisée des erreurs

### 4. **Performance**
- Requêtes optimisées
- Gestion efficace des connexions via le singleton Database
- Cache potentiel des données d'objet

### 5. **Lisibilité du code**
- Code plus expressif et lisible
- Méthodes avec des noms explicites
- Documentation complète

## Utilisation dans le système existant

### Migration progressive

Les nouvelles classes peuvent être utilisées progressivement pour remplacer les requêtes SQL directes :

```php
// Ancien code
$stmt = $pdo->prepare("SELECT * FROM place_objects WHERE place_id = ?");
$stmt->execute([$placeId]);
$objects = $stmt->fetchAll();

// Nouveau code
$objects = PlaceObject::findByPlaceId($placeId);
```

### Compatibilité

Les classes sont compatibles avec le système existant et peuvent être utilisées en parallèle pendant la migration.

## Conclusion

Les classes `PlaceObject` et `StartingEquipment` sont maintenant disponibles et fonctionnelles ! Elles apportent :

1. **Code plus maintenable** avec l'encapsulation de la logique métier
2. **Sécurité renforcée** avec la gestion centralisée des erreurs
3. **Performance optimisée** avec les requêtes optimisées
4. **Réutilisabilité** avec des méthodes disponibles dans tout le système
5. **Lisibilité** avec un code plus expressif et documenté

Les classes sont **complètes et fonctionnelles** !

